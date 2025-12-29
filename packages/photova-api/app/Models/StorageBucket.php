<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StorageBucket extends Model
{
    use HasFactory, HasUuids;

    public const PROVIDER_AWS = 'aws';
    public const PROVIDER_DIGITALOCEAN = 'digitalocean';
    public const PROVIDER_CLOUDFLARE = 'cloudflare';
    public const PROVIDER_BACKBLAZE = 'backblaze';
    public const PROVIDER_WASABI = 'wasabi';
    public const PROVIDER_MINIO = 'minio';
    public const PROVIDER_GDRIVE = 'gdrive';
    public const PROVIDER_DROPBOX = 'dropbox';
    public const PROVIDER_ONEDRIVE = 'onedrive';
    public const PROVIDER_SFTP = 'sftp';
    public const PROVIDER_FTP = 'ftp';
    public const PROVIDER_WEBDAV = 'webdav';
    public const PROVIDER_OTHER = 'other';

    public const PROVIDERS = [
        self::PROVIDER_AWS,
        self::PROVIDER_DIGITALOCEAN,
        self::PROVIDER_CLOUDFLARE,
        self::PROVIDER_BACKBLAZE,
        self::PROVIDER_WASABI,
        self::PROVIDER_MINIO,
        self::PROVIDER_GDRIVE,
        self::PROVIDER_DROPBOX,
        self::PROVIDER_ONEDRIVE,
        self::PROVIDER_SFTP,
        self::PROVIDER_FTP,
        self::PROVIDER_WEBDAV,
        self::PROVIDER_OTHER,
    ];

    public const S3_PROVIDERS = [
        self::PROVIDER_AWS,
        self::PROVIDER_DIGITALOCEAN,
        self::PROVIDER_CLOUDFLARE,
        self::PROVIDER_BACKBLAZE,
        self::PROVIDER_WASABI,
        self::PROVIDER_MINIO,
        self::PROVIDER_OTHER,
    ];

    public const OAUTH_PROVIDERS = [
        self::PROVIDER_GDRIVE,
        self::PROVIDER_DROPBOX,
        self::PROVIDER_ONEDRIVE,
    ];

    protected $fillable = [
        'user_id',
        'name',
        'provider',
        'config',
        'credentials',
        'is_default',
        'is_active',
        'last_connected_at',
    ];

    protected function casts(): array
    {
        return [
            'config' => 'array',
            'credentials' => 'encrypted:array',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
            'last_connected_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function assets(): HasMany
    {
        return $this->hasMany(Asset::class, 'storage_bucket_id');
    }

    public function isS3Compatible(): bool
    {
        return in_array($this->provider, self::S3_PROVIDERS, true);
    }

    public function isOAuthProvider(): bool
    {
        return in_array($this->provider, self::OAUTH_PROVIDERS, true);
    }

    public function getRcloneType(): string
    {
        return match ($this->provider) {
            self::PROVIDER_AWS,
            self::PROVIDER_DIGITALOCEAN,
            self::PROVIDER_CLOUDFLARE,
            self::PROVIDER_BACKBLAZE,
            self::PROVIDER_WASABI,
            self::PROVIDER_MINIO,
            self::PROVIDER_OTHER => 's3',
            self::PROVIDER_GDRIVE => 'drive',
            self::PROVIDER_DROPBOX => 'dropbox',
            self::PROVIDER_ONEDRIVE => 'onedrive',
            self::PROVIDER_SFTP => 'sftp',
            self::PROVIDER_FTP => 'ftp',
            self::PROVIDER_WEBDAV => 'webdav',
            default => 's3',
        };
    }

    public function buildRcloneConfig(): array
    {
        $config = array_merge(
            ['type' => $this->getRcloneType()],
            $this->config ?? [],
            $this->credentials ?? []
        );

        if ($this->isS3Compatible()) {
            $config['provider'] = $this->getS3Provider();
        }

        return $config;
    }

    private function getS3Provider(): string
    {
        return match ($this->provider) {
            self::PROVIDER_AWS => 'AWS',
            self::PROVIDER_DIGITALOCEAN => 'DigitalOcean',
            self::PROVIDER_CLOUDFLARE => 'Cloudflare',
            self::PROVIDER_BACKBLAZE => 'B2',
            self::PROVIDER_WASABI => 'Wasabi',
            self::PROVIDER_MINIO => 'Minio',
            default => 'Other',
        };
    }

    public function markConnected(): void
    {
        $this->update(['last_connected_at' => now()]);
    }
}
