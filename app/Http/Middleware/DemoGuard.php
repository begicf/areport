<?php

namespace App\Http\Middleware;

use App\Model\FactModule;
use App\Support\Demo;
use Closure;
use Illuminate\Http\Request;

/** Keeps a shared demo intact for every visitor (see App\Support\Demo). */
class DemoGuard
{
    /** Plugin requests that only compute or check (they change nothing others see). */
    private const PRO_ALLOWED = [
        '#^dpm-model/frameworks/\d+/validate-instance$#',
        '#^dpm-model/validations/check$#',
        '#^validations/check$#',
        '#^dpm-model/modules/\d+/validate-xbrl$#',
        '#^dpm-model/export$#',
    ];

    public function handle(Request $request, Closure $next)
    {
        if ($request->isMethodSafe() || ! Demo::restricted()) {
            return $next($request);
        }
        $path = trim($request->path(), '/');
        $pro = trim((string) config('areport-pro.route_prefix', 'pro'), '/').'/';

        if (str_starts_with($path, 'taxonomy/')) {
            return $this->deny($request, 'Taxonomies are shared by everyone in this demo, so they cannot be changed here.');
        }
        if (str_starts_with($path, $pro)) {
            $rest = substr($path, strlen($pro));
            foreach (self::PRO_ALLOWED as $re) {
                if (preg_match($re, $rest)) {
                    return $next($request);
                }
            }

            return $this->deny($request, 'The DPM Architect model and the settings are shared by everyone in this demo — you can explore them, validate and export, but not change them. Contact us for your own workspace.');
        }
        if (in_array($path, ['table/save', 'table/import', 'instance/settings'], true) && $request->filled('instance_id')) {
            $instance = FactModule::query()->withoutGlobalScope('owner')->find($request->input('instance_id'));
            if (Demo::readOnlyInstance($instance)) {
                return $this->deny($request, 'This sample report is shared by everyone in the demo. Create your own report (New) to enter data.');
            }
        }

        return $next($request);
    }

    private function deny(Request $request, string $message)
    {
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['message' => $message, 'demo' => true], 403);
        }

        return back()->with('warning', $message);
    }
}
