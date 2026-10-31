<?php

namespace App\Http\Controllers\Export;

use App\Http\Controllers\Controller;
use App\Model\FactHeader;
use App\Model\FactModule;
use App\Model\Taxonomy;
use AReportDpmXBRL\Creat\CreateXBRLCsvPackage;
use AReportDpmXBRL\Creat\CreateXBRLFromDB;
use AReportDpmXBRL\Library\Format;


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
        $module = $fact_module->module_path;
        $period = $fact_module->periodYmd();

        $this->_entityId = $fact_module->entity_id ?? null;

        $find = [];

        foreach ($fact_module->factHeader as $row):
            $find[] = $row->table_path;
        endforeach;

        $data = FactHeader::prepareDataForXbrl($fact_module->id, $period);


        $instance =
            new CreateXBRLFromDB($period, $this->reportingLei(), $module, $data, $find, $fact_module->taxonomy->folder);
        $file = $instance->writeXbrl();

        return response()->streamDownload(function () use ($file) {
            echo $file;
        }, 'areport_'.$this->fileSlug().'_'.date("dmYHis").'.xbrl', ['Content-Type' => 'text/xml']);


    }

    public function exportCsvInstance()
    {
        $fact_module = $this->instance(['taxonomy', 'factHeader.factTable']);
        $module = $fact_module->module_path;
        $period = $fact_module->periodYmd();

        $this->_entityId = $fact_module->entity_id ?? null;

        $data = FactHeader::prepareDataForXbrlCsv($fact_module->id, $period);

        $instance = new CreateXBRLCsvPackage(
            $period,
            $this->reportingLei(),
            $module,
            $data,
            $fact_module->taxonomy->folder
        );

        $file = $instance->writePackage();

        return response()->download(
            $file['path'],
            'areport_'.$this->fileSlug().'_'.date('dmYHis').'.zip',
            ['Content-Type' => 'application/zip']
        )->deleteFileAfterSend(true);
    }
}
