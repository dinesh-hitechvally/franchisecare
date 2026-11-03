<?php

use App\Http\Controllers\DocsController;
use App\Http\Controllers\PublicController;
use Illuminate\Support\Facades\Route;

// documentation: a hub and three separate pages (API clients that ask for JSON at / still get the small JSON answer)
Route::get('/', [DocsController::class, 'hub']);
Route::get('/docs', fn () => redirect('/'));
Route::get('/docs/manual', [DocsController::class, 'manual']);
Route::get('/docs/developer', [DocsController::class, 'developer']);
Route::get('/docs/api', [DocsController::class, 'api']);

// Hosted sign-up forms and landing pages (public)
Route::get('/f/{slug}', [PublicController::class, 'form'])->middleware('throttle:120,1');
Route::get('/p/{slug}', [PublicController::class, 'page'])->middleware('throttle:120,1');
