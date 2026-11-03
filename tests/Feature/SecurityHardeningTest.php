<?php

namespace Tests\Feature;

use App\Exceptions\Handler;
use App\Model\FactModule;
use App\Model\Taxonomy;
use App\User;
use AReportDpmXBRL\Creat\CreateXBRLFromDB;
use AReportDpmXBRL\Render\RenderHtmlTable;
use Begicf\AReportDpm\Models\Framework;
use Begicf\AReportDpm\Models\Module;
use Begicf\AReportDpm\Models\TableGroup;
use DOMDocument;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/** Findings of the QA pass: what a request can make the server read, render or write. */
class SecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::query()->create(['name' => 'Alice', 'email' => 'alice@example.com', 'password' => Hash::make('secret'), 'email_verified_at' => now()]);
        $this->actingAs($this->user);
    }

    public function test_a_malformed_id_is_not_found_not_a_server_error(): void
    {
        // PostgreSQL refuses "abc" for a bigint column (SQLSTATE 22P02).
        $pdo = new class('invalid input syntax for type bigint: "abc"') extends \PDOException {
            public function __construct(string $message)
            {
                parent::__construct($message);
                $this->code = '22P02';
            }
        };
        $e = new QueryException('pgsql', 'select * from fact_module where id = ?', ['abc'], $pdo);

        $response = app(Handler::class)->render(Request::create('/table/open?id=abc'), $e);
        $this->assertSame(404, $response->getStatusCode());
    }

    public function test_a_table_path_outside_the_taxonomies_is_not_read(): void
    {
        $taxonomy = Taxonomy::query()->create(['name' => 'EBA 4.4', 'original_name' => 'eba44', 'path' => 'eba44', 'folder' => 'eba44']);
        $instance = FactModule::withoutGlobalScopes()->create([
            'user_id' => $this->user->id, 'taxonomy_id' => $taxonomy->id, 'period' => '2026-12-31', 'module_name' => 'Own Funds',
            'module_path' => 'www.eba.europa.eu/eu/fr/xbrl/crr/fws/corep/4.0/mod/corep_of.xsd', 'groups' => '{}',
        ]);

        foreach ([base_path('.env'), base_path('composer.json'), storage_path('app/public/tax/../../../.env')] as $path) {
            $this->post('/table/ajax', ['instance_id' => $instance->id, 'tab' => $path])->assertNotFound();
            $this->post('/table/export', ['instance_id' => $instance->id, 'table' => $path, 'mod' => 'x', 'export_type' => 'xlsx'])->assertNotFound();
        }
    }

    public function test_a_stored_cell_value_cannot_break_out_of_its_input(): void
    {
        $attr = new \ReflectionMethod(RenderHtmlTable::class, 'attr');
        $this->assertSame('&#039; autofocus onfocus=alert(1) x=&#039;', $attr->invoke(null, "' autofocus onfocus=alert(1) x='"));
        $this->assertSame('&lt;b&gt;&amp;&quot;', $attr->invoke(null, '<b>&"'));
        $this->assertSame('', $attr->invoke(null, false));
    }

    public function test_text_facts_are_escaped_in_xbrl_xml(): void
    {
        $writer = new CreateXBRLFromDB('2026-12-31', 'rs:DUMMYLEI123456789012.CON', 'www.eba.europa.eu/eu/fr/xbrl/crr/fws/corep/4.0/mod/corep_of.xsd', [[
            'context' => json_encode([]), 'metric' => 'eba_met:si_dummy', 'numeric_value' => null,
            'sheetcode' => '000', 'string_value' => 'Demo Bank & "Partners" <d.d.>', 'cr_code' => 'c0010r0010',
        ]], ['www.eba.europa.eu/eu/fr/xbrl/crr/fws/corep/4.0/tab/c_01.00/c_01.00.xsd'], 'eba42');

        $xml = $writer->writeXbrl();
        $document = new DOMDocument();
        $this->assertTrue($document->loadXML($xml), 'the instance is well-formed XML');
        $this->assertSame('Demo Bank & "Partners" <d.d.>', $document->getElementsByTagNameNS('http://www.eba.europa.eu/xbrl/crr/dict/met', 'si_dummy')->item(0)?->textContent);
    }

    public function test_eba_frameworks_modules_and_groups_are_read_only(): void
    {
        $this->requirePro();
        $taxonomy = Taxonomy::query()->create(['name' => 'EBA 4.4', 'original_name' => 'eba44', 'path' => 'eba44', 'folder' => 'eba44']);
        DB::table('taxonomies')->where('id', $taxonomy->id)->update(['active' => true]);
        $fw = Framework::query()->create(['taxonomy_id' => $taxonomy->id, 'source' => 'eba', 'code' => 'COREP', 'name' => 'COREP', 'regulator' => 'eba']);
        $module = Module::query()->create(['taxonomy_id' => $taxonomy->id, 'framework_id' => $fw->id, 'source' => 'eba', 'code' => 'COREP_OF', 'name' => 'Own funds']);
        $group = TableGroup::query()->create(['taxonomy_id' => $taxonomy->id, 'framework_id' => $fw->id, 'source' => 'eba', 'code' => 'cr', 'name' => 'Credit risk']);

        $this->putJson('/pro/dpm-model/frameworks/'.$fw->id, ['dpm_version' => '1.0'])->assertForbidden();
        $this->putJson('/pro/dpm-model/modules/'.$module->id, ['name' => 'Renamed'])->assertForbidden();
        $this->deleteJson('/pro/dpm-model/modules/'.$module->id)->assertForbidden();
        $this->postJson('/pro/dpm-model/modules/'.$module->id.'/move', ['direction' => 'up'])->assertForbidden();
        $this->putJson('/pro/dpm-model/table-groups/'.$group->id, ['name' => 'Renamed'])->assertForbidden();
        $this->deleteJson('/pro/dpm-model/table-groups/'.$group->id)->assertForbidden();
        $this->assertSame(['Own funds', 'Credit risk'], [$module->fresh()->name, $group->fresh()->name]);

        // "New framework" with an existing code is refused instead of rewriting EBA's framework.
        $this->post('/pro/dpm-model/tables', ['mode' => 'new', 'new_framework_code' => 'corep', 'code' => 'S_99.00'])->assertSessionHas('warning');
        $this->assertSame(['eba', 'eba'], [$fw->fresh()->source, $fw->fresh()->regulator]);

        // Nor does a new module take over EBA's.
        $own = Framework::query()->create(['taxonomy_id' => $taxonomy->id, 'source' => 'authored', 'code' => 'SPN', 'name' => 'SPN', 'regulator' => 'fba']);
        $this->post('/pro/dpm-model/tables', ['mode' => 'existing', 'framework_id' => $own->id, 'new_module_code' => 'COREP_OF', 'code' => 'S_99.00'])->assertSessionHas('warning');
        $this->assertSame([$fw->id, 'eba'], [$module->fresh()->framework_id, $module->fresh()->source]);
        $this->post('/pro/dpm-model/tables', ['mode' => 'existing', 'framework_id' => $own->id, 'code' => 'S_99.00'])->assertRedirect();
        $this->assertTrue(Module::query()->where('code', 'SPN_CUSTOM')->where('framework_id', $own->id)->exists());
    }
}
