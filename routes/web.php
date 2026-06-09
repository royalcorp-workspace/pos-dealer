<?php

use App\Http\Controllers\Api\GoogleAuthController;
use App\Http\Controllers\Frontend\AuthController;
use App\Http\Controllers\Frontend\CartController;
use App\Http\Controllers\Frontend\DashboardController;
use App\Http\Controllers\Frontend\HomeController;
use App\Http\Controllers\Frontend\PageController;
use App\Http\Controllers\Frontend\ProductController;

use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::get('/products', [ProductController::class, 'index'])->name('products.index');
Route::get('/products/{id}', [ProductController::class, 'show'])->name('products.show');

Route::get('/brands', [PageController::class, 'brands'])->name('brands');
Route::get('/categories', [PageController::class, 'categories'])->name('categories');
Route::get('/promos', [PageController::class, 'promos'])->name('promos');
Route::get('/blog', [PageController::class, 'blog'])->name('blog');
Route::get('/help', [PageController::class, 'help'])->name('help');
Route::get('/400', [PageController::class, 'error400'])->name('errors.400');
Route::get('/403', [PageController::class, 'error403'])->name('errors.403');
Route::get('/404', [PageController::class, 'error404'])->name('errors.404');
Route::get('/500', [PageController::class, 'error500'])->name('errors.500');

Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

Route::post('/login', [AuthController::class, 'login'])->name('login');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::get('/forgot-password', [AuthController::class, 'showForgotPassword'])->name('forgot-password.show');
Route::post('/forgot-password', [AuthController::class, 'processForgotPassword'])->name('forgot-password.process');
Route::get('/password-otp-sent', function () {
    return view('frontend.password-otp-sent', ['email' => request()->query('email', '')]);
})->name('password-otp.sent');
Route::get('/reset-password', [AuthController::class, 'showResetPassword'])->name('reset-password.show');
Route::get('/email/verify', function () {
    return view('frontend.verify-email');
})->name('verification.notice');
Route::post('/resend-verification', function () {
    return response()->json(['message' => 'Verification email resent']);
})->name('verification.resend');
Route::get('/auth/google/redirect', [GoogleAuthController::class, 'redirectToGoogle'])->name('auth.google.redirect');
Route::get('/auth/google/callback', [GoogleAuthController::class, 'handleGoogleCallback'])->name('auth.google.callback');
Route::get('/auth/callback', [AuthController::class, 'googleCallback'])->name('auth.callback');
Route::post('/auth/google/session', [AuthController::class, 'storeGoogleSession'])->name('auth.google.session');

Route::post('/cart/add', [CartController::class, 'add'])->name('cart.add');
Route::post('/cart/update/{id}', [CartController::class, 'update'])->name('cart.update');
Route::post('/cart/remove/{id}', [CartController::class, 'remove'])->name('cart.remove');

