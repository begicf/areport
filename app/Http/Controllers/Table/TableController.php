<?php

namespace App\Http\Controllers\Table;

use App\Http\Controllers\Controller;
use App\Model\Taxonomy;
use DpmXbrl\Library\Data;
use DpmXbrl\Library\Format;
use DpmXbrl\ReadExcel;
use DpmXbrl\Tax;
use DpmXbrl\UploadXbrl;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Input;


class TableController extends Controller
{

    private $_tablePath;
    private $_period;
    private $_taxonomy;


    public function table(Request $request)
    {

        $this->_taxonomy = Taxonomy::all()->where('active', true)->first();
        $this->_period = $request->get('period');

        $table = array_map('json_decode', $request->get('table'));

        $groups = array_column($table, 'group');

        $tables = array_map(function ($arr) {
            return $arr->group = json_encode($arr->table);
        }, $table);

        $_groups = array_combine($groups, $tables);

        return view('table.table', [

            'taxonomy' => $this->_tablePath,
            'table' => $table,
            'groups' => $_groups,
            'period' => $this->_period,
            'mod' => $request->get('mod'),
            'group' => $request->get('mod')
        ]);


    }

    public function renderTable(Request $request)
    {

        $groups_array = json_decode($request->get('group'), true);

        if ($request->get('tab')):
            $tc = $request->get('tab');
        else:
            $tc = current($groups_array);
        endif;

        if (file_exists($tc)):

            $tax = Data::getTax($tc);

            $taxOb = new Tax();

            $data = $taxOb->render()->renderHtml($tax);
            $data['groups'] = $this->makeButtonGroup($groups_array, $tc);
            $data['table_path'] = $tc;
            return response($data);
        else:
            abort(404);
        endif;

    }

    public function exportTable(Request $request)
    {

        $tax = Data::getTax($request->get('table'));

        $taxOb = new Tax();

        $taxOb->export($tax, null, $request->get('export_type'), null)->renderOutputAll(null)->exportFormat();


    }

    private function makeButtonGroup($array, $table): ?string
    {
        $buttonGroup = null;

        if (is_array($array) && count($array) > 1):

            $buttonGroup = "<div class='btn-group' role='group' aria-label='Basic example'>";
            foreach ($array as $key => $row):
                $active = ($row == $table) ? 'active' : '';
                $buttonGroup .= "<button type='button' onclick='changeTable(this,\"T\")' value='$row'  class='btn btn-primary $active'>" . Format::getAfterSpecChar($key, '_t', 2) . "</button>";
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
}
