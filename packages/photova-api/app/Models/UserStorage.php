<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class UserStorage extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'user_id',
        'name',
        'driver',
        'config',
        'is_default',
        'last_scanned_at',
    ];

    protected function casts(): array
    {
        return [
            'config' => 'encrypted:array',
            'is_default' => 'boolean',
            'last_scanned_at' => 'datetime',
        ];
    }

    protected $hidden = [
        'config', // Never expose raw config in API responses
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get a configured filesystem disk for this storage.
     */
    public function getDisk(): \Illuminate\Contracts\Filesystem\Filesystem
    {
        $config = $this->config;

        return match ($this->driver) {
            's3', 'r2' => Storage::build([
                'driver' => 's3',
                'key' => $config['key'] ?? null,
                'secret' => $config['secret'] ?? null,
                'region' => $config['region'] ?? 'us-east-1',
                'bucket' => $config['bucket'] ?? null,
                'endpoint' => $config['endpoint'] ?? null,
                'use_path_style_endpoint' => $config['use_path_style_endpoint'] ?? false,
            ]),
            'gcs' => Storage::build([
                'driver' => 'gcs',
                'project_id' => $config['project_id'] ?? null,
                'key_file' => $config['key_file'] ?? null,
                'bucket' => $config['bucket'] ?? null,
            ]),
            'ftp' => Storage::build([
                'driver' => 'ftp',
                'host' => $config['host'] ?? null,
                'username' => $config['username'] ?? null,
                'password' => $config['password'] ?? null,
                'port' => $config['port'] ?? 21,
                'root' => $config['root'] ?? '/',
            ]),
            'sftp' => Storage::build([
                'driver' => 'sftp',
                'host' => $config['host'] ?? null,
                'username' => $config['username'] ?? null,
                'password' => $config['password'] ?? null,
                'privateKey' => $config['private_key'] ?? null,
                'port' => $config['port'] ?? 22,
                'root' => $config['root'] ?? '/',
            ]),
            'local' => Storage::build([
                'driver' => 'local',
                'root' => $config['root'] ?? storage_path('app'),
            ]),
            default => throw new \InvalidArgumentException("Unsupported driver: {$this->driver}"),
        };
    }

    /**
     * Get config fields safe for API response (no secrets).
     */
    public function getSafeConfigAttribute(): array
    {
        $config = $this->config;
        $safe = [];

        // Only expose non-sensitive fields
        $safeFields = ['bucket', 'region', 'endpoint', 'host', 'port', 'root', 'project_id'];

        foreach ($safeFields as $field) {
            if (isset($config[$field])) {
                $safe[$field] = $config[$field];
            }
        }

        // Indicate if secrets are set (but don't expose them)
        $secretFields = ['key', 'secret', 'password', 'private_key', 'key_file'];
        foreach ($secretFields as $field) {
            if (!empty($config[$field])) {
                $safe[$field . '_set'] = true;
            }
        }

        return $safe;
    }

    /**
     * Supported drivers and their required config fields.
     */
    public static function getDrivers(): array
    {
        return [
            's3' => [
                'name' => 'Amazon S3',
                'fields' => [
                    ['name' => 'key', 'label' => 'Access Key ID', 'type' => 'text', 'required' => true],
                    ['name' => 'secret', 'label' => 'Secret Access Key', 'type' => 'password', 'required' => true],
                    ['name' => 'bucket', 'label' => 'Bucket', 'type' => 'text', 'required' => true],
                    ['name' => 'region', 'label' => 'Region', 'type' => 'text', 'required' => true, 'default' => 'us-east-1'],
                    ['name' => 'endpoint', 'label' => 'Custom Endpoint (optional)', 'type' => 'text', 'required' => false],
                ],
            ],
            'r2' => [
                'name' => 'Cloudflare R2',
                'fields' => [
                    ['name' => 'key', 'label' => 'Access Key ID', 'type' => 'text', 'required' => true],
                    ['name' => 'secret', 'label' => 'Secret Access Key', 'type' => 'password', 'required' => true],
                    ['name' => 'bucket', 'label' => 'Bucket', 'type' => 'text', 'required' => true],
                    ['name' => 'endpoint', 'label' => 'Endpoint URL', 'type' => 'text', 'required' => true, 'placeholder' => 'https://<account_id>.r2.cloudflarestorage.com'],
                ],
            ],
            'gcs' => [
                'name' => 'Google Cloud Storage',
                'fields' => [
                    ['name' => 'project_id', 'label' => 'Project ID', 'type' => 'text', 'required' => true],
                    ['name' => 'key_file', 'label' => 'Service Account JSON', 'type' => 'textarea', 'required' => true],
                    ['name' => 'bucket', 'label' => 'Bucket', 'type' => 'text', 'required' => true],
                ],
            ],
            'ftp' => [
                'name' => 'FTP',
                'fields' => [
                    ['name' => 'host', 'label' => 'Host', 'type' => 'text', 'required' => true],
                    ['name' => 'port', 'label' => 'Port', 'type' => 'number', 'required' => false, 'default' => 21],
                    ['name' => 'username', 'label' => 'Username', 'type' => 'text', 'required' => true],
                    ['name' => 'password', 'label' => 'Password', 'type' => 'password', 'required' => true],
                    ['name' => 'root', 'label' => 'Root Path', 'type' => 'text', 'required' => false, 'default' => '/'],
                ],
            ],
            'sftp' => [
                'name' => 'SFTP',
                'fields' => [
                    ['name' => 'host', 'label' => 'Host', 'type' => 'text', 'required' => true],
                    ['name' => 'port', 'label' => 'Port', 'type' => 'number', 'required' => false, 'default' => 22],
                    ['name' => 'username', 'label' => 'Username', 'type' => 'text', 'required' => true],
                    ['name' => 'password', 'label' => 'Password (or use Private Key)', 'type' => 'password', 'required' => false],
                    ['name' => 'private_key', 'label' => 'Private Key (PEM)', 'type' => 'textarea', 'required' => false],
                    ['name' => 'root', 'label' => 'Root Path', 'type' => 'text', 'required' => false, 'default' => '/'],
                ],
            ],
            'local' => [
                'name' => 'Local Filesystem',
                'fields' => [
                    ['name' => 'root', 'label' => 'Root Path', 'type' => 'text', 'required' => true, 'placeholder' => '/var/www/images'],
                ],
            ],
        ];
    }
}
