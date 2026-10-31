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
use Illuminate\Support\Facades\DB;


class TableController extends Controller
{


    private $_period;
    private $_taxonomy;
    private ?FactModule $_instance = null;

    public function __construct()
    {
        $this->_taxonomy = Taxonomy::query()->where('active', true)->first();

        $this->_period = Carbon::parse(request('period'))->format('Y-m-d');


    }

    /**
     * Bind the instance named by the request's instance_id. Scoped to the signed-in
     * user (FactModule's owner scope), so another user's id is a 404. The instance's
     * own taxonomy and reference date win over the active taxonomy and the request.
     * Called from the actions, not the constructor: auth has not run there yet.
     */
    private function bindInstance(): ?FactModule
    {
        if ($this->_instance === null && request()->filled('instance_id')) {
            $instance = FactModule::fromRequest();
            abort_unless($instance, 404, 'Instance not found.');

            $this->_instance = $instance;
            if ($instance->taxonomy) {
                $this->_taxonomy = $instance->taxonomy;
            }
            $this->_period = $instance->periodYmd();
        }

        return $this->_instance;
    }

    /** The chosen entity id if it is one of the user's entities (areport-pro), else null. */
    private function ownedEntityId($id): ?int
    {
        $entityCls = \Begicf\AReportPro\Models\DpmEntity::class;
        if (! $id || ! class_exists($entityCls)) {
            return null;
        }

        return $entityCls::query()->whereKey($id)->value('id');
    }

    public function table(Request $request)
    {


        if ($request->get('view_home')):

            // Any of the user's instances opens in its own taxonomy, active or not.
            $fact_module = FactModule::query()->with('taxonomy')->findOrFail($request->get('id'));
            $_groups = json_decode($fact_module->groups, true) ?: [];

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

            // EBA reference dates are period ends; refuse anything else up front.
            $refDate = Carbon::parse($this->_period);
            if (! $refDate->isSameDay($refDate->copy()->endOfMonth())) {
                return redirect('/modules')->with('warning', 'The reference date '.$refDate->format('d.m.Y').' is not a month end. Choose a period end such as '.$refDate->copy()->endOfMonth()->format('d.m.Y').'.');
            }

            // The instance exists from the moment it is opened, so every later call
            // (save, render, validate, export) addresses it by id. Same module, date,
            // entity and consolidation scope in the same taxonomy reopens it with the
            // new table selection; solo (IND) and consolidated (CON) are separate reports.
            $fact_module = FactModule::updateOrCreate(
                [
                    'taxonomy_id' => $this->_taxonomy->id,
                    'period' => $this->_period,
                    'module_path' => $this->getNormalizeModule($request->get('module_path')),
                    'entity_id' => $this->ownedEntityId($request->get('entity_id')),
                    'consolidation' => $request->get('consolidation') === 'IND' ? 'IND' : 'CON',
                ],
                [
                    'module_name' => $request->get('module_name'),
                    'groups' => json_encode($_groups),
                ]
            );
            $fact_module->load('taxonomy');

        else:

            return redirect('/home')->with('warning', 'Please choose a table group.');

        endif;

        $entityCls = \Begicf\AReportPro\Models\DpmEntity::class;
        $entity = ($fact_module->entity_id && class_exists($entityCls)) ? $entityCls::find($fact_module->entity_id) : null;

        return view('table.table', [

            'groups' => $_groups,
            'instance_id' => $fact_module->id,
            'period' => $fact_module->periodYmd(),
            'mod' => preg_replace('/\\\\/', '\\\\\\\\', $request->get('view_home') ? $fact_module->module_path : $request->get('module_path')),
            'module_name' => $fact_module->module_name,
            'entity_id' => $fact_module->entity_id,
            'entity' => $entity,
            'taxonomy_name' => $fact_module->taxonomy->name ?? '',
            'instance' => $fact_module,
            'last_saved' => $fact_module->updated_at,
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


        $this->bindInstance();

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
                $this->_taxonomy->id,
                $this->bindInstance()?->id
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
        $this->bindInstance();

        $tax = Data::getTax($request->get('table'));

        $import = FactHeader::getCRData(
            $this->getNormalizeTable($this->getNormalizeTable($request->get('table'))),
            $this->_period,
            $this->getNormalizeModule($this->getNormalizeModule($request->get('mod'))),
            null,
            true,
            $this->_taxonomy->id,
            $this->_instance?->id
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

    /**
     * Save the open table sheet of an instance. The form is the full state of the
     * sheet: changed values are written, cleared ones removed, each change audited.
     * Answers JSON so the form can show "Saved 10:22" or a real error.
     */
    public function saveTable(Request $request)
    {
        $instance = $this->bindInstance();

        if (! $instance) {
            return response()->json(['ok' => false, 'error' => 'Instance not found. Reopen it from Home.'], 404);
        }

        $changes = 0;

        if ($request->get('table_data') && $request->get('tab')):

            $cr_sheet = '000';
            if (!empty($request->get('sheet'))):
                $cr_sheet = (json_decode($request->get('sheet'), true))['sheet'] ?? '000';
            endif;

            $tab = $this->getNormalizeTable($request->get('tab'));

            $changes = DB::transaction(function () use ($instance, $tab, $cr_sheet, $request) {
                $fact_header = FactHeader::updateOrCreate(
                    [
                        'module_id' => $instance->id,
                        'table_path' => $tab,
                    ],
                    [
                        'module_id' => $instance->id,
                        'table_path' => $tab,
                        'cr_sheet_code_last' => $cr_sheet,
                    ]
                );

                return FactTable::storeInstance($request->get('table_data'), $fact_header->id, $request->get('sheet'), $fact_header);
            });

            if ($changes > 0) {
                $instance->forceFill(['data_changed_at' => now()])->save();
            }
        endif;

        return response()->json([
            'ok' => true,
            'changes' => $changes,
            'saved_at' => now()->format('H:i'),
        ]);
    }

    /**
     * Validate the reporting instance the user is entering: take the current form's
     * cell values, run this table's DPM-XL rules (via the shared InstanceValidator),
     * and return the failing cells so the form can highlight exactly what to fix.
     */
    public function validateInstance(Request $request)
    {
        $this->bindInstance();

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
        $form = \App\Support\FormQuery::parse((string) $request->get('table_data'));
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

        $context = $this->validationContext($this->_instance, [$table->code]);
        $result = $validator->validate($table->framework, $cellValues, [$table->code], $context);
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
     * The instance's module (for per-module rule applicability and severity) and
     * its filing indicators (for rule preconditions): a template counts as reported
     * when chosen so, or by default when it holds data. $openTables are tables on
     * the form right now, reported even before they are saved.
     */
    private function validationContext(?FactModule $instance, array $openTables = []): ?array
    {
        $validatorClass = \Begicf\AReportPro\Services\InstanceValidator::class;
        $context = $instance ? $validatorClass::moduleContext($instance->module_path) : null;
        if (! $context) {
            return null;
        }

        $indicators = [];
        foreach ((new \App\Support\FilingPreflight($instance))->templates() as $code => $t) {
            $indicators[$code] = $t['choice'] ?? $t['has_data'];
        }
        foreach ($openTables as $table) {
            $code = strtoupper(preg_replace('/\.[a-z]$/i', '', (string) $table));
            $indicators[$code] = $indicators[$code] ?? true;
        }
        $context['indicators'] = $indicators;

        // Rules straight from the EBA DPM 2.0 database when one defines this module version.
        $moduleVid = \AReportDpmXBRL\Library\ModuleInfo::load((string) ($instance->taxonomy->folder ?? ''), (string) $instance->module_path)['module_vid'] ?? null;
        $database = $validatorClass::dpm2DatabaseFor($moduleVid);
        if ($database) {
            $context['dpm2'] = ['database' => $database, 'module_vid' => $moduleVid];
        }

        // The owner's own module (generated in the DPM Architect): its authored rules apply too.
        if (method_exists($validatorClass, 'ownModuleFor') && ($own = $validatorClass::ownModuleFor($instance->module_path))) {
            $context['own_module_id'] = $own->id;
        }

        return $context;
    }

    /** Build t<table>,r,c → value for a whole saved module + resolve its framework/codes. */
    private function moduleCellValues(FactModule $module): array
    {
        $tableModel = \Begicf\AReportPro\Models\Dpm\DpmTable::class;
        $cellValues = [];
        $tolerance = [];
        $dims = [];
        $codes = [];
        // The framework named by the module path (…/fws/corep/…) in the instance's own
        // taxonomy — the one its rules were imported under.
        $frameworkCode = \AReportDpmXBRL\Library\ModuleInfo::frameworkCode((string) $module->module_path);
        $framework = $frameworkCode
            ? \Begicf\AReportPro\Models\Dpm\Framework::query()->where('taxonomy_id', $module->taxonomy_id)->where('code', $frameworkCode)->first()
            : null;

        foreach ($module->factHeader as $header) {
            $tcode = strtolower(pathinfo((string) $header->table_path, PATHINFO_FILENAME));
            if ($tcode === '') {
                continue;
            }
            $codes[$tcode] = true;
            if (! $framework) {
                // Prefer the active taxonomy, else any taxonomy defining this table
                // (that's where its rules live).
                $t = $tableModel::query()->whereRaw('LOWER(code) = ?', [$tcode])
                    ->orderByRaw('CASE WHEN taxonomy_id = ? THEN 0 ELSE 1 END', [$this->_taxonomy->id])
                    ->first();
                $framework = $t?->framework;
            }
            // Values as reported (amounts × the instance's unit, percentages as ratios), with
            // the half-width of their rounding for interval arithmetic (0.5·10^-decimals).
            foreach ($header->factTable()->get(['cr_code', 'cr_sheet_code', 'string_value', 'metric', 'xbrl_context_key_raw']) as $fact) {
                if (! preg_match('/^c([^r]+)r(.+)$/i', (string) $fact->cr_code, $m)) {
                    continue;
                }
                $v = $fact->string_value;
                if ($v === '' || $v === null) {
                    continue;
                }
                $key = 't'.$tcode.',r'.strtolower($m[2]).',c'.strtolower($m[1]);
                // A z-axis sheet adds a component (s0010, or the sheet member's code).
                $sheet = (string) $fact->cr_sheet_code;
                if ($sheet !== '' && $sheet !== '000') {
                    $key .= ',s'.strtolower(preg_replace('/[^A-Za-z0-9.]/', '', $sheet));
                }
                // The data point's dimensions by code (CEG, qEEA, …) for [where …] / group by.
                $raw = json_decode((string) $fact->xbrl_context_key_raw, true);
                if (is_array($raw)) {
                    foreach ($raw as $dim => $member) {
                        if (is_string($member) && ! in_array($dim, ['metric', 'typ', 'sheet', 'order'], true) && str_contains($dim, ':')) {
                            $dims[$key][substr($dim, strrpos($dim, ':') + 1)] = $member;
                        }
                    }
                }
                $kind = is_numeric($v) ? \AReportDpmXBRL\Library\MetricTypes::kind((string) ($module->taxonomy->folder ?? ''), $fact->metric) : null;
                $scaled = $kind ? \AReportDpmXBRL\Library\FactValue::forKind($kind, (string) $v, (int) ($module->monetary_scale ?: 1000)) : $v;
                $cellValues[$key] = is_numeric($scaled) ? ($scaled + 0) : $scaled;
                $decimals = ['monetary' => $module->decimals_monetary ?? -3, 'percentage' => $module->decimals_percentage ?? 4,
                    'integer' => $module->decimals_integer ?? 0, 'decimal' => $module->decimals_decimal ?? 2][$kind] ?? null;
                if ($decimals !== null) {
                    $tolerance[$key] = 0.5 * (10 ** (-$decimals));
                }
            }
        }

        return ['cells' => $cellValues, 'codes' => array_keys($codes), 'framework' => $framework, 'tolerance' => $tolerance, 'dims' => $dims];
    }

    /**
     * Full validation report for a whole saved module instance — a dedicated page
     * listing every rule (passed / failed / indeterminate / unsupported) and the
     * cross-table checks, for the filer. Requires the areport-pro plugin + DPM-XL.
     */
    public function validationReport(Request $request)
    {
        $validatorClass = \Begicf\AReportPro\Services\InstanceValidator::class;
        if (! class_exists($validatorClass)) {
            abort(404, 'The validation plugin is not installed.');
        }

        $module = FactModule::query()
            ->when($request->get('id'), fn ($q) => $q->where('id', $request->get('id')))
            ->when(! $request->get('id') && $request->get('module_path'), fn ($q) => $q->where('module_path', $request->get('module_path'))->where('taxonomy_id', $this->_taxonomy->id))
            ->with(['factHeader', 'taxonomy'])
            ->first();

        abort_unless($module, 404, 'Instance not found.');
        if ($module->taxonomy) {
            $this->_taxonomy = $module->taxonomy;
        }

        /** @var \Begicf\AReportPro\Services\InstanceValidator $validator */
        $validator = app($validatorClass);

        $built = $this->moduleCellValues($module);
        $entityCls = \Begicf\AReportPro\Models\DpmEntity::class;
        $entity = ($module->entity_id && class_exists($entityCls)) ? $entityCls::find($module->entity_id) : null;

        $meta = [
            'module' => $module->module_name,
            'period' => $module->periodYmd(),
            'period_label' => $module->period ? $module->period->format('d.m.Y') : '',
            'taxonomy' => $this->_taxonomy->name,
            'entity' => $entity?->name,
            'lei' => $entity?->lei,
            'tables' => count($built['codes']),
            'values' => count($built['cells']),
            'generated' => now()->format('d M Y, H:i'),
        ];

        $report = ['summary' => [], 'rules' => []];
        $error = null;
        if (! $validator->available()) {
            $error = 'DPM-XL service is offline — start it to run validation.';
        } elseif (empty($built['cells'])) {
            $error = 'This instance has no saved values to validate.';
        } else {
            $context = $this->validationContext($module);
            if ($context) {
                $context['tolerance'] = $built['tolerance'];
                $context['dims'] = $built['dims'];
            }
            // DPM 2.0 database rules and the owner's own rules need no imported framework; the legacy rules do.
            $framework = $built['framework'] ?? ((! empty($context['dpm2']) || ! empty($context['own_module_id'])) ? new \Begicf\AReportPro\Models\Dpm\Framework() : null);
            if (! $framework) {
                $error = 'No DPM framework found for this module.';
            } else {
                $report = $validator->report($framework, $built['cells'], $built['codes'], $context);
                if (isset($report['error'])) {
                    $error = $report['error'];
                } else {
                    $module->stamp(['validated_at' => now(), 'validation_summary' => $report['summary']]);
                }
                $meta['rules_source'] = ! empty($context['dpm2']) ? 'EBA DPM 2.0 database '.$context['dpm2']['database']->label : (! empty($context['own_module_id']) ? 'own rules (DPM Architect Validations)' : 'taxonomy validation linkbases');
                if (! empty($context['dpm2']) && ! empty($context['own_module_id'])) {
                    $meta['rules_source'] .= ' + own rules';
                }
            }
        }

        if ($request->get('format') === 'csv' && ! $error) {
            return $this->validationReportCsv($meta, $report);
        }

        $meta['id'] = $module->id;

        return view('table.validation-report', compact('meta', 'report', 'error'));
    }

    /** The validation report as CSV, for the audit file. */
    private function validationReportCsv(array $meta, array $report)
    {
        $name = 'validation_'.preg_replace('/[^A-Za-z0-9]+/', '_', (string) $meta['module']).'_'.$meta['period'].'.csv';

        return response()->streamDownload(function () use ($meta, $report) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Module', $meta['module'], 'Entity', $meta['entity'], 'LEI', $meta['lei'], 'Reference date', $meta['period'], 'Generated', $meta['generated']]);
            fputcsv($out, []);
            fputcsv($out, ['Rule', 'Severity', 'Status', 'Tables', 'Rule text', 'Cells and values', 'Precondition', 'Note']);
            foreach ($report['rules'] ?? [] as $r) {
                $facts = collect($r['facts'])->map(fn ($f) => $f['cell'].' = '.($f['value'] ?? '(empty)'))->implode('; ');
                fputcsv($out, [$r['code'], $r['severity'], $r['status'], implode(' ', $r['tables']), $r['rule'], $facts, $r['precondition'] ?? '', $r['error'] ?? '']);
            }
            fclose($out);
        }, $name, ['Content-Type' => 'text/csv']);
    }

    /**
     * Open an instance on a given table and cell (links from the validation
     * report): GET /table/open?id=…&table=C_01.00&cell=c0010r0010.
     */
    public function open(Request $request)
    {
        $request->merge(['view_home' => true]);
        $response = $this->table($request);

        if ($response instanceof \Illuminate\View\View) {
            $response->with('focus', [
                'table' => strtolower((string) $request->get('table')),
                'cell' => strtolower((string) $request->get('cell')),
            ]);
        }

        return $response;
    }

    /**
     * The instance's table groups for the form's table list: which hold data and
     * how many blocking errors the last full validation found in each.
     */
    public function navigator(Request $request)
    {
        $instance = $this->bindInstance();
        abort_unless($instance, 404);

        $withData = $instance->factHeader()->withCount('factTable')->get()
            ->filter(fn ($h) => $h->fact_table_count > 0)
            ->map(fn ($h) => strtolower(pathinfo((string) $h->table_path, PATHINFO_FILENAME)))
            ->flip();
        $errors = collect((array) ($instance->validation_summary['tables'] ?? []))
            ->mapWithKeys(fn ($n, $code) => [strtolower(str_replace(' ', '_', $code)) => $n]);
        $validatedCurrent = $instance->status()['key'] !== 'draft';

        $items = [];
        foreach ((array) json_decode((string) $instance->groups, true) as $name => $tablesJson) {
            // A group is {"C_01.00": {"eba_tC_01.00": "/…/c_01.00.xsd", …}}: collect the paths.
            $paths = [];
            $decoded = (array) json_decode((string) $tablesJson, true);
            array_walk_recursive($decoded, function ($path) use (&$paths) {
                $paths[] = $path;
            });
            $tables = array_map(fn ($path) => strtolower(pathinfo((string) $path, PATHINFO_FILENAME)), $paths);
            $items[] = [
                'name' => $name,
                'tables' => $tables,
                'has_data' => collect($tables)->contains(fn ($t) => $withData->has($t)),
                'errors' => $validatedCurrent ? collect($tables)->sum(fn ($t) => $errors[$t] ?? 0) : null,
            ];
        }

        return response()->json(['items' => $items, 'validated' => $validatedCurrent]);
    }

    /** Cell-level change history of one instance, newest first. */
    public function history(Request $request)
    {
        $module = FactModule::query()->with('taxonomy')->findOrFail($request->get('id'));

        $audits = $module->audits()->with('user:id,name,email')
            ->orderByDesc('created_at')->orderByDesc('id')
            ->paginate(100)->withQueryString();

        return view('table.history', compact('module', 'audits'));
    }

}
