<?php

namespace App\Http\Controllers\Table;

use App\Http\Controllers\Controller;
use App\Model\FactHeader;
use App\Model\FactModule;
use App\Model\FactTable;
use App\Model\Taxonomy;
use AReportDpmXBRL\Helper\ReadExcel;
use AReportDpmXBRL\Library\Data;
use AReportDpmXBRL\Library\Format;
use AReportDpmXBRL\Render;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;


class TableController extends Controller
{


    private $_period;
    private $_taxonomy;

    public function __construct()
    {
        $this->_taxonomy = Taxonomy::query()->where('active', true)->first();

        $this->_period = Carbon::parse(request('period'))->format('Y-m-d');


    }

    public function table(Request $request)
    {


        if ($request->get('view_home')):

            $fact_module = FactModule::query()
                ->where('id', '=', $request->get('id'))
                ->where('taxonomy_id', '=', $this->_taxonomy->id)
                ->firstOrFail();
            $_groups = json_decode($fact_module->groups);
            $module_path = $fact_module->module_path;
            $module_name = $fact_module->module_name;
            $entity_id = $fact_module->entity_id;

        elseif ($request->get('table')):

            $_groups = [];

            foreach ($request->get('table') as $item):

                $tmp = json_decode($item, true);

                if (!is_array($tmp) || empty($tmp)) {
                    continue;
                }

                $groupName = key($tmp);

                if (!is_string($groupName) || $groupName === '') {
                    continue;
                }

                $_groups[$groupName] = json_encode($tmp);

            endforeach;

            if (empty($_groups)) {
                return redirect('/modules')->with('warning', 'Please choose at least one valid table group.');
            }

            $module_path = $request->get('module_path');
            $module_name = $request->get('module_name');
            $entity_id = $request->get('entity_id');

            Session::flash('groups', $_groups);

        else:

            return redirect('/home')->with('warning', 'Please choose a table group.');

        endif;

        return view('table.table', [

            'groups' => $_groups,
            'period' => $this->_period,
            'mod' => preg_replace('/\\\\/', '\\\\\\\\', $module_path),
            'module_name' => $module_name,
            'entity_id' => $entity_id ?? null,
            'group' => $request->get('mod')
        ]);


    }

    private function getTablePath($table, $group = null): ?string
    {

        if ($table):
            $tc = $table;
        else:
            $currentGroup = is_array($group) ? current($group) : null;
            $tc = is_array($currentGroup) ? current($currentGroup) : null;
        endif;

        return $tc;

    }

    public function renderTable(Request $request)
    {


        $groups_array = json_decode($request->get('group'), true);

        $tc = $this->getTablePath($request->get('tab'), $groups_array);

        if (file_exists($tc)):

            $tax = Data::getTax($tc);

            $render = new Render($tax);

            $data = $this->getData($tc);

            $import['sheets'] = $data['sheets'] ?? '000';
            $import['file'] = $data;
            $import['ext'] = 'DB';

            $data = $render->renderHtmlForm($import, $request->get('sheet'));
            $data['tableCode'] = $render->getCode();

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
            FactHeader::getCRData(
                $this->getNormalizeTable($tc),
                $this->_period,
                $this->getNormalizeModule(request()->get('mod')),
                $cr_sheet,
                null,
                $this->_taxonomy->id
            );

        return (request()->get('json')) ? response()->json($data) : $data;

    }

    private function getNormalizeTable($table)
    {
        if (strpos($table, $this->_taxonomy->folder) !== false):
            return Format::getAfterSpecChar($table, $this->_taxonomy->folder, strlen($this->_taxonomy->folder) + 1);
        endif;
        return $table;
    }

    private function getNormalizeModule($module)
    {
        if (strpos($module, $this->_taxonomy->folder) !== false):
            return Format::getAfterSpecChar($module, $this->_taxonomy->folder, strlen($this->_taxonomy->folder) + 1);
        endif;
        return $module;
    }

    public function exportTable(Request $request)
    {

        $tax = Data::getTax($request->get('table'));

        $import = FactHeader::getCRData(
            $this->getNormalizeTable($this->getNormalizeTable($request->get('table'))),
            $this->_period,
            $this->getNormalizeModule($this->getNormalizeModule($request->get('mod'))),
            null,
            true,
            $this->_taxonomy->id
        );

        $additional['period'] = $this->_period;

        $render = new Render($tax, null, $additional);


        $render->export($request->get('export_type'))->renderOutputAll($import);


    }

    private function makeButtonGroup($array, $table): ?string
    {
        $buttonGroup = null;

        if (is_array($array) && count(current($array)) > 1):

            $buttonGroup = "<div class='btn - group' role='group' aria-label='Basic example'>";
            foreach (current($array) as $key => $row):
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
        $table_code = request('table_code');
        $file = file_get_contents($tpn_name);


        if ($format == 'xml'):
            $xml = simplexml_load_string($file, "SimpleXMLElement", LIBXML_NOCDATA);
            $file = json_encode($xml);
        endif;

        $arr = json_decode($file, TRUE);
        if (empty(request('typ_table'))):
            return ['ext' => 'xlsx', 'file' => $arr['table_' . $table_code]['sheet_' . $sheet]];
        else:
            $tmp = [];
            $r = [];

            foreach ($arr['table_' . $table_code]['sheet_' . $sheet] as $key => $row):
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
                [
                    ['period', $this->_period],
                    ['module_path', $mod],
                    ['taxonomy_id', $this->_taxonomy->id],
                ]
            )->first();

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

    /**
     * Validate the reporting instance the user is entering: take the current form's
     * cell values, run this table's DPM-XL rules (via the shared InstanceValidator),
     * and return the failing cells so the form can highlight exactly what to fix.
     */
    public function validateInstance(Request $request)
    {
        $validatorClass = \Begicf\AReportPro\Services\InstanceValidator::class;
        $tableModel = \Begicf\AReportPro\Models\Dpm\DpmTable::class;
        if (! class_exists($validatorClass) || ! class_exists($tableModel)) {
            return response()->json(['ok' => false, 'error' => 'The DPM Architect package is not installed.'], 422);
        }

        /** @var \Begicf\AReportPro\Services\InstanceValidator $validator */
        $validator = app($validatorClass);
        if (! $validator->available()) {
            return response()->json(['ok' => false, 'error' => 'DPM-XL service is offline — start it to run validation.'], 422);
        }

        // Resolve the open table (by code, then by table-path basename) within the active taxonomy.
        $code = strtolower(trim((string) $request->get('table_code')));
        $base = strtolower(pathinfo((string) $request->get('tab'), PATHINFO_FILENAME));
        $table = $tableModel::query()
            ->where('taxonomy_id', $this->_taxonomy->id)
            ->where(function ($q) use ($code, $base) {
                if ($code !== '') {
                    $q->whereRaw('LOWER(code) = ?', [$code]);
                }
                if ($base !== '') {
                    $q->orWhereRaw('LOWER(code) = ?', [$base]);
                }
            })
            ->first();

        if (! $table || ! $table->framework) {
            return response()->json(['ok' => false, 'error' => 'No DPM framework rules found for this table.'], 422);
        }

        // Form fields are c<col>r<row>[value]; build the cell keys the rules use: t<table>,r<row>,c<col>.
        parse_str((string) $request->get('table_data'), $form);
        $tcode = strtolower($table->code);
        $cellValues = [];
        foreach ($form as $name => $payload) {
            if (! is_array($payload) || ! array_key_exists('value', $payload)) {
                continue;
            }
            if (! preg_match('/^c([^r]+)r(.+)$/i', $name, $m)) {
                continue;
            }
            $v = $payload['value'];
            if ($v === '' || $v === null) {
                continue;
            }
            $key = 't'.$tcode.',r'.strtolower($m[2]).',c'.strtolower($m[1]);
            $cellValues[$key] = is_numeric($v) ? ($v + 0) : $v;
        }

        if (empty($cellValues)) {
            return response()->json(['ok' => false, 'error' => 'Enter some values before validating.'], 422);
        }

        $result = $validator->validate($table->framework, $cellValues, [$table->code]);
        if (isset($result['error'])) {
            return response()->json(['ok' => false, 'error' => $result['error']], 422);
        }

        // No rules ran → say so plainly, so an empty report isn't mistaken for a clean pass.
        $note = ((int) ($result['summary']['total'] ?? 0) === 0)
            ? 'No validation rules are defined for '.$table->code.' in the active taxonomy ('.$this->_taxonomy->folder.').'
            : null;

        return response()->json([
            'ok' => true,
            'table' => $table->code,
            'values' => count($cellValues),
            'summary' => $result['summary'],
            'failed' => $result['failed'],
            'failed_cells' => $result['failed_cells'],
            'note' => $note,
        ]);
    }

    /**
     * Validate a whole saved module instance (all its tables) with the DPM-XL
     * rules — used from the Home registry. Requires the areport-pro validation
     * plugin and a running DPM-XL service.
     */
    public function validateModule(Request $request)
    {
        $validatorClass = \Begicf\AReportPro\Services\InstanceValidator::class;
        $tableModel = \Begicf\AReportPro\Models\Dpm\DpmTable::class;
        if (! class_exists($validatorClass) || ! class_exists($tableModel)) {
            return response()->json(['ok' => false, 'error' => 'The validation plugin is not installed.'], 422);
        }

        $module = FactModule::query()
            ->where('taxonomy_id', $this->_taxonomy->id)
            ->when($request->get('id'), fn ($q) => $q->where('id', $request->get('id')))
            ->when(! $request->get('id') && $request->get('module_path'), fn ($q) => $q->where('module_path', $request->get('module_path')))
            ->with('factHeader')
            ->first();

        if (! $module) {
            return response()->json(['ok' => false, 'error' => 'Instance not found.'], 404);
        }

        /** @var \Begicf\AReportPro\Services\InstanceValidator $validator */
        $validator = app($validatorClass);
        if (! $validator->available()) {
            return response()->json(['ok' => false, 'error' => 'DPM-XL service is offline — start it to run validation.'], 422);
        }

        // Build t<table>,r,c → value across every table in the module from saved data.
        $cellValues = [];
        $codes = [];
        $framework = null;
        foreach ($module->factHeader as $header) {
            $tcode = strtolower(pathinfo((string) $header->table_path, PATHINFO_FILENAME));
            if ($tcode === '') {
                continue;
            }
            $codes[$tcode] = true;
            if (! $framework) {
                // Resolve the table's framework — prefer the active taxonomy, else any
                // taxonomy that defines this table (that's where its rules live).
                $t = $tableModel::query()->whereRaw('LOWER(code) = ?', [$tcode])
                    ->orderByRaw('CASE WHEN taxonomy_id = ? THEN 0 ELSE 1 END', [$this->_taxonomy->id])
                    ->first();
                $framework = $t?->framework;
            }
            $cells = FactHeader::getCRData($header->table_path, $module->period, $module->module_path, null, true, $this->_taxonomy->id) ?? [];
            // getCRData with $all=true is sheet-keyed: [sheet][crcode][...]; flatten.
            foreach ($cells as $maybeSheet => $rows) {
                $rowset = (is_array($rows) && ! isset($rows['integer']) && ! isset($rows['string'])) ? $rows : [$maybeSheet => $rows];
                foreach ($rowset as $crCode => $vals) {
                    if (! is_string($crCode) || ! preg_match('/^c([^r]+)r(.+)$/i', $crCode, $m)) {
                        continue;
                    }
                    $v = $vals['string'] ?? null;
                    if ($v === '' || $v === null) {
                        continue;
                    }
                    $key = 't'.$tcode.',r'.strtolower($m[2]).',c'.strtolower($m[1]);
                    $cellValues[$key] = is_numeric($v) ? ($v + 0) : $v;
                }
            }
        }

        if (! $framework) {
            return response()->json(['ok' => false, 'error' => 'No DPM framework found for this module.'], 422);
        }
        if (empty($cellValues)) {
            return response()->json(['ok' => false, 'error' => 'This instance has no saved values to validate.'], 422);
        }

        $result = $validator->validate($framework, $cellValues, array_keys($codes));
        if (isset($result['error'])) {
            return response()->json(['ok' => false, 'error' => $result['error']], 422);
        }

        $note = ((int) ($result['summary']['total'] ?? 0) === 0)
            ? 'No validation rules are defined for these tables in the active taxonomy ('.$this->_taxonomy->folder.').'
            : null;

        return response()->json([
            'ok' => true,
            'module' => $module->module_name,
            'tables' => count($codes),
            'values' => count($cellValues),
            'summary' => $result['summary'],
            'failed' => $result['failed'],
            'note' => $note,
        ]);
    }

    private function saveModule()
    {

        $mod = $this->getNormalizeModule(\request('module'));
        $entityId = \request('entity_id') ?: null;

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
                    'entity_id' => $entityId,
                    'module_name' => \request('module_name'),
                    'module_path' => $mod,
                    'groups' => json_encode(Session::get('groups'))
                ]);

        elseif ($entityId):
            // Keep the chosen reporting entity on an already-created instance.
            FactModule::where([
                ['period', \request('period')],
                ['module_path', $mod],
                ['taxonomy_id', $this->_taxonomy->id],
            ])->update(['entity_id' => $entityId]);

        endif;


    }

}
