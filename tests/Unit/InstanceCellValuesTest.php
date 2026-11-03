<?php

namespace Tests\Unit;

use App\Model\FactModule;
use App\Support\InstanceCellValues;
use Tests\TestCase;

/** Values as the rules see them, the same on the form's live check and in the full report. */
class InstanceCellValuesTest extends TestCase
{
    public function test_amounts_are_scaled_and_percentages_become_ratios(): void
    {
        $module = (new FactModule())->forceFill(['monetary_scale' => 1000, 'decimals_monetary' => -3, 'decimals_percentage' => 4]);

        $this->assertSame([350000, 500.0], InstanceCellValues::factValue('350', 'eba_met:mi53', $module));
        $this->assertSame([0.125, 0.00005], InstanceCellValues::factValue('12.5', 'eba_met:pi1', $module));
        $this->assertSame([7, 0.5], InstanceCellValues::factValue('7', 'eba_met:ii1', $module));
        $this->assertSame(['text', null], InstanceCellValues::factValue('text', 'eba_met:si1', $module));

        $module->monetary_scale = 1;
        $this->assertSame([350, 500.0], InstanceCellValues::factValue('350', 'eba_met:mi53', $module));
    }
}
