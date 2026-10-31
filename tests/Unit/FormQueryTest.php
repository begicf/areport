<?php

namespace Tests\Unit;

use App\Support\FormQuery;
use PHPUnit\Framework\TestCase;

class FormQueryTest extends TestCase
{
    public function testMatchesParseStrForTheFormShapes(): void
    {
        $q = http_build_query([
            'c010r010' => ['value' => '12', 'dim' => '{"metric":"eba_met:qA"}'],
            'c020r010' => ['value' => ['eba_X:x1', 'eba_X:x2']],
            'token' => 'a b&c',
        ]);
        parse_str($q, $expected);
        $this->assertSame($expected, FormQuery::parse($q));
    }

    public function testLargeSheetIsNotTruncated(): void
    {
        $cells = [];
        for ($i = 1; $i <= 2000; $i++) {
            $cells['c010r'.$i] = ['value' => (string) $i, 'dim' => '{}'];
        }
        $parsed = FormQuery::parse(http_build_query($cells));   // 4000 variables, > max_input_vars
        $this->assertCount(2000, $parsed);
        $this->assertSame('2000', $parsed['c010r2000']['value']);
    }
}
