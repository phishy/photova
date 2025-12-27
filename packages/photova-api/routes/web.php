<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/docs', fn() => view('docs'))->name('docs');

// Dashboard routes (auth handled client-side via Alpine.js)
Route::prefix('dashboard')->group(function () {
    Route::get('/', fn() => view('dashboard.index'))->name('dashboard');
    Route::get('/keys', fn() => view('dashboard.keys'))->name('dashboard.keys');
    Route::get('/usage', fn() => view('dashboard.usage'))->name('dashboard.usage');
    Route::get('/assets', fn() => view('dashboard.assets'))->name('dashboard.assets');
    Route::get('/playground', fn() => view('dashboard.playground'))->name('dashboard.playground');
});
