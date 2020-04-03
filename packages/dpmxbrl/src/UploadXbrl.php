<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace DpmXbrl;

/**
 * Description of UploadXbrl
 *
 * @author begicf
 */
class UploadXbrl {

    //put your code here
    private $dom;
    private $instance;

    public function __construct($path) {
        $this->dom = DomToArray::invoke($path);

        $this->metric();
    }

    public function Instance() {



        return $this->instance;
    }

    private function getDimension($element, $id) {

        $explicitMember = $element->getElementsByTagName('explicitMember');
        $tmp = array();
        foreach ($explicitMember as $member):

            $tmp[$member->getAttribute('dimension')] = $member->nodeValue;
        endforeach;
        if (!empty($tmp)):
            $this->instance[$id]['dimension'] = json_encode($tmp);
        endif;
    }

    private function metric() {

        $tmpContext = new \DOMXPath($this->dom);
        $context = $tmpContext->query("//*[@contextRef]");
        if (!is_null($context)):
            $id = 0;
            foreach ($context as $cont):
                $id++;
                $decimals = $cont->getAttribute('decimals');
                $contextRef = $cont->getAttribute('contextRef');
                $this->instance[$id]['contextRef'] = $contextRef;
                $this->context($id, $contextRef);
                if (empty($decimals)):
                    $this->instance[$id][$cont->nodeValue] = $cont->nodeValue;
                else:
                    $this->instance[$id]['value'] = $cont->nodeValue;
                    $this->instance[$id]['decimals'] = $cont->getAttribute('decimals');
                    $this->instance[$id]['unitRef'] = $cont->getAttribute('unitRef');
                    $this->instance[$id]['metric'] = $cont->nodeName;


                    $arr = json_decode($this->instance[$id]['dimension'], true);
                    $met = ['metric' => $cont->nodeName];
                    $this->instance[$id]['dimension'] = json_encode(array_merge($met, $arr));
                // $this->instance[$id]['metric'] = $cont->nodeName;
                endif;


            endforeach;
        endif;
    }

    private function context($id, $contextRef) {

        $XpathConRef = new \DOMXPath($this->dom);

        $elements = $XpathConRef->query("//*[@id='$contextRef']");


        if (!is_null($elements)):
            foreach ($elements as $element) :

                $period = $element->getElementsByTagName('instant');

                if (!is_null($period[0])):

                    $this->instance[$id]['period'] = $period[0]->nodeValue;
                endif;

                $this->getDimension($element, $id);


            endforeach;
        endif;
    }

}
