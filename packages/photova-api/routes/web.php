<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/docs', fn() => view('docs'))->name('docs');

Route::get('/og-preview', fn() => view('og.image'))->name('og.preview');

// Dashboard routes (auth handled client-side via Alpine.js)
Route::prefix('dashboard')->group(function () {
    Route::get('/', fn() => view('dashboard.index'))->name('dashboard');
    Route::get('/keys', fn() => view('dashboard.keys'))->name('dashboard.keys');
    Route::get('/usage', fn() => view('dashboard.usage'))->name('dashboard.usage');
    Route::get('/storage', fn() => view('dashboard.storage'))->name('dashboard.storage');
    Route::get('/assets', fn() => view('dashboard.assets'))->name('dashboard.assets');
    Route::get('/playground', fn() => view('dashboard.playground'))->name('dashboard.playground');
    Route::get('/settings', fn() => view('dashboard.settings'))->name('dashboard.settings');
    
    // Admin routes (role check handled client-side via Alpine.js)
    Route::prefix('admin')->group(function () {
        Route::get('/analytics', fn() => view('dashboard.admin.analytics'))->name('dashboard.admin.analytics');
        Route::get('/pricing', fn() => view('dashboard.pricing'))->name('dashboard.admin.pricing');
    });
    
    // Legacy redirect for old pricing URL
    Route::get('/pricing', fn() => redirect()->route('dashboard.admin.pricing'));
});
