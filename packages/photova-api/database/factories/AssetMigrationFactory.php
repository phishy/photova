<?php

namespace Database\Factories;

use App\Models\AssetMigration;
use App\Models\StorageBucket;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class AssetMigrationFactory extends Factory
{
    protected $model = AssetMigration::class;

    public function definition(): array
    {
        return [
            'id' => Str::uuid(),
            'user_id' => User::factory(),
            'from_bucket_id' => null,
            'to_bucket_id' => StorageBucket::factory(),
            'status' => AssetMigration::STATUS_PENDING,
            'total_assets' => fake()->numberBetween(1, 100),
            'processed_assets' => 0,
            'failed_assets' => 0,
            'bytes_transferred' => 0,
            'delete_source' => false,
            'error_log' => null,
            'started_at' => null,
            'completed_at' => null,
        ];
    }

    public function processing(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => AssetMigration::STATUS_PROCESSING,
            'started_at' => now(),
            'processed_assets' => (int) ($attributes['total_assets'] * 0.5),
            'bytes_transferred' => fake()->numberBetween(1000000, 100000000),
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => AssetMigration::STATUS_COMPLETED,
            'started_at' => now()->subMinutes(10),
            'completed_at' => now(),
            'processed_assets' => $attributes['total_assets'],
            'bytes_transferred' => fake()->numberBetween(10000000, 500000000),
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => AssetMigration::STATUS_FAILED,
            'started_at' => now()->subMinutes(5),
            'completed_at' => now(),
            'processed_assets' => (int) ($attributes['total_assets'] * 0.3),
            'failed_assets' => (int) ($attributes['total_assets'] * 0.7),
            'error_log' => [
                ['asset_id' => Str::uuid(), 'message' => 'Connection timeout', 'timestamp' => now()->toIso8601String()],
            ],
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => AssetMigration::STATUS_CANCELLED,
            'started_at' => now()->subMinutes(2),
            'completed_at' => now(),
        ]);
    }

    public function withDeleteSource(): static
    {
        return $this->state(fn (array $attributes) => [
            'delete_source' => true,
        ]);
    }
}
