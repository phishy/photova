<?php

namespace Database\Factories;

use App\Models\UsageDaily;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class UsageDailyFactory extends Factory
{
    protected $model = UsageDaily::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'date' => fake()->dateTimeBetween('-30 days', 'now')->format('Y-m-d'),
            'operation' => fake()->randomElement([
                'background-remove',
                'upscale',
                'unblur',
                'colorize',
                'inpaint',
                'restore',
            ]),
            'request_count' => fake()->numberBetween(1, 100),
            'error_count' => fake()->numberBetween(0, 10),
            'total_latency_ms' => fake()->numberBetween(1000, 100000),
        ];
    }
}
