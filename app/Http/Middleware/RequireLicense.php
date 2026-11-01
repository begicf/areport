<?php

namespace App\Http\Middleware;

use App\Support\License;
use Closure;
use Illuminate\Http\Request;

/** license:<product> — with enforcement on, the AReport license must include the product. */
class RequireLicense
{
    public function handle(Request $request, Closure $next, string $product)
    {
        if ($reason = License::refusal($product)) {
            abort(402, $reason);
        }

        return $next($request);
    }
}
