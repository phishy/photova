<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class OptionalAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!config('photova.auth.enabled', true)) {
            return $next($request);
        }

        $authMiddleware = new AuthenticateWithApiKey();
        return $authMiddleware->handle($request, $next);
    }
}
