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
            if (
                request()->server('HTTP_X_FORWARDED_PROTO') === 'https'
                || request()->header('X-Forwarded-Proto') === 'https'
                || request()->isSecure()
                || !app()->environment('local')
                || env('FORCE_HTTPS', false)
            ) {
                \Illuminate\Support\Facades\URL::forceScheme('https');
            }

            // Global audit listener: automatically populate creator, editor, and timestamps
            \Illuminate\Support\Facades\Event::listen('eloquent.creating: *', function ($eventName, array $data) {
                $model = $data[0] ?? null;
                if ($model instanceof \Illuminate\Database\Eloquent\Model) {
                    $username = \App\Concerns\HasAuditUser::resolveCurrentUsername();
                    if ($model->isFillable('creator') && empty($model->creator)) {
                        $model->creator = $username;
                    }
                    if ($model->isFillable('editor') && empty($model->editor)) {
                        $model->editor = $username;
                    }
                    if ($model->usesTimestamps()) {
                        if (empty($model->created_at)) {
                            $model->created_at = now();
                        }
                        if (empty($model->updated_at)) {
                            $model->updated_at = now();
                        }
                    }
                }
            });

            \Illuminate\Support\Facades\Event::listen('eloquent.updating: *', function ($eventName, array $data) {
                $model = $data[0] ?? null;
                if ($model instanceof \Illuminate\Database\Eloquent\Model) {
                    $username = \App\Concerns\HasAuditUser::resolveCurrentUsername();
                    if ($model->isFillable('editor')) {
                        $model->editor = $username;
                    }
                    if ($model->usesTimestamps()) {
                        $model->updated_at = now();
                    }
                }
            });
        }
    }
}
