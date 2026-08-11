<?php

declare(strict_types=1);

namespace App\Models\Frontend;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $table = 'notifications';

    protected $fillable = [
        'user_id',
        'title',
        'message',
        'type',
        'link_url',
        'is_read',
        'is_broadcast',
        'starts_at',
        'ends_at',
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'is_broadcast' => 'boolean',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected static function boot(): void
    {
        parent::boot();
        static::addGlobalScope('visible', function ($query) {
            $query->where(function ($q) {
                $q->whereNull('starts_at')->orWhere('starts_at', '<=', now());
            })->where(function ($q) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>=', now());
            });
        });
    }

    public function scopeUnread($query, $userId = null)
    {
        $query->where('is_read', false);
        if ($userId) {
            $query->where(function ($q) use ($userId) {
                $q->where('user_id', $userId)->orWhere('is_broadcast', true);
            });
        }
        return $query;
    }
}
