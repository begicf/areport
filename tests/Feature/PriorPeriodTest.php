<?php

namespace Tests\Feature;

use App\Model\FactAudit;
use App\Model\FactHeader;
use App\Model\FactModule;
use App\Model\FactTable;
use App\Model\Taxonomy;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PriorPeriodTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private FactModule $q3;
    private FactModule $q4;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::query()->create(['name' => 'Alice', 'email' => 'alice@example.com', 'password' => Hash::make('secret'), 'email_verified_at' => now()]);
        $this->actingAs($this->user);
        $tax = Taxonomy::query()->create(['name' => 'EBA 4.2', 'original_name' => 'eba42', 'path' => 'tax', 'folder' => 'eba42']);
        $make = fn ($period) => FactModule::query()->create(['taxonomy_id' => $tax->id, 'period' => $period, 'module_name' => 'Own Funds', 'groups' => '{}',
            'module_path' => 'www.eba.europa.eu/eu/fr/xbrl/crr/fws/corep/4.2/mod/corep_of.xsd', 'consolidation' => 'CON']);
        $this->q3 = $make('2026-09-30');
        $this->q4 = $make('2026-12-31');
        $h = FactHeader::query()->create(['module_id' => $this->q3->id, 'table_path' => 'www.eba.europa.eu/eu/fr/xbrl/crr/fws/corep/4.2/tab/c_01.00/c_01.00.xsd', 'cr_sheet_code_last' => '000']);
        foreach (['c0010r0010' => '5000', 'c0010r0015' => '4000'] as $cr => $v) {
            FactTable::query()->create(['fact_header_id' => $h->id, 'cr_code' => $cr, 'cr_sheet_code' => '000', 'string_value' => $v, 'metric' => 'eba_met:qABJ', 'xbrl_context_key' => '', 'xbrl_context_key_raw' => '{}', 'user_id' => $this->user->id]);
        }
        // Q4 already has one value of its own.
        $h4 = FactHeader::query()->create(['module_id' => $this->q4->id, 'table_path' => $h->table_path, 'cr_sheet_code_last' => '000']);
        FactTable::query()->create(['fact_header_id' => $h4->id, 'cr_code' => 'c0010r0010', 'cr_sheet_code' => '000', 'string_value' => '6000', 'metric' => 'eba_met:qABJ', 'xbrl_context_key' => '', 'xbrl_context_key_raw' => '{}', 'user_id' => $this->user->id]);
    }

    public function test_the_earlier_period_is_suggested(): void
    {
        $this->getJson('/table/prior-instances?instance_id='.$this->q4->id)
            ->assertOk()->assertJson(['suggested' => $this->q3->id]);
    }

    public function test_copy_fills_empty_cells_and_keeps_entered_ones(): void
    {
        $this->postJson('/table/copy-from', ['instance_id' => $this->q4->id, 'source_id' => $this->q3->id])
            ->assertOk()->assertJson(['added' => 1, 'changed' => 0, 'kept' => 1]);
        $values = FactTable::query()->whereIn('fact_header_id', $this->q4->factHeader()->pluck('id'))->pluck('string_value', 'cr_code');
        $this->assertSame(['c0010r0010' => '6000', 'c0010r0015' => '4000'], $values->sortKeys()->all());
        $this->assertSame(1, FactAudit::query()->where('fact_module_id', $this->q4->id)->count());

        $this->postJson('/table/copy-from', ['instance_id' => $this->q4->id, 'source_id' => $this->q3->id, 'overwrite' => true])
            ->assertOk()->assertJson(['changed' => 1]);
    }

    public function test_prior_values_of_a_table(): void
    {
        $this->getJson('/table/prior-values?instance_id='.$this->q4->id.'&source_id='.$this->q3->id.'&table_path=/x/www.eba.europa.eu/eu/fr/xbrl/crr/fws/corep/4.2/tab/c_01.00/c_01.00.xsd&sheet=000')
            ->assertOk()->assertJson(['values' => ['c0010r0010' => '5000', 'c0010r0015' => '4000']]);
    }

    public function test_another_module_or_users_instance_cannot_be_a_source(): void
    {
        $other = User::query()->create(['name' => 'Bob', 'email' => 'bob@example.com', 'password' => Hash::make('x'), 'email_verified_at' => now()]);
        $foreign = FactModule::query()->withoutGlobalScopes()->create(['taxonomy_id' => $this->q3->taxonomy_id, 'period' => '2026-06-30', 'module_name' => 'X', 'groups' => '{}',
            'module_path' => $this->q3->module_path, 'user_id' => $other->id]);
        $this->postJson('/table/copy-from', ['instance_id' => $this->q4->id, 'source_id' => $foreign->id])->assertNotFound();
    }
}
