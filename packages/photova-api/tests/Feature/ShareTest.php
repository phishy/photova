<?php

use App\Models\Asset;
use App\Models\Share;
use App\Models\ShareAnalytic;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('assets');
    Storage::fake('thumbs');
});

describe('authenticated share management', function () {
    it('can list shares', function () {
        $user = User::factory()->create();
        $share = Share::factory()->for($user)->create(['asset_ids' => []]);

        $response = $this->actingAs($user)->getJson('/api/shares');

        $response->assertOk()
            ->assertJsonCount(1, 'shares')
            ->assertJsonPath('shares.0.id', $share->id);
    });

    it('can create a share', function () {
        $user = User::factory()->create();
        $asset = Asset::factory()->for($user)->create();

        $response = $this->actingAs($user)->postJson('/api/shares', [
            'asset_ids' => [$asset->id],
            'expires_in' => '7d',
            'allow_download' => true,
            'allow_zip' => true,
        ]);

        $response->assertCreated()
            ->assertJsonPath('share.assetCount', 1)
            ->assertJsonPath('share.allowDownload', true)
            ->assertJsonPath('share.allowZip', true)
            ->assertJsonStructure(['share' => ['id', 'slug', 'url'], 'url']);
    });

    it('can create password protected share', function () {
        $user = User::factory()->create();
        $asset = Asset::factory()->for($user)->create();

        $response = $this->actingAs($user)->postJson('/api/shares', [
            'asset_ids' => [$asset->id],
            'password' => 'secret123',
        ]);

        $response->assertCreated()
            ->assertJsonPath('share.hasPassword', true);
    });

    it('cannot create share with non-existent assets', function () {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/shares', [
            'asset_ids' => ['00000000-0000-0000-0000-000000000000'],
        ]);

        $response->assertNotFound();
    });

    it('cannot create share with another users assets', function () {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $asset = Asset::factory()->for($otherUser)->create();

        $response = $this->actingAs($user)->postJson('/api/shares', [
            'asset_ids' => [$asset->id],
        ]);

        $response->assertNotFound();
    });

    it('can view own share', function () {
        $user = User::factory()->create();
        $share = Share::factory()->for($user)->create(['asset_ids' => []]);

        $response = $this->actingAs($user)->getJson("/api/shares/{$share->id}");

        $response->assertOk()
            ->assertJsonPath('share.id', $share->id);
    });

    it('cannot view another users share', function () {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $share = Share::factory()->for($otherUser)->create(['asset_ids' => []]);

        $response = $this->actingAs($user)->getJson("/api/shares/{$share->id}");

        $response->assertNotFound();
    });

    it('can update share', function () {
        $user = User::factory()->create();
        $share = Share::factory()->for($user)->create([
            'asset_ids' => [],
            'allow_download' => true,
        ]);

        $response = $this->actingAs($user)->patchJson("/api/shares/{$share->id}", [
            'allow_download' => false,
            'expires_in' => '24h',
        ]);

        $response->assertOk()
            ->assertJsonPath('share.allowDownload', false);
    });

    it('can delete share', function () {
        $user = User::factory()->create();
        $share = Share::factory()->for($user)->create(['asset_ids' => []]);

        $response = $this->actingAs($user)->deleteJson("/api/shares/{$share->id}");

        $response->assertOk();
        $this->assertDatabaseMissing('shares', ['id' => $share->id]);
    });
});

describe('public share access', function () {
    it('can view public share', function () {
        $user = User::factory()->create();
        $asset = Asset::factory()->for($user)->create();
        $share = Share::factory()->for($user)->create([
            'asset_ids' => [$asset->id],
        ]);

        $response = $this->getJson("/api/s/{$share->slug}");

        $response->assertOk()
            ->assertJsonPath('share.assetCount', 1)
            ->assertJsonCount(1, 'assets');
    });

    it('returns 404 for non-existent share', function () {
        $response = $this->getJson('/api/s/nonexistent');

        $response->assertNotFound();
    });

    it('returns 410 for expired share', function () {
        $user = User::factory()->create();
        $share = Share::factory()->for($user)->expired()->create([
            'asset_ids' => [],
        ]);

        $response = $this->getJson("/api/s/{$share->slug}");

        $response->assertStatus(410);
    });

    it('requires password for protected share', function () {
        $user = User::factory()->create();
        $share = Share::factory()->for($user)->withPassword('secret')->create([
            'asset_ids' => [],
        ]);

        $response = $this->getJson("/api/s/{$share->slug}");

        $response->assertUnauthorized()
            ->assertJsonPath('password_required', true);
    });

    it('accepts correct password', function () {
        $user = User::factory()->create();
        $asset = Asset::factory()->for($user)->create();
        $share = Share::factory()->for($user)->withPassword('secret')->create([
            'asset_ids' => [$asset->id],
        ]);

        $response = $this->getJson("/api/s/{$share->slug}?password=secret");

        $response->assertOk()
            ->assertJsonCount(1, 'assets');
    });

    it('rejects incorrect password', function () {
        $user = User::factory()->create();
        $share = Share::factory()->for($user)->withPassword('secret')->create([
            'asset_ids' => [],
        ]);

        $response = $this->getJson("/api/s/{$share->slug}?password=wrong");

        $response->assertUnauthorized();
    });

    it('increments view count', function () {
        $user = User::factory()->create();
        $share = Share::factory()->for($user)->create([
            'asset_ids' => [],
            'view_count' => 0,
        ]);

        $this->getJson("/api/s/{$share->slug}");
        $this->getJson("/api/s/{$share->slug}");

        $this->assertDatabaseHas('shares', [
            'id' => $share->id,
            'view_count' => 2,
        ]);
    });
});

describe('share downloads', function () {
    it('can download individual asset from share', function () {
        $user = User::factory()->create();
        Storage::disk('assets')->put('test.png', file_get_contents(base_path('tests/fixtures/test.png')));
        $asset = Asset::factory()->for($user)->create([
            'storage_key' => 'test.png',
            'mime_type' => 'image/png',
        ]);
        $share = Share::factory()->for($user)->create([
            'asset_ids' => [$asset->id],
            'allow_download' => true,
        ]);

        $response = $this->get("/api/s/{$share->slug}/assets/{$asset->id}/download");

        $response->assertOk()
            ->assertHeader('Content-Type', 'image/png');
    });

    it('cannot download when downloads disabled', function () {
        $user = User::factory()->create();
        $asset = Asset::factory()->for($user)->create();
        $share = Share::factory()->for($user)->create([
            'asset_ids' => [$asset->id],
            'allow_download' => false,
        ]);

        $response = $this->get("/api/s/{$share->slug}/assets/{$asset->id}/download");

        $response->assertForbidden();
    });

    it('cannot download asset not in share', function () {
        $user = User::factory()->create();
        $asset1 = Asset::factory()->for($user)->create();
        $asset2 = Asset::factory()->for($user)->create();
        $share = Share::factory()->for($user)->create([
            'asset_ids' => [$asset1->id],
        ]);

        $response = $this->get("/api/s/{$share->slug}/assets/{$asset2->id}/download");

        $response->assertNotFound();
    });
});

describe('zip downloads', function () {
    it('authenticated user can download selected assets as zip', function () {
        $user = User::factory()->create();
        Storage::disk('assets')->put('test.png', file_get_contents(base_path('tests/fixtures/test.png')));
        $asset = Asset::factory()->for($user)->create([
            'storage_key' => 'test.png',
            'mime_type' => 'image/png',
        ]);

        $response = $this->actingAs($user)->postJson('/api/assets/zip', [
            'asset_ids' => [$asset->id],
        ]);

        $response->assertOk()
            ->assertHeader('Content-Type', 'application/zip');
    });

    it('cannot download zip with no assets', function () {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/assets/zip', [
            'asset_ids' => [],
        ]);

        $response->assertUnprocessable();
    });

    it('public share zip download works', function () {
        $user = User::factory()->create();
        Storage::disk('assets')->put('test.png', file_get_contents(base_path('tests/fixtures/test.png')));
        $asset = Asset::factory()->for($user)->create([
            'storage_key' => 'test.png',
            'mime_type' => 'image/png',
        ]);
        $share = Share::factory()->for($user)->create([
            'asset_ids' => [$asset->id],
            'allow_zip' => true,
        ]);

        $response = $this->get("/api/s/{$share->slug}/zip");

        $response->assertOk()
            ->assertHeader('Content-Type', 'application/zip');
    });

    it('cannot download zip when disabled', function () {
        $user = User::factory()->create();
        $asset = Asset::factory()->for($user)->create();
        $share = Share::factory()->for($user)->create([
            'asset_ids' => [$asset->id],
            'allow_zip' => false,
        ]);

        $response = $this->get("/api/s/{$share->slug}/zip");

        $response->assertForbidden();
    });
});

describe('share analytics', function () {
    it('records view analytics', function () {
        $user = User::factory()->create();
        $asset = Asset::factory()->for($user)->create();
        $share = Share::factory()->for($user)->create([
            'asset_ids' => [$asset->id],
        ]);

        $this->getJson("/api/s/{$share->slug}");

        $this->assertDatabaseHas('share_analytics', [
            'share_id' => $share->id,
            'event_type' => ShareAnalytic::EVENT_VIEW,
        ]);
    });

    it('records download analytics', function () {
        $user = User::factory()->create();
        Storage::disk('assets')->put('test.png', file_get_contents(base_path('tests/fixtures/test.png')));
        $asset = Asset::factory()->for($user)->create([
            'storage_key' => 'test.png',
            'mime_type' => 'image/png',
        ]);
        $share = Share::factory()->for($user)->create([
            'asset_ids' => [$asset->id],
            'allow_download' => true,
        ]);

        $this->get("/api/s/{$share->slug}/assets/{$asset->id}/download");

        $this->assertDatabaseHas('share_analytics', [
            'share_id' => $share->id,
            'event_type' => ShareAnalytic::EVENT_DOWNLOAD,
            'asset_id' => $asset->id,
        ]);
    });

    it('records zip download analytics', function () {
        $user = User::factory()->create();
        Storage::disk('assets')->put('test.png', file_get_contents(base_path('tests/fixtures/test.png')));
        $asset = Asset::factory()->for($user)->create([
            'storage_key' => 'test.png',
            'mime_type' => 'image/png',
        ]);
        $share = Share::factory()->for($user)->create([
            'asset_ids' => [$asset->id],
            'allow_zip' => true,
        ]);

        $this->get("/api/s/{$share->slug}/zip");

        $this->assertDatabaseHas('share_analytics', [
            'share_id' => $share->id,
            'event_type' => ShareAnalytic::EVENT_ZIP_DOWNLOAD,
        ]);
    });

    it('owner can view share analytics', function () {
        $user = User::factory()->create();
        $share = Share::factory()->for($user)->create(['asset_ids' => []]);

        ShareAnalytic::create([
            'share_id' => $share->id,
            'event_type' => ShareAnalytic::EVENT_VIEW,
            'ip_address' => '127.0.0.1',
        ]);

        $response = $this->actingAs($user)->getJson("/api/shares/{$share->id}/analytics");

        $response->assertOk()
            ->assertJsonPath('summary.views', 1)
            ->assertJsonPath('summary.downloads', 0)
            ->assertJsonCount(1, 'events');
    });

    it('cannot view another users share analytics', function () {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $share = Share::factory()->for($otherUser)->create(['asset_ids' => []]);

        $response = $this->actingAs($user)->getJson("/api/shares/{$share->id}/analytics");

        $response->assertNotFound();
    });

    it('includes analytics summary in share list', function () {
        $user = User::factory()->create();
        $share = Share::factory()->for($user)->create(['asset_ids' => []]);

        ShareAnalytic::create([
            'share_id' => $share->id,
            'event_type' => ShareAnalytic::EVENT_VIEW,
        ]);

        $response = $this->actingAs($user)->getJson('/api/shares');

        $response->assertOk()
            ->assertJsonPath('shares.0.analytics.views', 1);
    });
});
