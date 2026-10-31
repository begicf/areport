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

/** COREP large exposures: counterparties of C 28.00 / C 29.00 must be identified in C 27.00. */
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

    private function cells(string $table, array $cells): void
    {
        $header = FactHeader::query()->create([
            'module_id' => $this->instance->id, 'cr_sheet_code_last' => '000',
            'table_path' => "www.eba.europa.eu/eu/fr/xbrl/crr/fws/corep/4.2/tab/{$table}/{$table}.xsd",
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

    public function test_all_codes_identified_passes(): void
    {
        $this->cells('c_27.00', ['c011r1' => 'BANK-A']);
        $this->cells('c_28.00', ['c010r1' => 'BANK-A']);

        $this->assertSame([], (new FilingPreflight($this->instance->fresh()))->keysNotInRegister());
    }
}
