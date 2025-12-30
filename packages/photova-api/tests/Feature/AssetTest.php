<?php

use App\Jobs\AnalyzeAsset;
use App\Models\Asset;
use App\Models\StorageBucket;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('assets');
    Bus::fake([AnalyzeAsset::class]);
});

test('authenticated user can list their assets', function () {
    $user = User::factory()->create();
    Asset::factory()->count(3)->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)
        ->getJson('/api/assets');

    $response->assertOk()
        ->assertJsonStructure([
            'assets' => [
                '*' => ['id', 'storageBucketId', 'filename', 'mimeType', 'size'],
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
            'asset' => ['id', 'storageBucketId', 'filename', 'mimeType', 'size'],
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

test('assets can be filtered by storage bucket', function () {
    $user = User::factory()->create();
    $bucket = StorageBucket::factory()->create(['user_id' => $user->id]);
    
    Asset::factory()->count(2)->create(['user_id' => $user->id, 'storage_bucket_id' => null]);
    Asset::factory()->count(3)->create(['user_id' => $user->id, 'storage_bucket_id' => $bucket->id]);

    $response = $this->actingAs($user)
        ->getJson("/api/assets?storage_bucket_id={$bucket->id}");

    $response->assertOk()
        ->assertJsonCount(3, 'assets');
});

test('assets can be filtered by system storage', function () {
    $user = User::factory()->create();
    $bucket = StorageBucket::factory()->create(['user_id' => $user->id]);
    
    Asset::factory()->count(2)->create(['user_id' => $user->id, 'storage_bucket_id' => null]);
    Asset::factory()->count(3)->create(['user_id' => $user->id, 'storage_bucket_id' => $bucket->id]);

    $response = $this->actingAs($user)
        ->getJson('/api/assets?storage_bucket_id=system');

    $response->assertOk()
        ->assertJsonCount(2, 'assets');
});

test('authenticated user can get thumbnail of their image asset', function () {
    $user = User::factory()->create();
    
    // Create a real image file
    $imageContent = file_get_contents(__DIR__ . '/../fixtures/test.png');
    $storageKey = 'test-' . uniqid() . '.png';
    Storage::disk('assets')->put($storageKey, $imageContent);
    
    $asset = Asset::factory()->create([
        'user_id' => $user->id,
        'storage_key' => $storageKey,
        'mime_type' => 'image/png',
    ]);

    $response = $this->actingAs($user)
        ->get("/api/assets/{$asset->id}/thumb");

    $response->assertOk()
        ->assertHeader('Content-Type', 'image/jpeg');
    
    // Check cache header contains expected directives (order may vary)
    $cacheControl = $response->headers->get('Cache-Control');
    expect($cacheControl)->toContain('max-age=31536000')
        ->toContain('public')
        ->toContain('immutable');
});

test('thumbnail endpoint accepts width and height parameters', function () {
    $user = User::factory()->create();
    
    $imageContent = file_get_contents(__DIR__ . '/../fixtures/test.png');
    $storageKey = 'test-' . uniqid() . '.png';
    Storage::disk('assets')->put($storageKey, $imageContent);
    
    $asset = Asset::factory()->create([
        'user_id' => $user->id,
        'storage_key' => $storageKey,
        'mime_type' => 'image/png',
    ]);

    $response = $this->actingAs($user)
        ->get("/api/assets/{$asset->id}/thumb?w=100&h=100");

    $response->assertOk()
        ->assertHeader('Content-Type', 'image/jpeg');
});

test('thumbnail endpoint clamps dimensions to valid range', function () {
    $user = User::factory()->create();
    
    $imageContent = file_get_contents(__DIR__ . '/../fixtures/test.png');
    $storageKey = 'test-' . uniqid() . '.png';
    Storage::disk('assets')->put($storageKey, $imageContent);
    
    $asset = Asset::factory()->create([
        'user_id' => $user->id,
        'storage_key' => $storageKey,
        'mime_type' => 'image/png',
    ]);

    // Test with dimensions outside valid range (should be clamped)
    $response = $this->actingAs($user)
        ->get("/api/assets/{$asset->id}/thumb?w=5000&h=5000");

    $response->assertOk();
});

test('thumbnail endpoint returns 400 for non-image assets', function () {
    $user = User::factory()->create();
    
    $storageKey = 'test-' . uniqid() . '.txt';
    Storage::disk('assets')->put($storageKey, 'Hello, World!');
    
    $asset = Asset::factory()->create([
        'user_id' => $user->id,
        'storage_key' => $storageKey,
        'mime_type' => 'text/plain',
    ]);

    $response = $this->actingAs($user)
        ->get("/api/assets/{$asset->id}/thumb");

    $response->assertStatus(400);
});

test('user cannot get thumbnail of another users asset', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    
    $asset = Asset::factory()->create([
        'user_id' => $otherUser->id,
        'mime_type' => 'image/png',
    ]);

    $response = $this->actingAs($user)
        ->get("/api/assets/{$asset->id}/thumb");

    $response->assertForbidden();
});

test('unauthenticated user cannot access thumbnail endpoint', function () {
    $user = User::factory()->create();
    $asset = Asset::factory()->create(['user_id' => $user->id]);

    $response = $this->get("/api/assets/{$asset->id}/thumb");

    $response->assertUnauthorized();
});

test('thumbnail endpoint caches generated thumbnails', function () {
    Storage::fake('thumbs');
    
    $user = User::factory()->create();
    
    $imageContent = file_get_contents(__DIR__ . '/../fixtures/test.png');
    $storageKey = 'test-' . uniqid() . '.png';
    Storage::disk('assets')->put($storageKey, $imageContent);
    
    $asset = Asset::factory()->create([
        'user_id' => $user->id,
        'storage_key' => $storageKey,
        'mime_type' => 'image/png',
    ]);

    // First request - cache MISS
    $response1 = $this->actingAs($user)
        ->get("/api/assets/{$asset->id}/thumb?w=100&h=100");
    
    $response1->assertOk()
        ->assertHeader('X-Thumbnail-Cache', 'MISS');

    // Second request - cache HIT
    $response2 = $this->actingAs($user)
        ->get("/api/assets/{$asset->id}/thumb?w=100&h=100");
    
    $response2->assertOk()
        ->assertHeader('X-Thumbnail-Cache', 'HIT');
});

test('thumbnail cache is invalidated when asset is updated', function () {
    Storage::fake('thumbs');
    
    $user = User::factory()->create();
    
    $imageContent = file_get_contents(__DIR__ . '/../fixtures/test.png');
    $storageKey = 'test-' . uniqid() . '.png';
    Storage::disk('assets')->put($storageKey, $imageContent);
    
    $asset = Asset::factory()->create([
        'user_id' => $user->id,
        'storage_key' => $storageKey,
        'mime_type' => 'image/png',
    ]);

    // First request - cache MISS
    $response1 = $this->actingAs($user)
        ->get("/api/assets/{$asset->id}/thumb?w=100&h=100");
    
    $response1->assertOk()
        ->assertHeader('X-Thumbnail-Cache', 'MISS');

    // Update asset timestamp to a future time (simulates content change)
    $asset->updated_at = now()->addMinute();
    $asset->save();
    $asset->refresh();

    // Request after update - should be cache MISS (timestamp changed)
    $response2 = $this->actingAs($user)
        ->get("/api/assets/{$asset->id}/thumb?w=100&h=100");
    
    $response2->assertOk()
        ->assertHeader('X-Thumbnail-Cache', 'MISS');
});
