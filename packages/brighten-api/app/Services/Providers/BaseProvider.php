<?php

namespace App\Services\Providers;

abstract class BaseProvider
{
    protected string $apiKey;
    protected string $name;

    public function __construct(string $apiKey)
    {
        $this->apiKey = $apiKey;
    }

    abstract public function execute(string $operation, string $image, array $options = []): array;

    abstract public function getSupportedOperations(): array;

    public function getName(): string
    {
        return $this->name;
    }

    protected function decodeImage(string $image): string
    {
        if (preg_match('/^data:[^;]+;base64,(.+)$/', $image, $matches)) {
            return base64_decode($matches[1]);
        }
        return base64_decode($image);
    }

    protected function encodeImage(string $data, string $mimeType = 'image/png'): string
    {
        return "data:{$mimeType};base64," . base64_encode($data);
    }
}
