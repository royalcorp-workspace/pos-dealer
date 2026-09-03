<?php

declare(strict_types=1);

namespace App\Models\Frontend;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class WarrantyClaim extends Model
{
    use HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $table = 'warranty_claims';

    protected static function boot(): void
    {
        parent::boot();
        static::addGlobalScope('not-deleted', fn($q) => $q->where('warranty_claims.deleted', false));
        static::addGlobalScope('published', fn($q) => $q->where('warranty_claims.is_published', true));
    }

    protected $fillable = [
        'title',
        'slug',
        'content',
        'steps',
        'required_documents',
        'processing_time_days',
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
            'required_documents' => 'array',
            'processing_time_days' => 'integer',
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
