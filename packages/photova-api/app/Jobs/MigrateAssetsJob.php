<?php

namespace App\Jobs;

use App\Models\Asset;
use App\Models\AssetMigration;
use App\Services\RcloneService;
use App\Services\StorageService;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class MigrateAssetsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 3600;

    public function __construct(
        private AssetMigration $migration
    ) {
    }

    public function handle(RcloneService $rclone, StorageService $storage): void
    {
        $migration = $this->migration->fresh();

        if ($migration->isCancelled()) {
            return;
        }

        $migration->markAsProcessing();

        $query = Asset::where('user_id', $migration->user_id);

        if ($migration->from_bucket_id) {
            $query->where('storage_bucket_id', $migration->from_bucket_id);
        } else {
            $query->whereNull('storage_bucket_id');
        }

        $assets = $query->get();

        foreach ($assets as $asset) {
            if ($migration->fresh()->isCancelled()) {
                return;
            }

            try {
                $this->migrateAsset($rclone, $storage, $asset, $migration);
                $migration->incrementProcessed($asset->size);
            } catch (Exception $e) {
                Log::error('Asset migration failed', [
                    'migration_id' => $migration->id,
                    'asset_id' => $asset->id,
                    'error' => $e->getMessage(),
                ]);
                $migration->incrementFailed();
                $migration->addError($asset->id, $e->getMessage());
            }
        }

        if ($migration->failed_assets > 0 && $migration->processed_assets === 0) {
            $migration->markAsFailed();
        } else {
            $migration->markAsCompleted();
        }
    }

    private function migrateAsset(RcloneService $rclone, StorageService $storage, Asset $asset, AssetMigration $migration): void
    {
        $fromSystem = $migration->from_bucket_id === null;
        $toSystem = $migration->to_bucket_id === null;

        if ($fromSystem && $toSystem) {
            return;
        }

        if ($fromSystem) {
            $this->migrateFromSystemToRclone($rclone, $asset, $migration);
        } elseif ($toSystem) {
            $this->migrateFromRcloneToSystem($storage, $rclone, $asset, $migration);
        } else {
            $this->migrateRcloneToRclone($rclone, $asset, $migration);
        }
    }

    private function migrateFromSystemToRclone(RcloneService $rclone, Asset $asset, AssetMigration $migration): void
    {
        $contents = Storage::disk('assets')->get($asset->storage_key);

        if ($contents === null) {
            throw new Exception('Source file not found');
        }

        $rclone->writeFile($migration->toBucket, $asset->storage_key, $contents);

        $asset->update(['storage_bucket_id' => $migration->to_bucket_id]);

        if ($migration->delete_source) {
            Storage::disk('assets')->delete($asset->storage_key);
        }
    }

    private function migrateFromRcloneToSystem(StorageService $storage, RcloneService $rclone, Asset $asset, AssetMigration $migration): void
    {
        // Use StorageService which has AWS SDK fallback for S3-compatible storage
        $contents = $storage->retrieve($asset);

        if ($contents === null) {
            throw new Exception('Failed to read source file from bucket');
        }

        Storage::disk('assets')->put($asset->storage_key, $contents);

        $asset->update(['storage_bucket_id' => null]);

        if ($migration->delete_source) {
            $rclone->deleteFile($migration->fromBucket, $asset->storage_key);
        }
    }

    private function migrateRcloneToRclone(RcloneService $rclone, Asset $asset, AssetMigration $migration): void
    {
        if ($migration->delete_source) {
            $rclone->moveFile(
                $migration->fromBucket,
                $asset->storage_key,
                $migration->toBucket,
                $asset->storage_key
            );
        } else {
            $rclone->copyFile(
                $migration->fromBucket,
                $asset->storage_key,
                $migration->toBucket,
                $asset->storage_key
            );
        }

        $asset->update(['storage_bucket_id' => $migration->to_bucket_id]);
    }

    public function failed(Exception $exception): void
    {
        $this->migration->markAsFailed();
        $this->migration->addError('job', $exception->getMessage());
    }
}
