<?php

namespace Tests\Feature;

use App\Model\FactHeader;
use App\Model\FactModule;
use App\Model\FactTable;
use App\Model\Taxonomy;
use App\User;
use Begicf\AReportPro\Models\DpmEntity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class FilingSettingsAndPreflightTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private FactModule $instance;

    protected function setUp(): void
    {
        parent::setUp();

        if (! class_exists(DpmEntity::class)) {
            $this->markTestSkipped('areport-pro is not installed.');
        }

        $this->user = User::query()->create(['name' => 'Alice', 'email' => 'alice@example.com', 'password' => Hash::make('secret')]);
        $this->actingAs($this->user);

        $taxonomy = Taxonomy::query()->create(['name' => 'EBA 4.2', 'original_name' => 'eba42', 'path' => 'eba42', 'folder' => 'eba42']);
        DB::table('taxonomies')->where('id', $taxonomy->id)->update(['active' => true]);

        $entity = DpmEntity::query()->create(['lei' => '5493001KJTIIGC8Y1R12', 'name' => 'Example Bank', 'is_default' => true]);

        $this->instance = FactModule::query()->create([
            'taxonomy_id' => $taxonomy->id,
            'entity_id' => $entity->id,
            'period' => '2026-12-31',
            'module_name' => 'Own Funds',
            'module_path' => 'www.eba.europa.eu/eu/fr/xbrl/crr/fws/corep/4.2/mod/corep_of.xsd',
            'groups' => '{}',
        ]);

        // One monetary fact in C 01.00 (dp109056), as the form stores it.
        $header = FactHeader::query()->create([
            'module_id' => $this->instance->id,
            'table_path' => 'www.eba.europa.eu/eu/fr/xbrl/crr/fws/corep/4.2/tab/c_01.00/c_01.00.xsd',
            'cr_sheet_code_last' => '000',
        ]);
        FactTable::query()->create([
            'fact_header_id' => $header->id,
            'cr_code' => 'c0010r0010',
            'cr_sheet_code' => '000',
            'string_value' => '1500',
            'metric' => 'eba_met:qABJ',
            'xbrl_context_key' => '',
            'user_id' => $this->user->id,
            'xbrl_context_key_raw' => json_encode([
                'metric' => 'eba_met:qABJ',
                'eba_dim_4.0:qADT' => 'eba_qOR:qx2005',
                'eba_dim_4.0:qJLJ' => 'eba_qOR:qx2078',
                'eba_dim_4.0:qLNK' => 'eba_qAP:qx2135',
                '__meta' => ['fact_variable_id' => 109056],
            ]),
        ]);
    }

    private function settings(array $overrides = []): array
    {
        return array_merge([
            'instance_id' => $this->instance->id,
            'entity_id' => $this->instance->entity_id,
            'period' => '2026-12-31',
            'consolidation' => 'CON',
            'country' => 'FR',
            'base_currency' => 'EUR',
            'monetary_scale' => 1000,
            'decimals_monetary' => -3,
            'decimals_percentage' => 4,
            'decimals_integer' => 0,
            'decimals_decimal' => 2,
            'filing_indicators' => [],
        ], $overrides);
    }

    private function preflightCodes(string $format = 'csv'): array
    {
        $result = $this->postJson('/instance/preflight', ['instance_id' => $this->instance->id, 'format' => $format])->assertOk()->json();

        return [$result, array_column($result['checks'], 'code')];
    }

    public function testSettingsListTheModuleTemplatesWithTheirData(): void
    {
        $d = $this->getJson('/instance/settings?instance_id='.$this->instance->id)->assertOk()->json();

        $this->assertSame('4.1.0', $d['module']['version']);
        $this->assertTrue($d['templates']['C_01.00']['has_data']);
        $this->assertFalse($d['templates']['C_02.00']['has_data']);
        $this->assertSame('Example Bank', $d['entity']['name']);
    }

    public function testPreflightBlocksUntilTheFilingIsComplete(): void
    {
        [$result, $codes] = $this->preflightCodes();
        $this->assertFalse($result['ok']);
        $this->assertSame(['country', 'not_validated'], $codes);   // the entity has no country yet; never validated (warning)

        $this->postJson('/instance/settings', $this->settings())->assertOk();

        [$result, $codes] = $this->preflightCodes();
        $this->assertTrue($result['ok']);
        $this->assertSame(['not_validated'], $codes);   // a warning, not a blocker
        $this->assertMatchesRegularExpression('/^5493001KJTIIGC8Y1R12\.CON_FR_COREP040100_COREPOF_2026-12-31_\d{17}\.zip$/', $result['package']);

        // XML is allowed but flagged: EBA 4.x is remitted as xBRL-CSV.
        [$result, $codes] = $this->preflightCodes('xml');
        $this->assertTrue($result['ok']);
        $this->assertSame(['not_validated', 'xml_legacy'], $codes);
    }

    public function testPreflightCatchesDatesIndicatorsAndLei(): void
    {
        $this->postJson('/instance/settings', $this->settings(['period' => '2026-12-30']))->assertOk();
        $this->assertContains('refdate_month_end', $this->preflightCodes()[1]);

        $this->postJson('/instance/settings', $this->settings(['period' => '2025-12-31']))->assertOk();
        $this->assertContains('refdate_before_module', $this->preflightCodes()[1]);   // module 4.1.0 applies from 2026-03-31

        $this->postJson('/instance/settings', $this->settings(['filing_indicators' => ['C_01.00' => 'false', 'C_02.00' => 'true']]))->assertOk();
        [, $codes] = $this->preflightCodes();
        $this->assertContains('fi_false_with_data', $codes);
        $this->assertContains('fi_true_without_data', $codes);
        $this->assertContains('no_data', $codes);
        $this->assertSame(['C_01.00' => false, 'C_02.00' => true], $this->instance->fresh()->filing_indicators);

        DpmEntity::query()->whereKey($this->instance->entity_id)->update(['lei' => 'HWUPKR0MPOU8FGXBT395']);
        $this->assertContains('lei_invalid', $this->preflightCodes()[1]);
    }

    public function testAnotherUsersEntityCannotBeAssigned(): void
    {
        $other = DpmEntity::query()->withoutGlobalScopes()->create(['lei' => 'HWUPKR0MPOU8FGXBT394', 'name' => 'Not mine', 'user_id' => 999]);

        $this->postJson('/instance/settings', $this->settings(['entity_id' => $other->id]))->assertStatus(422);
    }

    public function testCsvExportCarriesTheSettingsAndTheEbaName(): void
    {
        $this->postJson('/instance/settings', $this->settings(['consolidation' => 'IND', 'monetary_scale' => 1000000]))->assertOk();

        $response = $this->post('/instance/export-csv', ['instance_id' => $this->instance->id])->assertOk();

        $file = $response->baseResponse->getFile()->getPathname();
        $this->assertMatchesRegularExpression(
            '/filename=5493001KJTIIGC8Y1R12\.IND_FR_COREP040100_COREPOF_2026-12-31_\d{17}\.zip/',
            (string) $response->headers->get('content-disposition')
        );

        $zip = new \ZipArchive();
        $zip->open($file);
        $contents = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $contents[basename($zip->getNameIndex($i))] = $zip->getFromIndex($i);
        }
        $zip->close();

        $this->assertStringContainsString("entityID,rs:5493001KJTIIGC8Y1R12.IND\n", $contents['parameters.csv']);
        $this->assertStringContainsString("dp109056,1500000000\n", $contents['c_01.00.csv']);   // 1 500 million
    }

    public function testExportIsRefusedWhenChecksFail(): void
    {
        // No country set → the pre-export check fails → no download.
        $this->post('/instance/export-csv', ['instance_id' => $this->instance->id])->assertStatus(422);
    }
}
