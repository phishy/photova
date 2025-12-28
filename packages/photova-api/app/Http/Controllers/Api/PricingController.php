<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProviderPricing;
use App\Services\PricingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PricingController extends Controller
{
    public function __construct(private PricingService $pricingService)
    {
    }

    public function index(): JsonResponse
    {
        $pricing = $this->pricingService->getAllPricing();

        return response()->json([
            'pricing' => $pricing->map(fn($p) => [
                'id' => $p->id,
                'provider' => $p->provider,
                'operation' => $p->operation,
                'model' => $p->model,
                'unit_type' => $p->unit_type,
                'cost_per_unit' => (float) $p->cost_per_unit,
                'price_per_unit' => (float) $p->price_per_unit,
                'margin' => $p->margin,
                'margin_percent' => $p->margin_percent,
                'currency' => $p->currency,
                'effective_at' => $p->effective_at->toIso8601String(),
                'is_active' => $p->is_active,
                'notes' => $p->notes,
            ]),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'provider' => 'required|string|max:50',
            'operation' => 'required|string|max:50',
            'model' => 'nullable|string|max:255',
            'unit_type' => 'required|string|in:per_image,per_second,per_megapixel,per_token',
            'cost_per_unit' => 'required|numeric|min:0',
            'price_per_unit' => 'required|numeric|min:0',
            'effective_at' => 'nullable|date',
            'is_active' => 'boolean',
            'notes' => 'nullable|string|max:500',
        ]);

        $validated['effective_at'] = $validated['effective_at'] ?? now();
        $validated['is_active'] = $validated['is_active'] ?? true;

        $pricing = $this->pricingService->createPricing($validated);

        return response()->json(['pricing' => $pricing], 201);
    }

    public function show(ProviderPricing $pricing): JsonResponse
    {
        return response()->json(['pricing' => $pricing]);
    }

    public function update(Request $request, ProviderPricing $pricing): JsonResponse
    {
        $validated = $request->validate([
            'provider' => 'sometimes|string|max:50',
            'operation' => 'sometimes|string|max:50',
            'model' => 'nullable|string|max:255',
            'unit_type' => 'sometimes|string|in:per_image,per_second,per_megapixel,per_token',
            'cost_per_unit' => 'sometimes|numeric|min:0',
            'price_per_unit' => 'sometimes|numeric|min:0',
            'effective_at' => 'nullable|date',
            'is_active' => 'boolean',
            'notes' => 'nullable|string|max:500',
        ]);

        $pricing = $this->pricingService->updatePricing($pricing, $validated);

        return response()->json(['pricing' => $pricing]);
    }

    public function destroy(ProviderPricing $pricing): JsonResponse
    {
        $this->pricingService->deletePricing($pricing);

        return response()->json(null, 204);
    }

    public function summary(): JsonResponse
    {
        $pricing = $this->pricingService->getAllPricing();

        $byProvider = $pricing->groupBy('provider')->map(fn($items) => [
            'count' => $items->count(),
            'operations' => $items->pluck('operation')->unique()->values(),
        ]);

        $byOperation = $pricing->groupBy('operation')->map(fn($items) => [
            'count' => $items->count(),
            'providers' => $items->pluck('provider')->unique()->values(),
            'avg_margin_percent' => round($items->avg('margin_percent'), 2),
        ]);

        return response()->json([
            'total' => $pricing->count(),
            'by_provider' => $byProvider,
            'by_operation' => $byOperation,
        ]);
    }
}
