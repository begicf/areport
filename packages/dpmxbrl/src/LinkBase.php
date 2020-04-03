<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace DpmXbrl;

use DpmXbrl\Gen\Link;
use DpmXbrl\Gen\TableLinkbase;
use DpmXbrl\Link\DefinitionLink;
use DpmXbrl\Module\Presentation;

/**
 * Description of LinkBase
 *
 * @author begicf
 */
class LinkBase implements \IteratorAggregate
{

    private $links;
    private $assertion;

    public function __construct($baseLinks, $assertion = null)
    {

        foreach ($baseLinks as $key => $path):

            switch ($key):
                case 'lab-en':
                case 'lab-ba':
                case 'lab-bs-Latn-BA':
                case 'lab-codes':
                    $this->links[$key] = new Link($path);
                    break;
                case 'rend':
                    $this->links[$key] = new TableLinkbase($path);
                    break;
                case 'def':
                    $this->links[$key] = new DefinitionLink($path);
                    break;
                case 'pre':
                    $this->links[$key] = new Presentation($path);
                    break;
                default:
                    if ($assertion == TRUE):
                        $this->links[$key] = new Link($path); //assertion
                    endif;
            endswitch;

        endforeach;

        return true;
    }

    /**
     * @return ArcCollection[]
     */
    public function getIterator()
    {

        return new \ArrayIterator($this->links);
    }

}
