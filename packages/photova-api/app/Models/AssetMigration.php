<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetMigration extends Model
{
    use HasFactory, HasUuids;

    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'user_id',
        'from_bucket_id',
        'to_bucket_id',
        'status',
        'total_assets',
        'processed_assets',
        'failed_assets',
        'bytes_transferred',
        'delete_source',
        'error_log',
        'started_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'total_assets' => 'integer',
            'processed_assets' => 'integer',
            'failed_assets' => 'integer',
            'bytes_transferred' => 'integer',
            'delete_source' => 'boolean',
            'error_log' => 'array',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function fromBucket(): BelongsTo
    {
        return $this->belongsTo(StorageBucket::class, 'from_bucket_id');
    }

    public function toBucket(): BelongsTo
    {
        return $this->belongsTo(StorageBucket::class, 'to_bucket_id');
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isProcessing(): bool
    {
        return $this->status === self::STATUS_PROCESSING;
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function canBeCancelled(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_PROCESSING], true);
    }

    public function getProgressPercentage(): int
    {
        if ($this->total_assets === 0) {
            return 0;
        }

        return (int) round(($this->processed_assets / $this->total_assets) * 100);
    }

    public function markAsProcessing(): void
    {
        $this->update([
            'status' => self::STATUS_PROCESSING,
            'started_at' => now(),
        ]);
    }

    public function markAsCompleted(): void
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'completed_at' => now(),
        ]);
    }

    public function markAsFailed(): void
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'completed_at' => now(),
        ]);
    }

    public function markAsCancelled(): void
    {
        $this->update([
            'status' => self::STATUS_CANCELLED,
            'completed_at' => now(),
        ]);
    }

    public function incrementProcessed(int $bytes = 0): void
    {
        $this->increment('processed_assets');
        if ($bytes > 0) {
            $this->increment('bytes_transferred', $bytes);
        }
    }

    public function incrementFailed(): void
    {
        $this->increment('failed_assets');
    }

    public function addError(string $assetId, string $message): void
    {
        $errors = $this->error_log ?? [];
        $errors[] = [
            'asset_id' => $assetId,
            'message' => $message,
            'timestamp' => now()->toIso8601String(),
        ];
        $this->update(['error_log' => $errors]);
    }
}
