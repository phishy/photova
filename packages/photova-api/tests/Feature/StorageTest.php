<?php

use App\Models\AssetMigration;
use App\Models\StorageBucket;
use App\Models\User;
use App\Services\StorageService;

test('authenticated user can list storage buckets', function () {
    $user = User::factory()->create();
    StorageBucket::factory()->count(2)->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)
        ->getJson('/api/storage');

    $response->assertOk()
        ->assertJsonStructure([
            'system' => ['id', 'name', 'provider', 'isDefault', 'assetsCount'],
            'buckets' => [
                '*' => ['id', 'name', 'provider', 'isDefault', 'isActive', 'assetsCount'],
            ],
            'rcloneAvailable',
        ])
        ->assertJsonCount(2, 'buckets');
});

test('authenticated user can list storage providers', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->getJson('/api/storage/providers');

    $response->assertOk()
        ->assertJsonStructure([
            'providers' => [
                '*' => ['id', 'name', 'type', 'fields'],
            ],
        ]);
    
    $providers = $response->json('providers');
    expect(count($providers))->toBeGreaterThan(5);
});

test('authenticated user can view their storage bucket', function () {
    $user = User::factory()->create();
    $bucket = StorageBucket::factory()->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)
        ->getJson("/api/storage/{$bucket->id}");

    $response->assertOk()
        ->assertJson([
            'bucket' => ['id' => $bucket->id, 'name' => $bucket->name],
        ]);
});

test('user cannot view another users storage bucket', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $bucket = StorageBucket::factory()->create(['user_id' => $otherUser->id]);

    $response = $this->actingAs($user)
        ->getJson("/api/storage/{$bucket->id}");

    $response->assertForbidden();
});

test('authenticated user can update their storage bucket', function () {
    $user = User::factory()->create();
    $bucket = StorageBucket::factory()->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)
        ->patchJson("/api/storage/{$bucket->id}", [
            'name' => 'Updated Bucket Name',
        ]);

    $response->assertOk()
        ->assertJson([
            'bucket' => ['name' => 'Updated Bucket Name'],
        ]);
});

test('authenticated user can delete empty storage bucket', function () {
    $user = User::factory()->create();
    $bucket = StorageBucket::factory()->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)
        ->deleteJson("/api/storage/{$bucket->id}");

    $response->assertOk();
    $this->assertDatabaseMissing('storage_buckets', ['id' => $bucket->id]);
});

test('authenticated user cannot delete bucket with assets', function () {
    $user = User::factory()->create();
    $bucket = StorageBucket::factory()->create(['user_id' => $user->id]);
    $user->assets()->create([
        'storage_bucket_id' => $bucket->id,
        'storage_key' => 'test.jpg',
        'filename' => 'test.jpg',
        'mime_type' => 'image/jpeg',
        'size' => 1000,
    ]);

    $response = $this->actingAs($user)
        ->deleteJson("/api/storage/{$bucket->id}");

    $response->assertStatus(422)
        ->assertJson(['error' => 'Cannot delete bucket with assets. Migrate them first.']);
});

test('authenticated user can set bucket as default', function () {
    $user = User::factory()->create();
    $bucket = StorageBucket::factory()->create(['user_id' => $user->id, 'is_default' => false]);

    $response = $this->actingAs($user)
        ->postJson("/api/storage/{$bucket->id}/default");

    $response->assertOk()
        ->assertJson(['bucket' => ['isDefault' => true]]);
    
    expect($bucket->fresh()->is_default)->toBeTrue();
});

test('setting bucket as default clears other defaults', function () {
    $user = User::factory()->create();
    $bucket1 = StorageBucket::factory()->create(['user_id' => $user->id, 'is_default' => true]);
    $bucket2 = StorageBucket::factory()->create(['user_id' => $user->id, 'is_default' => false]);

    $this->actingAs($user)
        ->postJson("/api/storage/{$bucket2->id}/default");

    expect($bucket1->fresh()->is_default)->toBeFalse();
    expect($bucket2->fresh()->is_default)->toBeTrue();
});

test('authenticated user can clear default to use system storage', function () {
    $user = User::factory()->create();
    $bucket = StorageBucket::factory()->create(['user_id' => $user->id, 'is_default' => true]);

    $response = $this->actingAs($user)
        ->deleteJson('/api/storage/default');

    $response->assertOk()
        ->assertJson(['message' => 'Default cleared. Using platform storage.']);
    
    expect($bucket->fresh()->is_default)->toBeFalse();
});

test('cannot set inactive bucket as default', function () {
    $user = User::factory()->create();
    $bucket = StorageBucket::factory()->inactive()->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)
        ->postJson("/api/storage/{$bucket->id}/default");

    $response->assertStatus(422)
        ->assertJson(['error' => 'Cannot set inactive bucket as default.']);
});

test('authenticated user can list migrations', function () {
    $user = User::factory()->create();
    AssetMigration::factory()->count(2)->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)
        ->getJson('/api/storage/migrations');

    $response->assertOk()
        ->assertJsonStructure([
            'migrations' => [
                '*' => ['id', 'fromBucket', 'toBucket', 'status', 'totalAssets', 'processedAssets'],
            ],
        ])
        ->assertJsonCount(2, 'migrations');
});

test('authenticated user can view migration status', function () {
    $user = User::factory()->create();
    $migration = AssetMigration::factory()->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)
        ->getJson("/api/storage/migrations/{$migration->id}");

    $response->assertOk()
        ->assertJson([
            'migration' => ['id' => $migration->id],
        ]);
});

test('user cannot view another users migration', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $migration = AssetMigration::factory()->create(['user_id' => $otherUser->id]);

    $response = $this->actingAs($user)
        ->getJson("/api/storage/migrations/{$migration->id}");

    $response->assertForbidden();
});

test('authenticated user can cancel pending migration', function () {
    $user = User::factory()->create();
    $migration = AssetMigration::factory()->create([
        'user_id' => $user->id,
        'status' => AssetMigration::STATUS_PENDING,
    ]);

    $response = $this->actingAs($user)
        ->postJson("/api/storage/migrations/{$migration->id}/cancel");

    $response->assertOk()
        ->assertJson(['migration' => ['status' => 'cancelled']]);
});

test('cannot cancel completed migration', function () {
    $user = User::factory()->create();
    $migration = AssetMigration::factory()->create([
        'user_id' => $user->id,
        'status' => AssetMigration::STATUS_COMPLETED,
    ]);

    $response = $this->actingAs($user)
        ->postJson("/api/storage/migrations/{$migration->id}/cancel");

    $response->assertStatus(422)
        ->assertJson(['error' => 'Migration cannot be cancelled.']);
});

test('cannot start migration when one is already in progress', function () {
    $user = User::factory()->create();
    $bucket = StorageBucket::factory()->create(['user_id' => $user->id]);
    $user->assets()->create([
        'storage_key' => 'test.jpg',
        'filename' => 'test.jpg',
        'mime_type' => 'image/jpeg',
        'size' => 1000,
    ]);
    AssetMigration::factory()->create([
        'user_id' => $user->id,
        'status' => AssetMigration::STATUS_PROCESSING,
    ]);

    $response = $this->actingAs($user)
        ->postJson('/api/storage/migrate', [
            'from_bucket_id' => null,
            'to_bucket_id' => $bucket->id,
        ]);

    $response->assertStatus(422)
        ->assertJson(['error' => 'You have a migration in progress. Please wait for it to complete.']);
});

test('cannot migrate to same bucket', function () {
    $user = User::factory()->create();
    $bucket = StorageBucket::factory()->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)
        ->postJson('/api/storage/migrate', [
            'from_bucket_id' => $bucket->id,
            'to_bucket_id' => $bucket->id,
        ]);

    $response->assertStatus(422)
        ->assertJson(['error' => 'Source and destination must be different.']);
});

test('cannot migrate if no assets to migrate', function () {
    $user = User::factory()->create();
    $bucket = StorageBucket::factory()->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)
        ->postJson('/api/storage/migrate', [
            'from_bucket_id' => null,
            'to_bucket_id' => $bucket->id,
        ]);

    $response->assertStatus(422)
        ->assertJson(['error' => 'No assets to migrate.']);
});

test('unauthenticated user cannot access storage endpoints', function () {
    $response = $this->getJson('/api/storage');
    $response->assertUnauthorized();
});
