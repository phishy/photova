<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class Share extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'user_id',
        'slug',
        'name',
        'asset_ids',
        'expires_at',
        'password',
        'allow_download',
        'allow_zip',
        'view_count',
    ];

    protected function casts(): array
    {
        return [
            'asset_ids' => 'array',
            'expires_at' => 'datetime',
            'allow_download' => 'boolean',
            'allow_zip' => 'boolean',
            'view_count' => 'integer',
            'password' => 'hashed',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Share $share) {
            if (empty($share->slug)) {
                $share->slug = static::generateUniqueSlug();
            }
        });
    }

    public static function generateUniqueSlug(): string
    {
        do {
            $slug = Str::random(12);
        } while (static::where('slug', $slug)->exists());

        return $slug;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function isPasswordProtected(): bool
    {
        return $this->password !== null;
    }

    public function checkPassword(string $password): bool
    {
        if (!$this->isPasswordProtected()) {
            return true;
        }

        return password_verify($password, $this->password);
    }

    public function incrementViewCount(): void
    {
        $this->increment('view_count');
    }

    public function getAssets()
    {
        return Asset::whereIn('id', $this->asset_ids)
            ->where('user_id', $this->user_id)
            ->get();
    }

    public function getUrl(): string
    {
        return url('/s/' . $this->slug);
    }

    public function analytics(): HasMany
    {
        return $this->hasMany(ShareAnalytic::class);
    }

    public function logAnalytic(string $eventType, Request $request, ?string $assetId = null): ShareAnalytic
    {
        return $this->analytics()->create([
            'event_type' => $eventType,
            'asset_id' => $assetId,
            'ip_address' => $request->ip(),
            'user_agent' => substr($request->userAgent() ?? '', 0, 512),
            'referer' => substr($request->header('Referer') ?? '', 0, 512),
        ]);
    }

    public function getAnalyticsSummary(): array
    {
        $analytics = $this->analytics()
            ->selectRaw('event_type, COUNT(*) as count')
            ->groupBy('event_type')
            ->pluck('count', 'event_type')
            ->toArray();

        return [
            'views' => $analytics[ShareAnalytic::EVENT_VIEW] ?? 0,
            'downloads' => $analytics[ShareAnalytic::EVENT_DOWNLOAD] ?? 0,
            'zipDownloads' => $analytics[ShareAnalytic::EVENT_ZIP_DOWNLOAD] ?? 0,
            'total' => array_sum($analytics),
        ];
    }
}
