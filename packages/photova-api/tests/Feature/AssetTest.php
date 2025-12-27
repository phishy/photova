<?php

use App\Models\Asset;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');
});

test('authenticated user can list their assets', function () {
    $user = User::factory()->create();
    Asset::factory()->count(3)->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)
        ->getJson('/api/assets');

    $response->assertOk()
        ->assertJsonStructure([
            'assets' => [
                '*' => ['id', 'bucket', 'filename', 'mimeType', 'size'],
            ],
        ])
        ->assertJsonCount(3, 'assets');
});

test('authenticated user can upload a file', function () {
    $user = User::factory()->create();
    $file = UploadedFile::fake()->image('test.png', 100, 100);

    $response = $this->actingAs($user)
        ->postJson('/api/assets', [
            'file' => $file,
        ]);

    $response->assertCreated()
        ->assertJsonStructure([
            'asset' => ['id', 'bucket', 'filename', 'mimeType', 'size'],
        ]);

    $this->assertDatabaseHas('assets', [
        'user_id' => $user->id,
        'filename' => 'test.png',
    ]);
});

test('authenticated user can upload base64 image', function () {
    $user = User::factory()->create();
    $imageData = 'data:image/png;base64,' . base64_encode(file_get_contents(__DIR__ . '/../fixtures/test.png') ?: random_bytes(100));

    $response = $this->actingAs($user)
        ->postJson('/api/assets', [
            'image' => $imageData,
            'filename' => 'uploaded.png',
        ]);

    $response->assertCreated();
});

test('authenticated user can view their asset', function () {
    $user = User::factory()->create();
    $asset = Asset::factory()->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)
        ->getJson("/api/assets/{$asset->id}");

    $response->assertOk()
        ->assertJson([
            'asset' => ['id' => $asset->id],
        ]);
});

test('user cannot view another users asset', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $asset = Asset::factory()->create(['user_id' => $otherUser->id]);

    $response = $this->actingAs($user)
        ->getJson("/api/assets/{$asset->id}");

    $response->assertForbidden();
});

test('authenticated user can delete their asset', function () {
    $user = User::factory()->create();
    $asset = Asset::factory()->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)
        ->deleteJson("/api/assets/{$asset->id}");

    $response->assertOk();
    $this->assertDatabaseMissing('assets', ['id' => $asset->id]);
});

test('unauthenticated user cannot access assets', function () {
    $response = $this->getJson('/api/assets');

    $response->assertUnauthorized();
});

test('assets can be filtered by bucket', function () {
    $user = User::factory()->create();
    Asset::factory()->count(2)->create(['user_id' => $user->id, 'bucket' => 'assets']);
    Asset::factory()->count(3)->create(['user_id' => $user->id, 'bucket' => 'uploads']);

    $response = $this->actingAs($user)
        ->getJson('/api/assets?bucket=uploads');

    $response->assertOk()
        ->assertJsonCount(3, 'assets');
});
