<?php

use App\Http\Controllers\Api\ApiKeyController;
use App\Http\Controllers\Api\AssetController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\FolderController;
use App\Http\Controllers\Api\OperationController;
use App\Http\Controllers\Api\PricingController;
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

Route::prefix('auth')->group(function () {
    Route::post('/signup', [AuthController::class, 'signup']);
    Route::post('/login', [AuthController::class, 'login']);
    
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
        Route::patch('/me', [AuthController::class, 'update']);
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
    Route::post('/assets', [AssetController::class, 'store']);
    Route::post('/assets/move', [AssetController::class, 'move']);
    Route::get('/assets/{asset}', [AssetController::class, 'show']);
    Route::patch('/assets/{asset}', [AssetController::class, 'update']);
    Route::post('/assets/{asset}/share', [AssetController::class, 'share']);
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

    Route::get('/storages/drivers', [StorageController::class, 'drivers']);
    Route::get('/storages', [StorageController::class, 'index']);
    Route::post('/storages', [StorageController::class, 'store']);
    Route::get('/storages/{storage}', [StorageController::class, 'show']);
    Route::patch('/storages/{storage}', [StorageController::class, 'update']);
    Route::delete('/storages/{storage}', [StorageController::class, 'destroy']);
    Route::post('/storages/{storage}/test', [StorageController::class, 'test']);
    Route::post('/storages/{storage}/scan', [StorageController::class, 'scan']);
    Route::post('/storages/{storage}/import', [StorageController::class, 'import']);
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
});
