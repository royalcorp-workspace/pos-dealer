<?php

declare(strict_types=1);

namespace App\Models\Frontend;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class AboutUs extends Model
{
    use HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $table = 'about_us';

    protected static function boot(): void
    {
        parent::boot();
        static::addGlobalScope('active', fn($q) => $q->where('about_us.deleted', false)->where('about_us.is_active', true));
    }

    protected $fillable = [
        'company_name',
        'tagline',
        'description',
        'vision',
        'mission',
        'values',
        'established_year',
        'address',
        'phone',
        'email',
        'logo',
        'cover_image',
        'social_media',
        'is_active',
        'sort_order',
        'creator',
        'editor',
        'deleted',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'deleted' => 'boolean',
            'sort_order' => 'integer',
            'social_media' => 'array',
            'established_year' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    protected $appends = ['logo_url', 'cover_image_url'];

    public function getLogoUrlAttribute(): ?string
    {
        return media_url($this->logo);
    }

    public function getCoverImageUrlAttribute(): ?string
    {
        return media_url($this->cover_image);
    }
}
