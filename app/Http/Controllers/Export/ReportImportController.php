<?php

namespace App\Http\Controllers\Export;

use App\Http\Controllers\Controller;
use App\Model\Entity;
use App\Model\FactModule;
use App\Model\Taxonomy;
use App\Support\ReportFile;
use App\Support\ReportFiller;
use AReportDpmXBRL\Config\Config;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * Import a report someone already has (xBRL-XML instance or xBRL-CSV package) as a new
 * report: the module, reference date, entity and filing settings come from the file,
 * its facts go into the tables. Filling a large module takes a while (every table is
 * rendered), so it runs after the response; the page polls status() for progress.
 */
class ReportImportController extends Controller
{
    public function store(Request $request)
    {
        $request->validate(['report' => ['required', 'file', 'max:51200']]);
        $upload = $request->file('report');

        $taxonomies = Taxonomy::query()->orderByDesc('active')->orderByDesc('id')->get();
        $found = null;
        $locate = function (string $url) use ($taxonomies, &$found): ?string {
            $rel = ltrim(preg_replace('#^https?://#i', '', $url), '/');
            if ($rel === '' || str_contains($rel, '..')) {
                return null;
            }
            foreach ($taxonomies as $taxonomy) {
                $file = $this->root($taxonomy).$rel;
                if (is_file($file)) {
                    $found = $taxonomy;

                    return $file;
                }
            }

            return null;
        };

        try {
            $report = ReportFile::read($upload->getRealPath(), $upload->getClientOriginalName(), $locate);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['ok' => false, 'error' => $e->getMessage()], 422);
        }

        $modulePath = $locate($report['module']);
        if (! $modulePath || ! $found) {
            return response()->json(['ok' => false, 'error' => 'The module '.$report['module'].' is not in an uploaded taxonomy. Upload that taxonomy release first.'], 422);
        }
        if (! $report['facts']) {
            return response()->json(['ok' => false, 'error' => 'The file has no facts.'], 422);
        }
        $period = $report['period'] && strtotime($report['period']) ? date('Y-m-d', strtotime($report['period'])) : null;
        if (! $period) {
            return response()->json(['ok' => false, 'error' => 'The file has no reference date.'], 422);
        }

        $groups = ReportFiller::groups($modulePath);
        if (! $groups) {
            return response()->json(['ok' => false, 'error' => 'The module has no tables.'], 422);
        }
        $entity = $report['lei'] ? Entity::query()->where('lei', strtoupper($report['lei']))->first() : null;
        $decimals = $report['decimals'];

        $instance = FactModule::query()->create(array_filter([
            'taxonomy_id' => $found->id,
            'period' => $period,
            'module_name' => $this->moduleName($modulePath),
            'module_path' => substr($modulePath, strlen($this->root($found))),
            'groups' => json_encode($groups),
            'entity_id' => $entity?->id,
            'consolidation' => $report['consolidation'] ?: 'CON',
            'base_currency' => $report['currency'],
            'monetary_scale' => ReportFiller::monetaryScale($report['facts'], $found->folder),
            'decimals_monetary' => $decimals['monetary'] ?? null,
            'decimals_percentage' => $decimals['percentage'] ?? null,
            'decimals_integer' => $decimals['integer'] ?? null,
            'decimals_decimal' => $decimals['decimal'] ?? null,
            'filing_indicators' => $report['indicators'] ?: null,
        ], fn ($v) => $v !== null));

        $key = self::key($instance->id);
        $state = ['state' => 'running', 'done' => 0, 'total' => 0, 'format' => $report['format'],
            'entity' => $entity ? $entity->name : null, 'lei' => $report['lei'], 'period' => $period, 'module' => $instance->module_name];
        Cache::put($key, $state, now()->addHour());

        $facts = $report['facts'];
        $folder = $found->folder;
        app()->terminating(function () use ($instance, $facts, $folder, $key, $state) {
            ignore_user_abort(true);
            set_time_limit(0);
            try {
                $result = ReportFiller::fill($instance, $facts, $folder, function (int $done, int $total) use ($key, $state) {
                    Cache::put($key, ['done' => $done, 'total' => $total] + $state, now()->addHour());
                });
                if ($result['mapped'] === 0) {
                    $instance->deleteWithData();
                    Cache::put($key, ['state' => 'failed', 'error' => 'None of the '.$result['facts'].' facts matched a cell of '.$state['module'].'.'] + $state, now()->addHour());

                    return;
                }
                $instance->forceFill(['data_changed_at' => now()])->save();
                Cache::put($key, ['state' => 'done', 'done' => $result['tables'], 'total' => $result['tables'],
                    'facts' => $result['facts'], 'mapped' => $result['mapped'], 'unmapped' => array_slice($result['unmapped'], 0, 20),
                    'unmapped_count' => count($result['unmapped']), 'url' => route('instance.open', ['id' => $instance->id])] + $state, now()->addHour());
            } catch (\Throwable $e) {
                report($e);
                $instance->deleteWithData();
                Cache::put($key, ['state' => 'failed', 'error' => 'The import stopped: '.$e->getMessage()] + $state, now()->addHour());
            }
        });

        return response()->json(['ok' => true, 'id' => $instance->id, 'status' => route('report.import.status', ['instance' => $instance->id])]);
    }

    /** Progress of an import started by this user. */
    public function status(int $instance)
    {
        $state = Cache::get(self::key($instance));
        abort_unless($state && (($state['state'] ?? '') === 'failed' || FactModule::query()->whereKey($instance)->exists()), 404);

        return response()->json($state);
    }

    private static function key(int $instance): string
    {
        return 'report-import:'.auth()->id().':'.$instance;
    }

    private function root(Taxonomy $taxonomy): string
    {
        return rtrim(Config::publicDir(), DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$taxonomy->folder.DIRECTORY_SEPARATOR;
    }

    /** The module's English label (mod/<code>-lab-en.xml), else its code. */
    private function moduleName(string $modulePath): string
    {
        $lab = preg_replace('/\.xsd$/', '-lab-en.xml', $modulePath);
        if (is_file($lab) && preg_match('#<link:label[^>]*>([^<]+)</link:label>#', (string) file_get_contents($lab), $m)) {
            return html_entity_decode(trim($m[1]));
        }

        return strtoupper(pathinfo($modulePath, PATHINFO_FILENAME));
    }
}
