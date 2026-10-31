<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class FactTable extends Model
{
    protected $table = 'fact_table';
    protected $fillable = ['fact_header_id', 'cr_code', 'string_value', 'cr_sheet_code', 'metric', 'xbrl_context_key', 'xbrl_context_key_raw', 'user_id'];

    /**
     * Store the submitted form as the full state of one table sheet: new and changed
     * values are written, cleared cells (and rows removed from an open table) are
     * deleted, and every change is recorded in fact_audit.
     *
     * @return int number of cells changed
     */
    public static function storeInstance($req, $factheader_id, $sheet, ?FactHeader $header = null)
    {


        parse_str($req, $res);

        $cr_sheet = null;
        if (isset($sheet)):
            $sheet = (json_decode($sheet, true));
            $cr_sheet = $sheet['sheet'] ?? null;
            unset($sheet['sheet'],$sheet['metric'],$sheet['order']);
            if (empty($sheet)) {
                $sheet = null;
            }
        endif;
        $sheetCode = $cr_sheet ?? '000';

        $existing = self::query()
            ->where('fact_header_id', $factheader_id)
            ->where('cr_sheet_code', $sheetCode)
            ->pluck('string_value', 'cr_code')
            ->all();

        $audit = [];
        $submitted = [];

        foreach ($res as $key => $row):

            if (!is_array($row) || !array_key_exists('value', $row)) {
                continue;
            }

            $value = is_string($row['value']) ? trim($row['value']) : $row['value'];
            $submitted[$key] = true;

            // "0" is a reported value; only an empty field means "no value".
            if ($value === '' || $value === null):
                if (array_key_exists($key, $existing)) {
                    self::query()->where('fact_header_id', $factheader_id)
                        ->where('cr_sheet_code', $sheetCode)->where('cr_code', $key)->delete();
                    $audit[] = [$key, $existing[$key], null];
                }
                continue;
            endif;

            if (array_key_exists($key, $existing) && (string) $existing[$key] === (string) $value) {
                continue;
            }

            $arr = json_decode($row['dim'] ?? '', true);

            if (!is_array($arr)) {
                $arr = [];
            }

            if (isset($arr['metric'])):
                $metric = $arr['metric'];
            else:
                $metric = NULL;
            endif;

            $raw = $arr;

            if (isset($sheet)):
                $raw = array_merge($raw, $sheet);
            endif;

            $arr = $raw;

            unset($arr['metric'], $arr['__meta']);

            if (isset($arr['typedMember'])):
                unset($arr['typedMember']);
                $dim = self::makeString($arr);
            else:

                $dim = self::makeString($arr);
            endif;

            self::updateOrCreate([
                'fact_header_id' => $factheader_id,
                'cr_code' => $key,
                'cr_sheet_code' => $cr_sheet ?? '000'

            ], [
                'fact_header_id' => $factheader_id,
                'cr_code' => $key,
                'cr_sheet_code' => $cr_sheet ?? '000',
                'xbrl_context_key' => $dim,
                'xbrl_context_key_raw' => json_encode($raw),
                'string_value' => $value,
                'metric' => $metric,
                'user_id' => Auth::id()
            ]);

            $audit[] = [$key, $existing[$key] ?? null, $value];

        endforeach;

        // Cells stored for this sheet but no longer on the form (rows removed from an
        // open table). Only when the form actually carried cells, so an empty or failed
        // render never wipes a table.
        if (!empty($submitted)) {
            foreach (array_diff_key($existing, $submitted) as $key => $old) {
                self::query()->where('fact_header_id', $factheader_id)
                    ->where('cr_sheet_code', $sheetCode)->where('cr_code', $key)->delete();
                $audit[] = [$key, $old, null];
            }
        }

        if ($audit && $header) {
            $now = now();
            FactAudit::insert(array_map(fn ($a) => [
                'fact_module_id' => $header->module_id,
                'fact_header_id' => $header->id,
                'table_path' => $header->table_path,
                'cr_sheet_code' => $sheetCode,
                'cr_code' => $a[0],
                'old_value' => $a[1],
                'new_value' => $a[2],
                'user_id' => Auth::id(),
                'created_at' => $now,
            ], $audit));
        }

        return count($audit);
    }

    /**
     * @param $arr
     * @return string
     */
    private static function makeString($arr)
    {


        if (isset($arr['typ'])):
            unset($arr['typ']);
        endif;

        $tmp = array();
        foreach ($arr as $key => $value) {
            $val = substr(strstr($value, ':x'), strlen(':x'));
            $keyDomen = strstr($value, ':x', TRUE);

            $key = substr(strstr($key, ':'), strlen(':'));

            if ($val != 0):
                $tmp[$key] = $keyDomen . ':x' . $val;
            elseif ($val === FALSE):
                $tmp[$key] = $value;
            endif;
        }

        ksort($tmp);

        $string = NULL;
        foreach ($tmp as $key => $str):

            $string .= $key . '=' . $str . ',';

        endforeach;

        return rtrim($string, ',');
    }
}
