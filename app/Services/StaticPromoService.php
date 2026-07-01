<?php

namespace App\Services;

use App\Models\Frontend\Promo\PriceProductSetting;

class StaticPromoService
{
    public static function forProduct(object $product): ?array
    {
        $dbPromo = PriceProductSetting::active()
            ->whereIn('type', [1, 2])
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
                'label' => ($type === 'percentage' ? (float) $discountValue . '%' : 'Rp ' . number_format((float) $discountValue, 0, ',', '.')),
            ];
        }

        $globalPromo = PriceProductSetting::active()
            ->whereIn('type', [1, 2])
            ->where('scope', 1)
            ->whereHas('products', fn($q) => $q->where('products.id', $product->id)->where('products.deleted', false))
            ->first();

        if ($globalPromo) {
            $type = $globalPromo->discount_type == 1 ? 'percentage' : 'fixed';
            return [
                'discount_type' => $type,
                'discount_value' => (float) $globalPromo->discount_value,
                'code' => $globalPromo->code,
                'label' => ($type === 'percentage' ? (float) $globalPromo->discount_value . '%' : 'Rp ' . number_format((float) $globalPromo->discount_value, 0, ',', '.')),
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

    public static function calculateItemDiscounts(array $item, int $quantity, float $originalPrice): array
    {
        $volumeSettings = PriceProductSetting::active()->where('type', 2)->with(['volumeTiers', 'products'])->get();
        
        $matchedTier = null;
        $matchedVs = null;
        foreach ($volumeSettings as $vs) {
            if ($vs->scope == 2) {
                $hasProduct = $vs->products->contains('id', $item['product_id']);
                if (!$hasProduct) continue;
            }
            $tiers = $vs->volume_tiers ?? [];
            if (!empty($tiers) && is_array($tiers)) {
                foreach ($tiers as $tier) {
                    $minQty = $tier['min_quantity'] ?? 0;
                    $maxQty = $tier['max_quantity'] ?? PHP_INT_MAX;
                    if ($quantity >= $minQty && $quantity <= $maxQty) {
                        $matchedTier = $tier;
                        $matchedVs = $vs;
                        break;
                    }
                }
            }
            if ($matchedTier) break;
        }

        $staticDiscount = 0.0;
        $volumeDiscount = 0.0;

        if ($matchedTier) {
            $discountType = (int) ($matchedTier['discount_type'] ?? $matchedVs->discount_type);
            $discountVal = (float) ($matchedTier['discount_value'] ?? $matchedVs->discount_value);

            if ($discountType == 1) {
                $promotionalPrice = $originalPrice * (1 - $discountVal / 100);
            } else {
                $promotionalPrice = $discountVal;
            }

            $volumeDiscount = max(0.0, ($originalPrice - $promotionalPrice) * $quantity);
        } else {
            $promoItem = \App\Models\Frontend\Promo\PriceProductSettingItem::where('product_id', $item['product_id'])
                ->where('deleted', false)
                ->whereHas('priceProductSetting', function($query) {
                    $query->whereIn('type', [1, 2])->where('deleted', false);
                })
                ->where(function($q) use ($item) {
                    $variantId = $item['variant_id'] ?? ($item['id'] !== $item['product_id'] ? $item['id'] : null);
                    if ($variantId) {
                        $q->where('variant_id', $variantId)->orWhereNull('variant_id');
                    } else {
                        $q->whereNull('variant_id');
                    }
                })
                ->orderByRaw('CASE WHEN variant_id IS NOT NULL THEN 1 ELSE 2 END')
                ->first();

            if ($promoItem) {
                $discountType = $promoItem->discount_type;
                $discountValue = (float) $promoItem->discount_value;
                if ($discountType == 1) {
                    $staticDiscount = ($originalPrice * $discountValue / 100) * $quantity;
                } else {
                    $staticDiscount = $discountValue * $quantity;
                }
            } else {
                $globalPromo = PriceProductSetting::active()
                    ->where('type', 1)
                    ->where('scope', 1)
                    ->first();
                if ($globalPromo) {
                    $discountType = $globalPromo->discount_type;
                    $discountValue = (float) $globalPromo->discount_value;
                    if ($discountType == 1) {
                        $staticDiscount = ($originalPrice * $discountValue / 100) * $quantity;
                    } else {
                        $staticDiscount = $discountValue * $quantity;
                    }
                }
            }
        }

        return [
            'static_discount' => $staticDiscount,
            'volume_discount' => $volumeDiscount,
            'promotional_price' => $matchedTier ? ($originalPrice - ($volumeDiscount / $quantity)) : ($originalPrice - ($staticDiscount / $quantity))
        ];
    }
}