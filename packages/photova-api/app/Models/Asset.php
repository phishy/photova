<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Asset extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'user_id',
        'storage_bucket_id',
        'folder_id',
        'storage_key',
        'filename',
        'mime_type',
        'size',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'size' => 'integer',
            'view_count' => 'integer',
            'download_count' => 'integer',
            'last_viewed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function folder(): BelongsTo
    {
        return $this->belongsTo(Folder::class);
    }

    public function storageBucket(): BelongsTo
    {
        return $this->belongsTo(StorageBucket::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class)->withTimestamps();
    }

    public function isOnSystemStorage(): bool
    {
        return $this->storage_bucket_id === null;
    }

    public function isOnUserStorage(): bool
    {
        return $this->storage_bucket_id !== null;
    }

    public function analytics(): HasMany
    {
        return $this->hasMany(AssetAnalytic::class);
    }

    public function getAnalyticsSummary(int $days = 30): array
    {
        $since = now()->subDays($days);

        $analytics = $this->analytics()
            ->where('created_at', '>=', $since)
            ->selectRaw('event_type, COUNT(*) as count')
            ->groupBy('event_type')
            ->pluck('count', 'event_type')
            ->toArray();

        return [
            'views' => $analytics[AssetAnalytic::EVENT_VIEW] ?? 0,
            'downloads' => $analytics[AssetAnalytic::EVENT_DOWNLOAD] ?? 0,
            'total' => array_sum($analytics),
        ];
    }
}
