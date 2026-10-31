<?php

namespace Tests\Feature;

use App\Model\Taxonomy;
use App\User;
use Begicf\AReportPro\Services\DpmTaxonomyExporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TaxonomyExportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (! class_exists(DpmTaxonomyExporter::class)) {
            $this->markTestSkipped('areport-pro is not installed.');
        }
        $this->actingAs(User::query()->create(['name' => 'Alice', 'email' => 'alice@example.com', 'password' => Hash::make('secret'), 'email_verified_at' => now()]));
        $taxonomy = Taxonomy::query()->create(['name' => 'EBA 4.4', 'original_name' => 'eba44', 'path' => 'eba44', 'folder' => 'eba44']);
        DB::table('taxonomies')->where('id', $taxonomy->id)->update(['active' => true]);
    }

    public function test_export_info_lists_only_the_owners_frameworks(): void
    {
        $this->getJson('/pro/dpm-model/export')
            ->assertOk()
            ->assertJson(['frameworks' => []])
            ->assertJsonStructure(['frameworks', 'arelle', 'owner' => ['host', 'prefix']]);
    }

    public function test_export_needs_a_framework(): void
    {
        $this->postJson('/pro/dpm-model/export', ['frameworks' => []])->assertStatus(422);
        $this->postJson('/pro/dpm-model/export', ['frameworks' => [999999]])
            ->assertStatus(422)
            ->assertJson(['message' => 'Choose at least one framework to export.']);
    }

    public function test_with_enforcement_the_plugin_needs_a_signed_license(): void
    {
        config(['areport-pro.license.enforcement' => true, 'areport-pro.license.key' => 'not-a-license']);
        $this->getJson('/pro/dpm-model/export')->assertStatus(402);

        config(['areport-pro.license.enforcement' => false]);
        $this->getJson('/pro/dpm-model/export')->assertOk();
    }

    public function test_download_rejects_unknown_tokens(): void
    {
        $this->get('/pro/dpm-model/export/not-a-token')->assertNotFound();
        $this->get('/pro/dpm-model/export/'.str_repeat('a', 32))->assertNotFound();
    }
}
