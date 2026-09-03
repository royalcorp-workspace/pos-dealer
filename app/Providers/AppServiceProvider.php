<?php

namespace {
    if (!function_exists('media_url')) {
        function media_url($path) {
            if (!$path) return '';
            if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
                return $path;
            }
            $s3Url = rtrim((string)(config('filesystems.disks.s3.url') ?? env('AWS_URL', '')), '/');
            if ($s3Url) {
                return $s3Url . '/' . ltrim($path, '/');
            }
            $cmsUrl = rtrim(env('CMS_URL', 'http://127.0.0.1:82'), '/');
            return $cmsUrl . '/storage/' . ltrim($path, '/');
        }
    }

    if (!function_exists('cms_asset')) {
        function cms_asset($path) {
            return media_url($path);
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
            if (!app()->environment('local') || env('FORCE_HTTPS', false)) {
                \Illuminate\Support\Facades\URL::forceScheme('https');
            }
        }
    }
}
