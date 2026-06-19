<?php

namespace App\Services;

use App\Models\Frontend\Promo\PriceProductSetting;

class StaticPromoService
{
    public static function forProduct(object $product): ?array
    {
        $dbPromo = PriceProductSetting::active()
            ->where('type', 1)
            ->where('scope', 2)
            ->whereHas('products', fn($q) => $q->where('products.id', $product->id)->where('products.deleted', false))
            ->first();

        if ($dbPromo) {
            $pivot = $dbPromo->products->first(fn($p) => $p->id === $product->id)?->pivot;
            $discountType = $pivot->discount_type ?? $dbPromo->discount_type;
            $discountValue = $pivot->discount_value ?? $dbPromo->discount_value;
            $type = $discountType == 1 ? 'percentage' : 'fixed';
            return [
                'discount_type' => $type,
                'discount_value' => (float) $discountValue,
                'code' => $dbPromo->code,
                'label' => ($type === 'percentage' ? (float) $discountValue . '%' : 'Rp ' . number_format($discountValue, 0, ',', '.')),
            ];
        }

        $globalPromo = PriceProductSetting::active()
            ->where('type', 1)
            ->where('scope', 1)
            ->first();

        if ($globalPromo) {
            $type = $globalPromo->discount_type == 1 ? 'percentage' : 'fixed';
            return [
                'discount_type' => $type,
                'discount_value' => (float) $globalPromo->discount_value,
                'code' => $globalPromo->code,
                'label' => ($type === 'percentage' ? (float) $globalPromo->discount_value . '%' : 'Rp ' . number_format($globalPromo->discount_value, 0, ',', '.')),
            ];
        }

        return null;
    }

    public static function discountedPrice(float $price, ?array $promo): float
    {
        if (!$promo) {
            return $price;
        }

        return match ($promo['discount_type']) {
            'percentage' => max(0, $price * (1 - ((float) $promo['discount_value']) / 100)),
            'fixed' => max(0, $price - ((float) $promo['discount_value'])),
            default => $price,
        };
    }
}