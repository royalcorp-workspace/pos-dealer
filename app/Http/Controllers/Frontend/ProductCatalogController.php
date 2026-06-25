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
            ->with(['brand', 'category', 'images', 'variants', 'tags']);

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

        // Filter by stock
        if ($inStock === '1') {
            $query->whereHas('variants', fn($q) => $q->where('stock_qty', '>', 0));
        }

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

        $products = $query->orderBy('sort_order')->paginate(12);

        return view('frontend.product.index', [
            'products' => $products,
            'categories' => $categories,
            'brands' => $brands,
            'tags' => $tags,
            'filterType' => $filterType,
            'filterValue' => $filterValue,
            'filters' => $request->query(),
        ]);
    }

    public function show(Product $product)
    {
        $product->load(['brand', 'category', 'images', 'variants', 'tags']);
        
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
}