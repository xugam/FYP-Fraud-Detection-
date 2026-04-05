<?php

use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Transaction\TransactionController;
use App\Http\Controllers\Wallet\WalletController;
use Illuminate\Support\Facades\Route;

// Public
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login',    [AuthController::class, 'login']);

// Authenticated
Route::middleware('auth:sanctum')->group(function () {

    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me',      [AuthController::class, 'me']);

    // Wallet (frozen check applied)
    Route::prefix('wallet')->middleware('check.frozen')->group(function () {
        Route::get('/',        [WalletController::class, 'balance']);
        Route::post('/deposit', [WalletController::class, 'deposit']);
    });

    // Transactions (frozen check applied)
    Route::prefix('transactions')->middleware('check.frozen')->group(function () {
        Route::post('/transfer', [TransactionController::class, 'transfer'])->middleware('throttle:transfers');
        Route::get('/history',   [TransactionController::class, 'history']);
    });

    // Admin only
    Route::prefix('admin')->middleware('role:admin')->group(function () {
        Route::get('/users',                               [AdminController::class, 'users']);
        Route::get('/transactions',                        [AdminController::class, 'transactions']);
        Route::get('/transactions/flagged',                [AdminController::class, 'flaggedTransactions']);
        Route::get('/risk-logs',                           [AdminController::class, 'riskLogs']);
        Route::get('/analytics',                           [AdminController::class, 'analytics']);
        Route::post('/users/{user}/freeze',                [AdminController::class, 'freezeAccount']);
        Route::post('/users/{user}/unfreeze',              [AdminController::class, 'unfreezeAccount']);
    });
});
