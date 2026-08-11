<?php

declare(strict_types=1);

namespace App\Models\Frontend;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Event extends Model
{
    use HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $table = 'events';

    protected $fillable = [
        'title',
        'slug',
        'description',
        'start_date',
        'end_date',
        'is_active',
        'deleted',
        'creator',
        'editor',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'is_active' => 'boolean',
        'deleted' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected static function boot(): void
    {
        parent::boot();
        static::addGlobalScope('active', fn($q) => $q->where('is_active', true)->where('deleted', false));
    }

    public function popups(): HasMany
    {
        return $this->hasMany(EventPopup::class, 'event_id');
    }

    public function isActiveEvent(): bool
    {
        if (!$this->is_active) return false;
        if ($this->start_date && now()->lt($this->start_date)) return false;
        if ($this->end_date && now()->gt($this->end_date)) return false;
        return true;
    }
}
