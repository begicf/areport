<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace DpmXbrl;

use DpmXbrl\Config\Config;
use DpmXbrl\Library\DomToArray;
use DpmXbrl\Render\Axis;
use DpmXbrl\Render\RenderExport;
use DpmXbrl\Render\RenderOutput;
use DpmXbrl\Render\RenderPDF;
use DpmXbrl\Render\RenderTable;
use Exception;

/**
 * Description of Tax
 *
 * @author begicf
 */
class Tax
{

    //put your code here
    private $tax;
    private $filename;
    private $path = array();
    private $arr;

    /**
     *

     * @param type $arr -
     * @param type $filename - naziv tabele
     */
    public function __construct($arr = NULL, $filename = NULL)
    {

        $this->tax = DomToArray::getPath(Config::publicDir(), ['tab' => 'tab' . DIRECTORY_SEPARATOR]);
        $this->arr = $arr;
        $this->filename = $filename;

        $this->getPathXsd();
    }

    public function getTax()
    {
        $tax = array();

        foreach ($this->path as $path):
            $table = new Set($path, $this->arr);
            foreach ($table->load() as $key => $row):
                $tax[$key] = $row->Xbrl;

            endforeach;
        endforeach;
        return $tax;
    }

    private function getPathXsd()
    {

        if (!is_null($this->filename)):

            foreach ($this->tax['tab'] as $row):

                if (strpos($row, $this->filename) !== false) {
                    try {
                        if (strpos($row, Config::$owner) !== false) {
                            $this->path[] = $row;
                        }
                    } catch (Exception $e) {


                    }
                }
            endforeach;

        endif;
    }

    public function getTableID($tax)
    {
        $tableNameId = key($tax['rend']['table']);

        $tableLabelName = $tax['rend']['table'][$tableNameId]['label'];
        $axis = new Axis($tax);

        $tableID =
            $axis->searchLabel($tax['rend']['path'] . "#" . $tableLabelName, 'http://www.eba.europa.eu/xbrl/role/dpm-db-id');
        return $tableID;
    }

    public function render()
    {

        return new RenderTable();
    }

    public function exportDB()
    {

        return new RenderExport();
    }

    public function export($tax, $lang, $type, $additionalData)
    {

        if (!isset($additionalData['file_path'])):
            $additionalData['file_path'] = Config::publicDir() . $this->filename;
        endif;
        return new RenderOutput($tax, $lang, $type, $additionalData);
    }

    public function exportPDF()
    {
        return new RenderPDF();
    }

}
