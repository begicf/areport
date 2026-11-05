<?php

namespace Tests\Unit;

use App\Http\Controllers\Table\TableController;
use App\Support\RuleExplain;
use Tests\TestCase;

/** A failed sum rule names its total and what the parts add up to. */
class RuleExplainTest extends TestCase
{
    public function test_a_total_that_does_not_add_up(): void
    {
        $values = ['tz_02.00.a,r0100,c0070' => 100, 'tz_02.00.a,r0110,c0070' => 30, 'tz_02.00.a,r0120,c0070' => 50];
        $checks = RuleExplain::failing('with {tZ_02.00.a, c0070, default: 0, interval: true}: {r0100} = {r0110} + {r0120} + {r0130}', $values);

        $this->assertCount(1, $checks);
        $this->assertSame('tz_02.00.a,r0100,c0070', $checks[0]['target']);
        $this->assertSame(['tz_02.00.a,r0110,c0070', 'tz_02.00.a,r0120,c0070', 'tz_02.00.a,r0130,c0070'], array_column($checks[0]['parts'], 'cell'));
        $this->assertEquals(80, $checks[0]['rhs']);   // a missing cell counts as 0 (default: 0)
        $this->assertEquals(20, $checks[0]['diff']);
        $this->assertSame('Z_02.00.A · R0100/C0070', RuleExplain::pretty($checks[0]['target']));

        $values['tz_02.00.a,r0100,c0070'] = 80.4;
        $this->assertSame([], RuleExplain::failing('with {tZ_02.00.a, c0070, default: 0}: {r0100} = {r0110} + {r0120}', $values, ['tz_02.00.a,r0100,c0070' => 0.5]));
    }

    public function test_a_rule_over_a_list_of_rows_fails_on_some_of_them(): void
    {
        $values = ['tz_02.00.b,r0150,c0091' => 10, 'tz_02.00.b,r0150,c0041' => 4, 'tz_02.00.b,r0150,c0051' => 6,
            'tz_02.00.b,r0210,c0091' => 9, 'tz_02.00.b,r0210,c0041' => 4, 'tz_02.00.b,r0210,c0051' => 6];
        $checks = RuleExplain::failing('with {tZ_02.00.b, (r0150, r0210), default: 0, interval: true}: {c0091} = {c0041} + {c0051}', $values);

        $this->assertSame(['tz_02.00.b,r0210,c0091'], array_column($checks, 'target'));
        $this->assertEquals(-1, $checks[0]['diff']);
    }

    public function test_inequalities_differences_full_references_and_sheets(): void
    {
        $values = ['tc_01.00,r0010,c0010' => 5, 'tc_01.00,r0020,c0010' => 9, 'tc_01.00,r0030,c0010' => 2];
        $checks = RuleExplain::failing('{tC_01.00, r0010, c0010} >= {tC_01.00, r0020, c0010} - {tC_01.00, r0030, c0010}', $values);
        $this->assertSame('>=', $checks[0]['op']);
        $this->assertSame([1, -1], array_column($checks[0]['parts'], 'sign'));
        $this->assertEquals(7, $checks[0]['rhs']);

        // The total on the right: read as "total <= …" from its side.
        $checks = RuleExplain::failing('with {tC_01.00, c0010}: {r0020} + {r0030} <= {r0010}', $values);
        $this->assertSame('tc_01.00,r0010,c0010', $checks[0]['target']);
        $this->assertSame('>=', $checks[0]['op']);

        // A table with z-axis sheets: the rule holds on each sheet.
        $sheeted = ['tc_09.02,r0010,c0010,sal' => 3, 'tc_09.02,r0020,c0010,sal' => 3, 'tc_09.02,r0010,c0010,sat' => 5, 'tc_09.02,r0020,c0010,sat' => 4];
        $checks = RuleExplain::failing('with {tC_09.02, c0010, default: 0}: {r0010} = {r0020}', $sheeted);
        $this->assertSame(['tc_09.02,r0010,c0010,sat'], array_column($checks, 'target'));
    }

    public function test_other_rule_shapes_are_not_explained(): void
    {
        foreach ([
            'with {tZ_03.01, default: null, interval: false}: {r*, c0010} >= 0',
            'with {tC_01.00, c0010}: sum({r0010-0050}) = {r0060}',
            'with {tC_01.00, c0010}: if {r0010} > 0 then {r0020} > 0 endif',
            'with {tC_01.00, c0010}: {r0010} + {r0020} = {r0030} + {r0040}',
        ] as $rule) {
            $this->assertNull(RuleExplain::failing($rule, ['tc_01.00,r0010,c0010' => 1]), $rule);
        }
    }

    public function test_only_the_total_is_a_failing_cell_on_the_form(): void
    {
        $failed = [
            ['code' => 'v1', 'severity' => 'error', 'rule' => 'with {tZ_02.00.a, c0070, default: 0}: {r0100} = {r0110} + {r0120}',
                'facts' => [['cell' => 'Z_02.00.A · R0100/C0070'], ['cell' => 'Z_02.00.A · R0110/C0070'], ['cell' => 'Z_02.00.A · R0120/C0070']]],
            ['code' => 'v2', 'severity' => 'error', 'rule' => 'with {tZ_02.00.a, c0080}: {r*} >= 0', 'facts' => [['cell' => 'Z_02.00.A · R0300/C0080']]],
        ];
        $cells = ['z_02.00.a,r0100,c0070', 'z_02.00.a,r0110,c0070', 'z_02.00.a,r0120,c0070', 'z_02.00.a,r0300,c0080'];
        $values = ['tz_02.00.a,r0100,c0070' => 10, 'tz_02.00.a,r0110,c0070' => 1, 'tz_02.00.a,r0120,c0070' => 2, 'tz_02.00.a,r0300,c0080' => -1];

        [$out, $failing, $involved] = TableController::explainFailures($failed, $cells, $values, []);

        $this->assertSame(['z_02.00.a,r0100,c0070', 'z_02.00.a,r0300,c0080'], $failing);
        $this->assertSame(['z_02.00.a,r0110,c0070', 'z_02.00.a,r0120,c0070'], $involved);
        $this->assertEquals(3, $out[0]['checks'][0]['expected']);
        $this->assertSame('z_02.00.a,r0100,c0070', $out[0]['checks'][0]['key']);
        $this->assertArrayNotHasKey('checks', $out[1]);
    }
}
