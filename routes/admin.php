<?php

use App\Http\Controllers\Admin\AdminController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->middleware('role:admin', 'auth:sanctum')->group(function () {
    Route::get('/users',                               [AdminController::class, 'users']);
    Route::get('/transactions',                        [AdminController::class, 'transactions']);
    Route::get('/transactions/flagged',                [AdminController::class, 'flaggedTransactions']);
    Route::get('/risk-logs',                           [AdminController::class, 'riskLogs']);
    Route::get('/analytics',                           [AdminController::class, 'analytics']);
    Route::post('/users/{user}/freeze',                [AdminController::class, 'freezeAccount']);
    Route::post('/users/{user}/unfreeze',              [AdminController::class, 'unfreezeAccount']);
});
