<?php

namespace Tests\Feature;

use App\Model\FactModule;
use App\Model\Taxonomy;
use App\Support\License;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LicenseEnforcementTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::query()->create(['name' => 'Ana', 'email' => 'ana@bank.example', 'password' => Hash::make('secret')]);
        $this->user->forceFill(['email_verified_at' => now()])->save();
    }

    public function test_without_enforcement_every_product_is_open(): void
    {
        config(['areport.license.enforcement' => false, 'areport.license.key' => null]);

        foreach (array_keys(License::PRODUCTS) as $product) {
            $this->assertNull(License::refusal($product));
        }
    }

    public function test_with_enforcement_a_product_needs_a_valid_license(): void
    {
        config(['areport.license.enforcement' => true, 'areport.license.key' => 'not-a-license']);

        $this->assertStringContainsString('Not an AReport license key', License::refusal('validation'));
        $this->assertFalse(License::covers('teams'));
    }

    public function test_teams_routes_need_the_teams_product(): void
    {
        if (! class_exists(\Begicf\AReportTeams\Support\Teams::class)) {
            $this->markTestSkipped('areport-teams is not installed.');
        }
        config(['areport.license.enforcement' => true, 'areport.license.key' => 'not-a-license']);
        $this->actingAs($this->user)->get('/teams')->assertStatus(402);

        config(['areport.license.enforcement' => false]);
        $this->actingAs($this->user)->get('/teams')->assertOk();
    }

    public function test_validation_needs_the_validation_product(): void
    {
        $this->requireDpm();
        $taxonomy = Taxonomy::query()->create(['name' => 'EBA', 'original_name' => 'eba', 'path' => 'eba', 'folder' => 'eba']);
        $report = FactModule::withoutGlobalScopes()->create([
            'user_id' => $this->user->id, 'taxonomy_id' => $taxonomy->id, 'period' => '2026-09-30',
            'module_name' => 'Own Funds', 'module_path' => 'www.eba.europa.eu/eu/fr/xbrl/crr/fws/corep/4.0/mod/corep_of.xsd', 'groups' => '{}',
        ]);
        config(['areport.license.enforcement' => true, 'areport.license.key' => 'not-a-license']);

        $this->actingAs($this->user)->get('/table/validation-report?id='.$report->id)->assertStatus(402);
        $this->artisan('areport:validate-instances', ['--id' => [$report->id]])->assertFailed();
    }
}
