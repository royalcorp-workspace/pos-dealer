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

        if (!$filterType) {
            $catSlug = $request->route()->parameter('categorySlug');
            $brandSlug = $request->route()->parameter('brandSlug');
            $tagSlug = $request->route()->parameter('tagSlug');
            $genericSlug = $request->route()->parameter('slug');

            if ($catSlug) {
                $legacySlugMap = [
                    'kasur-spring-bed' => 'spring-mattress',
                    'kasur-matras' => 'spring-mattress',
                    'headboard-sandaran' => 'headboard',
                    'protector' => 'mattress-protector',
                    'kasur-busa-foam' => 'foam-mattress',
                    'bantal-guling' => 'pillow-case',
                    'aksesoris-tidur' => 'accessories',
                    'elite-pillow' => 'pillow-case',
                    'bolster' => 'pillow-case',
                    'elite-mattress' => 'spring-mattress',
                    'bj-matrass' => 'mattress',
                    'royal' => 'foam-mattress',
                    'serenity' => 'spring-mattress',
                    'lady' => 'bed-sheet',
                    'moro' => 'bed-linen',
                ];

                if (isset($legacySlugMap[$catSlug])) {
                    return redirect()->route('category.show', $legacySlugMap[$catSlug], 301);
                }

                $filterType = 'category';
                $filterValue = $catSlug;
                if (!\App\Models\Frontend\ProductsCatalog\ProductCategory::where('slug', $filterValue)->where('deleted', false)->exists()) abort(404);
            } elseif ($brandSlug) {
                $filterType = 'brand';
                $filterValue = $brandSlug;
                if (!\App\Models\Frontend\ProductsCatalog\Brand::where('slug', $filterValue)->where('deleted', false)->exists()) abort(404);
            } elseif ($tagSlug) {
                $filterType = 'tag';
                $filterValue = $tagSlug;
                if (!\App\Models\Frontend\ProductsCatalog\ProductTag::where('slug', $filterValue)->where('deleted', false)->exists()) abort(404);
            } elseif ($genericSlug) {
                if (\App\Models\Frontend\ProductsCatalog\ProductCategory::where('slug', $genericSlug)->where('deleted', false)->exists()) {
                    $filterType = 'category';
                    $filterValue = $genericSlug;
                } elseif (\App\Models\Frontend\ProductsCatalog\Brand::where('slug', $genericSlug)->where('deleted', false)->exists()) {
                    $filterType = 'brand';
                    $filterValue = $genericSlug;
                } elseif (\App\Models\Frontend\ProductsCatalog\ProductTag::where('slug', $genericSlug)->where('deleted', false)->exists()) {
                    $filterType = 'tag';
                    $filterValue = $genericSlug;
                } else {
                    abort(404);
                }
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

        $query = Product::where('products.deleted', false)
            ->where(function ($q) {
                $q->where('products.is_bundle', false)
                  ->orWhereNull('products.is_bundle');
            })
            ->where(function ($q) {
                $q->where('products.show_on_web', true)
                  ->orWhereNull('products.show_on_web');
            })
            ->select('products.*')
            ->selectRaw('
                COALESCE(
                    (SELECT 
                        CASE 
                            WHEN ppsi.discount_type = 1 THEN (SELECT CAST(MIN(sell_price) AS numeric) FROM product_variants WHERE product_id = products.id AND deleted = false AND sell_price > 0) * (1 - CAST(ppsi.discount_value AS numeric) / 100)
                            ELSE (SELECT CAST(MIN(sell_price) AS numeric) FROM product_variants WHERE product_id = products.id AND deleted = false AND sell_price > 0) - CAST(ppsi.discount_value AS numeric)
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
                            WHEN pps.discount_type = 1 THEN (SELECT CAST(MIN(sell_price) AS numeric) FROM product_variants WHERE product_id = products.id AND deleted = false AND sell_price > 0) * (1 - CAST(pps.discount_value AS numeric) / 100)
                            ELSE (SELECT CAST(MIN(sell_price) AS numeric) FROM product_variants WHERE product_id = products.id AND deleted = false AND sell_price > 0) - CAST(pps.discount_value AS numeric)
                        END
                     FROM price_product_settings pps
                     WHERE pps.scope = 1
                       AND pps.is_active = true 
                       AND pps.deleted = false
                       AND (pps.start_date IS NULL OR pps.start_date <= NOW())
                       AND (pps.end_date IS NULL OR pps.end_date >= NOW())
                     LIMIT 1
                    ),
                    (SELECT CAST(MIN(sell_price) AS numeric) FROM product_variants WHERE product_id = products.id AND deleted = false AND sell_price > 0)
                ) as promo_price
            ')
            ->with(['brand', 'category', 'images', 'variants', 'colors', 'tags']);

        // Apply existing type/value filters (only for search now, others are handled by selected arrays)
        if ($filterType && $filterValue) {
            if ($filterType === 'search') {
                $query->where(function ($q) use ($filterValue) {
                    $cleanQuery = trim($filterValue);
                    $terms = array_filter(explode(' ', strtolower($cleanQuery)));

                    // 1. Exact phrase match
                    $q->where('name', 'ilike', '%' . $cleanQuery . '%')
                      ->orWhere('slug', 'ilike', '%' . $cleanQuery . '%')
                      ->orWhereHas('brand', fn($b) => $b->where('name', 'ilike', '%' . $cleanQuery . '%'));

                    // 2. Multi-word match (ALL words must match somewhere: in name, slug, category, or brand)
                    if (count($terms) > 1) {
                        $q->orWhere(function ($q2) use ($terms) {
                            foreach ($terms as $term) {
                                $q2->where(function ($q3) use ($term) {
                                    $q3->where('name', 'ilike', '%' . $term . '%')
                                       ->orWhere('slug', 'ilike', '%' . $term . '%')
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
                });
            }
        }

        // Filter by price range (through variants)
        if ($minPrice || $maxPrice) {
            $query->whereHas('variants', function ($q) use ($minPrice, $maxPrice) {
                $q->where('deleted', false)->where('sell_price', '>', 0);
                if (!empty($minPrice) && (float)$minPrice > 0) {
                    $q->where('sell_price', '>=', (float)$minPrice);
                }
                if (!empty($maxPrice) && (float)$maxPrice > 0) {
                    $q->where('sell_price', '<=', (float)$maxPrice);
                }
            });
        }

        // Filter by selected brands (array)
        if (!empty($selectedBrands)) {
            $query->whereHas('brand', fn($q) => $q->whereIn('slug', $selectedBrands));
        }

        // Filter by selected categories (including all children/subcategories recursively)
        if (!empty($selectedCategories)) {
            $allCategoryIds = [];
            foreach ($selectedCategories as $catSlug) {
                $catModel = ProductCategory::where('slug', $catSlug)->where('deleted', false)->first();
                if ($catModel) {
                    $categoryIds = $this->getCategoryHierarchyIds($catModel);
                    $allCategoryIds = array_merge($allCategoryIds, $categoryIds);
                }
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
        // 301 Permanent Redirect if accessed via an old/previous slug
        $requestedSlug = request()->segment(2);
        if (!empty($requestedSlug) && $requestedSlug !== $product->slug) {
            return redirect()->route('products.show', $product->slug, 301);
        }

        if ($product->is_bundle) {
            return redirect()->route('bundling.show', $product->slug);
        }

        if (!empty($product->slug)) {
            session()->put('last_checkout_product_url', route('products.show', $product->slug));
        }

        $product->load(['brand', 'category', 'images', 'variants.images', 'colors', 'tags']);

        // Load suggest bundle items if any
        $suggestAddons = \App\Models\Frontend\ProductsCatalog\ProductBundlingItem::where('product_bundling_id', $product->id)
            ->where('is_suggest', true)
            ->with(['product.variants', 'variant'])
            ->get();

        if ($suggestAddons->isEmpty()) {
            $bundleIds = \App\Models\Frontend\ProductsCatalog\ProductBundlingItem::where('product_id', $product->id)
                ->where('is_suggest', false)
                ->pluck('product_bundling_id');
            if ($bundleIds->isNotEmpty()) {
                $suggestAddons = \App\Models\Frontend\ProductsCatalog\ProductBundlingItem::whereIn('product_bundling_id', $bundleIds)
                    ->where('is_suggest', true)
                    ->with(['product.variants', 'variant'])
                    ->get();
            }
        }

        // Load smart related products (same category or brand - 5 items)
        $relatedProducts = Product::where('deleted', false)
            ->where(function ($q) {
                $q->where('is_bundle', false)
                  ->orWhereNull('is_bundle');
            })
            ->where('id', '!=', $product->id)
            ->where(function($q) use ($product) {
                if ($product->category_id) $q->where('category_id', $product->category_id);
                if ($product->brand_id) $q->orWhere('brand_id', $product->brand_id);
            })
            ->with(['brand', 'category', 'images', 'variants'])
            ->take(5)
            ->get();

        $attributeGroups = [];
        $hasAnyNonIgnoredAttr = false;
        
        $detectedCompletenessTitle = null;
        $detectedSizeTitle = null;
        $hasThicknessSetting = false;
        $hasExplicitThicknessFlag = false;

        foreach ($product->variants as $v) {
            $rawA = $v->getRawOriginal('attributes');
            $pA = is_string($rawA) ? json_decode($rawA, true) : $rawA;
            if (is_array($pA)) {
                if (!empty($pA['_size_title'])) {
                    $detectedSizeTitle = trim((string)$pA['_size_title']);
                }
                if (!empty($pA['_completeness_title'])) {
                    $detectedCompletenessTitle = trim((string)$pA['_completeness_title']);
                }
                if (isset($pA['_has_thickness'])) {
                    $hasExplicitThicknessFlag = true;
                    if ($pA['_has_thickness'] === true || $pA['_has_thickness'] === 'true' || $pA['_has_thickness'] === 1 || $pA['_has_thickness'] === '1') {
                        $hasThicknessSetting = true;
                    }
                } elseif (!empty($pA['Ketebalan'])) {
                    $hasThicknessSetting = true;
                }
            }
        }
        $sizeTitle = $detectedSizeTitle ?: 'Ukuran';
        $completenessTitle = $detectedCompletenessTitle ?: 'Kelengkapan';
        $isThicknessEnabled = $hasExplicitThicknessFlag ? $hasThicknessSetting : $hasThicknessSetting;

        // Determine if any variant is explicitly marked active (status = 1 / true)
        $hasExplicitActive = $product->variants->contains(function ($v) {
            return ($v->status === true || (string)$v->status === '1' || (int)$v->status === 1) 
                && !($v->deleted === true || (int)$v->deleted === 1) 
                && (float)$v->sell_price > 0;
        });

        foreach ($product->variants as $variant) {
            // Skip invalid or deleted variants (0 price or deleted=true)
            if ((float) $variant->sell_price <= 0 || $variant->deleted === true || (int)$variant->deleted === 1) {
                continue;
            }

            // If some variants are explicitly active, only skip variants that are explicitly deactivated (status = 0 / false)
            if ($hasExplicitActive && ($variant->status === false || (string)$variant->status === '0')) {
                continue;
            }

            $variantAttributes = [];
            $rawAttributes = $variant->getRawOriginal('attributes');
            if ($rawAttributes) {
                $variantAttributes = is_string($rawAttributes) ? json_decode($rawAttributes, true) : $rawAttributes;
            }
            if (!is_array($variantAttributes)) {
                $variantAttributes = [];
            }
            
            $ignoredKeys = ['width', 'length', 'height', 'weight', 'status', '_size_title', '_completeness_title', '_has_thickness', 'image', 'image_url', 'thickness', 'tebal'];
            foreach ($variantAttributes as $key => $value) {
                if (in_array(strtolower($key), $ignoredKeys) || in_array($key, $ignoredKeys) || empty($value)) {
                    continue;
                }
                $normKey = $key;
                $normValue = (string) $value;
                if (strcasecmp($normKey, 'feel') === 0 || strcasecmp($normKey, 'completeness') === 0 || strcasecmp($normKey, 'kelengkapan') === 0) {
                    $normKey = $completenessTitle;
                }
                if (strcasecmp($normKey, 'ukuran') === 0 || strcasecmp($normKey, $sizeTitle) === 0) {
                    $normKey = $sizeTitle;
                }
                if (strcasecmp($normValue, 'mattress only') === 0 || strcasecmp($normValue, 'mattress') === 0) {
                    $normValue = 'Kasur Saja';
                } elseif (strcasecmp($normValue, 'fullset') === 0 || strcasecmp($normValue, 'full bed set') === 0) {
                    $normValue = 'Set Kasur + Divan';
                }

                if (!isset($attributeGroups[$normKey])) {
                    $attributeGroups[$normKey] = [];
                }
                $exists = false;
                foreach ($attributeGroups[$normKey] as $opt) {
                    if (strcasecmp(trim($opt), trim($normValue)) === 0) {
                        $exists = true;
                        break;
                    }
                }
                if (!$exists) {
                    $attributeGroups[$normKey][] = $normValue;
                }
                $hasAnyNonIgnoredAttr = true;
            }
            
            // 1. Ensure Size is added under $sizeTitle
            $ukuranVal = null;
            if (!empty($variantAttributes[$sizeTitle])) {
                $ukuranVal = (string) $variantAttributes[$sizeTitle];
            } elseif (!empty($variantAttributes['Ukuran'])) {
                $ukuranVal = (string) $variantAttributes['Ukuran'];
            } elseif (!empty($variantAttributes['Dimensi'])) {
                $ukuranVal = (string) $variantAttributes['Dimensi'];
            } elseif (!empty($variantAttributes['Size'])) {
                $ukuranVal = (string) $variantAttributes['Size'];
            } elseif (!empty($variantAttributes['width']) && !empty($variantAttributes['length'])) {
                $w = (int)$variantAttributes['width'];
                $l = (int)$variantAttributes['length'];
                $wStr = $w < 100 ? '0' . $w : (string)$w;
                $ukuranVal = "{$wStr} X {$l}";
            } elseif (!empty($variant->width) && !empty($variant->length)) {
                $w = (int)$variant->width;
                $l = (int)$variant->length;
                $wStr = $w < 100 ? '0' . $w : (string)$w;
                $ukuranVal = "{$wStr} X {$l}";
            } elseif (preg_match('/(\d{2,3})\s*[xX]\s*(\d{3})/i', (string)$variant->variant_name, $m)) {
                $w = (int)$m[1];
                $l = (int)$m[2];
                $wStr = $w < 100 ? '0' . $w : (string)$w;
                $ukuranVal = "{$wStr} X {$l}";
            }

            if ($ukuranVal) {
                if (!isset($attributeGroups[$sizeTitle])) {
                    $attributeGroups[$sizeTitle] = [];
                }
                $alreadyExists = false;
                foreach ($attributeGroups[$sizeTitle] as $existingUkuran) {
                    if (strcasecmp(trim($existingUkuran), trim($ukuranVal)) === 0) {
                        $alreadyExists = true;
                        break;
                    }
                }
                if (!$alreadyExists) {
                    $attributeGroups[$sizeTitle][] = $ukuranVal;
                }
            }

            // 2. Ensure Completeness / Custom Option is added if present or detectable from variant_name
            $kelengkapanVal = null;
            if (!empty($variantAttributes[$completenessTitle])) {
                $kelengkapanVal = (string) $variantAttributes[$completenessTitle];
            } elseif (!empty($variantAttributes['Kelengkapan'])) {
                $kelengkapanVal = (string) $variantAttributes['Kelengkapan'];
            } elseif (!empty($variantAttributes['feel'])) {
                $kelengkapanVal = (string) $variantAttributes['feel'];
            } elseif (!empty($variantAttributes['completeness'])) {
                $kelengkapanVal = (string) $variantAttributes['completeness'];
            } elseif (preg_match('/(kasur\s+saja|mattress\s+only|matras\s+saja)/i', (string)$variant->variant_name)) {
                $kelengkapanVal = 'Kasur Saja';
            } elseif (preg_match('/(set\s+kasur\s*\+\s*divan|set\s+kasur|full\s*set|full\s*bed\s*set)/i', (string)$variant->variant_name)) {
                $kelengkapanVal = 'Set Kasur + Divan';
            }

            if ($kelengkapanVal) {
                if (strcasecmp($kelengkapanVal, 'mattress only') === 0 || strcasecmp($kelengkapanVal, 'mattress') === 0) {
                    $kelengkapanVal = 'Kasur Saja';
                } elseif (strcasecmp($kelengkapanVal, 'fullset') === 0 || strcasecmp($kelengkapanVal, 'full bed set') === 0) {
                    $kelengkapanVal = 'Set Kasur + Divan';
                }
                if (!isset($attributeGroups[$completenessTitle])) {
                    $attributeGroups[$completenessTitle] = [];
                }
                $alreadyExists = false;
                foreach ($attributeGroups[$completenessTitle] as $existingK) {
                    if (strcasecmp(trim($existingK), trim($kelengkapanVal)) === 0) {
                        $alreadyExists = true;
                        break;
                    }
                }
                if (!$alreadyExists) {
                    $attributeGroups[$completenessTitle][] = $kelengkapanVal;
                }
            }

            // 3. Ensure "Ketebalan" is added ONLY IF thickness is enabled
            if ($isThicknessEnabled) {
                $ketebalanVal = null;
                if (!empty($variantAttributes['Ketebalan'])) {
                    $ketebalanVal = (string) $variantAttributes['Ketebalan'];
                } elseif (!empty($variantAttributes['tebal'])) {
                    $ketebalanVal = ((int)$variantAttributes['tebal']) . ' cm';
                } elseif (!empty($variantAttributes['height']) && (float)$variantAttributes['height'] > 0) {
                    $ketebalanVal = ((int)$variantAttributes['height']) . ' cm';
                } elseif (preg_match('/[Tt]\.?\s*(\d{2})\s*(?:cm)?/i', (string)$variant->variant_name, $m)) {
                    $ketebalanVal = $m[1] . ' cm';
                } elseif (preg_match('/(?:tebal|tinggi)\s*(\d{2})/i', (string)$variant->variant_name, $m)) {
                    $ketebalanVal = $m[1] . ' cm';
                }

                if ($ketebalanVal) {
                    if (!str_ends_with(strtolower($ketebalanVal), 'cm')) {
                        $ketebalanVal .= ' cm';
                    }
                    if (!isset($attributeGroups['Ketebalan'])) {
                        $attributeGroups['Ketebalan'] = [];
                    }
                    $alreadyExists = false;
                    foreach ($attributeGroups['Ketebalan'] as $existingT) {
                        if (strcasecmp(trim($existingT), trim($ketebalanVal)) === 0) {
                            $alreadyExists = true;
                            break;
                        }
                    }
                    if (!$alreadyExists) {
                        $attributeGroups['Ketebalan'][] = $ketebalanVal;
                    }
                }
            }
        }

        // Sort each attribute group from smallest to largest (numerically / dimension-wise)
        foreach ($attributeGroups as $groupKey => &$optionsList) {
            usort($optionsList, function ($a, $b) use ($groupKey, $completenessTitle) {
                if (strcasecmp($groupKey, $completenessTitle) === 0 || strcasecmp($groupKey, 'Kelengkapan') === 0) {
                    // Kasur Saja should appear before Set Kasur + Divan
                    if (stripos((string)$a, 'Kasur Saja') !== false) return -1;
                    if (stripos((string)$b, 'Kasur Saja') !== false) return 1;
                }
                // Extract first number in string (e.g. 120 from "120 X 200" or 25 from "25 cm")
                preg_match('/\d+/', (string)$a, $matchesA);
                preg_match('/\d+/', (string)$b, $matchesB);
                $numA = isset($matchesA[0]) ? (int)$matchesA[0] : 999999;
                $numB = isset($matchesB[0]) ? (int)$matchesB[0] : 999999;

                if ($numA === $numB) {
                    return strnatcasecmp((string)$a, (string)$b);
                }
                return $numA <=> $numB;
            });
        }
        unset($optionsList);

        if ($sizeTitle !== 'Ukuran' && isset($attributeGroups['Ukuran'])) {
            unset($attributeGroups['Ukuran']);
        }

        return view('frontend.product.show', compact('product', 'attributeGroups', 'sizeTitle', 'completenessTitle', 'relatedProducts', 'suggestAddons'));
    }

    public function searchSuggestions(Request $request)
    {
        $query = strtolower(trim($request->query('q')));
        
        if (!$query || strlen($query) < 2) {
            return response()->json([]);
        }

        $words = array_filter(explode(' ', $query));

        $products = Product::where('deleted', false)
            ->where(function ($q) {
                $q->where('is_bundle', false)
                  ->orWhereNull('is_bundle');
            })
            ->where(function ($q) use ($query, $words) {
                // 1. Exact phrase match in name, slug, or brand
                $q->where('name', 'ilike', '%' . $query . '%')
                  ->orWhere('slug', 'ilike', '%' . $query . '%')
                  ->orWhereHas('brand', fn($b) => $b->where('name', 'ilike', '%' . $query . '%'));

                // 2. Multi-word match: ALL words must match somewhere
                if (count($words) > 1) {
                    $q->orWhere(function ($q2) use ($words) {
                        foreach ($words as $word) {
                            $q2->where(function ($q3) use ($word) {
                                $q3->where('name', 'ilike', '%' . $word . '%')
                                   ->orWhere('slug', 'ilike', '%' . $word . '%')
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
            })
            ->with(['category', 'brand', 'variants'])
            ->limit(20) // Fetch more to sort by relevance in PHP
            ->get();

        // Sort in PHP to ensure the most relevant (exact matches) appear first
        $products = $products->sortByDesc(function ($product) use ($query, $words) {
            $name = strtolower($product->name);
            $brandName = strtolower($product->brand->name ?? '');
            $score = 0;
            
            if ($name === $query) return 1000;
            if ($brandName === $query) $score += 800;
            if (str_starts_with($brandName, $query)) $score += 600;
            if (str_contains($brandName, $query)) $score += 400;
            if (str_starts_with($name, $query)) $score += 300;
            if (str_contains($name, $query)) $score += 200;
            
            $matchedWords = 0;
            foreach ($words as $word) {
                if (str_contains($brandName, $word)) {
                    $score += 150;
                    $matchedWords++;
                } elseif (str_contains($name, $word)) {
                    $score += 50;
                    $matchedWords++;
                }
            }
            if (count($words) > 1 && $matchedWords === count($words)) {
                $score += 100;
            }
            return $score;
        })
        ->take(5)
        ->values()
        ->map(function ($product) {
            $variantsData = $product->variants ? $product->variants->where('deleted', false) : collect();
            $validVariants = $variantsData->where('sell_price', '>', 0);
            $hasVariants = $validVariants->isNotEmpty();
            $minVariant = $hasVariants ? $validVariants->sortBy('sell_price')->first() : null;
            $originalPrice = $minVariant ? (float) $minVariant->sell_price : 0;
            $staticPromo = \App\Services\StaticPromoService::forProduct($product, $originalPrice);
            $price = \App\Services\StaticPromoService::discountedPrice($originalPrice, $staticPromo);

            return [
                'id' => $product->id,
                'name' => $product->name,
                'slug' => $product->slug,
                'thumbnail_url' => $product->thumbnail_url,
                'price' => (float) $price,
                'sell_price' => (float) $price,
                'category' => $product->category->name ?? '',
                'brand' => $product->brand->name ?? ''
            ];
        });

        return response()->json($products);
    }

    private function getCategoryHierarchyIds(ProductCategory $category): array
    {
        $ids = [$category->id];
        $children = $category->children()->where('deleted', false)->pluck('id')->toArray();
        $ids = array_merge($ids, $children);

        foreach ($children as $childId) {
            $child = ProductCategory::find($childId);
            if ($child && !$child->deleted) {
                $grandchildren = $child->children()->where('deleted', false)->pluck('id')->toArray();
                $ids = array_merge($ids, $grandchildren);
            }
        }

        return array_unique($ids);
    }

    private function getSortExpression(?string $sort): string
    {
        return match ($sort) {
            'price_asc' => 'promo_price ASC NULLS LAST, created_at DESC',
            'price_desc' => 'promo_price DESC NULLS LAST, created_at DESC',
            'newest' => 'created_at DESC',
            'best_seller' => 'best_seller DESC, created_at DESC',
            'best_selling' => '(SELECT COALESCE(SUM(quantity), 0) FROM order_items WHERE order_items.product_id = products.id) DESC',
            'oldest' => 'created_at ASC',
            'name_asc' => 'name ASC',
            'name_desc' => 'name DESC',
            'rating' => 'average_rating DESC NULLS LAST, created_at DESC',
            'display_web', 'urutan' => 'CASE WHEN products.sort_order > 0 THEN products.sort_order ELSE 999999 END ASC, products.best_seller DESC, products.created_at DESC',
            default => 'CASE WHEN products.sort_order > 0 THEN products.sort_order ELSE 999999 END ASC, products.best_seller DESC, products.created_at DESC',
        };
    }
}
