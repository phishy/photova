<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShareAnalytic extends Model
{
    use HasUuids;

    const EVENT_VIEW = 'view';
    const EVENT_DOWNLOAD = 'download';
    const EVENT_ZIP_DOWNLOAD = 'zip_download';

    protected $table = 'share_analytics';

    public $timestamps = false;

    protected $fillable = [
        'share_id',
        'event_type',
        'asset_id',
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

    public function share(): BelongsTo
    {
        return $this->belongsTo(Share::class);
    }
}
