<?php

declare(strict_types=1);

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Frontend\Buffer\Buffer;
use App\Models\Frontend\Buffer\BufferItem;
use App\Models\Frontend\Customer\Customer;
use App\Models\Frontend\ProductsCatalog\Product;
use App\Models\Frontend\ProductsCatalog\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CartController extends Controller
{
    public function add(Request $request)
    {
        $request->validate([
            'product_id' => 'required|string|uuid',
            'quantity' => 'required|integer|min:1',
            'variant_id' => 'nullable|string|uuid',
        ]);

        $productId = $request->input('product_id');
        $variantId = $request->input('variant_id');
        $quantity = (int) $request->input('quantity');

        $product = Product::where('id', $productId)->first();
        if (!$product) {
            return response()->json(['error' => 'Product not found'], 404);
        }

        $price = $product->base_price;
        if ($variantId) {
            $variant = ProductVariant::where('id', $variantId)->first();
            if ($variant) {
                $price = $variant->price;
            }
        }

        $staticPromo = \App\Services\StaticPromoService::forProduct($product);
        $price = \App\Services\StaticPromoService::discountedPrice((float) $price, $staticPromo);

        $userId = session()->get('is_logged_in')
            ? (session()->get('user')['id'] ?? session()->get('user')['sub'] ?? null)
            : null;

        $customerId = $this->resolveCustomerId();
        $sessionId = session()->getId();

        $buffer = Buffer::where(function ($q) use ($customerId, $sessionId) {
                if ($customerId) {
                    $q->where('customer_id', $customerId)->orWhere('session_id', $sessionId);
                } else {
                    $q->where('session_id', $sessionId);
                }
            })
            ->first();

        if (!$buffer) {
            $buffer = Buffer::create([
                'id' => Str::uuid()->toString(),
                'customer_id' => $customerId,
                'session_id' => $sessionId,
                'customer_name' => session()->get('user')['name'] ?? null,
                'customer_email' => session()->get('user')['email'] ?? null,
                'creator' => $userId,
                'editor' => $userId,
            ]);
        }

        $cartItemId = $variantId ? $variantId : $productId;
        $existingItem = BufferItem::where('buffer_id', $buffer->id)
            ->where(function ($q) use ($cartItemId, $productId) {
                $q->where('id', $cartItemId)
                  ->orWhere(function ($q2) use ($cartItemId, $productId) {
                      $q2->where('product_variant_id', $cartItemId)
                         ->orWhere(function ($q3) use ($cartItemId, $productId) {
                             $q3->whereNull('product_variant_id')->where('product_id', $cartItemId);
                         });
                  });
            })
            ->first();

        if ($existingItem) {
            $existingItem->update([
                'quantity' => $existingItem->quantity + $quantity,
            ]);
            $item = $existingItem;
        } else {
            $item = BufferItem::create([
                'id' => Str::uuid()->toString(),
                'buffer_id' => $buffer->id,
                'product_id' => $productId,
                'product_variant_id' => $variantId,
                'name' => $product->name,
                'quantity' => $quantity,
                'unit_price' => (float) $price,
                'total' => (float) $price * $quantity,
                'discount_nominal' => 0,
                'discount_percent' => 0,
                'item_notes' => '',
            ]);
        }

        $this->recalculateBuffer($buffer);

        $cart = $this->getBufferCartArray($buffer);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Produk berhasil masuk keranjang',
                'cart' => $cart,
                'cart_count' => $this->getCartCount($cart),
                'cart_total' => $this->getCartTotal($cart),
                'cart_drawer_html' => view('frontend.components.cart-drawer-body', ['cart' => $cart])->render(),
            ]);
        }

        return redirect()->back()->with('success', 'Produk berhasil ditambahkan ke keranjang!');
    }

    public function toggleWishlist(Request $request)
    {
        $request->validate([
            'product_id' => 'required|string|uuid',
        ]);

        $productId = $request->input('product_id');
        $wishlist = session()->get('wishlist', []);

        if (!is_array($wishlist)) {
            $wishlist = [];
        }

        $index = array_search($productId, $wishlist, true);
        $inWishlist = false;

        if ($index !== false) {
            unset($wishlist[$index]);
        } else {
            $wishlist[] = $productId;
            $inWishlist = true;
        }

        session()->put('wishlist', array_values($wishlist));

        return response()->json([
            'success' => true,
            'in_wishlist' => $inWishlist,
        ]);
    }

    public function update(Request $request, string $id)
    {
        $request->validate([
            'quantity' => 'required|integer|min:1',
        ]);

        $quantity = (int) $request->input('quantity');
        $userId = session()->get('is_logged_in')
            ? (session()->get('user')['id'] ?? session()->get('user')['sub'] ?? null)
            : null;
        $customerId = $this->resolveCustomerId();
        $sessionId = session()->getId();

        $buffer = Buffer::where(function ($q) use ($customerId, $sessionId) {
                if ($customerId) {
                    $q->where('customer_id', $customerId)->orWhere('session_id', $sessionId);
                } else {
                    $q->where('session_id', $sessionId);
                }
            })
            ->first();

        if (!$buffer) {
            return response()->json(['error' => 'Cart not found'], 404);
        }

        $item = BufferItem::where('buffer_id', $buffer->id)->where('id', $id)->first();
        if (!$item) {
            return response()->json(['error' => 'Item not found'], 404);
        }

        if ($quantity < 1) {
            $item->delete();
        } else {
            $item->update(['quantity' => $quantity]);
        }

        $this->recalculateBuffer($buffer);
        $cart = $this->getBufferCartArray($buffer);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'cart' => $cart,
                'cart_count' => $this->getCartCount($cart),
                'cart_total' => $this->getCartTotal($cart),
                'cart_drawer_html' => view('frontend.components.cart-drawer-body', ['cart' => $cart])->render(),
            ]);
        }

        return redirect()->back();
    }

    public function remove(Request $request, string $id)
    {
        $userId = session()->get('is_logged_in')
            ? (session()->get('user')['id'] ?? session()->get('user')['sub'] ?? null)
            : null;
        $customerId = $this->resolveCustomerId();
        $sessionId = session()->getId();

        $buffer = Buffer::where(function ($q) use ($customerId, $sessionId) {
                if ($customerId) {
                    $q->where('customer_id', $customerId)->orWhere('session_id', $sessionId);
                } else {
                    $q->where('session_id', $sessionId);
                }
            })
            ->first();

        if ($buffer) {
            $item = BufferItem::where('buffer_id', $buffer->id)->where('id', $id)->first();
            if ($item) {
                $item->delete();
                $this->recalculateBuffer($buffer);
            }
        }

        $cart = $buffer ? $this->getBufferCartArray($buffer) : [];

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'cart' => $cart,
                'cart_count' => $this->getCartCount($cart),
                'cart_total' => $this->getCartTotal($cart),
                'cart_drawer_html' => view('frontend.components.cart-drawer-body', ['cart' => $cart])->render(),
            ]);
        }

        return redirect()->back();
    }

    public function preview(Request $request)
    {
        $userId = session()->get('is_logged_in')
            ? (session()->get('user')['id'] ?? session()->get('user')['sub'] ?? null)
            : null;
        $customerId = $this->resolveCustomerId();
        $sessionId = session()->getId();

        $buffer = Buffer::where(function ($q) use ($customerId, $sessionId) {
                if ($customerId) {
                    $q->where('customer_id', $customerId)->orWhere('session_id', $sessionId);
                } else {
                    $q->where('session_id', $sessionId);
                }
            })
            ->with(['items.product', 'items.variant'])
            ->first();

        if (!$buffer || $buffer->items->isEmpty()) {
            return redirect()->route('checkout')->with('warning', 'Keranjang belanja kosong.');
        }

        $cart = $this->getBufferCartArray($buffer);

        $request->validate([
            'name' => 'required|string|max:200',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:20',
            'city' => 'required|string|max:100',
            'address' => 'required|string|max:1000',
            'postal_code' => 'nullable|string|max:10',
            'courier' => 'required|in:' . implode(',', array_keys(self::SHIPPING_PRICES)),
            'voucher_code' => 'nullable|string|max:500',
            'voucher_codes' => 'nullable|string|max:500',
            'item_notes' => 'nullable|array',
            'item_notes.*' => 'nullable|string|max:500',
            'voucher_discount' => 'nullable|numeric|min:0',
        ]);

        $courier = $request->input('courier');
        $itemNotes = (array) $request->input('item_notes', []);
        $recalculatedCart = [];
        $subtotal = 0.0;
        foreach ($cart as $key => $item) {
            $variantId = $item['variant_id'] ?? ($item['id'] !== $item['product_id'] ? $item['id'] : null);
            $originalPrice = 0.0;
            if ($variantId) {
                $variantModel = \App\Models\Frontend\ProductsCatalog\ProductVariant::find($variantId);
                if ($variantModel) {
                    $originalPrice = (float) $variantModel->price;
                }
            }
            if ($originalPrice <= 0.0) {
                $productModel = \App\Models\Frontend\ProductsCatalog\Product::find($item['product_id']);
                if ($productModel) {
                    $originalPrice = (float) $productModel->base_price;
                }
            }
            if ($originalPrice <= 0.0) {
                $originalPrice = (float) $item['price'];
            }
            $res = \App\Services\StaticPromoService::calculateItemDiscounts($item, (int) $item['quantity'], $originalPrice);
            $item['price'] = $res['promotional_price'];
            $recalculatedCart[$key] = $item;
            $subtotal += $res['promotional_price'] * (int) $item['quantity'];
        }
        $cart = $recalculatedCart;
        $shippingCost = self::SHIPPING_PRICES[$courier] ?? 0;
        $voucherCodes = $this->parseVoucherCodes($request);

        $voucherDiscount = 0;
        if (!empty($voucherCodes)) {
            $userId = session()->get('is_logged_in')
                ? (session()->get('user')['id'] ?? session()->get('user')['sub'] ?? null)
                : null;

            $vouchers = \App\Models\Frontend\Promo\Voucher::active()
                ->with(['products', 'categories'])
                ->where(function ($query) use ($voucherCodes) {
                    foreach ($voucherCodes as $code) {
                        $query->orWhereRaw('LOWER(code) = ?', [strtolower($code)]);
                    }
                })
                ->get()
                ->keyBy(fn($v) => strtoupper($v->code));

            $orderedVouchers = collect($voucherCodes)
                ->map(fn($code) => $vouchers->get(strtoupper($code)))
                ->filter()
                ->values();

            $discountSum = 0;
            foreach ($orderedVouchers as $voucher) {
                if (!$voucher->canBeUsedBy($userId)) continue;

                $eligibleSubtotal = 0.0;
                if ((int) $voucher->scope === 2) {
                    $eligibleProductIds = $voucher->products()->where('deleted', false)->pluck('products.id')->unique()->toArray();
                    $eligibleSubtotal = (float) collect($cart)
                        ->filter(fn($item) => in_array($item['product_id'] ?? null, $eligibleProductIds, true))
                        ->sum(fn($item) => ($item['price'] ?? 0) * ($item['quantity'] ?? 0));
                } elseif ((int) $voucher->scope === 3) {
                    $eligibleProductIds = $voucher->categories()->where('deleted', false)
                        ->with('products')
                        ->get()
                        ->flatMap(fn($category) => $category->products->where('deleted', false)->pluck('id'))
                        ->unique()
                        ->toArray();

                    $eligibleSubtotal = (float) collect($cart)
                        ->filter(fn($item) => in_array($item['product_id'] ?? null, $eligibleProductIds, true))
                        ->sum(fn($item) => ($item['price'] ?? 0) * ($item['quantity'] ?? 0));
                } else {
                    $eligibleSubtotal = (float) collect($cart)
                        ->sum(fn($item) => ($item['price'] ?? 0) * ($item['quantity'] ?? 0));
                }

                if ($eligibleSubtotal < (float) ($voucher->min_purchase ?? 0)) continue;

                if ((int) $voucher->type === 1) {
                    $maxDiscount = ($voucher->max_discount !== null && (float) $voucher->max_discount > 0)
                        ? (float) $voucher->max_discount
                        : PHP_FLOAT_MAX;

                    $discountValue = min(($eligibleSubtotal * (float) $voucher->value / 100), $maxDiscount);
                } elseif ((int) $voucher->type === 2) {
                    $discountValue = min((float) $voucher->value, $eligibleSubtotal);
                } elseif ((int) $voucher->type === 3) {
                    $discountValue = min((float) $voucher->value, (float) $shippingCost);
                } else {
                    $discountValue = 0.0;
                }

                $discountSum += (float) $discountValue;
            }

            $voucherDiscount = max(0, min((float) $discountSum, $subtotal + $shippingCost));
        }

        $preview = [
            'customer' => $request->only(['name', 'email', 'phone', 'city', 'address', 'postal_code']),
            'courier' => $courier,
            'courier_label' => self::SHIPPING_LABELS[$courier] ?? $courier,
            'shipping_cost' => $shippingCost,
            'voucher_code' => implode(',', $voucherCodes),
            'voucher_codes' => $voucherCodes,
            'voucher_discount' => $voucherDiscount,
            'subtotal' => $subtotal,
            'total' => max(0, $subtotal + $shippingCost - $voucherDiscount),
            'items' => array_map(function ($item) use ($itemNotes) {
                $item['item_note'] = $item['item_note'] ?? ($itemNotes[$item['id']] ?? '');
                return $item;
            }, array_values($cart)),
        ];

        session()->put('order_preview', $preview);

        return redirect()->route('order.preview');
    }

    public function reorder(string $orderId)
    {
        if (!session()->get('is_logged_in')) {
            return redirect()->route('home')->with('show_login', true);
        }

        $order = \App\Models\Frontend\Order::with(['items.product.brand', 'items.variant', 'customer'])
            ->findOrFail($orderId);

        if (($order->deleted ?? false)) {
            abort(404);
        }

        $this->ensureOrderBelongsToCurrentUser($order);

        $items = $order->items;
        if ($items->isEmpty()) {
            return redirect()->back()->with('warning', 'Pesanan ini tidak memiliki item untuk di-order ulang.');
        }

        $userId = session()->get('user')['id'] ?? session()->get('user')['sub'] ?? null;
        $customerId = $this->resolveCustomerId();
        $sessionId = session()->getId();

        $buffer = Buffer::where(function ($q) use ($customerId, $sessionId) {
                if ($customerId) {
                    $q->where('customer_id', $customerId)->orWhere('session_id', $sessionId);
                } else {
                    $q->where('session_id', $sessionId);
                }
            })
            ->first();

        if (!$buffer) {
            $buffer = Buffer::create([
                'id' => Str::uuid()->toString(),
                'customer_id' => $customerId,
                'session_id' => $sessionId,
                'customer_name' => session()->get('user')['name'] ?? null,
                'customer_email' => session()->get('user')['email'] ?? null,
                'creator' => $userId,
                'editor' => $userId,
            ]);
        }

        $addedQuantity = 0;
        $skippedCount = 0;

        foreach ($items as $item) {
            $cartItem = $this->buildReorderCartItem($item);
            if (!$cartItem) {
                $skippedCount++;
                continue;
            }

            $cartItemId = $cartItem['id'];
            $existingItem = BufferItem::where('buffer_id', $buffer->id)->where('id', $cartItemId)->first();

            if ($existingItem) {
                $existingItem->update([
                    'quantity' => $existingItem->quantity + $cartItem['quantity'],
                ]);
            } else {
                BufferItem::create([
                    'id' => Str::uuid()->toString(),
                    'buffer_id' => $buffer->id,
                    'product_id' => $cartItem['product_id'],
                    'product_variant_id' => $cartItem['variant_id'] ?? null,
                    'name' => $cartItem['name'],
                    'quantity' => $cartItem['quantity'],
                    'unit_price' => (float) $cartItem['price'],
                    'total' => (float) $cartItem['price'] * $cartItem['quantity'],
                    'discount_nominal' => 0,
                    'discount_percent' => 0,
                    'item_notes' => $cartItem['item_note'] ?? '',
                ]);
            }

            $addedQuantity += $cartItem['quantity'];
        }

        $this->recalculateBuffer($buffer);

        $message = $skippedCount > 0
            ? "Berhasil menambahkan {$addedQuantity} item ke keranjang. {$skippedCount} item tidak tersedia untuk di-order ulang."
            : "Berhasil menambahkan {$addedQuantity} item ke keranjang.";

        return redirect()->route('checkout')->with('success', $message);
    }

    private function ensureOrderBelongsToCurrentUser(\App\Models\Frontend\Order $order): void
    {
        $user = session()->get('user', []);
        $email = (string) ($user['email'] ?? '');

        if ($email === '' || !$order->customer) {
            return;
        }

        if ($order->customer->email !== $email) {
            abort(403);
        }
    }

    private function buildReorderCartItem(\App\Models\Frontend\Order\OrderItem $item): ?array
    {
        $product = $item->product;
        if (!$product || ($product->deleted ?? false) || !$product->status) {
            return null;
        }

        $variant = $item->product_variant_id ? $item->variant : null;
        if ($variant && (!$variant->status || $variant->product_id !== $product->id)) {
            return null;
        }

        $price = $product->base_price;
        if ($variant) {
            $price = $variant->price;
        }

        $cartItemId = $variant ? $variant->id : $product->id;
        $name = $variant && $variant->variant_name
            ? $product->name . ' - ' . $variant->variant_name
            : $product->name;

        return [
            'id' => $cartItemId,
            'product_id' => $product->id,
            'variant_id' => $variant?->id,
            'name' => $name,
            'brand' => $product->brand?->name ?? '',
            'image' => $product->thumbnail_url ?? '',
            'price' => (float) $price,
            'quantity' => max(1, (int) $item->quantity),
            'reorder_from_order_id' => $item->order_id,
        ];
    }

    private function getBufferCartArray(Buffer $buffer): array
    {
        $items = $buffer->items()->get()->map(function ($item) {
            return [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'variant_id' => $item->product_variant_id,
                'name' => $item->name,
                'brand' => $item->product->brand->name ?? '',
                'image' => $item->product->thumbnail_url ?? '',
                'price' => (float) $item->unit_price,
                'quantity' => (int) $item->quantity,
                'item_note' => $item->item_notes ?? '',
            ];
        })->toArray();

        return $items;
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
            $total += $item['price'] * $item['quantity'];
        }
        return $total;
    }

    private function parseVoucherCodes(Request $request): array
    {
        $rawCodes = $request->input('voucher_codes') ?: $request->input('voucher_code');
        if (!$rawCodes) {
            return [];
        }

        return array_values(array_unique(array_filter(array_map('strtoupper', preg_split('/[,;]+/', (string) $rawCodes) ?: []))));
    }

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
}
