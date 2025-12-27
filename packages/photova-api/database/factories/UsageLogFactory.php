<?php

namespace Database\Factories;

use App\Models\UsageLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class UsageLogFactory extends Factory
{
    protected $model = UsageLog::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'api_key_id' => null,
            'operation' => fake()->randomElement([
                'background-remove',
                'upscale',
                'unblur',
                'colorize',
                'inpaint',
                'restore',
            ]),
            'status' => fake()->randomElement(['success', 'error']),
            'latency_ms' => fake()->numberBetween(100, 5000),
            'request_id' => Str::uuid(),
            'error_message' => null,
            'metadata' => [],
        ];
    }

    public function error(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'error',
            'error_message' => fake()->sentence(),
        ]);
    }
}
