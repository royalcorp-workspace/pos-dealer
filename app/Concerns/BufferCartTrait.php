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
        if (!session()->has('guest_session_id')) {
            session()->put('guest_session_id', session()->getId() ?: Str::random(40));
        }
        return (string) session()->get('guest_session_id');
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

        return Buffer::create([
            'id' => Str::uuid()->toString(),
            'customer_id' => $customerId,
            'session_id' => $sessionId,
            'customer_name' => session()->get('user')['name'] ?? null,
            'customer_email' => session()->get('user')['email'] ?? null,
            'creator' => $userId,
            'editor' => $userId,
        ]);
    }

    private function getCurrentBuffer(): ?Buffer
    {
        $customerId = $this->resolveCustomerId();
        $sessionId = $this->getSessionId();

        return Buffer::where(function ($q) use ($customerId, $sessionId) {
            if ($customerId) {
                $q->where('customer_id', $customerId)->orWhere('session_id', $sessionId);
            } else {
                $q->where('session_id', $sessionId);
            }
        })->first();
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
                if ($isBundle && $item->item_notes) {
                    $bundleNotes = json_decode($item->item_notes, true) ?? [];
                }

                return [
                    'id' => $item->id,
                    'product_id' => $item->product_id,
                    'variant_id' => $item->product_variant_id,
                    'name' => $item->name,
                    'brand' => $item->product->brand->name ?? '',
                    'image' => $item->product->thumbnail_url ?? '',
                    'sell_price' => (float) $item->unit_price,
                    'quantity' => (int) $item->quantity,
                    'item_note' => $item->item_notes ?? '',
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
}
