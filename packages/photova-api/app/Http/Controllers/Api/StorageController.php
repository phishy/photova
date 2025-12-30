<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\MigrateAssetsJob;
use App\Models\AssetMigration;
use App\Models\StorageBucket;
use App\Services\StorageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class StorageController extends Controller
{
    public function __construct(private StorageService $storage)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $buckets = $request->user()->storageBuckets()
            ->withCount('assets')
            ->orderByDesc('is_default')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn ($bucket) => $this->formatBucket($bucket));

        $systemStorage = [
            'id' => 'system',
            'name' => 'Platform Storage',
            'provider' => 'system',
            'isDefault' => $request->user()->getDefaultStorageBucket() === null,
            'isActive' => true,
            'assetsCount' => $request->user()->assets()->whereNull('storage_bucket_id')->count(),
        ];

        return response()->json([
            'system' => $systemStorage,
            'buckets' => $buckets,
            'rcloneAvailable' => $this->storage->isRcloneAvailable(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        if (!$this->storage->isRcloneAvailable()) {
            return response()->json([
                'error' => 'Storage service unavailable',
            ], 503);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'provider' => ['required', 'string', Rule::in(StorageBucket::PROVIDERS)],
            'config' => 'required|array',
            'credentials' => 'required|array',
            'is_default' => 'sometimes|boolean',
            'auto_analyze' => 'sometimes|boolean',
        ]);

        $bucket = $request->user()->storageBuckets()->create([
            'name' => $validated['name'],
            'provider' => $validated['provider'],
            'config' => $validated['config'],
            'credentials' => $validated['credentials'],
            'is_default' => false,
            'is_active' => true,
            'auto_analyze' => $validated['auto_analyze'] ?? true,
        ]);

        $connected = $this->storage->testConnection($bucket);

        if (!$connected) {
            $bucket->delete();
            return response()->json([
                'error' => 'Failed to connect to storage. Please check your credentials.',
            ], 422);
        }

        $bucket->markConnected();

        if ($validated['is_default'] ?? false) {
            $this->setAsDefault($request->user(), $bucket);
        }

        return response()->json([
            'bucket' => $this->formatBucket($bucket->fresh()->loadCount('assets')),
        ], 201);
    }

    public function show(Request $request, StorageBucket $bucket): JsonResponse
    {
        $this->authorizeBucket($request, $bucket);

        return response()->json([
            'bucket' => $this->formatBucket($bucket->loadCount('assets')),
        ]);
    }

    public function update(Request $request, StorageBucket $bucket): JsonResponse
    {
        $this->authorizeBucket($request, $bucket);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'config' => 'sometimes|array',
            'credentials' => 'sometimes|array',
            'is_active' => 'sometimes|boolean',
            'auto_analyze' => 'sometimes|boolean',
        ]);

        if (isset($validated['credentials']) || isset($validated['config'])) {
            $testBucket = clone $bucket;
            $testBucket->config = $validated['config'] ?? $bucket->config;
            $testBucket->credentials = $validated['credentials'] ?? $bucket->credentials;

            if (!$this->storage->testConnection($testBucket)) {
                return response()->json([
                    'error' => 'Failed to connect with new credentials.',
                ], 422);
            }

            $validated['last_connected_at'] = now();
        }

        $bucket->update($validated);

        return response()->json([
            'bucket' => $this->formatBucket($bucket->fresh()->loadCount('assets')),
        ]);
    }

    public function destroy(Request $request, StorageBucket $bucket): JsonResponse
    {
        $this->authorizeBucket($request, $bucket);

        if ($bucket->assets()->exists()) {
            return response()->json([
                'error' => 'Cannot delete bucket with assets. Migrate them first.',
            ], 422);
        }

        $bucket->delete();

        return response()->json(['message' => 'Storage bucket deleted']);
    }

    public function test(Request $request, StorageBucket $bucket): JsonResponse
    {
        $this->authorizeBucket($request, $bucket);

        $connected = $this->storage->testConnection($bucket);

        if ($connected) {
            $bucket->markConnected();
        }

        return response()->json([
            'connected' => $connected,
            'lastConnectedAt' => $bucket->fresh()->last_connected_at?->toIso8601String(),
        ]);
    }

    public function setDefault(Request $request, StorageBucket $bucket): JsonResponse
    {
        $this->authorizeBucket($request, $bucket);

        if (!$bucket->is_active) {
            return response()->json([
                'error' => 'Cannot set inactive bucket as default.',
            ], 422);
        }

        $this->setAsDefault($request->user(), $bucket);

        return response()->json([
            'bucket' => $this->formatBucket($bucket->fresh()->loadCount('assets')),
        ]);
    }

    public function clearDefault(Request $request): JsonResponse
    {
        $request->user()->storageBuckets()->where('is_default', true)->update(['is_default' => false]);

        return response()->json(['message' => 'Default cleared. Using platform storage.']);
    }

    public function migrate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'from_bucket_id' => 'nullable|uuid|exists:storage_buckets,id',
            'to_bucket_id' => 'nullable|uuid|exists:storage_buckets,id',
            'delete_source' => 'sometimes|boolean',
        ]);

        if ($validated['from_bucket_id'] === $validated['to_bucket_id']) {
            return response()->json([
                'error' => 'Source and destination must be different.',
            ], 422);
        }

        $hasPendingMigration = $request->user()->assetMigrations()
            ->whereIn('status', [AssetMigration::STATUS_PENDING, AssetMigration::STATUS_PROCESSING])
            ->exists();

        if ($hasPendingMigration) {
            return response()->json([
                'error' => 'You have a migration in progress. Please wait for it to complete.',
            ], 422);
        }

        $fromBucketId = $validated['from_bucket_id'] ?? null;
        $toBucketId = $validated['to_bucket_id'] ?? null;

        if ($fromBucketId) {
            $fromBucket = StorageBucket::find($fromBucketId);
            $this->authorizeBucket($request, $fromBucket);
            $assetsCount = $request->user()->assets()->where('storage_bucket_id', $fromBucketId)->count();
        } else {
            $assetsCount = $request->user()->assets()->whereNull('storage_bucket_id')->count();
        }

        if ($toBucketId) {
            $toBucket = StorageBucket::find($toBucketId);
            $this->authorizeBucket($request, $toBucket);
        }

        if ($assetsCount === 0) {
            return response()->json([
                'error' => 'No assets to migrate.',
            ], 422);
        }

        $migration = $request->user()->assetMigrations()->create([
            'from_bucket_id' => $fromBucketId,
            'to_bucket_id' => $toBucketId,
            'status' => AssetMigration::STATUS_PENDING,
            'total_assets' => $assetsCount,
            'delete_source' => $validated['delete_source'] ?? false,
        ]);

        MigrateAssetsJob::dispatch($migration);

        return response()->json([
            'migration' => $this->formatMigration($migration),
        ], 202);
    }

    public function migrations(Request $request): JsonResponse
    {
        $migrations = $request->user()->assetMigrations()
            ->with(['fromBucket', 'toBucket'])
            ->orderByDesc('created_at')
            ->limit(20)
            ->get()
            ->map(fn ($m) => $this->formatMigration($m));

        return response()->json(['migrations' => $migrations]);
    }

    public function migrationStatus(Request $request, AssetMigration $migration): JsonResponse
    {
        if ($migration->user_id !== $request->user()->id) {
            abort(403, 'Unauthorized');
        }

        return response()->json([
            'migration' => $this->formatMigration($migration->load(['fromBucket', 'toBucket'])),
        ]);
    }

    public function cancelMigration(Request $request, AssetMigration $migration): JsonResponse
    {
        if ($migration->user_id !== $request->user()->id) {
            abort(403, 'Unauthorized');
        }

        if (!$migration->canBeCancelled()) {
            return response()->json([
                'error' => 'Migration cannot be cancelled.',
            ], 422);
        }

        $migration->markAsCancelled();

        return response()->json([
            'migration' => $this->formatMigration($migration),
        ]);
    }

    public function providers(): JsonResponse
    {
        $providers = [
            [
                'id' => StorageBucket::PROVIDER_AWS,
                'name' => 'Amazon S3',
                'type' => 's3',
                'fields' => ['bucket', 'region', 'access_key_id', 'secret_access_key'],
            ],
            [
                'id' => StorageBucket::PROVIDER_DIGITALOCEAN,
                'name' => 'DigitalOcean Spaces',
                'type' => 's3',
                'fields' => ['bucket', 'region', 'access_key_id', 'secret_access_key'],
            ],
            [
                'id' => StorageBucket::PROVIDER_CLOUDFLARE,
                'name' => 'Cloudflare R2',
                'type' => 's3',
                'fields' => ['bucket', 'account_id', 'access_key_id', 'secret_access_key'],
            ],
            [
                'id' => StorageBucket::PROVIDER_BACKBLAZE,
                'name' => 'Backblaze B2',
                'type' => 's3',
                'fields' => ['bucket', 'region', 'access_key_id', 'secret_access_key'],
            ],
            [
                'id' => StorageBucket::PROVIDER_WASABI,
                'name' => 'Wasabi',
                'type' => 's3',
                'fields' => ['bucket', 'region', 'access_key_id', 'secret_access_key'],
            ],
            [
                'id' => StorageBucket::PROVIDER_MINIO,
                'name' => 'Minio',
                'type' => 's3',
                'fields' => ['bucket', 'endpoint', 'access_key_id', 'secret_access_key'],
            ],
            [
                'id' => StorageBucket::PROVIDER_GDRIVE,
                'name' => 'Google Drive',
                'type' => 'oauth',
                'fields' => [],
            ],
            [
                'id' => StorageBucket::PROVIDER_DROPBOX,
                'name' => 'Dropbox',
                'type' => 'oauth',
                'fields' => [],
            ],
            [
                'id' => StorageBucket::PROVIDER_ONEDRIVE,
                'name' => 'OneDrive',
                'type' => 'oauth',
                'fields' => [],
            ],
            [
                'id' => StorageBucket::PROVIDER_SFTP,
                'name' => 'SFTP',
                'type' => 'credentials',
                'fields' => ['host', 'port', 'user', 'pass', 'root'],
            ],
            [
                'id' => StorageBucket::PROVIDER_FTP,
                'name' => 'FTP',
                'type' => 'credentials',
                'fields' => ['host', 'port', 'user', 'pass', 'root'],
            ],
            [
                'id' => StorageBucket::PROVIDER_WEBDAV,
                'name' => 'WebDAV',
                'type' => 'credentials',
                'fields' => ['url', 'user', 'pass'],
            ],
            [
                'id' => StorageBucket::PROVIDER_OTHER,
                'name' => 'Other S3-Compatible',
                'type' => 's3',
                'fields' => ['bucket', 'endpoint', 'region', 'access_key_id', 'secret_access_key'],
            ],
        ];

        return response()->json(['providers' => $providers]);
    }

    private function authorizeBucket(Request $request, StorageBucket $bucket): void
    {
        if ($bucket->user_id !== $request->user()->id) {
            abort(403, 'Unauthorized');
        }
    }

    private function setAsDefault($user, StorageBucket $bucket): void
    {
        $user->storageBuckets()->where('is_default', true)->update(['is_default' => false]);
        $bucket->update(['is_default' => true]);
    }

    private function formatBucket(StorageBucket $bucket): array
    {
        return [
            'id' => $bucket->id,
            'name' => $bucket->name,
            'provider' => $bucket->provider,
            'config' => $bucket->config,
            'isDefault' => $bucket->is_default,
            'isActive' => $bucket->is_active,
            'autoAnalyze' => $bucket->auto_analyze,
            'assetsCount' => $bucket->assets_count ?? 0,
            'lastConnectedAt' => $bucket->last_connected_at?->toIso8601String(),
            'created' => $bucket->created_at->toIso8601String(),
            'updated' => $bucket->updated_at->toIso8601String(),
        ];
    }

    private function formatMigration(AssetMigration $migration): array
    {
        return [
            'id' => $migration->id,
            'fromBucket' => $migration->fromBucket ? [
                'id' => $migration->fromBucket->id,
                'name' => $migration->fromBucket->name,
            ] : ['id' => 'system', 'name' => 'Platform Storage'],
            'toBucket' => $migration->toBucket ? [
                'id' => $migration->toBucket->id,
                'name' => $migration->toBucket->name,
            ] : ['id' => 'system', 'name' => 'Platform Storage'],
            'status' => $migration->status,
            'totalAssets' => $migration->total_assets,
            'processedAssets' => $migration->processed_assets,
            'failedAssets' => $migration->failed_assets,
            'bytesTransferred' => $migration->bytes_transferred,
            'progress' => $migration->getProgressPercentage(),
            'deleteSource' => $migration->delete_source,
            'startedAt' => $migration->started_at?->toIso8601String(),
            'completedAt' => $migration->completed_at?->toIso8601String(),
            'created' => $migration->created_at->toIso8601String(),
        ];
    }
}
