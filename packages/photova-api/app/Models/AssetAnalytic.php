<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Http\Request;

class AssetAnalytic extends Model
{
    use HasUuids;

    const EVENT_VIEW = 'view';
    const EVENT_DOWNLOAD = 'download';
    const EVENT_THUMBNAIL = 'thumbnail';

    const SOURCE_SHARE = 'share';
    const SOURCE_DIRECT = 'direct';
    const SOURCE_API = 'api';

    protected $table = 'asset_analytics';

    public $timestamps = false;

    protected $fillable = [
        'asset_id',
        'share_id',
        'event_type',
        'source',
        'ip_address',
        'user_agent',
        'referer',
        'country',
        'city',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function share(): BelongsTo
    {
        return $this->belongsTo(Share::class);
    }

    public static function log(
        Asset $asset,
        string $eventType,
        string $source,
        Request $request,
        ?Share $share = null
    ): self {
        $analytic = self::create([
            'asset_id' => $asset->id,
            'share_id' => $share?->id,
            'event_type' => $eventType,
            'source' => $source,
            'ip_address' => $request->ip(),
            'user_agent' => substr($request->userAgent() ?? '', 0, 512),
            'referer' => substr($request->header('Referer') ?? '', 0, 512),
        ]);

        if ($eventType === self::EVENT_VIEW) {
            $asset->increment('view_count');
            $asset->update(['last_viewed_at' => now()]);
        } elseif ($eventType === self::EVENT_DOWNLOAD) {
            $asset->increment('download_count');
        }

        return $analytic;
    }
}
