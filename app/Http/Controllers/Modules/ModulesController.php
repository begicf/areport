<?php

namespace App\Http\Controllers\Modules;

use App\Http\Controllers\Controller;
use App\Model\Taxonomy;
use DpmXbrl\Library\ArrayManipulation;
use DpmXbrl\Library\Data;
use DpmXbrl\Library\Format;
use Illuminate\Http\Request;
use DpmXbrl\Mod;


class ModulesController extends Controller
{
    private $_taxonomy;

    public function __construct()
    {
        $this->_taxonomy = Taxonomy::all()->where('active', true)->first();


    }

    public function index()
    {
        if (empty($this->_taxonomy)) {

            return redirect('/home')->with('warning', 'Please active the taxonomy !');

        }

        return view('modules.modules');
    }

    public function group(Request $request)
    {

        $module = Data::getTax($request->get('module'));

        $parent = ArrayManipulation::searchHref($module['pre'], key($module['elements']));

        $groups = Mod::getGroupTable($module['pre'], key($parent));

        $_g = [];

        foreach ($groups as $key => $group) {
            $tmp = [];
            $tmp['group'] = $key;
            foreach ($group as $row) {

                $k = Format::getAfterSpecChar($row['href'], '#');
                $tmp['table'][$k] = dirname($request->get('module')) . DIRECTORY_SEPARATOR . (explode("-rend", $row['href']))[0] . '.xsd';
            }

            $_g[$key][] = json_encode($tmp);

        }

        return response()->json($_g);

    }

    public function json(Request $request)
    {

        $mod = new Mod(storage_path('app/public/') . $this->_taxonomy->file);

        $id = $request->get('id');

        return response()->json($mod->module($id, $request->get('ext'), $request->get('path'), $request->get('mod')));


    }
}
