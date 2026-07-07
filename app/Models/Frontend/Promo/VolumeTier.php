<?php

namespace App\Models\Frontend\Promo;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VolumeTier extends Model
{
    protected $table = 'price_product_setting_volume_tiers';

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'price_product_setting_id',
        'min_purchase',
        'discount_type',
        'discount_value',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'min_purchase' => 'integer',
            'discount_type' => 'integer',
            'discount_value' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    public function priceProductSetting(): BelongsTo
    {
        return $this->belongsTo(PriceProductSetting::class, 'price_product_setting_id', 'id');
    }
}
