<?php

namespace App\Http\Controllers\Modules;

use App\Http\Controllers\Controller;
use App\Model\Taxonomy;
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

    public function test()
    {


        $mod = new Mod(storage_path('app/public/') . $this->_taxonomy->file, 'en');

        $mod->getModule('#');

    }

    public function json(Request $request)
    {

        $mod = new Mod(storage_path('app/public/') . $this->_taxonomy->file, 'en');

        $id = $request->get('id');
//        if ($id != '#'):
//
//            $mod->getTable($request->get('id'), $request->get('id'));
//
//        else:
       //return json_encode($mod->getModule($id, $request->get('ext'), $request->get('path')), $request->get('mod'));
        return response()->json($mod->getModule($id, $request->get('ext'),$request->get('path'),$request->get('mod')));

//        endif;


    }
}
