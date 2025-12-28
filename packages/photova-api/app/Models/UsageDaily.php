<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UsageDaily extends Model
{
    use HasFactory;

    protected $table = 'usage_daily';

    protected $fillable = [
        'user_id',
        'date',
        'operation',
        'request_count',
        'error_count',
        'total_latency_ms',
        'total_cost',
        'total_price',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'request_count' => 'integer',
            'error_count' => 'integer',
            'total_latency_ms' => 'integer',
            'total_cost' => 'decimal:6',
            'total_price' => 'decimal:6',
        ];
    }

    public function getTotalMarginAttribute(): float
    {
        return (float) ($this->total_price ?? 0) - (float) ($this->total_cost ?? 0);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
