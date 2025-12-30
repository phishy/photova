<?php

use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\ApiKeyController;
use App\Http\Controllers\Api\AssetController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\FolderController;
use App\Http\Controllers\Api\OperationController;
use App\Http\Controllers\Api\PricingController;
use App\Http\Controllers\Api\ShareController;
use App\Http\Controllers\Api\StorageController;
use App\Http\Controllers\Api\TagController;
use App\Http\Controllers\Api\SystemController;
use App\Http\Controllers\Api\UsageController;
use App\Http\Middleware\EnsureSuperAdmin;
use App\Http\Middleware\OptionalAuth;
use Illuminate\Support\Facades\Route;

Route::get('/health', [SystemController::class, 'health']);
Route::get('/operations', [SystemController::class, 'operations']);
Route::get('/openapi.json', [SystemController::class, 'openapi']);

// Public signed asset access (no auth required)
Route::get('/public/assets/{asset}', [AssetController::class, 'publicDownload'])
    ->name('assets.public')
    ->middleware('signed');

// Public share access (no auth required)
Route::prefix('s')->group(function () {
    Route::get('/{slug}', [ShareController::class, 'publicShow']);
    Route::get('/{slug}/assets/{asset}/thumb', [ShareController::class, 'publicThumbnail']);
    Route::get('/{slug}/assets/{asset}/download', [ShareController::class, 'publicDownload']);
    Route::get('/{slug}/zip', [ShareController::class, 'publicZip']);
});

Route::prefix('auth')->group(function () {
    Route::post('/signup', [AuthController::class, 'signup']);
    Route::post('/login', [AuthController::class, 'login']);
    
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
        Route::patch('/me', [AuthController::class, 'update']);
        Route::patch('/me/password', [AuthController::class, 'updatePassword']);
    });
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/keys', [ApiKeyController::class, 'index']);
    Route::post('/keys', [ApiKeyController::class, 'store']);
    Route::get('/keys/{key}', [ApiKeyController::class, 'show']);
    Route::patch('/keys/{key}', [ApiKeyController::class, 'update']);
    Route::delete('/keys/{key}', [ApiKeyController::class, 'destroy']);
    Route::post('/keys/{key}/regenerate', [ApiKeyController::class, 'regenerate']);

    Route::get('/usage/summary', [UsageController::class, 'summary']);
    Route::get('/usage/timeseries', [UsageController::class, 'timeseries']);
    Route::get('/usage/current', [UsageController::class, 'current']);

    Route::get('/assets', [AssetController::class, 'index']);
    Route::get('/assets/insights', [AssetController::class, 'insights']);
    Route::get('/assets/geo', [AssetController::class, 'geo']);
    Route::get('/assets/analytics', [AssetController::class, 'analyticsAggregate']);
    Route::post('/assets', [AssetController::class, 'store']);
    Route::post('/assets/move', [AssetController::class, 'move']);
    Route::get('/assets/{asset}', [AssetController::class, 'show']);
    Route::get('/assets/{asset}/thumb', [AssetController::class, 'thumbnail']);
    Route::get('/assets/{asset}/analytics', [AssetController::class, 'analytics']);
    Route::patch('/assets/{asset}', [AssetController::class, 'update']);
    Route::post('/assets/{asset}/share', [AssetController::class, 'share']);
    Route::post('/assets/{asset}/rotate', [AssetController::class, 'rotate']);
    Route::delete('/assets/{asset}', [AssetController::class, 'destroy']);

    Route::get('/folders', [FolderController::class, 'index']);
    Route::post('/folders', [FolderController::class, 'store']);
    Route::get('/folders/{folder}', [FolderController::class, 'show']);
    Route::patch('/folders/{folder}', [FolderController::class, 'update']);
    Route::delete('/folders/{folder}', [FolderController::class, 'destroy']);
    Route::post('/folders/{folder}/move-assets', [FolderController::class, 'moveAssets']);

    Route::get('/tags', [TagController::class, 'index']);
    Route::post('/tags', [TagController::class, 'store']);
    Route::patch('/tags/{tag}', [TagController::class, 'update']);
    Route::delete('/tags/{tag}', [TagController::class, 'destroy']);
    Route::post('/assets/{asset}/tags', [TagController::class, 'setAssetTags']);

    Route::get('/shares', [ShareController::class, 'index']);
    Route::post('/shares', [ShareController::class, 'store']);
    Route::get('/shares/{share}', [ShareController::class, 'show']);
    Route::get('/shares/{share}/analytics', [ShareController::class, 'analytics']);
    Route::patch('/shares/{share}', [ShareController::class, 'update']);
    Route::delete('/shares/{share}', [ShareController::class, 'destroy']);
    Route::post('/assets/zip', [ShareController::class, 'downloadZip']);

    Route::get('/storage', [StorageController::class, 'index']);
    Route::get('/storage/providers', [StorageController::class, 'providers']);
    Route::post('/storage', [StorageController::class, 'store']);
    Route::get('/storage/migrations', [StorageController::class, 'migrations']);
    Route::post('/storage/migrate', [StorageController::class, 'migrate']);
    Route::delete('/storage/default', [StorageController::class, 'clearDefault']);
    Route::get('/storage/migrations/{migration}', [StorageController::class, 'migrationStatus']);
    Route::post('/storage/migrations/{migration}/cancel', [StorageController::class, 'cancelMigration']);
    Route::get('/storage/{bucket}', [StorageController::class, 'show']);
    Route::patch('/storage/{bucket}', [StorageController::class, 'update']);
    Route::delete('/storage/{bucket}', [StorageController::class, 'destroy']);
    Route::post('/storage/{bucket}/test', [StorageController::class, 'test']);
    Route::post('/storage/{bucket}/default', [StorageController::class, 'setDefault']);
});

Route::prefix('v1')->middleware(OptionalAuth::class)->group(function () {
    Route::post('/{operation}', [OperationController::class, 'execute'])
        ->where('operation', 'background-remove|upscale|unblur|colorize|inpaint|restore|analyze');
});

Route::middleware(['auth:sanctum', EnsureSuperAdmin::class])->prefix('admin')->group(function () {
    Route::get('/pricing', [PricingController::class, 'index']);
    Route::get('/pricing/summary', [PricingController::class, 'summary']);
    Route::post('/pricing', [PricingController::class, 'store']);
    Route::get('/pricing/{pricing}', [PricingController::class, 'show']);
    Route::patch('/pricing/{pricing}', [PricingController::class, 'update']);
    Route::delete('/pricing/{pricing}', [PricingController::class, 'destroy']);

    Route::get('/dashboard', [AdminController::class, 'dashboard']);
    Route::get('/timeseries', [AdminController::class, 'timeseries']);
    Route::get('/top-users', [AdminController::class, 'topUsers']);
    Route::get('/top-assets', [AdminController::class, 'topAssets']);
    Route::get('/top-shares', [AdminController::class, 'topShares']);
});
