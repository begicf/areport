<?php

namespace App\Support;

/**
 * What a failed sum rule means for the reporter: "R0100 is 56 938, R0110 + … + R0210 add up
 * to 608 112". Reads the common DPM-XL shape
 *
 *     with {tZ_02.00.a, (r0150, r0210), default: 0, interval: true}: {c0091} = {c0041} + {c0051}
 *
 * — one cell compared (=, >=, <=, >, <) with a sum or difference of cells, the with clause
 * filling in the table, row or column (a list there: the rule holds for each of them). Each
 * failing instance names its total (the single cell on one side) and its parts. Rules of any
 * other shape (functions, filters, wildcards, ranges) are left unexplained: null.
 */
class RuleExplain
{
    /**
     * @param  array<string, mixed>  $values  "t<table>,r<row>,c<col>" (lower) → value, as validated
     * @param  array<string, float>  $tolerance  cell → allowed rounding difference
     * @param  array<string, list<string>>|null  $sheets  table → its sheet codes (see sheets()); worked out when not given
     * @return list<array{target: string, op: string, parts: list<array{cell: string, sign: int}>, lhs: float, rhs: float, diff: float}>|null
     */
    public static function failing(string $rule, array $values, array $tolerance = [], ?array $sheets = null): ?array
    {
        $parsed = self::parse($rule);
        if (! $parsed) {
            return null;
        }
        [$with, $left, $op, $right] = $parsed;
        $sheets ??= self::sheets($values);

        $out = [];
        foreach (self::instances($with, array_merge($left, $right)) as $bind) {
            // A table with z-axis sheets ("t…,r,c,<sheet>"): the rule holds on each sheet.
            $table = $left[0]['ref']['t'][0] ?? $bind['t'];
            foreach ($sheets['t'.$table] ?? [null] as $sheet) {
                $bind['s'] = $sheet;
                $check = self::check($left, $op, $right, $bind, $values, $tolerance);
                if ($check === false) {
                    return null;
                }
                if ($check) {
                    $out[] = $check;
                }
            }
        }

        return $out;
    }

    /** table ("tc_09.02") → the sheet codes its cells carry ("sal", "sat", …); tables without sheets are left out. */
    public static function sheets(array $values): array
    {
        $sheets = [];
        foreach (array_keys($values) as $key) {
            $p = explode(',', (string) $key);
            if (isset($p[3])) {
                $sheets[$p[0]][$p[3]] = true;
            }
        }

        return array_map('array_keys', $sheets);
    }

    /** One instance of the rule: null when it holds, false when it cannot be read, else what fails. */
    private static function check(array $left, string $op, array $right, array $bind, array $values, array $tolerance): array|false|null
    {
        $cells = fn (array $side) => array_map(fn ($t) => ['cell' => self::key($t['ref'], $bind), 'sign' => $t['sign']], $side);
        $l = $cells($left);
        $r = $cells($right);
        if (in_array(null, array_column($l, 'cell'), true) || in_array(null, array_column($r, 'cell'), true)) {
            return false;
        }
        $sum = function (array $side) use ($values): ?float {
            $total = 0.0;
            foreach ($side as $t) {
                $v = $values[$t['cell']] ?? 0;
                if (! is_numeric($v)) {
                    return null;
                }
                $total += $t['sign'] * (float) $v;
            }

            return $total;
        };
        $lhs = $sum($l);
        $rhs = $sum($r);
        if ($lhs === null || $rhs === null) {
            return false;
        }
        $tol = 0.0;
        foreach (array_merge($l, $r) as $t) {
            $tol += abs((float) ($tolerance[$t['cell']] ?? 0));
        }
        if (self::holds($lhs, $op, $rhs, $tol)) {
            return null;
        }
        // The total is the side with one positive cell (left first, as EBA writes them).
        if (count($l) === 1 && $l[0]['sign'] === 1) {
            return ['target' => $l[0]['cell'], 'op' => $op, 'parts' => $r, 'lhs' => $lhs, 'rhs' => $rhs, 'diff' => $lhs - $rhs];
        }
        if (count($r) === 1 && $r[0]['sign'] === 1) {
            return ['target' => $r[0]['cell'], 'op' => self::flip($op), 'parts' => $l, 'lhs' => $rhs, 'rhs' => $lhs, 'diff' => $rhs - $lhs];
        }

        return false;
    }

    /** "tz_02.00.a,r0100,c0070" → "Z_02.00.A · R0100/C0070" (as the validator names cells). */
    public static function pretty(string $key): string
    {
        $parts = explode(',', $key);

        return strtoupper(ltrim($parts[0], 't')).' · '.strtoupper(implode('/', array_slice($parts, 1)));
    }

    /** @return array{0: array, 1: list<array{ref: array, sign: int}>, 2: string, 3: list<array{ref: array, sign: int}>}|null */
    private static function parse(string $rule): ?array
    {
        $rule = trim(preg_replace('/^[A-Za-z0-9_]+:\s*(?=with\b|\{)/', '', trim($rule)));
        $with = [];
        if (preg_match('/^with\s*(\{[^{}]*\})\s*:\s*(.+)$/s', $rule, $m)) {
            $with = self::ref($m[1], true);
            if ($with === null) {
                return null;
            }
            $rule = $m[2];
        }
        $term = '\{[^{}]*\}';
        $side = '[+-]?\s*'.$term.'(?:\s*[+-]\s*'.$term.')*';
        if (! preg_match('/^\s*('.$side.')\s*(>=|<=|=|>|<)\s*('.$side.')\s*$/s', $rule, $m)) {
            return null;
        }
        $left = self::side($m[1]);
        $right = self::side($m[3]);

        return $left && $right ? [$with, $left, $m[2], $right] : null;
    }

    private static function side(string $text): ?array
    {
        preg_match_all('/([+-]?)\s*(\{[^{}]*\})/', $text, $m, PREG_SET_ORDER);
        $terms = [];
        foreach ($m as $t) {
            $ref = self::ref($t[2], false);
            if ($ref === null) {
                return null;
            }
            $terms[] = ['ref' => $ref, 'sign' => $t[1] === '-' ? -1 : 1];
        }

        return $terms;
    }

    /** {tX, r0100, (c0010, c0020), default: 0} → ['t' => ['x'], 'r' => ['0100'], 'c' => ['0010', '0020']] */
    private static function ref(string $text, bool $isWith): ?array
    {
        $body = trim($text, "{} \t\n");
        $parts = preg_split('/,(?![^()]*\))/', $body);
        $ref = [];
        foreach ($parts as $part) {
            $part = trim($part);
            if ($part === '' || preg_match('/^(default|interval)\s*:/i', $part)) {
                continue;
            }
            if (preg_match('/^t([A-Za-z][\w.\-]*)$/', $part, $m) && ! preg_match('/^[rcs]\d/i', $part)) {
                $ref['t'] = [strtolower($m[1])];
                continue;
            }
            $list = preg_match('/^\((.*)\)$/s', $part, $m) ? array_map('trim', explode(',', $m[1])) : [$part];
            $axis = null;
            $codes = [];
            foreach ($list as $item) {
                if (! preg_match('/^([rc])(\d+[a-z]?)$/i', $item, $mm)) {
                    return null;   // sheets, wildcards, ranges, functions: not explained
                }
                $a = strtolower($mm[1]);
                if ($axis !== null && $axis !== $a) {
                    return null;
                }
                $axis = $a;
                $codes[] = strtolower($mm[2]);
            }
            if ($axis === null || isset($ref[$axis])) {
                return null;
            }
            $ref[$axis] = $codes;
        }

        return $ref;
    }

    /** Every combination of the listed rows / columns the rule runs over. */
    private static function instances(array $with, array $terms): array
    {
        $lists = [];
        foreach (['t', 'r', 'c'] as $axis) {
            $lists[$axis] = $with[$axis] ?? [null];
        }
        foreach ($terms as $t) {
            foreach (['r', 'c'] as $axis) {
                if (isset($t['ref'][$axis]) && count($t['ref'][$axis]) > 1) {
                    return [];   // a list inside an operand: not a plain sum
                }
            }
        }
        $out = [];
        foreach ($lists['t'] as $t) {
            foreach ($lists['r'] as $r) {
                foreach ($lists['c'] as $c) {
                    $out[] = ['t' => $t, 'r' => $r, 'c' => $c];
                }
            }
        }

        return $out;
    }

    private static function key(array $ref, array $bind): ?string
    {
        $t = $ref['t'][0] ?? $bind['t'];
        $r = $ref['r'][0] ?? $bind['r'];
        $c = $ref['c'][0] ?? $bind['c'];

        return $t !== null && $r !== null && $c !== null ? 't'.$t.',r'.$r.',c'.$c.(empty($bind['s']) ? '' : ','.$bind['s']) : null;
    }

    private static function holds(float $l, string $op, float $r, float $tol): bool
    {
        $tol = max($tol, 1e-9 * max(1, abs($l), abs($r)));

        return match ($op) {
            '=' => abs($l - $r) <= $tol,
            '>=' => $l >= $r - $tol,
            '<=' => $l <= $r + $tol,
            '>' => $l > $r - $tol,
            '<' => $l < $r + $tol,
        };
    }

    private static function flip(string $op): string
    {
        return ['>=' => '<=', '<=' => '>=', '>' => '<', '<' => '>', '=' => '='][$op];
    }
}
