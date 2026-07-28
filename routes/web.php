<?php

use App\Http\Controllers\Api\EmailVerificationController;
use App\Http\Controllers\LandingPageController;
use Illuminate\Support\Facades\Route;

Route::get('/', LandingPageController::class)->name('home');

// Email verification link target (clicked from the verification email).
// The `signed` middleware validates the signature + expiry.
Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
    ->middleware('signed')
    ->name('verification.verify');
