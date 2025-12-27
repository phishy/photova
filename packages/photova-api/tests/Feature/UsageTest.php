<?php

use App\Models\UsageDaily;
use App\Models\User;

test('authenticated user can get usage summary', function () {
    $user = User::factory()->create();
    UsageDaily::factory()->create([
        'user_id' => $user->id,
        'date' => now()->toDateString(),
        'operation' => 'background-remove',
        'request_count' => 10,
        'error_count' => 1,
    ]);

    $response = $this->actingAs($user)
        ->getJson('/api/usage/summary');

    $response->assertOk()
        ->assertJsonStructure([
            'summary' => [
                'totalRequests',
                'totalErrors',
                'averageLatencyMs',
                'monthlyLimit',
                'byOperation',
            ],
        ]);
});

test('authenticated user can get usage timeseries', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->getJson('/api/usage/timeseries');

    $response->assertOk()
        ->assertJsonStructure([
            'timeseries' => [
                '*' => ['date', 'requests', 'errors'],
            ],
        ]);
});

test('authenticated user can get current month usage', function () {
    $user = User::factory()->create(['monthly_limit' => 100]);
    UsageDaily::factory()->create([
        'user_id' => $user->id,
        'date' => now()->toDateString(),
        'request_count' => 25,
    ]);

    $response = $this->actingAs($user)
        ->getJson('/api/usage/current');

    $response->assertOk()
        ->assertJsonStructure([
            'current' => ['used', 'limit', 'remaining', 'period'],
        ])
        ->assertJson([
            'current' => [
                'limit' => 100,
            ],
        ]);
});

test('unauthenticated user cannot access usage', function () {
    $response = $this->getJson('/api/usage/summary');

    $response->assertUnauthorized();
});
