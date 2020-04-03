<?php
namespace DpmXbrl\Creat;
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace DpmXbrl;

use DpmXbrl\Config\Config;
use XMLWriter;

//echo "<pre>", print_r(array_values($_POST)), "</pre>";
//die();

/**
 * Description of CreatInstace
 *
 * @author begicf
 */
class CreatInstace extends XMLWriter {

    public $name;
    private $metric = array();
    private $input;
    private $date = '2016-09-30';
    private $organisation = '529900CEJZKMQ4AKBF28';
    private $schemaRef = 'www.fba.ba/fr/xbrl/fws/eba_corep/its-2015-04/2015-08-31/mod/corep_bh_or.xsd';

    public function __construct($input) {

        $this->input = $input;

        $this->openMemory();


        $this->setIndent(true);
        $this->setIndentString(' ');
        $this->startDocument('1.0', 'UTF-8');
        $this->startComment();
        $this->writeComment("FBA v.1.0");
        $this->endComment();


        $this->XbrlNS();
        $this->XbrlschemaRef();
        $this->XbrlUnit();

        $this->XbrlContextFin();
        $this->XbrlContext();

        //$this->XbrlMetric();
        $this->XbrlFind();

        $this->endElement();
        $this->endDocument();

        $this->output();
        //$this->writeInstance();
    }

    public function output() {
        // ob_clean();
        // header('Content-type: text/xml');
        echo $this->outputMemory();
    }

    public function writeInstance() {
        // header('Content-type: text/xml');
        // header('Content-Disposition: attachment; filename=instance.xbrl');
        $this->name = time() . '.xbrl';
        file_put_contents('rw/' . $this->name, $this->outputMemory());
    }

    private function XbrlNS() {

        $this->startElementNS(
                'xbrli', 'xbrl', 'http://www.xbrl.org/2003/instance'
        );
        $this->writeAttribute(
                'xmlns:iso4217', 'http://www.xbrl.org/2003/iso4217'
        );
        $this->writeAttribute(
                'xmlns:xbrldi', 'http://xbrl.org/2006/xbrldi'
        );
        $this->writeAttribute(
                'xmlns:link', 'http://www.xbrl.org/2003/linkbase'
        );
        $this->writeAttribute(
                'xmlns:xlink', 'http://www.w3.org/1999/xlink'
        );
        $this->writeAttribute(
                'xmlns:find', 'http://www.eurofiling.info/xbrl/ext/filing-indicators'
        );


        $_owner = Config::owners();
        $dom = array();
        $met = array();
        $dim = array();
        foreach ($this->input as $row):
            if (!empty($row['dim'])):
                $arr = json_decode($row['dim'], true);

                foreach ($arr as $k => $row):
                    //dimension
                    $typ = strstr($k, ':', TRUE);
                    if ($typ !== FALSE):
                        $dim[$typ] = $typ;
                    endif;
                    //metric
                    $key = strstr($row, ':', TRUE);
                    if ($k == 'metric'):

                        if (!isset($met[$key])):
                            $id = strstr($row, '_', TRUE);
                            $met[$key] = $key;
                            $this->writeAttribute("xmlns:$key", $_owner[$id]['namespace'] . DIRECTORY_SEPARATOR . 'dict' . DIRECTORY_SEPARATOR . 'met');
                        endif;

                    else:
                        //domain
                        if (!isset($dom[$key])):
                            $id = strstr($row, '_', TRUE);
                            $dom[$key] = $key;
                            $domID = substr(strstr($key, '_'), strlen('_'));

                            $this->writeAttribute("xmlns:$key", $_owner[$id]['namespace'] . DIRECTORY_SEPARATOR . 'dict' . DIRECTORY_SEPARATOR . 'dom' . DIRECTORY_SEPARATOR . $domID);
                        endif;
                    endif;
                endforeach;

            endif;
        endforeach;
    }

    private function XbrlschemaRef() {
        $this->startElement('link:schemaRef');
        $this->writeAttribute(
                'xlink:type', 'simple'
        );
        $this->writeAttribute(
                'xlink:href',  strstr($this->schemaRef, 'www')
        );
        $this->endElement();
    }

    private function XbrlUnit() {
        $this->startElement('xbrli:unit');
        $this->writeAttribute(
                'id', 'uBAM'
        );
        $this->writeElementNs('xbrli', 'measure', NULL, 'iso4217:BAM');
        $this->endElement();
    }

    private function XbrlContextFin() {


        //frist
        $this->startElement('xbrli:context');
        $this->writeAttribute(
                'id', 'cfin' . $this->organisation
        );

        //entity
        $this->startElement('xbrli:entity');
        $this->startElement('xbrli:identifier');
        $this->writeAttribute(
                'scheme', 'http://standards.iso.org/iso/17442'
        );
        $this->writeRaw($this->organisation);
        $this->endElement();
        $this->endElement();
        //period
        $this->startElement('xbrli:period');
        $this->writeElementNs('xbrli', 'instant', NULL, $this->date);
        $this->endElement();
        $this->endElement();
    }

    private function XbrlContext() {

        $id = 1;
        foreach ($this->input as $key => $row):
            if (!empty($row['value'])):



                $this->startElement('xbrli:context');
                $this->writeAttribute(
                        'id', 'c' . $this->organisation . '-' . $id
                );

                //entity
                $this->startElement('xbrli:entity');
                $this->startElement('xbrli:identifier');
                $this->writeAttribute(
                        'scheme', 'http://standards.iso.org/iso/17442'
                );
                $this->writeRaw($this->organisation);
                $this->endElement();
                $this->endElement();
                //period
                $this->startElement('xbrli:period');
                $this->writeElementNs('xbrli', 'instant', NULL, $this->date);
                $this->endElement();

                //scenario    
                $this->startElement('xbrli:scenario');
                $arr = json_decode($row['dim'], true);

                foreach ($arr as $k => $scenario):

                    if ($k != 'metric' & substr(strstr($scenario, ':'), 2) != 0):

                        $this->startElement('xbrldi:explicitMember');
                        $this->writeAttribute(
                                'dimension', $k
                        );
                        $this->writeRaw($scenario);
                        $this->endElement();

                    elseif ($k == 'metric' & !empty($scenario)):

                        $this->metric[$id] = array('metric' => $scenario, 'value' => $_POST[$key]);
                        $id++;
                    endif;

                endforeach;
                $this->endElement();


                $this->endElement();

            endif;

        endforeach;
    }

    private function XbrlMetric() {

        foreach ($this->metric as $key => $row):

            $this->startElement($row['metric']);

            $this->writeAttribute(
                    'contextRef', 'c' . $this->organisation . '-' . $key
            );
            $this->writeAttribute(
                    'decimals', '-3'
            );
            $this->writeAttribute(
                    'unitRef', 'uBAM'
            );
            $this->writeRaw($row['value'] * 1000);
            $this->endElement();

        endforeach;
    }

    private function XbrlFind() {
        $this->startElementNS('find','fIndicators');

        $this->startElementNS('find','filingIndicator');
        $this->writeAttribute('contextRef', 'cfin' . $this->organisation);

        $this->writeRaw('C_16.00');
        $this->endElement();

        $this->endElement();
    }

}
