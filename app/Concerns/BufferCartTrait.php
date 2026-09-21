<?php

declare(strict_types=1);

namespace App\Concerns;

use App\Models\Frontend\Buffer\Buffer;
use App\Models\Frontend\Buffer\BufferItem;
use App\Models\Frontend\Customer\Customer;
use App\Models\Frontend\ProductsCatalog\Product;
use Illuminate\Support\Str;

trait BufferCartTrait
{
    private function resolveCustomerId(): ?string
    {
        if (!session()->get('is_logged_in')) {
            return null;
        }

        $user = session()->get('user', []);
        $userId = $user['id'] ?? $user['sub'] ?? null;
        $email = $user['email'] ?? null;

        if (!$userId) {
            return null;
        }

        $customer = Customer::where('user_id', $userId)->first();
        if (!$customer && $email) {
            $customer = Customer::where('email', $email)->first();
        }

        return $customer?->id;
    }

    private function getSessionId(): string
    {
        $cookieSessionId = request()->cookie('guest_session_id');
        $sessionSessionId = session()->get('guest_session_id');

        $sessionId = $sessionSessionId ?: ($cookieSessionId ?: (session()->getId() ?: Str::random(40)));

        if (!session()->has('guest_session_id') || session()->get('guest_session_id') !== $sessionId) {
            session()->put('guest_session_id', $sessionId);
        }

        try {
            cookie()->queue(cookie()->make('guest_session_id', $sessionId, 60 * 24 * 30));
        } catch (\Throwable $e) {}

        return (string) $sessionId;
    }

    private function findOrCreateBuffer(): Buffer
    {
        $buffer = $this->getCurrentBuffer();
        if ($buffer) {
            return $buffer;
        }

        $customerId = $this->resolveCustomerId();
        $sessionId = $this->getSessionId();
        $userId = session()->get('is_logged_in')
            ? (session()->get('user')['id'] ?? session()->get('user')['sub'] ?? null)
            : null;

        $buffer = Buffer::create([
            'id' => Str::uuid()->toString(),
            'customer_id' => $customerId,
            'session_id' => $sessionId,
            'customer_name' => session()->get('user')['name'] ?? null,
            'customer_email' => session()->get('user')['email'] ?? null,
            'creator' => $userId,
            'editor' => $userId,
        ]);

        try {
            cookie()->queue(cookie()->make('buffer_cart_id', $buffer->id, 60 * 24 * 30));
            cookie()->queue(cookie()->make('guest_session_id', $sessionId, 60 * 24 * 30));
        } catch (\Throwable $e) {}

        return $buffer;
    }

    private function getCurrentBuffer(): ?Buffer
    {
        $customerId = $this->resolveCustomerId();
        $sessionId = $this->getSessionId();
        $cookieToken = request()->cookie('guest_session_id');
        $cookieBufferId = request()->cookie('buffer_cart_id');

        $query = Buffer::where(function ($q) use ($customerId, $sessionId, $cookieToken) {
            if ($customerId) {
                $q->where('customer_id', $customerId)
                  ->orWhere('session_id', $sessionId);
                if ($cookieToken) {
                    $q->orWhere('session_id', $cookieToken);
                }
            } else {
                $q->where('session_id', $sessionId);
                if ($cookieToken) {
                    $q->orWhere('session_id', $cookieToken);
                }
            }
        });

        // 1. Highest priority: buffer that actually has items, latest updated
        $buffer = (clone $query)->whereHas('items')->latest('updated_at')->first();

        // 2. If not found by session/customer, check if cookie 'buffer_cart_id' points to a buffer with items
        if (!$buffer && $cookieBufferId) {
            $buffer = Buffer::where('id', $cookieBufferId)->whereHas('items')->first();
        }

        // 3. Fallback: most recently updated buffer matching query
        if (!$buffer) {
            $buffer = (clone $query)->latest('updated_at')->first();
        }

        // 4. Fallback: buffer by cookie buffer_cart_id even if empty
        if (!$buffer && $cookieBufferId) {
            $buffer = Buffer::where('id', $cookieBufferId)->first();
        }

        if ($buffer) {
            $updates = [];
            if ($buffer->session_id !== $sessionId) {
                $updates['session_id'] = $sessionId;
            }
            if ($customerId && $buffer->customer_id !== $customerId) {
                $updates['customer_id'] = $customerId;
            }
            if (!empty($updates)) {
                $buffer->update($updates);
            }

            try {
                cookie()->queue(cookie()->make('buffer_cart_id', $buffer->id, 60 * 24 * 30));
                cookie()->queue(cookie()->make('guest_session_id', $sessionId, 60 * 24 * 30));
            } catch (\Throwable $e) {}
        }

        return $buffer;
    }

    private function recalculateBuffer(Buffer $buffer): void
    {
        $items = $buffer->items()->get();
        $subtotal = $items->sum(fn($item) => (float) $item->unit_price * (int) $item->quantity);
        $discount = $items->sum(function ($item) {
            $itemTotal = (float) $item->unit_price * (int) $item->quantity;
            $discountNominal = (float) $item->discount_nominal;
            $discountPercent = $itemTotal > 0 ? ($itemTotal * (float) $item->discount_percent / 100) : 0;
            return $discountNominal + $discountPercent;
        });
        $tax = 0;
        $total = $subtotal - $discount + $tax;

        $buffer->update([
            'subtotal' => $subtotal,
            'discount' => $discount,
            'tax' => $tax,
            'total' => $total,
        ]);
    }

    private function getBufferCartArray(Buffer $buffer): array
    {
        $items = $buffer->items()
            ->with(['product.brand', 'variant'])
            ->orderBy('created_at')
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

                $basePrice = (float) ($itemMeta['base_price'] ?? $item->unit_price);
                $sellPrice = (float) ($itemMeta['sell_price'] ?? $itemMeta['after_disc_price'] ?? ($item->total > 0 && $item->quantity > 0 ? ($item->total / $item->quantity) : $item->unit_price));

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
                    'base_price' => $basePrice,
                    'sell_price' => $sellPrice,
                    'original_price' => $basePrice,
                    'unit_price' => $basePrice,
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

        session()->put('cart', $items);

        return $items;
    }

    private function getCartCount(array $cart): int
    {
        $count = 0;
        foreach ($cart as $item) {
            $count += $item['quantity'];
        }
        return $count;
    }

    private function getCartTotal(array $cart): float
    {
        $total = 0.0;
        foreach ($cart as $item) {
            $total += $item['sell_price'] * $item['quantity'];
        }
        return $total;
    }

    public function rememberLastProductUrl(?string $url): void
    {
        if ($url) {
            session()->put('last_checkout_product_url', $url);
        }
    }

    public function getLastProductUrl(): string
    {
        $url = session()->get('last_checkout_product_url');
        if ($url) {
            return $url;
        }

        $buffer = $this->getCurrentBuffer();
        if ($buffer) {
            $lastItem = $buffer->items()->latest('created_at')->first();
            if ($lastItem) {
                if ($lastItem->product_id) {
                    $prod = Product::find($lastItem->product_id);
                    if ($prod && !empty($prod->slug)) {
                        $url = route('products.show', $prod->slug);
                        session()->put('last_checkout_product_url', $url);
                        return $url;
                    }
                } elseif ($lastItem->item_notes) {
                    $bundleNotes = json_decode($lastItem->item_notes, true);
                    if (!empty($bundleNotes['bundle_id'])) {
                        $bundle = \App\Models\Frontend\ProductsCatalog\ProductBundling::find($bundleNotes['bundle_id']);
                        if ($bundle && !empty($bundle->slug)) {
                            $url = route('bundling.show', $bundle->slug);
                            session()->put('last_checkout_product_url', $url);
                            return $url;
                        }
                    }
                }
            }
        }

        return route('products.index');
    }
}
