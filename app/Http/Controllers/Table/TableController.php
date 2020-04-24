<?php

namespace App\Http\Controllers\Table;

use App\Http\Controllers\Controller;
use App\Model\Taxonomy;
use DpmXbrl\Library\Data;
use DpmXbrl\Library\Normalise;
use Illuminate\Http\Request;
use DpmXbrl\Tax;


class TableController extends Controller
{

    private $_tablePath;
    private $_modPath;
    private $_period;
    private $_taxonomy;

    public function table(Request $request)
    {


        $module=Data::getTax($request->get('mod'));

        dump(Data::buildTree($module['pre'],'loc_fba_Corep_Ind'));
        dd($request->all());
        $this->_taxonomy = Taxonomy::all()->where('active', true)->first();

        $_taxonomyPath= storage_path('app/public/') . $this->_taxonomy->file;

       // dd($request->all());

//            $this->_tablePath = Normalise::taxPath(($request->get('taxonomy')));
//            $this->_modPath = Normalise::taxPath($request->get('mod'));

            $this->_period = $request->get('period');
//            $this->_sheetcode = $request->get('sheetcode');
//            $this->_lang = $request->get('lang');



        //loc_fba_tgBA_84.00


        //$mod = Data::getTax(Config::publicDir() . $this->_modPath, Config::$moduleSet, false);



        if (file_exists($request->get('table_xsd'))):


            $tmpDir = pathinfo($request->get('table_xsd'));


            $tax = Data::getTax($request->get('table_xsd'));

            $taxOb = new Tax();
            //$tax = $taxOb->getTax();



//            $mod = Data::searchLabel($mod['pre'], 'href', Format::getAfterSpecChar(key($tax['rend']['table']), '_'));
//
//            $tax_path = Format::getBeforeSpecChar($this->_modPath, '/');
//
//            $_mod =
//                (new Mod(public_path() . DIRECTORY_SEPARATOR . 'tax' . DIRECTORY_SEPARATOR . $tax_path, 'bs-Latn-BA'))->getTable(current($mod)['from'], $this->_modPath, true);


            $tableID = $taxOb->getTableID($tax);

            return view('table.table', [
                'tableHtml' => $taxOb->render()->renderHtml($tax),
                'taxonomy' => $this->_tablePath,

                'period' => $this->_period,

                'ext_code' => $tableID,
                'mod' => $this->_modPath,
                'group' => $request->get('mod')
            ]);


        else:
            abort(404);
        endif;
    }
}
