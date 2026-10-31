<?php

namespace App\Http\Controllers\Export;

use App\Http\Controllers\Controller;
use App\Model\FactHeader;
use App\Model\FactModule;
use App\Model\Taxonomy;
use App\Support\FilingPreflight;
use AReportDpmXBRL\Creat\CreateXBRLCsvPackage;
use AReportDpmXBRL\Creat\CreateXBRLFromDB;
use AReportDpmXBRL\Library\Format;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;


class InstanceController extends Controller
{
    private $_entityId = null;
    private $_taxonomy;

    public function __construct()
    {
        $this->_taxonomy = Taxonomy::query()->where('active', true)->first();
    }

    private function json_decode_array($input)
    {
        $arr = [];
        $from_json = json_decode($input, true);
        foreach ($from_json as $row):
            $arr[] = $row;
        endforeach;
        return $arr;
    }


    private function array_flatten($array)
    {
        if (!is_array($array)) {
            return FALSE;
        }
        $result = array();
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $result = array_merge($result, $this->array_flatten($value));
            } else {
                $result[$key] = $value;
            }
        }
        return $result;
    }


    /**
     * The instance to export: by instance_id (the signed-in user's own), else the legacy
     * period + module lookup in the active taxonomy. 404 when neither matches.
     */
    private function instance(array $with): FactModule
    {
        if (request()->filled('instance_id')) {
            $fact_module = FactModule::query()->with($with)->find(request('instance_id'));
        } else {
            $fact_module = FactModule::where([
                ['period', '=', request('period')],
                ['module_path', '=', $this->getNormalizeModule(request('mod'))],
                ['taxonomy_id', '=', $this->_taxonomy->id],
            ])->with($with)->first();
        }

        abort_unless($fact_module, 404, 'Instance not found for export.');

        return $fact_module;
    }

    private function getNormalizeModule($module)
    {
        if (strpos($module, $this->_taxonomy->folder) !== false):
            return Format::getAfterSpecChar($module, $this->_taxonomy->folder, strlen($this->_taxonomy->folder) + 1);
        endif;
        return $module;
    }

    /**
     * The reporting entity for this export: the one chosen on the instance
     * (request 'entity') if given, else the default entity, else the legacy
     * single LEI setting. Returns ['lei' => …, 'name' => …].
     *
     * @return array{lei:string,name:?string}
     */
    private function currentEntity(): array
    {
        $entityCls = \Begicf\AReportPro\Models\DpmEntity::class;
        if (class_exists($entityCls)) {
            $id = $this->_entityId ?: request('entity');
            $e = $id ? $entityCls::find($id) : $entityCls::current();
            if ($e && $e->lei) {
                return ['lei' => $e->lei, 'name' => $e->name];
            }
        }

        $settingCls = \Begicf\AReportPro\Models\DpmSetting::class;
        $lei = class_exists($settingCls) ? ($settingCls::get('lei') ?: null) : null;
        $name = class_exists($settingCls) ? ($settingCls::get('entity_name') ?: null) : null;

        return ['lei' => $lei ?: env('LEI_CODE', '12345678912345678912'), 'name' => $name];
    }

    private function reportingLei(): string
    {
        return $this->currentEntity()['lei'];
    }

    /**
     * A filename slug from the entity name — EBA instances carry only the LEI,
     * so the readable entity name is surfaced in the download filename instead.
     */
    private function fileSlug(): string
    {
        $entity = $this->currentEntity();
        $base = $entity['name'] ?: $entity['lei'];
        $slug = trim(preg_replace('/[^A-Za-z0-9]+/', '_', (string) $base), '_');

        return substr($slug, 0, 40) ?: 'instance';
    }

    public function exportInstance()
    {
        $fact_module = $this->instance(['taxonomy', 'factHeader']);

        $this->_entityId = $fact_module->entity_id ?? null;
        $preflight = $this->guard($fact_module, 'xml');

        $file = $this->xbrlXml($fact_module);
        $filename = $preflight['package'] ?: 'areport_'.$this->fileSlug().'_'.date("dmYHis").'.xbrl';
        $fact_module->stamp(['exported_at' => now(), 'export_format' => 'xml', 'export_package' => $filename]);

        return response()->streamDownload(function () use ($file) {
            echo $file;
        }, $filename, ['Content-Type' => 'text/xml']);


    }

    /** The instance as an xBRL-XML document. */
    private function xbrlXml(FactModule $fact_module): string
    {
        $period = $fact_module->periodYmd();
        $find = $fact_module->factHeader->pluck('table_path')->all();
        $data = FactHeader::prepareDataForXbrl($fact_module->id, $period);
        $instance = new CreateXBRLFromDB($period, $fact_module->entityIdentifier($this->reportingLei()), $fact_module->module_path, $data, $find, $fact_module->taxonomy->folder);

        return $instance->setOptions($fact_module->exportOptions())->writeXbrl();
    }

    /** The instance as an xBRL-CSV report package: ['path' => zip, 'filename' => …]. */
    private function xbrlCsvPackage(FactModule $fact_module): array
    {
        $period = $fact_module->periodYmd();
        $data = FactHeader::prepareDataForXbrlCsv($fact_module->id, $period);
        $instance = new CreateXBRLCsvPackage($period, $fact_module->entityIdentifier($this->reportingLei()), $fact_module->module_path, $data, $fact_module->taxonomy->folder);

        try {
            return $instance->setOptions($fact_module->exportOptions())->writePackage();
        } catch (\RuntimeException $e) {
            abort(422, $e->getMessage());   // e.g. "No table facts available" — not a server error
        }
    }

    /**
     * Validate the instance as it would be exported (xBRL-XML or xBRL-CSV) with Arelle,
     * offline against the local taxonomy: XBRL 2.1, dimensions, data types, units…
     */
    public function arelleValidate(Request $request)
    {
        $cls = \Begicf\AReportPro\Services\ArelleValidator::class;
        abort_unless(class_exists($cls) && $cls::available(), 422, 'Arelle is not configured (AREPORT_PRO_ARELLE_CMD).');
        $format = $request->get('format') === 'xml' ? 'xml' : 'csv';
        $fact_module = $this->instance($format === 'xml' ? ['taxonomy', 'factHeader'] : ['taxonomy', 'factHeader.factTable']);
        $this->_entityId = $fact_module->entity_id ?? null;

        if ($format === 'xml') {
            $path = tempnam(sys_get_temp_dir(), 'areport').'.xbrl';
            file_put_contents($path, $this->xbrlXml($fact_module));
        } else {
            // Arelle recognises a report package by its .zip name.
            $package = $this->xbrlCsvPackage($fact_module);
            $path = sys_get_temp_dir().'/'.uniqid('areport_').'_'.basename($package['filename']);
            rename($package['path'], $path);
        }
        try {
            $result = (new $cls($fact_module->taxonomy))->validate($path);
        } catch (\RuntimeException $e) {
            abort(422, $e->getMessage());
        } finally {
            @unlink($path);
        }
        // Temp file names mean nothing to the user.
        foreach ($result['messages'] as &$m) {
            if ($m['ref'] && str_starts_with((string) $m['ref'], sys_get_temp_dir())) {
                $m['ref'] = null;
            }
        }

        return response()->json($result + ['format' => $format, 'messages' => array_slice($result['messages'], 0, 300)]);
    }

    public function exportCsvInstance()
    {
        $fact_module = $this->instance(['taxonomy', 'factHeader.factTable']);

        $this->_entityId = $fact_module->entity_id ?? null;
        $this->guard($fact_module, 'csv');

        $file = $this->xbrlCsvPackage($fact_module);
        $fact_module->stamp(['exported_at' => now(), 'export_format' => 'csv', 'export_package' => $file['filename']]);

        // The archive is named like the package folder inside it (EBA convention).
        return response()->download(
            $file['path'],
            $file['filename'],
            ['Content-Type' => 'application/zip']
        )->deleteFileAfterSend(true);
    }

    /**
     * Run the pre-export checks; errors stop the download (the form runs them first
     * and shows them, so this is the safety net). Returns the check result.
     */
    private function guard(FactModule $instance, string $format): array
    {
        $result = (new FilingPreflight($instance))->run($format);

        if (! $result['ok'] && ! request()->boolean('force')) {
            $errors = collect($result['checks'])->where('level', 'error')->pluck('message')->implode("\n");
            abort(422, "The instance did not pass the pre-export checks:\n".$errors);
        }

        return $result;
    }

    /** The signed-in user's reporting entities (areport-pro), or none without the plugin. */
    private function ownedEntities()
    {
        $entityCls = \Begicf\AReportPro\Models\DpmEntity::class;

        return class_exists($entityCls) ? $entityCls::query()->orderByDesc('is_default')->orderBy('name')->get() : collect();
    }

    /** Pre-export checks as JSON, for the form's export dialog. */
    public function preflight(Request $request)
    {
        $instance = $this->instance(['taxonomy']);

        return response()->json((new FilingPreflight($instance))->run($request->get('format') === 'xml' ? 'xml' : 'csv'));
    }

    /** Filing settings and filing indicators of an instance. */
    public function settings()
    {
        $instance = $this->instance(['taxonomy']);
        $preflight = new FilingPreflight($instance);
        $info = $preflight->moduleInfo();
        $entity = $instance->entityRecord();

        return response()->json([
            'settings' => [
                'consolidation' => $instance->consolidation,
                'country' => $instance->country,
                'country_default' => $entity->country ?? null,
                'base_currency' => $instance->base_currency,
                'monetary_scale' => $instance->monetary_scale,
                'decimals_monetary' => $instance->decimals_monetary,
                'decimals_percentage' => $instance->decimals_percentage,
                'decimals_integer' => $instance->decimals_integer,
                'decimals_decimal' => $instance->decimals_decimal,
            ],
            'entity' => $entity ? ['id' => $entity->id, 'name' => $entity->name, 'lei' => $entity->lei] : null,
            'entities' => $this->ownedEntities()->map(fn ($e) => ['id' => $e->id, 'name' => $e->name, 'lei' => $e->lei, 'country' => $e->country])->values(),
            'reference_date' => $instance->periodYmd(),
            'module' => $info ? array_intersect_key($info, array_flip(['version', 'from', 'to', 'framework', 'module'])) : null,
            'scales' => FactModule::MONETARY_SCALES,
            'templates' => $preflight->templates(),
        ]);
    }

    public function updateSettings(Request $request)
    {
        $instance = $this->instance([]);

        $data = $request->validate([
            'entity_id' => ['nullable', 'integer', Rule::in($this->ownedEntities()->pluck('id')->all())],
            'period' => ['required', 'date'],
            'consolidation' => ['required', Rule::in(['CON', 'IND'])],
            'country' => ['nullable', 'regex:/^[A-Za-z]{2}$/'],
            'base_currency' => ['required', 'regex:/^[A-Za-z]{3}$/'],
            'monetary_scale' => ['required', Rule::in(array_keys(FactModule::MONETARY_SCALES))],
            'decimals_monetary' => ['required', 'integer', 'between:-9,9'],
            'decimals_percentage' => ['required', 'integer', 'between:0,12'],
            'decimals_integer' => ['required', 'integer', 'between:0,9'],
            'decimals_decimal' => ['required', 'integer', 'between:0,12'],
            'filing_indicators' => ['nullable', 'array'],
            'filing_indicators.*' => ['nullable', Rule::in(['auto', 'true', 'false', true, false])],
        ]);

        // Only explicit choices are stored; "auto" means "reported when it has data".
        $choices = [];
        foreach ((array) ($data['filing_indicators'] ?? []) as $code => $choice) {
            if ($choice === 'auto' || $choice === null) {
                continue;
            }
            $choices[(string) $code] = filter_var($choice, FILTER_VALIDATE_BOOLEAN);
        }

        $instance->update([
            'data_changed_at' => now(),
            'entity_id' => $data['entity_id'] ?? null,
            'period' => \Carbon\Carbon::parse($data['period'])->format('Y-m-d'),
            'consolidation' => $data['consolidation'],
            'country' => $data['country'] ? strtoupper($data['country']) : null,
            'base_currency' => strtoupper($data['base_currency']),
            'monetary_scale' => (int) $data['monetary_scale'],
            'decimals_monetary' => (int) $data['decimals_monetary'],
            'decimals_percentage' => (int) $data['decimals_percentage'],
            'decimals_integer' => (int) $data['decimals_integer'],
            'decimals_decimal' => (int) $data['decimals_decimal'],
            'filing_indicators' => $choices ?: null,
        ]);

        return response()->json(['ok' => true, 'amount_unit' => $instance->amountUnitLabel()]);
    }
}
