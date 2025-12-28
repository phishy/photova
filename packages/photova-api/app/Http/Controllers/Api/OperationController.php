<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UsageDaily;
use App\Models\UsageLog;
use App\Services\ProviderManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OperationController extends Controller
{
    private const VALID_OPERATIONS = [
        'background-remove',
        'upscale',
        'unblur',
        'colorize',
        'inpaint',
        'restore',
    ];

    public function execute(Request $request, string $operation): JsonResponse
    {
        if (!in_array($operation, self::VALID_OPERATIONS)) {
            return response()->json(['error' => 'Invalid operation'], 400);
        }

        $validated = $request->validate([
            'image' => 'required|string',
            'options' => 'sometimes|array',
        ]);

        $startTime = microtime(true);
        $requestId = Str::uuid()->toString();

        try {
            $providerManager = app(ProviderManager::class);
            $result = $providerManager->execute($operation, $validated['image'], $validated['options'] ?? []);

            $latencyMs = (int) ((microtime(true) - $startTime) * 1000);

            $this->logUsage($request, $operation, 'success', $latencyMs, $requestId);

            return response()->json([
                'image' => $result['image'],
                'metadata' => [
                    'provider' => $result['provider'],
                    'model' => $result['model'] ?? null,
                    'processingTime' => $latencyMs,
                    'requestId' => $requestId,
                ],
            ]);
        } catch (\Exception $e) {
            $latencyMs = (int) ((microtime(true) - $startTime) * 1000);

            $this->logUsage($request, $operation, 'error', $latencyMs, $requestId, $e->getMessage());

            return response()->json([
                'error' => $e->getMessage(),
                'requestId' => $requestId,
            ], 500);
        }
    }

    private function logUsage(
        Request $request,
        string $operation,
        string $status,
        int $latencyMs,
        string $requestId,
        ?string $errorMessage = null
    ): void {
        $user = $request->user();
        if (!$user) {
            return;
        }

        $apiKey = $request->attributes->get('api_key');

        UsageLog::create([
            'user_id' => $user->id,
            'api_key_id' => $apiKey?->id,
            'operation' => $operation,
            'status' => $status,
            'latency_ms' => $latencyMs,
            'request_id' => $requestId,
            'error_message' => $errorMessage,
        ]);

        $today = now()->toDateString();
        
        UsageDaily::upsert(
            [
                'user_id' => $user->id,
                'date' => $today,
                'operation' => $operation,
                'request_count' => 1,
                'error_count' => $status === 'error' ? 1 : 0,
                'total_latency_ms' => $latencyMs,
            ],
            ['user_id', 'date', 'operation'],
            [
                'request_count' => DB::raw('usage_daily.request_count + 1'),
                'error_count' => DB::raw('usage_daily.error_count + ' . ($status === 'error' ? 1 : 0)),
                'total_latency_ms' => DB::raw('usage_daily.total_latency_ms + ' . $latencyMs),
            ]
        );
    }
}
