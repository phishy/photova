<?php

namespace App\Jobs;

use App\Models\Asset;
use App\Models\UsageDaily;
use App\Models\UsageLog;
use App\Services\PricingService;
use App\Services\ProviderManager;
use App\Services\StorageService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AnalyzeAsset implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;
    public int $backoff = 30;

    public function __construct(
        public Asset $asset
    ) {}

    public function handle(
        ProviderManager $providerManager,
        StorageService $storageService,
        PricingService $pricingService
    ): void {
        if (!str_starts_with($this->asset->mime_type, 'image/')) {
            return;
        }

        $startTime = microtime(true);
        $requestId = Str::uuid()->toString();

        try {
            $base64Image = $this->getAssetAsBase64($storageService);
            if (!$base64Image) {
                return;
            }

            $result = $providerManager->execute('analyze', $base64Image, []);

            $latencyMs = (int) ((microtime(true) - $startTime) * 1000);
            $provider = $result['provider'];
            $model = $result['model'] ?? null;
            $costs = $pricingService->calculateCosts($provider, 'analyze', $model);

            $this->logUsage(
                'success',
                $latencyMs,
                $requestId,
                $provider,
                $model,
                $costs['cost'],
                $costs['price']
            );

            $this->saveAnalysisResult($result);
        } catch (\Exception $e) {
            $latencyMs = (int) ((microtime(true) - $startTime) * 1000);

            $this->logUsage(
                'error',
                $latencyMs,
                $requestId,
                errorMessage: $e->getMessage()
            );

            Log::error("AnalyzeAsset: Failed for asset {$this->asset->id}: {$e->getMessage()}");
        }
    }

    private function getAssetAsBase64(StorageService $storageService): ?string
    {
        $content = $storageService->retrieve($this->asset);

        if (!$content) {
            Log::warning("AnalyzeAsset: Could not read asset {$this->asset->id}");
            return null;
        }

        $mimeType = $this->asset->mime_type;

        // Convert HEIC/HEIF to JPEG - AI providers don't support these formats
        if (in_array($mimeType, ['image/heic', 'image/heif']) && extension_loaded('imagick')) {
            $content = $this->convertToJpeg($content);
            $mimeType = 'image/jpeg';
        }

        return 'data:' . $mimeType . ';base64,' . base64_encode($content);
    }

    /**
     * Convert image content to JPEG format using ImageMagick
     */
    private function convertToJpeg(string $content): string
    {
        // Use dynamic instantiation to avoid static analysis errors (imagick is a runtime extension)
        $imagick = new ('Imagick')();
        $imagick->readImageBlob($content);
        $imagick->autoOrient();
        $imagick->setImageFormat('jpeg');
        $imagick->setImageCompressionQuality(92);
        $output = $imagick->getImageBlob();
        $imagick->destroy();

        return $output;
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

    private function logUsage(
        string $status,
        int $latencyMs,
        string $requestId,
        ?string $provider = null,
        ?string $model = null,
        float $cost = 0,
        float $price = 0,
        ?string $errorMessage = null
    ): void {
        $userId = $this->asset->user_id;

        UsageLog::create([
            'user_id' => $userId,
            'api_key_id' => null,
            'operation' => 'analyze',
            'source' => 'internal',
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
                'user_id' => $userId,
                'date' => $today,
                'operation' => 'analyze',
                'source' => 'internal',
                'request_count' => 1,
                'error_count' => $status === 'error' ? 1 : 0,
                'total_latency_ms' => $latencyMs,
                'total_cost' => $cost,
                'total_price' => $price,
            ],
            ['user_id', 'date', 'operation', 'source'],
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
