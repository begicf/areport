<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class FactHeader extends Model
{
    protected $table = 'fact_header';
    protected $fillable = ['taxonomy_id', 'module_id', 'table_path', 'module_path', 'module_name', 'period', 'cr_sheet_code_last'];


    public function factTable()
    {

        return $this->hasMany('App\Model\FactTable', 'fact_header_id', 'id');
    }

    public function factModule()
    {

        return $this->belongsTo('App\Model\FactModule','module_id','id');

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
        dump($table_path, $period, $module_path);

        $fact_module = self::whereHas('factModule', function ($query) use ($period, $module_path) {
            $query->period = $period;
            $query->module_path = $module_path;
        })->first();


        dd($fact_module);

        $result = $fact_module::with('factTable')->where(['table_path', '=', $table_path])->first();

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
