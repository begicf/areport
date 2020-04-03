<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace DpmXbrl;

ini_set('max_execution_time', 300);
ini_set('memory_limit', '1024M');

use DpmXbrl\Library\Normalise;
use DpmXbrl\Library\DomToArray;

/**
 * Description of Modules
 *
 * @author begicf
 */
class Mod
{

    //put your code here
    private $modules;
    private $data = array();
    private $path;
    private $lang;


    // public function __construct($path = '/taxonomy')
    public function __construct($path = NULL, $lang = NULL)
    {
        if (!empty($path) && !empty($lang)):
            $this->path = $path;
            $this->lang = $lang;
            $this->modules = DomToArray::getPath($this->path, ['mod' => 'mod' . DIRECTORY_SEPARATOR]);
        endif;
    }


    private function getId($arr, $val)
    {
        if (is_array($arr)) {
            if (isset($arr['from']) && trim($arr['from']) == trim($val)) {
                return isset($arr['category_id']) ? $arr['category_id'] : 'Not found';
            }
            foreach ($arr as $values) {
                if (is_array($values)) {
                    return $this->getId($values, $val);
                }
            }
        }
    }


    public function getTable($id, $modulePath = null, $return = false)
    {
        $module = array();
        $i = 0;
        foreach ($this->modules['mod'] as $mod):

            $module[$i] = $this->getXbrlSpec($mod);
            $module[$i]['mod_path'] = Normalise::taxPath($mod);
            ++$i;
        endforeach;

        foreach ($module as $mod):

            $this->lang = Library\Data::checkLang($mod);

            if (isset($mod['pre'])):
                foreach ($mod['pre'] as $key => $row):


                    if (empty($id) && !isset($row['order']) && isset($row['label'])):

                        $name =
                            (empty($this->lang)) ? $row['label'] : call_user_func_array("array_merge", DomToArray::search_multdim($mod[$this->lang], 'from', $row['label']));
                        $this->data[] = [
                            'parent' => '#',
                            "children" => true,
                            'id' => $row['label'],
                            "text" => (empty($this->lang)) ? $row['label'] : $name['@content'],
                            "mod" => $mod['mod_path'],
                            'type' => '#'];


                    elseif (isset($row['from']) && $row['from'] == $id && isset($row['label'])):

                        $path = pathinfo(strtok($row['href'], "#"));

                        if (strpos($path['filename'], '-rend')):

                            $row['href'] =
                                preg_replace('#^https?://#', '', $path['dirname']) . DIRECTORY_SEPARATOR . str_replace('-rend', '.xsd', $path['filename']);

                            $path['extension'] = 'xsd';
                            $type = 'file';
                            $children = false;

                            $mod['mod_path'] = $modulePath;

                        else:

                            $type = 'group';
                            $children = true;
                        endif;

                        if ($path['extension'] == 'xsd'):


                            if (strpos($row['href'], 'www') !== false):

                                $str = preg_replace('#^https?://#', '', $row['href']);
                                $pathXsd = $this->getDir() . DIRECTORY_SEPARATOR . strtok($str, "#");
                            else:
                                $pathXsd =
                                    dirname($mod['pre']['path']) . DIRECTORY_SEPARATOR . strtok($row['href'], "#");
                            endif;


                            $getFile = explode('/', $pathXsd);

                            //Uzima putanju modula
                            $tpmPath = substr($mod['pre']['path'], 0, strpos($mod['pre']['path'], '/mod'));

                            //Pretražuje putanju tax gdje se nalazi modul i gleda da li postoji XSD file koji je jednak linkovanom fajlu
                            $getFileXsdSource = DomToArray::getPath($tpmPath, ['tab' => end($getFile)]);
                     ;
                            //Get XBRL specification source

                            if (empty($getFileXsdSource)):
                                throw new \Exception(("Not found tab.xsd"));
                            endif;

                            $linkSource = $this->getXbrlSpec($getFileXsdSource['tab'][0]);

                            $ext_code = null;
                            if (isset($linkSource['lab-codes'])):
                                $ext_code =
                                    DomToArray::search_multdim_multival($linkSource['lab-codes'], $row['label'], 'http://www.eba.europa.eu/xbrl/role/dpm-db-id');

                            endif;

                            //Get XBRL specification destination
                            $linkDestination = $this->getXbrlSpec($pathXsd);

                            $link = array_merge($linkSource, $linkDestination);
                            //  echo "<pre>", print_r($link), "</pre>";

                            $this->lang = Library\Data::checkLang($link);


                            try {

                                $name =
                                    (empty($this->lang)) ? $row['label'] : current(DomToArray::search_multdim($link[$this->lang], 'from', $row['label']));

                            } catch (\Exception $e) {
                                throw new \Exception('The name is not set for: ' . $row['label']);

                            }
                        endif;

                        $this->data[$row['order'] - 1] = [
                            'parent' => $row['from'],
                            "children" => $children,
                            'data' => $pathXsd,
                            'lang' => preg_replace('/lab-/', '', $this->lang, 1),
                            'id' => $row['to'],
                            'ext_code' => $ext_code,
                            "text" => (empty($name)) ? $row['href'] : $name['@content'],
                            "mod" => $mod['mod_path'],
                            'type' => $type];

                    endif;

                endforeach;
            endif;

        endforeach;
        if ($return == false):
            echo json_encode($this->data);
        else:
            return $this->data;
        endif;
    }

    public function getXbrlSpec($path)
    {

        if (file_exists($path)):


            $spec = array();

            $xbrl = new Set($path, Library\Data::getLangSpec('mod'));

            $_xbrl = $xbrl->load();

            if (is_array($_xbrl) || is_object($_xbrl)):

                foreach ($xbrl->load() as $key => $row):
                    $spec[$key] = $row->Xbrl;
                endforeach;
            endif;
            return $spec;


        else:
            die('Not found' . $path);
        endif;
    }


    private function getDir()
    {

        return $this->path;
    }

}
