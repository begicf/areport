<?php

namespace App\Http\Controllers\Stats;

use App\Http\Controllers\Controller;
use App\Support\Stats;
use App\User;
use Illuminate\Http\Request;

/** The usage counts as JSON, for whoever holds areport.stats.token; without a token, nothing here. */
class StatsController extends Controller
{
    public function json(Request $request)
    {
        $token = (string) config('areport.stats.token');
        abort_unless($token !== '' && hash_equals($token, (string) $request->bearerToken()), 404);
        $days = Stats::days(min(366, max(1, (int) $request->query('days', 30))));

        return response()->json([
            'metrics' => Stats::METRICS,
            'days' => $days,
            'users' => [
                'total' => User::query()->count(),
                'verified' => User::query()->whereNotNull('email_verified_at')->count(),
            ],
        ]);
    }
}
