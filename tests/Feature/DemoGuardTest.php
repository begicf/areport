<?php

namespace Tests\Feature;

use App\Model\FactModule;
use App\Model\Taxonomy;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/** A public demo stays intact: visitors try everything without changing what others see. */
class DemoGuardTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;
    private User $visitor;
    private FactModule $shared;
    private FactModule $own;

    protected function setUp(): void
    {
        parent::setUp();
        $mk = fn ($n) => User::query()->create(['name' => $n, 'email' => $n.'@example.com', 'password' => Hash::make('secret'), 'email_verified_at' => now()]);
        $this->owner = $mk('owner');
        $this->visitor = $mk('visitor');
        config(['areport.demo.shared_user_id' => $this->owner->id, 'areport.demo.read_only' => true, 'areport.demo.admins' => []]);
        $taxonomy = Taxonomy::query()->create(['name' => 'EBA 4.2', 'original_name' => 'eba42', 'path' => 'tax', 'folder' => 'eba42']);
        $make = fn (User $u) => FactModule::query()->create(['taxonomy_id' => $taxonomy->id, 'period' => '2026-12-31', 'module_name' => 'Own Funds', 'groups' => '{}',
            'module_path' => 'www.eba.europa.eu/eu/fr/xbrl/crr/fws/corep/4.2/mod/corep_of.xsd', 'user_id' => $u->id]);
        $this->shared = $make($this->owner);
        $this->own = $make($this->visitor);
    }

    public function test_a_visitor_cannot_change_a_shared_report(): void
    {
        $this->actingAs($this->visitor)
            ->postJson('/table/save', ['instance_id' => $this->shared->id])
            ->assertStatus(403)->assertJson(['demo' => true]);
        $this->actingAs($this->visitor)
            ->postJson('/instance/settings', ['instance_id' => $this->shared->id])
            ->assertStatus(403);
    }

    public function test_a_visitors_own_report_is_not_blocked(): void
    {
        $r = $this->actingAs($this->visitor)->postJson('/instance/settings', ['instance_id' => $this->own->id]);
        $this->assertNotSame(true, $r->json('demo'));
    }

    public function test_the_shared_workspace_is_read_only_for_visitors(): void
    {
        $this->actingAs($this->visitor)->post('/taxonomy/delete', ['id' => 1])->assertSessionHas('warning');
        if (class_exists(\Begicf\AReportPro\AReportProServiceProvider::class)) {
            $this->actingAs($this->visitor)->postJson('/pro/settings/general', ['lei' => 'x'])->assertStatus(403);
            // Checking a DPM-XL expression changes nothing — allowed.
            $r = $this->actingAs($this->visitor)->postJson('/pro/validations/check', ['expression' => '{r0010} > 0']);
            $this->assertNotSame(true, $r->json('demo'));
        }
    }

    public function test_the_shared_user_and_production_are_unrestricted(): void
    {
        $r = $this->actingAs($this->owner)->postJson('/instance/settings', ['instance_id' => $this->shared->id]);
        $this->assertNotSame(true, $r->json('demo'));

        config(['areport.demo.shared_user_id' => null]);
        $r = $this->actingAs($this->visitor)->postJson('/table/save', ['instance_id' => $this->own->id]);
        $this->assertNotSame(true, $r->json('demo'));
    }
}
