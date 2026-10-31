<?php

namespace Tests\Feature;

use App\Model\FactAudit;
use App\Model\FactHeader;
use App\Model\FactModule;
use App\Model\FactTable;
use App\Model\Taxonomy;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class InstanceOwnershipAndSaveTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $email): User
    {
        return User::query()->create(['name' => $email, 'email' => $email, 'password' => Hash::make('secret')]);
    }

    private function taxonomy(string $folder, bool $active): Taxonomy
    {
        $taxonomy = Taxonomy::query()->create(['name' => strtoupper($folder), 'original_name' => $folder, 'path' => $folder, 'folder' => $folder]);
        DB::table('taxonomies')->where('id', $taxonomy->id)->update(['active' => $active]);

        return $taxonomy;
    }

    private function instanceFor(User $user, Taxonomy $taxonomy): FactModule
    {
        return FactModule::withoutGlobalScopes()->create([
            'user_id' => $user->id,
            'taxonomy_id' => $taxonomy->id,
            'period' => '2026-09-30',
            'module_name' => 'Own Funds',
            'module_path' => 'www.eba.europa.eu/eu/fr/xbrl/crr/fws/corep/4.0/mod/corep_of.xsd',
            'groups' => '{}',
        ]);
    }

    /** A form field as the reporting grid posts it: c<col>r<row>[value] + its context. */
    private function form(array $cells): string
    {
        $fields = [];
        foreach ($cells as $code => $value) {
            $fields[$code] = ['value' => $value, 'dim' => json_encode(['metric' => 'eba_met:mi53'])];
        }

        return http_build_query($fields);
    }

    public function testUsersOnlySeeTheirOwnInstances(): void
    {
        $tax = $this->taxonomy('eba40', true);
        $alice = $this->user('alice@example.com');
        $bob = $this->user('bob@example.com');
        $aliceInstance = $this->instanceFor($alice, $tax);
        $bobInstance = $this->instanceFor($bob, $tax);

        $rows = $this->actingAs($alice)->postJson('/areport/json')->assertOk()->json('rows');
        $this->assertSame([$aliceInstance->id], array_column($rows, 'id'));

        $this->actingAs($alice)->get('/table/history?id='.$bobInstance->id)->assertNotFound();
        $this->actingAs($alice)->postJson('/table/save', ['instance_id' => $bobInstance->id, 'table_data' => $this->form(['c010r010' => '5']), 'tab' => 'x.xsd'])
            ->assertNotFound();
        $this->assertSame(0, FactTable::query()->count());
    }

    public function testInstancesOfANonActiveTaxonomyStayListed(): void
    {
        $this->taxonomy('eba40', true);
        $old = $this->taxonomy('eba42', false);
        $alice = $this->user('alice@example.com');
        $instance = $this->instanceFor($alice, $old);

        $rows = $this->actingAs($alice)->postJson('/areport/json')->assertOk()->json('rows');

        $this->assertSame([$instance->id], array_column($rows, 'id'));
        $this->assertFalse($rows[0]['taxonomy_active']);
        $this->assertSame('EBA42', $rows[0]['taxonomy_name']);
    }

    public function testSaveKeepsZeroDeletesClearedCellsAndAuditsEachChange(): void
    {
        $tax = $this->taxonomy('eba40', true);
        $alice = $this->user('alice@example.com');
        $instance = $this->instanceFor($alice, $tax);
        $tab = 'eba40/www.eba.europa.eu/eu/fr/xbrl/crr/fws/corep/4.0/tab/c_01.00/c_01.00.xsd';

        $this->actingAs($alice)->postJson('/table/save', [
            'instance_id' => $instance->id,
            'table_data' => $this->form(['c010r010' => '1500', 'c010r020' => '0', 'c010r030' => '']),
            'tab' => $tab,
        ])->assertOk()->assertJson(['ok' => true, 'changes' => 2]);

        $header = FactHeader::query()->firstOrFail();
        $this->assertSame('www.eba.europa.eu/eu/fr/xbrl/crr/fws/corep/4.0/tab/c_01.00/c_01.00.xsd', $header->table_path);
        $this->assertSame(['c010r010' => '1500', 'c010r020' => '0'], FactTable::query()->orderBy('cr_code')->pluck('string_value', 'cr_code')->all());

        // Change one value, clear another: the cleared one is removed, both are audited.
        $this->actingAs($alice)->postJson('/table/save', [
            'instance_id' => $instance->id,
            'table_data' => $this->form(['c010r010' => '1750', 'c010r020' => '', 'c010r030' => '']),
            'tab' => $tab,
        ])->assertOk()->assertJson(['changes' => 2]);

        $this->assertSame(['c010r010' => '1750'], FactTable::query()->pluck('string_value', 'cr_code')->all());

        $trail = FactAudit::query()->orderBy('id')->get(['cr_code', 'old_value', 'new_value', 'user_id'])->toArray();
        $this->assertSame([
            ['cr_code' => 'c010r010', 'old_value' => null, 'new_value' => '1500', 'user_id' => $alice->id],
            ['cr_code' => 'c010r020', 'old_value' => null, 'new_value' => '0', 'user_id' => $alice->id],
            ['cr_code' => 'c010r010', 'old_value' => '1500', 'new_value' => '1750', 'user_id' => $alice->id],
            ['cr_code' => 'c010r020', 'old_value' => '0', 'new_value' => null, 'user_id' => $alice->id],
        ], $trail);

        // Saving the same state again changes nothing and adds no audit rows.
        $this->actingAs($alice)->postJson('/table/save', [
            'instance_id' => $instance->id,
            'table_data' => $this->form(['c010r010' => '1750', 'c010r020' => '']),
            'tab' => $tab,
        ])->assertOk()->assertJson(['changes' => 0]);
        $this->assertSame(4, FactAudit::query()->count());

        $this->actingAs($alice)->get('/table/history?id='.$instance->id)->assertOk()->assertSee('1750');
    }

    public function testRowsRemovedFromAnOpenTableAreDeletedButAnEmptyFormWipesNothing(): void
    {
        $tax = $this->taxonomy('eba40', true);
        $alice = $this->user('alice@example.com');
        $instance = $this->instanceFor($alice, $tax);
        $tab = 'eba40/corep/tab/c_07.00/c_07.00.xsd';

        $this->actingAs($alice)->postJson('/table/save', [
            'instance_id' => $instance->id, 'tab' => $tab,
            'table_data' => $this->form(['c010r1' => 'A', 'c010r2' => 'B']),
        ])->assertOk();

        // An empty serialization (table not rendered) must not delete anything.
        $this->actingAs($alice)->postJson('/table/save', [
            'instance_id' => $instance->id, 'tab' => $tab, 'table_data' => 'tableCode=',
        ])->assertOk()->assertJson(['changes' => 0]);
        $this->assertSame(2, FactTable::query()->count());

        // Row 2 removed from the grid → its stored cell goes.
        $this->actingAs($alice)->postJson('/table/save', [
            'instance_id' => $instance->id, 'tab' => $tab,
            'table_data' => $this->form(['c010r1' => 'A']),
        ])->assertOk()->assertJson(['changes' => 1]);
        $this->assertSame(['c010r1' => 'A'], FactTable::query()->pluck('string_value', 'cr_code')->all());
    }
}
