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

        return view('modules.index');
    }

    public function json(Request $request)
    {

        $mod = new Mod(storage_path('app/public/') . $this->_taxonomy->file, 'en');

        if ($request->get('id') != '#'):

            $mod->getTable($request->get('id'), $request->get('id'));

        else:

            $mod->getTable(NULL);

        endif;


    }
}
