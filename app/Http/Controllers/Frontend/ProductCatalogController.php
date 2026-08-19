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

        // Handle direct root slug (catch-all route)
        $urlSlug = $request->route()->parameter('tagSlug') ?? $request->route()->parameter('categorySlug') ?? $request->route()->parameter('brandSlug');
        
        if ($urlSlug && !$filterType) {
            // Check if it's a Category
            if (ProductCategory::where('slug', $urlSlug)->where('deleted', false)->exists()) {
                $filterType = 'category';
                $filterValue = $urlSlug;
            } 
            // Check if it's a Brand
            elseif (Brand::where('slug', $urlSlug)->where('deleted', false)->exists()) {
                $filterType = 'brand';
                $filterValue = $urlSlug;
            }
            // Check if it's a Tag
            elseif (ProductTag::where('slug', $urlSlug)->where('deleted', false)->exists()) {
                $filterType = 'tag';
                $filterValue = $urlSlug;
            }
            // If it doesn't match any entity, it's an invalid URL, so 404
            else {
                abort(404);
            }
        }

        $minPrice = $request->query('min_price');
        $maxPrice = $request->query('max_price');
        $inStock = $request->query('in_stock');
        
        $selectedBrands = $request->query('brands', []);
        if (!is_array($selectedBrands)) $selectedBrands = [$selectedBrands];
        
        $selectedCategories = $request->query('categories', []);
        if (!is_array($selectedCategories)) $selectedCategories = [$selectedCategories];
        
        $selectedTags = $request->query('tags', []);
        if (!is_array($selectedTags)) $selectedTags = [$selectedTags];

        // Ensure current route filter is checked in the sidebar
        if ($filterType === 'category' && $filterValue && !in_array($filterValue, $selectedCategories)) {
            $selectedCategories[] = $filterValue;
        }
        if ($filterType === 'brand' && $filterValue && !in_array($filterValue, $selectedBrands)) {
            $selectedBrands[] = $filterValue;
        }
        if ($filterType === 'tag' && $filterValue && !in_array($filterValue, $selectedTags)) {
            $selectedTags[] = $filterValue;
        }

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

        // Apply existing type/value filters (only for search now, others are handled by selected arrays)
        if ($filterType && $filterValue) {
            if ($filterType === 'search') {
                $query->where(function ($q) use ($filterValue) {
                    $terms = array_filter(explode(' ', strtolower(trim($filterValue))));
                    
                    // Build a fuzzy string for typos: 'k a s u r' -> '%k%a%s%u%r%'
                    $fuzzyString = '%';
                    foreach (str_split(str_replace(' ', '', strtolower(trim($filterValue)))) as $char) {
                        $fuzzyString .= $char . '%';
                    }

                    // 1. Exact phrase match
                    $q->where('name', 'ilike', '%' . $filterValue . '%')
                      ->orWhere('code', 'ilike', '%' . $filterValue . '%')
                      ->orWhere('slug', 'ilike', '%' . $filterValue . '%');

                    // 2. Multi-word match (e.g., "Kasur 200 100" requires ALL words to match somewhere)
                    if (count($terms) > 1) {
                        $q->orWhere(function ($q2) use ($terms) {
                            foreach ($terms as $term) {
                                $q2->where(function ($q3) use ($term) {
                                    $q3->where('name', 'ilike', '%' . $term . '%')
                                       ->orWhere('code', 'ilike', '%' . $term . '%')
                                       ->orWhereHas('category', function ($qCat) use ($term) {
                                           $qCat->where('name', 'ilike', '%' . $term . '%');
                                       })
                                       ->orWhereHas('brand', function ($qBrand) use ($term) {
                                           $qBrand->where('name', 'ilike', '%' . $term . '%');
                                       });
                                });
                            }
                        });
                    }

                    // 3. Typo/Fuzzy match ("ksur" -> "%k%s%u%r%")
                    if (strlen($filterValue) <= 15) {
                        $q->orWhere('name', 'ilike', $fuzzyString);
                    }
                });
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
            $allCategoryIds = [];
            $matchedCategories = ProductCategory::whereIn('slug', $selectedCategories)->get();
            foreach ($matchedCategories as $cat) {
                $allCategoryIds = array_merge($allCategoryIds, $this->getCategoryHierarchyIds($cat));
            }
            if (!empty($allCategoryIds)) {
                $query->whereIn('category_id', array_unique($allCategoryIds));
            }
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
            'filters' => array_merge($request->query(), [
                'categories' => $selectedCategories,
                'brands' => $selectedBrands,
                'tags' => $selectedTags,
            ]),
            'sort' => $sort,
        ]);
    }

    public function show(Product $product)
    {
        $product->load(['brand', 'category', 'images', 'variants', 'colors', 'tags', 'suggestedProducts.images']);

        $attributeGroups = [];
        foreach ($product->variants as $variant) {
            $variantAttributes = [];
            $rawAttributes = $variant->getRawOriginal('attributes');
            if ($rawAttributes) {
                $variantAttributes = is_string($rawAttributes) ? json_decode($rawAttributes, true) : $rawAttributes;
            }
            
            if (!empty($variantAttributes) && is_array($variantAttributes)) {
                $ignoredKeys = ['width', 'length', 'height', 'weight'];
                foreach ($variantAttributes as $key => $value) {
                    if (in_array(strtolower($key), $ignoredKeys)) {
                        continue;
                    }
                    if (!isset($attributeGroups[$key])) {
                        $attributeGroups[$key] = [];
                    }
                    if (!in_array($value, $attributeGroups[$key])) {
                        $attributeGroups[$key][] = $value;
                    }
                }
            }
        }

        return view('frontend.product.show', compact('product', 'attributeGroups'));
    }

    public function searchSuggestions(Request $request)
    {
        $query = strtolower(trim($request->query('q')));
        
        if (!$query || strlen($query) < 2) {
            return response()->json([]);
        }

        $words = array_filter(explode(' ', $query));
        
        // Build a fuzzy string for typos: 'k a s u r' -> '%k%a%s%u%r%'
        $fuzzyString = '%';
        foreach (str_split(str_replace(' ', '', $query)) as $char) {
            $fuzzyString .= $char . '%';
        }

        $products = Product::where('deleted', false)
            ->where(function ($q) use ($query, $words, $fuzzyString) {
                // 1. Exact phrase match
                $q->where('name', 'ilike', '%' . $query . '%')
                  ->orWhere('code', 'ilike', '%' . $query . '%');

                // 2. Multi-word match (e.g., "Kasur 200 100")
                if (count($words) > 1) {
                    $q->orWhere(function ($q2) use ($words) {
                        foreach ($words as $word) {
                            $q2->where(function ($q3) use ($word) {
                                $q3->where('name', 'ilike', '%' . $word . '%')
                                   ->orWhere('code', 'ilike', '%' . $word . '%')
                                   ->orWhereHas('category', function ($qCat) use ($word) {
                                       $qCat->where('name', 'ilike', '%' . $word . '%');
                                   })
                                   ->orWhereHas('brand', function ($qBrand) use ($word) {
                                       $qBrand->where('name', 'ilike', '%' . $word . '%');
                                   });
                            });
                        }
                    });
                }
                
                // 3. Typo/Fuzzy match ("ksur" -> "%k%s%u%r%")
                if (strlen($query) <= 15) {
                    $q->orWhere('name', 'ilike', $fuzzyString);
                }
            })
            ->with(['category', 'brand'])
            ->limit(20) // Fetch more to sort by relevance in PHP
            ->get();

        // Sort in PHP to ensure the most relevant (exact matches) appear first
        $products = $products->sortByDesc(function ($product) use ($query, $words) {
            $name = strtolower($product->name);
            $score = 0;
            
            if ($name === $query) return 1000;
            if (str_contains($name, $query)) $score += 500;
            if (str_starts_with($name, $query)) $score += 200;
            
            foreach ($words as $word) {
                if (str_contains($name, $word)) $score += 50;
            }
            return $score;
        })
        ->take(5)
        ->values()
        ->map(function ($product) {
            $variantsData = $product->variants;
            $validVariants = $variantsData->where('price', '>', 0);
            $hasVariants = $validVariants->isNotEmpty();
            $basePrice = (float)($product->base_price ?? 0);
            $originalPrice = $hasVariants ? (float) $validVariants->first()->price : $basePrice;
            $staticPromo = \App\Services\StaticPromoService::forProduct($product, $originalPrice);
            $price = \App\Services\StaticPromoService::discountedPrice($originalPrice, $staticPromo);

            return [
                'id' => $product->id,
                'name' => $product->name,
                'slug' => $product->slug,
                'thumbnail_url' => $product->thumbnail_url,
                'price' => $price,
                'category' => $product->category->name ?? '',
                'brand' => $product->brand->name ?? ''
            ];
        });

        return response()->json($products);
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
