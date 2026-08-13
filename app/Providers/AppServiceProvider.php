<?php

namespace {
    if (!function_exists('cms_asset')) {
        function cms_asset($path) {
            if (!$path) return '';
            if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
                return $path;
            }
            $cmsUrl = rtrim(env('CMS_URL', 'http://127.0.0.1:82'), '/');
            return $cmsUrl . '/storage/' . ltrim($path, '/');
        }
    }
}

namespace App\Providers {
    use Illuminate\Support\ServiceProvider;

    class AppServiceProvider extends ServiceProvider
    {
        /**
         * Register any application services.
         */
        public function register(): void
        {
            //
        }

        /**
         * Bootstrap any application services.
         */
        public function boot(): void
        {
            if (env('APP_ENV') === 'production' || env('FORCE_HTTPS', false)) {
                \Illuminate\Support\Facades\URL::forceScheme('https');
            }
        }
    }
}
