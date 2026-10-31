<?php

namespace Tests\Feature;

use App\Model\FactHeader;
use App\Model\FactModule;
use App\Model\FactTable;
use App\Model\Taxonomy;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class InstanceStatusTest extends TestCase
{
    use RefreshDatabase;

    private function makeInstance(): FactModule
    {
        $user = User::query()->create(['name' => 'Alice', 'email' => 'alice@example.com', 'password' => Hash::make('secret')]);
        $this->actingAs($user);
        $tax = Taxonomy::query()->create(['name' => 'EBA 4.2', 'original_name' => 'eba42', 'path' => 'eba42', 'folder' => 'eba42']);

        return FactModule::query()->create([
            'taxonomy_id' => $tax->id, 'period' => '2026-12-31', 'module_name' => 'Own Funds',
            'module_path' => 'www.eba.europa.eu/eu/fr/xbrl/crr/fws/corep/4.2/mod/corep_of.xsd',
            'groups' => json_encode([
                'C_01.00' => json_encode(['C_01.00' => ['eba_tC_01.00' => '/x/eba42/www.eba.europa.eu/eu/fr/xbrl/crr/fws/corep/4.2/tab/c_01.00/c_01.00.xsd']]),
                'C_02.00' => json_encode(['C_02.00' => ['eba_tC_02.00.a' => '/x/tab/c_02.00.a/c_02.00.a.xsd', 'eba_tC_02.00.b' => '/x/tab/c_02.00.b/c_02.00.b.xsd']]),
            ]),
            'data_changed_at' => now()->subHour(),
        ]);
    }

    public function testStatusFollowsDataValidationAndExport(): void
    {
        $m = $this->makeInstance();
        $this->assertSame('draft', $m->status()['key']);
        $this->assertSame('Not validated', $m->status()['label']);

        $m->stamp(['validated_at' => now(), 'validation_summary' => ['failed_errors' => 2, 'failed_warnings' => 1]]);
        $this->assertSame('errors', $m->fresh()->status()['key']);

        $m->stamp(['validated_at' => now(), 'validation_summary' => ['failed_errors' => 0, 'failed_warnings' => 3]]);
        $this->assertSame('warnings', $m->fresh()->status()['key']);

        $m->stamp(['exported_at' => now(), 'export_format' => 'csv']);
        $this->assertSame('Exported (CSV)', $m->fresh()->status()['label']);

        // An edit after export and validation puts it back to "changed since validation".
        $m->forceFill(['data_changed_at' => now()->addMinute()])->save();
        $this->assertSame('draft', $m->fresh()->status()['key']);
        $this->assertSame('Changed since validation', $m->fresh()->status()['label']);
    }

    public function testHomeCountsAndFiltersByStatus(): void
    {
        $m = $this->makeInstance();
        $m->stamp(['validated_at' => now(), 'validation_summary' => ['failed_errors' => 1]]);

        $all = $this->postJson('/areport/json')->assertOk()->json();
        $this->assertSame(1, $all['stats']['errors']);
        $this->assertSame('errors', $all['rows'][0]['status']);
        $this->assertSame('31.12.2026', $all['rows'][0]['period_label']);

        $this->assertSame(0, $this->postJson('/areport/json', ['status' => 'ready'])->json('total'));
        $this->assertSame(1, $this->postJson('/areport/json', ['status' => 'errors'])->json('total'));
    }

    public function testNavigatorReportsDataAndErrorsPerTableGroup(): void
    {
        $m = $this->makeInstance();
        $header = FactHeader::query()->create(['module_id' => $m->id, 'table_path' => 'www.eba.europa.eu/eu/fr/xbrl/crr/fws/corep/4.2/tab/c_01.00/c_01.00.xsd']);
        FactTable::query()->create(['fact_header_id' => $header->id, 'cr_code' => 'c0010r0010', 'cr_sheet_code' => '000', 'string_value' => '5', 'xbrl_context_key' => '', 'xbrl_context_key_raw' => '{}', 'user_id' => auth()->id()]);
        $m->stamp(['validated_at' => now(), 'validation_summary' => ['failed_errors' => 3, 'tables' => ['C_01.00' => 2, 'C_02.00.B' => 1]]]);

        $items = collect($this->getJson('/table/navigator?instance_id='.$m->id)->assertOk()->json('items'))->keyBy('name');

        $this->assertTrue($items['C_01.00']['has_data']);
        $this->assertSame(2, $items['C_01.00']['errors']);
        $this->assertFalse($items['C_02.00']['has_data']);
        $this->assertSame(['c_02.00.a', 'c_02.00.b'], $items['C_02.00']['tables']);
        $this->assertSame(1, $items['C_02.00']['errors']);
    }
}
