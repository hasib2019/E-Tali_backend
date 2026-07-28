<?php

use App\Http\Controllers\Api\EmailVerificationController;
use App\Http\Controllers\LandingPageController;
use App\Http\Controllers\LegalPageController;
use Illuminate\Support\Facades\Route;

Route::get('/', LandingPageController::class)->name('home');

Route::get('/privacy', [LegalPageController::class, 'privacy'])->name('privacy');
Route::get('/terms', [LegalPageController::class, 'terms'])->name('terms');

// Email verification link target (clicked from the verification email).
// The `signed` middleware validates the signature + expiry.
Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
    ->middleware('signed')
    ->name('verification.verify');
