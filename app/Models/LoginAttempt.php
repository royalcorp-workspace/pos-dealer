<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class LoginAttempt extends Model
{
    protected $table = 'login_attempts';

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'user_id',
        'ip_address',
        'email',
        'success',
        'attempted_at',
        'locked_until',
    ];

    public $timestamps = false;

    protected $casts = [
        'success' => 'boolean',
        'attempted_at' => 'datetime',
        'locked_until' => 'datetime',
        'user_id' => 'string',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model): void {
            if (empty($model->getKey())) {
                $model->setAttribute($model->getKeyName(), (string) Str::uuid());
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}