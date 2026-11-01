<?php

use App\Http\Controllers\PublicController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => response()->json(['app' => 'FranchiseCare Marketing API']));

// Hosted sign-up forms and landing pages (public)
Route::get('/f/{slug}', [PublicController::class, 'form'])->middleware('throttle:120,1');
Route::get('/p/{slug}', [PublicController::class, 'page'])->middleware('throttle:120,1');
