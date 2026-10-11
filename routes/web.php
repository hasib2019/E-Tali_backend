<?php

use App\Http\Controllers\Api\EmailVerificationController;
use App\Http\Controllers\LandingPageController;
use App\Http\Controllers\LegalPageController;
use App\Http\Controllers\WebAppController;
use Illuminate\Support\Facades\Route;

Route::get('/', LandingPageController::class)->name('home');

Route::get('/privacy', [LegalPageController::class, 'privacy'])->name('privacy');
Route::get('/terms', [LegalPageController::class, 'terms'])->name('terms');

// Browser version of the app (Expo web build in public/web-app). Catches the
// app's client-side routes so deep links and refreshes still load it.
Route::get('/web-app/{path?}', WebAppController::class)->where('path', '.*')->name('web-app');

// Email verification link target (clicked from the verification email).
// The `signed` middleware validates the signature + expiry.
Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
    ->middleware('signed')
    ->name('verification.verify');
