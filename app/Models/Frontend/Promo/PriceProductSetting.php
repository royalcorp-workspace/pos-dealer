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
        'event_id',
        'bundling_id',
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
            $query->where('price_product_settings.is_active', true)
                ->where('price_product_settings.deleted', false);
        });
    }

    public function scopeActive($query)
    {
        return $query->where('price_product_settings.is_active', true)
            ->where('price_product_settings.deleted', false);
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

    public function bundlings(): BelongsToMany
    {
        return $this->belongsToMany(\App\Models\Frontend\ProductsCatalog\ProductBundling::class, 'price_product_setting_items', 'price_product_setting_id', 'bundling_id')
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

    public function volumeTiers()
    {
        return $this->hasMany(VolumeTier::class, 'price_product_setting_id', 'id')->orderBy('sort_order');
    }

    public function getVolumeTiersAttribute()
    {
        if ($this->relationLoaded('volumeTiers')) {
            $relation = $this->getRelation('volumeTiers');
            if ($relation && $relation->isNotEmpty()) {
                $tiers = [];
                foreach ($relation as $vt) {
                    $dt = $vt->discount_type;
                    $dv = $vt->discount_value;
                    if ($dt == 1 && $dv > 100) {
                        $dt = 2; // Auto-correct to nominal
                    }
                    $tiers[] = [
                        'min_quantity' => $vt->min_purchase,
                        'discount_type' => $dt,
                        'discount_value' => $dv,
                    ];
                }
                return $tiers;
            }
        }

        // Fallback to relationship query if not loaded yet but has records
        if (!$this->relationLoaded('volumeTiers') && $this->volumeTiers()->exists()) {
            $relation = $this->volumeTiers()->get();
            $tiers = [];
            foreach ($relation as $vt) {
                $dt = $vt->discount_type;
                $dv = $vt->discount_value;
                if ($dt == 1 && $dv > 100) {
                    $dt = 2; // Auto-correct to nominal
                }
                $tiers[] = [
                    'min_quantity' => $vt->min_purchase,
                    'discount_type' => $dt,
                    'discount_value' => $dv,
                ];
            }
            return $tiers;
        }

        // Otherwise fall back to legacy JSON column
        $value = $this->attributes['volume_tiers'] ?? null;
        if ($value) {
            $decoded = is_string($value) ? json_decode($value, true) : $value;
            if (is_array($decoded)) {
                $tiers = [];
                foreach ($decoded as $tier) {
                    $dt = $tier['discount_type'] ?? 1;
                    $dv = $tier['discount_value'] ?? 0;
                    if ($dt == 1 && $dv > 100) {
                        $dt = 2; // Auto-correct to nominal
                    }
                    $tiers[] = [
                        'min_quantity' => $tier['min_quantity'] ?? ($tier['min_purchase'] ?? 0),
                        'discount_type' => $dt,
                        'discount_value' => $dv,
                    ];
                }
                return $tiers;
            }
        }

        return [];
    }
}