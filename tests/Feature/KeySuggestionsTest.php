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

class KeySuggestionsTest extends TestCase
{
    use RefreshDatabase;

    public function testIdentifiersFromOtherTablesAreSuggestedWithTheirName(): void
    {
        $user = User::query()->create(['name' => 'Ana', 'email' => 'ana@example.com', 'password' => Hash::make('secret')]);
        $this->actingAs($user);
        $tax = Taxonomy::query()->create(['name' => 'EBA 4.2', 'original_name' => 'eba42', 'path' => 'eba42', 'folder' => 'eba42']);
        $m = FactModule::query()->create(['taxonomy_id' => $tax->id, 'period' => '2026-12-31', 'module_name' => 'Large exposures',
            'module_path' => 'www.eba.europa.eu/eu/fr/xbrl/crr/fws/corep/4.2/mod/corep_le.xsd', 'groups' => '{}', 'user_id' => $user->id]);
        $c27 = FactHeader::query()->create(['module_id' => $m->id, 'table_path' => 'www.eba.europa.eu/eu/fr/xbrl/crr/fws/corep/4.2/tab/c_27.00/c_27.00.xsd']);
        $c29 = FactHeader::query()->create(['module_id' => $m->id, 'table_path' => 'www.eba.europa.eu/eu/fr/xbrl/crr/fws/corep/4.2/tab/c_29.00/c_29.00.xsd']);
        $key = fn ($dim) => json_encode([$dim => '*', 'typ' => 'eba_typ:qCC']);
        $row = fn ($h, $code, $value, $raw, $metric = null) => FactTable::query()->create(['fact_header_id' => $h->id, 'cr_code' => $code, 'cr_sheet_code' => '000',
            'string_value' => $value, 'xbrl_context_key_raw' => $raw, 'xbrl_context_key' => '', 'metric' => $metric, 'user_id' => $user->id]);
        $row($c27, 'c011r1', 'CP-001', $key('eba_dim_4.2:qINC'));
        $row($c27, 'c021r1', 'Demo Banka d.d.', '{}', 'eba_met:qCDF');
        $row($c27, 'c050r1', 'true', '{}', 'eba_met:qEBD');
        $row($c29, 'c020r1', 'GRP-9', $key('eba_dim_4.2:qGCC'));

        $items = $this->getJson('/table/key-suggestions?instance_id='.$m->id.'&dimension=eba_dim_4.2:qINC&typ=eba_typ:qCC')->assertOk()->json('items');

        $this->assertSame(['CP-001', 'GRP-9'], array_column($items, 'value'));        // same dimension first, then the same typed domain
        $this->assertSame('Demo Banka d.d.', $items[0]['label']);                    // the name on its row, not the flag
        $this->assertSame('C_27.00', $items[0]['table']);
        $this->assertNull($items[1]['label']);
    }
}
