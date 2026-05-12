<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\GoogleAuthController;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Public
Route::controller(AuthController::class)->group(function () {
    Route::post('/register', 'register');
    Route::post('/login', 'login');
    Route::post('forget-password', 'forgetPassword');
    Route::post('reset-password', 'resetPassword');
    Route::post('verify-token', 'verifyToken');
});

Route::get('/email/verify/{id}/{hash}', function (Request $request, $id, $hash) {

    if (!$request->hasValidSignature()) {
        return response()->json([
            'message' => 'Invalid or expired verification link'
        ], 403);
    }

    $user = User::find($id);

    if (!$user) {
        return response()->json([
            'message' => 'User not found'
        ], 404);
    }

    if (! hash_equals(sha1($user->getEmailForVerification()), $hash)) {
        return response()->json([
            'message' => 'Invalid verification hash'
        ], 403);
    }

    if (!$user->hasVerifiedEmail()) {
        $user->markEmailAsVerified();
    }

    return response()->json([
        'message' => 'Email verified successfully'
    ]);
})->middleware('signed')->name('verification.verify');

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me',      [AuthController::class, 'me']);
    Route::post('/email/verification-notification', function (Request $request) {
        $request->user()->sendEmailVerificationNotification();

        return response()->json([
            'message' => 'Verification link sent'
        ]);
    })->middleware('throttle:6,1')->name('verification.send');
});


// GOOGLE AUTH

Route::controller(GoogleAuthController::class)->group(function () {
    Route::get('/auth/google', 'redirect');
    Route::get('/auth/google/callback', 'callback');
});
