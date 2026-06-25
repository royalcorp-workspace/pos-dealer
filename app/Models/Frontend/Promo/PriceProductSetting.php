<?php

declare(strict_types=1);

namespace App\Models\Frontend\Promo;

use App\Models\Frontend\ProductsCatalog\Product;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class PriceProductSetting extends Model
{
    use HasUuids;

    protected $table = 'price_product_settings';

    protected $fillable = [
        'code',
        'title',
        'description',
        'type',
        'scope',
        'discount_type',
        'discount_value',
        'min_purchase',
        'max_discount',
        'start_date',
        'end_date',
        'image_url',
        'is_active',
        'is_featured',
        'sort_order',
        'creator',
        'editor',
        'deleted',
        'volume_tiers',
    ];

    protected function casts(): array
    {
        return [
            'type' => 'integer',
            'scope' => 'integer',
            'discount_type' => 'integer',
            'discount_value' => 'decimal:2',
            'min_purchase' => 'decimal:2',
            'max_discount' => 'decimal:2',
            'start_date' => 'datetime',
            'end_date' => 'datetime',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'sort_order' => 'integer',
            'deleted' => 'boolean',
            'volume_tiers' => 'array',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::addGlobalScope('active', function ($query) {
            $query->where('is_active', true)
                ->where('deleted', false)
                ->where(function ($q) {
                    $q->whereNull('start_date')->orWhere('start_date', '<=', now());
                })
                ->where(function ($q) {
                    $q->whereNull('end_date')->orWhere('end_date', '>=', now());
                });
        });
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where('deleted', false)
            ->where(function ($q) {
                $q->whereNull('start_date')->orWhere('start_date', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('end_date')->orWhere('end_date', '>=', now());
            });
    }

    public function scopeFeatured($query)
    {
        return $query->active()->where('is_featured', true)->orderBy('sort_order');
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'price_product_setting_items', 'price_product_setting_id', 'product_id')
            ->withPivot('discount_type', 'discount_value');
    }

    public function isVolumeDiscount(): bool
    {
        return (int) $this->type === 2;
    }

    public function typeLabel(): string
    {
        return $this->isVolumeDiscount() ? 'Diskon Volume' : 'Diskon Langsung';
    }
}