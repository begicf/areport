<?php

namespace App\Support;

use App\Model\FactHeader;
use App\Model\FactModule;
use App\Model\FactTable;
use AReportDpmXBRL\Library\ArrayManipulation;
use AReportDpmXBRL\Library\Data;
use AReportDpmXBRL\Library\FactValue;
use AReportDpmXBRL\Library\Format;
use AReportDpmXBRL\ModuleTree;
use AReportDpmXBRL\Render;
use DOMDocument;
use DOMXPath;

/**
 * Fill a reporting instance with the facts of a report file (see ReportFile) the way a
 * reporter would: render every table and sheet of the module, put each fact into the
 * form cell with the same concept and dimensions, and save the sheet through
 * FactTable::storeInstance (so values are checked and audited like typed ones).
 * Open tables get one row per key, typed z-axes one sheet per key value.
 */
class ReportFiller
{
    /** Table groups of a module as an instance stores them: group → json {group: {code: schema}}. */
    public static function groups(string $modulePath): array
    {
        $module = Data::getTax($modulePath);
        $dir = dirname($modulePath);
        $parent = ArrayManipulation::searchHref($module['pre'] ?? [], key($module['elements'] ?? []));
        $groups = $parent ? ModuleTree::getGroupTable($module['pre'], key($parent)) : [];
        set_time_limit(600);   // ModuleTree caps max_execution_time when it loads

        $out = [];
        foreach ($groups as $key => $group) {
            $tables = [];
            foreach ($group as $row) {
                $tables[Format::getAfterSpecChar($row['href'], '#')] = $dir.DIRECTORY_SEPARATOR.(explode('-rend', $row['href']))[0].'.xsd';
            }
            $out[$key] = json_encode([$key => $tables]);
        }

        return $out;
    }

    /**
     * Amounts are entered in thousands when every amount is a whole thousand, else in units.
     *
     * @param  list<array{concept: string, value: string}>  $facts
     */
    public static function monetaryScale(array $facts, string $taxonomyFolder): int
    {
        foreach ($facts as $fact) {
            if (\AReportDpmXBRL\Library\MetricTypes::kind($taxonomyFolder, $fact['concept']) === 'monetary'
                && ! preg_match('/^-?\d*000(\.0*)?$/', trim($fact['value']))) {
                return 1;
            }
        }

        return 1000;
    }

    /**
     * @param  callable(int, int)|null  $progress  called with (tables done, tables) after each table
     * @return array{tables: int, facts: int, filled: int, mapped: int, unmapped: list<string>}
     */
    public static function fill(FactModule $instance, array $facts, string $taxonomyFolder, ?callable $progress = null): array
    {
        $scaleDigits = (int) round(log10(max(1, (int) ($instance->monetary_scale ?: 1000))));
        $sig = fn (string $concept, array $dims) => self::signature($concept, $dims);

        // Facts without typed keys by signature; all facts by concept (open rows, typed sheets).
        $plain = $byConcept = [];
        foreach ($facts as $i => $fact) {
            if (! $fact['typed']) {
                $plain[$sig($fact['concept'], $fact['explicit'])][] = $i;   // the same fact may sit in two tables
            }
            $byConcept[strtolower(ReportFile::local($fact['concept']))][] = $i;
        }
        // dimension=member pairs the file has: sheets for other members are not opened.
        $present = [];
        foreach ($facts as $fact) {
            foreach ($fact['explicit'] as $d => $m) {
                $present[strtolower($d.'='.ReportFile::local($m))] = true;
            }
        }
        $used = [];
        $tables = $filled = 0;

        $entered = function (string $value, array $dim) use ($scaleDigits): string {
            return match ($dim['__meta']['value_type'] ?? '') {
                'p' => is_numeric($value) ? FactValue::shift($value, 2) : $value,
                'm' => is_numeric($value) ? FactValue::shift($value, -$scaleDigits) : $value,
                default => $value,
            };
        };

        $paths = [];
        foreach (json_decode((string) $instance->groups, true) ?: [] as $group) {
            foreach (json_decode($group, true) ?: [] as $schemas) {
                foreach ((array) $schemas as $tablePath) {
                    if (is_string($tablePath) && is_file($tablePath)) {
                        $paths[$tablePath] = true;
                    }
                }
            }
        }

        foreach (array_keys($paths) as $tablePath) {
            $tables++;
            if ($progress) {
                $progress($tables - 1, count($paths));
            }
            $tax = Data::getTax($tablePath);   // parsed once, rendered per sheet
            $first = (new Render($tax))->renderHtmlForm(['ext' => 'DB', 'file' => [], 'sheets' => '000'], null);
            $sheets = self::sheetOptions($first['sheets'] ?? null);
            $typedZ = null;
            if (preg_match("/class='app-typed-sheet' data-dimension='([^']+)' data-typ='([^']*)'/", (string) ($first['sheets'] ?? ''), $tz)) {
                // Typed z-axis: one sheet per key value in the file (what "Add" does on the form).
                $typedZ = ReportFile::local($tz[1]);
                $sheets = [];
                $values = [];
                foreach ($facts as $fact) {
                    if (isset($fact['typed'][$typedZ])) {
                        $values[$fact['typed'][$typedZ]] = true;
                    }
                }
                foreach (array_keys($values) as $kv) {
                    $sheets[] = json_encode(['typedMember' => [$tz[1] => ['typ' => $tz[2], 'value' => (string) $kv]], 'sheet' => 'k:'.$kv]);
                }
            }

            foreach ($sheets as $sheetJson) {
                $sheetDims = $sheetJson ? (json_decode($sheetJson, true) ?: []) : [];
                $zTyped = $sheetDims['typedMember'] ?? [];
                unset($sheetDims['sheet'], $sheetDims['metric'], $sheetDims['order'], $sheetDims['typedMember']);
                foreach ($sheetDims as $d => $m) {
                    $m = is_string($m) ? ReportFile::local($m) : '';
                    if ($m !== '' && ! preg_match('/^q?x0$/i', $m) && ! isset($present[strtolower(ReportFile::local((string) $d).'='.$m)])) {
                        continue 2;
                    }
                }
                $form = $sheetJson === null ? $first : (new Render($tax))->renderHtmlForm(['ext' => 'DB', 'file' => [], 'sheets' => '000'], $sheetJson);
                $cells = self::formCells((string) ($form['table'] ?? ''));
                $pairs = [];

                // Fixed cells.
                foreach ($cells as $cell => $dimJson) {
                    $dim = json_decode($dimJson, true) ?: [];
                    if (empty($dim['metric']) || $zTyped || in_array('*', $dim, true)) {
                        continue;
                    }
                    $fixed = array_diff_key($dim, ['metric' => 1, '__meta' => 1, 'typ' => 1, 'typedMember' => 1]) + $sheetDims;
                    $same = $plain[$sig($dim['metric'], $fixed)] ?? [];
                    if ($same) {
                        $used += array_fill_keys($same, true);
                        $pairs[$cell] = ['value' => $entered($facts[end($same)]['value'], $dim), 'dim' => $dimJson];
                    }
                }

                // Typed z-axis sheet: facts whose typed key is this sheet's.
                if ($zTyped) {
                    $zv = [];
                    foreach ($zTyped as $d => $t) {
                        $zv[ReportFile::local($d)] = (string) $t['value'];
                    }
                    foreach ($cells as $cell => $dimJson) {
                        $dim = json_decode($dimJson, true) ?: [];
                        if (empty($dim['metric'])) {
                            continue;
                        }
                        $want = $sig($dim['metric'], array_diff_key($dim, ['metric' => 1, '__meta' => 1, 'typ' => 1, 'typedMember' => 1]) + $sheetDims);
                        foreach ($byConcept[strtolower(ReportFile::local($dim['metric']))] ?? [] as $i) {
                            if ($facts[$i]['typed'] == $zv && $sig($facts[$i]['concept'], $facts[$i]['explicit']) === $want) {
                                $used[$i] = true;
                                $pairs[$cell] = ['value' => $entered($facts[$i]['value'], $dim), 'dim' => $dimJson];
                            }
                        }
                    }
                }

                // Open rows: key cells ("*") take the file's key values, one row per key set.
                $keyCells = [];
                foreach ($cells as $cell => $dimJson) {
                    $dim = json_decode($dimJson, true) ?: [];
                    foreach ($dim as $k => $v) {
                        if ($v === '*') {
                            $keyCells[ReportFile::local($k)] = ['col' => preg_replace('/r\d+$/', '', $cell), 'dim' => $dimJson, 'typed' => isset($dim['typ'])];
                        }
                    }
                }
                if ($keyCells && ! $zTyped) {
                    $rowsByKey = [];
                    foreach ($cells as $cell => $dimJson) {
                        $dim = json_decode($dimJson, true) ?: [];
                        if (empty($dim['metric'])) {
                            continue;
                        }
                        $fixed = array_filter(array_diff_key($dim, ['metric' => 1, '__meta' => 1, 'typ' => 1, 'typedMember' => 1]) + $sheetDims, fn ($m) => $m !== '*');
                        $col = preg_replace('/r\d+$/', '', $cell);
                        foreach ($byConcept[strtolower(ReportFile::local($dim['metric']))] ?? [] as $i) {
                            $explicit = $facts[$i]['explicit'];
                            $typed = $facts[$i]['typed'];
                            $keys = [];
                            foreach ($keyCells as $kd => $kc) {
                                $v = $kc['typed'] ? ($typed[$kd] ?? null) : ($explicit[$kd] ?? null);
                                if ($v === null) {
                                    continue 2;
                                }
                                $keys[$kd] = $v;
                                unset($explicit[$kd]);
                            }
                            if (array_diff_key($typed, $keyCells) || $sig('', $explicit) !== $sig('', $fixed)) {
                                continue;
                            }
                            $rk = json_encode($keys);
                            $rowsByKey[$rk] ??= count($rowsByKey) + 1;
                            $r = 'r'.$rowsByKey[$rk];
                            foreach ($keys as $kd => $v) {
                                $pairs[$keyCells[$kd]['col'].$r] = ['value' => $v, 'dim' => $keyCells[$kd]['dim']];
                            }
                            $pairs[$col.$r] = ['value' => $entered($facts[$i]['value'], $dim), 'dim' => $dimJson];
                            $used[$i] = true;
                        }
                    }
                }

                if (! $pairs) {
                    continue;
                }
                $header = FactHeader::query()->updateOrCreate(
                    ['module_id' => $instance->id, 'table_path' => Format::getAfterSpecChar($tablePath, $taxonomyFolder, strlen($taxonomyFolder) + 1)],
                    ['cr_sheet_code_last' => $sheetJson ? (json_decode($sheetJson, true)['sheet'] ?? '000') : '000']
                );
                $filled += FactTable::storeInstance(http_build_query($pairs), $header->id, $sheetJson, $header);
            }
        }

        $unmapped = [];
        foreach (array_diff_key($facts, $used) as $fact) {
            $unmapped[] = ReportFile::local($fact['concept']).' '.json_encode($fact['explicit'] + $fact['typed']);
        }

        return ['tables' => $tables, 'facts' => count($facts), 'filled' => $filled, 'mapped' => count($used), 'unmapped' => $unmapped];
    }

    /** Concept and explicit members by local name, lower case, default members (x0) left out. */
    private static function signature(string $concept, array $dims): string
    {
        $parts = [];
        foreach ($dims as $dimension => $member) {
            if (! is_string($member) || $member === '*') {
                continue;
            }
            $m = strtolower(ReportFile::local($member));
            if ($m === 'x0' || $m === 'qx0') {
                continue;
            }
            $parts[strtolower(ReportFile::local((string) $dimension))] = $m;
        }
        ksort($parts);

        return strtolower(ReportFile::local($concept)).'|'.http_build_query($parts);
    }

    private static function sheetOptions(?string $html): array
    {
        if (! $html || ! str_contains($html, '<option')) {
            return [null];
        }
        $doc = new DOMDocument();
        @$doc->loadHTML('<?xml encoding="utf-8"?>'.$html);
        $options = [];
        foreach ((new DOMXPath($doc))->query('//option') as $o) {
            $options[] = $o->getAttribute('value');
        }

        return $options ?: [null];
    }

    /** cell name → its "[dim]" json, for cells that take a value. */
    private static function formCells(string $html): array
    {
        if ($html === '') {
            return [];
        }
        $doc = new DOMDocument();
        @$doc->loadHTML('<?xml encoding="utf-8"?>'.$html);
        $xp = new DOMXPath($doc);
        $dims = $takes = [];
        foreach ($xp->query("//input[contains(@name,'[dim]')]") as $h) {
            $dims[substr($h->getAttribute('name'), 0, -5)] = $h->getAttribute('value');
        }
        foreach ($xp->query("//*[contains(@name,'[value]')]") as $in) {
            $takes[preg_replace('/\[value\](\[\])?$/', '', $in->getAttribute('name'))] = true;
        }

        return array_intersect_key($dims, $takes);
    }
}
