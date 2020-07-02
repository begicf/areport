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

                foreach ($result->factTable as $row):
                    $context[$row->id]['context'] = $row->xbrl_context_key_raw;
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
     * @param $table_path
     * @param $period
     * @param $module_path
     * @param null $sheet
     * @return array
     */
    public static function getCRData($table_path, $period, $module_path, $sheet = null, $all = null): ?array
    {

        $data = [];
        $r = 0;

        $fact_module = FactModule::where([
            ['period', '=', $period],
            ['module_path', '=', $module_path]
        ])->first();


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
