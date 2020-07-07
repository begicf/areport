<?php

namespace App\Http\Controllers\Export;

use App\Http\Controllers\Controller;
use App\Model\FactHeader;
use App\Model\FactModule;
use App\Model\Taxonomy;
use AReportDpmXBRL\Creat\CreateXBRLFromDB;
use AReportDpmXBRL\Library\Format;


class InstanceController extends Controller
{
    public function __construct()
    {
        $this->_taxonomy = Taxonomy::all()->where('active', '=', 1)->first();
    }

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


    private function getNormalizeModule($module)
    {
        if (strpos($module, $this->_taxonomy->folder)):
            return Format::getAfterSpecChar($module, $this->_taxonomy->folder, strlen($this->_taxonomy->folder) + 1);
        endif;
        return $module;
    }

    public function exportInstance()
    {

        $module=$this->getNormalizeModule(request('mod'));

        $fact_module = FactModule::where([
            ['period', '=', request('period')],
            ['module_path', '=', $module]
        ])->with(['taxonomy', 'factHeader'])->first();

        $find = [];

        foreach ($fact_module->factHeader as $row):
            $find[] = $row->table_path;
        endforeach;

        $data = FactHeader::prepareDataForXbrl($fact_module->id, request('mod'));


        $instance =
            new CreateXBRLFromDB(request('period'), $module, $data, $find, $fact_module->taxonomy->folder);
        $file = $instance->writeXbrl();

        return response()->streamDownload(function () use ($file) {
            echo $file;
        }, 'areport.xbrl', ['Content-Type' => 'text/xml']);


    }
}
