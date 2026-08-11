<?php

declare(strict_types=1);

namespace App\Models\Frontend;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventPopup extends Model
{
    use HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $table = 'event_popups';

    protected $fillable = [
        'event_id',
        'title',
        'image_url',
        'link_url',
        'button_text',
        'is_active',
        'deleted',
        'creator',
        'editor',
    ];

    protected $casts = [
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

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class, 'event_id');
    }
}
