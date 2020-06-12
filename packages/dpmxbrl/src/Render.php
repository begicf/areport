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
 * Class Tax
 * @category
 * Areport @package DpmXbrl
 * @author Fuad Begic <fuad.begic@gmail.com>
 * Date: 12/06/2020
 */
class Render
{

    //put your code here
    private $tax;
    private $filename;
    private $path = array();


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



    public function export($tax, $lang, $type, $additionalData)
    {

        if (!isset($additionalData['file_path'])):
            $additionalData['file_path'] = Config::publicDir() . $this->filename;
        endif;
        return new RenderOutput($tax, $lang, $type, $additionalData);
    }



}
