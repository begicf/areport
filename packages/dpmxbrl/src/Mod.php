<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace DpmXbrl;

ini_set('max_execution_time', 300);
ini_set('memory_limit', '1024M');

use DpmXbrl\Config\Config;
use DpmXbrl\Library\Directory;
use DpmXbrl\Library\Format;
use DpmXbrl\Library\Normalise;
use DpmXbrl\Library\DomToArray;
use DpmXbrl\Library\Data;

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
    private $freamworks;


    // public function __construct($path = '/taxonomy')
    public function __construct($path = NULL, $lang = NULL)
    {
        if (!empty($path) && !empty($lang)):
            $this->path = $path;
            $this->lang = $lang;
            // $this->freamworks = Directory::searchFile($this->path, 'fws.xsd');
            // $this->freamworks = current(Directory::searchFileGlob($this->path . DIRECTORY_SEPARATOR . 'fws.xsd'));
            //$this->modules = Directory::getPath($this->path, ['mod' => 'mod' . DIRECTORY_SEPARATOR]);
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


    public function getModule($id, $ext, $path, $mod = null)
    {


        switch ($ext):

            case 'fws':
                //$fws = Directory::searchFile($this->path, 'fws.xsd');
                //potrebno ispravit
                $fws = Directory::searchFileExclude($this->path, 'fws.xsd');
                //$fws = current(Directory::searchFileGlob($this->path . DIRECTORY_SEPARATOR . 'fws.xsd'));;
                return $this->getFreamworks($fws);

                break;
            case 'tax':
                $taxonomy = Directory::searchFile($path, 'tax.xsd');
                return $this->getTaxonomy($id, $taxonomy);
                // dd($tax);
                break;
            case 'mod':
                //dump($path);
                if (!empty($path)):
                    $this->modules = Directory::getPath($path, ['mod' => 'mod/']);
                endif;

                $this->getTable($id, $mod);
                return $this->getTable($id, $mod);
                break;


        endswitch;


    }


    public function getTaxonomy($id, $taxonomy)
    {

        $data = [];
        foreach ($taxonomy as $key => $rows):

            $tax = Data::getTax($rows->getRealPath(), null, null);

            foreach ($tax['elements'] as $k => $row):

                $data[] = [
                    'parent' => $id,
                    'children' => true,
                    'data' => $rows->getPath(),
                    'id' => str_replace(".", "", $row['name']),
                    'text' => $row['name'] . ' / ' . $row['creationDate'],
                    'type' => 'mod',
                    'creationDate' => $row['creationDate']

                ];
            endforeach;

        endforeach;
        usort($data, function ($a, $b) {
            return $a['creationDate'] <=> $b['creationDate'];
        });
        return $data;

    }

    public function getFreamworks($freamworks)
    {
//dd($this->freamworks);
        //dd($freamworks);
        $data = [];
        foreach ($freamworks as $fws):
//dump($fws);
            $fw = Data::getTax($fws->getRealPath(), null, null);

            foreach ($fw['elements'] as $row):

                $data[] = [
                    'parent' => '#',
                    'children' => true,
                    'data' => $fws->getPath() . DIRECTORY_SEPARATOR . strtolower($row['name'] . DIRECTORY_SEPARATOR),
                    'id' => $row['id'],
                    'text' => $row['name'],
                    'type' => 'fws'

                ];


            endforeach;
        endforeach;
        sort($data);

        return $data;
    }

    public function getTable($id, $modulePath = null)
    {

        $data = [];
        $module = array();
        $i = 0;
        foreach ($this->modules['mod'] as $mod):

            $module[$i] = $this->getXbrlSpec($mod);
            $module[$i]['mod_path'] = Normalise::taxPath($mod);
            ++$i;
        endforeach;
//dd($this->modules['mod']);
        foreach ($module as $mod):

            $this->lang = Library\Data::checkLang($mod);

            if (isset($mod['pre'])):
                foreach ($mod['pre'] as $key => $row):


                    if (!isset($row['order']) && isset($row['label'])):

                        $name =
                            (empty($this->lang)) ? $row['label'] : call_user_func_array("array_merge", DomToArray::search_multdim($mod[$this->lang], 'from', $row['label']));
//                        $data[] = [
//                            'parent' => $id,
//                            "children" => true,
//                            'data' => $this->path,
//                            'id' => $row['label'],
//                            'ext' => 'tab',
//                            "text" => (empty($this->lang)) ? $row['label'] : $name['@content'],
//                            "mod" => $mod['mod_path'],
//                            'type' => '#'
//                        ];


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

                        $data[$row['order'] - 1] = [
                            'parent' => $row['from'],
                            "children" => $children,
                            'data' => $this->path,
                            'lang' => preg_replace('/lab-/', '', $this->lang, 1),
                            'id' => $row['to'],
                            'ext_code' => $ext_code,
                            "text" => (empty($name)) ? $row['href'] : $name['@content'],
                            "mod" => $pathXsd,
                            'type' => $type
                        ];

                    endif;

                endforeach;
            endif;

        endforeach;

//dump($data);
        return $data;

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
