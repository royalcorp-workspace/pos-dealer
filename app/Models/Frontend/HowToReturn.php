<?php

declare(strict_types=1);

namespace App\Models\Frontend;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class HowToReturn extends Model
{
    use HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $table = 'how_to_returns';

    protected static function boot(): void
    {
        parent::boot();
        static::addGlobalScope('not-deleted', fn($q) => $q->where('how_to_returns.deleted', false));
        static::addGlobalScope('published', fn($q) => $q->where('how_to_returns.is_published', true));
    }

    protected $fillable = [
        'title',
        'slug',
        'content',
        'steps',
        'featured_image',
        'is_published',
        'sort_order',
        'meta_title',
        'meta_description',
        'creator',
        'editor',
        'deleted',
    ];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'deleted' => 'boolean',
            'sort_order' => 'integer',
            'steps' => 'array',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    protected $appends = ['featured_image_url'];

    public function getFeaturedImageUrlAttribute(): ?string
    {
        return media_url($this->featured_image);
    }
}
