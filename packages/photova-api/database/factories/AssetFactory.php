<?php

namespace Database\Factories;

use App\Models\Asset;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class AssetFactory extends Factory
{
    protected $model = Asset::class;

    public function definition(): array
    {
        return [
            'id' => Str::uuid(),
            'user_id' => User::factory(),
            'bucket' => 'assets',
            'storage_key' => Str::uuid() . '.png',
            'filename' => fake()->word() . '.png',
            'mime_type' => 'image/png',
            'size' => fake()->numberBetween(1000, 5000000),
            'metadata' => [],
        ];
    }
}
