<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Frontend\ProductsCatalog\Brand;
use App\Models\Frontend\ProductsCatalog\Product;
use App\Models\Frontend\ProductsCatalog\ProductCategory;
use App\Models\Frontend\ProductsCatalog\ProductTag;
use Illuminate\Http\Request;

class ProductCatalogController extends Controller
{
    public function index(Request $request)
    {
        $filterType = $request->query('type');
        $filterValue = $request->query('value');

        // Handle tag slug in URL path
        $tagSlug = $request->route()->parameter('tagSlug');
        if ($tagSlug && !$filterType) {
            $filterType = 'search';
            $filterValue = $tagSlug;
        }

        // Handle category slug in URL path (SEO-friendly)
        $categorySlug = $request->route()->parameter('categorySlug');
        if ($categorySlug && !$filterType) {
            $filterType = 'category';
            $filterValue = $categorySlug;
        }

        // Handle brand slug in URL path
        $brandSlug = $request->route()->parameter('brandSlug');
        if ($brandSlug && !$filterType) {
            $filterType = 'brand';
            $filterValue = $brandSlug;
        }

        $minPrice = $request->query('min_price', 0);
        $maxPrice = $request->query('max_price');
        $inStock = $request->query('in_stock');
        $selectedBrands = $request->query('brands', []);
        $selectedCategories = $request->query('categories', []);
        $selectedTags = $request->query('tags', []);

        $categories = ProductCategory::where('deleted', false)
            ->whereNull('parent_id')
            ->with('children.children')
            ->orderBy('sort_order')
            ->get();

        $brands = Brand::where('deleted', false)
            ->orderBy('sort_order')
            ->get();

        $tags = ProductTag::where('deleted', false)
            ->orderBy('sort_order')
            ->get();

        $query = Product::where('deleted', false)
            ->select('products.*')
            ->selectRaw('
                COALESCE(
                    (SELECT 
                        CASE 
                            WHEN ppsi.discount_type = 1 THEN CAST(products.base_price AS numeric) * (1 - CAST(ppsi.discount_value AS numeric) / 100)
                            ELSE CAST(products.base_price AS numeric) - CAST(ppsi.discount_value AS numeric)
                        END
                     FROM price_product_setting_items ppsi
                     JOIN price_product_settings pps ON pps.id = ppsi.price_product_setting_id
                     WHERE ppsi.product_id = products.id 
                       AND ppsi.deleted = false 
                       AND pps.is_active = true 
                       AND pps.deleted = false
                       AND (pps.start_date IS NULL OR pps.start_date <= NOW())
                       AND (pps.end_date IS NULL OR pps.end_date >= NOW())
                     LIMIT 1
                    ),
                    (SELECT
                        CASE 
                            WHEN pps.discount_type = 1 THEN CAST(products.base_price AS numeric) * (1 - CAST(pps.discount_value AS numeric) / 100)
                            ELSE CAST(products.base_price AS numeric) - CAST(pps.discount_value AS numeric)
                        END
                     FROM price_product_settings pps
                     WHERE pps.scope = 1
                       AND pps.is_active = true 
                       AND pps.deleted = false
                       AND (pps.start_date IS NULL OR pps.start_date <= NOW())
                       AND (pps.end_date IS NULL OR pps.end_date >= NOW())
                     LIMIT 1
                    ),
                    CAST(products.base_price AS numeric)
                ) as promo_price
            ')
            ->with(['brand', 'category', 'images', 'variants', 'colors', 'tags']);

        // Apply existing type/value filters
        if ($filterType && $filterValue) {
            if ($filterType === 'brand') {
                $query->whereHas('brand', fn($q) => $q->where('slug', $filterValue));
            } elseif ($filterType === 'category') {
                $category = ProductCategory::where('slug', $filterValue)->first();
                if ($category) {
                    $categoryIds = $this->getCategoryHierarchyIds($category);
                    $query->whereIn('category_id', $categoryIds);
                }
            } elseif ($filterType === 'search') {
                $query->where(function ($q) use ($filterValue) {
                    $q->where('name', 'like', "%{$filterValue}%")
                        ->orWhere('slug', 'like', "%{$filterValue}%")
                        ->orWhereHas('brand', fn($b) => $b->where('name', 'like', "%{$filterValue}%"))
                        ->orWhereHas('category', fn($c) => $c->where('name', 'like', "%{$filterValue}%"));
                });

                // Fuzzy search: split terms and search each separately for typo tolerance
                $terms = explode(' ', $filterValue);
                foreach ($terms as $term) {
                    if (strlen($term) > 2) {
                        $query->orWhere(function ($q) use ($term) {
                            $q->where('name', 'like', "%{$term}%")
                                ->orWhere('slug', 'like', "%{$term}%")
                                ->orWhereHas('brand', fn($b) => $b->where('name', 'like', "%{$term}%"))
                                ->orWhereHas('tags', fn($t) => $t->where('name', 'like', "%{$term}%"));
                        });
                    }
                }
            }
        }

        // Filter by price range (through variants)
        if ($minPrice || $maxPrice) {
            $query->whereHas('variants', function ($q) use ($minPrice, $maxPrice) {
                if ($minPrice) {
                    $q->where('price', '>=', $minPrice);
                }
                if ($maxPrice) {
                    $q->where('price', '<=', $maxPrice);
                }
            });
        }

        // Filter by stock (Bypassed)
        // if ($inStock === '1') {
        //     $query->whereHas('variants', fn($q) => $q->where('stock_quantity', '>', 0));
        // }

        // Filter by selected brands (array)
        if (!empty($selectedBrands)) {
            $query->whereHas('brand', fn($q) => $q->whereIn('slug', $selectedBrands));
        }

        // Filter by selected categories (array)
        if (!empty($selectedCategories)) {
            $query->whereHas('category', fn($q) => $q->whereIn('slug', $selectedCategories));
        }

        // Filter by selected tags (array)
        if (!empty($selectedTags)) {
            $query->whereHas('tags', fn($q) => $q->whereIn('slug', $selectedTags));
        }

        $products = $query;

        $sort = $request->query('sort', 'best_seller');
        $sortExpression = $this->getSortExpression($sort);

        $products = $query->orderByRaw($sortExpression)
            ->paginate(12)
            ->withQueryString();

        if ($request->ajax() || $request->wantsJson() || $request->has('load_more')) {
            $gridHtml = '';
            $listHtml = '';
            foreach ($products as $product) {
                $gridHtml .= view('frontend.components.product-card-dynamic', ['product' => $product])->render();
                $listHtml .= view('frontend.components.product-card-list', ['product' => $product])->render();
            }
            return response()->json([
                'grid_html' => $gridHtml,
                'list_html' => $listHtml,
                'next_page_url' => $products->nextPageUrl(),
                'has_more' => $products->hasMorePages(),
            ]);
        }

        return view('frontend.product.index', [
            'products' => $products,
            'categories' => $categories,
            'brands' => $brands,
            'tags' => $tags,
            'filterType' => $filterType,
            'filterValue' => $filterValue,
            'filters' => $request->query(),
            'sort' => $sort,
        ]);
    }

    public function show(Product $product)
    {
        $product->load(['brand', 'category', 'images', 'variants', 'colors', 'tags']);

        return view('frontend.product.show', compact('product'));
    }

    private function getCategoryHierarchyIds(ProductCategory $category): array
    {
        $ids = [$category->id];
        $children = $category->children()->pluck('id')->toArray();
        $ids = array_merge($ids, $children);

        foreach ($children as $childId) {
            $child = ProductCategory::find($childId);
            if ($child) {
                $grandchildren = $child->children()->pluck('id')->toArray();
                $ids = array_merge($ids, $grandchildren);
            }
        }

        return array_unique($ids);
    }

    private function getSortExpression(?string $sort): string
    {
        return match ($sort) {
            'price_asc' => 'promo_price ASC, created_at DESC',
            'price_desc' => 'promo_price DESC, created_at DESC',
            'newest' => 'created_at DESC',
            'best_seller' => 'best_seller DESC, created_at DESC',
            'best_selling' => '(SELECT COALESCE(SUM(quantity), 0) FROM order_items WHERE order_items.product_id = products.id) DESC',
            'oldest' => 'created_at ASC',
            'name_asc' => 'name ASC',
            'name_desc' => 'name DESC',
            'rating' => 'average_rating DESC NULLS LAST, created_at DESC',
            default => 'best_seller DESC, created_at DESC',
        };
    }
}
