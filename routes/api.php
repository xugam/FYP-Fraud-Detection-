<?php

use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Transaction\TransactionController;
use App\Http\Controllers\Wallet\WalletController;
use Illuminate\Support\Facades\Route;


require __DIR__ . '/auth.php';
require __DIR__ . '/admin.php';


// Authenticated
Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('wallet')->middleware('check.frozen')->group(function () {
        Route::get('/', [WalletController::class, 'balance']);
        Route::post('/deposit', [WalletController::class, 'deposit']);
    });

    Route::prefix('transactions')->middleware('check.frozen')->group(function () {
        Route::post('/transfer', [TransactionController::class, 'transfer'])->middleware('throttle:transfers');
        Route::get('/history',   [TransactionController::class, 'history']);
    });
});
