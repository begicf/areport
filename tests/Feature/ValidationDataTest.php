<?php

namespace Tests\Feature;

use App\Model\Taxonomy;
use App\User;
use Begicf\AReportDpm\Models\Dpm2Database;
use Begicf\AReportDpm\Models\Preparation;
use Begicf\AReportDpm\Support\Background;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ValidationDataTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    /** @var array<int, array> background commands started */
    private array $started = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->requireDpm();
        $this->user = User::query()->create(['name' => 'Ana', 'email' => 'ana@bank.example', 'password' => Hash::make('secret')]);
        $this->user->forceFill(['email_verified_at' => now()])->save();
        config(['areport-dpm.manage' => null, 'areport.upload' => true]);
        $started = &$this->started;
        $this->app->instance(Background::class, new class($started) extends Background {
            public function __construct(private array &$started) {}

            public function artisan(string $command, array $options, string $log): void
            {
                $this->started[] = [$command, $options];
            }
        });
    }

    public function test_the_page_lists_taxonomies_and_databases(): void
    {
        Taxonomy::query()->create(['name' => 'EBA 4.4', 'original_name' => 'eba44', 'path' => 'eba44', 'folder' => 'eba44']);
        Dpm2Database::query()->create(['label' => '4.4', 'schema_name' => 'dpm2_4_4', 'file_name' => 'DPM2 Database_v 4_4.zip', 'status' => 'ready']);

        $this->actingAs($this->user)->get('/dpm')->assertOk()->assertSee('Validation data')->assertSee('Validation service');
        $this->actingAs($this->user)->getJson('/dpm/taxonomies')->assertOk()
            ->assertJson(['taxonomies' => [['name' => 'EBA 4.4', 'tables' => 0, 'rules' => 0, 'preparation' => null]]]);
        $this->actingAs($this->user)->getJson('/dpm/databases')->assertOk()->assertJson(['databases' => [['label' => '4.4', 'status' => 'ready']]]);
    }

    public function test_preparing_a_taxonomy_runs_in_the_background_once_at_a_time(): void
    {
        $taxonomy = Taxonomy::query()->create(['name' => 'EBA 4.4', 'original_name' => 'eba44', 'path' => 'eba44', 'folder' => 'eba44']);

        $this->actingAs($this->user)->postJson('/dpm/taxonomies/'.$taxonomy->id.'/prepare')->assertOk();
        $this->assertSame([['dpm:prepare', ['taxonomy' => $taxonomy->id]]], $this->started);
        $this->assertSame('queued', Preparation::query()->where('taxonomy_id', $taxonomy->id)->value('status'));

        $this->actingAs($this->user)->postJson('/dpm/taxonomies/'.$taxonomy->id.'/prepare')->assertStatus(409);
        $this->assertCount(1, $this->started);
    }

    public function test_dpm_prepare_fails_clearly_on_a_folder_without_tables(): void
    {
        $taxonomy = Taxonomy::query()->create(['name' => 'Missing', 'original_name' => 'missing', 'path' => 'missing', 'folder' => 'no-such-folder']);

        $this->artisan('dpm:prepare', ['--taxonomy' => $taxonomy->id]);
        $prep = Preparation::query()->where('taxonomy_id', $taxonomy->id)->sole();
        $this->assertSame('failed', $prep->status);
        $this->assertStringStartsWith('No tables found in the taxonomy folder', $prep->message);
        $this->assertNotNull($prep->finished_at);
        $this->assertNotNull($prep->started_at);
    }

    public function test_the_validation_product_and_the_demo_guard_apply(): void
    {
        config(['areport.license.enforcement' => true, 'areport.license.key' => 'not-a-license']);
        $this->actingAs($this->user)->get('/dpm')->assertStatus(402);
        config(['areport.license.enforcement' => false]);

        $taxonomy = Taxonomy::query()->create(['name' => 'EBA 4.4', 'original_name' => 'eba44', 'path' => 'eba44', 'folder' => 'eba44']);
        config(['areport.demo.shared_user_id' => 999, 'areport.demo.read_only' => true]);   // a shared demo: visitors look, but change nothing
        $this->actingAs($this->user)->postJson('/dpm/taxonomies/'.$taxonomy->id.'/prepare')->assertStatus(403);
        $this->assertSame([], $this->started);
    }

    public function test_the_validation_data_stays_as_it_is_where_uploads_are_off(): void
    {
        config(['areport.upload' => false]);   // e.g. a shared demo server, for every user
        $taxonomy = Taxonomy::query()->create(['name' => 'EBA 4.4', 'original_name' => 'eba44', 'path' => 'eba44', 'folder' => 'eba44']);
        $db = Dpm2Database::query()->create(['label' => '4.4', 'schema_name' => 'dpm2_4_4', 'file_name' => 'DPM2 Database_v 4_4.zip', 'status' => 'ready']);

        $this->actingAs($this->user)->get('/dpm')->assertOk()->assertSee('cannot be prepared again');
        $this->actingAs($this->user)->postJson('/dpm/taxonomies/'.$taxonomy->id.'/prepare')->assertForbidden();
        $this->actingAs($this->user)->deleteJson('/dpm/databases/'.$db->id)->assertForbidden();
        $this->actingAs($this->user)->postJson('/dpm/databases/upload', [])->assertForbidden();
        $this->assertSame([], $this->started);
        $this->assertTrue(Dpm2Database::query()->whereKey($db->id)->exists());

        config(['areport-dpm.manage' => true]);   // switched on for the validation data alone
        $this->actingAs($this->user)->deleteJson('/dpm/databases/'.$db->id)->assertOk();
    }
}
