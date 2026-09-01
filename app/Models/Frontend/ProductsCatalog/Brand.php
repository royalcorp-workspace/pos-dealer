<?php

declare(strict_types=1);

namespace App\Models\Frontend\ProductsCatalog;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Brand extends Model
{
    use HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $table = 'brands';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'logo',
        'banner',
        'banner_web',
        'banner_mobile',
        'sort_order',
        'status',
        'is_featured',
        'creator',
        'editor',
        'deleted',
    ];

    protected $appends = ['logo_url', 'banner_url', 'banner_web_url', 'banner_mobile_url'];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'status' => 'boolean',
            'is_featured' => 'boolean',
            'deleted' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function getLogoUrlAttribute(): ?string
    {
        return media_url($this->logo);
    }

    public function getBannerUrlAttribute(): ?string
    {
        return media_url($this->banner);
    }

    public function getBannerWebUrlAttribute(): ?string
    {
        return media_url($this->banner_web ?: $this->banner);
    }

    public function getBannerMobileUrlAttribute(): ?string
    {
        return media_url($this->banner_mobile ?: $this->banner);
    }

    public function categories(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(
            ProductCategory::class,
            'brand_category_relations',
            'brand_id',
            'category_id'
        );
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'brand_id');
    }
}