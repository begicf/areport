<?php

namespace Tests\Unit;

use App\Support\CellLabels;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CellLabelsTest extends TestCase
{
    use RefreshDatabase;

    public function test_codes_without_a_model_stay_codes(): void
    {
        $l = new CellLabels(999999);
        $p = 'www.eba.europa.eu/eu/fr/xbrl/crr/fws/corep/4.2/tab/c_01.00/c_01.00.xsd';
        $this->assertNull($l->cell($p, 'c0010r0020'));
        $this->assertSame('row 3', $l->cell($p, 'c0010r3'));   // open-table row
        $this->assertNull($l->value('1250000'));               // not a member
        $this->assertSame('LEI-123', $l->sheet($p, 'k:LEI-123'));
        $this->assertNull($l->sheet($p, '000'));
    }
}
