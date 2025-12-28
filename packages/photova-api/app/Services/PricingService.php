<?php

namespace App\Services;

use App\Models\ProviderPricing;
use Illuminate\Support\Facades\Cache;

class PricingService
{
    private const CACHE_TTL = 300;

    public function getPricing(string $provider, string $operation, ?string $model = null): ?ProviderPricing
    {
        $cacheKey = "pricing:{$provider}:{$operation}:" . ($model ?? 'default');

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($provider, $operation, $model) {
            $query = ProviderPricing::active()
                ->forOperation($provider, $operation, $model)
                ->first();

            if (!$query && $model) {
                $query = ProviderPricing::active()
                    ->forOperation($provider, $operation, null)
                    ->first();
            }

            return $query;
        });
    }

    public function calculateCosts(
        string $provider,
        string $operation,
        ?string $model = null,
        int $units = 1
    ): array {
        $pricing = $this->getPricing($provider, $operation, $model);

        if (!$pricing) {
            return [
                'cost' => 0,
                'price' => 0,
                'pricing_found' => false,
            ];
        }

        return [
            'cost' => (float) $pricing->cost_per_unit * $units,
            'price' => (float) $pricing->price_per_unit * $units,
            'pricing_found' => true,
            'unit_type' => $pricing->unit_type,
        ];
    }

    public function clearCache(?string $provider = null, ?string $operation = null): void
    {
        if ($provider && $operation) {
            Cache::forget("pricing:{$provider}:{$operation}:default");
            $models = ProviderPricing::where('provider', $provider)
                ->where('operation', $operation)
                ->whereNotNull('model')
                ->pluck('model');

            foreach ($models as $model) {
                Cache::forget("pricing:{$provider}:{$operation}:{$model}");
            }
        } else {
            $pricings = ProviderPricing::all();
            foreach ($pricings as $pricing) {
                $cacheKey = "pricing:{$pricing->provider}:{$pricing->operation}:" . ($pricing->model ?? 'default');
                Cache::forget($cacheKey);
            }
        }
    }

    public function getAllPricing(): \Illuminate\Database\Eloquent\Collection
    {
        return ProviderPricing::active()
            ->orderBy('provider')
            ->orderBy('operation')
            ->get();
    }

    public function createPricing(array $data): ProviderPricing
    {
        $pricing = ProviderPricing::create($data);
        $this->clearCache($data['provider'], $data['operation']);
        return $pricing;
    }

    public function updatePricing(ProviderPricing $pricing, array $data): ProviderPricing
    {
        $oldProvider = $pricing->provider;
        $oldOperation = $pricing->operation;

        $pricing->update($data);

        $this->clearCache($oldProvider, $oldOperation);
        if ($oldProvider !== $pricing->provider || $oldOperation !== $pricing->operation) {
            $this->clearCache($pricing->provider, $pricing->operation);
        }

        return $pricing;
    }

    public function deletePricing(ProviderPricing $pricing): void
    {
        $provider = $pricing->provider;
        $operation = $pricing->operation;

        $pricing->delete();

        $this->clearCache($provider, $operation);
    }
}
