<?php

declare(strict_types=1);

namespace App\Concerns;

use Illuminate\Database\Eloquent\Model;

trait HasAuditUser
{
    public static function resolveCurrentUsername(): string
    {
        // 1. Session user (takes precedence for web storefront/customer session)
        if (function_exists('session') && session()->has('user')) {
            $user = session()->get('user');
            if (is_array($user)) {
                if (!empty($user['username'])) {
                    return (string) $user['username'];
                }
                if (!empty($user['name'])) {
                    return (string) $user['name'];
                }
                if (!empty($user['email'])) {
                    return (string) $user['email'];
                }
            } elseif (is_object($user)) {
                if (!empty($user->username)) {
                    return (string) $user->username;
                }
                if (!empty($user->name)) {
                    return (string) $user->name;
                }
                if (!empty($user->email)) {
                    return (string) $user->email;
                }
            }
        }

        // 2. Auth Guard
        if (function_exists('auth') && auth()->check()) {
            $user = auth()->user();
            if (!empty($user->username)) {
                return (string) $user->username;
            }
            if (!empty($user->name)) {
                return (string) $user->name;
            }
            if (!empty($user->email)) {
                return (string) $user->email;
            }
        }

        return 'customer';
    }

    public static function bootHasAuditUser(): void
    {
        static::creating(function (Model $model) {
            $username = static::resolveCurrentUsername();
            if (empty($model->creator) && $model->isFillable('creator')) {
                $model->creator = $username;
            }
            if (empty($model->editor) && $model->isFillable('editor')) {
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
        });

        static::updating(function (Model $model) {
            $username = static::resolveCurrentUsername();
            if ($model->isFillable('editor')) {
                $model->editor = $username;
            }
            if ($model->usesTimestamps()) {
                $model->updated_at = now();
            }
        });
    }
}
