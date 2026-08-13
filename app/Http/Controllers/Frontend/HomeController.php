<?php

declare(strict_types=1);

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Frontend\Banner;
use App\Models\Frontend\Event;
use App\Models\Frontend\HomepageSection;
use App\Models\Frontend\Notification;
use App\Models\Frontend\ProductsCatalog\Brand;
use App\Models\Frontend\ProductsCatalog\Product;
use App\Models\Frontend\ProductsCatalog\ProductBundling;
use App\Models\Frontend\ProductsCatalog\ProductCategory;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index()
    {
        $bestsellers = Product::where('deleted', false)
            ->where('best_seller', true)
            ->with(['brand', 'category', 'images', 'variants', 'tags'])
            ->take(8)
            ->get();

        $recommended = Product::where('deleted', false)
            ->with(['brand', 'category', 'images', 'variants', 'tags'])
            ->take(8)
            ->get();

        $recommendedTotal = Product::where('deleted', false)->count();

        $specialSection = HomepageSection::where('section_key', 'like', '%spesial%')
            ->orWhere('section_key', 'like', '%special%')
            ->first();
            
        $featuredProductId = null;
        if ($specialSection && $specialSection->meta) {
            $meta = is_string($specialSection->meta) ? json_decode($specialSection->meta, true) : (array)$specialSection->meta;
            $featuredProductId = $meta['featured_product_id'] ?? null;
        }

        $featuredQuery = Product::where('deleted', false)
            ->with(['brand', 'category', 'images', 'variants', 'tags']);
            
        if ($featuredProductId) {
            $featured = $featuredQuery->where('id', $featuredProductId)->first();
        } else {
            $featured = $featuredQuery->where('is_new', true)->first();
        }

        $categories = ProductCategory::where('deleted', false)
            ->whereNull('parent_id')
            ->withCount('products')
            ->take(6)
            ->get();

        $brands = Brand::where('deleted', false)
            ->where('status', true)
            ->with(['products' => function ($q) {
                $q->where('deleted', false)
                  ->where('status', true)
                  ->with(['variants', 'images', 'brand', 'category'])
                  ->take(20);
            }])
            ->orderBy('sort_order', 'asc')
            ->get();

        foreach ($brands as $brand) {
            $brand->top_promo_products = $brand->products->map(function($product) {
                $validVariants = $product->variants->where('price', '>', 0);
                $originalPrice = $validVariants->isNotEmpty() ? (float) $validVariants->min('price') : (float) ($product->base_price ?? 0);
                $promo = \App\Services\StaticPromoService::forProduct($product, $originalPrice);
                $discountedPrice = \App\Services\StaticPromoService::discountedPrice($originalPrice, $promo);
                
                $discountPercent = $originalPrice > 0 ? (($originalPrice - $discountedPrice) / $originalPrice) * 100 : 0;
                $product->calculated_discount = $discountPercent;
                return $product;
            })->sortByDesc('calculated_discount')->take(3)->values();
        }

        $banners = Banner::where('is_active', true)
            ->where('deleted', false)
            ->with(['images' => fn($q) => $q->where('deleted', false)->orderBy('sort_order')])
            ->orderBy('sort_order', 'asc')
            ->get()
            ->groupBy('type');

        $homepageSections = HomepageSection::where('is_visible', true)
            ->orderBy('sort_order', 'asc')
            ->get();

        $eventPopups = Event::where('is_active', true)
            ->where('deleted', false)
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now())
            ->with('popups')
            ->get();

        $notifications = Notification::where('is_broadcast', true)
            ->orderBy('created_at', 'desc')
            ->get();

        $bundles = ProductBundling::where('is_active', true)
            ->with(['items.product.brand', 'items.product.images', 'items.variant'])
            ->orderBy('created_at', 'desc')
            ->take(6)
            ->get();

        foreach ($bundles as $bundle) {
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

        return view('frontend.home', compact(
            'bestsellers',
            'recommended',
            'recommendedTotal',
            'featured',
            'categories',
            'brands',
            'banners',
            'homepageSections',
            'eventPopups',
            'notifications',
            'bundles'
        ));
    }

    public function loadMore(Request $request)
    {
        $offset = $request->query('offset', 8);
        $limit = $request->query('limit', 4);

        $query = Product::where('deleted', false);

        if ($request->query('sort')) {
            $query->orderByRaw($this->getSortExpression($request->query('sort')));
        }

        $products = $query
            ->with(['brand', 'category', 'images', 'variants', 'tags'])
            ->skip($offset)
            ->take($limit)
            ->get();

        $html = '';
        foreach ($products as $product) {
            $html .= view('frontend.components.product-card-dynamic', ['product' => $product])->render();
        }

        return response()->json([
            'html' => $html,
            'count' => $products->count(),
        ]);
    }

    private function getSortExpression(?string $sort): string
    {
        return match ($sort) {
            'price_asc' => 'base_price ASC',
            'price_desc' => 'base_price DESC',
            'newest' => 'created_at DESC',
            'oldest' => 'created_at ASC',
            'name_asc' => 'name ASC',
            'name_desc' => 'name DESC',
            default => 'created_at DESC',
        };
    }
}

