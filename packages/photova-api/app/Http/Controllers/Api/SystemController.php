<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class SystemController extends Controller
{
    public function health(): JsonResponse
    {
        return response()->json([
            'status' => 'ok',
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    public function operations(): JsonResponse
    {
        $operations = [];
        
        foreach (config('photova.operations') as $name => $config) {
            $operations[] = [
                'name' => $name,
                'provider' => $config['provider'],
                'fallback' => $config['fallback'] ?? null,
            ];
        }

        return response()->json(['operations' => $operations]);
    }

    public function openapi()
    {
        return redirect('/docs/api.json');
    }
}
