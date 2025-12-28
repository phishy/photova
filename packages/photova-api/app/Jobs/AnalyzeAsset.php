<?php

namespace App\Jobs;

use App\Models\Asset;
use App\Services\ProviderManager;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class AnalyzeAsset implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;
    public int $backoff = 30;

    public function __construct(
        public Asset $asset
    ) {}

    public function handle(ProviderManager $providerManager): void
    {
        if (!str_starts_with($this->asset->mime_type, 'image/')) {
            return;
        }

        try {
            $base64Image = $this->getAssetAsBase64();
            if (!$base64Image) {
                return;
            }

            $result = $providerManager->execute('analyze', $base64Image, []);

            $this->saveAnalysisResult($result);
        } catch (\Exception $e) {
            Log::error("AnalyzeAsset: Failed for asset {$this->asset->id}: {$e->getMessage()}");
        }
    }

    private function getAssetAsBase64(): ?string
    {
        $bucketConfig = config("photova.storage.buckets.{$this->asset->bucket}");
        $disk = $bucketConfig['disk'] ?? 'local';
        $storagePath = ($bucketConfig['path'] ?? 'assets') . '/' . $this->asset->storage_key;

        $content = Storage::disk($disk)->get($storagePath);
        if (!$content) {
            Log::warning("AnalyzeAsset: Could not read asset {$this->asset->id}");
            return null;
        }

        return 'data:' . $this->asset->mime_type . ';base64,' . base64_encode($content);
    }

    private function saveAnalysisResult(array $result): void
    {
        $metadata = $this->asset->metadata ?? [];
        $metadata['caption'] = $result['caption'] ?? null;
        $metadata['analyzed_at'] = now()->toIso8601String();
        $metadata['analysis_provider'] = $result['provider'] ?? null;

        $this->asset->update(['metadata' => $metadata]);

        Log::info("AnalyzeAsset: Completed for asset {$this->asset->id}");
    }
}
