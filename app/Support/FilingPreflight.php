<?php

namespace App\Support;

use App\Model\FactModule;
use AReportDpmXBRL\Library\Lei;
use AReportDpmXBRL\Library\ModuleInfo;
use Carbon\Carbon;

/**
 * Technical checks an EBA submission must pass before it leaves the house: who
 * reports (LEI, consolidation scope, country), for which date (month end, within the
 * module version's applicability), in which units, and which templates (filing
 * indicators against the data actually entered). Errors block the export.
 */
class FilingPreflight
{
    public function __construct(private FactModule $instance)
    {
    }

    /** Module JSON facts (version, applicability, filing indicators), or null for XML-only modules. */
    public function moduleInfo(): ?array
    {
        $folder = $this->instance->taxonomy->folder ?? null;

        return $folder ? ModuleInfo::load($folder, (string) $this->instance->module_path) : null;
    }

    /**
     * Every filing indicator of the module with whether the instance holds data for it
     * and the explicit choice, if any: [code => ['has_data' => bool, 'choice' => ?bool]].
     */
    public function templates(): array
    {
        $info = $this->moduleInfo();
        $tableToIndicator = [];
        $result = [];

        foreach (($info['filing_indicators'] ?? []) as $code => $tables) {
            $result[$code] = ['has_data' => false, 'choice' => null];
            foreach ($tables as $table) {
                $tableToIndicator[$table] = $code;
            }
        }

        $headers = $this->instance->factHeader()->withCount('factTable')->get(['id', 'table_path', 'module_id']);
        foreach ($headers as $header) {
            $table = strtolower(pathinfo((string) $header->table_path, PATHINFO_FILENAME));
            // Table "c_07.00.a" files under indicator "C_07.00" when the module JSON is absent.
            $code = $tableToIndicator[$table] ?? strtoupper(preg_replace('/\.[a-z]$/', '', $table));
            $result[$code] = $result[$code] ?? ['has_data' => false, 'choice' => null];
            $result[$code]['has_data'] = $result[$code]['has_data'] || $header->fact_table_count > 0;
        }

        foreach ((array) $this->instance->filing_indicators as $code => $choice) {
            $result[$code] = $result[$code] ?? ['has_data' => false, 'choice' => null];
            $result[$code]['choice'] = (bool) $choice;
        }

        ksort($result, SORT_NATURAL);

        return $result;
    }

    /**
     * Rows of open tables that hold values but miss a key column. Key columns come
     * from the table's xBRL-CSV metadata (columns other than datapoint/factValue,
     * laid out along X); a key cell is c<headerCode>r<row>.
     *
     * @return array<int, array{table: string, row: int, column: string}>
     */
    public function openRowsWithoutKey(): array
    {
        $root = storage_path('app/public/tax/'.($this->instance->taxonomy->folder ?? ''));
        $out = [];
        foreach ($this->instance->factHeader()->with('factTable')->get() as $header) {
            $json = $root.'/'.preg_replace('/\.xsd$/i', '.json', ltrim((string) $header->table_path, '/'));
            $meta = is_file($json) ? json_decode((string) file_get_contents($json), true) : null;
            $template = is_array($meta['tableTemplates'] ?? null) ? reset($meta['tableTemplates']) : null;
            $keys = [];
            foreach ((array) ($template['columns'] ?? []) as $name => $col) {
                $doc = $col['eba:documentation'] ?? [];
                if (! in_array($name, ['datapoint', 'factValue'], true) && ($doc['headerDirection'] ?? '') === 'X' && ! empty($doc['headerCode'])) {
                    $keys[strtolower('c'.$doc['headerCode'])] = $name;
                }
            }
            if (! $keys) {
                continue;
            }
            $cells = [];
            $valueRows = [];
            foreach ($header->factTable as $f) {
                if (! preg_match('/^(c[0-9a-z._-]+?)r(\d+)$/i', (string) $f->cr_code, $m)) {
                    continue;
                }
                $cells[$f->cr_sheet_code][strtolower($m[1])][(int) $m[2]] = trim((string) $f->string_value) !== '';
                if (! empty($f->metric) && ! isset($keys[strtolower($m[1])])) {
                    $valueRows[$f->cr_sheet_code][(int) $m[2]] = true;
                }
            }
            $table = strtoupper(basename(dirname((string) $header->table_path)));
            foreach ($valueRows as $sheet => $rows) {
                foreach (array_keys($rows) as $row) {
                    foreach ($keys as $col => $name) {
                        if (empty($cells[$sheet][$col][$row])) {
                            $out[] = ['table' => $table, 'row' => $row, 'column' => $name.' ('.substr($col, 1).')'];
                        }
                    }
                }
            }
        }

        return $out;
    }

    /**
     * @param string $format 'csv' | 'xml'
     * @return array{ok: bool, checks: array<int, array{level: string, code: string, message: string}>, package: ?string}
     */
    public function run(string $format = 'csv'): array
    {
        $checks = [];
        $add = function (string $level, string $code, string $message) use (&$checks) {
            $checks[] = compact('level', 'code', 'message');
        };

        $m = $this->instance;
        $entity = $m->entityRecord();
        $info = $this->moduleInfo();
        $refDate = $m->period ? Carbon::parse($m->period) : null;

        // Who reports
        if (! $entity) {
            $add('error', 'entity_missing', 'No reporting entity is set on this instance.');
        } elseif (! Lei::isValid($entity->lei)) {
            $add('error', 'lei_invalid', "The LEI {$entity->lei} of {$entity->name} fails the ISO 17442 check digits.");
        }
        if (! in_array($m->consolidation, ['CON', 'IND'], true)) {
            $add('error', 'consolidation', 'Set the consolidation scope (CON or IND); it is part of the entity identifier.');
        }
        if (! preg_match('/^[A-Z]{2}$/', (string) $m->filingCountry())) {
            $add('error', 'country', 'Set the country of the competent authority; the EBA package name requires it.');
        }

        // For which date
        if (! $refDate) {
            $add('error', 'refdate_missing', 'The instance has no reference date.');
        } else {
            if (! $refDate->isSameDay($refDate->copy()->endOfMonth())) {
                $add('error', 'refdate_month_end', 'The reference date '.$refDate->format('d.m.Y').' is not a month end; EBA reference dates are period ends (e.g. 31.12.2026).');
            }
            if (! empty($info['from']) && $refDate->lt(Carbon::parse($info['from']))) {
                $add('error', 'refdate_before_module', "This module version ({$info['version']}) applies from ".Carbon::parse($info['from'])->format('d.m.Y').'; use the taxonomy release valid for '.$refDate->format('d.m.Y').'.');
            }
            if (! empty($info['to']) && $refDate->gt(Carbon::parse($info['to']))) {
                $add('error', 'refdate_after_module', "This module version ({$info['version']}) applies up to ".Carbon::parse($info['to'])->format('d.m.Y').'.');
            }
        }

        // Units
        if (! preg_match('/^[A-Z]{3}$/', (string) $m->base_currency)) {
            $add('error', 'currency', 'Set the base currency as an ISO 4217 code (e.g. EUR).');
        }
        if (($m->decimals_monetary ?? -3) < -4) {
            $add('warning', 'decimals_monetary', 'Monetary decimals below -4 are coarser than EBA filing rules allow.');
        }
        if (($m->decimals_percentage ?? 4) < 4) {
            $add('warning', 'decimals_percentage', 'Percentages should be reported with at least 4 decimals.');
        }

        // Which templates
        $templates = $this->templates();
        $reported = 0;
        foreach ($templates as $code => $t) {
            if ($t['choice'] === false && $t['has_data']) {
                $add('error', 'fi_false_with_data', "{$code} is marked \"not reported\" but holds data. Remove the data or report the template.");
            } elseif ($t['choice'] === true && ! $t['has_data']) {
                $add('warning', 'fi_true_without_data', "{$code} is marked \"reported\" but has no data.");
            }
            if ($t['has_data'] && $t['choice'] !== false) {
                $reported++;
            }
        }
        if ($reported === 0) {
            $add('error', 'no_data', 'No template holds data, so there is nothing to report.');
        }

        // Open rows: every row with values needs its key (e.g. the counterparty or
        // country column); without it the fact has no identity (xBRL-CSV tcre:missingValue).
        foreach ($this->openRowsWithoutKey() as $miss) {
            $add('error', 'open_row_key_missing', "{$miss['table']} row {$miss['row']}: enter {$miss['column']} — the row holds values but its key is empty.");
        }

        // Validation (the instance's last full run against the module's rules)
        $status = $m->status();
        if ($status['key'] === 'draft') {
            $add('warning', 'not_validated', $m->validated_at
                ? 'The data changed since the last validation. Validate again before filing.'
                : 'The instance has not been validated. Run the validation report before filing.');
        } elseif ($status['errors'] > 0) {
            $add('warning', 'validation_errors', "The last validation found {$status['errors']} blocking error(s). The authority will reject the report until they are fixed.");
        }

        // Format
        if ($format === 'csv' && ! $info) {
            $add('error', 'csv_metadata_missing', 'This module has no xBRL-CSV metadata (a pre-DPM 2.0 module). Export it as xBRL-XML.');
        }
        // Filing Rules v5.9: xBRL-CSV only for reference dates from 31 March 2026 (resubmissions included).
        if ($format === 'xml' && $refDate && $refDate->gte(Carbon::create(2026, 3, 31))) {
            $add('error', 'xml_not_accepted', 'xBRL-XML is not accepted for reference dates from 31.03.2026. Export the xBRL-CSV package.');
        } elseif ($format === 'xml' && $info) {
            $add('warning', 'xml_legacy', 'EBA 4.x frameworks are remitted as xBRL-CSV. Use xBRL-XML only for earlier reference dates your authority still accepts.');
        }

        $package = null;
        if ($info && $entity && $refDate && $m->filingCountry()) {
            $id = strtoupper((string) $entity->lei).'.'.($m->consolidation ?: 'CON');
            $package = ModuleInfo::packageName($id, $m->filingCountry(), $info, $refDate->format('Y-m-d'));
        }

        return [
            'ok' => collect($checks)->where('level', 'error')->isEmpty(),
            'checks' => $checks,
            'package' => $package ? $package.($format === 'xml' ? '.xbrl' : '.zip') : null,
        ];
    }
}
