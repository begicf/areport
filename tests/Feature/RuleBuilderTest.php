<?php

namespace Tests\Feature;

use App\Model\FactModule;
use App\Model\Taxonomy;
use App\User;
use Begicf\AReportDpm\Services\DpmXlClient;
use Begicf\AReportPro\Services\AiTranslator;
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

    public function test_a_rule_is_tried_on_a_saved_report_without_storing_anything(): void
    {
        $sent = [];
        $this->app->instance(DpmXlClient::class, new class($sent) extends DpmXlClient {
            public function __construct(private array &$sent) {}

            public function available(): bool { return true; }

            public function evaluateBatch(array $values, array $rules, array $tolerance = [], array $dims = [], array $params = []): array
            {
                $this->sent[] = $rules;

                return ['results' => [['code' => 'try', 'ok' => true, 'supported' => true, 'result' => false, 'passed' => false,
                    'used' => ['tc_01.00,r0020,c0010'], 'missing' => ['tc_01.00,r0130,c0010'], 'failing' => []]]];
            }
        });
        $report = FactModule::query()->create(['module_name' => 'Own Funds - COREP', 'module_path' => 'x/corep_of.xsd', 'period' => '2026-06-30', 'groups' => '{}', 'taxonomy_id' => Taxonomy::query()->value('id')]);

        $this->getJson('/pro/validations/instances')->assertOk()->assertJsonPath('items.0.id', $report->id);
        $this->postJson('/pro/validations/try', ['expression' => 'with {tC_01.00, c0010, default: 0, interval: true}: {r0020} = {r0130}', 'instance_id' => $report->id])
            ->assertOk()->assertJson(['ok' => true, 'outcome' => 'failed', 'missing' => ['tc_01.00,r0130,c0010']]);
        $this->assertSame('dpm-xl-0.9', $sent[0][0]['dialect']);
        // Someone else's report is not found.
        $other = User::query()->create(['name' => 'Bob', 'email' => 'bob@example.com', 'password' => Hash::make('secret'), 'email_verified_at' => now()]);
        $this->actingAs($other)->postJson('/pro/validations/try', ['expression' => '{tC_01.00, r0010, c0010} >= 0', 'instance_id' => $report->id])->assertNotFound();
    }

    public function test_an_ai_draft_is_checked_and_sent_back_once_when_the_parser_rejects_it(): void
    {
        config(['areport-pro.ai.provider' => 'claude', 'areport-pro.ai.api_key' => 'test-key']);
        $prompts = [];
        $this->app->instance(AiTranslator::class, new class($prompts) extends AiTranslator {
            public function __construct(private array &$prompts) {}

            public function json(string $system, string $prompt, array $schema, string $name, string $effort = 'low'): ?array
            {
                $this->prompts[] = $prompt;

                return count($this->prompts) === 1
                    ? ['expression' => '{r0015 = r0020}', 'explanation' => 'draft']
                    : ['expression' => 'with {tC_01.00, c0010, default: 0, interval: true}: {r0015} = {r0020} + {r0530}', 'explanation' => 'Tier 1 is CET1 plus AT1.'];
            }
        });
        $this->app->instance(DpmXlClient::class, new class extends DpmXlClient {
            public function __construct() {}

            public function available(): bool { return true; }

            public function validate(string $expression): array
            {
                return str_starts_with($expression, 'with') ? ['ok' => true] : ['ok' => false, 'error' => 'mismatched input'];
            }
        });

        $this->postJson('/pro/validations/ai-draft', ['description' => 'Tier 1 equals CET1 plus AT1', 'tables' => [['code' => 'C_01.00', 'rows' => [['0015', 'Tier 1'], ['0020', 'CET1'], ['0530', 'AT1']], 'cols' => [['0010', 'Amount']]]]])
            ->assertOk()->assertJson(['ok' => true, 'explanation' => 'Tier 1 is CET1 plus AT1.', 'check' => ['ok' => true]])
            ->assertJsonPath('expression', 'with {tC_01.00, c0010, default: 0, interval: true}: {r0015} = {r0020} + {r0530}');
        $this->assertCount(2, $prompts);
        $this->assertStringContainsString('mismatched input', $prompts[1]);
        $this->assertStringContainsString('"0530","AT1"', $prompts[0]);
    }

    public function test_without_an_ai_key_drafting_is_refused(): void
    {
        config(['areport-pro.ai.api_key' => null]);
        $this->postJson('/pro/validations/ai-draft', ['description' => 'x', 'tables' => [['code' => 'C_01.00', 'rows' => [], 'cols' => []]]])->assertStatus(409);
    }
}
