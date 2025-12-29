<?php

namespace Database\Factories;

use App\Models\StorageBucket;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class StorageBucketFactory extends Factory
{
    protected $model = StorageBucket::class;

    public function definition(): array
    {
        return [
            'id' => Str::uuid(),
            'user_id' => User::factory(),
            'name' => fake()->company() . ' Storage',
            'provider' => StorageBucket::PROVIDER_AWS,
            'config' => [
                'bucket' => fake()->slug(),
                'region' => 'us-east-1',
            ],
            'credentials' => [
                'access_key_id' => 'AKIA' . strtoupper(Str::random(16)),
                'secret_access_key' => Str::random(40),
            ],
            'is_default' => false,
            'is_active' => true,
            'last_connected_at' => null,
        ];
    }

    public function default(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_default' => true,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function aws(): static
    {
        return $this->state(fn (array $attributes) => [
            'provider' => StorageBucket::PROVIDER_AWS,
            'config' => [
                'bucket' => fake()->slug(),
                'region' => fake()->randomElement(['us-east-1', 'us-west-2', 'eu-west-1']),
            ],
        ]);
    }

    public function digitalocean(): static
    {
        return $this->state(fn (array $attributes) => [
            'provider' => StorageBucket::PROVIDER_DIGITALOCEAN,
            'config' => [
                'bucket' => fake()->slug(),
                'region' => fake()->randomElement(['nyc3', 'sfo3', 'ams3', 'sgp1']),
            ],
        ]);
    }

    public function sftp(): static
    {
        return $this->state(fn (array $attributes) => [
            'provider' => StorageBucket::PROVIDER_SFTP,
            'config' => [
                'host' => fake()->domainName(),
                'port' => 22,
                'root' => '/var/www/uploads',
            ],
            'credentials' => [
                'user' => fake()->userName(),
                'pass' => Str::random(16),
            ],
        ]);
    }

    public function gdrive(): static
    {
        return $this->state(fn (array $attributes) => [
            'provider' => StorageBucket::PROVIDER_GDRIVE,
            'config' => [
                'root_folder_id' => Str::random(33),
            ],
            'credentials' => [
                'token' => json_encode([
                    'access_token' => Str::random(100),
                    'refresh_token' => Str::random(50),
                    'expiry' => now()->addHour()->toIso8601String(),
                ]),
            ],
        ]);
    }
}
