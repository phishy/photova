<?php

namespace Database\Factories;

use App\Models\ApiKey;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ApiKeyFactory extends Factory
{
    protected $model = ApiKey::class;

    public function definition(): array
    {
        $plainKey = ApiKey::PREFIX . Str::random(32);

        return [
            'user_id' => User::factory(),
            'name' => fake()->words(2, true) . ' Key',
            'key_prefix' => substr($plainKey, 0, 20),
            'key_hash' => Hash::make($plainKey),
            'key' => $plainKey,
            'status' => 'active',
            'scopes' => [],
            'last_used_at' => null,
            'expires_at' => null,
        ];
    }

    public function revoked(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'revoked',
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => now()->subDay(),
        ]);
    }
}
