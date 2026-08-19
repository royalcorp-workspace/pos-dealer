@extends('frontend.layouts.app')

@php
    $title = __('Semua Produk');
    if ($filterType === 'brand' && $filterValue) {
        $brand = $brands->first(fn($b) => $b->slug === $filterValue);
        $title = $brand ? __('Brand') . ': ' . $brand->name : __('Brand') . ': ' . $filterValue;
    } elseif ($filterType === 'category' && $filterValue) {
        $category = $categories->first(fn($c) => $c->slug === $filterValue);
        $title = $category ? __('Kategori') . ': ' . $category->name : __('Kategori') . ': ' . $filterValue;
    } elseif ($filterType === 'search' && $filterValue) {
        $title = __('Pencarian') . ': "' . $filterValue . '"';
    }
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
            $sliderBanners = \App\Models\Frontend\Banner::with('images')->where('type', 3)->where('target_id', $brand->id)->where('is_active', true)->orderBy('sort_order')->get();
        } elseif ($filterType === 'category' && isset($category)) {
            $targetBannerObj = $category;
            $sliderBanners = \App\Models\Frontend\Banner::with('images')->where('type', 4)->where('target_id', $category->id)->where('is_active', true)->orderBy('sort_order')->get();
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

    @push('jsonld')
        <script type="application/ld+json">
            @json($productCollectionSchema)
        </script>
        <script type="application/ld+json">
            @json($productBreadcrumbSchema)
        </script>
    @endpush

    <div class="container mx-auto px-4 md:px-6 pt-4 pb-12 md:pt-8 md:pb-12 min-h-[70vh]" x-data="{ viewMode: localStorage.getItem('productViewMode') || 'grid', isFilterOpen: false }" 
        @product-view-mode.window="viewMode = $event.detail" @open-filter.window="isFilterOpen = true">
        
        <!-- Category / Brand Banner -->
        @if($sliderImages->isNotEmpty())
            <!-- Dynamic Banner Slider -->
            <section class="w-full mb-8 relative bg-brand-light overflow-hidden rounded-lg shadow-sm font-sans group" x-data="{ activeSlide: 0, slidesCount: {{ count($sliderImages) }} }" x-init="setInterval(() => activeSlide = (activeSlide + 1) % slidesCount, 6000)">
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
                                <div class="w-full h-full hidden md:block">{!! $img['web'] !!}</div>
                                <div class="w-full h-full block md:hidden">{!! $img['mobile'] !!}</div>
                            @else
                                <div class="w-full h-full hidden md:block">
                                    <img src="{{ cms_asset($img['web']) }}" alt="{{ $img['title'] }}" class="w-full h-full object-cover md:object-center">
                                </div>
                                <div class="w-full h-full block md:hidden">
                                    <img src="{{ cms_asset($img['mobile']) }}" alt="{{ $img['title'] }}" class="w-full h-full object-cover object-center">
                                </div>
                            @endif

                            @if($img['link'])
                                </a>
                            @endif
                        </div>
                    @endforeach
                </div>
                
                @if(count($sliderImages) > 1)
                    <div class="absolute bottom-4 left-1/2 transform -translate-x-1/2 flex gap-2 z-20">
                        @foreach($sliderImages as $index => $img)
                            <button @click="activeSlide = {{ $index }}" class="w-2.5 h-2.5 rounded-full transition-all duration-500" :class="activeSlide === {{ $index }} ? 'bg-brand-gold w-6' : 'bg-white/60 hover:bg-white'"></button>
                        @endforeach
                    </div>
                @endif
            </section>
        @elseif($targetBannerObj && ($targetBannerObj->banner_web || $targetBannerObj->banner_mobile || $targetBannerObj->embed_web || $targetBannerObj->embed_mobile))
            <!-- Fallback Banner (Image or Embed) -->
            <section class="w-full mb-8 relative bg-brand-light rounded-lg shadow-sm overflow-hidden group">
                @if(($targetBannerObj->banner_type ?? 1) == 2)
                    @if(!empty($targetBannerObj->banner_link))
                        <a href="{{ $targetBannerObj->banner_link }}" class="block w-full">
                    @endif

                    @if($targetBannerObj->embed_web)
                        <img src="{{ $targetBannerObj->embed_web }}" alt="{{ $targetBannerObj->name }}" class="{{ $targetBannerObj->embed_mobile ? 'hidden md:block' : 'block' }} w-full h-auto object-contain group-hover:scale-[1.02] transition-transform duration-500">
                    @endif
                    @if($targetBannerObj->embed_mobile)
                        <img src="{{ $targetBannerObj->embed_mobile }}" alt="{{ $targetBannerObj->name }}" class="{{ $targetBannerObj->embed_web ? 'block md:hidden' : 'block' }} w-full h-auto object-contain group-hover:scale-[1.02] transition-transform duration-500">
                    @endif

                    @if(!empty($targetBannerObj->banner_link))
                        </a>
                    @endif
                @else
                    @if(!empty($targetBannerObj->banner_link))
                        <a href="{{ $targetBannerObj->banner_link }}" class="block w-full">
                    @endif

                    @if($targetBannerObj->banner_web)
                        <img src="{{ cms_asset($targetBannerObj->banner_web) }}" alt="{{ $targetBannerObj->name }}" class="{{ $targetBannerObj->banner_mobile ? 'hidden md:block' : 'block' }} w-full h-auto object-contain group-hover:scale-[1.02] transition-transform duration-500">
                    @endif
                    @if($targetBannerObj->banner_mobile)
                        <img src="{{ cms_asset($targetBannerObj->banner_mobile) }}" alt="{{ $targetBannerObj->name }}" class="{{ $targetBannerObj->banner_web ? 'block md:hidden' : 'block' }} w-full h-auto object-contain group-hover:scale-[1.02] transition-transform duration-500">
                    @endif

                    @if(!empty($targetBannerObj->banner_link))
                        </a>
                    @endif
                @endif
            </section>
        @endif

        <!-- Listing Header -->
        <div class="flex flex-col md:flex-row justify-between items-start md:items-end mb-6 md:mb-8 gap-4 border-b border-gray-100 pb-4 md:pb-6 font-sans">
            <div>
                <h1 class="text-xl md:text-4xl font-extrabold text-brand-dark tracking-tight font-serif mb-1 md:mb-2 leading-tight">
                    {{ $title }}
                </h1>
            </div>

            <div class="flex items-center gap-3 font-sans">
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
                    <select name="sort" onchange="this.form.submit()" class="border border-brand-muted rounded-lg px-3 py-2 text-sm font-semibold text-brand-dark bg-white focus:ring-brand-gold focus:border-brand-gold cursor-pointer focus:outline-none">
                        <option value="best_seller" {{ $sort === 'best_seller' ? 'selected' : '' }}>{{ __('Best Seller') }}</option>
                        <option value="best_selling" {{ $sort === 'best_selling' ? 'selected' : '' }}>{{ __('Terlaris') }}</option>
                        <option value="price_asc" {{ $sort === 'price_asc' ? 'selected' : '' }}>{{ __('Harga: Terendah') }}</option>
                        <option value="price_desc" {{ $sort === 'price_desc' ? 'selected' : '' }}>{{ __('Harga: Tertinggi') }}</option>
                        <option value="newest" {{ $sort === 'newest' ? 'selected' : '' }}>{{ __('Terbaru') }}</option>
                    </select>
                </form>
                <button @click="$dispatch('open-filter')" class="flex items-center gap-2 px-4 py-2 border border-brand-muted rounded-lg text-sm font-semibold text-brand-dark hover:border-brand-gold transition-colors bg-white focus:outline-none xl:hidden">
                    <i class="fa-solid fa-filter w-4 h-4"></i> {{ __('Filter') }}
                </button>
                <div class="flex items-center border border-brand-muted rounded-lg overflow-hidden bg-white">
                    <button type="button" @click="viewMode = 'grid'; localStorage.setItem('productViewMode', 'grid')" 
                        :class="{'bg-brand-light text-brand-dark': viewMode === 'grid', 'text-gray-400 hover:text-brand-dark hover:bg-gray-50': viewMode !== 'grid'}" 
                        class="px-3 py-2 focus:outline-none transition-colors" aria-label="Tampilan grid">
                        <i class="fa-solid fa-border-all w-4 h-4"></i>
                    </button>
                    <button type="button" @click="viewMode = 'list'; localStorage.setItem('productViewMode', 'list')" 
                        :class="{'bg-brand-light text-brand-dark': viewMode === 'list', 'text-gray-400 hover:text-brand-dark hover:bg-gray-50': viewMode !== 'list'}" 
                        class="px-3 py-2 focus:outline-none transition-colors" aria-label="Tampilan list">
                        <i class="fa-solid fa-list w-4 h-4"></i>
                    </button>
                </div>
            </div>
        </div>

        <div class="flex flex-col lg:flex-row gap-8">
            <!-- Sidebar Categories (Desktop) -->
            <aside class="hidden lg:block lg:w-64 flex-shrink-0">
                <div class="bg-white border border-brand-muted rounded-2xl p-6 shadow-sm sticky top-6 mb-6 max-h-[calc(100vh-48px)] overflow-y-auto">
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
                            <div class="h-full flex flex-col bg-white shadow-2xl overflow-y-scroll">
                                <div class="flex items-center justify-between p-5 border-b border-brand-muted bg-brand-light">
                                    <h2 class="text-lg font-bold text-brand-dark flex items-center gap-2">
                                        <i class="fa-solid fa-filter"></i> {{ __('Filter Produk') }}
                                    </h2>
                                    <button 
                                        @click="isFilterOpen = false" 
                                        class="p-2 text-gray-400 hover:text-brand-dark bg-white hover:bg-brand-muted rounded-full transition-colors flex items-center justify-center focus:outline-none"
                                    >
                                        <i class="fa-solid fa-xmark w-4 h-4"></i>
                                    </button>
                                </div>
                                <div class="flex-1 p-5 space-y-6">
                                    <div class="bg-white border border-brand-muted rounded-2xl p-5 shadow-sm">
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
                    <div x-show="viewMode === 'grid'" class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-6 catalog-products-grid">
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