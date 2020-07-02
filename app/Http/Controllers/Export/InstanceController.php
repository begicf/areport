<?php

namespace App\Http\Controllers\Export;

use App\Http\Controllers\Controller;
use App\Model\FactHeader;
use App\Model\FactModule;
use AReportDpmXBRL\Creat\CreateXBRLFromDB;

class InstanceController extends Controller
{

    private function json_decode_array($input)
    {
        $arr = [];
        $from_json = json_decode($input, true);
        foreach ($from_json as $row):
            $arr[] = $row;
        endforeach;
        return $arr;
    }


    private function array_flatten($array)
    {
        if (!is_array($array)) {
            return FALSE;
        }
        $result = array();
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $result = array_merge($result, $this->array_flatten($value));
            } else {
                $result[$key] = $value;
            }
        }
        return $result;
    }

    public function exportInstance()
    {

        $fact_module = FactModule::where([
            ['period', '=', request('period')],
            ['module_path', '=', request('mod')]
        ])->with(['taxonomy', 'factHeader'])->first();

        $find = [];

        foreach ($fact_module->factHeader as $row):
            $find[] = $row->table_path;
        endforeach;

        $data = FactHeader::prepareDataForXbrl($fact_module->id, request('mod'));


        $instance =
            new CreateXBRLFromDB(request('period'), request('mod'), $data, $find, $fact_module->taxonomy->folder);
        $file = $instance->writeXbrl();
        dump($file);
        dump(\request()->all());


    }
}
