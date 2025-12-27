<?php

namespace App\Services\Providers;

use Exception;
use Illuminate\Support\Facades\Http;

class FalProvider extends BaseProvider
{
    protected string $name = 'fal';

    private const MODELS = [
        'background-remove' => 'fal-ai/birefnet',
        'upscale' => 'fal-ai/real-esrgan',
    ];

    public function execute(string $operation, string $image, array $options = []): array
    {
        $model = self::MODELS[$operation] ?? null;

        if (!$model) {
            throw new Exception("Fal.ai does not support operation '{$operation}'");
        }

        $input = $this->buildInput($operation, $image, $options);

        $response = Http::withHeaders([
            'Authorization' => "Key {$this->apiKey}",
            'Content-Type' => 'application/json',
        ])
            ->timeout(120)
            ->post("https://fal.run/{$model}", $input);

        if (!$response->successful()) {
            throw new Exception('Fal.ai API error: ' . $response->body());
        }

        $data = $response->json();
        $imageUrl = $data['image']['url'] ?? $data['output']['url'] ?? null;

        if (!$imageUrl) {
            throw new Exception('No image URL in Fal.ai response');
        }

        return [
            'image' => $this->fetchAndEncodeResult($imageUrl),
            'provider' => $this->name,
            'model' => $model,
        ];
    }

    public function getSupportedOperations(): array
    {
        return array_keys(self::MODELS);
    }

    private function buildInput(string $operation, string $image, array $options): array
    {
        return match ($operation) {
            'upscale' => [
                'image_url' => $image,
                'scale' => $options['scale'] ?? 2,
            ],
            default => [
                'image_url' => $image,
            ],
        };
    }

    private function fetchAndEncodeResult(string $url): string
    {
        $response = Http::get($url);

        if (!$response->successful()) {
            throw new Exception('Failed to fetch result image from Fal.ai');
        }

        $contentType = $response->header('Content-Type') ?? 'image/png';
        return $this->encodeImage($response->body(), $contentType);
    }
}
