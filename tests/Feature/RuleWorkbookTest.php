<?php

namespace Tests\Feature;

use App\Model\Taxonomy;
use App\User;
use Begicf\AReportDpm\Models\Operation;
use Begicf\AReportDpm\Services\DpmXlClient;
use Begicf\AReportPro\Services\RuleWorkbook;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class RuleWorkbookTest extends TestCase
{
    use RefreshDatabase;

    private int $taxonomyId;

    private int $moduleId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->requirePro();
        $this->actingAs(User::query()->create(['name' => 'Alice', 'email' => 'alice@example.com', 'password' => Hash::make('secret'), 'email_verified_at' => now()]));
        $taxonomy = Taxonomy::query()->create(['name' => 'EBA 4.4', 'original_name' => 'eba44', 'path' => 'eba44', 'folder' => 'eba44']);
        DB::table('taxonomies')->where('id', $taxonomy->id)->update(['active' => true]);
        $this->taxonomyId = $taxonomy->id;
        $fw = DB::table('dpm_frameworks')->insertGetId(['taxonomy_id' => $taxonomy->id, 'source' => 'authored', 'code' => 'SPN', 'created_at' => now(), 'updated_at' => now()]);
        $this->moduleId = DB::table('dpm_modules')->insertGetId(['taxonomy_id' => $taxonomy->id, 'framework_id' => $fw, 'source' => 'authored', 'code' => 'FBA1', 'name' => 'FBA 1', 'created_at' => now(), 'updated_at' => now()]);

        // An EBA rule and one of the owner's.
        $eba = Operation::query()->create(['taxonomy_id' => $taxonomy->id, 'source' => 'eba', 'code' => 'v0172_m', 'type' => 'validation', 'origin' => 'user_defined']);
        $v = $eba->versions()->create(['expression' => 'with {tC_01.00, c0010, default: 0, interval: true}: {r0020} = {r0030}', 'description' => 'CET1', 'from_release' => '4.0']);
        $v->scopes()->create(['module_code' => 'COREP_OF', 'severity' => 'error', 'is_active' => true]);
        $own = Operation::query()->create(['taxonomy_id' => $taxonomy->id, 'framework_id' => $fw, 'source' => 'authored', 'code' => 'fba_1', 'type' => 'validation', 'origin' => 'user_defined']);
        $v = $own->versions()->create(['expression' => '{tS_01.00, r0010, c0010} >= 0', 'description' => 'Not negative']);
        $v->scopes()->create(['module_id' => $this->moduleId, 'severity' => 'warning', 'is_active' => true]);
        RuleWorkbook::syncTables($v);

        $this->app->instance(DpmXlClient::class, new class extends DpmXlClient {
            public function __construct() {}

            public function available(): bool { return true; }

            public function evaluateBatch(array $values, array $rules, array $tolerance = [], array $dims = []): array
            {
                return ['results' => array_map(fn ($r) => str_contains($r['expression'], '>>')
                    ? ['code' => $r['code'], 'ok' => false, 'error' => "line 1:20 mismatched input '>'"]
                    : ['code' => $r['code'], 'ok' => true, 'result' => null], $rules)];
            }
        });
    }

    public function test_the_rules_export_as_a_workbook_with_their_modules(): void
    {
        $response = $this->get('/pro/validations/export');
        $response->assertOk();
        $path = $response->baseResponse->getFile()->getPathname();
        $sheet = IOFactory::load($path)->getSheetByName('Rules');
        $rows = $sheet->toArray();

        $this->assertSame(array_values(RuleWorkbook::COLUMNS), $rows[0]);
        $byCode = collect(array_slice($rows, 1))->keyBy(0);
        $this->assertSame('own', $byCode['fba_1'][2]);
        $this->assertSame('FBA1 (warning)', $byCode['fba_1'][5]);
        $this->assertSame('S_01.00', $byCode['fba_1'][6]);
        $this->assertSame('EBA', $byCode['v0172_m'][2]);
        $this->assertSame('COREP_OF (error)', $byCode['v0172_m'][5]);

        // The grid's filters narrow the export.
        $own = IOFactory::load($this->get('/pro/validations/export?owner=own')->baseResponse->getFile()->getPathname())->getSheetByName('Rules')->toArray();
        $this->assertCount(2, $own);
    }

    public function test_an_import_is_previewed_then_applied_and_eba_rows_are_left_alone(): void
    {
        $file = $this->workbook([
            ['Code', 'Type', 'Owner', 'Modules', 'Expression', 'Message'],
            ['v0172_m', 'validation', 'EBA', 'COREP_OF (error)', '{tC_01.00, r0010, c0010} = 1', 'changed EBA'],
            ['fba_1', 'validation', 'own', 'FBA1 (error, deactivated)', '{tS_01.00, r0010, c0010} >= 0', 'Not negative'],
            ['fba_2', '', '', 'FBA1 (warning)', 'with {tS_01.00, c0010, default: 0, interval: true}: {r0010} = {r0020} + {r0030}', 'Total'],
        ]);

        $preview = $this->post('/pro/validations/import', ['file' => $file])->assertOk()->json();
        $this->assertSame(['new' => 1, 'changed' => 1, 'unchanged' => 0, 'skipped' => 1, 'error' => 0], $preview['counts']);
        $this->assertSame('Changes modules.', $preview['rows'][1]['messages'][0]);
        $this->assertSame(1, Operation::query()->where('source', '<>', 'eba')->count(), 'a preview writes nothing');

        $this->post('/pro/validations/import', ['file' => $file, 'commit' => 1])->assertOk()->assertJson(['created' => 1, 'updated' => 1]);
        $new = Operation::query()->where('code', 'fba_2')->firstOrFail();
        $this->assertSame('authored', $new->source);
        $this->assertSame(['S_01.00'], DB::table('dpm_operation_tables')->where('operation_version_id', $new->current->id)->pluck('table_code')->all());
        $this->assertSame('warning', $new->current->scopes()->value('severity'));
        $scope = Operation::query()->where('code', 'fba_1')->first()->current->scopes()->first();
        $this->assertSame(['error', false], [$scope->severity, (bool) $scope->is_active]);
        $this->assertSame('CET1', Operation::query()->where('code', 'v0172_m')->first()->current->description);
    }

    public function test_rows_in_error_stop_the_import(): void
    {
        $file = $this->workbook([
            ['Code', 'Modules', 'Expression', 'Precondition'],
            ['ok_rule', 'FBA1 (error)', '{tS_01.00, r0010, c0010} >= 0', ''],
            ['9bad', 'FBA1 (error)', '{tS_01.00, r0010, c0010} >= 0', ''],
            ['bad_syntax', 'FBA1 (error)', '{tS_01.00, r0010, c0010} >> 0', ''],
            ['bad_module', 'XYZ (error)', '{tS_01.00, r0010, c0010} >= 0', ''],
            ['bad_pre', 'FBA1 (error)', '{tS_01.00, r0010, c0010} >= 0', 'p_none'],
            ['ok_rule', 'FBA1 (sometimes)', '{tS_01.00, r0010, c0010} >= 0', ''],
        ]);

        $preview = $this->post('/pro/validations/import', ['file' => $file])->assertOk()->json();
        $this->assertSame(1, $preview['counts']['new']);
        $this->assertSame(5, $preview['counts']['error']);
        $messages = collect($preview['rows'])->pluck('messages', 'line');
        $this->assertStringContainsString('must start with a letter', $messages[3][0]);
        $this->assertStringContainsString('DPM-XL: line 1:20', $messages[4][0]);
        $this->assertSame('Unknown module XYZ.', $messages[5][0]);
        $this->assertSame('Unknown precondition p_none.', $messages[6][0]);
        $this->assertStringContainsString('repeated', implode(' ', $messages[7]));

        $this->post('/pro/validations/import', ['file' => $file, 'commit' => 1])->assertStatus(422);
        $this->assertFalse(Operation::query()->where('code', 'ok_rule')->exists());
    }

    public function test_the_module_cell_reads_back_what_the_export_writes(): void
    {
        $scopes = [['module' => 'FBA2', 'severity' => 'warning', 'is_active' => false], ['module' => 'FBA1', 'severity' => 'error', 'is_active' => true]];
        $cell = RuleWorkbook::formatScopes($scopes);
        $this->assertSame('FBA1 (error), FBA2 (warning, deactivated)', $cell);
        $this->assertSame([['module' => 'FBA1', 'severity' => 'error', 'is_active' => true], ['module' => 'FBA2', 'severity' => 'warning', 'is_active' => false]], RuleWorkbook::parseScopes($cell));
        $this->assertSame([['module' => 'SBP_RM 1.5.0', 'severity' => 'error', 'is_active' => true]], RuleWorkbook::parseScopes('SBP_RM 1.5.0'));
        $this->assertNull(RuleWorkbook::parseScopes('FBA1 (often)'));
    }

    private function workbook(array $rows): UploadedFile
    {
        $book = new Spreadsheet();
        $book->getActiveSheet()->setTitle('Rules')->fromArray($rows);
        $path = tempnam(sys_get_temp_dir(), 'rw').'.xlsx';
        (new Xlsx($book))->save($path);

        return new UploadedFile($path, 'rules.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
    }
}
