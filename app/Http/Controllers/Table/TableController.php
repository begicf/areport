<?php

namespace App\Http\Controllers\Table;

use App\Http\Controllers\Controller;
use App\Model\FactHeader;
use App\Model\FactModule;
use App\Model\FactTable;
use App\Model\Taxonomy;
use AReportDpmXBRL\Library\Data;
use AReportDpmXBRL\Library\Format;
use AReportDpmXBRL\ReadExcel;
use AReportDpmXBRL\Render;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;


class TableController extends Controller
{

    private $_tablePath;
    private $_period;
    private $_taxonomy;

    public function __construct()
    {
        $this->_taxonomy = Taxonomy::all()->where('active', '=', 1)->first();

        $this->_period = Carbon::parse(request('period'))->format('Y-m-d');


    }

    public function table(Request $request)
    {


        if ($request->get('view_home')):

            $fact_module = FactModule::find($request->get('id'));
            $_groups = json_decode($fact_module->groups);
            $module_path = $fact_module->module_path;
            $module_name = $fact_module->module_name;

        elseif ($request->get('view_table')):

            dd($request->all());

        elseif ($request->get('table')):

            $_groups = [];

            foreach ($request->get('table') as $item):

                $tmp = json_decode($item, true);

                $_groups[key($tmp)] = json_encode($tmp);

            endforeach;

            $module_path = $request->get('module_path');
            $module_name = $request->get('module_name');

            Session::flash('groups', $_groups);

        else:

            return redirect('/home')->with('warning', 'Please chose the table group!');

        endif;

        return view('table.table', [

            'taxonomy' => $this->_tablePath,
            'groups' => $_groups,
            'period' => $this->_period,
            'mod' => $module_path,
            'module_name' => $module_name,
            'group' => $request->get('mod')
        ]);


    }

    private function getTablePath($table, $group = null): ?string
    {


        if ($table):
            $tc = $table;
        else:


            $tc = current(current($group));

        endif;

        return $tc;

    }

    public function renderTable(Request $request)
    {


        $groups_array = json_decode($request->get('group'), true);

        $tc = $this->getTablePath($request->get('tab'), $groups_array);

        if (file_exists($tc)):

            $tax = Data::getTax($tc);

            $render = new Render();

            $data = $this->getData($tc);

            $import['sheets'] = $data['sheets'] ?? '000';
            $import['file'] = $data;
            $import['ext'] = 'DB';

            $data = $render->render()->renderHtml($tax, $import);

            $data['groups'] = $this->makeButtonGroup($groups_array, $tc);
            $data['table_path'] = $tc;
            return response($data);
        else:
            return abort(404);
        endif;

    }

    public function getData($tc = null)
    {

        if (is_null($tc)):
            $tc = $this->getTablePath(request()->get('tab'));
        endif;

        $cr_sheet = null;

        if (request()->get('sheet')):
            $sheet = (json_decode(request()->get('sheet'), true));
            $cr_sheet = $sheet['sheet'];
            unset($sheet['sheet']);
        endif;

        $data =
            FactHeader::getCRData($this->getNormalizeTable($tc), $this->_period, $this->getNormalizeModule(request()->get('mod')), $cr_sheet);

        return (request()->get('json')) ? response()->json($data) : $data;

    }

    private function getNormalizeTable($table)
    {

        return Format::getAfterSpecChar($table, $this->_taxonomy->folder, strlen($this->_taxonomy->folder) + 1);

    }

    private function getNormalizeModule($module)
    {
        if (strpos($module, $this->_taxonomy->folder)):
            return Format::getAfterSpecChar($module, $this->_taxonomy->folder, strlen($this->_taxonomy->folder) + 1);
        endif;
        return $module;
    }

    public function exportTable(Request $request)
    {

        $tax = Data::getTax($request->get('table'));

        $render = new Render();

        $import = FactHeader::getCRData(
            $this->getNormalizeTable($request->get('table')),
            $this->_period,
            $this->getNormalizeModule($request->get('mod')),
            null,
            true
        );

        $additional['period']=$this->_period;

        if ($request->get('export_type') == 'xslx'):
            $render->export($tax, null, $request->get('export_type'), $additional)->renderOutputAll($import)->exportFormat();
        else:
            $render->export($tax, null, $request->get('export_type'), $additional)->renderOutput($import)->exportFormat();
        endif;


    }

    private function makeButtonGroup($array, $table): ?string
    {
        $buttonGroup = null;

        if (is_array($array) && count($array) > 1):

            $buttonGroup = "<div class='btn - group' role='group' aria-label='Basic example'>";
            foreach ($array as $key => $row):
                $active = ($row == $table) ? 'active' : '';
                $buttonGroup .= "<button type='button' onclick='changeTable(this, \"T\")' value='$row'  class='btn btn-primary $active'>" . Format::getAfterSpecChar($key, '_t', 2) . "</button>";
            endforeach;
            $buttonGroup .= "</div>";
        endif;

        return $buttonGroup;
    }


    public function importTable(Request $request)
    {

        $import = NULL;

        $path = pathinfo($request->get('taxonomy'));


        $methode = str_replace(".", "", $path['filename']);
        $file_name = $_FILES['fileToUpload']['name'];
        $tpn_name = $_FILES['fileToUpload']['tmp_name'];

        $import['ext'] = pathinfo($file_name, PATHINFO_EXTENSION);

        if ($import['ext'] == 'xbrl'):

            $upload = new UploadXbrl($tpn_name);

            $import['file'] = $upload->Instance();

        elseif ($import['ext'] == 'xlsx'):

            $upload = new ReadExcel($tpn_name, request('sheetcode'));

            $args['column'] = $request->get('column');
            $args['colspan'] = $request->get('colspanmax');
            $args['rowspan'] = $request->get('rowspanmax');
            $args['typ_table'] = $request->get('typ_table');
            $import['file'] = $upload->$methode($args);

        elseif ($import['ext'] == 'xml' || $import['ext'] == 'json'):

            return response()->json($this->ImportXMLJSON($import['ext'], $tpn_name));

        endif;


        return $import;
    }


    private function ImportXMLJSON($format, $tpn_name)
    {
        $sheet = (request('sheetcode')) ?? '000';
        $ext_code = request('ext_code');
        $file = file_get_contents($tpn_name);


        if ($format == 'xml'):
            $xml = simplexml_load_string($file, "SimpleXMLElement", LIBXML_NOCDATA);
            $file = json_encode($xml);
        endif;

        $arr = json_decode($file, TRUE);
        if (empty(request('typ_table'))):
            return ['ext' => 'xlsx', 'file' => $arr['table_' . $ext_code]['sheet_' . $sheet]];
        else:
            $tmp = [];
            $r = [];
            foreach ($arr['table_' . $ext_code]['sheet_' . $sheet] as $key => $row):
                $k = substr($key, strpos($key, "r") + 1);
                $r[$k] = $k;
                $tmp[$key] = $row;
            endforeach;

            $tmp['row'] = max($r) - 1;

            return ['ext' => 'xlsx', 'file' => $tmp];
        endif;

    }

    public function saveTable(Request $request)
    {


        $mod = $this->getNormalizeModule($request->get('module'));

        $this->saveModule();

        if ($request->get('table_data')):

            $tc = $request->get('tab');

            if (!empty($request->get('sheet'))):
                $cr_sheet = (json_decode($request->get('sheet'), true))['sheet'];
            endif;

            $tab = $this->getNormalizeTable($tc);


            $fact_module = FactModule::where(
                [['period', $this->_period], ['module_path', $mod]])->first();

            if (empty($fact_module->id)):
                throw new \Exception('An error has occurred! Fetching Fact Module! ');
            endif;

            $fact_header = FactHeader::updateOrCreate(

                [
                    'module_id' => $fact_module->id,
                    'table_path' => $tab,
                ],
                [
                    'module_id' => $fact_module->id,
                    'table_path' => $tab,
                    'cr_sheet_code_last' => $cr_sheet ?? '000'
                ]

            );

            FactTable::storeInstance($request->get('table_data'), $fact_header->id, $request->get('sheet'));
        endif;
    }

    private function saveModule()
    {

        $mod = $this->getNormalizeModule(\request('module'));

        if (Session::has('groups')):
            FactModule::updateOrCreate(
                [
                    'period' => \request('period'),
                    'module_path' => $mod,
                    'taxonomy_id' => $this->_taxonomy->id,
                ],
                [
                    'period' => \request('period'),
                    'taxonomy_id' => $this->_taxonomy->id,
                    'module_name' => \request('module_name'),
                    'module_path' => $mod,
                    'groups' => json_encode(Session::get('groups'))
                ]);

        endif;


    }

}
