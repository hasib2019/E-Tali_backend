<?php

use App\Http\Controllers\Api\AnalyticsController;
use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BackupVaultController;
use App\Http\Controllers\Api\BatchController;
use App\Http\Controllers\Api\BudgetController;
use App\Http\Controllers\Api\BusinessController;
use App\Http\Controllers\Api\CashbookController;
use App\Http\Controllers\Api\CashCategoryController;
use App\Http\Controllers\Api\DeviceTokenController;
use App\Http\Controllers\Api\DriveBackupController;
use App\Http\Controllers\Api\EmailVerificationController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\FeeController;
use App\Http\Controllers\Api\GoogleAuthController;
use App\Http\Controllers\Api\MessController;
use App\Http\Controllers\Api\MigrationController;
use App\Http\Controllers\Api\NoteController;
use App\Http\Controllers\Api\ReminderController;
use App\Http\Controllers\Api\SalaryController;
use App\Http\Controllers\Api\SavingsGoalController;
use App\Http\Controllers\Api\SubscriptionController;
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
Route::post('/auth/google', [GoogleAuthController::class, 'login']);
Route::get('/packages', [SubscriptionController::class, 'packages']);

/*
|--------------------------------------------------------------------------
| Protected routes (Sanctum token required)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum', \App\Http\Middleware\TouchLastActive::class])->group(function () {
    // Auth / account (always reachable, even while locked, so the app can
    // read state and drive the verify / renew / logout flows).
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Email verification (reachable while unverified so the app can drive the flow).
    Route::post('/email/resend', [EmailVerificationController::class, 'resend'])
        ->middleware('throttle:6,1');
    Route::get('/email/status', [EmailVerificationController::class, 'status']);

    /*
    |----------------------------------------------------------------------
    | Push devices, in-app notification inbox, usage analytics.
    | Reachable regardless of subscription lock so pushes/tracking keep
    | working (and a locked user still sees "renew" notifications).
    |----------------------------------------------------------------------
    */
    Route::post('/devices', [DeviceTokenController::class, 'store']);
    Route::delete('/devices', [DeviceTokenController::class, 'destroy']);

    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount']);
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead']);
    Route::post('/notifications/{userNotification}/read', [NotificationController::class, 'markRead']);
    Route::post('/notifications/{userNotification}/opened', [NotificationController::class, 'opened']);

    Route::post('/analytics/events', [AnalyticsController::class, 'store']);

    // One-time server→device migration (reachable regardless of lock so users
    // can always pull their data down; nothing is deleted server-side).
    Route::get('/migration/status', [MigrationController::class, 'status']);
    Route::post('/migration/export', [MigrationController::class, 'export']);
    Route::post('/migration/confirm', [MigrationController::class, 'confirm']);
    Route::get('/migration/media-manifest', [MigrationController::class, 'mediaManifest']);
    Route::post('/migration/media-batch', [MigrationController::class, 'mediaBatch']);

    /*
    |----------------------------------------------------------------------
    | Google Drive backup — needs active + verified, but ALLOWED while the
    | subscription is expired so a locked user can still rescue/migrate data.
    |----------------------------------------------------------------------
    */
    Route::middleware('subscription:ignore_expiry')->group(function () {
        Route::get('/drive/status', [DriveBackupController::class, 'status']);
        Route::post('/drive/connect', [DriveBackupController::class, 'connect']);
        Route::delete('/drive/disconnect', [DriveBackupController::class, 'disconnect']);
        Route::get('/drive/files', [DriveBackupController::class, 'driveFiles']);

        Route::post('/backups/run', [DriveBackupController::class, 'backupNow']);
        Route::get('/backups/history', [DriveBackupController::class, 'history']);
        Route::get('/backups/export', [DriveBackupController::class, 'export']);
        Route::put('/backups/schedule', [DriveBackupController::class, 'schedule']);
        Route::post('/backups/restore', [DriveBackupController::class, 'restore']);

        // Encrypted device-backup vault. Key/list/download stay reachable while
        // expired so a locked user can still RESTORE/rescue their own data.
        Route::get('/backup/key', [BackupVaultController::class, 'key']);
        Route::get('/backup/list', [BackupVaultController::class, 'list']);
        Route::get('/backup/{encryptedBackup}/download', [BackupVaultController::class, 'download']);
        // Uploading a new backup (owner-storage cost) is premium-gated in the controller.
        Route::post('/backup/upload', [BackupVaultController::class, 'upload']);
    });

    /*
    |----------------------------------------------------------------------
    | Business data — full lock: requires active + verified + subscribed.
    |----------------------------------------------------------------------
    */
    Route::middleware('subscription')->group(function () {
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
        Route::get('transactions/{transaction}', [TransactionController::class, 'show']);
        Route::put('transactions/{transaction}', [TransactionController::class, 'update']);
        Route::delete('transactions/{transaction}', [TransactionController::class, 'destroy']);

        // Products (পণ্য catalog) with inventory
        Route::get('businesses/{business}/products', [ProductController::class, 'index']);
        Route::post('businesses/{business}/products', [ProductController::class, 'store']);
        Route::put('products/{product}', [ProductController::class, 'update']);
        Route::post('products/{product}/adjust-stock', [ProductController::class, 'adjustStock']);
        Route::delete('products/{product}', [ProductController::class, 'destroy']);

        // Vouchers / bills (বিক্রি / ক্রয়) with line items
        Route::get('parties/{party}/vouchers', [VoucherController::class, 'index']);
        Route::post('parties/{party}/vouchers', [VoucherController::class, 'store']);
        Route::get('vouchers/{voucher}', [VoucherController::class, 'show']);
        Route::put('vouchers/{voucher}', [VoucherController::class, 'update']);
        Route::delete('vouchers/{voucher}', [VoucherController::class, 'destroy']);

        // Cashbook (নগদ জমা / খরচ)
        Route::get('businesses/{business}/cashbook', [CashbookController::class, 'index']);
        Route::post('businesses/{business}/cashbook', [CashbookController::class, 'store']);
        Route::get('cashbook/{cashbookEntry}', [CashbookController::class, 'show']);
        Route::put('cashbook/{cashbookEntry}', [CashbookController::class, 'update']);
        Route::delete('cashbook/{cashbookEntry}', [CashbookController::class, 'destroy']);

        // Cash categories (income/expense buckets shown as chips)
        Route::get('businesses/{business}/cash-categories', [CashCategoryController::class, 'index']);
        Route::post('businesses/{business}/cash-categories', [CashCategoryController::class, 'store']);
        Route::put('cash-categories/{cashCategory}', [CashCategoryController::class, 'update']);
        Route::delete('cash-categories/{cashCategory}', [CashCategoryController::class, 'destroy']);

        // Named budgets / envelopes (personal)
        Route::get('businesses/{business}/budgets', [BudgetController::class, 'index']);
        Route::post('businesses/{business}/budgets', [BudgetController::class, 'store']);
        Route::put('budgets/{budget}', [BudgetController::class, 'update']);
        Route::delete('budgets/{budget}', [BudgetController::class, 'destroy']);

        // Salary / allowance (personal)
        Route::post('businesses/{business}/salary', [SalaryController::class, 'add']);

        // Mess / hostel manager
        Route::get('businesses/{business}/mess/sheet', [MessController::class, 'sheet']);
        Route::get('businesses/{business}/mess/entries', [MessController::class, 'entries']);
        Route::post('businesses/{business}/mess/meal', [MessController::class, 'setMeal']);
        Route::post('businesses/{business}/mess/deposit', [MessController::class, 'deposit']);
        Route::post('businesses/{business}/mess/bazar', [MessController::class, 'bazar']);
        Route::delete('mess/entries/{messEntry}', [MessController::class, 'removeEntry']);

        // Business notes (ব্যবসার নোট)
        Route::get('businesses/{business}/notes', [NoteController::class, 'index']);
        Route::post('businesses/{business}/notes', [NoteController::class, 'store']);
        Route::put('notes/{note}', [NoteController::class, 'update']);
        Route::delete('notes/{note}', [NoteController::class, 'destroy']);

        // Savings goals (personal)
        Route::get('businesses/{business}/savings', [SavingsGoalController::class, 'index']);
        Route::post('businesses/{business}/savings', [SavingsGoalController::class, 'store']);
        Route::put('savings/{savingsGoal}', [SavingsGoalController::class, 'update']);
        Route::delete('savings/{savingsGoal}', [SavingsGoalController::class, 'destroy']);

        // Bill / fee reminders
        Route::get('businesses/{business}/reminders', [ReminderController::class, 'index']);
        Route::post('businesses/{business}/reminders', [ReminderController::class, 'store']);
        Route::put('reminders/{reminder}', [ReminderController::class, 'update']);
        Route::delete('reminders/{reminder}', [ReminderController::class, 'destroy']);

        // Teacher — batches
        Route::get('businesses/{business}/batches', [BatchController::class, 'index']);
        Route::post('businesses/{business}/batches', [BatchController::class, 'store']);
        Route::put('batches/{batch}', [BatchController::class, 'update']);
        Route::delete('batches/{batch}', [BatchController::class, 'destroy']);

        // Teacher — fee collection
        Route::get('businesses/{business}/fees/collection', [FeeController::class, 'collection']);
        Route::post('businesses/{business}/fees', [FeeController::class, 'store']);
        Route::delete('fees/{feePayment}', [FeeController::class, 'destroy']);

        // Teacher — attendance
        Route::get('businesses/{business}/attendance', [AttendanceController::class, 'index']);
        Route::post('businesses/{business}/attendance', [AttendanceController::class, 'store']);

        // Reports
        Route::get('businesses/{business}/reports/summary', [ReportController::class, 'summary']);
        Route::get('businesses/{business}/reports/monthly', [ReportController::class, 'monthly']);
        Route::get('businesses/{business}/reports/personal', [ReportController::class, 'personal']);
        Route::get('businesses/{business}/reports/cashbook', [ReportController::class, 'cashbook']);
        Route::get('businesses/{business}/reports/parties', [ReportController::class, 'parties']);
        Route::get('parties/{party}/statement', [ReportController::class, 'statement']);
    });
});
