<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Frontend\Order;
use App\Models\Frontend\OrderItem;
use App\Models\Frontend\ProductsCatalog\Product;
use App\Models\Frontend\ProductsCatalog\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class CartController extends Controller
{
    public function toggleWishlist(Request $request)
    {
        $request->validate([
            'product_id' => 'required|string|uuid',
        ]);

        $productId = $request->input('product_id');
        $wishlist = session()->get('wishlist', []);
        $isInWishlist = in_array($productId, $wishlist);

        if ($isInWishlist) {
            $wishlist = array_values(array_filter($wishlist, fn($id) => $id !== $productId));
            session()->put('wishlist', $wishlist);
            $message = 'Produk dihapus dari favorit';
        } else {
            $wishlist[] = $productId;
            session()->put('wishlist', $wishlist);
            $message = 'Produk ditambahkan ke favorit';
        }

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

        return redirect()->back()->with('success', $message);
    }

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

        $cart = session()->get('cart', []);
        $cartItemId = $variantId ? $variantId : $productId;

        if (isset($cart[$cartItemId])) {
            $cart[$cartItemId]['quantity'] += $quantity;
            $cart[$cartItemId]['item_note'] = $cart[$cartItemId]['item_note'] ?? '';
        } else {
            $cart[$cartItemId] = [
                'id' => $cartItemId,
                'product_id' => $productId,
                'name' => $product->name,
                'brand' => $product->brand->name ?? '',
                'image' => $product->thumbnail_url ?? '',
                'price' => (float) $price,
                'quantity' => $quantity,
                'item_note' => '',
            ];
        }

        session()->put('cart', $cart);

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

    public function update(Request $request, string $id)
    {
        $request->validate([
            'quantity' => 'required|integer',
        ]);

        $quantity = (int) $request->input('quantity');
        $cart = session()->get('cart', []);

        if (isset($cart[$id])) {
            if ($quantity < 1) {
                unset($cart[$id]);
            } else {
                $cart[$id]['quantity'] = $quantity;
                $cart[$id]['item_note'] = $cart[$id]['item_note'] ?? '';
            }
            session()->put('cart', $cart);
        }

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
        $cart = session()->get('cart', []);

        if (isset($cart[$id])) {
            unset($cart[$id]);
            session()->put('cart', $cart);
        }

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
        $cart = session()->get('cart', []);
        if (count($cart) === 0) {
            return redirect()->route('checkout')->with('warning', 'Keranjang belanja kosong.');
        }

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

        // Hitung voucher diskon dari backend supaya voucher persentase
        // benar-benar mengurangi total (dan konsisten dengan checkout).
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

                // eligible subtotal mengikuti scope voucher
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

                // nilai diskon sesuai type
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

        if (!Schema::hasTable('orders') || !Schema::hasTable('order_items')) {
            abort(404);
        }

        $order = Order::with(['items.product.brand', 'items.variant', 'customer'])
            ->findOrFail($orderId);

        if (($order->deleted ?? false)) {
            abort(404);
        }

        $this->ensureOrderBelongsToCurrentUser($order);

        $items = $order->items;
        if ($items->isEmpty()) {
            return redirect()->back()->with('warning', 'Pesanan ini tidak memiliki item untuk di-order ulang.');
        }

        $cart = session()->get('cart', []);
        $addedQuantity = 0;
        $skippedCount = 0;

        foreach ($items as $item) {
            $cartItem = $this->buildReorderCartItem($item);
            if (!$cartItem) {
                $skippedCount++;
                continue;
            }

            $cartItemId = $cartItem['id'];
            if (isset($cart[$cartItemId])) {
                $cart[$cartItemId]['quantity'] += $cartItem['quantity'];
            } else {
                $cart[$cartItemId] = $cartItem;
            }

            $addedQuantity += $cartItem['quantity'];
        }

        session()->put('cart', $cart);

        $message = $skippedCount > 0
            ? "Berhasil menambahkan {$addedQuantity} item ke keranjang. {$skippedCount} item tidak tersedia untuk di-order ulang."
            : "Berhasil menambahkan {$addedQuantity} item ke keranjang.";

        return redirect()->route('checkout')->with('success', $message);
    }

    private function ensureOrderBelongsToCurrentUser(Order $order): void
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

    private function parseVoucherCodes(Request $request): array
    {
        $rawCodes = $request->input('voucher_codes') ?: $request->input('voucher_code');
        if (!$rawCodes) {
            return [];
        }

        return array_values(array_unique(array_filter(array_map('strtoupper', preg_split('/[,;]+/', (string) $rawCodes) ?: []))));
    }

    private function buildReorderCartItem(OrderItem $item): ?array
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
            $total += $res['promotional_price'] * (int) $item['quantity'];
        }
        return $total;
    }
}