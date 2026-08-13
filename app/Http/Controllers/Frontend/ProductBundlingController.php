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
            $query->where('price', '>=', $request->query('min_price'));
        }

        if ($request->query('max_price')) {
            $query->where('price', '<=', $request->query('max_price'));
        }

        $sort = $request->query('sort', 'newest');
        if ($sort === 'price_asc') {
            $query->orderBy('price', 'asc');
        } elseif ($sort === 'price_desc') {
            $query->orderBy('price', 'desc');
        } else {
            $query->orderBy('created_at', 'desc');
        }

        $bundlings = $query->with(['items.product', 'items.product.brand', 'items.variant'])
            ->paginate(12)
            ->withQueryString();

        foreach ($bundlings as $bundle) {
            // Use header price as the original normal price
            $bundle->total_original = (float) $bundle->price;

            // Harga dasar Bundling adalah Harga Fix yang diinput Admin
            $bundlePrice = (float) $bundle->price;
            
            // Cek apakah Bundling ini di-override oleh Price Product Setting (PPS)
            $ppsPromo = \App\Services\StaticPromoService::forBundling($bundle, $bundlePrice);
            if ($ppsPromo) {
                // Potong diskon PPS dari Harga Fix Bundling
                $bundlePrice = \App\Services\StaticPromoService::discountedPrice($bundlePrice, $ppsPromo);
                $bundle->pps_label = $ppsPromo['label'];
            }

            $bundle->total_price = $bundlePrice;
            $bundle->discount_percent = $bundle->total_original > 0
                ? round((($bundle->total_original - $bundle->total_price) / $bundle->total_original) * 100, 0)
                : 0;
            $bundle->thumbnail_url = $bundle->items->first()?->product?->thumbnail_url;
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
            'items.product.variants',
            'items.product.brand',
            'items.product.images',
            'items.variant',
        ]);

        // Use header price as the original normal price
        $bundle->total_original = (float) $bundle->price;

        // Harga dasar Bundling adalah Harga Fix yang diinput Admin
        $bundlePrice = (float) $bundle->price;
        
        // Cek apakah Bundling ini di-override oleh Price Product Setting (PPS)
        $ppsPromo = \App\Services\StaticPromoService::forBundling($bundle, $bundlePrice);
        if ($ppsPromo) {
            // Potong diskon PPS dari Harga Fix Bundling
            $bundlePrice = \App\Services\StaticPromoService::discountedPrice($bundlePrice, $ppsPromo);
            $bundle->pps_label = $ppsPromo['label'];
        }

        $bundle->total_price = $bundlePrice;
        $bundle->discount_percent = $bundle->total_original > 0
            ? round((($bundle->total_original - $bundle->total_price) / $bundle->total_original) * 100, 0)
            : 0;

        $relatedProducts = Product::where('deleted', false)
            ->where('status', true)
            ->where('id', '!=', $bundle->id)
            ->with(['brand', 'category', 'images', 'variants'])
            ->take(8)
            ->get();

        $wishlist = session()->get('wishlist', []);

        return view('frontend.bundling.show', compact('bundle', 'relatedProducts', 'wishlist'));
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
