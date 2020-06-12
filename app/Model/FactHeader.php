<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class FactHeader extends Model
{
    protected $table = 'fact_header';
    protected $fillable = ['taxonomy_id', 'table_path', 'module_path', 'period', 'cr_sheet_code_last'];

    public function facttable()
    {

        return $this->hasMany('App\Model\FactTable', 'fact_header_id', 'id');
    }

    /**
     * @param $table_path
     * @param $period
     * @param $module_path
     * @param null $sheet
     * @return array
     */
    public static function getCRData($table_path, $period, $module_path, $sheet = null): ?array
    {

        $data = [];
        $r = 0;

        $result =
            self::with('facttable')->where(['table_path' => $table_path, 'period' => $period, 'module_path' => $module_path])->first();

        if (isset($result->facttable)):

            $sheet = (is_null($sheet)) ? $result->cr_sheet_code_last : $sheet;

            $filter = $result->facttable->where('cr_sheet_code', $sheet);

            foreach ($filter as $row):

                $data[$row->cr_code]['integer'] = floatval($row->string_value);
                $data[$row->cr_code]['string'] = $row->string_value;

                $r = substr($row->cr_code, strpos($row->cr_code, "r") + 1);;

            endforeach;
            $data['row'] = $r - 1;
            $data['sheets'] = ($sheet != '000') ? self::getSheet($result) : '000';

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

        foreach ($result->facttable as $row):

            $data[$row->cr_sheet_code] = 'found';
        endforeach;

        $data[$result->cr_sheet_code_last] = 'active';

        return $data;
    }
}
