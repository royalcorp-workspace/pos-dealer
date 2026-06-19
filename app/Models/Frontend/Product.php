<?php

declare(strict_types=1);

namespace App\Models\Frontend;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'brand_id',
        'category_id',
        'name',
        'slug',
        'description',
        'short_description',
        'price',
        'cost_price',
        'stock',
        'min_stock',
        'sku',
        'barcode',
        'images',
        'specifications',
        'is_active',
        'is_featured',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'cost_price' => 'decimal:2',
            'images' => 'array',
            'specifications' => 'array',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function getAverageRatingAttribute(): float
    {
        return Review::avgRating($this->id);
    }

    public function getReviewCountAttribute(): int
    {
        return $this->reviews()->where('is_published', true)->where('is_approved', true)->count();
    }
}
