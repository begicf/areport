<?php

namespace App\Http\Controllers\Stats;

use App\Http\Controllers\Controller;
use App\Model\Entity;
use App\Model\FactModule;
use App\Support\Demo;
use App\Support\Stats;
use App\User;
use Illuminate\Http\Request;

/**
 * The usage counts, and the users who confirmed their e-mail, as JSON for whoever holds
 * areport.stats.token (the operator's own dashboard); without a token, nothing here.
 */
class StatsController extends Controller
{
    public function json(Request $request)
    {
        $this->authorizeToken($request);
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

    /** Who signed up and confirmed their e-mail: name, address, when, and what they did (reports, entities). */
    public function users(Request $request)
    {
        $this->authorizeToken($request);
        $users = User::query()->whereNotNull('email_verified_at')
            ->when(Demo::sharedUserId(), fn ($q, $id) => $q->whereKeyNot($id))
            ->orderByDesc('email_verified_at')->get(['id', 'name', 'email', 'created_at', 'email_verified_at']);
        $ids = $users->modelKeys();
        $reports = FactModule::withoutGlobalScopes()->whereIn('user_id', $ids)->selectRaw('user_id, count(*) as n')->groupBy('user_id')->pluck('n', 'user_id');
        $entities = Entity::withoutGlobalScopes()->whereIn('user_id', $ids)->orderBy('name')->get(['user_id', 'name', 'lei', 'country'])->groupBy('user_id');

        return response()->json(['users' => $users->map(fn (User $u) => [
            'name' => $u->name,
            'email' => $u->email,
            'registered_at' => $u->created_at?->toIso8601String(),
            'verified_at' => $u->email_verified_at?->toIso8601String(),
            'reports' => (int) ($reports[$u->id] ?? 0),
            'entities' => ($entities[$u->id] ?? collect())->map(fn ($e) => ['name' => $e->name, 'lei' => $e->lei, 'country' => $e->country])->values(),
        ])->values()]);
    }

    private function authorizeToken(Request $request): void
    {
        $token = (string) config('areport.stats.token');
        abort_unless($token !== '' && hash_equals($token, (string) $request->bearerToken()), 404);
    }
}
