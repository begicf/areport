<?php

namespace Tests\Feature;

use App\Model\Taxonomy;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RuleBuilderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->requirePro();
        $this->actingAs(User::query()->create(['name' => 'Alice', 'email' => 'alice@example.com', 'password' => Hash::make('secret'), 'email_verified_at' => now()]));
        $taxonomy = Taxonomy::query()->create(['name' => 'EBA 4.4', 'original_name' => 'eba44', 'path' => 'eba44', 'folder' => 'eba44']);
        DB::table('taxonomies')->where('id', $taxonomy->id)->update(['active' => true]);
        $fw = DB::table('dpm_frameworks')->insertGetId(['taxonomy_id' => $taxonomy->id, 'source' => 'eba', 'code' => 'COREP_OF', 'created_at' => now(), 'updated_at' => now()]);
        foreach (['c_01.00', 'c_07.00.a', 'f_01.01'] as $code) {
            DB::table('dpm_tables')->insert(['taxonomy_id' => $taxonomy->id, 'framework_id' => $fw, 'source' => 'eba', 'code' => $code, 'name' => strtoupper($code), 'created_at' => now(), 'updated_at' => now()]);
        }
    }

    public function test_the_builder_finds_tables_by_code_as_dpm_xl_writes_them(): void
    {
        $this->get('/pro/validations')->assertOk()->assertSee('RuleBuilder', false);

        $this->getJson('/pro/validations/lookup?type=table&q=C 07.00')->assertOk()
            ->assertJsonCount(1, 'items')->assertJsonPath('items.0.code', 'C_07.00.a')->assertJsonPath('items.0.extra', 'COREP_OF');
        // The tables an existing rule refers to ({tC_01.00, …}), by their DPM-XL codes.
        $codes = collect($this->getJson('/pro/validations/lookup?type=table&codes=c_01.00,F_01.01')->json('items'))->pluck('code')->sort()->values()->all();
        $this->assertSame(['C_01.00', 'F_01.01'], $codes);
    }
}
