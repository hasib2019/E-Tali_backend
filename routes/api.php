<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BusinessController;
use App\Http\Controllers\Api\CashbookController;
use App\Http\Controllers\Api\PartyController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\TransactionController;
use App\Http\Controllers\Api\VoucherController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public routes
|--------------------------------------------------------------------------
*/
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

/*
|--------------------------------------------------------------------------
| Protected routes (Sanctum token required)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Businesses (প্রতিষ্ঠান)
    Route::apiResource('businesses', BusinessController::class);

    // Parties (customer / supplier) nested under a business
    Route::get('businesses/{business}/parties', [PartyController::class, 'index']);
    Route::post('businesses/{business}/parties', [PartyController::class, 'store']);
    Route::get('parties/{party}', [PartyController::class, 'show']);
    Route::put('parties/{party}', [PartyController::class, 'update']);
    Route::delete('parties/{party}', [PartyController::class, 'destroy']);

    // Party transactions (দিলাম / পেলাম)
    Route::get('parties/{party}/transactions', [TransactionController::class, 'index']);
    Route::post('parties/{party}/transactions', [TransactionController::class, 'store']);
    Route::delete('transactions/{transaction}', [TransactionController::class, 'destroy']);

    // Products (পণ্য catalog)
    Route::get('businesses/{business}/products', [ProductController::class, 'index']);
    Route::post('businesses/{business}/products', [ProductController::class, 'store']);
    Route::put('products/{product}', [ProductController::class, 'update']);
    Route::delete('products/{product}', [ProductController::class, 'destroy']);

    // Vouchers / bills (বিক্রি / ক্রয়) with line items
    Route::get('parties/{party}/vouchers', [VoucherController::class, 'index']);
    Route::post('parties/{party}/vouchers', [VoucherController::class, 'store']);
    Route::get('vouchers/{voucher}', [VoucherController::class, 'show']);
    Route::delete('vouchers/{voucher}', [VoucherController::class, 'destroy']);

    // Cashbook (নগদ জমা / খরচ)
    Route::get('businesses/{business}/cashbook', [CashbookController::class, 'index']);
    Route::post('businesses/{business}/cashbook', [CashbookController::class, 'store']);
    Route::delete('cashbook/{cashbookEntry}', [CashbookController::class, 'destroy']);

    // Reports
    Route::get('businesses/{business}/reports/summary', [ReportController::class, 'summary']);
    Route::get('businesses/{business}/reports/cashbook', [ReportController::class, 'cashbook']);
    Route::get('businesses/{business}/reports/parties', [ReportController::class, 'parties']);
    Route::get('parties/{party}/statement', [ReportController::class, 'statement']);
});
