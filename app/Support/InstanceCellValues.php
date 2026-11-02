<?php

namespace App\Support;

use App\Model\FactModule;
use App\Model\Taxonomy;

/**
 * A saved instance's values as the DPM-XL service takes them ("tc_01.00,r0010,c0010"
 * [,s…] => value), with each value's rounding half-width (interval arithmetic), its
 * dimensions ([where …] / group by), the tables reported and the instance's framework.
 * Used by validation, and by the DPM Architect to try a rule on a report.
 */
class InstanceCellValues
{
    /** @return array{cells: array, codes: array, framework: mixed, tolerance: array, dims: array} */
    public static function of(FactModule $module, ?Taxonomy $preferred = null): array
    {
        $preferId = $preferred?->id ?? $module->taxonomy_id;
        $tableModel = \Begicf\AReportDpm\Models\DpmTable::class;
        $cellValues = [];
        $tolerance = [];
        $dims = [];
        $codes = [];
        // The framework named by the module path (…/fws/corep/…) in the instance's own
        // taxonomy — the one its rules were imported under.
        $frameworkCode = \AReportDpmXBRL\Library\ModuleInfo::frameworkCode((string) $module->module_path);
        $framework = $frameworkCode
            ? \Begicf\AReportDpm\Models\Framework::query()->where('taxonomy_id', $module->taxonomy_id)->where('code', $frameworkCode)->first()
            : null;

        foreach ($module->factHeader as $header) {
            $tcode = strtolower(pathinfo((string) $header->table_path, PATHINFO_FILENAME));
            if ($tcode === '') {
                continue;
            }
            $codes[$tcode] = true;
            if (! $framework) {
                // Prefer the active taxonomy, else any taxonomy defining this table
                // (that's where its rules live).
                $t = $tableModel::query()->whereRaw('LOWER(code) = ?', [$tcode])
                    ->orderByRaw('CASE WHEN taxonomy_id = ? THEN 0 ELSE 1 END', [$preferId])
                    ->first();
                $framework = $t?->framework;
            }
            // Values as reported (amounts × the instance's unit, percentages as ratios), with
            // the half-width of their rounding for interval arithmetic (0.5·10^-decimals).
            foreach ($header->factTable()->get(['cr_code', 'cr_sheet_code', 'string_value', 'metric', 'xbrl_context_key_raw']) as $fact) {
                if (! preg_match('/^c([^r]+)r(.+)$/i', (string) $fact->cr_code, $m)) {
                    continue;
                }
                $v = $fact->string_value;
                if ($v === '' || $v === null) {
                    continue;
                }
                $key = 't'.$tcode.',r'.strtolower($m[2]).',c'.strtolower($m[1]);
                // A z-axis sheet adds a component (s0010, or the sheet member's code).
                $sheet = (string) $fact->cr_sheet_code;
                if ($sheet !== '' && $sheet !== '000') {
                    $key .= ',s'.strtolower(preg_replace('/[^A-Za-z0-9.]/', '', $sheet));
                }
                // The data point's dimensions by code (CEG, qEEA, …) for [where …] / group by.
                $raw = json_decode((string) $fact->xbrl_context_key_raw, true);
                if (is_array($raw)) {
                    foreach ($raw as $dim => $member) {
                        if (is_string($member) && ! in_array($dim, ['metric', 'typ', 'sheet', 'order'], true) && str_contains($dim, ':')) {
                            $dims[$key][substr($dim, strrpos($dim, ':') + 1)] = $member;
                        }
                    }
                }
                $kind = is_numeric($v) ? \AReportDpmXBRL\Library\MetricTypes::kind((string) ($module->taxonomy->folder ?? ''), $fact->metric) : null;
                $scaled = $kind ? \AReportDpmXBRL\Library\FactValue::forKind($kind, (string) $v, (int) ($module->monetary_scale ?: 1000)) : $v;
                $cellValues[$key] = is_numeric($scaled) ? ($scaled + 0) : $scaled;
                $decimals = ['monetary' => $module->decimals_monetary ?? -3, 'percentage' => $module->decimals_percentage ?? 4,
                    'integer' => $module->decimals_integer ?? 0, 'decimal' => $module->decimals_decimal ?? 2][$kind] ?? null;
                if ($decimals !== null) {
                    $tolerance[$key] = 0.5 * (10 ** (-$decimals));
                }
            }
        }

        return ['cells' => $cellValues, 'codes' => array_keys($codes), 'framework' => $framework, 'tolerance' => $tolerance, 'dims' => $dims];
    }
}
