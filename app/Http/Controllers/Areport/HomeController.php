<?php

namespace App\Http\Controllers\Areport;

use App\Http\Controllers\Controller;
use App\Model\FactModule;
use App\Model\Taxonomy;
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
        $data = FactModule::query();

        $activeTaxonomyId = Taxonomy::query()->where('active', true)->value('id');

        if (!empty($activeTaxonomyId)) {
            $data->where('taxonomy_id', '=', $activeTaxonomyId);
        }

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

        $result = $data->orderBy($sort, $order)
            ->offset($offset)
            ->limit($limit)
            ->get()
            ->toArray();

        return response()->json([
            'all_total' => $allTotal,
            'total' => $filteredTotal,
            'rows' => $result,
        ]);

    }
}
