<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->extend(Tests\TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

function createUser(array $attributes = []): User
{
    return User::factory()->create($attributes);
}

function createAuthenticatedUser(array $attributes = []): User
{
    $user = createUser($attributes);
    test()->actingAs($user);
    return $user;
}
