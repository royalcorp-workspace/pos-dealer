<?php

declare(strict_types=1);

namespace App\Models\Frontend\ProductsCatalog;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $table = 'products';

    protected static function boot(): void
    {
        parent::boot();
        static::addGlobalScope('not-deleted', fn($q) => $q->where('products.deleted', false));
        static::addGlobalScope('sellable', function ($q) {
            $q->where(function ($query) {
                $query->whereHas('variants', function ($q2) {
                          $q2->where('sell_price', '>', 0);
                      });
            });
        });
        static::addGlobalScope('in_promo', function ($q) {
            $hasActiveEvent = \App\Models\Frontend\Event::where('is_active', true)
                ->where('deleted', false)
                ->where(function($query) {
                    $query->whereNull('start_date')->orWhere('start_date', '<=', now());
                })
                ->where(function($query) {
                    $query->whereNull('end_date')->orWhere('end_date', '>=', now());
                })->exists();

            if ($hasActiveEvent) {
                $q->whereHas('priceProductSettings', function ($q2) {
                    $q2->where('price_product_settings.is_active', true)
                       ->where('price_product_settings.deleted', false);
                });
            }
        });
    }

    protected $fillable = [
        'category_id',
        'brand_id',
        'name',
        'slug',
        'code',
        'thumbnail',
        'alt_text',
        'short_description',
        'description',
        'warranty_duration',
        'best_seller',
        'is_new',
        'is_bundle',
        'sort_order',
        'status',
        'creator',
        'editor',
        'deleted',
    ];

    protected function casts(): array
    {
        return [
            'best_seller' => 'boolean',
            'is_new' => 'boolean',
            'is_bundle' => 'boolean',
            'sort_order' => 'integer',
            'status' => 'boolean',
            'deleted' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    protected $appends = ['thumbnail_url'];

    public function getThumbnailUrlAttribute(): string
    {
        if (!$this->thumbnail) {
            return asset('images/dummy/header.jpg');
        }

        if (filter_var($this->thumbnail, FILTER_VALIDATE_URL)) {
            return $this->thumbnail;
        }

        $cmsUrl = env('CMS_URL', '');
        if ($cmsUrl) {
            return rtrim($cmsUrl, '/') . '/storage/' . ltrim($this->thumbnail, '/');
        }

        return asset('storage/' . ltrim($this->thumbnail, '/'));
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class, 'brand_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'category_id');
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class, 'product_id');
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class, 'product_id');
    }


    public function colors(): HasMany
    {
        return $this->hasMany(ProductColor::class, 'product_id');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(ProductTag::class, 'product_tag_relations', 'product_id', 'tag_id');
    }

    public function priceProductSettings(): BelongsToMany
    {
        return $this->belongsToMany(\App\Models\Frontend\Promo\PriceProductSetting::class, 'price_product_setting_items', 'product_id', 'price_product_setting_id')
            ->withPivot('discount_type', 'discount_value');
    }

    public function bundlingItems(): HasMany
    {
        return $this->hasMany(ProductBundlingItem::class, 'product_id');
    }

    public function suggestedProducts(): BelongsToMany
    {
        return $this->belongsToMany(
            Product::class,
            'product_suggestions',
            'product_id',
            'suggested_product_id'
        )->withPivot('sort_order')->orderByPivot('sort_order');
    }
}