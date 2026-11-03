<?php

namespace Tests\Feature;

use App\Model\Taxonomy;
use App\User;
use Begicf\AReportDpm\Models\Dpm2Database;
use Begicf\AReportDpm\Models\Operation;
use Begicf\AReportDpm\Models\RulePackage;
use Begicf\AReportDpm\Services\DpmValidationImporter;
use Begicf\AReportDpm\Services\RulePackageImporter;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Tests\TestCase;
use ZipArchive;

/** EBA small validation packages: previewed, then applied to the DPM 2.0 database, the EBA operations and the taxonomy set files. */
class RulePackageTest extends TestCase
{
    // Not RefreshDatabase: SQLite cannot ATTACH the DPM 2.0 "schema" inside its transaction.
    use DatabaseMigrations;

    private const SET = 'www.eba.europa.eu/eu/fr/xbrl/crr/fws/corep/4.2/set/corep_of-ignore-val.xml';

    private string $storage;

    /** @var array<int, array{0: int, 1: string}> applicability refreshed (taxonomy, version) */
    private array $refreshed = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->requireDpm();
        $this->storage = sys_get_temp_dir().'/areport-pkg-'.bin2hex(random_bytes(4));
        $this->app->useStoragePath($this->storage);
        config(['areport-dpm.manage' => true]);
        $this->actingAs(User::query()->create(['name' => 'Ana', 'email' => 'ana@bank.example', 'password' => Hash::make('secret'), 'email_verified_at' => now()]));

        // A DPM 2.0 database: Access booleans as -1 / 0, as mdb-export writes them.
        DB::statement("ATTACH DATABASE ':memory:' AS dpm2_t");
        Schema::create('dpm2_t.operation', fn ($t) => [$t->integer('operationid'), $t->string('code'), $t->string('type')]);
        Schema::create('dpm2_t.operationversion', fn ($t) => [$t->integer('operationvid'), $t->integer('operationid')]);
        Schema::create('dpm2_t.operationscope', fn ($t) => [$t->integer('operationscopeid'), $t->integer('operationvid'), $t->integer('isactive'), $t->string('severity'), $t->string('fromsubmissiondate')->nullable()]);
        Schema::create('dpm2_t.operationscopecomposition', fn ($t) => [$t->integer('operationscopeid'), $t->integer('modulevid')]);
        Schema::create('dpm2_t.moduleversion', fn ($t) => [$t->integer('modulevid'), $t->string('code'), $t->string('versionnumber')]);
        DB::table('dpm2_t.operation')->insert([['operationid' => 1, 'code' => 'v7375_m', 'type' => 'validation'], ['operationid' => 2, 'code' => 'v26586_m', 'type' => 'validation']]);
        DB::table('dpm2_t.operationversion')->insert([['operationvid' => 11, 'operationid' => 1], ['operationvid' => 12, 'operationid' => 2]]);
        DB::table('dpm2_t.moduleversion')->insert([['modulevid' => 5, 'code' => 'COREP_OF', 'versionnumber' => '4.2.0']]);
        DB::table('dpm2_t.operationscope')->insert([
            ['operationscopeid' => 101, 'operationvid' => 11, 'isactive' => -1, 'severity' => 'error', 'fromsubmissiondate' => '2026-03-31 00:00:00'],
            ['operationscopeid' => 102, 'operationvid' => 12, 'isactive' => 0, 'severity' => 'warning', 'fromsubmissiondate' => '2026-03-31 00:00:00'],
        ]);
        DB::table('dpm2_t.operationscopecomposition')->insert([['operationscopeid' => 101, 'modulevid' => 5], ['operationscopeid' => 102, 'modulevid' => 5]]);
        Dpm2Database::query()->create(['label' => '4.2.1', 'schema_name' => 'dpm2_t', 'file_name' => 'db.zip', 'status' => 'ready']);

        // Its rules copied into a taxonomy, and that taxonomy's folder with the 4.2 set file.
        $taxonomy = Taxonomy::query()->create(['name' => 'EBA 4.2', 'original_name' => 'eba42', 'path' => 'eba42', 'folder' => 'eba42']);
        $op = Operation::query()->create(['taxonomy_id' => $taxonomy->id, 'source' => 'eba', 'code' => 'v7375_m', 'type' => 'validation', 'origin' => 'user_defined']);
        $v = $op->versions()->create(['expression' => '{tC_01.00, r0010, c0010} >= 0', 'metadata' => ['dpm2_vid' => 11]]);
        $v->scopes()->create(['module_code' => 'COREP_OF 4.2.0', 'severity' => 'error', 'is_active' => true]);
        File::ensureDirectoryExists(dirname($this->storage.'/app/public/tax/eba42/'.self::SET));
        file_put_contents($this->storage.'/app/public/tax/eba42/'.self::SET, '<old/>');

        $refreshed = &$this->refreshed;
        $this->app->instance(DpmValidationImporter::class, new class($refreshed) extends DpmValidationImporter {
            public function __construct(private array &$refreshed) {}

            public function refreshApplicability(Taxonomy $taxonomy, string $versionPath): int
            {
                $this->refreshed[] = [$taxonomy->id, $versionPath];

                return 3;
            }
        });
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->storage);
        parent::tearDown();
    }

    public function test_a_package_is_previewed_then_applied(): void
    {
        $files = [$this->scripts(), $this->microTaxonomy()];

        $preview = $this->post('/dpm/packages', ['files' => $files])->assertOk()->json();
        $db = $preview['plan']['databases'][0];
        $this->assertSame([2, 1], [$db['matched'], $db['unknown']], 'scope 999 is not in the database');
        $this->assertSame(['v7375_m', 'v26586_m'], array_column($db['changes'], 'code'));
        $this->assertSame(['active' => false, 'severity' => 'warning', 'from' => '2026-09-13'], $db['changes'][0]['new']);
        $this->assertSame(['COREP_OF 4.2.0'], $db['changes'][0]['modules']);
        $this->assertSame([['path' => 'corep/4.2', 'replaced' => 1, 'added' => 0, 'unchanged' => 0, 'changed' => 1]], $preview['plan']['taxonomies'][0]['versions']);
        $this->assertSame(['dora/4.2'], $preview['plan']['unmatched_versions']);
        $this->assertSame(-1, (int) DB::table('dpm2_t.operationscope')->where('operationscopeid', 101)->value('isactive'), 'a preview changes nothing');

        $this->post('/dpm/packages/'.$preview['package'].'/apply')->assertOk()
            ->assertJson(['result' => ['databases' => [['label' => '4.2.1', 'changed' => 2]], 'operation_scopes' => 1, 'taxonomies' => [['name' => 'EBA 4.2', 'files' => 1, 'module_rules' => 3]]]]);

        $scopes = DB::table('dpm2_t.operationscope')->orderBy('operationscopeid')->get();
        $this->assertSame([0, 'warning', '2026-09-13'], [(int) $scopes[0]->isactive, $scopes[0]->severity, $scopes[0]->fromsubmissiondate]);
        $this->assertSame([-1, 'warning'], [(int) $scopes[1]->isactive, $scopes[1]->severity], 'reactivated, as an Access true');
        $ours = DB::table('dpm_operation_scopes')->first();
        $this->assertSame([false, 'warning'], [(bool) $ours->is_active, $ours->severity]);

        $package = RulePackage::query()->findOrFail($preview['package']);
        $this->assertSame('<new/>', file_get_contents($this->storage.'/app/public/tax/eba42/'.self::SET));
        $this->assertSame('<old/>', file_get_contents($package->dir().'/backup/eba42/'.self::SET), 'the replaced file is kept');
        $this->assertDirectoryDoesNotExist($package->dir().'/x');
        $this->assertCount(1, $this->refreshed);
        $this->getJson('/dpm/packages')->assertJson(['packages' => [['id' => $package->id, 'user' => 'Ana']]]);
        $this->post('/dpm/packages/'.$package->id.'/apply')->assertStatus(409);

        // The same package again: nothing left to change.
        $again = $this->post('/dpm/packages', ['files' => [$this->scripts(), $this->microTaxonomy()]])->assertOk()->json('plan');
        $this->assertSame([], $again['databases'][0]['changes']);
        $this->assertSame(1, $again['taxonomies'][0]['versions'][0]['unchanged']);
    }

    public function test_the_scripts_are_read_not_run(): void
    {
        $older = "update OperationScope set OperationScope.IsActive = 0 where OperationScope.OperationVID = 10412\n"
            ."update OperationScope set OperationScope.IsActive = 0 where OperationScope.OperationVID IN (10413, 10414)\n";
        $this->assertSame([
            ['by' => 'operationvid', 'ids' => [10412], 'set' => ['active' => false]],
            ['by' => 'operationvid', 'ids' => [10413, 10414], 'set' => ['active' => false]],
        ], RulePackageImporter::parseScript($older));

        foreach (["DELETE FROM OperationScope WHERE 1 = 1;", "UPDATE OperationScope SET IsActive = 0 WHERE OperationScopeID IN (1); DROP TABLE Operation;",
            "UPDATE OperationScope SET OperationVID = 4 WHERE OperationScopeID = 1;", "UPDATE OperationScope SET IsActive = 0 WHERE Severity = 'error';"] as $sql) {
            try {
                RulePackageImporter::parseScript($sql);
                $this->fail('Accepted: '.$sql);
            } catch (RuntimeException $e) {
                $this->assertNotSame('', $e->getMessage());
            }
        }

        $this->post('/dpm/packages', ['files' => [UploadedFile::fake()->createWithContent('x.sql', 'DROP TABLE OperationScope;')]])
            ->assertStatus(422)->assertJson(['ok' => false]);
        $this->post('/dpm/packages', ['files' => [UploadedFile::fake()->createWithContent('notes.txt', 'hello')]])
            ->assertStatus(422)->assertJsonPath('error', fn ($e) => str_starts_with($e, 'No DPM script'));
        $this->assertSame(0, RulePackage::query()->count());
    }

    public function test_nothing_is_applied_where_the_validation_data_is_locked(): void
    {
        $package = $this->post('/dpm/packages', ['files' => [$this->scripts()]])->assertOk()->json('package');
        config(['areport-dpm.manage' => false, 'areport.upload' => false]);

        $this->post('/dpm/packages', ['files' => [$this->scripts()]])->assertForbidden();
        $this->post('/dpm/packages/'.$package.'/apply')->assertForbidden();
        $this->get('/dpm')->assertOk()->assertSee('Applying packages is turned off');
        $this->assertSame(-1, (int) DB::table('dpm2_t.operationscope')->where('operationscopeid', 101)->value('isactive'));
    }

    private function scripts(): UploadedFile
    {
        return $this->zip('DPM VR updates scripts.zip', ['DPM VR updates scripts.sql' => "-- Sync OperationScope IsActive, Severity and FromSubmissionDate\n\n"
            ."UPDATE OperationScope SET IsActive = 0, Severity = 'warning', FromSubmissionDate = '13/09/2026' WHERE OperationScopeID IN (101, 999);\n\n"
            ."UPDATE OperationScope SET IsActive = 1, Severity = 'warning', FromSubmissionDate = '13/09/2026' WHERE OperationScopeID IN (102);\n"]);
    }

    /** Micro_taxonomy_package.zip: one zip per release holding only set/ folders. */
    private function microTaxonomy(): UploadedFile
    {
        $inner = $this->zip('inner.zip', [
            'EBA_XBRL_4.2_SET_4.3.0.1/META-INF/taxonomyPackage.xml' => '<taxonomyPackage/>',
            'EBA_XBRL_4.2_SET_4.3.0.1/'.self::SET => '<new/>',
            'EBA_XBRL_4.2_SET_4.3.0.1/www.eba.europa.eu/eu/fr/xbrl/crr/fws/dora/4.2/set/dora-ignore-val.xml' => '<x/>',
            '../escape.xml' => 'no',
        ]);

        return $this->zip('Micro_taxonomy_package.zip', ['EBA_XBRL_4.2_SET_4.3.0.1.zip' => file_get_contents($inner->getRealPath()), 'readme.txt' => 'Micro taxonomy package 4.3.0.1']);
    }

    private function zip(string $name, array $entries): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'pkg');
        $zip = new ZipArchive();
        $zip->open($path, ZipArchive::OVERWRITE);
        foreach ($entries as $entry => $content) {
            $zip->addFromString($entry, $content);
        }
        $zip->close();

        return new UploadedFile($path, $name, 'application/zip', null, true);
    }
}
