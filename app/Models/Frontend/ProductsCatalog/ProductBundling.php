<?php

declare(strict_types=1);

namespace App\Models\Frontend\ProductsCatalog;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductBundling extends Model
{
    use HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $table = 'products_bundling';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'price',
        'image_url',
        'is_active',
        'event_id',
        'deleted',
        'creator',
        'editor',
    ];

    protected $appends = ['thumbnail_url'];

    public function getThumbnailUrlAttribute(): ?string
    {
        if ($this->image_url) {
            return cms_asset($this->image_url);
        }
        return $this->items->first()?->product?->thumbnail_url;
    }

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'is_active' => 'boolean',
            'deleted' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();
        static::addGlobalScope('not-deleted', fn($q) => $q->where('products_bundling.deleted', false));
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function items(): HasMany
    {
        return $this->hasMany(ProductBundlingItem::class, 'product_bundling_id');
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Frontend\Event::class, 'event_id');
    }

    public function priceProductSettings(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(\App\Models\Frontend\Promo\PriceProductSetting::class, 'price_product_setting_items', 'bundling_id', 'price_product_setting_id')
            ->withPivot('discount_type', 'discount_value');
    }
}
