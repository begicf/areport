<?php

namespace Tests\Unit;

use Begicf\AReportPro\Services\DpmFrameworkReleaser;
use PHPUnit\Framework\TestCase;

class FrameworkReleaseDiffTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        if (! class_exists(DpmFrameworkReleaser::class)) {
            $this->markTestSkipped('areport-pro is not installed.');
        }
    }

    public function test_next_version(): void
    {
        $this->assertSame('1.1', DpmFrameworkReleaser::nextVersion('1.0'));
        $this->assertSame('1.10', DpmFrameworkReleaser::nextVersion('1.9'));
        $this->assertSame('2.0.1', DpmFrameworkReleaser::nextVersion('2.0.0'));
        $this->assertSame('2.1', DpmFrameworkReleaser::nextVersion('2'));
    }

    public function test_diff_lists_tables_ordinates_modules_and_rules(): void
    {
        $ord = fn ($label, $metric = null) => ['label' => $label, 'abstract' => false, 'metric' => $metric, 'dimensions' => null, 'key' => null];
        $old = [
            'tables' => [
                'S_01.00' => ['name' => 'Incidents', 'ordinates' => ['x:0010' => $ord('Number', 'm1'), 'y:0010' => $ord('Internal'), 'y:0020' => $ord('External')]],
                'S_02.00' => ['name' => 'Old', 'ordinates' => []],
            ],
            'modules' => ['M1' => ['name' => 'M', 'tables' => ['S_01.00', 'S_02.00'], 'rules' => ['v1' => ['expression' => 'a > 0', 'severity' => 'error', 'active' => true]]]],
        ];
        $new = [
            'tables' => [
                'S_01.00' => ['name' => 'Incidents', 'ordinates' => ['x:0010' => $ord('Number', 'm2'), 'y:0010' => $ord('Internal fraud'), 'y:0030' => $ord('Cyber')]],
                'S_03.00' => ['name' => 'New', 'ordinates' => []],
            ],
            'modules' => ['M1' => ['name' => 'M', 'tables' => ['S_01.00', 'S_03.00'], 'rules' => ['v1' => ['expression' => 'a >= 0', 'severity' => 'error', 'active' => true], 'v2' => ['expression' => 'b > 0', 'severity' => 'warning', 'active' => true]]]],
        ];

        $changes = array_map(fn ($c) => $c['kind'].' '.$c['change'].' '.$c['what'], (new DpmFrameworkReleaser())->diff($old, $new));

        $this->assertEqualsCanonicalizing([
            'column changed S_01.00 column 0010',
            'row changed S_01.00 row 0010',
            'row added S_01.00 row 0030',
            'row removed S_01.00 row 0020',
            'table added S_03.00',
            'table removed S_02.00',
            'module changed M1',
            'rule changed v1',
            'rule added v2',
        ], $changes);
        $this->assertSame([], (new DpmFrameworkReleaser())->diff($new, $new));
    }
}
