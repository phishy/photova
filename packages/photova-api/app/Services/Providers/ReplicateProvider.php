<?php

namespace App\Services\Providers;

use Exception;
use Illuminate\Support\Facades\Http;

class ReplicateProvider extends BaseProvider
{
    protected string $name = 'replicate';
    private array $models;

    private const ANALYSIS_OPERATIONS = ['analyze'];

    public function __construct(string $apiKey, array $models = [])
    {
        parent::__construct($apiKey);
        $this->models = $models;
    }

    public function execute(string $operation, string $image, array $options = []): array
    {
        $model = $this->models[$operation] ?? null;

        if (!$model) {
            throw new Exception("No model configured for operation '{$operation}'");
        }

        // Extract version hash from model config (format: "owner/model:version" or just "version")
        $version = str_contains($model, ':') ? explode(':', $model)[1] : $model;

        $input = $this->buildInput($operation, $image, $options);

        $response = Http::withToken($this->apiKey)
            ->timeout(120)
            ->post('https://api.replicate.com/v1/predictions', [
                'version' => $version,
                'input' => $input,
            ]);

        if (!$response->successful()) {
            throw new Exception('Replicate API error: ' . $response->body());
        }

        $prediction = $response->json();
        $result = $this->waitForResult($prediction['id']);

        // Analysis operations return text, not images
        if (in_array($operation, self::ANALYSIS_OPERATIONS)) {
            // Strip "Caption:" prefix if present (some models add it)
            $caption = preg_replace('/^Caption:\s*/i', '', $result);
            return [
                'caption' => $caption,
                'provider' => $this->name,
                'model' => $model,
            ];
        }

        return [
            'image' => $this->fetchAndEncodeResult($result),
            'provider' => $this->name,
            'model' => $model,
        ];
    }

    public function getSupportedOperations(): array
    {
        return array_keys($this->models);
    }

    private function buildInput(string $operation, string $image, array $options): array
    {
        return match ($operation) {
            'upscale' => ['image' => $image, 'scale' => $options['scale'] ?? 2],
            'inpaint' => [
                'image' => $image,
                'mask' => $options['mask'] ?? null,
                'prompt' => $options['prompt'] ?? '',
            ],
            'unblur', 'restore' => ['img' => $image],
            'colorize' => [
                'input_image' => $image,
                'model_name' => $options['model_name'] ?? 'Artistic',
            ],
            'analyze' => array_filter([
                'image' => $image,
                'task' => $options['task'] ?? 'image_captioning',
                'question' => $options['question'] ?? null,
            ], fn($v) => $v !== null),
            default => ['image' => $image],
        };
    }

    private function waitForResult(string $predictionId, int $maxWait = 300): string
    {
        $waited = 0;
        $interval = 2;

        while ($waited < $maxWait) {
            $response = Http::withToken($this->apiKey)
                ->get("https://api.replicate.com/v1/predictions/{$predictionId}");

            $data = $response->json();

            if ($data['status'] === 'succeeded') {
                $output = $data['output'];
                return is_array($output) ? $output[0] : $output;
            }

            if ($data['status'] === 'failed') {
                throw new Exception('Replicate prediction failed: ' . ($data['error'] ?? 'Unknown error'));
            }

            sleep($interval);
            $waited += $interval;
        }

        throw new Exception('Replicate prediction timed out');
    }

    private function fetchAndEncodeResult(string $url): string
    {
        $response = Http::get($url);

        if (!$response->successful()) {
            throw new Exception('Failed to fetch result image');
        }

        $contentType = $response->header('Content-Type') ?? 'image/png';
        return $this->encodeImage($response->body(), $contentType);
    }
}
