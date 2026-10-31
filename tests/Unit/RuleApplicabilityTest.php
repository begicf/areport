<?php

namespace Tests\Unit;

use Begicf\AReportPro\Services\InstanceValidator;
use Tests\TestCase;

class RuleApplicabilityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        if (! class_exists(InstanceValidator::class)) {
            $this->markTestSkipped('areport-pro is not installed.');
        }
    }

    public function testFilingIndicatorPreconditions(): void
    {
        $reported = ['C_01.00' => true, 'C_35.01' => true, 'C_35.02' => false, 'A_00.01' => true];

        $this->assertTrue(InstanceValidator::preconditionHolds('$find:tA_00.01', $reported));          // EBA 3.x form
        $this->assertFalse(InstanceValidator::preconditionHolds('$v_C_35.01 and $v_C_35.02', $reported)); // EBA 4.x form
        $this->assertTrue(InstanceValidator::preconditionHolds('$v_C_35.01 or $v_C_35.02', $reported));
        $this->assertTrue(InstanceValidator::preconditionHolds('not($v_C_35.02) and $v_C_01.00', $reported));
        $this->assertTrue(InstanceValidator::preconditionHolds('($v_C_35.01 and ($v_C_35.02 or $v_C_01.00))', $reported));
        $this->assertFalse(InstanceValidator::preconditionHolds('$v_X_99.99', $reported));             // not reported
        $this->assertNull(InstanceValidator::preconditionHolds('count($x) > 1', $reported));           // unknown → applies
    }

    public function testModuleContextFromModulePath(): void
    {
        $this->assertSame(
            ['version_path' => 'corep/4.2', 'module' => 'corep_of'],
            InstanceValidator::moduleContext('www.eba.europa.eu/eu/fr/xbrl/crr/fws/corep/4.2/mod/corep_of.xsd')
        );
        $this->assertSame(
            ['version_path' => 'ae/its-005-2020/2020-11-15', 'module' => 'ae_con'],
            InstanceValidator::moduleContext('www.eba.europa.eu/eu/fr/xbrl/crr/fws/ae/its-005-2020/2020-11-15/mod/ae_con.xsd')
        );
        $this->assertNull(InstanceValidator::moduleContext('www.eba.europa.eu/eu/fr/xbrl/crr/fws/dora/4.2'));
    }
}
