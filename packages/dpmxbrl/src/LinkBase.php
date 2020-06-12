<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace DpmXbrl;

use DpmXbrl\Config\Config;
use DpmXbrl\Gen\Link;
use DpmXbrl\Gen\TableLinkbase;
use DpmXbrl\Link\DefinitionLink;
use DpmXbrl\Module\Presentation;

/**
 * Class LinkBase
 * @category
 * @package Areport DpmXbrl
 * @author Fuad Begic <fuad.begic@gmail.com>
 * Date: 12/06/2020
 * Time: 12:14
 */
class LinkBase implements \IteratorAggregate
{

    private $links;
    private $assertion;

    public function __construct($baseLinks, $assertion = null)
    {


        foreach ($baseLinks as $key => $path):


            foreach (Config::$lang as $item) {
                if ('lab-' . $item == $key):
                    $this->links[$key] = new Link($path);
                endif;

            }

            switch ($key):

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
