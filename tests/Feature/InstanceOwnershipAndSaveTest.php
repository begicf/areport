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

    /** A form field as the reporting grid posts it: c<col>r<row>[value] + its context (metric: monetary unless given). */
    private function form(array $cells, array $metrics = []): string
    {
        $fields = [];
        foreach ($cells as $code => $value) {
            $fields[$code] = ['value' => $value, 'dim' => json_encode(['metric' => $metrics[$code] ?? 'eba_met:mi53'])];
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

    public function testTheSharedDemoUsersInstancesAreVisibleToEveryone(): void
    {
        $tax = $this->taxonomy('eba44', true);
        $demo = $this->user('demo@example.com');
        $alice = $this->user('alice@example.com');
        $bob = $this->user('bob@example.com');
        $demoInstance = $this->instanceFor($demo, $tax);
        $aliceInstance = $this->instanceFor($alice, $tax);
        $bobInstance = $this->instanceFor($bob, $tax);

        config(['areport.demo.shared_user_id' => $demo->id]);

        $rows = $this->actingAs($alice)->postJson('/areport/json')->assertOk()->json('rows');
        $ids = array_column($rows, 'id');
        sort($ids);
        $this->assertSame([$demoInstance->id, $aliceInstance->id], $ids);

        // Other users' own instances stay private.
        $this->actingAs($alice)->get('/table/history?id='.$bobInstance->id)->assertNotFound();
    }

    public function testAnXbrlImportOnlyReadsTablesOfTheTaxonomy(): void
    {
        $tax = $this->taxonomy('eba44', true);
        $alice = $this->user('alice@example.com');
        $instance = $this->instanceFor($alice, $tax);
        $file = \Illuminate\Http\UploadedFile::fake()->createWithContent('instance.xbrl', '<xbrli:xbrl xmlns:xbrli="http://www.xbrl.org/2003/instance"/>');

        $this->actingAs($alice)->post('/table/import', ['instance_id' => $instance->id, 'tab' => base_path('composer.json'), 'fileToUpload' => $file])
            ->assertStatus(422);
        $this->actingAs($alice)->post('/table/import', ['instance_id' => $instance->id, 'tab' => '', 'fileToUpload' => $file])
            ->assertStatus(422);
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
            'table_data' => $this->form(['c010r1' => 'A', 'c010r2' => 'B'], ['c010r1' => 'eba_met:si1', 'c010r2' => 'eba_met:si1']),
        ])->assertOk();

        // An empty serialization (table not rendered) must not delete anything.
        $this->actingAs($alice)->postJson('/table/save', [
            'instance_id' => $instance->id, 'tab' => $tab, 'table_data' => 'tableCode=',
        ])->assertOk()->assertJson(['changes' => 0]);
        $this->assertSame(2, FactTable::query()->count());

        // Row 2 removed from the grid → its stored cell goes.
        $this->actingAs($alice)->postJson('/table/save', [
            'instance_id' => $instance->id, 'tab' => $tab,
            'table_data' => $this->form(['c010r1' => 'A'], ['c010r1' => 'eba_met:si1']),
        ])->assertOk()->assertJson(['changes' => 1]);
        $this->assertSame(['c010r1' => 'A'], FactTable::query()->pluck('string_value', 'cr_code')->all());
    }

    public function testValuesThatAreNotNumbersAreNotSavedAndAreReported(): void
    {
        $tax = $this->taxonomy('eba40', true);
        $alice = $this->user('alice@example.com');
        $instance = $this->instanceFor($alice, $tax);
        $tab = 'eba40/www.eba.europa.eu/eu/fr/xbrl/crr/fws/corep/4.0/tab/c_01.00/c_01.00.xsd';

        $this->actingAs($alice)->postJson('/table/save', [
            'instance_id' => $instance->id,
            'table_data' => $this->form(
                ['c010r010' => '1500.25', 'c010r020' => 'abc', 'c010r030' => '12,5', 'c010r040' => '7', 'c010r050' => '1.5', 'c010r060' => 'n/a'],
                ['c010r030' => 'eba_met:pi1', 'c010r040' => 'eba_met:ii1', 'c010r050' => 'eba_met:ii1', 'c010r060' => 'eba_met:si1'],
            ),
            'tab' => $tab,
        ])->assertOk()->assertJson(['ok' => true, 'changes' => 3, 'invalid' => [
            'c010r020' => 'Enter a number (e.g. 1250000 or 12.5).',
            'c010r030' => 'Enter a number (e.g. 1250000 or 12.5).',
            'c010r050' => 'Enter a whole number.',
        ]]);

        // Text stays text; the refused cells keep what they had (nothing).
        $this->assertSame(['c010r010' => '1500.25', 'c010r040' => '7', 'c010r060' => 'n/a'], FactTable::query()->orderBy('cr_code')->pluck('string_value', 'cr_code')->all());
    }

    public function testAReportIsDeletedWithItsCellsAndOnlyByItsOwner(): void
    {
        $tax = $this->taxonomy('eba40', true);
        $alice = $this->user('alice@example.com');
        $bob = $this->user('bob@example.com');
        $instance = $this->instanceFor($alice, $tax);
        $this->actingAs($alice)->postJson('/table/save', ['instance_id' => $instance->id, 'tab' => 'eba40/corep/tab/c_01.00/c_01.00.xsd',
            'table_data' => $this->form(['c010r010' => '5'])])->assertOk();
        $this->assertSame(1, FactAudit::query()->count());

        $this->actingAs($bob)->deleteJson('/instance/'.$instance->id)->assertNotFound();
        $this->assertSame(1, FactTable::query()->count());

        $row = collect($this->actingAs($alice)->postJson('/areport/json')->json('rows'))->firstWhere('id', $instance->id);
        $this->assertTrue($row['deletable']);
        $this->actingAs($alice)->deleteJson('/instance/'.$instance->id)->assertOk();
        $this->assertSame([0, 0, 0, 0], [FactModule::withoutGlobalScopes()->count(), FactHeader::query()->count(), FactTable::query()->count(), FactAudit::query()->count()]);
    }

    public function testCreatingAReportRedirectsToItsOwnAddress(): void
    {
        $tax = $this->taxonomy('eba40', true);
        $alice = $this->user('alice@example.com');
        $form = fn (string $period) => ['period' => $period, 'consolidation' => 'CON', 'module_name' => 'Own Funds',
            'module_path' => 'www.eba.europa.eu/eu/fr/xbrl/crr/fws/corep/4.0/mod/corep_of.xsd',
            'table' => [json_encode(['C 01.00' => ['www.eba.europa.eu/eu/fr/xbrl/crr/fws/corep/4.0/tab/c_01.00/c_01.00.xsd']])]];

        // Typed day first with slashes: a date, not a server error.
        $response = $this->actingAs($alice)->post('/table', $form('31/12/2026'));
        $instance = FactModule::query()->sole();
        $response->assertRedirect(route('instance.open', ['id' => $instance->id]));
        $this->assertSame('2026-12-31', $instance->periodYmd());

        $this->actingAs($alice)->post('/table', $form('31.13.2026'))->assertRedirect('/modules')->assertSessionHas('warning');
        $this->actingAs($alice)->post('/table', $form('15-12-2026'))->assertRedirect('/modules')->assertSessionHas('warning');
        $this->assertSame(1, FactModule::query()->count());
    }
}
