<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class FactHeader extends Model
{
    protected $table = 'fact_header';
    protected $fillable = ['taxonomy_id', 'table_path', 'module_path', 'period'];

    public function facttable()
    {

        return $this->hasMany('App\Model\FactTable', 'fact_header_id', 'id');
    }

    public static function getCRData($table_path, $period, $module_path)
    {

        $data = [];
        $r = 0;

        $result =
            FactHeader::with('facttable')->where(['table_path' => $table_path, 'period' => $period, 'module_path' => $module_path])->first();

        if (isset($result->facttable)):
            foreach ($result->facttable as $row):

                $data[$row->cr_code]['integer'] = floatval($row->string_value);
                $data[$row->cr_code]['string'] = $row->string_value;

                $r = substr($row->cr_code, strpos($row->cr_code, "r") + 1);;

            endforeach;
            $data['row'] = $r - 1;
            return $data;

        endif;

    }
}
