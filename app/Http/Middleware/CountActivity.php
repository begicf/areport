<?php

namespace App\Http\Middleware;

use App\Support\Stats;
use Closure;
use Illuminate\Http\Request;

/** Counts what signed-in users do (App\Support\Stats): the request kind, never its content. */
class CountActivity
{
    private const ACTIONS = [
        'GET table/open' => 'forms_opened',
        'POST table/validate' => 'validations',
        'POST instance/export' => 'exports',
        'POST instance/export-csv' => 'exports',
        'POST table/import' => 'imports',
    ];

    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        if ($request->user() && $response->getStatusCode() < 400) {
            Stats::seen($request->user());
            $path = trim($request->path(), '/');
            $metric = self::ACTIONS[$request->method().' '.$path] ?? null;
            if (! $metric && $request->isMethod('GET') && $path === trim((string) config('areport-pro.route_prefix', 'pro'), '/').'/dpm-model') {
                $metric = 'architect';
            }
            if ($metric) {
                Stats::hit($metric);
            }
        }

        return $response;
    }
}
