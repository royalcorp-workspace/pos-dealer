<?php

declare(strict_types=1);

namespace App\Http\Controllers\Frontend;

use App\Concerns\BufferCartTrait;
use App\Http\Controllers\Controller;
use App\Models\Frontend\Buffer\BufferItem;
use App\Models\Frontend\ProductsCatalog\Product;
use App\Models\Frontend\ProductsCatalog\ProductBundling;
use App\Services\StaticPromoService;
use Illuminate\Http\Request;

class ProductBundlingController extends Controller
{
    use BufferCartTrait;

    public function index(Request $request)
    {
        $query = ProductBundling::where('is_active', true)
            ->where('deleted', false);

        if ($request->query('search')) {
            $search = $request->query('search');
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->query('min_price')) {
            $query->where('sell_price', '>=', $request->query('min_price'));
        }

        if ($request->query('max_price')) {
            $query->where('sell_price', '<=', $request->query('max_price'));
        }

        $sort = $request->query('sort', 'newest');
        if ($sort === 'price_asc') {
            $query->orderBy('sell_price', 'asc');
        } elseif ($sort === 'price_desc') {
            $query->orderBy('sell_price', 'desc');
        } else {
            $query->orderBy('created_at', 'desc');
        }

        $bundlings = $query->with(['items.product.variants' => fn($q) => $q->where('deleted', false)->where('sell_price', '>', 0), 'items.product.brand', 'items.variant'])
            ->paginate(12)
            ->withQueryString();

        foreach ($bundlings as $bundle) {
            $mainItem = $bundle->items->where('is_suggest', false)->first() ?: $bundle->items->first();
            $mainProduct = $mainItem?->product;
            $bundle->main_product = $mainProduct;
            $bundle->main_product_slug = $mainProduct?->slug;

            $suggestItems = $bundle->items->where('is_suggest', true)->values();
            if ($suggestItems->isEmpty() && $bundle->items->count() > 1) {
                $suggestItems = $bundle->items->slice(1)->values();
            }
            $bundle->suggest_items = $suggestItems;

            // Hitung total penghematan dari produk-produk pelengkap suggest
            $totalSavings = 0;
            $suggestTotalNormal = 0;
            $suggestTotalBundle = 0;
            foreach ($suggestItems as $sItem) {
                $sNorm = (float)($sItem->variant?->sell_price ?: ($sItem->product?->variants->where('deleted', false)->where('sell_price', '>', 0)->min('sell_price') ?: 0));
                $sPrice = (float)($sItem->bundle_price ?: $sNorm);
                if ($sItem->discount_percent && !$sItem->bundle_price && $sNorm > 0) {
                    $sPrice = round($sNorm * (1 - ($sItem->discount_percent / 100)));
                }
                $suggestTotalNormal += $sNorm;
                $suggestTotalBundle += $sPrice;
                if ($sNorm > $sPrice) {
                    $totalSavings += ($sNorm - $sPrice);
                }
            }
            $bundle->total_savings = $totalSavings;
            $bundle->suggest_total_normal = $suggestTotalNormal;
            $bundle->suggest_total_bundle = $suggestTotalBundle;

            // Hitung rentang harga produk utama
            if ($mainProduct) {
                $validVars = $mainProduct->variants->where('deleted', false)->where('sell_price', '>', 0);
                $minP = (float)($validVars->min('sell_price') ?: $mainProduct->price ?: 0);
                $maxP = (float)($validVars->max('sell_price') ?: $minP);
                $bundle->main_min_price = $minP;
                $bundle->main_max_price = $maxP;
                $bundle->main_price_range_text = ($minP > 0 && $maxP > $minP)
                    ? 'Rp ' . number_format($minP, 0, ',', '.') . ' - Rp ' . number_format($maxP, 0, ',', '.')
                    : 'Rp ' . number_format($minP, 0, ',', '.');
            } else {
                $bundle->main_min_price = (float)$bundle->price;
                $bundle->main_max_price = (float)$bundle->price;
                $bundle->main_price_range_text = 'Rp ' . number_format((float)$bundle->price, 0, ',', '.');
            }

            // Total paket mulai dari
            $bundle->start_price = $bundle->main_min_price + $suggestTotalBundle;
            $bundle->start_original_price = $bundle->main_min_price + $suggestTotalNormal;

            $bundle->thumbnail_url = $bundle->image_url ? cms_asset($bundle->image_url) : ($bundle->banner_image ? cms_asset($bundle->banner_image) : $mainProduct?->thumbnail_url);
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'bundlings' => $bundlings->items(),
                'next_page_url' => $bundlings->nextPageUrl(),
                'has_more' => $bundlings->hasMorePages(),
            ]);
        }

        return view('frontend.bundling.index', compact('bundlings', 'sort'));
    }

    public function show(ProductBundling $bundle)
    {
        if (!$bundle->is_active || $bundle->deleted) {
            abort(404);
        }

        $bundle->load([
            'items.product.variants' => fn($q) => $q->where('deleted', false)->where('sell_price', '>', 0),
            'items.product.brand',
            'items.product.images',
            'items.variant',
        ]);

        $mainItem = $bundle->items->where('is_suggest', false)->first() ?: $bundle->items->first();
        $mainProduct = $mainItem?->product;
        $bundle->main_product = $mainProduct;
        $bundle->main_product_slug = $mainProduct?->slug;

        $suggestItems = $bundle->items->where('is_suggest', true)->values();
        if ($suggestItems->isEmpty() && $bundle->items->count() > 1) {
            $suggestItems = $bundle->items->slice(1)->values();
        }
        $bundle->suggest_items = $suggestItems;

        $totalSavings = 0;
        $suggestTotalNormal = 0;
        $suggestTotalBundle = 0;
        foreach ($suggestItems as $sItem) {
            $sNorm = (float)($sItem->variant?->sell_price ?: ($sItem->product?->variants->where('deleted', false)->where('sell_price', '>', 0)->min('sell_price') ?: 0));
            $sPrice = (float)($sItem->bundle_price ?: $sNorm);
            if ($sItem->discount_percent && !$sItem->bundle_price && $sNorm > 0) {
                $sPrice = round($sNorm * (1 - ($sItem->discount_percent / 100)));
            }
            $suggestTotalNormal += $sNorm;
            $suggestTotalBundle += $sPrice;
            if ($sNorm > $sPrice) {
                $totalSavings += ($sNorm - $sPrice);
            }
        }
        $bundle->total_savings = $totalSavings;
        $bundle->suggest_total_normal = $suggestTotalNormal;
        $bundle->suggest_total_bundle = $suggestTotalBundle;

        if ($mainProduct) {
            $validVars = $mainProduct->variants->where('deleted', false)->where('sell_price', '>', 0);
            $minP = (float)($validVars->min('sell_price') ?: $mainProduct->price ?: 0);
            $maxP = (float)($validVars->max('sell_price') ?: $minP);
            $bundle->main_min_price = $minP;
            $bundle->main_max_price = $maxP;
            $bundle->main_price_range_text = ($minP > 0 && $maxP > $minP)
                ? 'Rp ' . number_format($minP, 0, ',', '.') . ' - Rp ' . number_format($maxP, 0, ',', '.')
                : 'Rp ' . number_format($minP, 0, ',', '.');
        } else {
            $bundle->main_min_price = (float)$bundle->price;
            $bundle->main_max_price = (float)$bundle->price;
            $bundle->main_price_range_text = 'Rp ' . number_format((float)$bundle->price, 0, ',', '.');
        }

        $bundle->start_price = $bundle->main_min_price + $suggestTotalBundle;
        $bundle->start_original_price = $bundle->main_min_price + $suggestTotalNormal;

        if (!empty($bundle->slug)) {
            session()->put('last_checkout_product_url', route('bundling.show', $bundle->slug));
        }

        $relatedProducts = Product::where('deleted', false)
            ->where('status', true)
            ->where('is_bundle', false)
            ->where('id', '!=', $bundle->id)
            ->with(['brand', 'category', 'images', 'variants' => fn($q) => $q->where('deleted', false)->where('sell_price', '>', 0)])
            ->take(8)
            ->get();

        $wishlist = session()->get('wishlist', []);

        return view('frontend.bundling.show', compact('bundle', 'relatedProducts', 'wishlist', 'mainProduct', 'suggestItems'));
    }

    public function addToCart(Request $request)
    {
        $request->validate([
            'bundling_id' => 'required|string|uuid',
            'quantity' => 'required|integer|min:1',
            'variants' => 'nullable|array',
        ]);

        $bundling = ProductBundling::where('id', $request->input('bundling_id'))
            ->where('is_active', true)
            ->where('deleted', false)
            ->with(['items.product.variants', 'items.variant'])
            ->first();

        if (!$bundling) {
            return response()->json(['error' => 'Bundle not found'], 404);
        }

        $quantity = (int) $request->input('quantity');

        $buffer = $this->findOrCreateBuffer();

        $bundlePrice = (float) $bundling->price;
        $ppsPromo = \App\Services\StaticPromoService::forBundling($bundling, $bundlePrice);
        if ($ppsPromo) {
            $bundlePrice = \App\Services\StaticPromoService::discountedPrice($bundlePrice, $ppsPromo);
        }

        $existingItem = BufferItem::where('buffer_id', $buffer->id)
            ->where('name', 'BUNDLE_' . $bundling->id)
            ->first();

        if ($existingItem) {
            $existingItem->update([
                'quantity' => $existingItem->quantity + $quantity,
            ]);
        } else {
            $selectedVariants = $request->input('variants', []);
            $firstItem = $bundling->items->first();
            $firstProductId = $firstItem ? $firstItem->product_id : null;
            $firstVariantId = $firstProductId && isset($selectedVariants[$firstProductId]) 
                ? $selectedVariants[$firstProductId] 
                : ($firstItem && $firstItem->variant_id ? $firstItem->variant_id : null);

            BufferItem::create([
                'id' => \Illuminate\Support\Str::uuid()->toString(),
                'buffer_id' => $buffer->id,
                'product_id' => $firstProductId,
                'product_variant_id' => $firstVariantId,
                'name' => 'BUNDLE_' . $bundling->id,
                'quantity' => $quantity,
                'unit_price' => $bundlePrice,
                'total' => $bundlePrice * $quantity,
                'discount_nominal' => 0,
                'discount_percent' => 0,
                'item_notes' => json_encode([
                    'bundle_id' => $bundling->id,
                    'bundle_name' => $bundling->name,
                    'bundle_slug' => $bundling->slug,
                    'bundle_price' => (float) $bundling->price,
                    'items' => $bundling->items->map(function($i) use ($selectedVariants) {
                        return [
                            'product_id' => $i->product_id,
                            'product_name' => $i->product?->name ?? '',
                            'quantity' => $i->quantity,
                            'variant_id' => $selectedVariants[$i->product_id] ?? $i->variant_id,
                        ];
                    })->toArray(),
                ]),
            ]);
        }

        if ($request->has('selected_suggests') && is_array($request->input('selected_suggests'))) {
            $suggestItemIds = $request->input('selected_suggests');
            $suggestItems = $bundling->items->whereIn('id', $suggestItemIds);
            foreach ($suggestItems as $sItem) {
                $sPrice = (float)($sItem->bundle_price ?: ($sItem->variant?->sell_price ?: ($sItem->product?->variants->min('sell_price') ?: 0)));
                if ($sItem->discount_percent && !$sItem->bundle_price) {
                    $normal = (float)($sItem->variant?->sell_price ?: 0);
                    $sPrice = round($normal * (1 - ($sItem->discount_percent / 100)));
                }

                $sVariantId = $sItem->variant_id ?: ($sItem->product?->variants->first()?->id);
                BufferItem::create([
                    'id' => \Illuminate\Support\Str::uuid()->toString(),
                    'buffer_id' => $buffer->id,
                    'product_id' => $sItem->product_id,
                    'product_variant_id' => $sVariantId,
                    'name' => ($sItem->product?->name ?? 'Produk Pelengkap') . ($sItem->variant ? ' (' . $sItem->variant->variant_name . ')' : '') . ' [Bundling Hemat]',
                    'quantity' => (int)($sItem->quantity ?: 1) * $quantity,
                    'unit_price' => $sPrice,
                    'total' => $sPrice * ((int)($sItem->quantity ?: 1) * $quantity),
                    'discount_nominal' => 0,
                    'discount_percent' => 0,
                    'item_notes' => json_encode([
                        'from_bundle_id' => $bundling->id,
                        'bundle_name' => $bundling->name,
                        'is_suggest_addon' => true,
                    ]),
                ]);
            }
        }

        $this->recalculateBuffer($buffer);

        $cart = $this->getBufferCartArray($buffer);

        return response()->json([
            'success' => true,
            'message' => 'Bundle berhasil masuk ke keranjang',
            'cart' => $cart,
            'cart_count' => $this->getCartCount($cart),
            'cart_total' => $this->getCartTotal($cart),
            'cart_drawer_html' => view('frontend.components.cart-drawer-body', ['cart' => $cart])->render(),
        ]);
    }
}
