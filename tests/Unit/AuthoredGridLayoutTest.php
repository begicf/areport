<?php

namespace Tests\Unit;

use Begicf\AReportPro\Http\Controllers\DpmModelController;
use Begicf\AReportPro\Models\Dpm\DpmTable;
use ReflectionMethod;
use Tests\TestCase;

/** DPM Architect grid: a data parent column (a total) keeps its own column — L or reversed L. */
class AuthoredGridLayoutTest extends TestCase
{
    private function grid(array $cols): string
    {
        if (! class_exists(DpmModelController::class)) {
            $this->markTestSkipped('areport-pro is not installed.');
        }
        $m = new ReflectionMethod(DpmModelController::class, 'authoredGridHtml');
        $m->setAccessible(true);
        $html = $m->invoke(app(DpmModelController::class), new DpmTable(['code' => 'X_01.00', 'name' => 'X']), $cols, [], []);

        return preg_replace('/ data-[a-z]+="[^"]*"/', '', $html);
    }

    private function col(int $id, ?int $parent, int $level, string $label, string $rc, bool $abstract = false, ?string $order = null): array
    {
        return ['ordinate_id' => $id, 'parent_id' => $parent, 'level' => $level, 'label' => $label, 'rc_code' => $rc,
            'is_abstract' => $abstract, 'concept_id' => null, 'editable' => true, 'child_order' => $order];
    }

    private function rcRow(string $html): array
    {
        preg_match_all('/<th class="rc-code">([^<]*)<\/th>/', $html, $m);

        return $m[1];
    }

    public function testTotalAfterItsBreakdownIsAReversedL(): void
    {
        $html = $this->grid([
            $this->col(1, null, 0, 'Kolona 1', '010'),
            $this->col(2, null, 0, 'Konona', '050', false, 'children-first'),
            $this->col(3, 2, 1, 'Kolona 3', '030'),
            $this->col(4, 2, 1, 'Kolona 4', '040'),
        ]);

        $this->assertSame(['010', '030', '040', '050'], $this->rcRow($html));          // the total's own column last
        $this->assertStringContainsString('<th class="gh-colname gh-own-head" colspan="3">Konona</th>', $html);
        $this->assertMatchesRegularExpression('/Kolona 4<\/th><th class="gh-colname gh-own"><\/th>/', $html);
    }

    public function testTotalBeforeItsBreakdownIsAnL(): void
    {
        $html = $this->grid([
            $this->col(2, null, 0, 'Total', '010', false, 'parent-first'),
            $this->col(3, 2, 1, 'Of which A', '020'),
            $this->col(4, 2, 1, 'Of which B', '030'),
        ]);

        $this->assertSame(['010', '020', '030'], $this->rcRow($html));
        $this->assertMatchesRegularExpression('/<th class="gh-colname gh-own"><\/th><th class="gh-colname">Of which A/', $html);
    }

    public function testAbstractParentHasNoOwnColumn(): void
    {
        $html = $this->grid([
            $this->col(2, null, 0, 'Group', '', true),
            $this->col(3, 2, 1, 'A', '010'),
            $this->col(4, 2, 1, 'B', '020'),
        ]);

        $this->assertSame(['010', '020'], $this->rcRow($html));
        $this->assertStringNotContainsString('gh-own', $html);
    }
}
