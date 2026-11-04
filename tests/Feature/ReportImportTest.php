<?php

namespace Tests\Feature;

use App\Model\Entity;
use App\Model\FactHeader;
use App\Model\FactModule;
use App\Model\FactTable;
use App\Model\Taxonomy;
use App\Support\ReportFile;
use App\Support\XbrlInstanceImport;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/** Opening a report someone already has (EBA's xBRL-CSV and xBRL-XML samples) as a new instance. */
class ReportImportTest extends TestCase
{
    use RefreshDatabase;

    private string $samples;
    private string $root;

    protected function setUp(): void
    {
        parent::setUp();
        $this->samples = storage_path('app/public/sample_instances_4.2_hotfix/');
        $this->root = storage_path('app/public/tax/eba44/');
        if (! glob($this->samples.'xBRL-CSV/*_AE_*.zip') || ! is_file($this->root.'www.eba.europa.eu/eu/fr/xbrl/crr/fws/ae/4.2/mod/ae.xsd')) {
            $this->markTestSkipped('EBA 4.4 taxonomy and the 4.2 sample instances are not in storage.');
        }
        $this->actingAs(User::query()->create(['name' => 'Alice', 'email' => 'alice@example.com', 'password' => Hash::make('secret'), 'email_verified_at' => now()]));
    }

    private function sample(string $format, string $module): string
    {
        return glob($this->samples.($format === 'csv' ? 'xBRL-CSV/*_'.$module.'_*.zip' : 'xBRL-XML/*_'.$module.'_*.xbrl'))[0];
    }

    private function locate(): \Closure
    {
        return fn (string $url) => is_file($f = $this->root.preg_replace('#^https?://#', '', $url)) ? $f : null;
    }

    public function test_an_xbrl_csv_package_and_an_instance_read_the_same_way(): void
    {
        $csv = ReportFile::read($this->sample('csv', 'AE'), 'ae.zip', $this->locate());
        $xml = ReportFile::read($this->sample('xml', 'AE'), 'ae.xbrl', $this->locate());

        foreach ([$csv, $xml] as $report) {
            $this->assertSame('http://www.eba.europa.eu/eu/fr/xbrl/crr/fws/ae/4.2/mod/ae.xsd', $report['module']);
            $this->assertSame('DUMMYLEI123456789012', $report['lei']);
            $this->assertSame('CON', $report['consolidation']);
            $this->assertSame('EUR', $report['currency']);
            $this->assertSame(-3, $report['decimals']['monetary']);
            $this->assertTrue($report['indicators']['F_32.01']);
        }
        // F 34.00.c: the currency column is an explicit member, F 35.00: the CBC column a typed key.
        $currency = collect($csv['facts'])->first(fn ($f) => isset($f['explicit']['CUS']));
        $this->assertMatchesRegularExpression('/^eba_CU:[A-Z]{3}$/', $currency['explicit']['CUS']);
        $typed = collect($csv['facts'])->first(fn ($f) => isset($f['typed']['CBC']));
        $this->assertNotEmpty($typed['typed']['CBC']);
        $this->assertSame([], collect($csv['facts'])->filter(fn ($f) => isset($f['explicit']['CBC']))->all());
    }

    public function test_a_package_fills_the_open_table_of_the_form(): void
    {
        $report = ReportFile::read($this->sample('csv', 'FP'), 'fp.zip', $this->locate());
        $table = $this->root.'www.eba.europa.eu/eu/fr/xbrl/crr/fws/fp/4.2/tab/p_01.01/p_01.01.xsd';

        $result = XbrlInstanceImport::forTableFromReport($report['facts'], $table, [], 'eba44', 1000);

        $this->assertSame(164, $result['matched']);
    }

    public function test_a_report_package_becomes_a_new_instance(): void
    {
        $taxonomy = Taxonomy::query()->create(['name' => 'EBA 4.4', 'original_name' => 'eba44', 'path' => 'eba44', 'folder' => 'eba44']);
        Entity::query()->create(['user_id' => auth()->id(), 'lei' => 'DUMMYLEI123456789012', 'name' => 'Demo Bank', 'country' => 'BA']);
        $file = $this->sample('csv', 'AE');

        $start = $this->post('/instance/import-report', ['report' => new UploadedFile($file, basename($file), 'application/zip', null, true)], ['Accept' => 'application/json'])
            ->assertOk()->assertJson(['ok' => true]);

        // Filling runs once the response is out (the test kernel terminates right away).
        $status = $this->getJson($start->json('status'))->assertOk()->json();
        $this->assertSame('done', $status['state'], $status['error'] ?? '');
        $this->assertSame(23, $status['total']);
        $this->assertSame(0, $status['unmapped_count']);

        $instance = FactModule::query()->findOrFail($start->json('id'));
        $this->assertSame('Asset Encumbrance', $instance->module_name);
        $this->assertSame($taxonomy->id, $instance->taxonomy_id);
        $this->assertSame('www.eba.europa.eu/eu/fr/xbrl/crr/fws/ae/4.2/mod/ae.xsd', $instance->module_path);
        $this->assertSame('Demo Bank', $instance->entityRecord()->name);
        $this->assertSame(1, $instance->monetary_scale);   // the sample's amounts are not whole thousands
        $this->assertNotNull($instance->data_changed_at);
        $values = FactTable::query()->whereIn('fact_header_id', FactHeader::query()->where('module_id', $instance->id)->pluck('id'))->whereNotNull('metric')->count();
        $this->assertSame(1358, $values);   // every row of the package; 80 are a fact shown in two tables
    }

    public function test_a_file_for_a_module_that_is_not_uploaded_is_refused(): void
    {
        $xml = tempnam(sys_get_temp_dir(), 'inst');
        file_put_contents($xml, '<xbrli:xbrl xmlns:xbrli="http://www.xbrl.org/2003/instance" xmlns:link="http://www.xbrl.org/2003/linkbase" xmlns:xlink="http://www.w3.org/1999/xlink">'
            .'<link:schemaRef xlink:type="simple" xlink:href="http://www.example.com/fws/x/mod/x.xsd"/></xbrli:xbrl>');

        $this->post('/instance/import-report', ['report' => new UploadedFile($xml, 'x.xbrl', 'application/xml', null, true)], ['Accept' => 'application/json'])
            ->assertStatus(422)->assertJsonFragment(['error' => 'The module http://www.example.com/fws/x/mod/x.xsd is not in an uploaded taxonomy. Upload that taxonomy release first.']);
        $this->post('/instance/import-report', ['report' => UploadedFile::fake()->createWithContent('notes.txt', 'hello')], ['Accept' => 'application/json'])
            ->assertStatus(422);
        $this->assertSame(0, FactModule::query()->count());
        unlink($xml);
    }
}
