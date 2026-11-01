<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/** API-integration keys are limited to /api/v1; they must not open the full dashboard API. */
class BlockIntegrationTokens
{
    public function handle(Request $request, Closure $next)
    {
        $token = $request->user()?->currentAccessToken();
        if ($token && method_exists($token, 'can') && ! $token->can('*')) {
            abort(403, 'This API key can only be used with the /api/v1 endpoints.');
        }

        return $next($request);
    }
}
