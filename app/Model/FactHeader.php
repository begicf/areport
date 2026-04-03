<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class FactHeader extends Model
{
    protected $table = 'fact_header';
    protected $fillable = ['module_id', 'table_path', 'module_path', 'module_name', 'period', 'cr_sheet_code_last'];


    public function factTable()
    {

        return $this->hasMany('App\Model\FactTable', 'fact_header_id', 'id');
    }

    public function factModule()
    {

        return $this->belongsTo('App\Model\FactModule', 'id');

    }

    /**
     * @param $module_path
     * @param $period
     */
    public static function prepareDataForXbrl($fact_module_id, $period): array
    {


        $results =
            self::with('factTable')->where([
                ['module_id', '=', $fact_module_id]
            ])->get();

        $context = [];
        $i = 0;

        foreach ($results as $result) :

            if (isset($result->factTable)):
                $rowContextMap = self::buildRowKeyContextMap($result->factTable);

                foreach ($result->factTable as $row):
                    $parsedCode = self::parseCrCode($row->cr_code);
                    $groupKey = self::buildRowGroupKey($row->cr_sheet_code, $parsedCode['row_code'] ?? null);
                    $factContext = self::sanitizeRawContext($row->xbrl_context_key_raw);

                    if (!empty($row->metric) && isset($rowContextMap[$groupKey])) {
                        $factContext = self::mergeExportContext($factContext, $rowContextMap[$groupKey]);
                    }

                    $context[$row->id]['context'] = json_encode($factContext);
                    $context[$row->id]['period'] = $period;
                    $context[$row->id]['metric'] = $row->metric;
                    $context[$row->id]['numeric_value'] = $row->string_value;
                    $context[$row->id]['sheetcode'] = $row->cr_sheet_code;
                    $context[$row->id]['string_value'] = $row->string_value;
                    $context[$row->id]['cr_code'] = $row->cr_code;
                    $i++;
                endforeach;
            endif;

        endforeach;


        return $context;

    }

    /**
     * Build a neutral fact payload for xBRL-CSV packaging.
     *
     * @param $fact_module_id
     * @param $period
     * @return array
     */
    public static function prepareDataForXbrlCsv($fact_module_id, $period): array
    {
        $results = self::with('factTable')->where([
            ['module_id', '=', $fact_module_id]
        ])->get();

        $facts = [];

        foreach ($results as $result) :
            if (!isset($result->factTable)):
                continue;
            endif;

            foreach ($result->factTable as $row):
                $parsedCode = self::parseCrCode($row->cr_code);
                $columnCode = $parsedCode['column_code'] ?? null;
                $rowCode = $parsedCode['row_code'] ?? null;
                $rowIndex = $parsedCode['row_index'] ?? null;

                $rawContext = self::decodeRawContext($row->xbrl_context_key_raw);
                $context = self::sanitizeRawContext($rawContext);
                $meta = self::extractRawMeta($rawContext);

                $facts[] = [
                    'fact_header_id' => $row->fact_header_id,
                    'table_path' => $result->table_path,
                    'module_path' => $result->module_path,
                    'period' => $period,
                    'sheetcode' => $row->cr_sheet_code,
                    'cr_code' => $row->cr_code,
                    'column_code' => $columnCode,
                    'row_code' => $rowCode,
                    'row_index' => $rowIndex,
                    'metric' => $row->metric,
                    'value' => $row->string_value,
                    'context' => $context,
                    'meta' => $meta,
                ];
            endforeach;
        endforeach;

        return $facts;
    }

    private static function decodeRawContext($raw): array
    {
        if (is_array($raw)) {
            return $raw;
        }

        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : [];
    }

    private static function parseCrCode($crCode): array
    {
        if (preg_match('/^(c[0-9A-Za-z._-]+?)(r\d+)$/', (string) $crCode, $matches)) {
            return [
                'column_code' => $matches[1],
                'row_code' => $matches[2],
                'row_index' => intval(substr($matches[2], 1)),
            ];
        }

        return [];
    }

    private static function sanitizeRawContext($raw): array
    {
        $context = self::decodeRawContext($raw);

        unset($context['metric'], $context['__meta']);

        return $context;
    }

    private static function extractRawMeta($raw): array
    {
        $context = self::decodeRawContext($raw);

        return (isset($context['__meta']) && is_array($context['__meta'])) ? $context['__meta'] : [];
    }

    private static function buildRowKeyContextMap($rows): array
    {
        $map = [];

        foreach ($rows as $row) {
            $parsedCode = self::parseCrCode($row->cr_code);

            if (empty($parsedCode['row_code'])) {
                continue;
            }

            $rawContext = self::decodeRawContext($row->xbrl_context_key_raw);
            $context = self::sanitizeRawContext($rawContext);
            $groupKey = self::buildRowGroupKey($row->cr_sheet_code, $parsedCode['row_code']);
            $typed = $rawContext['typ'] ?? null;

            foreach ($context as $dimensionKey => $dimensionValue) {
                if ($dimensionValue !== '*') {
                    continue;
                }

                if ($row->string_value === null || $row->string_value === '') {
                    continue;
                }

                if (self::looksLikeQName($row->string_value)) {
                    $map[$groupKey][$dimensionKey] = $row->string_value;
                    continue;
                }

                if (!empty($typed)) {
                    $map[$groupKey]['typedMember'][$dimensionKey] = [
                        'typ' => $typed,
                        'value' => $row->string_value,
                    ];
                }
            }
        }

        return $map;
    }

    private static function buildRowGroupKey($sheetCode, $rowCode): string
    {
        return ($sheetCode ?? '000') . '|' . ($rowCode ?? 'r0');
    }

    private static function mergeExportContext(array $context, array $rowContext): array
    {
        foreach ($rowContext as $key => $value) {
            if ($key === 'typedMember') {
                $context['typedMember'] = array_merge($context['typedMember'] ?? [], $value);
                continue;
            }

            if (!array_key_exists($key, $context)) {
                $context[$key] = $value;
            }
        }

        return $context;
    }

    private static function looksLikeQName($value): bool
    {
        return is_string($value) && preg_match('/^[A-Za-z_][A-Za-z0-9._-]*:[A-Za-z0-9._-]+$/', $value) === 1;
    }

    /**
     * @param $table_path
     * @param $period
     * @param $module_path
     * @param null $sheet
     * @return array
     */
    public static function getCRData($table_path, $period, $module_path, $sheet = null, $all = null, $taxonomyId = null): ?array
    {

        $data = [];
        $r = 0;

        $factModuleQuery = FactModule::query()->where([
            ['period', '=', $period],
            ['module_path', '=', $module_path]
        ]);

        if (!is_null($taxonomyId)) {
            $factModuleQuery->where('taxonomy_id', '=', $taxonomyId);
        }

        $fact_module = $factModuleQuery->first();


        if (!is_null($fact_module)):

            $result =
                self::with('factTable')->where([
                    ['table_path', '=', $table_path],
                    ['module_id', '=', $fact_module->id]
                ])->first();

            if (isset($result->factTable)):

                $sheet = (is_null($sheet)) ? $result->cr_sheet_code_last : $sheet;

                $filter = is_null($all) ? $result->factTable->where('cr_sheet_code', $sheet) : $result->factTable;

                foreach ($filter as $row):

                    if (is_null($all)):
                        $data[$row->cr_code]['integer'] = floatval($row->string_value);
                        $data[$row->cr_code]['string'] = $row->string_value;
                    else:

                        $data[$row->cr_sheet_code][$row->cr_code]['integer'] = floatval($row->string_value);
                        $data[$row->cr_sheet_code][$row->cr_code]['string'] = $row->string_value;
                    endif;
                    $r = substr($row->cr_code, strpos($row->cr_code, "r") + 1);;

                endforeach;

                $data['row'] = $r - 1;

                $data['sheets'] = is_null($all) ? ($sheet != '000') ? self::getSheet($result) : '000' : '';

                return $data;

            endif;
        endif;
        return $data;

    }

    /**
     * @param $module_path
     * @param $period
     * @return array
     */
    private static function getSheet($result): array
    {
        $data = [];

        foreach ($result->factTable as $row):

            $data[$row->cr_sheet_code] = 'found';
        endforeach;

        $data[$result->cr_sheet_code_last] = 'active';

        return $data;
    }
}
