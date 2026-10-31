<?php

namespace Tests\Unit;

use Begicf\AReportPro\Http\Controllers\DpmDictionaryController;
use Tests\TestCase;

/** A new dictionary item / module gets the next code of its series, as a suggestion. */
class CodeSuggestionTest extends TestCase
{
    private function next(array $codes, string $default): string
    {
        if (! class_exists(DpmDictionaryController::class)) {
            $this->markTestSkipped('areport-pro is not installed.');
        }

        return DpmDictionaryController::followingCode($codes, $default);
    }

    public function test_continues_the_series(): void
    {
        $this->assertSame('x3', $this->next(['x1', 'x2'], 'x1'));
        $this->assertSame('qEC11', $this->next(['qEC1', 'qEC10', 'qEC2'], 'qEC1'));
    }

    public function test_follows_the_most_common_series(): void
    {
        $this->assertSame('x3', $this->next(['x1', 'x2', 'GA', 'y7'], 'x1'));
    }

    public function test_starts_with_the_default(): void
    {
        $this->assertSame('COREP_FBA1', $this->next([], 'COREP_FBA1'));
        $this->assertSame('COREP_FBA2', $this->next(['COREP_FBA1'], 'COREP_FBA1'));
    }
}
