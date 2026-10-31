<?php

namespace App\Http\Controllers\Areport;

use App\Http\Controllers\Controller;
use App\Model\FactModule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {

        return view('areport.home');
    }

    public function json(Request $request)
    {
        // Every instance the user owns, whatever taxonomy is active: an instance opens
        // in its own taxonomy, so switching the active one never hides work.
        $data = FactModule::query()->with('taxonomy:id,name,active');

        $search = trim((string) $request->get('search'));
        if ($search !== '') {
            $search = '%' . mb_strtolower($search) . '%';

            $data->where(function ($query) use ($search) {
                $query->whereRaw('LOWER(module_name) LIKE ?', [$search])
                    ->orWhereRaw('LOWER(module_path) LIKE ?', [$search])
                    ->orWhereRaw('CAST(period AS TEXT) LIKE ?', [$search]);
            });
        }

        $instances = $data->get();

        $entityCls = \Begicf\AReportPro\Models\DpmEntity::class;
        $entities = class_exists($entityCls)
            ? $entityCls::query()->whereIn('id', $instances->pluck('entity_id')->filter()->unique())->get(['id', 'name', 'lei'])->keyBy('id')
            : collect();

        // Who made the latest cell change on each instance.
        $lastEditors = DB::table('fact_audit as a')
            ->join(DB::raw('(select fact_module_id, max(id) as id from fact_audit group by fact_module_id) as last'), 'last.id', '=', 'a.id')
            ->leftJoin('users as u', 'u.id', '=', 'a.user_id')
            ->whereIn('a.fact_module_id', $instances->pluck('id'))
            ->pluck('u.name', 'a.fact_module_id');

        $rows = $instances->map(function (FactModule $m) use ($entities, $lastEditors) {
            $entity = $entities->get($m->entity_id);
            $status = $m->status();
            $changed = $m->data_changed_at ?? $m->updated_at;

            return [
                'id' => $m->id,
                'period' => $m->period ? $m->period->format('Y-m-d') : null,
                'period_label' => $m->period ? $m->period->format('d.m.Y') : null,
                'module_name' => $m->module_name,
                'module_path' => $m->module_path,
                'consolidation' => $m->consolidation,
                'taxonomy_name' => $m->taxonomy->name ?? null,
                'taxonomy_active' => (bool) ($m->taxonomy->active ?? false),
                'entity_name' => $entity->name ?? null,
                'entity_lei' => $entity->lei ?? null,
                'status' => $status['key'],
                'status_label' => $status['label'],
                'changed_at' => $changed ? $changed->format('Y-m-d H:i:s') : null,
                'changed_label' => $changed ? $changed->format('d.m.Y H:i') : null,
                'changed_by' => $lastEditors[$m->id] ?? null,
            ];
        });

        // Counts per status over everything the search matches (the tiles).
        $stats = ['all' => $rows->count()] + $rows->countBy('status')->all();

        $status = (string) $request->get('status');
        if ($status === 'ready') {
            $rows = $rows->whereIn('status', ['validated', 'warnings']);   // validated, with or without warnings
        } elseif ($status !== '' && $status !== 'all') {
            $rows = $rows->where('status', $status);
        }

        $allowedSorts = ['period', 'module_name', 'changed_at', 'status'];
        $sort = in_array($request->get('sort'), $allowedSorts, true) ? $request->get('sort') : 'changed_at';
        $rows = $rows->sortBy($sort, SORT_NATURAL | SORT_FLAG_CASE, $request->get('order') !== 'asc')->values();

        $offset = max((int) $request->get('offset', 0), 0);
        $limit = max((int) $request->get('limit', 15), 1);

        return response()->json([
            'all_total' => $stats['all'],
            'total' => $rows->count(),
            'stats' => $stats,
            'rows' => $rows->slice($offset, $limit)->values(),
        ]);
    }
}
