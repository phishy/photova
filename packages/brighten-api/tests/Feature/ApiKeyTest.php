<?php

use App\Models\ApiKey;
use App\Models\User;

test('authenticated user can list their api keys', function () {
    $user = User::factory()->create();
    ApiKey::factory()->count(3)->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)
        ->getJson('/api/keys');

    $response->assertOk()
        ->assertJsonStructure([
            'keys' => [
                '*' => ['id', 'name', 'keyPrefix', 'status', 'scopes'],
            ],
        ])
        ->assertJsonCount(3, 'keys');
});

test('authenticated user can create an api key', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->postJson('/api/keys', [
            'name' => 'Test Key',
        ]);

    $response->assertCreated()
        ->assertJsonStructure([
            'key' => ['id', 'name', 'keyPrefix', 'status'],
            'plainKey',
        ]);

    expect($response->json('plainKey'))->toStartWith('br_live_');
    $this->assertDatabaseHas('api_keys', ['name' => 'Test Key', 'user_id' => $user->id]);
});

test('authenticated user can view their api key', function () {
    $user = User::factory()->create();
    $apiKey = ApiKey::factory()->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)
        ->getJson("/api/keys/{$apiKey->id}");

    $response->assertOk()
        ->assertJson([
            'key' => ['id' => $apiKey->id, 'name' => $apiKey->name],
        ]);
});

test('user cannot view another users api key', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $apiKey = ApiKey::factory()->create(['user_id' => $otherUser->id]);

    $response = $this->actingAs($user)
        ->getJson("/api/keys/{$apiKey->id}");

    $response->assertForbidden();
});

test('authenticated user can update their api key', function () {
    $user = User::factory()->create();
    $apiKey = ApiKey::factory()->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)
        ->patchJson("/api/keys/{$apiKey->id}", [
            'name' => 'Updated Key Name',
        ]);

    $response->assertOk()
        ->assertJson([
            'key' => ['name' => 'Updated Key Name'],
        ]);
});

test('authenticated user can revoke their api key', function () {
    $user = User::factory()->create();
    $apiKey = ApiKey::factory()->create(['user_id' => $user->id, 'status' => 'active']);

    $response = $this->actingAs($user)
        ->patchJson("/api/keys/{$apiKey->id}", [
            'status' => 'revoked',
        ]);

    $response->assertOk()
        ->assertJson([
            'key' => ['status' => 'revoked'],
        ]);
});

test('authenticated user can delete their api key', function () {
    $user = User::factory()->create();
    $apiKey = ApiKey::factory()->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)
        ->deleteJson("/api/keys/{$apiKey->id}");

    $response->assertOk();
    $this->assertDatabaseMissing('api_keys', ['id' => $apiKey->id]);
});

test('authenticated user can regenerate their api key', function () {
    $user = User::factory()->create();
    $apiKey = ApiKey::factory()->create(['user_id' => $user->id]);
    $oldPrefix = $apiKey->key_prefix;

    $response = $this->actingAs($user)
        ->postJson("/api/keys/{$apiKey->id}/regenerate");

    $response->assertOk()
        ->assertJsonStructure([
            'key' => ['id', 'keyPrefix'],
            'plainKey',
        ]);

    expect($response->json('key.keyPrefix'))->not->toBe($oldPrefix);
});

test('unauthenticated user cannot access api keys', function () {
    $response = $this->getJson('/api/keys');

    $response->assertUnauthorized();
});
