<?php

namespace Tests\Feature;

use App\Model\FactHeader;
use App\Model\FactModule;
use App\Model\FactTable;
use App\Model\Taxonomy;
use App\Support\FilingPreflight;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/** Keys of open tables must be identified in their register table (COREP large exposures, DORA register of information). */
class KeyRegisterPreflightTest extends TestCase
{
    use RefreshDatabase;

    private FactModule $instance;

    protected function setUp(): void
    {
        parent::setUp();
        if (! is_file(storage_path('app/public/tax/eba42/www.eba.europa.eu/eu/fr/xbrl/crr/fws/corep/4.2/tab/c_27.00/c_27.00.json'))) {
            $this->markTestSkipped('The EBA 4.2 taxonomy is not available.');
        }
        $user = User::query()->create(['name' => 'Alice', 'email' => 'alice@example.com', 'password' => Hash::make('secret'), 'email_verified_at' => now()]);
        $this->actingAs($user);
        $taxonomy = Taxonomy::query()->create(['name' => 'EBA 4.2', 'original_name' => 'eba42', 'path' => 'eba42', 'folder' => 'eba42']);
        $this->instance = FactModule::query()->create([
            'taxonomy_id' => $taxonomy->id, 'period' => '2026-12-31', 'module_name' => 'Large exposures', 'groups' => '{}',
            'module_path' => 'www.eba.europa.eu/eu/fr/xbrl/crr/fws/corep/4.2/mod/corep_le.xsd', 'user_id' => $user->id,
        ]);
    }

    private function cells(string $table, array $cells, string $framework = 'corep/4.2'): void
    {
        $header = FactHeader::query()->create([
            'module_id' => $this->instance->id, 'cr_sheet_code_last' => '000',
            'table_path' => "www.eba.europa.eu/eu/fr/xbrl/crr/fws/{$framework}/tab/{$table}/{$table}.xsd",
        ]);
        foreach ($cells as $cr => $value) {
            FactTable::query()->create(['fact_header_id' => $header->id, 'cr_code' => $cr, 'cr_sheet_code' => '000', 'string_value' => $value,
                'metric' => null, 'xbrl_context_key' => '', 'xbrl_context_key_raw' => '{}', 'user_id' => auth()->id()]);
        }
    }

    public function test_codes_missing_from_c27_are_reported(): void
    {
        $this->cells('c_27.00', ['c011r1' => 'BANK-A', 'c011r2' => 'GROUP-1']);
        $this->cells('c_28.00', ['c010r1' => 'BANK-A', 'c010r2' => 'BANK-B', 'c020r1' => '100']);
        $this->cells('c_29.00', ['c010r1' => 'BANK-A', 'c020r1' => 'GROUP-2']);

        $missing = (new FilingPreflight($this->instance->fresh()))->keysNotInRegister();

        $this->assertEqualsCanonicalizing([
            'C_28.00 qINC BANK-B',
            'C_29.00 qGCC GROUP-2',
        ], array_map(fn ($m) => $m['table'].' '.$m['key'].' '.$m['value'], $missing));
    }

    public function test_an_empty_register_is_reported_once_per_table(): void
    {
        $this->cells('c_28.00', ['c010r1' => 'BANK-A', 'c010r2' => 'BANK-B']);

        $missing = (new FilingPreflight($this->instance->fresh()))->keysNotInRegister();

        $this->assertSame([['table' => 'C_28.00', 'key' => 'qINC', 'value' => null, 'register' => 'C_27.00']], $missing);
    }

    public function test_two_rows_with_the_same_key_are_reported(): void
    {
        // C 27.00 is keyed by the counterparty code (011) and its type (015) together.
        $this->cells('c_27.00', ['c011r1' => 'BANK-A', 'c015r1' => 'eba_CT:x12', 'c011r2' => 'BANK-B', 'c015r2' => 'eba_CT:x12',
            'c011r3' => 'BANK-A', 'c015r3' => 'eba_CT:x12', 'c011r4' => 'BANK-A', 'c015r4' => 'eba_CT:x13']);

        $preflight = new FilingPreflight($this->instance->fresh());
        $this->assertSame([['table' => 'C_27.00', 'rows' => [1, 3], 'key' => 'BANK-A / eba_CT:x12']], $preflight->duplicateOpenKeys());
        $this->assertContains('open_row_key_duplicate', array_column($preflight->run('csv')['checks'], 'code'));
    }

    public function test_all_codes_identified_passes(): void
    {
        $this->cells('c_27.00', ['c011r1' => 'BANK-A']);
        $this->cells('c_28.00', ['c010r1' => 'BANK-A']);

        $this->assertSame([], (new FilingPreflight($this->instance->fresh()))->keysNotInRegister());
    }

    public function test_dora_keys_follow_their_registers(): void
    {
        // B_02.01 registers contractual arrangements (CRZ), B_05.01 ICT providers (ICT),
        // B_01.02 the entities (ESI) that B_02.02 names as users (qLES).
        $this->cells('b_01.02', ['c0010r1' => 'LEI-A'], 'dora/4.0');
        $this->cells('b_02.01', ['c0010r1' => 'CA-1'], 'dora/4.0');
        $this->cells('b_05.01', ['c0010r1' => 'PROV-1'], 'dora/4.0');
        $this->cells('b_02.02', ['c0010r1' => 'CA-1', 'c0020r1' => 'LEI-A', 'c0030r1' => 'PROV-1',
            'c0010r2' => 'CA-2', 'c0020r2' => 'LEI-B', 'c0030r2' => 'PROV-2'], 'dora/4.0');

        $missing = (new FilingPreflight($this->instance->fresh()))->keysNotInRegister();

        $this->assertEqualsCanonicalizing([
            'B_02.02 CRZ CA-2 B_02.01',
            'B_02.02 qLES LEI-B B_01.02',
            'B_02.02 ICT PROV-2 B_05.01',
        ], array_map(fn ($m) => $m['table'].' '.$m['key'].' '.$m['value'].' '.$m['register'], $missing));
    }

    public function test_linked_arrangements_must_be_registered_too(): void
    {
        $this->cells('b_02.01', ['c0010r1' => 'CA-1'], 'dora/4.0');
        $this->cells('b_02.03', ['c0010r1' => 'CA-1', 'c0020r1' => 'CA-9'], 'dora/4.0');

        $missing = (new FilingPreflight($this->instance->fresh()))->keysNotInRegister();

        $this->assertSame([['table' => 'B_02.03', 'key' => 'CRD', 'value' => 'CA-9', 'register' => 'B_02.01']], $missing);
    }

    public function test_registers_can_be_added_in_config(): void
    {
        config(['areport.key_registers' => [['register' => 'B_06.01', 'key' => 'IOB', 'refs' => ['TYA']]]]);
        $this->cells('b_06.01', ['c0010r1' => 'F-1'], 'dora/4.0');
        $this->cells('b_02.02', ['c0050r1' => 'F-1', 'c0060r1' => 'F-2'], 'dora/4.0');

        $missing = (new FilingPreflight($this->instance->fresh()))->keysNotInRegister();

        $this->assertContains(['table' => 'B_02.02', 'key' => 'TYA', 'value' => 'F-2', 'register' => 'B_06.01'], $missing);
        $this->assertNotContains('F-1', array_column($missing, 'value'));
    }
}
