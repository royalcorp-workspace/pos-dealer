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
    }

    protected $fillable = [
        'category_id',
        'brand_id',
        'name',
        'slug',
        'thumbnail',
        'alt_text',
        'short_description',
        'description',
        'base_price',
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
            'base_price' => 'decimal:2',
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

    public function getThumbnailUrlAttribute(): ?string
    {
        return $this->thumbnail ? asset('storage/' . $this->thumbnail) : null;
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
}