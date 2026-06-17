<?php

use App\Http\Controllers\Api\GoogleAuthController;
use App\Http\Controllers\Frontend\AuthController;
use App\Http\Controllers\Frontend\CartController;
use App\Http\Controllers\Frontend\DashboardController;
use App\Http\Controllers\Frontend\HomeController;
use App\Http\Controllers\Frontend\PageController;
use App\Http\Controllers\Frontend\ProductController;
use App\Http\Controllers\Frontend\ProductCatalogController;

use App\Models\Frontend\ProductsCatalog\Brand;
use App\Models\Frontend\ProductsCatalog\Product;
use App\Models\Frontend\ProductsCatalog\ProductCategory;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/home', fn () => redirect()->route('home'))->name('home.redirect');

Route::get('/sitemap.xml', function () {
    $urls = [
        [
            'loc' => route('home'),
            'lastmod' => now()->toIso8601String(),
            'changefreq' => 'daily',
            'priority' => '1.0',
        ],
        [
            'loc' => route('categories'),
            'lastmod' => now()->toIso8601String(),
            'changefreq' => 'weekly',
            'priority' => '0.9',
        ],
        [
            'loc' => route('brands'),
            'lastmod' => now()->toIso8601String(),
            'changefreq' => 'weekly',
            'priority' => '0.8',
        ],
        [
            'loc' => route('promos'),
            'lastmod' => now()->toIso8601String(),
            'changefreq' => 'daily',
            'priority' => '0.8',
        ],
        [
            'loc' => route('blog'),
            'lastmod' => now()->toIso8601String(),
            'changefreq' => 'weekly',
            'priority' => '0.7',
        ],
        [
            'loc' => route('help'),
            'lastmod' => now()->toIso8601String(),
            'changefreq' => 'monthly',
            'priority' => '0.6',
        ],
    ];

    ProductCategory::where('deleted', false)
        ->whereNotNull('slug')
        ->orderBy('sort_order')
        ->pluck('slug')
        ->each(function ($slug) use (&$urls) {
            $urls[] = [
                'loc' => route('category.show', $slug),
                'lastmod' => now()->toIso8601String(),
                'changefreq' => 'weekly',
                'priority' => '0.8',
            ];
        });

    Brand::where('deleted', false)
        ->whereNotNull('slug')
        ->orderBy('sort_order')
        ->pluck('slug')
        ->each(function ($slug) use (&$urls) {
            $urls[] = [
                'loc' => route('products.index', ['type' => 'brand', 'value' => $slug]),
                'lastmod' => now()->toIso8601String(),
                'changefreq' => 'weekly',
                'priority' => '0.8',
            ];
        });

    Product::where('deleted', false)
        ->whereNotNull('slug')
        ->orderBy('sort_order')
        ->pluck('slug')
        ->each(function ($slug) use (&$urls) {
            $urls[] = [
                'loc' => route('products.show', $slug),
                'lastmod' => now()->toIso8601String(),
                'changefreq' => 'weekly',
                'priority' => '0.9',
            ];
        });

    $xml = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
    $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . PHP_EOL;

    foreach ($urls as $url) {
        $xml .= '  <url>' . PHP_EOL;
        $xml .= '    <loc>' . e($url['loc']) . '</loc>' . PHP_EOL;
        $xml .= '    <lastmod>' . e($url['lastmod']) . '</lastmod>' . PHP_EOL;
        $xml .= '    <changefreq>' . e($url['changefreq']) . '</changefreq>' . PHP_EOL;
        $xml .= '    <priority>' . e($url['priority']) . '</priority>' . PHP_EOL;
        $xml .= '  </url>' . PHP_EOL;
    }

    $xml .= '</urlset>' . PHP_EOL;

    return response($xml, 200)->header('Content-Type', 'application/xml; charset=UTF-8');
})->name('sitemap');

Route::get('/robots.txt', function () {
    // Explicitly allow AI search crawlers for Generative Engine Optimization (GEO)
    $robots = "User-agent: GPTBot" . PHP_EOL;
    $robots .= "Allow: /" . PHP_EOL . PHP_EOL;

    $robots .= "User-agent: ChatGPT-User" . PHP_EOL;
    $robots .= "Allow: /" . PHP_EOL . PHP_EOL;

    $robots .= "User-agent: Google-Extended" . PHP_EOL;
    $robots .= "Allow: /" . PHP_EOL . PHP_EOL;

    $robots .= "User-agent: ClaudeBot" . PHP_EOL;
    $robots .= "Allow: /" . PHP_EOL . PHP_EOL;

    $robots .= "User-agent: PerplexityBot" . PHP_EOL;
    $robots .= "Allow: /" . PHP_EOL . PHP_EOL;

    $robots .= "User-agent: *" . PHP_EOL;
    $robots .= "Disallow: /checkout" . PHP_EOL;
    $robots .= "Disallow: /payment" . PHP_EOL;
    $robots .= "Disallow: /dashboard" . PHP_EOL;
    $robots .= "Disallow: /forgot-password" . PHP_EOL;
    $robots .= "Disallow: /reset-password" . PHP_EOL;
    $robots .= "Disallow: /password-otp-sent" . PHP_EOL;
    $robots .= "Disallow: /email/verify" . PHP_EOL;
    $robots .= "Disallow: /auth/" . PHP_EOL;
    $robots .= "Allow: /" . PHP_EOL . PHP_EOL;
    $robots .= "Sitemap: " . url('/sitemap.xml') . PHP_EOL;

    return response($robots, 200)->header('Content-Type', 'text/plain; charset=UTF-8');
});

Route::get('/products', [ProductCatalogController::class, 'index'])->name('products.index');
Route::get('/products/{product:slug}', [ProductCatalogController::class, 'show'])->name('products.show');
Route::get('/category/{categorySlug}', [ProductCatalogController::class, 'index'])->name('category.show');

Route::get('/brands', [PageController::class, 'brands'])->name('brands');
Route::get('/categories', [PageController::class, 'categories'])->name('categories');
Route::get('/promos', [PageController::class, 'promos'])->name('promos');
Route::get('/blog', [PageController::class, 'blog'])->name('blog');
Route::get('/help', [PageController::class, 'help'])->name('help');

Route::get('/{tagSlug}', [ProductCatalogController::class, 'index'])->where('tagSlug', '^[a-z0-9-]+(-[a-z0-9-]+)*$')->name('products.by-tag');
Route::get('/400', [PageController::class, 'error400'])->name('errors.400');
Route::get('/403', [PageController::class, 'error403'])->name('errors.403');
Route::get('/404', [PageController::class, 'error404'])->name('errors.404');
Route::get('/500', [PageController::class, 'error500'])->name('errors.500');

Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

Route::post('/login', [AuthController::class, 'login'])->name('login');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
Route::post('/devices/{device}/logout', [AuthController::class, 'logoutDevice'])->name('devices.logout');

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
Route::post('/cart/toggle-wishlist', [CartController::class, 'toggleWishlist'])->name('cart.toggle-wishlist');
Route::post('/cart/update/{id}', [CartController::class, 'update'])->name('cart.update');
Route::post('/cart/remove/{id}', [CartController::class, 'remove'])->name('cart.remove');
Route::get('/checkout', function () {
    $cart = session()->get('cart', []);
    return view('frontend.checkout', compact('cart'));
})->name('checkout');
Route::post('/checkout', function () {
    $cart = session()->get('cart', []);
    session()->put('cart_backup', $cart);
    session()->put('cart', []);
    return redirect()->route('payment');
})->name('checkout.process');

Route::get('/payment', function () {
    $order = [
        'id' => 'ORD-' . date('Ymd') . '-' . rand(1000, 9999),
        'items' => session()->get('cart_backup', []),
    ];
    $paymentMethods = [
        ['code' => 'bca', 'name' => 'BCA', 'type' => 'transfer', 'icon' => 'bank'],
        ['code' => 'mandiri', 'name' => 'Mandiri', 'type' => 'transfer', 'icon' => 'bank'],
        ['code' => 'bni', 'name' => 'BNI', 'type' => 'transfer', 'icon' => 'bank'],
        ['code' => 'bri', 'name' => 'BRI', 'type' => 'transfer', 'icon' => 'bank'],
        ['code' => 'gopay', 'name' => 'GoPay', 'type' => 'ewallet', 'icon' => 'wallet'],
        ['code' => 'ovo', 'name' => 'OVO', 'type' => 'ewallet', 'icon' => 'wallet'],
        ['code' => 'dana', 'name' => 'DANA', 'type' => 'ewallet', 'icon' => 'wallet'],
        ['code' => 'shopeepay', 'name' => 'ShopeePay', 'type' => 'ewallet', 'icon' => 'wallet'],
    ];
    return view('frontend.payment', compact('order', 'paymentMethods'));
})->name('payment');

Route::get('/thankyou', function () {
    return view('frontend.thankyou');
})->name('thankyou');

