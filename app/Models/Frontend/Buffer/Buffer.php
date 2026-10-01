<?php

declare(strict_types=1);

namespace App\Models\Frontend\Buffer;

use App\Models\Customer;
use App\Models\Frontend\Customer\Customer as FrontendCustomer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Buffer extends Model
{
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'customer_id',
        'session_id',
        'customer_name',
        'customer_email',
        'customer_phone',
        'subtotal',
        'tax',
        'discount',
        'total',
        'courier_id',
        'voucher_id',
        'voucher_nominal',
        'shipping_cost',
        'shipping_cost_subsidy',
        'shipping_addresses_id',
        'transaction_fee',
        'meta',
        'creator',
        'editor',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'tax' => 'decimal:2',
            'discount' => 'decimal:2',
            'total' => 'decimal:2',
            'voucher_nominal' => 'decimal:2',
            'shipping_cost' => 'decimal:2',
            'shipping_cost_subsidy' => 'decimal:2',
            'transaction_fee' => 'decimal:2',
            'meta' => 'array',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(FrontendCustomer::class, 'customer_id', 'id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(BufferItem::class, 'buffer_id', 'id');
    }

    public static function resolveActiveCart(): ?self
    {
        $customerId = null;
        if (session()->get('is_logged_in')) {
            $user = session()->get('user', []);
            $userId = $user['id'] ?? $user['sub'] ?? null;
            $email = $user['email'] ?? null;
            if ($userId) {
                $customer = FrontendCustomer::where('user_id', $userId)->first();
                if (!$customer && $email) {
                    $customer = FrontendCustomer::where('email', $email)->first();
                }
                $customerId = $customer?->id;
            }
        }

        $sessionBufferId = session()->get('buffer_cart_id');
        $sessionGuestId = session()->get('guest_session_id');
        $cookieToken = request()->cookie('guest_session_id');
        $cookieBufferId = request()->cookie('buffer_cart_id') ?: $sessionBufferId;
        $currentSessionId = session()->getId();

        $sessionIds = array_values(array_filter(array_unique([$sessionGuestId, $cookieToken, $currentSessionId])));

        $query = self::where(function ($q) use ($customerId, $sessionIds) {
            if ($customerId) {
                $q->where('customer_id', $customerId);
                if (!empty($sessionIds)) {
                    $q->orWhereIn('session_id', $sessionIds);
                }
            } elseif (!empty($sessionIds)) {
                $q->whereIn('session_id', $sessionIds);
            }
        });

        // 1. Buffer that has items, latest updated
        $buffer = (clone $query)->whereHas('items')->latest('updated_at')->first();

        // 2. Cookie or Session buffer_cart_id if has items
        if (!$buffer && $cookieBufferId) {
            $buffer = self::where('id', $cookieBufferId)->whereHas('items')->first();
        }
        if (!$buffer && $sessionBufferId) {
            $buffer = self::where('id', $sessionBufferId)->whereHas('items')->first();
        }

        // 3. Fallback: latest updated matching query
        if (!$buffer) {
            $buffer = (clone $query)->latest('updated_at')->first();
        }

        // 4. Fallback: buffer by cookie buffer_cart_id even if empty
        if (!$buffer && $cookieBufferId) {
            $buffer = self::where('id', $cookieBufferId)->first();
        }

        return $buffer;
    }

    public static function getActiveCartSummary(): array
    {
        $buffer = self::resolveActiveCart();
        $cart = [];
        if ($buffer) {
            $cart = $buffer->items()
                ->with(['product.brand', 'variant'])
                ->get()
                ->map(function ($item) {
                    $isBundle = str_starts_with($item->name ?? '', 'BUNDLE_');
                    $bundleNotes = [];
                    $userNote = '';
                    if ($item->item_notes) {
                        $decodedNotes = is_string($item->item_notes) ? json_decode($item->item_notes, true) : (is_array($item->item_notes) ? $item->item_notes : null);
                        if (is_array($decodedNotes) && (isset($decodedNotes['bundle_id']) || isset($decodedNotes['bundle_name']) || isset($decodedNotes['items']))) {
                            $isBundle = true;
                            $bundleNotes = $decodedNotes;
                            $userNote = $decodedNotes['user_note'] ?? '';
                        } else {
                            $userNote = is_string($item->item_notes) ? $item->item_notes : '';
                        }
                    }
                    $itemMeta = is_array($item->meta) ? $item->meta : (json_decode((string) $item->meta, true) ?: []);
                    $colorId = $itemMeta['color_id'] ?? null;
                    $colorName = $itemMeta['color_name'] ?? null;
                    $colorCode = $itemMeta['color_code'] ?? null;

                    $displayName = $item->name;
                    if ($isBundle && !empty($bundleNotes['bundle_name'])) {
                        $displayName = $bundleNotes['bundle_name'];
                    }

                    return [
                        'id' => $item->id,
                        'product_id' => $item->product_id,
                        'variant_id' => $item->product_variant_id,
                        'name' => $displayName,
                        'brand' => $item->product->brand->name ?? '',
                        'image' => $item->product->thumbnail_url ?? '',
                        'sell_price' => (float) $item->unit_price,
                        'quantity' => (int) $item->quantity,
                        'item_note' => $userNote,
                        'color_id' => $colorId,
                        'color_name' => $colorName,
                        'color_code' => $colorCode,
                        'type' => $isBundle ? 'bundle' : 'product',
                        'bundle_data' => $bundleNotes,
                    ];
                })
                ->toArray();
        }

        $cartItemCount = (int) collect($cart)->sum('quantity');

        $cartTotal = 0.0;
        foreach ($cart as $item) {
            $isBundle = ($item['type'] ?? null) === 'bundle' || str_starts_with($item['name'] ?? '', 'BUNDLE_');
            $bundleData = $item['bundle_data'] ?? null;

            if ($isBundle && $bundleData) {
                $originalPrice = (float) ($bundleData['bundle_price'] ?? ($bundleData['bundle_total_original'] ?? ($item['sell_price'] ?? 0)));
            } else {
                $variantId = $item['variant_id'] ?? ($item['id'] !== ($item['product_id'] ?? null) ? $item['id'] : null);
                $originalPrice = 0.0;
                if ($variantId) {
                    $variantModel = \App\Models\Frontend\ProductsCatalog\ProductVariant::find($variantId);
                    if ($variantModel) {
                        $originalPrice = (float) $variantModel->sell_price;
                    }
                }
                if ($originalPrice <= 0.0 && !empty($item['product_id'])) {
                    $productModel = \App\Models\Frontend\ProductsCatalog\Product::find($item['product_id']);
                    if ($productModel) {
                        $originalPrice = (float) ($productModel->variants->where('status', true)->min('sell_price') ?? 0);
                    }
                }
                if ($originalPrice <= 0.0) {
                    $originalPrice = (float) ($item['sell_price'] ?? 0);
                }
            }
            $res = \App\Services\StaticPromoService::calculateItemDiscounts($item, (int) ($item['quantity'] ?? 1), $originalPrice);
            $cartTotal += (float) $res['promotional_price'] * (int) ($item['quantity'] ?? 1);
        }

        return [
            'buffer' => $buffer,
            'cart' => $cart,
            'cartItemCount' => $cartItemCount,
            'cartTotal' => $cartTotal,
        ];
    }
}
