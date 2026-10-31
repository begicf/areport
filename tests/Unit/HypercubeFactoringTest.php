<?php

namespace Tests\Unit;

use Begicf\AReportPro\Services\DpmTaxonomyGenerator;
use ReflectionClass;
use ReflectionMethod;
use Tests\TestCase;

/**
 * Generated definition linkbase: the open cells of one metric are split into
 * hypercubes whose combinations are exactly the open cells, so a closed cell is
 * not reportable.
 */
class HypercubeFactoringTest extends TestCase
{
    private function factor(array $tuples, array $dims): array
    {
        if (! class_exists(DpmTaxonomyGenerator::class)) {
            $this->markTestSkipped('areport-pro is not installed.');
        }
        $generator = (new ReflectionClass(DpmTaxonomyGenerator::class))->newInstanceWithoutConstructor();
        $m = new ReflectionMethod(DpmTaxonomyGenerator::class, 'factor');
        $m->setAccessible(true);

        return $m->invoke($generator, $tuples, $dims);
    }

    /** Every combination a set of cubes allows. */
    private function covered(array $cubes): array
    {
        $out = [];
        foreach ($cubes as $cube) {
            $combos = [[]];
            foreach ($cube['sets'] as $dim => $values) {
                $next = [];
                foreach ($combos as $c) {
                    foreach ($values as $v) {
                        $next[] = $c + [$dim => $v];
                    }
                }
                $combos = $next;
            }
            foreach ($combos as $c) {
                ksort($c);
                $out[] = json_encode($c);
            }
        }
        sort($out);

        return $out;
    }

    private function keys(array $tuples): array
    {
        $out = array_map(function ($t) {
            ksort($t);

            return json_encode($t);
        }, $tuples);
        sort($out);

        return $out;
    }

    public function test_a_full_grid_is_one_cube(): void
    {
        $tuples = [];
        foreach (['r1', 'r2', 'r3'] as $r) {
            foreach (['c1', 'c2'] as $c) {
                $tuples[] = ['row' => $r, 'col' => $c];
            }
        }
        $cubes = $this->factor($tuples, ['row', 'col']);

        $this->assertCount(1, $cubes);
        $this->assertSame(6, $cubes[0]['cells']);
        $this->assertSame($this->keys($tuples), $this->covered($cubes));
    }

    public function test_a_closed_cell_is_left_out_exactly(): void
    {
        // 3 × 3 with r3/c3 closed: no cube may allow it.
        $tuples = [];
        foreach (['r1', 'r2', 'r3'] as $r) {
            foreach (['c1', 'c2', 'c3'] as $c) {
                if (! ($r === 'r3' && $c === 'c3')) {
                    $tuples[] = ['row' => $r, 'col' => $c];
                }
            }
        }
        $cubes = $this->factor($tuples, ['row', 'col']);

        $this->assertSame($this->keys($tuples), $this->covered($cubes));
        $this->assertNotContains(json_encode(['col' => 'c3', 'row' => 'r3']), $this->covered($cubes));
        $this->assertSame(8, array_sum(array_column($cubes, 'cells')));
        $this->assertLessThanOrEqual(2, count($cubes));
    }

    public function test_three_dimensions_with_scattered_gaps(): void
    {
        $tuples = [];
        foreach (['a', 'b'] as $x) {
            foreach (['p', 'q', 'r'] as $y) {
                foreach (['1', '2'] as $z) {
                    if (! in_array($x.$y.$z, ['aq2', 'br1'], true)) {
                        $tuples[] = ['x' => $x, 'y' => $y, 'z' => $z];
                    }
                }
            }
        }
        $cubes = $this->factor($tuples, ['x', 'y', 'z']);

        $this->assertSame($this->keys($tuples), $this->covered($cubes));
        $this->assertSame(count($tuples), array_sum(array_column($cubes, 'cells')));
    }

    public function test_no_dimensions_is_one_cube(): void
    {
        $cubes = $this->factor([[]], []);

        $this->assertCount(1, $cubes);
        $this->assertSame(1, $cubes[0]['cells']);
    }
}
