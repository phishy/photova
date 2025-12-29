<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\StorageBucket;
use App\Models\User;
use Aws\S3\S3Client;
use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class StorageService
{
    private RcloneService $rclone;

    public function __construct(RcloneService $rclone)
    {
        $this->rclone = $rclone;
    }

    private function createS3Client(StorageBucket $bucket): S3Client
    {
        $config = $bucket->config ?? [];
        $credentials = $bucket->credentials ?? [];

        $endpoint = $config['endpoint'] ?? null;
        $region = $config['region'] ?? 'us-east-1';
        $accessKey = $credentials['access_key_id'] ?? '';
        $secretKey = $credentials['secret_access_key'] ?? '';

        $clientConfig = [
            'version' => 'latest',
            'region' => $region,
            'credentials' => [
                'key' => $accessKey,
                'secret' => $secretKey,
            ],
        ];

        if ($endpoint) {
            $clientConfig['endpoint'] = $this->resolveEndpoint($endpoint);
            $clientConfig['use_path_style_endpoint'] = true;
        }

        return new S3Client($clientConfig);
    }

    private function resolveEndpoint(string $endpoint): string
    {
        if (app()->environment('local', 'testing')) {
            $endpoint = str_replace('://minio:', '://localhost:', $endpoint);
        }

        return $endpoint;
    }

    private function getS3BucketName(StorageBucket $bucket): string
    {
        return $bucket->config['bucket'] ?? '';
    }

    public function store(
        User $user,
        UploadedFile|string $file,
        string $filename,
        ?StorageBucket $bucket = null
    ): array {
        $bucket = $bucket ?? $user->getDefaultStorageBucket();
        $storageKey = $this->generateStorageKey($filename);

        if ($bucket === null) {
            return $this->storeToSystem($file, $storageKey);
        }

        return $this->storeToUserBucket($bucket, $file, $storageKey);
    }

    public function retrieve(Asset $asset): ?string
    {
        if ($asset->isOnSystemStorage()) {
            return $this->retrieveFromSystem($asset->storage_key);
        }

        return $this->retrieveFromUserBucket($asset->storageBucket, $asset->storage_key);
    }

    public function delete(Asset $asset): bool
    {
        if ($asset->isOnSystemStorage()) {
            return $this->deleteFromSystem($asset->storage_key);
        }

        return $this->deleteFromUserBucket($asset->storageBucket, $asset->storage_key);
    }

    public function getUrl(Asset $asset): ?string
    {
        if ($asset->isOnSystemStorage()) {
            return Storage::disk('assets')->url($asset->storage_key);
        }

        return null;
    }

    public function exists(Asset $asset): bool
    {
        if ($asset->isOnSystemStorage()) {
            return Storage::disk('assets')->exists($asset->storage_key);
        }

        $info = $this->rclone->getFileInfo($asset->storageBucket, $asset->storage_key);
        return $info !== null;
    }

    public function testConnection(StorageBucket $bucket): bool
    {
        return $this->rclone->testConnection($bucket);
    }

    public function isRcloneAvailable(): bool
    {
        return $this->rclone->isHealthy();
    }

    private function storeToSystem(UploadedFile|string $file, string $storageKey): array
    {
        if ($file instanceof UploadedFile) {
            Storage::disk('assets')->putFileAs('', $file, $storageKey);
            $size = $file->getSize();
        } else {
            Storage::disk('assets')->put($storageKey, $file);
            $size = strlen($file);
        }

        return [
            'storage_key' => $storageKey,
            'storage_bucket_id' => null,
            'size' => $size,
        ];
    }

    private function storeToUserBucket(
        StorageBucket $bucket,
        UploadedFile|string $file,
        string $storageKey
    ): array {
        if ($file instanceof UploadedFile) {
            $contents = file_get_contents($file->getRealPath());
            $size = $file->getSize();
        } else {
            $contents = $file;
            $size = strlen($file);
        }

        $success = $this->rclone->writeFile($bucket, $storageKey, $contents);

        if (!$success) {
            throw new Exception('Failed to store file to user bucket');
        }

        $bucket->markConnected();

        return [
            'storage_key' => $storageKey,
            'storage_bucket_id' => $bucket->id,
            'size' => $size,
        ];
    }

    private function retrieveFromSystem(string $storageKey): ?string
    {
        if (!Storage::disk('assets')->exists($storageKey)) {
            return null;
        }

        return Storage::disk('assets')->get($storageKey);
    }

    private function retrieveFromUserBucket(StorageBucket $bucket, string $storageKey): ?string
    {
        if ($bucket->isS3Compatible()) {
            return $this->retrieveFromS3($bucket, $storageKey);
        }

        Log::warning('Non-S3 user bucket retrieval not yet supported', [
            'bucket_id' => $bucket->id,
            'provider' => $bucket->provider,
        ]);

        return null;
    }

    private function retrieveFromS3(StorageBucket $bucket, string $storageKey): ?string
    {
        try {
            $client = $this->createS3Client($bucket);
            $bucketName = $this->getS3BucketName($bucket);

            $result = $client->getObject([
                'Bucket' => $bucketName,
                'Key' => $storageKey,
            ]);

            return (string) $result['Body'];
        } catch (Exception $e) {
            Log::warning('S3 retrieve failed', [
                'bucket_id' => $bucket->id,
                'key' => $storageKey,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    private function deleteFromSystem(string $storageKey): bool
    {
        return Storage::disk('assets')->delete($storageKey);
    }

    private function deleteFromUserBucket(StorageBucket $bucket, string $storageKey): bool
    {
        try {
            return $this->rclone->deleteFile($bucket, $storageKey);
        } catch (Exception $e) {
            return false;
        }
    }

    private function generateStorageKey(string $filename): string
    {
        $date = now()->format('Y/m/d');
        $uuid = Str::uuid();
        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        
        return "{$date}/{$uuid}.{$extension}";
    }
}
