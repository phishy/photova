<?php

namespace Database\Factories;

use App\Models\Share;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ShareFactory extends Factory
{
    protected $model = Share::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'slug' => Share::generateUniqueSlug(),
            'name' => $this->faker->optional()->words(3, true),
            'asset_ids' => [],
            'expires_at' => null,
            'password' => null,
            'allow_download' => true,
            'allow_zip' => true,
            'view_count' => 0,
        ];
    }

    public function withExpiration(int $hours = 24): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => now()->addHours($hours),
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => now()->subHours(1),
        ]);
    }

    public function withPassword(string $password = 'secret'): static
    {
        return $this->state(fn (array $attributes) => [
            'password' => $password,
        ]);
    }

    public function noDownload(): static
    {
        return $this->state(fn (array $attributes) => [
            'allow_download' => false,
            'allow_zip' => false,
        ]);
    }
}
