<?php

namespace DpmXbrl;

use DpmXbrl\Library\DomToArray;
use DpmXbrl\Render\RenderOutput;
use DpmXbrl\Render\RenderPDF;
use DpmXbrl\Render\RenderTable;

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 * Tools for rendering logical structure of the table(s). 
 * ver 1.0.
 * 
 */

/**
 * Description of Set
 *
 * @author begicf
 */
class Set
{

    public $schema;
    public $imports = array();
    public $namespace = array();
    private $linkbase = array();
    private $linkArray = array();
    private $assertion = null;

    /*
     * @void set $linkArray
     */

    private function setLinkArray($arr)
    {
        if (!empty($arr)):
            $this->linkArray = $arr;
        else:
            $this->linkArray = Library\Data::getLangSpec('all');
        endif;
    }

    public function __construct($basePath, $arr = NULL, $assertion = NULL)
    {

        $this->setLinkArray($arr);
        $this->schema = DomToArray::invoke($basePath);
        $this->getImports();
        $this->getNamespace();
        $this->getLinkbases();
        $this->assertion = $assertion;
    }

    /**
     * Get imports schema
     * @void  set $this->imports
     */
    private function getImports()
    {

        $xPath = new \DOMXPath($this->schema);

        $xPath->registerNamespace('xs', 'http://www.w3.org/2001/XMLSchema');
        $imports = $xPath->evaluate("//xs:schema/xs:import");

        foreach ($imports as $import) {

            $this->imports[$import->getAttribute('namespace')] = $import->getAttribute('schemaLocation');
        }
    }

    /**
     * @void set Namespace
     */
    private function getNamespace()
    {

        $xPath = new \DOMXPath($this->schema);

        $xPath->registerNamespace('xs', 'http://www.w3.org/2001/XMLSchema');

        $context = $this->schema->documentElement;

        foreach ($xPath->query('namespace::*', $context) as $node) {

            $this->namespace[$node->prefix] = $node->nodeValue;
        }
    }

    /**
     * Get namespace
     * @return type
     */
    public function getTargetNamespace()
    {

        $xPath = new \DOMXPath($this->schema);

        $xPath->registerNamespace('xs', 'http://www.w3.org/2001/XMLSchema');
        $targets = $xPath->evaluate("//xs:schema/@targetNamespace");

        foreach ($targets as $target) {

            return $target->nodeValue;
        }
    }

    /**
     * Get linkbase instance
     * @void set $this->linkbase
     *
     */
    public function getLinkbases()
    {

        $xPath = new \DOMXPath($this->schema);

        $xPath->registerNamespace('xs', 'http://www.w3.org/2001/XMLSchema');
        $xPath->registerNamespace('link', 'http://www.xbrl.org/2003/linkbase');
        $xPath->registerNamespace('xlink', 'http://www.w3.org/1999/xlink');

        $linkbase = $xPath->evaluate("//xs:schema/xs:annotation/xs:appinfo/link:linkbaseRef");


        foreach ($linkbase as $link) {
            $path = (dirname($this->schema->baseURI) . DIRECTORY_SEPARATOR . $link->getAttribute('xlink:href'));
            if (strpos($path, 'file:/') !== false):
                $path = str_replace('file:/', '', $path);
            endif;
            if (file_exists($path)):
                $this->getXbrlFileType($path, basename($this->schema->baseURI, ".xsd"));
            endif;
        }

    }

    /**
     * @param string $linkBasePath
     * Na osnovu naziva xml fila, odreduje tip fajla.
     */
    private function getXbrlFileType($linkBasePath, $name = NULL)
    {

        $file = pathinfo($linkBasePath);


        foreach ($this->linkArray as $key => $link):

            if ($file['filename'] == $name . '-' . $link):
                $this->linkbase[$key] = $linkBasePath;

            else:

                $this->linkbase[$file['filename']] = $linkBasePath;


            endif;
        endforeach;

    }

    /**
     * @param string $basePath
     * @param string $schemaName
     * @return Schema
     */
    public function load()
    {
        if (!empty($this->linkbase)):
            return new LinkBase($this->linkbase, $this->assertion);
        endif;
    }

    public function render()
    {

        return new RenderTable();
    }

    public function export()
    {

        return new RenderOutput();
    }

    public function exportPDF()
    {
        return new RenderPDF();
    }

}
