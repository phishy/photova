<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UsageLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'api_key_id',
        'operation',
        'provider',
        'model',
        'status',
        'latency_ms',
        'cost',
        'price',
        'request_id',
        'error_message',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'latency_ms' => 'integer',
            'cost' => 'decimal:6',
            'price' => 'decimal:6',
        ];
    }

    public function getMarginAttribute(): float
    {
        return (float) ($this->price ?? 0) - (float) ($this->cost ?? 0);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function apiKey(): BelongsTo
    {
        return $this->belongsTo(ApiKey::class);
    }
}
