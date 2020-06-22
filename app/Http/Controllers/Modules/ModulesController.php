<?php

namespace App\Http\Controllers\Modules;

use App\Http\Controllers\Controller;
use App\Model\FactModule;
use App\Model\Taxonomy;
use AReportDpmXBRL\Config\Config;
use AReportDpmXBRL\Library\ArrayManipulation;
use AReportDpmXBRL\Library\Data;
use AReportDpmXBRL\Library\Format;
use AReportDpmXBRL\ModuleTree;
use Illuminate\Http\Request;


class ModulesController extends Controller
{
    private $_taxonomy;

    public function __construct()
    {
        $this->_taxonomy = Taxonomy::all()->where('active', '=', 1)->first();


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


        if (is_file($request->get('module'))):

            $module = Data::getTax($request->get('module'));
            $dir = dirname($request->get('module'));
        else:
            $tax = FactModule::where('module_path', '=', $request->get('module'))->with('taxonomy')->first();
            $path = Config::publicDir() . DIRECTORY_SEPARATOR . $tax->taxonomy->folder . DIRECTORY_SEPARATOR . $request->get('module');

            $module = Data::getTax($path);
            $dir = dirname($path);
        endif;


        $parent = ArrayManipulation::searchHref($module['pre'], key($module['elements']));

        $groups = ModuleTree::getGroupTable($module['pre'], key($parent));

        $_g = [];

        foreach ($groups as $key => $group) {
            $tmp = [];

            foreach ($group as $row) {

                $k = Format::getAfterSpecChar($row['href'], '#');
                $tmp[$k] =
                    $dir . DIRECTORY_SEPARATOR . (explode("-rend", $row['href']))[0] . '.xsd';
            }

            $_g[$key] = json_encode($tmp);

        }

        return response()->json($_g);

    }

    public function json(Request $request)
    {

        $mod =
            new ModuleTree(storage_path('app/public/') . $this->_taxonomy->path . DIRECTORY_SEPARATOR . $this->_taxonomy->folder);

        $id = $request->get('id');

        return response()->json($mod->module($id, $request->get('ext'), $request->get('path'), $request->get('mod')));


    }
}
