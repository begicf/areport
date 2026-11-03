<?php

namespace Tests\Feature;

use App\Model\Taxonomy;
use App\User;
use Begicf\AReportDpm\Models\Axis;
use Begicf\AReportDpm\Models\Cell;
use Begicf\AReportDpm\Models\DataPoint;
use Begicf\AReportDpm\Models\Dimension;
use Begicf\AReportDpm\Models\Domain;
use Begicf\AReportDpm\Models\DpmTable;
use Begicf\AReportDpm\Models\Framework;
use Begicf\AReportDpm\Models\Member;
use Begicf\AReportDpm\Models\Metric;
use Begicf\AReportDpm\Models\Ordinate;
use Begicf\AReportPro\Models\TaxonomyLabel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/** DPM Architect: what the server keeps consistent when a model is edited or deleted. */
class ArchitectEditingTest extends TestCase
{
    use RefreshDatabase;

    private const FOLDER = 'phpunit_architect';

    private Taxonomy $taxonomy;
    private Framework $framework;
    private DpmTable $table;
    private Axis $rows;

    protected function setUp(): void
    {
        parent::setUp();
        $this->requirePro();
        $this->actingAs(User::query()->create(['name' => 'Alice', 'email' => 'alice@example.com', 'password' => Hash::make('secret'), 'email_verified_at' => now()]));
        $this->taxonomy = Taxonomy::query()->create(['name' => 'Test', 'original_name' => self::FOLDER, 'path' => 'tax', 'folder' => self::FOLDER]);
        DB::table('taxonomies')->where('id', $this->taxonomy->id)->update(['active' => true]);
        $this->framework = Framework::query()->create(['taxonomy_id' => $this->taxonomy->id, 'source' => 'authored', 'code' => 'SPN', 'name' => 'SPN', 'regulator' => 'fba']);
        $this->table = DpmTable::query()->create(['taxonomy_id' => $this->taxonomy->id, 'framework_id' => $this->framework->id, 'source' => 'authored', 'code' => 'S_99.00', 'name' => 'Test table']);
        $this->rows = Axis::query()->create(['taxonomy_id' => $this->taxonomy->id, 'table_id' => $this->table->id, 'orientation' => 'y']);
    }

    protected function tearDown(): void
    {
        $dir = storage_path('app/public/tax/'.self::FOLDER);
        if (is_dir($dir)) {
            $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST);
            foreach ($files as $f) {
                $f->isDir() ? rmdir($f->getPathname()) : unlink($f->getPathname());
            }
            rmdir($dir);
        }
        parent::tearDown();
    }

    private function ordinate(array $attrs, array $meta = []): Ordinate
    {
        return Ordinate::query()->create($attrs + ['taxonomy_id' => $this->taxonomy->id, 'axis_id' => $this->rows->id,
            'code' => 'o'.substr(md5(uniqid('', true)), 0, 10), 'label' => 'Row', 'metadata' => $meta + ['source' => 'authored']]);
    }

    public function test_an_rc_code_is_unique_on_its_axis(): void
    {
        $first = $this->ordinate(['rc_code' => '0010', 'label' => 'Assets']);
        $url = '/pro/dpm-model/tables/'.$this->table->id.'/ordinates';

        $this->postJson($url, ['orientation' => 'y', 'rc_code' => '0010', 'label' => 'Again'])
            ->assertStatus(422)->assertJsonFragment(['message' => 'RC code 0010 is already used on this axis (Assets).']);
        $this->postJson($url, ['orientation' => 'x', 'rc_code' => '0010', 'label' => 'A column'])->assertOk();   // another axis

        $second = $this->ordinate(['rc_code' => '0020']);
        $this->putJson('/pro/dpm-model/ordinates/'.$second->id, ['rc_code' => '0010'])->assertStatus(422);
        $this->putJson('/pro/dpm-model/ordinates/'.$first->id, ['rc_code' => '0010', 'label' => 'Total assets'])->assertOk();   // its own code
        $this->assertSame('0020', $second->fresh()->rc_code);
    }

    public function test_deleting_a_coordinate_deletes_its_whole_subtree(): void
    {
        $top = $this->ordinate(['rc_code' => '0010']);
        $child = $this->ordinate(['rc_code' => '0020', 'parent_id' => $top->id, 'level' => 1]);
        $grandchild = $this->ordinate(['rc_code' => '0030', 'parent_id' => $child->id, 'level' => 2]);
        $other = $this->ordinate(['rc_code' => '0040']);

        $this->deleteJson('/pro/dpm-model/ordinates/'.$top->id)->assertOk();

        $this->assertSame([$other->id], Ordinate::query()->where('axis_id', $this->rows->id)->pluck('id')->all());
        $this->assertNull($grandchild->fresh());
    }

    public function test_a_dictionary_item_in_use_is_not_deleted(): void
    {
        $domain = Domain::query()->create(['taxonomy_id' => $this->taxonomy->id, 'source' => 'authored', 'regulator' => 'fba', 'code' => 'FBA_RS', 'name' => 'Risk sources']);
        $member = Member::query()->create(['taxonomy_id' => $this->taxonomy->id, 'source' => 'authored', 'regulator' => 'fba', 'domain_id' => $domain->id, 'code' => 'x1', 'name' => 'Internal']);
        $dimension = Dimension::query()->create(['taxonomy_id' => $this->taxonomy->id, 'source' => 'authored', 'regulator' => 'fba', 'domain_id' => $domain->id, 'code' => 'FBA_DIM1', 'name' => 'Risk source']);
        $metric = Metric::query()->create(['taxonomy_id' => $this->taxonomy->id, 'source' => 'authored', 'regulator' => 'fba', 'code' => 'fba_m1', 'name' => 'Number of incidents']);
        $unused = Metric::query()->create(['taxonomy_id' => $this->taxonomy->id, 'source' => 'authored', 'regulator' => 'fba', 'code' => 'fba_m10', 'name' => 'Unused']);
        $this->ordinate(['rc_code' => '0010'], ['metric' => 'fba_met:fba_m1']);
        $this->ordinate(['rc_code' => '0020'], ['dimensions' => ['FBA_DIM1' => 'FBA_RS:x1']]);

        $this->deleteJson('/pro/dictionary/metrics/'.$metric->id)->assertStatus(422)
            ->assertJsonFragment(['message' => 'The metric is used by S_99.00 (0010). Change those coordinates first.']);
        $this->deleteJson('/pro/dictionary/dimensions/'.$dimension->id)->assertStatus(422);
        $this->deleteJson('/pro/dictionary/members/'.$member->id)->assertStatus(422);
        $this->assertNotNull($metric->fresh());

        $this->deleteJson('/pro/dictionary/metrics/'.$unused->id)->assertOk();   // "fba_m1" is in its code, but it is not used
        $this->assertNull($unused->fresh());
    }

    public function test_deleting_a_table_removes_its_generated_files_labels_and_data_points(): void
    {
        $path = 'www.fba.ba/eu/fr/xbrl/crr/fws/spn/1.0/tab/s_99.00/s_99.00.xsd';
        $dir = storage_path('app/public/tax/'.self::FOLDER.'/'.dirname($path));
        mkdir($dir, 0775, true);
        file_put_contents($dir.'/s_99.00.xsd', '<schema/>');
        file_put_contents($dir.'/s_99.00-lab-en.xml', '<linkbase/>');
        $this->table->update(['metadata' => ['generated' => ['path' => $path]]]);
        $row = $this->ordinate(['rc_code' => '0010']);
        $point = DataPoint::query()->create(['taxonomy_id' => $this->taxonomy->id, 'table_id' => $this->table->id, 'code' => 'dpfba1']);
        Cell::query()->create(['taxonomy_id' => $this->taxonomy->id, 'table_id' => $this->table->id, 'row_ordinate_id' => $row->id, 'data_point_id' => $point->id]);
        TaxonomyLabel::query()->create(['taxonomy_id' => $this->taxonomy->id, 'regulator' => 'fba', 'language' => 'bs', 'label_role' => 'http://www.xbrl.org/2008/role/label',
            'table_code' => 'S_99.00', 'concept_id' => $row->code, 'concept_href' => 'x#y', 'label_text' => 'Red']);

        $this->deleteJson('/pro/dpm-model/tables/'.$this->table->id)->assertOk();

        $this->assertDirectoryDoesNotExist($dir);
        $this->assertSame(0, TaxonomyLabel::query()->where('table_code', 'S_99.00')->count());
        $this->assertNull($point->fresh());
    }

    public function test_deleting_a_module_removes_only_its_own_generated_files(): void
    {
        $base = storage_path('app/public/tax/'.self::FOLDER.'/www.fba.ba/eu/fr/xbrl/crr/fws/spn/1.0');
        mkdir($base.'/mod', 0775, true);
        mkdir($base.'/val', 0775, true);
        $mine = ['mod/spn_fba1.xsd', 'mod/spn_fba1-lab-en.xml', 'mod/spn_fba1-pre.xml', 'mod/spn_fba1.json', 'val/spn_fba1-rules.json', 'val/spn_fba1-rules.csv'];
        $others = ['mod/spn_fba10.xsd', 'mod/spn_fba1.1.xsd', 'val/spn_fba10-rules.json'];
        foreach (array_merge($mine, $others) as $f) {
            file_put_contents($base.'/'.$f, 'x');
        }
        $module = \Begicf\AReportDpm\Models\Module::query()->create(['taxonomy_id' => $this->taxonomy->id, 'framework_id' => $this->framework->id, 'source' => 'authored',
            'code' => 'SPN_FBA1', 'name' => 'Module', 'metadata' => ['generated' => ['path' => 'www.fba.ba/eu/fr/xbrl/crr/fws/spn/1.0/mod/spn_fba1.xsd']]]);

        $this->deleteJson('/pro/dpm-model/modules/'.$module->id)->assertOk();

        foreach ($mine as $f) {
            $this->assertFileDoesNotExist($base.'/'.$f);
        }
        foreach ($others as $f) {
            $this->assertFileExists($base.'/'.$f);
        }
    }

    public function test_an_own_table_has_labels_before_it_is_generated(): void
    {
        $this->ordinate(['rc_code' => '0010', 'label' => 'Number of incidents']);

        $rows = $this->getJson('/pro/dpm-model/tables/'.$this->table->id.'/labels')->assertOk()->json('rows');

        $this->assertSame(['S_99.00:Test table', 'Test table', 'Number of incidents'], array_column($rows, 'source_label'));
        $this->assertSame(['table', 'table', 'y'], array_column($rows, 'kind'));
        $this->assertSame('0010', $rows[2]['rc_code']);
    }

    public function test_an_empty_abstract_coordinate_is_not_generated(): void
    {
        $header = $this->ordinate(['rc_code' => '', 'label' => 'Empty header', 'is_abstract' => true]);
        $this->ordinate(['rc_code' => '0010', 'label' => 'Data']);

        $generator = (new \ReflectionClass(\Begicf\AReportPro\Services\DpmTaxonomyGenerator::class))->newInstanceWithoutConstructor();
        (new \ReflectionProperty($generator, 'warnings'))->setValue($generator, []);
        $axes = (new \ReflectionMethod($generator, 'axes'))->invoke($generator, $this->table);

        $this->assertSame(['Data'], array_column($axes['y'], 'label'));
        $this->assertStringContainsString('"Empty header" is abstract and has no coordinates under it', (new \ReflectionProperty($generator, 'warnings'))->getValue($generator)[0]);
        $this->assertNotNull($header->fresh());   // only left out of the taxonomy
    }
}
