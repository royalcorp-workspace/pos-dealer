<?php

declare(strict_types=1);

namespace App\Models\Frontend;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductVariant extends Model
{
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'product_id',
        'sku',
        'variant_name',
        'width',
        'length',
        'height',
        'weight',
        'shipping_cost',
        'base_price',
        'sell_price',
        'stock_quantity',
        'min_order_qty',
        'sort_order',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'shipping_cost' => 'decimal:2',
            'base_price' => 'decimal:2',
            'sell_price' => 'decimal:2',
            'attributes' => 'array',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function images(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Models\Frontend\ProductsCatalog\ProductImage::class, 'variant_id')->orderBy('sort_order');
    }

    public function image(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(\App\Models\Frontend\ProductsCatalog\ProductImage::class, 'variant_id')->orderBy('sort_order');
    }

    public function getImageUrlAttribute(): ?string
    {
        if ($this->relationLoaded('image') && $this->image) {
            return $this->image->image_url ?? media_url($this->image->image);
        }
        $firstImg = $this->images->first();
        if ($firstImg) {
            return $firstImg->image_url ?? media_url($firstImg->image);
        }
        $rawAttrs = $this->getRawOriginal('attributes');
        $attrs = is_string($rawAttrs) ? json_decode($rawAttrs, true) : ($rawAttrs ?: []);
        $attrImg = $attrs['image'] ?? $attrs['image_url'] ?? null;
        return $attrImg ? media_url($attrImg) : null;
    }

    public function getPriceAttribute()
    {
        return $this->sell_price ?? $this->base_price ?? 0;
    }

    public function getStockQuantityAttribute($value)
    {
        $invStock = \Illuminate\Support\Facades\DB::table('inventories')
            ->where('product_variant_id', $this->id)
            ->where('deleted', false)
            ->sum('available');

        return $invStock !== null ? (int) $invStock : ($value ?? 0);
    }
}
