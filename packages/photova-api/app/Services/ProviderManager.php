<?php

namespace App\Services;

use App\Services\Providers\BaseProvider;
use App\Services\Providers\FalProvider;
use App\Services\Providers\RemoveBgProvider;
use App\Services\Providers\ReplicateProvider;
use Exception;

class ProviderManager
{
    private array $providers = [];

    public function __construct()
    {
        $this->registerProviders();
    }

    private function registerProviders(): void
    {
        if (config('photova.providers.replicate.api_key')) {
            $this->providers['replicate'] = new ReplicateProvider(
                config('photova.providers.replicate.api_key'),
                config('photova.providers.replicate.models', [])
            );
        }

        if (config('photova.providers.fal.api_key')) {
            $this->providers['fal'] = new FalProvider(
                config('photova.providers.fal.api_key')
            );
        }

        if (config('photova.providers.removebg.api_key')) {
            $this->providers['removebg'] = new RemoveBgProvider(
                config('photova.providers.removebg.api_key')
            );
        }
    }

    public function execute(string $operation, string $image, array $options = []): array
    {
        $config = config("photova.operations.{$operation}");

        if (!$config) {
            throw new Exception("Operation '{$operation}' is not configured");
        }

        $primaryProvider = $config['provider'];
        $fallbackProvider = $config['fallback'] ?? null;

        if (isset($this->providers[$primaryProvider])) {
            try {
                return $this->providers[$primaryProvider]->execute($operation, $image, $options);
            } catch (Exception $e) {
                if ($fallbackProvider && isset($this->providers[$fallbackProvider])) {
                    return $this->providers[$fallbackProvider]->execute($operation, $image, $options);
                }
                throw $e;
            }
        }

        if ($fallbackProvider && isset($this->providers[$fallbackProvider])) {
            return $this->providers[$fallbackProvider]->execute($operation, $image, $options);
        }

        throw new Exception("No provider available for operation '{$operation}'");
    }

    public function getProvider(string $name): ?BaseProvider
    {
        return $this->providers[$name] ?? null;
    }

    public function getAvailableProviders(): array
    {
        return array_keys($this->providers);
    }
}
