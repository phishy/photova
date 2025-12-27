<?php

namespace App\Http\Middleware;

use App\Models\ApiKey;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateWithApiKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $key = $this->extractApiKey($request);

        if (!$key) {
            return response()->json(['error' => 'API key required'], 401);
        }

        $apiKey = $this->validateApiKey($key);

        if (!$apiKey) {
            return response()->json(['error' => 'Invalid API key'], 401);
        }

        if (!$apiKey->isActive()) {
            return response()->json(['error' => 'API key is inactive or expired'], 401);
        }

        $apiKey->update(['last_used_at' => now()]);

        Auth::login($apiKey->user);
        $request->attributes->set('api_key', $apiKey);

        return $next($request);
    }

    private function extractApiKey(Request $request): ?string
    {
        $authHeader = $request->header('Authorization');
        if ($authHeader && str_starts_with($authHeader, 'Bearer ')) {
            return substr($authHeader, 7);
        }

        if ($request->header('X-API-Key')) {
            return $request->header('X-API-Key');
        }

        return null;
    }

    private function validateApiKey(string $key): ?ApiKey
    {
        if (!str_starts_with($key, ApiKey::PREFIX)) {
            return null;
        }

        $prefix = substr($key, 0, 20);

        $apiKeys = ApiKey::where('key_prefix', $prefix)
            ->where('status', 'active')
            ->get();

        foreach ($apiKeys as $apiKey) {
            if (Hash::check($key, $apiKey->key_hash)) {
                return $apiKey;
            }
        }

        return null;
    }
}
