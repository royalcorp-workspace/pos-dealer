<?php

use App\Http\Controllers\Api\ExpeditionIntegrationController;
use App\Http\Controllers\Api\GoogleAuthController;
use App\Http\Controllers\Frontend\AuthController;
use App\Http\Controllers\Frontend\CartController;
use App\Http\Controllers\Frontend\CheckoutController;
use App\Http\Controllers\Frontend\DashboardController;
use App\Http\Controllers\Frontend\HomeController;
use App\Http\Controllers\Frontend\OrderTrackingController;
use App\Http\Controllers\Frontend\PageController;
use App\Http\Controllers\Frontend\PaymentMethodController;
use App\Http\Controllers\Frontend\ProductCatalogController;
use App\Http\Controllers\Frontend\ReviewController;
use Illuminate\Support\Facades\Route;



Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/home/load-more', [HomeController::class, 'loadMore'])->name('home.load-more');
Route::get('/home', fn () => redirect()->route('home'))->name('home.redirect');

Route::get('/sitemap.xml', [PageController::class, 'sitemap'])->name('sitemap');
Route::get('/robots.txt', [PageController::class, 'robots']);

Route::get('/products', [ProductCatalogController::class, 'index'])->name('products.index');
Route::get('/products/{product:slug}', [ProductCatalogController::class, 'show'])->name('products.show');
Route::get('/category/{categorySlug}', [ProductCatalogController::class, 'index'])->name('category.show');

Route::get('/brands', [PageController::class, 'brands'])->name('brands');
Route::get('/brands/{brandSlug}', [ProductCatalogController::class, 'index'])->name('brands.show');
Route::get('/categories', [PageController::class, 'categories'])->name('categories');
Route::get('/promos', [PageController::class, 'promos'])->name('promos');
Route::get('/price-product-settings', [\App\Http\Controllers\Frontend\PriceProductSettingController::class, 'index'])->name('price-product-settings.index');
Route::get('/price-product-settings/{code}', [\App\Http\Controllers\Frontend\PriceProductSettingController::class, 'show'])->name('price-product-settings.show');
Route::get('/blog', [PageController::class, 'blog'])->name('blog');
Route::get('/blog/{blogPost:slug}', [PageController::class, 'blogShow'])->name('blog.show');
Route::get('/help', [PageController::class, 'help'])->name('help');
Route::get('/tentang-kami', [PageController::class, 'about'])->name('about');
Route::get('/klaim-garansi', [PageController::class, 'warranty'])->name('warranty');
Route::get('/syarat-dan-ketentuan', [PageController::class, 'terms'])->name('terms');
Route::get('/kebijakan-privacy', [PageController::class, 'privacy'])->name('privacy');
Route::get('/how-to-return', [PageController::class, 'returns'])->name('returns');

Route::get('/payment-methods', [PaymentMethodController::class, 'index'])->name('payment-methods');

Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
Route::get('/dashboard/addresses', [DashboardController::class, 'addresses'])->name('dashboard.addresses');
Route::post('/dashboard/addresses', [DashboardController::class, 'storeAddress'])->name('dashboard.addresses.store');
Route::put('/dashboard/addresses/{id}', [DashboardController::class, 'updateAddress'])->name('dashboard.addresses.update');
Route::delete('/dashboard/addresses/{id}', [DashboardController::class, 'deleteAddress'])->name('dashboard.addresses.delete');
Route::post('/dashboard/addresses/{id}/primary', [DashboardController::class, 'setPrimaryAddress'])->name('dashboard.addresses.primary');

Route::get('/checkout', [CheckoutController::class, 'index'])->name('checkout');
Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout.process');
Route::post('/checkout/check-user', function (Illuminate\Http\Request $request) {
    $email = $request->input('email');
    $phone = $request->input('phone');
    
    $customer = null;
    if ($email) {
        $customer = \App\Models\Frontend\Customer\Customer::where('email', $email)->first();
    } elseif ($phone) {
        $cleanPhone = preg_replace('/[^0-9]/', '', $phone);
        // Normalize 0 to 62
        if (str_starts_with($cleanPhone, '0')) {
            $cleanPhone = '62' . substr($cleanPhone, 1);
        }
        $customer = \App\Models\Frontend\Customer\Customer::where(function ($q) use ($phone, $cleanPhone) {
            $q->where('phone', $phone)
              ->orWhere('phone', 'like', '%' . $phone)
              ->orWhereRaw("REPLACE(REPLACE(REPLACE(phone, '-', ''), ' ', ''), '+', '') = ?", [$cleanPhone]);
        })->first();
    }
    
    if ($customer) {
        $address = \App\Models\Frontend\Customer\Address::where('user_id', $customer->user_id)
            ->where('deleted', false)
            ->orderBy('is_primary', 'desc')
            ->first();
            
        return response()->json([
            'registered' => true,
            'customer' => [
                'name' => $customer->name,
                'phone' => $customer->phone,
                'email' => $customer->email,
            ],
            'address' => $address ? [
                'address' => $address->address,
                'sub_district_id' => $address->sub_district_id,
                'city' => $address->city->name ?? ($address->subDistrict->city->name ?? ''),
                'postal_code' => $address->postal_code,
            ] : null
        ]);
    }
    
    return response()->json(['registered' => false]);
})->name('checkout.check-user');

Route::get('/order-preview', [CheckoutController::class, 'orderPreview'])->name('order.preview');

Route::get('/order-tracking', [OrderTrackingController::class, 'index'])->name('order.tracking');

Route::get('/payment', [CheckoutController::class, 'payment'])->name('payment');
Route::post('/payment/process', [CheckoutController::class, 'processPayment'])->name('payment.process');

Route::get('/thankyou', [CheckoutController::class, 'thankYou'])->name('thankyou');

Route::get('/register-success', [CheckoutController::class, 'registerSuccess'])->name('register.success');

Route::get('/register', [AuthController::class, 'showRegister'])->name('register.show');
Route::get('/forgot-password', [AuthController::class, 'showForgotPassword'])->name('forgot-password.show');
Route::get('/password-otp-sent', [CheckoutController::class, 'passwordOtpSent'])->name('password-otp.sent');
Route::get('/reset-password', [AuthController::class, 'showResetPassword'])->name('reset-password.show');

Route::post('/login', [AuthController::class, 'login'])->name('login');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
Route::post('/devices/{device}/logout', [AuthController::class, 'logoutDevice'])->name('devices.logout');

Route::post('/forgot-password', [AuthController::class, 'processForgotPassword'])->name('forgot-password.process');
Route::get('/email/verify', [PageController::class, 'verifyEmail'])->name('verification.notice');
Route::post('/resend-verification', [PageController::class, 'resendVerification'])->name('verification.resend');
Route::get('/auth/google/redirect', [GoogleAuthController::class, 'redirectToGoogle'])->name('auth.google.redirect');
Route::get('/auth/google/callback', [GoogleAuthController::class, 'handleGoogleCallback'])->name('auth.google.callback');
Route::get('/auth/callback', [AuthController::class, 'googleCallback'])->name('auth.callback');
Route::post('/auth/google/session', [AuthController::class, 'storeGoogleSession'])->name('auth.google.session');

Route::get('/api/expeditions/providers', [ExpeditionIntegrationController::class, 'providers'])->name('api.expeditions.providers');
Route::get('/api/expeditions/{provider}/tracking/{awb}', [ExpeditionIntegrationController::class, 'tracking'])->name('api.expeditions.tracking');
Route::post('/api/expeditions/rates', [ExpeditionIntegrationController::class, 'rates'])->name('api.expeditions.rates');

Route::post('/cart/add', [CartController::class, 'add'])->name('cart.add');
Route::post('/voucher/validate', [\App\Http\Controllers\Frontend\VoucherController::class, 'validate'])->name('voucher.validate');
Route::post('/cart/toggle-wishlist', [CartController::class, 'toggleWishlist'])->name('cart.toggle-wishlist');
Route::post('/cart/update/{id}', [CartController::class, 'update'])->name('cart.update');
Route::post('/cart/remove/{id}', [CartController::class, 'remove'])->name('cart.remove');
Route::post('/orders/{order}/reorder', [CartController::class, 'reorder'])->name('orders.reorder');
Route::post('/checkout/preview', [CartController::class, 'preview'])->name('checkout.preview');
Route::post('/order/{order}/cancel', [CheckoutController::class, 'cancelOrder'])->name('order.cancel');
Route::post('/order/{order}/reorder', [CheckoutController::class, 'reorder'])->name('order.reorder');
Route::post('/order/{order}/upload-payment-proof', [CheckoutController::class, 'uploadPaymentProof'])->name('order.upload-payment-proof');

Route::post('/reviews', [ReviewController::class, 'store'])->name('reviews.store');
Route::get('/products/{product}/reviews', [ReviewController::class, 'filter'])->name('reviews.filter');
Route::post('/reviews/{review}/report', [ReviewController::class, 'report'])->name('reviews.report');

Route::get('/{tagSlug}', [ProductCatalogController::class, 'index'])->where('tagSlug', '^[a-z0-9-]+(-[a-z0-9-]+)*$')->name('products.by-tag');
Route::get('/400', [PageController::class, 'error400'])->name('errors.400');
Route::get('/403', [PageController::class, 'error403'])->name('errors.403');
Route::get('/404', [PageController::class, 'error404'])->name('errors.404');
Route::get('/500', [PageController::class, 'error500'])->name('errors.500');
