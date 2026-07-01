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

        View::composer('frontend.layouts.app', function ($view) {
            $about = AboutUs::first();
            $whatsappNumber = $about && isset($about->social_media['whatsapp'])
                ? preg_replace('/[^0-9]/', '', $about->social_media['whatsapp'])
                : '6281112345678';
            $whatsappUrl = 'https://wa.me/' . $whatsappNumber;
            $view->with(compact('about', 'whatsappUrl'));
        });
    }
}
