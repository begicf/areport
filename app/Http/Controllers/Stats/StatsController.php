<?php

namespace App\Http\Controllers\Stats;

use App\Http\Controllers\Controller;
use App\Support\Stats;
use Illuminate\Http\Request;

class StatsController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(Stats::canView($request->user()), 404);
        $days = Stats::days(30);

        return view('stats.index', [
            'days' => $days,
            'metrics' => Stats::METRICS,
            'totals' => array_map(fn ($m) => array_sum(array_column($days, $m)), array_combine(array_keys(Stats::METRICS), array_keys(Stats::METRICS))),
            'max' => max(1, ...array_column($days, 'active_users')),
        ]);
    }
}
