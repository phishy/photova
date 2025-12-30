<?php

test('health endpoint returns ok status', function () {
    $response = $this->getJson('/api/health');

    $response->assertOk()
        ->assertJson(['status' => 'ok'])
        ->assertJsonStructure(['status', 'timestamp']);
});

test('operations endpoint lists available operations', function () {
    $response = $this->getJson('/api/operations');

    $response->assertOk()
        ->assertJsonStructure([
            'operations' => [
                '*' => ['name', 'provider'],
            ],
        ]);
});

test('openapi endpoint redirects to scramble docs', function () {
    $response = $this->get('/api/openapi.json');

    $response->assertRedirect('/docs/api.json');
});
