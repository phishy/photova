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
    private ImagePreprocessor $preprocessor;

    public function __construct()
    {
        $this->preprocessor = new ImagePreprocessor();
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

        $processedImage = $this->applyPreprocessing($image, $config['preprocess'] ?? []);

        $primaryProvider = $config['provider'];
        $fallbackProvider = $config['fallback'] ?? null;

        if (isset($this->providers[$primaryProvider])) {
            try {
                return $this->providers[$primaryProvider]->execute($operation, $processedImage, $options);
            } catch (Exception $e) {
                if ($fallbackProvider && isset($this->providers[$fallbackProvider])) {
                    return $this->providers[$fallbackProvider]->execute($operation, $processedImage, $options);
                }
                throw $e;
            }
        }

        if ($fallbackProvider && isset($this->providers[$fallbackProvider])) {
            return $this->providers[$fallbackProvider]->execute($operation, $processedImage, $options);
        }

        throw new Exception("No provider available for operation '{$operation}'");
    }

    private function applyPreprocessing(string $image, array $preprocessConfig): string
    {
        if (empty($preprocessConfig)) {
            return $image;
        }

        return $this->preprocessor->process($image, $preprocessConfig);
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
