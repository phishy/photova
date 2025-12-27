<?php

namespace App\Services\Providers;

use Exception;
use Illuminate\Support\Facades\Http;

class RemoveBgProvider extends BaseProvider
{
    protected string $name = 'removebg';

    public function execute(string $operation, string $image, array $options = []): array
    {
        if ($operation !== 'background-remove') {
            throw new Exception("RemoveBg only supports 'background-remove' operation");
        }

        $imageData = $this->decodeImage($image);

        $response = Http::withHeaders([
            'X-Api-Key' => $this->apiKey,
        ])
            ->timeout(60)
            ->attach('image_file', $imageData, 'image.png')
            ->post('https://api.remove.bg/v1.0/removebg', [
                'size' => $options['size'] ?? 'auto',
            ]);

        if (!$response->successful()) {
            throw new Exception('RemoveBg API error: ' . $response->body());
        }

        return [
            'image' => $this->encodeImage($response->body(), 'image/png'),
            'provider' => $this->name,
        ];
    }

    public function getSupportedOperations(): array
    {
        return ['background-remove'];
    }
}
