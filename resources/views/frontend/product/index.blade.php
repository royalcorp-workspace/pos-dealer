@extends('frontend.layouts.app')

@php
    $displayTitle = __('Semua Produk');
    $eyebrow = null;
    $pageDescription = null;
    $contextType = $filterType;

    $selectedCategorySlugs = $filters['categories'] ?? [];
    $selectedBrandSlugs = $filters['brands'] ?? [];
    $selectedTagSlugs = $filters['tags'] ?? [];

    if ($filterType === 'brand' && $filterValue) {
        $brand = $brands->first(fn($b) => $b->slug === $filterValue) ?? \App\Models\Frontend\ProductsCatalog\Brand::where('slug', $filterValue)->where('deleted', false)->first();
        $displayTitle = $brand ? $brand->name : $filterValue;
        $eyebrow = __('Koleksi Brand');
        $pageDescription = $brand?->description ?? null;
    } elseif ($filterType === 'category' && $filterValue) {
        $category = $categories->first(fn($c) => $c->slug === $filterValue) ?? \App\Models\Frontend\ProductsCatalog\ProductCategory::where('slug', $filterValue)->where('deleted', false)->first();
        $displayTitle = $category ? $category->name : $filterValue;
        $eyebrow = __('Koleksi Kategori');
        $pageDescription = $category?->description ?? null;
    } elseif ($filterType === 'search' && $filterValue) {
        $displayTitle = '"' . $filterValue . '"';
        $eyebrow = __('Hasil Pencarian');
    } elseif (!empty($selectedCategorySlugs) && count($selectedCategorySlugs) === 1 && empty($selectedBrandSlugs) && empty($selectedTagSlugs)) {
        $singleCatSlug = reset($selectedCategorySlugs);
        $matchedCategory = $categories->first(fn($c) => $c->slug === $singleCatSlug) ?? \App\Models\Frontend\ProductsCatalog\ProductCategory::where('slug', $singleCatSlug)->where('deleted', false)->first();
        $displayTitle = $matchedCategory ? $matchedCategory->name : str_replace('-', ' ', ucwords($singleCatSlug));
        $eyebrow = __('Koleksi Kategori');
        $pageDescription = $matchedCategory?->description ?? null;
        $contextType = 'category';
    } elseif (!empty($selectedBrandSlugs) && count($selectedBrandSlugs) === 1 && empty($selectedCategorySlugs) && empty($selectedTagSlugs)) {
        $singleBrandSlug = reset($selectedBrandSlugs);
        $matchedBrand = $brands->first(fn($b) => $b->slug === $singleBrandSlug) ?? \App\Models\Frontend\ProductsCatalog\Brand::where('slug', $singleBrandSlug)->where('deleted', false)->first();
        $displayTitle = $matchedBrand ? $matchedBrand->name : str_replace('-', ' ', ucwords($singleBrandSlug));
        $eyebrow = __('Koleksi Brand');
        $pageDescription = $matchedBrand?->description ?? null;
        $contextType = 'brand';
    } elseif (!empty($selectedTagSlugs) && count($selectedTagSlugs) === 1 && empty($selectedCategorySlugs) && empty($selectedBrandSlugs)) {
        $singleTagSlug = reset($selectedTagSlugs);
        $matchedTag = $tags->first(fn($t) => $t->slug === $singleTagSlug) ?? \App\Models\Frontend\ProductsCatalog\ProductTag::where('slug', $singleTagSlug)->where('deleted', false)->first();
        $displayTitle = $matchedTag ? $matchedTag->name : str_replace('-', ' ', ucwords($singleTagSlug));
        $eyebrow = __('Koleksi Tag');
        $contextType = 'tag';
    } elseif (!empty($selectedCategorySlugs) || !empty($selectedBrandSlugs) || !empty($selectedTagSlugs) || !empty($filters['min_price']) || !empty($filters['max_price']) || !empty($filters['in_stock'])) {
        $displayTitle = __('Hasil Filter Produk');
        $eyebrow = __('Filter Aktif');
        $pageDescription = __('Menampilkan produk sesuai kriteria filter yang Anda pilih.');
    }

    $title = $displayTitle;
    $title = html_entity_decode($title);
@endphp

@section('title', $title . ' - IMG')
@section('meta_description', 'Temukan ' . strtolower($title) . ' di IMG. Bandingkan kasur, springbed, bantal, dan perlengkapan tidur premium berdasarkan kategori, brand, harga, dan stok.')
@section('canonical', request()->fullUrl())
@section('og_type', 'website')

@section('content')
    @php
        $offset = ($products->currentPage() - 1) * $products->perPage();
        $productItems = $products->map(function ($product, $index) use ($offset) {
            return [
                '@type' => 'ListItem',
                'position' => $offset + $index + 1,
                'url' => route('products.show', $product->slug),
                'name' => $product->name,
            ];
        })->values()->toArray();

        $filterName = $title;
        if ($filterType === 'brand' && $filterValue) {
            $filterDescription = 'Koleksi produk dari brand ' . ($brand?->name ?? $filterValue) . ' di IMG, termasuk kasur, springbed, bantal, dan aksesori tidur premium.';
        } elseif ($filterType === 'category' && $filterValue) {
            $filterDescription = 'Koleksi produk kategori ' . ($category?->name ?? $filterValue) . ' di IMG dengan pilihan springbed dan perlengkapan tidur berkualitas.';
        } elseif ($filterType === 'search' && $filterValue) {
            $filterDescription = 'Hasil pencarian produk IMG untuk "' . e($filterValue) . '". Temukan kasur dan perlengkapan tidur yang sesuai kebutuhan Anda.';
        } else {
            $filterDescription = 'Katalog lengkap produk IMG dengan pilihan kasur, springbed, bantal, dan aksesori tidur premium.';
        }

        $breadcrumbItems = [
            [
                '@type' => 'ListItem',
                'position' => 1,
                'name' => 'Home',
                'item' => route('home'),
            ],
            [
                '@type' => 'ListItem',
                'position' => 2,
                'name' => $filterName,
            ],
        ];

        $productCollectionSchema = [
            '@context' => 'https://schema.org',
            '@type' => 'CollectionPage',
            '@id' => request()->fullUrl(),
            'name' => $title,
            'description' => $filterDescription,
            'url' => request()->fullUrl(),
            'isPartOf' => [
                '@type' => 'WebSite',
                'name' => 'IMG International Mattress Gallery',
                'url' => route('home'),
            ],
            'mainEntity' => [
                '@type' => 'ItemList',
                'name' => $title,
                'numberOfItems' => $products->total(),
                'itemListElement' => $productItems,
            ],
        ];

        $productBreadcrumbSchema = [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => $breadcrumbItems,
        ];
    @endphp

    @push('jsonld')
        <script type="application/ld+json">
            @json($productCollectionSchema)
        </script>
        <script type="application/ld+json">
            @json($productBreadcrumbSchema)
        </script>
    @endpush

    <!-- Category / Brand Banner -->
    @php
        $targetBannerObj = null;
        $sliderBanners = collect();

        if ($filterType === 'brand' && isset($brand)) {
            $targetBannerObj = $brand;
        } elseif ($filterType === 'category' && isset($category)) {
            $targetBannerObj = null; // $category; 
        }

        $sliderImages = $sliderBanners->flatMap(function($b) {
            if ($b->content_type == 2) {
                return [[ 'is_embed' => true, 'web' => $b->embed_web_content, 'mobile' => $b->embed_mobile_content ?: $b->embed_web_content, 'link' => $b->link_url, 'title' => $b->title ]];
            } else {
                if ($b->images->isNotEmpty()) {
                    return $b->images->map(fn($img) => [
                        'is_embed' => false,
                        'web' => $img->image_web_url,
                        'mobile' => $img->image_mobile_url ?: $img->image_web_url,
                        'link' => $img->link_url ?: $b->link_url,
                        'title' => $b->title
                    ]);
                } else {
                    return [[
                        'is_embed' => false,
                        'web' => $b->image_web_url,
                        'mobile' => $b->image_mobile_url ?: $b->image_web_url,
                        'link' => $b->link_url,
                        'title' => $b->title
                    ]];
                }
            }
        })->filter(fn($img) => !empty($img['web']) || !empty($img['is_embed']))->values();
    @endphp

    @php
        $selectedCategorySlugs = $filters['categories'] ?? [];
        $selectedBrandSlugs = $filters['brands'] ?? [];
        $selectedTagSlugs = $filters['tags'] ?? [];
        $hasActiveFilters = !empty($selectedCategorySlugs) || !empty($selectedBrandSlugs) || !empty($selectedTagSlugs) || !empty($filters['min_price']) || !empty($filters['max_price']) || !empty($filters['in_stock']) || request()->filled('q');

        $activeChips = [];
        $baseQuery = request()->except(['type', 'value', 'page']);

        // Categories chips
        foreach ($selectedCategorySlugs as $slug) {
            $catObj = $categories->first(fn($c) => $c->slug === $slug) ?? \App\Models\Frontend\ProductsCatalog\ProductCategory::where('slug', $slug)->where('deleted', false)->first();
            $newCats = array_values(array_diff($selectedCategorySlugs, [$slug]));
            $params = $baseQuery;
            if (!empty($newCats)) {
                $params['categories'] = $newCats;
            } else {
                unset($params['categories']);
            }
            $url = route('products.index', $params);
            $activeChips[] = [
                'label' => $catObj ? $catObj->name : str_replace('-', ' ', ucwords($slug)),
                'url' => $url,
            ];
        }

        // Brands chips
        foreach ($selectedBrandSlugs as $slug) {
            $brandObj = $brands->first(fn($b) => $b->slug === $slug) ?? \App\Models\Frontend\ProductsCatalog\Brand::where('slug', $slug)->where('deleted', false)->first();
            $newBrands = array_values(array_diff($selectedBrandSlugs, [$slug]));
            $params = $baseQuery;
            if (!empty($newBrands)) {
                $params['brands'] = $newBrands;
            } else {
                unset($params['brands']);
            }
            $url = route('products.index', $params);
            $activeChips[] = [
                'label' => $brandObj ? $brandObj->name : str_replace('-', ' ', ucwords($slug)),
                'url' => $url,
            ];
        }

        // Price range chip
        if (!empty($filters['min_price']) || !empty($filters['max_price'])) {
            $priceLabel = '';
            if (!empty($filters['min_price']) && !empty($filters['max_price'])) {
                $priceLabel = 'Rp ' . number_format((float)$filters['min_price'], 0, ',', '.') . ' - Rp ' . number_format((float)$filters['max_price'], 0, ',', '.');
            } elseif (!empty($filters['min_price'])) {
                $priceLabel = '>= Rp ' . number_format((float)$filters['min_price'], 0, ',', '.');
            } else {
                $priceLabel = '<= Rp ' . number_format((float)$filters['max_price'], 0, ',', '.');
            }
            $params = $baseQuery;
            unset($params['min_price'], $params['max_price']);
            $url = route('products.index', $params);
            $activeChips[] = [
                'label' => $priceLabel,
                'url' => $url,
            ];
        }

        // Stock chip
        if (!empty($filters['in_stock'])) {
            $params = $baseQuery;
            unset($params['in_stock']);
            $url = route('products.index', $params);
            $activeChips[] = [
                'label' => __('Tersedia'),
                'url' => $url,
            ];
        }

        // Tag chips
        foreach ($selectedTagSlugs as $slug) {
            $tagObj = $tags->first(fn($t) => $t->slug === $slug) ?? \App\Models\Frontend\ProductsCatalog\ProductTag::where('slug', $slug)->where('deleted', false)->first();
            $newTags = array_values(array_diff($selectedTagSlugs, [$slug]));
            $params = $baseQuery;
            if (!empty($newTags)) {
                $params['tags'] = $newTags;
            } else {
                unset($params['tags']);
            }
            $url = route('products.index', $params);
            $activeChips[] = [
                'label' => $tagObj ? $tagObj->name : str_replace('-', ' ', ucwords($slug)),
                'url' => $url,
            ];
        }

        $resetUrl = route('products.index');
    @endphp

    <div class="container mx-auto px-4 md:px-6 pt-4 pb-12 md:pt-6 md:pb-12 min-h-[70vh]" x-data="{ viewMode: localStorage.getItem('productViewMode') || 'grid', isFilterOpen: false }" 
        @product-view-mode.window="viewMode = $event.detail" @open-filter.window="isFilterOpen = true">
        
        <!-- Category / Brand Banner (Only shown if real uploaded banner exists) -->
        @if($sliderImages->isNotEmpty())
            <!-- Dynamic Banner Slider -->
            <section class="w-full mb-8 relative bg-brand-light overflow-hidden rounded-3xl shadow-sm font-sans group" x-data="{ activeSlide: 0, slidesCount: {{ count($sliderImages) }} }" x-init="setInterval(() => activeSlide = (activeSlide + 1) % slidesCount, 6000)">
                <div class="relative w-full h-[180px] sm:h-[250px] md:h-[320px] lg:h-[380px]">
                    @foreach($sliderImages as $index => $img)
                        <div x-show="activeSlide === {{ $index }}" 
                             x-transition:enter="transition ease-out duration-700" 
                             x-transition:enter-start="opacity-0" 
                             x-transition:enter-end="opacity-100" 
                             x-transition:leave="transition ease-in duration-500" 
                             x-transition:leave-start="opacity-100" 
                             x-transition:leave-end="opacity-0"
                             class="absolute inset-0 w-full h-full">
                            @if($img['link'])
                                <a href="{{ $img['link'] }}" class="block w-full h-full">
                            @endif
                            
                            @if($img['is_embed'])
                                <!-- Embed Code / Custom URL Link -->
                                <div class="w-full h-full hidden md:block">
                                    {!! $img['web'] !!}
                                </div>
                                <div class="w-full h-full block md:hidden">
                                    {!! $img['mobile'] !!}
                                </div>
                            @else
                                <!-- Uploaded Image -->
                                <div class="w-full h-full hidden md:block relative">
                                    <img src="{{ cms_asset($img['web']) }}" alt="{{ $img['title'] }}" class="w-full h-full object-cover md:object-center">
                                    @if($img['title'])
                                    <!-- Warm Dark Gradient Overlay for Signature Style -->
                                    <div class="absolute inset-0 bg-gradient-to-r from-brand-dark/80 via-brand-dark/40 to-transparent mix-blend-multiply"></div>
                                    <div class="absolute inset-0 flex items-center justify-start px-12 md:px-20">
                                        <div class="max-w-xl transform translate-y-4 opacity-0 transition-all duration-1000 delay-300 z-10"
                                             x-bind:class="activeSlide === {{ $index }} ? 'translate-y-0 opacity-100' : 'translate-y-4 opacity-0'">
                                            <div class="inline-block px-4 py-1.5 mb-6 border border-brand-gold/50 rounded-full text-brand-gold text-xs font-bold tracking-[0.2em] uppercase backdrop-blur-sm bg-brand-dark/30">
                                                Koleksi Eksklusif
                                            </div>
                                            <h2 class="text-4xl md:text-5xl lg:text-6xl text-white leading-tight mb-6 font-serif">{{ $img['title'] }}</h2>
                                            <p class="text-brand-muted text-lg md:text-xl mb-10 font-light leading-relaxed">Temukan koleksi tidur premium yang dirancang khusus untuk memberikan kualitas istirahat terbaik bagi Anda dan keluarga.</p>
                                            <div class="inline-flex items-center gap-3 px-8 py-4 bg-brand-gold text-white font-bold rounded-full hover:bg-brand-gold-dark hover:shadow-[0_8px_25px_rgba(192,157,107,0.4)] hover:-translate-y-1 transition-all duration-300 cursor-pointer text-sm tracking-wider uppercase">
                                                <span>{{ __('Belanja Sekarang') }}</span>
                                                <i class="fa-solid fa-arrow-right"></i>
                                            </div>
                                        </div>
                                    </div>
                                    @endif
                                </div>
                                <div class="w-full h-full block md:hidden relative">
                                    <img src="{{ cms_asset($img['mobile']) }}" alt="{{ $img['title'] }}" class="w-full h-full object-cover object-center">
                                    @if($img['title'])
                                    <!-- Warm Dark Gradient Overlay -->
                                    <div class="absolute inset-0 bg-gradient-to-t from-brand-dark/90 via-brand-dark/40 to-transparent mix-blend-multiply"></div>
                                    <div class="absolute inset-x-0 bottom-0 p-8 flex justify-start">
                                        <div class="w-full transform translate-y-4 opacity-0 transition-all duration-1000 delay-300 z-10"
                                             x-bind:class="activeSlide === {{ $index }} ? 'translate-y-0 opacity-100' : 'translate-y-4 opacity-0'">
                                            <div class="inline-block px-3 py-1 mb-4 border border-brand-gold/50 rounded-full text-brand-gold text-[10px] font-bold tracking-wider uppercase backdrop-blur-sm bg-brand-dark/30">
                                                Koleksi Eksklusif
                                            </div>
                                            <h2 class="text-3xl text-white leading-tight mb-3 font-serif">{{ $img['title'] }}</h2>
                                            <p class="text-brand-muted text-sm mb-6 font-light line-clamp-2">Koleksi tidur premium untuk kualitas istirahat paripurna.</p>
                                            <div class="inline-flex items-center gap-2 px-8 py-3.5 bg-brand-gold text-white font-bold rounded-full hover:bg-brand-gold-dark shadow-[0_4px_15px_rgba(192,157,107,0.3)] transition-all cursor-pointer text-xs tracking-wider uppercase">
                                                <span>{{ __('Belanja Sekarang') }}</span>
                                                <i class="fa-solid fa-arrow-right"></i>
                                            </div>
                                        </div>
                                    </div>
                                    @endif
                                </div>
                            @endif

                            @if($img['link'])
                                </a>
                            @endif
                        </div>
                    @endforeach
                </div>
                
                <!-- Slide Indicators -->
                @if(count($sliderImages) > 1)
                    <div class="absolute bottom-6 left-1/2 transform -translate-x-1/2 flex gap-3 z-20">
                        @foreach($sliderImages as $index => $img)
                            <button aria-label="Slide {{ $index + 1 }}" @click="activeSlide = {{ $index }}" class="w-3 h-3 rounded-full transition-all duration-500" :class="activeSlide === {{ $index }} ? 'bg-brand-gold w-8' : 'bg-white/60 hover:bg-white'"></button>
                        @endforeach
                    </div>
                @endif            </section>
        @elseif($targetBannerObj && ($targetBannerObj->banner_web || $targetBannerObj->banner_mobile || $targetBannerObj->embed_web || $targetBannerObj->embed_mobile))
            <!-- Fallback Banner (Image or Embed) -->
            <section class="w-full mb-8 relative bg-brand-light rounded-3xl shadow-sm overflow-hidden group">
                @if(($targetBannerObj->banner_type ?? 1) == 2)
                    @if(!empty($targetBannerObj->banner_link))
                        <a href="{{ $targetBannerObj->banner_link }}" class="block w-full">
                    @endif

                        @if($targetBannerObj->embed_web)
                            <img src="{{ $targetBannerObj->embed_web }}" alt="{{ $targetBannerObj->name }}" class="{{ $targetBannerObj->embed_mobile ? 'hidden md:block' : 'block' }} w-full h-auto object-contain transition-transform duration-700 hover:scale-[1.02]">
                        @endif
                        @if($targetBannerObj->embed_mobile)
                            <img src="{{ $targetBannerObj->embed_mobile }}" alt="{{ $targetBannerObj->name }}" class="{{ $targetBannerObj->embed_web ? 'block md:hidden' : 'block' }} w-full h-auto object-contain transition-transform duration-700 hover:scale-[1.02]">
                        @endif

                        @if(!empty($targetBannerObj->banner_link))
                            </a>
                        @endif
                    @else
                        @if(!empty($targetBannerObj->banner_link))
                            <a href="{{ $targetBannerObj->banner_link }}" class="block w-full relative">
                        @endif

                        @if($targetBannerObj->banner_web)
                            <img src="{{ cms_asset($targetBannerObj->banner_web) }}" alt="{{ $targetBannerObj->name }}" class="{{ $targetBannerObj->banner_mobile ? 'hidden md:block' : 'block' }} w-full h-auto object-contain transition-transform duration-700 hover:scale-[1.02]">
                        @endif
                        @if($targetBannerObj->banner_mobile)
                            <img src="{{ cms_asset($targetBannerObj->banner_mobile) }}" alt="{{ $targetBannerObj->name }}" class="{{ $targetBannerObj->banner_web ? 'block md:hidden' : 'block' }} w-full h-auto object-contain transition-transform duration-700 hover:scale-[1.02]">
                        @endif

                        @if(!empty($targetBannerObj->banner_link))
                            </a>
                        @endif
                    @endif
                </div>            </section>
        @endif

        <!-- Listing Controls Bar -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-4 gap-4 border-b border-[#EFEBE4] pb-4 font-sans">
            <div>
                <h1 class="text-xl sm:text-2xl font-extrabold text-brand-dark tracking-tight font-serif leading-tight">
                    {{ $displayTitle }}
                </h1>
                <p class="text-xs text-stone-500 font-medium mt-0.5">
                    {{ __('Menampilkan :count produk pilihan', ['count' => $products->total()]) }}
                </p>
            </div>

            <div class="flex items-center gap-3 font-sans w-full sm:w-auto justify-between sm:justify-end">
                <form method="GET" action="" class="inline-block" id="sort-form">
                    @foreach(request()->except('sort') as $key => $val)
                        @if(is_array($val))
                            @foreach($val as $item)
                                <input type="hidden" name="{{ $key }}[]" value="{{ $item }}">
                            @endforeach
                        @else
                            <input type="hidden" name="{{ $key }}" value="{{ $val }}">
                        @endif
                    @endforeach
                    <select name="sort" onchange="this.form.submit()" class="border border-[#E5DFC9] rounded-xl px-3.5 py-2 text-xs sm:text-sm font-semibold text-brand-dark bg-[#FAF8F5] focus:ring-2 focus:ring-brand-gold/30 focus:border-brand-gold cursor-pointer focus:outline-none shadow-2xs">
                        <option value="best_seller" {{ $sort === 'best_seller' ? 'selected' : '' }}>{{ __('Terlaris') }}</option>
                        <option value="newest" {{ $sort === 'newest' ? 'selected' : '' }}>{{ __('Terbaru') }}</option>
                        <option value="price_asc" {{ $sort === 'price_asc' ? 'selected' : '' }}>{{ __('Harga: Terendah') }}</option>
                        <option value="price_desc" {{ $sort === 'price_desc' ? 'selected' : '' }}>{{ __('Harga: Tertinggi') }}</option>
                    </select>
                </form>
                <button @click="$dispatch('open-filter')" class="flex items-center gap-2 px-4 py-2 border border-[#E5DFC9] rounded-xl text-xs sm:text-sm font-semibold text-brand-dark hover:border-brand-gold transition-colors bg-[#FAF8F5] focus:outline-none shadow-2xs lg:hidden cursor-pointer">
                    <i class="fa-solid fa-filter text-xs text-brand-gold-dark"></i> {{ __('Filter') }}
                </button>
                <div class="flex items-center border border-[#E5DFC9] rounded-xl overflow-hidden bg-[#FAF8F5] shadow-2xs">
                    <button type="button" @click="viewMode = 'grid'; localStorage.setItem('productViewMode', 'grid')" 
                        :class="{'bg-brand-dark text-brand-gold': viewMode === 'grid', 'text-stone-400 hover:text-brand-dark hover:bg-white': viewMode !== 'grid'}" 
                        class="px-3 py-2 focus:outline-none transition-colors cursor-pointer" aria-label="Tampilan grid">
                        <i class="fa-solid fa-border-all text-xs"></i>
                    </button>
                    <button type="button" @click="viewMode = 'list'; localStorage.setItem('productViewMode', 'list')" 
                        :class="{'bg-brand-dark text-brand-gold': viewMode === 'list', 'text-stone-400 hover:text-brand-dark hover:bg-white': viewMode !== 'list'}" 
                        class="px-3 py-2 focus:outline-none transition-colors cursor-pointer" aria-label="Tampilan list">
                        <i class="fa-solid fa-list text-xs"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Tokopedia-Style Active Filter Chips Bar -->
        @if(!empty($activeChips))
            <div class="mb-6 flex items-center gap-2 overflow-x-auto scrollbar-hide py-1.5 snap-x">
                <span class="text-xs font-bold text-stone-500 shrink-0 mr-1 flex items-center gap-1">
                    <i class="fa-solid fa-filter text-[10px] text-brand-gold-dark"></i>
                    <span>{{ __('Filter Aktif:') }}</span>
                </span>
                @foreach($activeChips as $chip)
                    <a 
                        href="{{ $chip['url'] }}" 
                        class="snap-start shrink-0 inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-[#FAF8F5] hover:bg-brand-gold/15 text-brand-dark border border-[#E5DFC9] hover:border-brand-gold text-xs font-semibold transition-all duration-200 shadow-2xs group cursor-pointer"
                        title="{{ __('Hapus filter') }}"
                    >
                        <span>{{ $chip['label'] }}</span>
                        <i class="fa-solid fa-xmark text-[10px] text-stone-400 group-hover:text-red-600 transition-colors"></i>
                    </a>
                @endforeach
                <a 
                    href="{{ $resetUrl }}" 
                    class="snap-start shrink-0 text-xs font-bold text-red-600 hover:text-red-700 underline underline-offset-2 ml-2 transition-colors cursor-pointer"
                >
                    {{ __('Hapus Semua') }}
                </a>
            </div>
        @endif

        <div class="flex flex-col lg:flex-row gap-8">
            <!-- Sidebar Categories (Desktop) -->
            <aside class="hidden lg:block lg:w-64 flex-shrink-0">
                <div class="bg-[#FAF8F5]/90 border border-[#EFEBE4] rounded-3xl p-6 shadow-2xs sticky top-6 mb-6 max-h-[calc(100vh-48px)] overflow-y-auto">
                    @include('frontend.product.sidebar-filters')
                </div>
            </aside>

            <!-- Mobile Filter Drawer (Mobile) -->
            <div 
                x-show="isFilterOpen" 
                x-cloak 
                class="fixed inset-0 z-[100] overflow-hidden font-sans lg:hidden"
                role="dialog" 
                aria-modal="true"
            >
                <div class="absolute inset-0 overflow-hidden">
                    <!-- Overlay -->
                    <div 
                        x-show="isFilterOpen"
                        x-transition:enter="ease-in-out duration-300"
                        x-transition:enter-start="opacity-0"
                        x-transition:enter-end="opacity-100"
                        x-transition:leave="ease-in-out duration-300"
                        x-transition:leave-start="opacity-100"
                        x-transition:leave-end="opacity-0"
                        @click="isFilterOpen = false"
                        class="absolute inset-0 bg-gray-900/40 backdrop-blur-sm transition-opacity"
                    ></div>

                    <!-- Slide Panel -->
                    <div class="fixed inset-y-0 left-0 pr-10 max-w-full flex">
                        <div 
                            x-show="isFilterOpen"
                            x-transition:enter="transform transition ease-in-out duration-300"
                            x-transition:enter-start="-translate-x-full"
                            x-transition:enter-end="translate-x-0"
                            x-transition:leave="transform transition ease-in-out duration-300"
                            x-transition:leave-start="translate-x-0"
                            x-transition:leave-end="-translate-x-full"
                            class="w-screen max-w-xs"
                        >
                            <div class="h-full flex flex-col bg-[#FAF8F5] shadow-2xl overflow-y-scroll">
                                <div class="flex items-center justify-between p-5 border-b border-[#EFEBE4] bg-white">
                                    <h2 class="text-base font-extrabold text-brand-dark flex items-center gap-2">
                                        <i class="fa-solid fa-filter text-brand-gold-dark"></i> {{ __('Filter Produk') }}
                                    </h2>
                                    <button 
                                        @click="isFilterOpen = false" 
                                        class="p-2 text-stone-400 hover:text-brand-dark bg-stone-100 hover:bg-stone-200 rounded-full transition-colors flex items-center justify-center focus:outline-none cursor-pointer"
                                    >
                                        <i class="fa-solid fa-xmark text-xs"></i>
                                    </button>
                                </div>
                                <div class="flex-1 p-5 space-y-6">
                                    <div class="bg-white border border-[#EFEBE4] rounded-2xl p-5 shadow-2xs">
                                        @include('frontend.product.sidebar-filters')
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="flex-1">
                <!-- Products Grid/List -->
                @if($products->count() > 0)
                    <!-- Grid View -->
                    <div x-show="viewMode === 'grid'" class="grid grid-cols-2 md:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4 gap-3 sm:gap-5 lg:gap-6 catalog-products-grid">
                        @foreach($products as $product)
                            @include('frontend.components.product-card-dynamic', ['product' => $product])
                        @endforeach
                    </div>

                    <!-- List View -->
                    <div x-show="viewMode === 'list'" class="flex flex-col gap-4 catalog-products-list" style="display: none;">
                        @foreach($products as $product)
                            @include('frontend.components.product-card-list', ['product' => $product])
                        @endforeach
                    </div>

                    <!-- Pagination / Load More -->
                    @if($filterType === 'category' || $filterType === 'brand')
                        @if($products->hasMorePages())
                            <div class="mt-12 text-center" id="catalog-load-more-container">
                                <button 
                                    type="button"
                                    id="catalog-load-more-btn"
                                    class="group px-8 py-3.5 rounded-full font-bold text-brand-darker bg-white border-2 border-brand-dark shadow-sm transition-all duration-300 hover:bg-brand-dark hover:text-white hover:border-brand-dark hover:shadow-xl focus:outline-none"
                                    data-next-page-url="{{ $products->nextPageUrl() }}"
                                >
                                    {!! __('Muat Lebih Banyak') !!} <span class="group-hover:translate-x-1 transition-transform inline-block">&rarr;</span>
                                </button>
                            </div>
                        @endif
                    @else
                        <div class="mt-10">
                            {{ $products->withQueryString()->links('frontend.components.pagination') }}
                        </div>
                    @endif
                @else
                    <div class="bg-white border border-brand-muted rounded-2xl p-12 text-center shadow-sm font-sans">
                        <div class="w-20 h-20 bg-brand-light rounded-full flex items-center justify-center text-brand-gold mx-auto mb-4">
                            <i class="fa-solid fa-border-all w-10 h-10"></i>
                        </div>
                        <h2 class="text-xl font-bold text-brand-dark mb-2">{{ __('Produk Tidak Ditemukan') }}</h2>
                        <p class="text-gray-500 max-w-md mx-auto">
                            {{ __('Maaf, kami belum memiliki produk untuk :type ":value". Silakan lihat pilihan lain.', ['type' => $filterType === 'brand' ? __('brand') : ($filterType === 'category' ? __('kategori') : __('pencarian')), 'value' => $filterValue]) }}
                        </p>
                        <div class="mt-6">
                            <a href="{{ route('home') }}" class="inline-block px-6 py-2.5 bg-brand-dark text-brand-gold rounded-xl font-bold hover:bg-brand-darker transition-colors">
                                {{ __('Kembali ke Home') }}
                            </a>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection