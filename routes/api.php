<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\GoogleAuthController;
use App\Http\Controllers\Api\PaymentGatewayController;
use App\Http\Controllers\Api\PasswordResetController;
use App\Http\Controllers\Api\ReviewController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('verify-email', [AuthController::class, 'verifyEmail']);

    Route::post('login', [AuthController::class, 'login']);
    Route::post('refresh', [AuthController::class, 'refresh']);
    Route::post('logout', [AuthController::class, 'logout']);
    Route::get('devices', [AuthController::class, 'devices']);
    Route::post('devices/{device}/logout', [AuthController::class, 'logoutDevice']);

    Route::post('forgot-password', [PasswordResetController::class, 'forgot']);
    Route::post('reset-password', [PasswordResetController::class, 'reset']);

    Route::post('google', [GoogleAuthController::class, 'googleSignIn']);
    Route::get('google', [GoogleAuthController::class, 'redirectToGoogle']);
    Route::get('google/callback', [GoogleAuthController::class, 'handleGoogleCallback']);

    Route::prefix('payment')->group(function () {
        Route::get('methods', [PaymentGatewayController::class, 'methods'])->name('payment.methods');
        Route::post('create', [PaymentGatewayController::class, 'create'])->name('payment.create');
        Route::post('callback', [PaymentGatewayController::class, 'callback'])->name('payment.callback');
        Route::get('status/{reference}', [PaymentGatewayController::class, 'status'])->name('payment.status');
    });
});

Route::get('products/{productId}/reviews', [ReviewController::class, 'index'])->name('api.reviews.index');
