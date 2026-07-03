<?php

use App\Http\Controllers\Api\EmailVerificationController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Email verification link target (clicked from the verification email).
// The `signed` middleware validates the signature + expiry.
Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
    ->middleware('signed')
    ->name('verification.verify');
