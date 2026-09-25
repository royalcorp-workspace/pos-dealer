<?php

namespace App\Providers;

use App\Models\Frontend\AboutUs;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;

class ViewServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        View::composer('frontend.components.footer', function ($view) {
            $about = AboutUs::first();
            $view->with(compact('about'));
        });

        View::composer(['frontend.layouts.app', 'frontend.*'], function ($view) {
            $about = AboutUs::first();
            $whatsappNumber = $about && isset($about->social_media['whatsapp'])
                ? preg_replace('/[^0-9]/', '', $about->social_media['whatsapp'])
                : '6281112345678';
            $whatsappUrl = 'https://wa.me/' . $whatsappNumber;

            $userId = session()->get('is_logged_in')
                ? (session()->get('user')['id'] ?? session()->get('user')['sub'] ?? null)
                : null;
            if ($userId && !session()->has('wishlist')) {
                $customerId = \Illuminate\Support\Facades\DB::table('customers')->where('user_id', $userId)->value('id');
                if ($customerId) {
                    $wishlistIds = \App\Models\Frontend\ProductsCatalog\Wishlist::where('customer_id', $customerId)->pluck('product_id')->all();
                    session()->put('wishlist', $wishlistIds);
                }
            }

            $currentWishlist = session()->get('wishlist', []);
            $wishlistCount = count($currentWishlist);

            $view->with(compact('about', 'whatsappUrl'))
                 ->with('wishlistCount', $wishlistCount);
        });
    }
}
