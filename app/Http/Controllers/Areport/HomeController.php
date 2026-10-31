<?php

namespace App\Http\Controllers\Areport;

use App\Http\Controllers\Controller;
use App\Model\FactModule;
use Illuminate\Http\Request;

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
        $data = FactModule::query();

        $allTotal = (clone $data)->count();

        $search = trim((string) $request->get('search'));
        if ($search !== '') {
            $search = '%' . mb_strtolower($search) . '%';

            $data->where(function ($query) use ($search) {
                $query->whereRaw('LOWER(module_name) LIKE ?', [$search])
                    ->orWhereRaw('LOWER(module_path) LIKE ?', [$search])
                    ->orWhereRaw('CAST(period AS TEXT) LIKE ?', [$search]);
            });
        }

        $allowedSorts = ['period', 'module_name', 'module_path'];
        $sort = in_array($request->get('sort'), $allowedSorts, true) ? $request->get('sort') : 'period';
        $order = $request->get('order') === 'asc' ? 'asc' : 'desc';
        $offset = max((int) $request->get('offset', 0), 0);
        $limit = max((int) $request->get('limit', 15), 1);

        $filteredTotal = (clone $data)->count();

        $rows = $data->with('taxonomy:id,name,active')
            ->orderBy($sort, $order)
            ->offset($offset)
            ->limit($limit)
            ->get();

        $entityCls = \Begicf\AReportPro\Models\DpmEntity::class;
        $entities = class_exists($entityCls)
            ? $entityCls::query()->whereIn('id', $rows->pluck('entity_id')->filter()->unique())->get(['id', 'name', 'lei'])->keyBy('id')
            : collect();

        $result = $rows->map(function (FactModule $m) use ($entities) {
            $entity = $entities->get($m->entity_id);

            return array_merge($m->toArray(), [
                'taxonomy_name' => $m->taxonomy->name ?? null,
                'taxonomy_active' => (bool) ($m->taxonomy->active ?? false),
                'entity_name' => $entity->name ?? null,
                'entity_lei' => $entity->lei ?? null,
                'updated_label' => $m->updated_at ? $m->updated_at->format('d M Y, H:i') : null,
            ]);
        })->all();

        return response()->json([
            'all_total' => $allTotal,
            'total' => $filteredTotal,
            'rows' => $result,
        ]);

    }
}
