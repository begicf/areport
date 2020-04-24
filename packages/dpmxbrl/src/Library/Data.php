<?php


namespace DpmXbrl\Library;


use DpmXbrl\Config\Config;
use DpmXbrl\Set;

/**
 *
 *
 * @author begicf
 */
class Data
{

    /**
     * @param $path
     * @param null $config
     * @param null $assetion
     * @return array|null
     */
    public static function getTax($path, $config = null, $assertion = null): ?array
    {


        $_tax = array();
        $tax = new Set($path, $config, $assertion);

        $_tax['imports'] = $tax->imports;
        $_tax['namespace'] = $tax->namespace;
        $_tax['elements'] = $tax->elements;

        foreach ($tax->load() as $key => $row):
            $_tax[$key] = $row->Xbrl;
        endforeach;


        return $_tax;
    }

    /**
     * @param $rows
     * @param $key
     * Korisi se kod explicitDimension
     * @return string|null
     */
    public static function getSelectedValue($rows, $key): ?string
    {


        $val = json_decode($key);

        if (!empty($rows)):
            foreach ($rows as $row):

                if (Format::getAfterSpecChar($row['href'], '#') === Format::getAfterSpecChar(current($val), ':')):

                    return $row['@content'];
                endif;


            endforeach;
        endif;
        return null;

    }

    /**
     * @param $breakdownTreeArc
     * @param $aspectNode
     * Pronalazi i setuje aspectNode po osama
     * @return array|null
     */
    public static function aspectNode($breakdownTreeArc, $aspectNode): ?array
    {

        $arr = [];

        foreach ($aspectNode as $a => $row):
            foreach ($breakdownTreeArc as $k => $item):
                $temp = DomToArray::search_multdim($item, 'to', $a);
                if (!empty($temp)):
                    $arr[$k] = $item;
                endif;

            endforeach;

        endforeach;

        return $arr;
    }

    /**
     * @param $arr
     * @param $field
     * @param $value
     * @return array|null
     */
    public static function searchLabel($arr, $field, $value)
    {
        if (!empty($arr)):
            $found = array();

            foreach ($arr as $key => $row):

                if (isset($row[$field])):
                    $str = Format::getAfterSpecChar(substr($row[$field], strpos($row[$field], '#') + 1), '_');

                    if ($str === $value):
                        $found[$key] = $row;
                    endif;


                endif;

            endforeach;
            return $found;
        endif;
        return null;
    }

    /**
     * @param $spec
     * @return array
     */
    public static function getLangSpec($spec)
    {
        foreach (Config::$lang as $row):
            $lang['lab-' . $row] = 'lab-' . $row;
        endforeach;

        switch ($spec):
            case "all":
                return \array_merge($lang, Config::$confSet);
            case "mod":
                return \array_merge($lang, Config::$moduleSet);
        endswitch;
    }

    /***
     * @param $array
     * @return string
     */
    public static function checkLang($array)
    {

        foreach (Config::$lang as $lang):
            if (array_key_exists('lab-' . $lang, $array)) {

                return 'lab-' . $lang;
            }

        endforeach;
    }


    public static function buildTree(array $elements, $parentId = 0)
    {
        $branch = array();

        foreach ($elements as $element) {
            if (isset ($element['from']) && $element['from'] == $parentId) {
                $children = self::buildTree($elements, $element['to']);
                if ($children) {
                    $element['children'] = $children;
                }
                $branch[] = $element;
            }
        }

        return $branch;
    }

}
