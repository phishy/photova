<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class ProviderPricing extends Model
{
    use HasFactory;

    protected $table = 'provider_pricing';

    protected $fillable = [
        'provider',
        'operation',
        'model',
        'unit_type',
        'cost_per_unit',
        'price_per_unit',
        'currency',
        'effective_at',
        'is_active',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'cost_per_unit' => 'decimal:6',
            'price_per_unit' => 'decimal:6',
            'effective_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeForOperation(Builder $query, string $provider, string $operation, ?string $model = null): Builder
    {
        return $query
            ->where('provider', $provider)
            ->where('operation', $operation)
            ->when($model, fn($q) => $q->where('model', $model))
            ->where('effective_at', '<=', now())
            ->orderByDesc('effective_at');
    }

    public function getMarginAttribute(): float
    {
        return (float) $this->price_per_unit - (float) $this->cost_per_unit;
    }

    public function getMarginPercentAttribute(): float
    {
        $price = (float) $this->price_per_unit;
        if ($price == 0) {
            return 0;
        }
        return round(($this->margin / $price) * 100, 2);
    }
}
