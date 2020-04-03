<?php

namespace DpmXbrl\Render;

use DpmXbrl\Library\DomToArray;
use DpmXbrl\Library\Data;
use DpmXbrl\Library\Format;

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Axis
 *
 * @author begicf
 */
class Axis
{

    //put your code here
    private $specification;
    private $lang = 'lab-en';

    public function __construct($spec, $lang = NULL)
    {
        $this->specification = $spec;

        if (!is_null($lang)):

            if (strpos($lang, 'lab-') === false):
                $lang = 'lab-' . $lang;
            endif;
            $this->lang = $lang;
        endif;
    }

    public function buildXAxis(array $elements, $parentId = 0, $n = 0, $node = array())
    {
        $branch = array();

        $col = 0;

        foreach ($elements as $element):

            if ($element['from'] == $parentId):

                //ruleNodes
                if (is_array($this->specification['rend']['ruleNode'][$element['from']]['dimension'])):
                    $node =
                        array_replace($node, $this->specification['rend']['ruleNode'][$element['from']]['dimension']);
                endif;

                $children = $this->buildXAxis($elements, $element['to'], $n + 1, $node);
                if ($children):

                    $element['row'] = $n++;

                    //ruleNode
                    if (is_array($this->specification['rend']['ruleNode'][$element['to']]['dimension'])):
                        $element['dimension'] =
                            array_replace($node, $this->specification['rend']['ruleNode'][$element['to']]['dimension']);
                        $element['abstract'] = $this->specification['rend']['ruleNode'][$element['to']]['abstract'];
                    else:
                        $element['abstract'] = $this->specification['rend']['ruleNode'][$element['to']]['abstract'];
                        $element['dimension'] = $node;
                    endif;


                    $element['metric'] = $this->specification['rend']['ruleNode'][$element['to']]['concept'];


                    $count_leaves = count(array_column($children, 'leaves_element'));
                    $element['all_element'] = count($children);

                    //brojac childe elemenata koji sadrze matric
                    $tmpC = 0;
                    foreach ($children as $c):

                        if ($c['metric'] != 'false'):
                            // echo $c['metric']." ".PHP_EOL;
                            $tmpC = $tmpC + 1;
                        endif;
                    endforeach;
                    ///  echo $tmpC . PHP_EOL;
                    $element['metric_element'] = $tmpC;


                    if (count(array_column($children, 'rollup'))):

                        $count_rollup = array_sum(array_column($children, 'rollup'));

                        if ($count_rollup <= 1):
                            $count_leaves = $count_leaves - 1;
                        else:

                            $count_leaves = 0;

                        endif;
                    endif;


                    if ($element['metric'] != 'false'):
                        $element['all_element'] = $element['all_element'] + 1;
                        $element['rollup'] = true;
                    endif;

                    $element['leaves_element'] = $element['all_element'] - $count_leaves;


                    $branch[] = $element;

                    foreach ($children as $c):


                        $c['col'] = $element['order'] + $c['order'];


                        $branch[] = $c;
                    endforeach;
                    $col = 0;
                    $n--;

                else:
                    //ruleNodes
                    if (is_array($this->specification['rend']['ruleNode'][$element['to']]['dimension'])):
                        $element['dimension'] =
                            array_replace($node, $this->specification['rend']['ruleNode'][$element['to']]['dimension']);
                        $element['abstract'] = $this->specification['rend']['ruleNode'][$element['to']]['abstract'];
                    else:
                        $element['abstract'] = $this->specification['rend']['ruleNode'][$element['to']]['abstract'];
                        $element['dimension'] = $node;
                    endif;
                    $element['metric'] = $this->specification['rend']['ruleNode'][$element['to']]['concept'];

                    $element['row'] = $n;

                    $branch[] = $element;
                endif;
            endif;
        endforeach;
        return $branch;
    }

    public function buildYAxis(array $elements, $parentId = 0, $n = 0, $node = array())
    {
        $branch = array();

        foreach ($elements as $element) :

            if (isset($element['from']) && $element['from'] == $parentId) :

                //ruleNodes
                if (is_array($this->specification['rend']['ruleNode'][$element['from']]['dimension'])):
                    $node =
                        array_replace($node, $this->specification['rend']['ruleNode'][$element['from']]['dimension']);
                endif;

                $children = $this->buildYAxis($elements, $element['to'], $n + 1, $node);

                if ($children):
                    $element['col'] = $n++;

                    //ruleNodes
                    if (is_array($this->specification['rend']['ruleNode'][$element['to']]['dimension'])):
                        $element['dimension'] =
                            array_replace($node, $this->specification['rend']['ruleNode'][$element['to']]['dimension']);
                        $element['abstract'] = $this->specification['rend']['ruleNode'][$element['to']]['abstract'];
                    else:
                        $element['abstract'] = $this->specification['rend']['ruleNode'][$element['to']]['abstract'];
                        $element['dimension'] = $node;
                    endif;

                    $element['metric'] = $this->specification['rend']['ruleNode'][$element['to']]['concept'];


                    $branch[] = $element;

                    foreach ($children as $c):

                        $branch[] = $c;

                    endforeach;
                    $n--;

                else:

                    //ruleNodes
                    if (is_array($this->specification['rend']['ruleNode'][$element['to']]['dimension'])):
                        $element['dimension'] =
                            array_replace($node, $this->specification['rend']['ruleNode'][$element['to']]['dimension']);
                        $element['abstract'] = $this->specification['rend']['ruleNode'][$element['to']]['abstract'];
                    else:
                        $element['abstract'] = $this->specification['rend']['ruleNode'][$element['to']]['abstract'];
                        $element['dimension'] = $node;
                    endif;


                    $element['metric'] = $this->specification['rend']['ruleNode'][$element['to']]['concept'];
                    $element['col'] = $n;

                    $branch[] = $element;
                endif;
            endif;
        endforeach;
        return $branch;
    }

    public function buildZAxis(array $elements, $parentId = 0, $node = array())
    {
        $branch = array();

        foreach ($elements as $element) :

            if ($element['from'] == $parentId) :

                //ruleNodes
                if (is_array($this->specification['rend']['ruleNode'][$element['from']]['dimension'])):
                    $node =
                        array_replace($node, $this->specification['rend']['ruleNode'][$element['from']]['dimension']);
                endif;

                $children = $this->buildZAxis($elements, $element['to'], $node);

                if ($children):


                    //ruleNodes
                    if (is_array($this->specification['rend']['ruleNode'][$element['to']]['dimension'])):
                        $element['dimension'] =
                            array_replace($node, $this->specification['rend']['ruleNode'][$element['to']]['dimension']);
                        $element['abstract'] = $this->specification['rend']['ruleNode'][$element['to']]['abstract'];
                    else:
                        $element['dimension'] = $node;
                    endif;
                    $element['metric'] = $this->specification['rend']['ruleNode'][$element['to']]['concept'];


                    $branch[] = $element;

                    foreach ($children as $c):

                        $branch[] = $c;

                    endforeach;

                else:

                    //ruleNodes
                    if (is_array($this->specification['rend']['ruleNode'][$element['to']]['dimension'])):
                        $element['dimension'] =
                            array_replace($node, $this->specification['rend']['ruleNode'][$element['to']]['dimension']);
                        $element['abstract'] = $this->specification['rend']['ruleNode'][$element['to']]['abstract'];
                    else:
                        $element['dimension'] = $node;
                    endif;


                    $element['metric'] = $this->specification['rend']['ruleNode'][$element['to']]['concept'];


                    $branch[] = $element;
                endif;
            endif;
        endforeach;
        return $branch;
    }

    public function searchLabel($value, $role)
    {

        switch ($role):
            case 'http://www.xbrl.org/2008/role/label':


                $found =
                    Data::searchLabel($this->specification[$this->lang], 'href', Format::getAfterSpecChar($value, '_'));


                foreach ($found as $value):

                    if ($value['role'] == $role):

                        return $value['@content'];
                    endif;
                endforeach;

                break;

            case 'http://www.xbrl.org/2008/role/verboseLabel':

                $found =
                    Data::searchLabel($this->specification[$this->lang], 'href', Format::getAfterSpecChar($value, '_'));
                foreach ($found as $value):

                    if ($value['role'] == $role):

                        return $value['@content'];
                    endif;
                endforeach;

                break;

            case 'http://www.eurofiling.info/xbrl/role/rc-code':

                $found = DomToArray::search_multdim($this->specification['lab-codes'], 'href', $value);

                foreach ($found as $value):

                    if ($value['role'] == $role):

                        return $value['@content'];
                    endif;
                endforeach;

                break;


            case 'http://www.eurofiling.info/xbrl/role/filing-indicator-code':

                $found = DomToArray::search_multdim($this->specification['lab-codes'], 'href', $value);

                foreach ($found as $value):

                    if ($value['role'] == $role):

                        return $value['@content'];
                    endif;
                endforeach;

                break;

            case 'http://www.eba.europa.eu/xbrl/role/dpm-db-id':

                $found = DomToArray::search_multdim($this->specification['lab-codes'], 'href', $value);

                foreach ($found as $value):

                    if ($value['role'] == $role):

                        return $value['@content'];
                    endif;
                endforeach;

                break;


            case 'http://xbrl.org/arcrole/PWD/2013-05-17/table-breakdown':

                $found =
                    array_replace_recursive($this->specification['rend']['tableBreakdownArc'], $this->specification['rend']['breakdownTreeArc']);

                $arr = array();
                foreach ($found as $key => $value) {
                    if ($value['axis']):
                        $arr[$value['axis']][$value['to']] = $value;
                    endif;
                }

                return $arr;
                break;

            case 'http://xbrl.org/arcrole/PWD/2013-05-17/breakdown-tree':


                $found = DomToArray::search_multdim($this->specification['rend']['breakdownTreeArc'], 'to', $value);

                foreach ($found as $value):

                    if ($value['arcrole'] == 'http://xbrl.org/arcrole/PWD/2013-05-17/breakdown-tree'):
                        return $value['from'];

                    elseif ($value['arcrole'] == 'http://xbrl.org/arcrole/2014/breakdown-tree'):

                        return $value['from'];
                    endif;
                endforeach;

                break;
        endswitch;
    }

    private function getAllDimensions()
    {
        $ruleNode = $this->specification['rend']['ruleNode'];

        $dim = array();
        foreach ($ruleNode as $key => $row):

            if (isset($this->breakdownTreeArc['z']['to']) && $key == $this->breakdownTreeArc['z']['to'])
                break;
            if (isset($row['dimension']) && is_array($row['dimension'])):
                foreach ($row['dimension'] as $key => $r):
                    if (!in_array($key, $dim) and $row['abstract'] != 'ture' and $row['concept'] != "false"):
                        $dim[$key] = strstr($r, ':', true);
                    endif;
                endforeach;
            endif;
        endforeach;
        return $dim;
    }

    /* Postoji bug u ovoj fukciji odnosi se na izvještaj c_08.01.b */
    /* Provjera vezu sa tax Definition odnosno da li se polja koriste ili ne */

    public function checkDef($dim)
    {

        $this->specification['def'];


        $dimA = json_decode($dim, true);
        $dom = array();


        $metric = null;
        foreach ($dimA as $key => $element):
            $p = explode('_', $element);

            if ($key === 'metric'):

                if ($element != 'false'):

                    $val = explode(':', $p[1]);
                    $key_help = $p[0] . '_' . $val[1];
                    $metric = $key_help;
                    $dom['metric'] = $key_help;

                endif;
            else:

                if (count($p) == 2):

                    $val = explode(':', $p[1]);

                    $key_help = $p[0] . '_' . $val[0] . ':' . $p[0] . '_' . $val[1];
                    $keyDim = strtok($key, '_') . '_' . substr($key, strpos($key, ":") + 1);
                    $dom[$keyDim . ':' . $key_help] = $keyDim;

                elseif (count($p) == 3):

                    $val = explode(':', $p[2]);
                    $key_help = $p[1] . '_' . $val[0] . ':' . $p[0] . '_' . $val[1];
                    $keyDim = strtok($key, '_') . '_' . substr($key, strpos($key, ":") + 1);
                    $dom[$keyDim . ':' . $key_help] = $keyDim;

                endif;
            endif;

        endforeach;


        foreach ($this->specification['def'] as $key => $val):
            $tmpDom = $dom;
            if (isset($tmpDom['metric']) && isset($val[$tmpDom['metric']])):
                unset($tmpDom['metric']);
                foreach ($val as $keyVal => $row):
                    if (array_key_exists($keyVal, $tmpDom)):

                        unset($tmpDom[$keyVal]);

                    endif;
                endforeach;


                if (empty($tmpDom)):
                    return $val[$metric];
                endif;

            endif;


        endforeach;

        return false;
    }

    public function getHierKey($domain, $value)
    {


        $_searckKey = Format::getAfterSpecChar(Format::getBeforeSpecChar($value, '_'), '#');

        foreach ($domain as $key => $row):


            if (strpos($key, $_searckKey) !== false):
                $_val = Format::getAfterSpecChar($value, '_');
                return $key . ':' . $_val;

            endif;

        endforeach;


    }

    public function mergeDimensions($x, $y, $typ = null)
    {

        $allDim = $this->getAllDimensions();
        $metric = array();

        if (!empty($x)):
            $x = call_user_func_array('array_merge', $x);
        endif;

        if (!empty($y) && !array_key_exists('dimensionAspect', $y)):
            $y = call_user_func_array('array_merge', $y);
        endif;


        if (isset($x['metric']) && $x['metric'] != 'false'):
            $metric = ['metric' => $x['metric']];
        elseif (isset($y['metric'])):
            $metric = ['metric' => $y['metric']];
        endif;


        // $merge=array();
        if (isset($y['dimensionAspect']) && isset($x['dimension'])):
            $merge = array_merge($metric, (array)$x['dimension']);
        elseif (isset($x['dimension']) && isset($y['dimension'])):
            $merge = array_merge($metric, (array)$x['dimension'], (array)$y['dimension']);
        elseif (isset($y['dimensionAspect'])):
            if (is_array($typ)):
                return json_encode(array_merge(array($y['dimensionAspect'] => "*"), $typ));
            else:
                return json_encode(array($y['dimensionAspect'] => "*"));
            endif;

        elseif (isset($x['dimension'])):
            $merge = array_merge($metric, (array)$x['dimension']);
        else:
            $merge = $metric;
        endif;


        //  echo "<pre>", print_r($merge), "</pre>";
        foreach ($allDim as $key => $row):
            if (!key_exists($key, $merge)):
                $merge[$key] = $row . ':x0';
            endif;
        endforeach;

        return json_encode($merge);
    }

    public function getMaxRow($row)
    {
        $var = array(1);
        if (isset($row) && !empty($row)):
            foreach ($row as $key => $row):
                $num = substr($key, strpos($key, "r") + 1);
                if (is_numeric($num)):
                    $var[] = $num;
                endif;
            endforeach;
            return max($var);
        else:
            return 1;
        endif;
    }

}