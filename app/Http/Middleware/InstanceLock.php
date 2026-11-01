<?php

namespace App\Http\Middleware;

use App\Model\FactModule;
use App\Support\InstanceHooks;
use Closure;
use Illuminate\Http\Request;

/**
 * Refuses changes to an instance's data or settings, and the filing export, while a
 * plugin locks the instance (e.g. it is in review or approved — see InstanceHooks).
 */
class InstanceLock
{
    private const ACTIONS = [
        'table/save' => 'edit',
        'table/import' => 'edit',
        'table/copy-from' => 'edit',
        'instance/settings' => 'edit',
        'instance/export' => 'export',
        'instance/export-csv' => 'export',
    ];

    public function handle(Request $request, Closure $next)
    {
        $action = self::ACTIONS[trim($request->path(), '/')] ?? null;
        if (! $action || $request->isMethodSafe() || ! $request->filled('instance_id') || ! auth()->check()) {
            return $next($request);
        }

        $reason = InstanceHooks::lockReason(FactModule::query()->find($request->input('instance_id')), $action);
        if (! $reason) {
            return $next($request);
        }
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['message' => $reason, 'locked' => true], 423);
        }
        if ($action === 'export') {
            abort(423, $reason);   // the export dialog posts a form for the download
        }

        return back()->with('warning', $reason);
    }
}
