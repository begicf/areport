<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace DpmXbrl\Render;

use DpmXbrl\Library\DomToArray;
use DpmXbrl\Helper\ExtendTCPDF;

/**
 * A.A.R.P. ( Augmentirani Automatski Render PDF-a)
 * Description of RenderPDF
 *
 * @authors begicf, ciricf
 *  */
class RenderPDF {

//put your code here

    private $specification;
    private $breakdownTreeArc;
    private $row = array();
    private $col = array();
    private $map = array();
    private $merge_ids = array(0);
    
    
    
    private function HideZeros($value) {
        //Doubleovi se javljaju sa tackom. Ako je double, zamijenit ce '.' sa ','
        if ($value == '0') return null;
        else return str_replace('.',',',$value);
    }
    
    private function FormatPercent($percent_value, $decimal_places=2, $decimal_delimiter=',', $percent_sufix='', $hide_nulls='1') {
        if($hide_nulls=='1' && empty($percent_value)){
            return null;    
        }
        else {
            $percent_value = (substr($percent_value, 0, 1) == '.') ? "0".$percent_value : $percent_value;
            $percent_value = number_format($percent_value, $decimal_places, $decimal_delimiter, '');
            $percent_value = (!empty($percent_sufix)) ? $percent_value.$percent_sufix : $percent_value;
            return $percent_value;
        }
    }
    
    
    
    private function myWordWrap($string, $length=3, $wrap='.', $from='right') {
        if (substr($string, 0, 1) == '-') {
            $string = substr($string, 1);

            if ($from == 'left')
                $txt = wordwrap($string, $length, $wrap, true);
            if ($from == 'right') {
                $txt = strrev($string);
                $temp = wordwrap($txt, $length, $wrap, true);
                $txt = strrev($temp);
            }

            return "-" . $txt;
        } else {

            if ($from == 'left')
                $txt = wordwrap($string, $length, $wrap, true);
            if ($from == 'right') {
                $txt = strrev($string);
                $temp = wordwrap($txt, $length, $wrap, true);
                $txt = strrev($temp);
            }

            return $txt;
        }
    }
    
    
    private function MapSetCellType($pColumn = 0, $pRow = 1,$cellType = 'default'){
        
        if(!isset($this->map[$pRow-1]))
            $this->map[$pRow-1] = array();
        if(!isset($this->map[$pRow-1][$pColumn]))
            $this->map[$pRow-1][$pColumn] = array();
        if(isset($this->map[$pRow-1][$pColumn]['TYPE']))
            $this->map[$pRow-1][$pColumn]['TYPE'] = $this->map[$pRow-1][$pColumn]['TYPE'].','.$cellType;
        else 
            $this->map[$pRow-1][$pColumn]['TYPE'] = $cellType;
    }
    
    private function MapSetCellValueByColumnAndRow($pColumn = 0, $pRow = 1, $pValue = null, $cellType = 'default',$header = 0){
        
        if(!isset($this->map[$pRow-1]))
            $this->map[$pRow-1] = array();
        if(!isset($this->map[$pRow-1][$pColumn]))
            $this->map[$pRow-1][$pColumn] = array();
        $this->map[$pRow-1][$pColumn]['VALUE'] = $pValue;
        $this->map[$pRow-1][$pColumn]['TYPE'] = $cellType;
        if($header == 1)
            $this->map[$pRow-1][$pColumn]['HEADER'] = 1;
        
        
    }
    
    private function MapMergeCellsByColumnAndRow($pColumn1 = 0, $pRow1 = 1, $pColumn2 = 0, $pRow2 = 1){
        //ostavljam redoslijed parametara kao u originalu, da se ne bih zbunio
        
        //za svaki slučaj vodimo koji je to merge id, da bi se lakše identifikovale cjeline kasnije
        $merge_id_max = max($this->merge_ids);
        array_push($this->merge_ids,$merge_id_max + 1);
        
        //za svaki red, za svaku kolonu, počevši od startnog do krajnjeg i od startne do krajnje
        $y_loc= 1;
        for($y=($pRow1-1);$y<=($pRow2-1);$y++){
            $x_loc = 1;
            for($x=$pColumn1;$x<=$pColumn2;$x++){
                if(!isset($this->map[$y]))
                    $this->map[$y] = array();
                if(!isset($this->map[$y][$x]))
                    $this->map[$y][$x] = array();
               // if (isset($this->map[$y][$x]['MERGED']))
               //     die("GREŠKA!!! VEĆ MERGANO");
                $this->map[$y][$x]['MERGED'] = 1;
                $this->map[$y][$x]['MERGE_ID'] = $merge_id_max + 1;
                
                //vodim i "poziciju ćelije u merge-u"
                //vertikalno i horizontalno - y i x
                //pozicija je stvarna, u smislu da pokazuje dio dijela u cjelini (npr.. vertikalno 4/7, horizontalno 1/3)
                $merge_position_string = ($y_loc).'/'.($pRow2-$pRow1+1).','.($x_loc).'/'.($pColumn2-$pColumn1+1);
                $this->map[$y][$x]['MERGE_POSITION'] = $merge_position_string;
                $x_loc++;
            }
            $y_loc++;
        }
    }
    
    public function renderPDF($xbrl, $import, $lang = NULL, $type = 'xlsx',$signer_and_bank_data) {
        
        $this->specification = $xbrl;

        $this->axis = new Axis($this->specification, $lang);
        $tableNameId = key($this->specification['rend']['table']);
        $this->breakdownTreeArc = $this->axis->searchLabel($tableNameId, 'http://xbrl.org/arcrole/PWD/2013-05-17/table-breakdown');

        $header = $this->axis->buildXAxis($this->specification['rend']['definitionNodeSubtreeArc'], $this->breakdownTreeArc['x']['to']);
        $contents = $this->axis->buildYAxis($this->specification['rend']['definitionNodeSubtreeArc'], $this->breakdownTreeArc['y']['to']);

        if (!empty($this->breakdownTreeArc['z']['to'])):
            $sheets = $this->axis->buildZAxis($this->specification['rend']['definitionNodeSubtreeArc'], $this->breakdownTreeArc['z']['to']);
        endif;

        /*
        $spreadsheet = new PHPExcel();

        $spreadsheet->getActiveSheet()->setTitle($tableNameId);

        $spreadsheet->setActiveSheetIndex(0);
        */
        $rowspanMax = $colspanMax = max(array_column($header, 'row')) + 1;

        if (!empty($contents)):
            $colspanMax = max(array_column($contents, 'col')) + 2;
        endif;

        $col = 0;
        $storPosition = array();
        
        
        /*        
        $this->MapSetCellValueByColumnAndRow(0, 1, $this->axis->searchLabel($this->specification['rend']['path'] . "#" . $tableNameId, 'http://www.xbrl.org/2008/role/label'));
        $spreadsheet->setActiveSheetIndex(0)->getStyleByColumnAndRow(0, 1)->applyFromArray($styleX);*/
         
        //public function mergeCellsByColumnAndRow($pColumn1 = 0, $pRow1 = 1, $pColumn2 = 0, $pRow2 = 1)
        
        //$this->MapMergeCellsByColumnAndRow(0, 1, 1, $rowspanMax + 1);
        
        $this->MapSetCellValueByColumnAndRow(0, 1, $this->axis->searchLabel($tableNameId, 'http://www.xbrl.org/2008/role/label'),'TABLE_NAME',1);
        $this->MapMergeCellsByColumnAndRow(0, 1, 1, $rowspanMax+1);
        
        $keys = array_keys($header);
        //X axis
        foreach (array_keys($keys) as $row):
            if (isset($keys[$row - 1])):
                $prev = $header[$keys[$row - 1]];
            endif;
            $this_value = $header[$keys[$row]];


            if (isset($storPosition[$this_value['row']])) {

                //provjer da li prethodna pozicija veca ili manja
                if ($storPosition[$this_value['row']] >= $storPosition[$prev['row']]):
                    $col = $storPosition[$this_value['row']] + 1;
                elseif (isset($col) && $this_value['row'] == 0 && $this_value['abstract'] == 'false'):
                    $col = $col + 1;
                endif;



                //Sacuvaj poziciju, ako pozicija posjeduje child elelemt onda je uvacaj za broj child elemenata
                $tmpPos = NULL;
                if (isset($this_value['leaves_element']) && isset($this_value['rollup']) && $this_value['abstract'] != 'true'):
                    $tmpPos = $col + $this_value['leaves_element'] - 1;
                elseif (isset($this_value['metric_element'])):
                    $tmpPos = $col + $this_value['metric_element'] - 1;
                else:
                    $tmpPos = $col;
                endif;
                //   echo $tmpPos;

                $storPosition[$this_value['row']] = $tmpPos;
            } else {


                $tmpPos = NULL;
                //Ako pozicija nije setovan a posjeduje child elemente setuj je na broj child elemenata plus broj kolona inace samo na broj kolona

                if (isset($this_value['leaves_element']) && isset($this_value['rollup']) && $this_value['abstract'] != 'true'):
                    //ako pozicija posjeduje childe elemente i ako se parent element popunjava odnosno ima metric vrijednost
                    $tmpPos = $col + $this_value['leaves_element'] - 1;
                elseif (isset($this_value['metric_element'])):
                    //ako pozicija posjeduje childe elemente samo uzmi u obzir broj metric
                    $tmpPos = $col + $this_value['metric_element'] - 1;
                else:
                    $tmpPos = $col;
                endif;


                $storPosition[$this_value['row']] = $tmpPos;
            }


            //Rc-code
            $this->col[$col]['rc-code'] = $rcCode = $this->axis->searchLabel($this->specification['rend']['path'] . "#" . $this_value['to'], 'http://www.eurofiling.info/xbrl/role/rc-code');
            $this->col[$col]['id'] = $this_value['to'];
            $this->col[$col]['abstract'] = $this_value['abstract'];

            
            
            
            $lebelName = $this->axis->searchLabel($this_value['to'], 'http://www.xbrl.org/2008/role/label');
            if (isset($this_value['rollup']) && $this_value['abstract'] != 'true'):

                
                
                $this->MapSetCellValueByColumnAndRow($col + 2, $this_value['row'] + 1, $lebelName,'LABEL',1);
                //$spreadsheet->setActiveSheetIndex(0)->getRowDimension($this_value['row'] + 1)->setRowHeight(70);


                $this->MapMergeCellsByColumnAndRow($col + 2, $this_value['row'] + 1, $this_value['leaves_element'] + $col + 1, $this_value['row'] + 1);
                $this->MapMergeCellsByColumnAndRow($col + 2, $this_value['row'] + 2, $col + 2, $rowspanMax);


                //$spreadsheet->setActiveSheetIndex(0)->getStyleByColumnAndRow($col + 2, $this_value['row'] + 1, $this_value['leaves_element'] + $col + 1, $this_value['row'] + 1)->applyFromArray($styleX);
                //$spreadsheet->setActiveSheetIndex(0)->getStyleByColumnAndRow($col + 2, $this_value['row'] + 1, $this_value['leaves_element'] + $col + 1, $this_value['row'] + 1)->getAlignment()->setWrapText(true);


                $this->MapMergeCellsByColumnAndRow($col + 2, $this_value['row'] + 2, $col + 2, $rowspanMax);
                //$spreadsheet->setActiveSheetIndex(0)->getStyleByColumnAndRow($col + 2, $this_value['row'] + 2, $col + 2, $rowspanMax)->applyFromArray($styleXFix);


                //$spreadsheet->getActiveSheet()->getRowDimensions($this_value['row'] + 1)->setRowHeight(10);



                //$pdf->MultiCell(20, 20, $rcCode, 1, 'J', 0, 0, $col + 2, $rowspanMax + 1, false, 0, false, true, 0);
                $this->MapSetCellValueByColumnAndRow($col + 2, $rowspanMax + 1, $rcCode,'RC_CODE',1);
                //$spreadsheet->setActiveSheetIndex(0)->getStyleByColumnAndRow($col + 2, $rowspanMax + 1)->applyFromArray($styleRC);

                $col = $col + 1;
            else:
                //
                if (isset($this_value['metric_element']) && $this_value['metric_element'] != 0 && $this_value['metric_element'] < $this_value['leaves_element']):
                    $this_value['leaves_element'] = $this_value['metric_element'];
                endif;

                $this->MapSetCellValueByColumnAndRow($col + 2, $this_value['row'] + 1, $lebelName,'LABEL',1);

                $rowspan = (isset($this_value['leaves_element']) || ($rowspanMax - 1) == $this_value['row']) ? 1 : $rowspanMax - $this_value['row'];
                $colspan = (isset($this_value['leaves_element']) ? ($this_value['leaves_element'] - 1) : 0);

                $this->MapMergeCellsByColumnAndRow($col + 2, $this_value['row'] + 1, $colspan + $col + 2, $this_value['row'] + $rowspan);

                //$spreadsheet->setActiveSheetIndex(0)->getStyleByColumnAndRow($col + 2, $this_value['row'] + 1, $colspan + $col + 2, $this_value['row'] + $rowspan)->applyFromArray($styleX);
                //$spreadsheet->setActiveSheetIndex(0)->getStyleByColumnAndRow($col + 2, $this_value['row'] + 1, $colspan + $col + 2, $this_value['row'] + $rowspan)->getAlignment()->setWrapText(true);

                //$spreadsheet->getActiveSheet()->getRowDimension($this_value['row'] + $rowspan)->setRowHeight(70);
                $this->MapSetCellValueByColumnAndRow($col + 2, $rowspanMax + 1, $rcCode,'RC_CODE',1);
                
                //$pdf->MultiCell(20, 20, $rcCode, 1, 'J', 0, 0, $col + 2, $rowspanMax + 1, false, 0, false, true, 0);
                //$spreadsheet->setActiveSheetIndex(0)->getStyleByColumnAndRow($col + 2, $rowspanMax + 1)->applyFromArray($styleRC);

            endif;



        endforeach;


        //Y
        $y = 0;
        foreach ($contents as $key => $row):

            $labelName = $this->axis->searchLabel($row['to'], 'http://www.xbrl.org/2008/role/label');
            $this->row[$y]['rc-code'] = $rcCode = $this->axis->searchLabel($this->specification['rend']['path'] . "#" . $row['to'], 'http://www.eurofiling.info/xbrl/role/rc-code');
            $this->row[$y]['id'] = $row['to'];
            $this->row[$y]['abstract'] = $row['abstract'];
            $y++;

            //   echo $key;
//set rc-code


            $countSt = strlen($labelName) + $row['col'];
            $str = str_pad($labelName, $countSt, "    ", STR_PAD_LEFT);


            
            //$spreadsheet->setActiveSheetIndex(0)->setCellValueExplicitByColumnAndRow(0, $key + $rowspanMax + 2, $rcCode);
            $this->MapSetCellValueByColumnAndRow(0, $key + $rowspanMax + 2, $rcCode,'RC_CODE');
            //$spreadsheet->setActiveSheetIndex(0)->getStyleByColumnAndRow(0, $key + $rowspanMax + 2)->applyFromArray($styleRC);


            $this->MapSetCellValueByColumnAndRow(1, $key + $rowspanMax + 2, $str,'LABEL');
            //$spreadsheet->setActiveSheetIndex(0)->getColumnDimensionByColumn(1)->setAutoSize(true);
            //$spreadsheet->setActiveSheetIndex(0)->getStyleByColumnAndRow(1, $key + $rowspanMax + 2)->applyFromArray($styleY);



        endforeach;
      
        $x = $y = 0;
        foreach ($this->col as $col):
            $y = 1;
            foreach ($this->row as $row):

                $y++;
                $name = 'c' . $col['rc-code'] . 'r' . $row['rc-code'];
               // echo $name;
               // die();
                //echo "<pre>", print_r($import), "</pre>";
                $value = (isset($import[$name])&&$import[$name]) ? $import[$name] : "";
              //  echo "<pre>", print_r($value), "</pre>";
                $dim = $this->axis->mergeDimensions(DomToArray::search_multdim($header, 'to', $col['id']), DomToArray::search_multdim($contents, 'to', $row['id']));
                $disabled = ($this->axis->checkDef($dim, $name) && $row['abstract'] != 'true' && $col['abstract'] != 'true') ? '' : 'disabled';

                if ($disabled == 'disabled'):
                      $this->MapSetCellValueByColumnAndRow(2 + $x, $rowspanMax + $y, $value,'DISABLED');
                   // $spreadsheet->setActiveSheetIndex(0)->getStyleByColumnAndRow(2 + $x, $rowspanMax + $y)->applyFromArray($styleDisable);
                //   $spreadsheet->setActiveSheetIndex(0)->getStyleByColumnAndRow(2 + $x, $rowspanMax + $y)->getNumberFormat()->setFormatCode('#,##0.00');
                else:
                    $this->MapSetCellValueByColumnAndRow(2 + $x, $rowspanMax + $y, $value,'NUMERIC');
                    //$spreadsheet->setActiveSheetIndex(0)->setCellValueExplicitByColumnAndRow(2 + $x, $rowspanMax + $y, $value, PHPExcel_Cell_DataType::TYPE_NUMERIC);
                    //$spreadsheet->setActiveSheetIndex(0)->getStyleByColumnAndRow(2 + $x, $rowspanMax + $y)->applyFromArray($styleY);
                    //echo "<pre>", print_r($value), "</pre>";
                   // die();
                    if (strpos($value['value'], '%') !== false):
                        $this->MapSetCellType(2 + $x, $rowspanMax + $y,'PERCENT');
                        //$spreadsheet->setActiveSheetIndex(0)->getStyleByColumnAndRow(2 + $x, $rowspanMax + $y)->getNumberFormat()->setFormatCode('0.000%;[Red]-0.000%');
                        //$spreadsheet->setActiveSheetIndex(0)->getStyleByColumnAndRow(2 + $x, $rowspanMax + $y)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
                    elseif (strpos($value['value'], '.') !== false):
                        $this->MapSetCellType(2 + $x, $rowspanMax + $y,'NOMINAL');
                        //$spreadsheet->setActiveSheetIndex(0)->getStyleByColumnAndRow(2 + $x, $rowspanMax + $y)->getNumberFormat()->setFormatCode('#,##0.00');
                    else:
                        //$spreadsheet->setActiveSheetIndex(0)->getStyleByColumnAndRow(2 + $x, $rowspanMax + $y)->getNumberFormat()->setFormatCode('#,##0');
                    endif;
                endif;
            endforeach;
            $x++;
        endforeach;

        $table_code = explode('-',$this->specification['rend']['path'])[0];
        $rep_num = $import['rep_num'];
        $period = $import['period'];
        $this->OutputPDF($table_code,$rep_num,$period,$signer_and_bank_data);
        
    }

    private function outputPDF($table_code,$rep_num,$period,$signer_and_bank_data) {
    
        // create new PDF document
        $pdf = new ExtendTCPDF('L', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

        // set document information
        $pdf->SetCreator('FBA');
        $pdf->SetAuthor('Batedis');
        $pdf->SetTitle('Faktoring');
        $pdf->SetSubject('Izvjestaj');
        $pdf->SetKeywords('Faktoring,Faktoring,Izvještaj');

        // set default header data
        $pdf->SetHeaderData(PDF_HEADER_TITLE, PDF_HEADER_STRING);

        // set header and footer fonts
        $pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
        $pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

        // set default monospaced font
        $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

        //set margins
        $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
        $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
        $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

        //set auto page breaks
        $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

        //set image scale factor
        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

        //set some language-dependent strings
        //$pdf->setLanguageArray($l);

        // ---------------------------------------------------------
        // set font
        $pdf->SetFont('dejavusans', '', 11, '', true);

        // add a page
        $pdf->AddPage();

       //analiza mape
        
       //računanje "rc_row"-a
        $rc_row = 0;
        foreach($this->map as $temp_y => $temp_map_row){
            //RED SA RC CODEOM NA poziciji x 2 (kolona broj 3) ima tip RC i header je 1...
            if(isset($temp_map_row[2]['TYPE']) && $temp_map_row[2]['TYPE'] == 'RC_CODE' && ($temp_map_row[2]['HEADER'] == 1)){
                $rc_row = $temp_y;
            }
        }
        
        //DIMENZIJE MAPE
        $map_y_dim = max(array_keys($this->map))+1;
        $map_x_dim = max(array_keys($this->map[0]))+1;
        
        //analiza headera
        
        $header_str_lengths = array();
        for($y=0;$y<$rc_row;$y++){
            foreach($this->map[$y] as $temp_x => $temp_cell){
                //NE RAČUNAMO TABLE NAME, on ima najviše karaktera obično
                if(!(($y== 0) && ($temp_x == 0))){
                    //samo prave naslove
                    if(isset($temp_cell['VALUE'])&&strlen($temp_cell['VALUE']) != 0)
                        array_push($header_str_lengths,strlen($temp_cell['VALUE']));
                }
            }
        }
        
        //PRETPOSTAVKA ŠIRINE NA OSNOVU PROSJEČNOG BROJA KARAKTERA
        $header_char_num_avg = array_sum($header_str_lengths)/count($header_str_lengths);
        $default_width = round($header_char_num_avg/1.5,0);
        
        
        //parametri... možda izvuči u konfig fileove za svaki od obrazaca, po potrebi
      
        //OVDJE BI TREBALO DA IDE UČITAVANJE CONF FILEOVA.. AKO STIGNEMO
        $table_code = explode('-',$this->specification['rend']['path'])[0];
        
        switch($table_code){
            case 'fa.008':
                    $rc_width = 10;
                    $description_width = 60;
                    //$default_width = 22;
                    $cell_height = 5.2;
                    $rc_height = 5;
                    $rc_font_size = 5;
                    $header_font_size = 6.5;
                    $regular_row_font_size = 6;
                    $magic = 8.28;
                    $separator_signer_vertical = 6;
                    $x_start= 15;
                    $y_start = 30;
                break;
            case 'fa.009':
                    $rc_width = 10;
                    $description_width = 60;
                    //$default_width = 22;
                    $cell_height = 5.2;
                    $rc_height = 5;
                    $rc_font_size = 6;
                    $header_font_size = 6.5;
                    $regular_row_font_size = 6;
                    $magic = 8.28;
                    $separator_signer_vertical = 6;
                    $x_start= 15;
                    $y_start = 30;
                
                break;
            case 'fa.012':
                    $rc_width = 10;
                    $description_width = 90;
                    //$default_width = 22;
                    $cell_height = 5;
                    $rc_height = 5;
                    $rc_font_size = 5;
                    $header_font_size = 6;
                    $regular_row_font_size = 6;
                    $magic = 8.28;
                    $separator_signer_vertical = 6;
                    $x_start= 15;
                    $y_start = 30;
                break;
            case 'fa.016':
                    $rc_width = 10;
                    $description_width = 90;
                    $default_width = 15;
                    $cell_height = 6;
                    $rc_height = 5;
                    $rc_font_size = 6;
                    $header_font_size = 7;
                    $regular_row_font_size = 6.5;
                    $magic = 8.28;
                    $separator_signer_vertical = 6;
                    $x_start= 15;
                    $y_start = 36;
                break;
            default:
                    $rc_width = 10;
                    $description_width = 60;
                    //$default_width = 22;
                    $cell_height = 6;
                    $rc_height = 5;
                    $rc_font_size = 6;
                    $header_font_size = 7;
                    $regular_row_font_size = 6.5;
                    $magic = 8.28;
                    $separator_signer_vertical = 6;
                    $x_start= 15;
                    $y_start = 36;
                
                break;

        }
        
        
        
        
        
        
        //ŠIRINE PO KOLONAMA
        $col_widths = array(0 => $rc_width,
                            1 => $description_width);
        
       for($x=2;$x<$map_x_dim;$x++){
            $col_widths[$x] = $default_width;
        }
        
        //VISINE PO REDOVIMA
        $row_heights = array();
        for($y=0;$y<$map_y_dim;$y++){
            if($y==$rc_row)
                $row_heights[$y] = $rc_height;
            else
                $row_heights[$y] = $cell_height;
        }
        
        
        //KOREKCIJA ĆELIJA KOJE ODSKAČU OD PROSJEČNE DUŽINE LABELE
        for($y=0;$y<$rc_row;$y++){
            foreach($this->map[$y] as $temp_x => $temp_cell){
                //NE RAČUNAMO TABLE NAME, on ima najviše karaktera obično
                if(!(($y== 0) && ($temp_x == 0))){
                    //samo prave naslove
                    if(isset($temp_cell['VALUE'])&&strlen($temp_cell['VALUE']) != 0){
                        if(isset($temp_cell['MERGED'])){
                            //AKO JE MERGANO
                            $cell_dimensions = $temp_cell['MERGE_POSITION'];
                            $row_dimensions = explode(',',$cell_dimensions)[0];
                            $column_dimensions = explode(',',$cell_dimensions)[1];
                            $row_span_index = explode('/',$row_dimensions)[0];
                            $col_span_index = explode('/',$column_dimensions)[0];
                            $row_span = explode('/',$row_dimensions)[1];
                            $col_span = explode('/',$column_dimensions)[1];
                            $modifier = $col_span;
                        }
                        else $modifier = 1;
                        
                        if((strlen($temp_cell['VALUE'])/($header_char_num_avg*$modifier))>1.4){
                            $col_widths[$temp_x] = round(strlen($temp_cell['VALUE'])/(2.2*$modifier),0);
                        }
                            
                            
                    }
                }
            }
        }
        
        
        //8.28 MAGIČNI BROJ
        //Priča je iz starih zapisa o PDF izvještajima (cca 15. stoljeće) da postoji magični broj koji se ima uzeti kao relevantan za određivanje visine redova
        //Navodno, množenjem širine kolone sa visinom reda neke ćelije i dijeljenjem treba dobiti najmanje 8.28. Stari PDF kreatori su u tu svrhu uzimali ćeliju sa najvećim brojem karaktera,
        // jer uglavljivanje teksta te ćelije u okvir same ćelije garantuje uglavljivanje i ostatka ćelija iz tog reda...
        
         for($y=0;$y<$rc_row;$y++){
            $max_temp_height = 0;
            foreach($this->map[$y] as $temp_x => $temp_cell){
                //NE RAČUNAMO TABLE NAME, on ima najviše karaktera obično
                if(!(($y== 0) && ($temp_x == 0))){
                    //samo prave naslove
                    if(isset($temp_cell['VALUE'])&&strlen($temp_cell['VALUE']) != 0){
                        if(isset($temp_cell['MERGED'])){
                            //AKO JE MERGANO
                            $cell_dimensions = $temp_cell['MERGE_POSITION'];
                            $row_dimensions = explode(',',$cell_dimensions)[0];
                            $column_dimensions = explode(',',$cell_dimensions)[1];
                            $row_span_index = explode('/',$row_dimensions)[0];
                            $col_span_index = explode('/',$column_dimensions)[0];
                            $row_span = explode('/',$row_dimensions)[1];
                            $col_span = explode('/',$column_dimensions)[1];
                            //SAMO ĆELIJA SA INDEXIMA 1,1
                            if($row_span_index == 1 && $col_span_index == 1){
                                $row_modifier = $row_span;
                                $total_cell_width = 0;
                                for($i=$temp_x;$i<=$temp_x+$col_span-1;$i++){
                                    $total_cell_width += $col_widths[$i];
                                }
                                $cell_height_temp = ($magic*strlen($temp_cell['VALUE']))/($row_span*$total_cell_width);
                                if($cell_height_temp > $max_temp_height)
                                    $max_temp_height = $cell_height_temp;
                            }
                        }
                        else {
                            //JEDNOSTRUKA ĆELIJA 
                        }
                    }
                }
            }
            //ipak ograniči na minimalni cell height....
            if($max_temp_height > $cell_height)
                $row_heights[$y] = $max_temp_height;
            else 
                $row_heights[$y] = $cell_height;
        }
        
        //POREFENAJ OPISE U REGULARNIM REDOVIMA
        for($y=$rc_row+1;$y<$map_y_dim;$y++){
            if(strlen($this->map[$y][1]['VALUE'])>=(($row_heights[$y]*$col_widths[1])/8.28)){
                $row_heights[$y] = ((8.28*strlen($this->map[$y][1]['VALUE']))/$col_widths[1]);
            }
        }
        
        /*
        echo "<pre>";
        print_r($row_heights);
        echo"</pre>";
        die();
        */
        

        //zasebni tretman headera
        $header_elements = array();
        
        $current_y_pos = 0;
        for($y = 0;$y<=$rc_row;$y++){
            $current_x_pos = 0;
            foreach($this->map[$y] as $x => $temp_cell){
                if(isset($temp_cell['MERGED']) && isset($temp_cell['MERGE_POSITION'])){
                    //MERGANO
                    $cell_dimensions = $temp_cell['MERGE_POSITION'];
                    $row_dimensions = explode(',',$cell_dimensions)[0];
                    $column_dimensions = explode(',',$cell_dimensions)[1];
                    $row_span_index = explode('/',$row_dimensions)[0];
                    $col_span_index = explode('/',$column_dimensions)[0];
                    $row_span = explode('/',$row_dimensions)[1];
                    $col_span = explode('/',$column_dimensions)[1];
                    
                    //SAMO PRVA OD MERGANIH ĆELIJA (indeksi 1,1) , JER ONA SADRŽI VALUE I TO SVE OSTALO
                    if($row_span_index == 1 && $col_span_index == 1){
                        
                        $temp_cell_width = 0;
                        $temp_cell_height = 0;                        
                        
                        //koliki joj je width?
                        for($temp_x = $x;$temp_x <= $x+$col_span-1;$temp_x++){
                            $temp_cell_width+=$col_widths[$temp_x];
                        }
                        //koliki joj je height?
                        for($temp_y = $y;$temp_y <= $y+$row_span-1;$temp_y++){
                            $temp_cell_height+=$row_heights[$temp_y];
                        }
                        
                        $temp_arr = array('VALUE' => $temp_cell['VALUE'],
                                          'X' => $current_x_pos,
                                          'Y' => $current_y_pos,
                                          'WIDTH' => $temp_cell_width,
                                          'HEIGHT' => $temp_cell_height);
                        array_push($header_elements,$temp_arr);
                    }
                }
                else {
                    //OBIČNA ĆELIJA - "JEDNOSTRUKA"
                    
                    
                    
                }
                $current_x_pos += $col_widths[$x];
            }
            $current_y_pos += $row_heights[$y];
        }

        //header
        $name_application = 'BATEDIS 2';
        $name_report = strtoupper(str_replace('.','',$table_code));
        $datum = str_replace('-','.',$period).".";
       
        $pdf->SetAbsX(15);
        $pdf->SetAbsY(15);

        $pdf->Header_Bank_Report($name_application,$name_report,$signer_and_bank_data['BANK_NAME'],$signer_and_bank_data['BANK_JMB'],$datum,$rep_num);

        $pdf->SetFillColor(255, 255, 255);
        $pdf->SetTextColor(0);

        //$pdf->Ln($separator_header_horizontal);
        
        //crtanje headera
        
        $pdf->SetFont('', 'B', $header_font_size);
        $pdf->SetFillColor(224, 235, 255);
        foreach($header_elements as $temp_element){
            $pdf->MultiCell($temp_element['WIDTH'], $temp_element['HEIGHT'], $temp_element['VALUE'], 1, 'C', 1,0, $temp_element['X']+$x_start,$temp_element['Y']+$y_start,true,0,false,false,$temp_element['HEIGHT'],'M',0);
        }
        
        //FRANCUSKO ZAGLAVLJE
        //sav info o francuskom se nalazi u rc_row indexu mape... ime tabele je već isprintano, pa krećemo od kolone 2
        //počinjemo nakon ove 2 kolone
        $temp_x_pos = $col_widths[0]+$col_widths[1];
        
        //računanje y koordinate
        $temp_y_pos = 0;
        for($y=0;$y<$rc_row;$y++){
            $temp_y_pos += $row_heights[$y];
        }
        
        $pdf->SetFont('', 'B', $rc_font_size);
        
        for($x=2;$x<$map_x_dim;$x++){
            $temp_element=$this->map[$rc_row][$x];
            $pdf->MultiCell($col_widths[$x], $row_heights[$rc_row], $temp_element['VALUE'], 1, 'C', 0,0, $temp_x_pos+$x_start,$temp_y_pos+$y_start,true,0,false,false,$row_heights[$rc_row],'M',0);
            $temp_x_pos += $col_widths[$x];
        }
        
        
        /*
        echo "<pre>";
        print_r($this->map);
        echo"</pre>";
        die();
        */
        
        
        //OSTATAK IZVJEŠTAJA - obični redovi
        
        //naštimaj ga na početak reda
        $y_abs = 0;
        for($y=0;$y<=$rc_row;$y++)
            $y_abs += $row_heights[$y]; 
        
        $pdf->SetAbsXY($x_start, $y_start+$y_abs);
        $pdf->SetFont('', '', $regular_row_font_size);
        //regularne ćelije
        for($y=$rc_row+1;$y<$map_y_dim;$y++){
            foreach($this->map[$y] as $x => $temp_cell){
                switch($temp_cell['TYPE']){
                    case 'RC_CODE':
                        $pdf->MultiCell($col_widths[$x], $row_heights[$y], $temp_cell['VALUE'], 1, 'C', 0,0, '','',true,0,false,false,$row_heights[$y],'M',0);
                        break;
                    case 'LABEL':
                        $pdf->MultiCell($col_widths[$x], $row_heights[$y], $temp_cell['VALUE'], 1, 'L', 0,0, '','',true,0,false,false,$row_heights[$y],'M',0);
                        break;
                    case 'NUMERIC':
                        $pdf->MultiCell($col_widths[$x], $row_heights[$y], $this->myWordWrap($this->HideZeros($temp_cell['VALUE']['value'])), 1, 'R', 0,0, '','',true,0,false,false,$row_heights[$y],'M',0);
                        break;
                    default:
                        
                        //DECIMALNI!!!
                        if(isset($temp_cell['VALUE']['value']))
                            $temp_val = $temp_cell['VALUE']['value'];
                        else 
                            $temp_val = '';
                        $pdf->MultiCell($col_widths[$x], $row_heights[$y], $temp_val, 1, 'R', 0,0, '','',true,0,false,false,$row_heights[$y],'M',0);
                        break;
                    
                    
                    
                }
                
                
                
                
                
                
                
                //$pdf->Cell($col_widths[$x], $row_heights[$y], $temp_cell['VALUE'], 1, 0, 'L', 1, '', 1);
            }
            $pdf->Ln();
        }
        
        
        $pdf->Ln($separator_signer_vertical);
        
        //potpisnici - sada su 2.... treba osmisliti varijantu kad ih je više / manje
        $pdf->SetFont('', 'B', 7);
        
        $signers = $signer_and_bank_data['signatures'];
        
        $pdf->Cell(100, 3, ($signers[1]['description'])??NULL, 1, 0, 'C', 1, '', 1);
        $pdf->Cell(20, 3, "", 0, 0, 'C', 0, '', 1);
        $pdf->Cell(100, 3, ($signers[2]['description'])??NULL, 1, 0, 'C', 1, '', 1);
        $pdf->Ln(5);
        $pdf->Cell(100, 3, "Potpis    ( Ime i prezime / tel. br. ovlaštenog lica )", 0, 0, 'C', 0, '', 1);
        $pdf->Cell(20, 3, "", 0, 0, 'C', 0, '', 1);
        $pdf->Cell(100, 3, "Potpis    ( Ime i prezime / tel. br. ovlaštenog lica )", 0, 0, 'C', 0, '', 1);
        $pdf->Ln(4);
        
        //$pdf->Cell($w, $h, $txt, $border, $ln, $align, $fill, $link, $stretch, $ignore_min_height, $calign, $valign)
        
        
        
        
        
        
        
        
        
        
        
        
        // ---------------------------------------------------------
        //Close and output PDF document
        ob_clean();
        $pdf->Output($name_report.'.pdf', 'I');
        
    }

}
