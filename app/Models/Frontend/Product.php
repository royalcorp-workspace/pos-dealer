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

    protected $appends = ['thumbnail_url'];

    protected $fillable = [
        'category_id',
        'brand_id',
        'name',
        'slug',
        'thumbnail',
        'alt_text',
        'short_description',
        'description',
        'courier_type',
        'shipping_scheme',
        'shipping_cost',
        'length',
        'width',
        'height',
        'weight',
        'best_seller',
        'is_new',
        'sort_order',
        'status',
        'show_on_web',
        'creator',
        'editor',
        'deleted',
    ];

    protected function casts(): array
    {
        return [
            'images' => 'array',
            'specifications' => 'array',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'status' => 'boolean',
            'show_on_web' => 'boolean',
            'deleted' => 'boolean',
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

    public function getCourierTypeLabelAttribute(): string
    {
        return match($this->courier_type) {
            'toko' => 'Kurir Dari Toko',
            'expedisi' => 'Kurir Dari Expedisi',
            default => 'Dari Keduanya',
        };
    }

    public function getThumbnailUrlAttribute(): string
    {
        if (!$this->thumbnail) {
            return asset('images/dummy/header.jpg');
        }

        return media_url($this->thumbnail);
    }

    public function getPriceAttribute(): float
    {
        if ($this->relationLoaded('variants') && $this->variants->isNotEmpty()) {
            $valid = $this->variants->filter(fn($v) => (float)$v->sell_price > 0 && !($v->deleted ?? false));
            if ($valid->isNotEmpty()) {
                return (float) $valid->min('sell_price');
            }
        }
        $v = $this->variants()->where('deleted', false)->where('sell_price', '>', 0)->orderBy('sell_price')->first();
        if ($v) {
            return (float) $v->sell_price;
        }
        return (float) ($this->attributes['base_price'] ?? 0);
    }

    public function getSellPriceAttribute(): float
    {
        return $this->price;
    }
}
