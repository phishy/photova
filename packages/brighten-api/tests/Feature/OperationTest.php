<?php

use App\Models\ApiKey;
use App\Models\User;
use App\Models\UsageDaily;
use App\Models\UsageLog;
use App\Services\ProviderManager;
use Mockery\MockInterface;

beforeEach(function () {
    config(['photova.auth.enabled' => true]);
});

test('invalid operation returns 404 due to route constraint', function () {
    $user = User::factory()->create();
    $apiKey = ApiKey::factory()->create(['user_id' => $user->id]);

    $response = $this->withHeader('Authorization', "Bearer {$apiKey->key}")
        ->postJson('/api/v1/invalid-operation', [
            'image' => 'data:image/png;base64,iVBORw0KGgo=',
        ]);

    $response->assertNotFound();
});

test('missing image returns validation error', function () {
    $user = User::factory()->create();
    $apiKey = ApiKey::factory()->create(['user_id' => $user->id]);

    $this->mock(ProviderManager::class, function (MockInterface $mock) {
        $mock->shouldReceive('execute')->never();
    });

    $response = $this->withHeader('Authorization', "Bearer {$apiKey->key}")
        ->postJson('/api/v1/background-remove', []);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['image']);
});

test('valid operation returns processed image', function () {
    $user = User::factory()->create();
    $apiKey = ApiKey::factory()->create(['user_id' => $user->id]);

    $this->mock(ProviderManager::class, function (MockInterface $mock) {
        $mock->shouldReceive('execute')
            ->once()
            ->with('background-remove', 'data:image/png;base64,iVBORw0KGgo=', [])
            ->andReturn([
                'image' => 'data:image/png;base64,PROCESSED',
                'provider' => 'replicate',
                'model' => 'test-model',
            ]);
    });

    $response = $this->withHeader('Authorization', "Bearer {$apiKey->key}")
        ->postJson('/api/v1/background-remove', [
            'image' => 'data:image/png;base64,iVBORw0KGgo=',
        ]);

    $response->assertOk()
        ->assertJsonStructure([
            'image',
            'metadata' => ['provider', 'model', 'processingTime', 'requestId'],
        ])
        ->assertJsonPath('image', 'data:image/png;base64,PROCESSED')
        ->assertJsonPath('metadata.provider', 'replicate');
});

test('operation with options passes options to provider', function () {
    $user = User::factory()->create();
    $apiKey = ApiKey::factory()->create(['user_id' => $user->id]);

    $this->mock(ProviderManager::class, function (MockInterface $mock) {
        $mock->shouldReceive('execute')
            ->once()
            ->with('upscale', 'data:image/png;base64,iVBORw0KGgo=', ['scale' => 2])
            ->andReturn([
                'image' => 'data:image/png;base64,UPSCALED',
                'provider' => 'replicate',
            ]);
    });

    $response = $this->withHeader('Authorization', "Bearer {$apiKey->key}")
        ->postJson('/api/v1/upscale', [
            'image' => 'data:image/png;base64,iVBORw0KGgo=',
            'options' => ['scale' => 2],
        ]);

    $response->assertOk()
        ->assertJsonPath('image', 'data:image/png;base64,UPSCALED');
});

test('provider failure returns 500 with error', function () {
    $user = User::factory()->create();
    $apiKey = ApiKey::factory()->create(['user_id' => $user->id]);

    $this->mock(ProviderManager::class, function (MockInterface $mock) {
        $mock->shouldReceive('execute')
            ->once()
            ->andThrow(new Exception('Provider API error'));
    });

    $response = $this->withHeader('Authorization', "Bearer {$apiKey->key}")
        ->postJson('/api/v1/background-remove', [
            'image' => 'data:image/png;base64,iVBORw0KGgo=',
        ]);

    $response->assertInternalServerError()
        ->assertJsonStructure(['error', 'requestId'])
        ->assertJsonPath('error', 'Provider API error');
});

test('successful operation logs usage', function () {
    $user = User::factory()->create();
    $apiKey = ApiKey::factory()->create(['user_id' => $user->id]);

    $this->mock(ProviderManager::class, function (MockInterface $mock) {
        $mock->shouldReceive('execute')
            ->once()
            ->andReturn([
                'image' => 'data:image/png;base64,PROCESSED',
                'provider' => 'replicate',
            ]);
    });

    $this->withHeader('Authorization', "Bearer {$apiKey->key}")
        ->postJson('/api/v1/background-remove', [
            'image' => 'data:image/png;base64,iVBORw0KGgo=',
        ]);

    $this->assertDatabaseHas('usage_logs', [
        'user_id' => $user->id,
        'api_key_id' => $apiKey->id,
        'operation' => 'background-remove',
        'status' => 'success',
    ]);

    $this->assertDatabaseHas('usage_daily', [
        'user_id' => $user->id,
        'operation' => 'background-remove',
    ]);
});

test('failed operation logs error', function () {
    $user = User::factory()->create();
    $apiKey = ApiKey::factory()->create(['user_id' => $user->id]);

    $this->mock(ProviderManager::class, function (MockInterface $mock) {
        $mock->shouldReceive('execute')
            ->once()
            ->andThrow(new Exception('Provider failed'));
    });

    $this->withHeader('Authorization', "Bearer {$apiKey->key}")
        ->postJson('/api/v1/background-remove', [
            'image' => 'data:image/png;base64,iVBORw0KGgo=',
        ]);

    $this->assertDatabaseHas('usage_logs', [
        'user_id' => $user->id,
        'operation' => 'background-remove',
        'status' => 'error',
        'error_message' => 'Provider failed',
    ]);
});

test('unauthenticated request returns 401', function () {
    $response = $this->postJson('/api/v1/background-remove', [
        'image' => 'data:image/png;base64,iVBORw0KGgo=',
    ]);

    $response->assertUnauthorized();
});

test('all valid operations are accepted', function (string $operation) {
    $user = User::factory()->create();
    $apiKey = ApiKey::factory()->create(['user_id' => $user->id]);

    $this->mock(ProviderManager::class, function (MockInterface $mock) {
        $mock->shouldReceive('execute')
            ->once()
            ->andReturn([
                'image' => 'data:image/png;base64,RESULT',
                'provider' => 'test',
            ]);
    });

    $response = $this->withHeader('Authorization', "Bearer {$apiKey->key}")
        ->postJson("/api/v1/{$operation}", [
            'image' => 'data:image/png;base64,iVBORw0KGgo=',
        ]);

    $response->assertOk();
})->with([
    'background-remove',
    'upscale',
    'unblur',
    'colorize',
    'inpaint',
    'restore',
]);

test('x-api-key header is accepted for authentication', function () {
    $user = User::factory()->create();
    $apiKey = ApiKey::factory()->create(['user_id' => $user->id]);

    $this->mock(ProviderManager::class, function (MockInterface $mock) {
        $mock->shouldReceive('execute')
            ->once()
            ->andReturn([
                'image' => 'data:image/png;base64,RESULT',
                'provider' => 'test',
            ]);
    });

    $response = $this->withHeader('X-API-Key', $apiKey->key)
        ->postJson('/api/v1/background-remove', [
            'image' => 'data:image/png;base64,iVBORw0KGgo=',
        ]);

    $response->assertOk();
});

test('inactive api key returns 401', function () {
    $user = User::factory()->create();
    $apiKey = ApiKey::factory()->create([
        'user_id' => $user->id,
        'status' => 'inactive',
    ]);

    $response = $this->withHeader('Authorization', "Bearer {$apiKey->key}")
        ->postJson('/api/v1/background-remove', [
            'image' => 'data:image/png;base64,iVBORw0KGgo=',
        ]);

    $response->assertUnauthorized();
});

test('daily usage is created on successful operation', function () {
    $user = User::factory()->create();
    $apiKey = ApiKey::factory()->create(['user_id' => $user->id]);

    $this->mock(ProviderManager::class, function (MockInterface $mock) {
        $mock->shouldReceive('execute')
            ->once()
            ->andReturn([
                'image' => 'data:image/png;base64,PROCESSED',
                'provider' => 'replicate',
            ]);
    });

    $this->withHeader('Authorization', "Bearer {$apiKey->key}")
        ->postJson('/api/v1/background-remove', [
            'image' => 'data:image/png;base64,iVBORw0KGgo=',
        ]);

    $daily = UsageDaily::where('user_id', $user->id)
        ->where('operation', 'background-remove')
        ->first();

    $this->assertNotNull($daily);
    $this->assertGreaterThanOrEqual(1, $daily->request_count);
    $this->assertEquals(0, $daily->error_count);
});

test('auth can be disabled via config', function () {
    config(['photova.auth.enabled' => false]);

    $this->mock(ProviderManager::class, function (MockInterface $mock) {
        $mock->shouldReceive('execute')
            ->once()
            ->andReturn([
                'image' => 'data:image/png;base64,RESULT',
                'provider' => 'test',
            ]);
    });

    $response = $this->postJson('/api/v1/background-remove', [
        'image' => 'data:image/png;base64,iVBORw0KGgo=',
    ]);

    $response->assertOk();
});
