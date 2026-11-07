<?php

namespace Tests\Unit;

use App\Http\Controllers\Table\TableController;
use App\Model\FactModule;
use PHPUnit\Framework\TestCase;

/** $ReportingLevel / $AccountingStandard for the EBA rules before 4.0, as the taxonomy's params.xml works them out. */
class FormulaParametersTest extends TestCase
{
    private function params(string $module, string $consolidation = 'CON'): array
    {
        return TableController::formulaParameters(new FactModule(['module_path' => 'www.eba.europa.eu/eu/fr/xbrl/crr/fws/'.$module, 'consolidation' => $consolidation]));
    }

    public function test_the_module_name_decides_the_reporting_level_and_standard(): void
    {
        $this->assertSame(['ReportingLevel' => 'ind', 'AccountingStandard' => 'IFRS'], $this->params('ae/its-005-2020/2020-11-15/mod/ae_ind.xsd'));
        $this->assertSame(['ReportingLevel' => 'con', 'AccountingStandard' => 'GAAP'], $this->params('finrep/its-005-2020/2020-11-15/mod/finrep9_con_gaap.xsd', 'IND'));
    }

    public function test_otherwise_the_instance_consolidation(): void
    {
        $this->assertSame('ind', $this->params('corep/its-005-2020/2022-03-01/mod/corep_of.xsd', 'IND')['ReportingLevel']);
        $this->assertSame('con', $this->params('corep/its-005-2020/2022-03-01/mod/corep_of.xsd')['ReportingLevel']);
    }
}
