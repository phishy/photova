<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UsageDaily;
use App\Models\UsageLog;
use App\Services\PricingService;
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
        'analyze',
    ];

    private const ANALYSIS_OPERATIONS = ['analyze'];

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
            $pricingService = app(PricingService::class);

            $result = $providerManager->execute($operation, $validated['image'], $validated['options'] ?? []);

            $latencyMs = (int) ((microtime(true) - $startTime) * 1000);

            $provider = $result['provider'];
            $model = $result['model'] ?? null;
            $costs = $pricingService->calculateCosts($provider, $operation, $model);

            $this->logUsage(
                $request,
                $operation,
                'success',
                $latencyMs,
                $requestId,
                null,
                $provider,
                $model,
                $costs['cost'],
                $costs['price']
            );

            $metadata = [
                'provider' => $provider,
                'model' => $model,
                'processingTime' => $latencyMs,
                'requestId' => $requestId,
            ];

            if (in_array($operation, self::ANALYSIS_OPERATIONS)) {
                return response()->json([
                    'caption' => $result['caption'],
                    'metadata' => $metadata,
                ]);
            }

            return response()->json([
                'image' => $result['image'],
                'metadata' => $metadata,
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
        ?string $errorMessage = null,
        ?string $provider = null,
        ?string $model = null,
        float $cost = 0,
        float $price = 0
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
            'provider' => $provider,
            'model' => $model,
            'status' => $status,
            'latency_ms' => $latencyMs,
            'cost' => $cost,
            'price' => $price,
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
                'total_cost' => $cost,
                'total_price' => $price,
            ],
            ['user_id', 'date', 'operation'],
            [
                'request_count' => DB::raw('usage_daily.request_count + 1'),
                'error_count' => DB::raw('usage_daily.error_count + ' . ($status === 'error' ? 1 : 0)),
                'total_latency_ms' => DB::raw('usage_daily.total_latency_ms + ' . $latencyMs),
                'total_cost' => DB::raw('usage_daily.total_cost + ' . $cost),
                'total_price' => DB::raw('usage_daily.total_price + ' . $price),
            ]
        );
    }
}
