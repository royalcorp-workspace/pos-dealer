<?php

declare(strict_types=1);

namespace App\Http\Controllers\Frontend;

use App\Concerns\BufferCartTrait;
use App\Http\Controllers\Controller;
use App\Models\Frontend\Buffer\BufferItem;
use App\Models\Frontend\Customer\Customer;
use App\Models\Frontend\ProductsCatalog\Product;
use App\Models\Frontend\ProductsCatalog\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CartController extends Controller
{
    use BufferCartTrait;
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

        $product = Product::with('variants')->where('id', $productId)->first();
        if (!$product) {
            return response()->json(['error' => 'Product not found'], 404);
        }

        if ($product->variants->isNotEmpty() && empty($variantId)) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Silakan pilih ukuran terlebih dahulu.'], 422);
            }
            return redirect()->back()->with('error', 'Silakan pilih ukuran terlebih dahulu.');
        }

        if ($product->colors()->exists() && empty($request->input('color_id'))) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Silakan pilih warna terlebih dahulu.'], 422);
            }
            return redirect()->back()->with('error', 'Silakan pilih warna terlebih dahulu.');
        }

        $basePrice = 0.0;
        $sellPrice = 0.0;
        if ($variantId) {
            $variant = ProductVariant::where('id', $variantId)->first();
            if ($variant) {
                $basePrice = (float) ($variant->base_price > 0 ? $variant->base_price : $variant->sell_price);
                $sellPrice = (float) $variant->sell_price;
            }
        }
        if ($basePrice <= 0.0) {
            $minBase = (float) ($product->variants->where('status', true)->min('base_price') ?? 0);
            $minSell = (float) ($product->variants->where('status', true)->min('sell_price') ?? 0);
            $basePrice = $minBase > 0 ? $minBase : $minSell;
            $sellPrice = $minSell;
        }
        if ($sellPrice <= 0.0) {
            $sellPrice = $basePrice;
        }
        if ($basePrice < $sellPrice) {
            $basePrice = $sellPrice;
        }

        $staticPromo = \App\Services\StaticPromoService::forProduct($product);
        $promotionalPrice = \App\Services\StaticPromoService::discountedPrice((float) $sellPrice, $staticPromo);

        $unitDiscount = max(0.0, $basePrice - $promotionalPrice);
        $discountNominal = $unitDiscount * $quantity;
        $discountPercent = $basePrice > 0 ? round(($unitDiscount / $basePrice) * 100, 2) : 0.0;

        $buffer = $this->findOrCreateBuffer();

        $colorId = $request->input('color_id');
        $color = $colorId ? \App\Models\Frontend\ProductsCatalog\ProductColor::find($colorId) : null;
        $colorName = $color?->color_name;
        $colorCode = $color?->color_code;

        $meta = [];
        if ($colorId && $color) {
            $meta['color_id'] = $colorId;
            $meta['color_name'] = $colorName;
            $meta['color_code'] = $colorCode;
        }
        $meta['base_price'] = $basePrice;
        $meta['original_price'] = $basePrice;
        $meta['sell_price'] = $promotionalPrice;
        $meta['after_disc_price'] = $promotionalPrice;
        $meta['discount_nominal'] = $discountNominal;
        $meta['discount_percent'] = $discountPercent;

        $existingItemQuery = BufferItem::where('buffer_id', $buffer->id)
            ->where('product_id', $productId);

        if ($variantId) {
            $existingItemQuery->where('product_variant_id', $variantId);
        } else {
            $existingItemQuery->whereNull('product_variant_id');
        }

        if ($colorId) {
            $existingItemQuery->where('meta->color_id', $colorId);
        } else {
            $existingItemQuery->where(function ($q) {
                $q->whereNull('meta')->orWhereNull('meta->color_id');
            });
        }

        $existingItem = $existingItemQuery->first();

        if ($existingItem) {
            $newQty = $existingItem->quantity + $quantity;
            $existingMeta = is_array($existingItem->meta) ? $existingItem->meta : (json_decode((string) $existingItem->meta, true) ?: []);
            $uBasePrice = (float) ($existingMeta['base_price'] ?? $existingItem->unit_price);
            $uSellPrice = (float) ($existingMeta['sell_price'] ?? $existingMeta['after_disc_price'] ?? $promotionalPrice);
            $uDiscountNominal = max(0.0, $uBasePrice - $uSellPrice) * $newQty;
            $existingMeta['discount_nominal'] = $uDiscountNominal;

            $existingItem->update([
                'quantity' => $newQty,
                'unit_price' => $uBasePrice,
                'total' => $uSellPrice * $newQty,
                'discount_nominal' => $uDiscountNominal,
                'meta' => $existingMeta,
            ]);
            $item = $existingItem;
        } else {
            $itemName = $product->name;
            if ($variant && !empty($variant->variant_name)) {
                $itemName .= ' - ' . $variant->variant_name;
            }
            if ($colorName) {
                $itemName .= ' (' . $colorName . ')';
            }

            $item = BufferItem::create([
                'id' => Str::uuid()->toString(),
                'buffer_id' => $buffer->id,
                'product_id' => $productId,
                'product_variant_id' => $variantId,
                'name' => $itemName,
                'quantity' => $quantity,
                'unit_price' => (float) $basePrice,
                'total' => (float) $promotionalPrice * $quantity,
                'discount_nominal' => $discountNominal,
                'discount_percent' => $discountPercent,
                'item_notes' => '',
                'meta' => $meta,
            ]);
        }

        // Handle selected suggest bundle items (Add-on Bundling)
        if ($request->has('selected_suggests') && is_array($request->input('selected_suggests'))) {
            $suggestIds = $request->input('selected_suggests');
            $suggestItems = \App\Models\Frontend\ProductsCatalog\ProductBundlingItem::whereIn('id', $suggestIds)
                ->with(['product.variants', 'variant'])
                ->get();

            foreach ($suggestItems as $sItem) {
                $sProduct = $sItem->product;
                if (!$sProduct) {
                    continue;
                }
                $sVariant = $sItem->variant ?: $sProduct->variants->first();
                $sNormalPrice = (float) ($sVariant?->sell_price ?: $sProduct->price ?: 0);
                $sPrice = (float) ($sItem->bundle_price > 0 ? $sItem->bundle_price : $sNormalPrice);
                if ($sItem->discount_percent && !$sItem->bundle_price) {
                    $sPrice = round($sNormalPrice * (1 - ($sItem->discount_percent / 100)));
                }

                $sVariantId = $sVariant?->id;
                $sItemQty = (int) ($sItem->quantity ?: 1) * $quantity;
                $sDiscNominal = max(0.0, $sNormalPrice - $sPrice) * $sItemQty;

                BufferItem::create([
                    'id' => Str::uuid()->toString(),
                    'buffer_id' => $buffer->id,
                    'product_id' => $sItem->product_id,
                    'product_variant_id' => $sVariantId,
                    'name' => $sProduct->name . ($sVariant && $sVariant->variant_name && $sVariant->variant_name !== 'Default' ? ' (' . $sVariant->variant_name . ')' : '') . ' [Bundling Hemat]',
                    'quantity' => $sItemQty,
                    'unit_price' => $sPrice,
                    'total' => $sPrice * $sItemQty,
                    'discount_nominal' => $sDiscNominal,
                    'discount_percent' => (float) $sItem->discount_percent,
                    'item_notes' => 'Add-on Bundling Hemat',
                    'meta' => [
                        'is_suggest_addon' => true,
                        'bundling_item_id' => $sItem->id,
                        'original_price' => $sNormalPrice,
                        'sell_price' => $sPrice,
                    ],
                ]);
            }
        }

        $this->recalculateBuffer($buffer);

        $cart = $this->getBufferCartArray($buffer);

        if (!empty($product->slug)) {
            $this->rememberLastProductUrl(route('products.show', $product->slug));
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

        return redirect()->back()->with('success', 'Produk berhasil ditambahkan ke keranjang!');
    }

    public function update(Request $request, string $id)
    {
        $request->validate([
            'quantity' => 'required|integer|min:0',
        ]);

        $quantity = (int) $request->input('quantity');
        $buffer = $this->findOrCreateBuffer();

        $item = BufferItem::where('buffer_id', $buffer->id)->where('id', $id)->first();
        if (!$item) {
            return response()->json(['error' => 'Item not found'], 404);
        }

        if ($quantity < 1) {
            if ($item->product_id) {
                $prod = \App\Models\Frontend\ProductsCatalog\Product::find($item->product_id);
                if ($prod && !empty($prod->slug)) {
                    $this->rememberLastProductUrl(route('products.show', $prod->slug));
                }
            } elseif ($item->item_notes) {
                $bundleNotes = json_decode((string) $item->item_notes, true);
                if (!empty($bundleNotes['bundle_id'])) {
                    $bundle = \App\Models\Frontend\ProductsCatalog\ProductBundling::find($bundleNotes['bundle_id']);
                    if ($bundle && !empty($bundle->slug)) {
                        $this->rememberLastProductUrl(route('bundling.show', $bundle->slug));
                    }
                }
            }
            $item->delete();
        } else {
            $itemMeta = is_array($item->meta) ? $item->meta : (json_decode((string) $item->meta, true) ?: []);
            $uBasePrice = (float) ($itemMeta['base_price'] ?? $item->unit_price);
            $uSellPrice = (float) ($itemMeta['sell_price'] ?? $itemMeta['after_disc_price'] ?? ($item->total > 0 && $item->quantity > 0 ? ($item->total / $item->quantity) : $item->unit_price));
            $uDiscNominal = max(0.0, $uBasePrice - $uSellPrice) * $quantity;
            $itemMeta['discount_nominal'] = $uDiscNominal;

            $item->update([
                'quantity' => $quantity,
                'unit_price' => $uBasePrice,
                'total' => $uSellPrice * $quantity,
                'discount_nominal' => $uDiscNominal,
                'meta' => $itemMeta,
            ]);
        }

        $this->recalculateBuffer($buffer);
        $cart = $this->getBufferCartArray($buffer);
        $cartCount = $this->getCartCount($cart);
        $lastProductUrl = $this->getLastProductUrl();

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'cart' => $cart,
                'cart_count' => $cartCount,
                'cart_total' => $this->getCartTotal($cart),
                'redirect_url' => $cartCount === 0 ? $lastProductUrl : null,
                'cart_drawer_html' => view('frontend.components.cart-drawer-body', ['cart' => $cart])->render(),
            ]);
        }

        $referer = (string) $request->header('referer', '');
        if ($cartCount === 0 && (str_contains($referer, '/checkout') || str_contains($referer, '/payment'))) {
            return redirect($lastProductUrl)->with('warning', 'Keranjang belanja Anda telah kosong.');
        }

        return redirect()->back();
    }

    public function remove(Request $request, string $id)
    {
        $buffer = $this->findOrCreateBuffer();

        $item = BufferItem::where('buffer_id', $buffer->id)->where('id', $id)->first();
        if ($item) {
            if ($item->product_id) {
                $prod = \App\Models\Frontend\ProductsCatalog\Product::find($item->product_id);
                if ($prod && !empty($prod->slug)) {
                    $this->rememberLastProductUrl(route('products.show', $prod->slug));
                }
            } elseif ($item->item_notes) {
                $bundleNotes = json_decode((string) $item->item_notes, true);
                if (!empty($bundleNotes['bundle_id'])) {
                    $bundle = \App\Models\Frontend\ProductsCatalog\ProductBundling::find($bundleNotes['bundle_id']);
                    if ($bundle && !empty($bundle->slug)) {
                        $this->rememberLastProductUrl(route('bundling.show', $bundle->slug));
                    }
                }
            }
            $item->delete();
            $this->recalculateBuffer($buffer);
        }

        $cart = $this->getBufferCartArray($buffer);
        $cartCount = $this->getCartCount($cart);
        $lastProductUrl = $this->getLastProductUrl();

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'cart' => $cart,
                'cart_count' => $cartCount,
                'cart_total' => $this->getCartTotal($cart),
                'redirect_url' => $cartCount === 0 ? $lastProductUrl : null,
                'cart_drawer_html' => view('frontend.components.cart-drawer-body', ['cart' => $cart])->render(),
            ]);
        }

        $referer = (string) $request->header('referer', '');
        if ($cartCount === 0 && (str_contains($referer, '/checkout') || str_contains($referer, '/payment'))) {
            return redirect($lastProductUrl)->with('warning', 'Keranjang belanja Anda telah kosong.');
        }

        return redirect()->back();
    }

    public function preview(Request $request)
    {
        $buffer = $this->getCurrentBuffer();

        if ($buffer) {
            $buffer->load(['items.product.brand', 'items.variant']);
        }

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
                    $originalPrice = (float) $variantModel->sell_price;
                }
            }
            if ($originalPrice <= 0.0) {
                $productModel = \App\Models\Frontend\ProductsCatalog\Product::find($item['product_id']);
                if ($productModel) {
                    $originalPrice = (float) ($productModel->variants->where('status', true)->min('sell_price') ?? 0);
                }
            }
            if ($originalPrice <= 0.0) {
                $originalPrice = (float) $item['sell_price'];
            }
            $res = \App\Services\StaticPromoService::calculateItemDiscounts($item, (int) $item['quantity'], $originalPrice);
            $item['sell_price'] = $res['promotional_price'];
            $recalculatedCart[$key] = $item;
            $subtotal += $res['promotional_price'] * (int) $item['quantity'];
        }
        $cart = $recalculatedCart;
        
        $courierModel = \App\Models\Frontend\Shipping\Courier::whereRaw('LOWER(code) = ?', [strtolower($courier)])->first();
        $shippingCost = 25000;
        if ($courierModel) {
            $shipping = \App\Models\Frontend\Shipping\ShippingAddress::where('courier_id', $courierModel->id)->where('type', 1)->first() 
                        ?? \App\Models\Frontend\Shipping\ShippingAddress::where('courier_id', $courierModel->id)->first();
            if ($shipping) {
                $shippingCost = (int) $shipping->price;
            }
        }

        $voucherCodes = $this->parseVoucherCodes($request);

        $voucherDiscount = 0;
        if (!empty($voucherCodes)) {
            $userId = session()->get('is_logged_in')
                ? (session()->get('user')['id'] ?? session()->get('user')['sub'] ?? null)
                : null;

            $vouchers = \App\Models\Frontend\Promo\Voucher::active()
                ->with(['categories'])
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

            if ($orderedVouchers->count() > 1 && !\App\Models\Frontend\Promo\Voucher::validateVoucherCombination($orderedVouchers)) {
                $orderedVouchers = $orderedVouchers->take(1);
            }

            $discountSum = 0;
            foreach ($orderedVouchers as $voucher) {
                if (!$voucher->canBeUsedBy($userId)) continue;

                $eligibleSubtotal = 0.0;
                if ((int) $voucher->scope === 2) {
                    $eligibleSubtotal = (float) collect($cart)
                        ->sum(fn($item) => ($item['sell_price'] ?? 0) * ($item['quantity'] ?? 0));
                } elseif ((int) $voucher->scope === 3) {
                    $eligibleProductIds = $voucher->categories()
                        ->where('product_category.deleted', false)
                        ->with('products')
                        ->get()
                        ->flatMap(fn($category) => $category->products->where('deleted', false)->pluck('id'))
                        ->unique()
                        ->toArray();

                    $eligibleSubtotal = (float) collect($cart)
                        ->filter(fn($item) => in_array($item['product_id'] ?? null, $eligibleProductIds, true))
                        ->sum(fn($item) => ($item['sell_price'] ?? 0) * ($item['quantity'] ?? 0));
                } else {
                    $eligibleSubtotal = (float) collect($cart)
                        ->sum(fn($item) => ($item['sell_price'] ?? 0) * ($item['quantity'] ?? 0));
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
            'courier_label' => $courierModel ? $courierModel->name : strtoupper($courier),
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

        $buffer = $this->findOrCreateBuffer();

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
                    'unit_price' => (float) $cartItem['sell_price'],
                    'total' => (float) $cartItem['sell_price'] * $cartItem['quantity'],
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

        $price = ($product->variants->where('status', true)->min('sell_price') ?? 0);
        if ($variant) {
            $price = $variant->sell_price;
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
            'sell_price' => (float) $price,
            'quantity' => max(1, (int) $item->quantity),
            'reorder_from_order_id' => $item->order_id,
        ];
    }

    private function parseVoucherCodes(Request $request): array
    {
        $rawCodes = $request->input('voucher_codes') ?: $request->input('voucher_code');
        if (!$rawCodes) {
            return [];
        }

        return array_values(array_unique(array_filter(array_map('strtoupper', preg_split('/[,;]+/', (string) $rawCodes) ?: []))));
    }
}
