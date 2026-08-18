@extends('frontend.layouts.app')

@section('title', __('Premium Mattress Gallery And Sleep Accessories') . ' - IMG')
@section('meta_description', __('Destinasi perlengkapan tidur eksklusif di IMG. Temukan koleksi kasur premium, bantal, dan aksesori tidur terbaik dengan garansi resmi, cicilan 0%, serta konsultasi gratis.'))
@section('canonical', route('home'))
@section('og_image', 'https://images.unsplash.com/photo-1540518614846-7eded433c457?auto=format&fit=crop&q=80&w=1200&h=800')

@section('content')
    @php
        $bestSellerItems = $bestsellers->map(function ($product, $index) {
            return [
                '@type' => 'ListItem',
                'position' => $index + 1,
                'url' => route('products.show', $product->slug),
                'name' => $product->name,
            ];
        })->values()->toArray();

        $categoryItems = ($categories ?? collect())->map(function ($category, $index) {
            return [
                '@type' => 'ListItem',
                'position' => $index + 1,
                'url' => route('category.show', $category->slug),
                'name' => $category->name,
            ];
        })->values()->toArray();

        $websiteSchema = [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            '@id' => route('home') . '#website',
            'name' => 'IMG International Mattress Gallery',
            'url' => route('home'),
            'description' => 'Destinasi perlengkapan tidur eksklusif dengan koleksi kasur premium, bantal, dan aksesori tidur berkualitas.',
            'publisher' => [
                '@type' => 'Organization',
                'name' => 'IMG International Mattress Gallery',
                'url' => route('home'),
            ],
        ];

        $homeBreadcrumbSchema = [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => [
                [
                    '@type' => 'ListItem',
                    'position' => 1,
                    'name' => 'Home',
                ],
            ],
        ];

        $bestSellerListSchema = [
            '@context' => 'https://schema.org',
            '@type' => 'ItemList',
            'name' => 'Best Seller Kasur IMG',
            'numberOfItems' => count($bestSellerItems),
            'itemListElement' => $bestSellerItems,
        ];

        $categoryListSchema = [
            '@context' => 'https://schema.org',
            '@type' => 'ItemList',
            'name' => 'Kategori Spesial IMG',
            'numberOfItems' => count($categoryItems),
            'itemListElement' => $categoryItems,
        ];

        $featuredValidVariants = $featured ? $featured->variants->where('price', '>', 0) : collect();
        $featuredIsVariable = $featuredValidVariants->isNotEmpty();
        
        $featuredOriginalPrice = $featured ? (($featuredIsVariable ? (float) $featuredValidVariants->min('price') : (float) ($featured->base_price ?? 0))) : 0;
        
        $featuredPromo = $featured ? \App\Services\StaticPromoService::forProduct($featured, $featuredOriginalPrice) : null;
        $featuredPrice = $featured ? \App\Services\StaticPromoService::discountedPrice($featuredOriginalPrice, $featuredPromo) : 0;
        
        $featuredOriginalMaxPrice = $featured && $featuredIsVariable && $featuredValidVariants->max('price') ? (float) $featuredValidVariants->max('price') : $featuredOriginalPrice;
        $featuredPriceMax = $featured ? \App\Services\StaticPromoService::discountedPrice($featuredOriginalMaxPrice, $featuredPromo) : 0;
        
        $featuredHasPriceRange = $featured && $featuredIsVariable && $featuredValidVariants->min('price') != $featuredValidVariants->max('price');
    @endphp

    @push('jsonld')
        <script type="application/ld+json">
        @json($websiteSchema)
        </script>
        <script type="application/ld+json">
        @json($homeBreadcrumbSchema)
        </script>
        <script type="application/ld+json">
        @json($bestSellerListSchema)
        </script>
        <script type="application/ld+json">
        @json($categoryListSchema)
        </script>
    @endpush

    <!-- Hero Section -->
    @php
        $sliderImages = collect();
        if(isset($banners) && isset($banners[1]) && count($banners[1]) > 0) {
            $sliderImages = $banners[1]->flatMap(function($b) {
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
                        // Fallback if no images are attached in relation but are directly on the banner
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
        }
    @endphp

    <!-- Hero Section / Dynamic Banner Slider -->
    @if($sliderImages->isNotEmpty())
        <section class="w-full relative bg-brand-light overflow-hidden font-sans group" x-data="{ activeSlide: 0, slidesCount: {{ count($sliderImages) }} }" x-init="setInterval(() => activeSlide = (activeSlide + 1) % slidesCount, 6000)">
            <div class="relative w-full">
                <!-- Invisible placeholders to set the container height to match the first image's exact ratio -->
                @if(!$sliderImages[0]['is_embed'])
                    <img src="{{ cms_asset($sliderImages[0]['mobile']) }}" alt="" aria-hidden="true" class="w-full h-auto invisible block md:hidden">
                    <img src="{{ cms_asset($sliderImages[0]['web']) }}" alt="" aria-hidden="true" class="w-full h-auto invisible hidden md:block">
                @else
                    <!-- Fallback ratio if first slide is embed -->
                    <div class="w-full aspect-[4/3] md:aspect-[3/1]"></div>
                @endif

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
            
            <!-- Slide Indicators -->
            @if(count($sliderImages) > 1)
                <div class="absolute bottom-6 left-1/2 transform -translate-x-1/2 flex gap-3 z-20">
                    @foreach($sliderImages as $index => $img)
                        <button aria-label="Slide {{ $index + 1 }}" @click="activeSlide = {{ $index }}" class="w-3 h-3 rounded-full transition-all duration-500" :class="activeSlide === {{ $index }} ? 'bg-brand-gold w-8' : 'bg-white/60 hover:bg-white'"></button>
                    @endforeach
                </div>
            @endif
        </section>
    @else
        <!-- Fallback Static Hero Section -->
        <section class="w-full relative bg-brand-light overflow-hidden font-sans">
            <div class="container mx-auto px-4 sm:px-6 py-8 sm:py-12 lg:py-20 flex flex-col lg:flex-row items-center gap-8 lg:gap-12 relative z-10">
                <!-- Text Content -->
                <div class="w-full lg:w-1/2 space-y-6 text-center lg:text-left pt-8 lg:pt-0">
                    <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-brand-muted text-brand-dark text-xs font-bold uppercase tracking-wider mb-2 border border-brand-gold/20 hero-badge">
                        <span class="w-2 h-2 rounded-full bg-brand-gold animate-pulse"></span>
                        EXTRA DISCOUNT UP TO 40%
                    </div>
                    
                    <h1 class="text-4xl sm:text-5xl lg:text-6xl font-extrabold text-brand-dark tracking-tight leading-tight font-serif hero-title">
                        {{ __('Tingkatkan') }} <span class="text-transparent bg-clip-text bg-gradient-to-r from-brand-gold to-brand-gold-dark">{{ __('Kualitas Tidur Anda') }}</span> {{ __('Hari Ini.') }}
                    </h1>
                    
                    <p class="text-lg text-brand-dark/80 max-w-xl mx-auto lg:mx-0 font-medium hero-copy">
                        {{ __('Temukan koleksi kasur premium dan perlengkapan tidur terbaik dari brand pilihan untuk istirahat yang lebih maksimal.') }}
                    </p>
                    
                    <div class="flex flex-col sm:flex-row items-center gap-4 pt-4 justify-center lg:justify-start hero-cta">
                        <a href="{{ route('categories') }}" class="w-full sm:w-auto px-8 py-4 bg-brand-dark hover:bg-brand-darker text-white font-bold rounded-full transition-all shadow-lg shadow-brand-dark/20 flex justify-center items-center gap-2 group">
                            {{ __('Belanja Sekarang') }}
                            <svg class="w-5 h-5 group-hover:translate-x-1 transition-transform" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M9 6l6 6-6 6" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </a>
                        <a href="{{ route('promos') }}" class="w-full sm:w-auto px-8 py-4 bg-white hover:bg-brand-light text-brand-dark font-bold rounded-full transition-all shadow-sm border border-brand-gold/30 hover:border-brand-gold text-center">
                            {{ __('Lihat Promo') }}
                        </a>
                    </div>
                </div>

                <!-- Image Grid / Banner -->
                <div class="w-full lg:w-1/2 relative h-[350px] sm:h-[500px] lg:h-[650px] rounded-3xl overflow-hidden shadow-2xl hero-image">
                    <!-- Main Image -->
                    <div class="absolute inset-0 bg-brand-muted">
                        <img 
                            src="https://images.unsplash.com/photo-1540518614846-7eded433c457?auto=format&fit=crop&q=80&w=1200&h=800" 
                            alt="Premium Mattress" 
                            class="w-full h-full object-cover"
                        >
                    </div>
                </div>
            </div>
        </section>
    @endif

    <!-- Running Banner removed as requested -->


    <!-- Feature Highlights -->
    <section class="bg-white border-b border-gray-100 py-8 lg:py-12">
        <div class="container mx-auto px-6">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-6 text-center divide-x divide-gray-100">
                <div class="flex flex-col items-center gap-3 px-4">
                    <div class="w-12 h-12 bg-brand-light rounded-full flex items-center justify-center text-brand-gold-dark mb-1">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" aria-hidden="true">
                                <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.6" fill="none" />
                                <path d="M8.5 12.5l1.8 1.8L15.5 9" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" fill="none" />
                            </svg>
                        </div>
                    <h4 class="font-bold text-brand-dark">{{ __('Garansi Resmi') }}</h4>
                    <p class="text-sm text-gray-500">{{ __('Hingga 15 Tahun') }}</p>
                </div>
                <div class="flex flex-col items-center gap-3 px-4">
                    <div class="w-12 h-12 bg-brand-light rounded-full flex items-center justify-center text-brand-gold-dark mb-1">
                            <i class="fa-solid fa-award w-6 h-6"></i>
                        </div>
                    <h4 class="font-bold text-brand-dark">{{ __('Produk Original') }}</h4>
                    <p class="text-sm text-gray-500">{{ __('100% Produk Asli') }}</p>
                </div>
                <div class="flex flex-col items-center gap-3 px-4">
                    <div class="w-12 h-12 bg-brand-light rounded-full flex items-center justify-center text-brand-gold-dark mb-1">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" aria-hidden="true">
                                <rect x="3" y="3" width="7" height="7" stroke="currentColor" stroke-width="1.6" fill="none" />
                                <rect x="14" y="3" width="7" height="7" stroke="currentColor" stroke-width="1.6" fill="none" />
                                <rect x="3" y="14" width="7" height="7" stroke="currentColor" stroke-width="1.6" fill="none" />
                                <rect x="14" y="14" width="7" height="7" stroke="currentColor" stroke-width="1.6" fill="none" />
                            </svg>
                        </div>
                    <h4 class="font-bold text-brand-dark">{{ __('Cicilan 0%') }}</h4>
                    <p class="text-sm text-gray-500">{{ __('Tanpa Kartu Kredit') }}</p>
                </div>
                <div class="flex flex-col items-center gap-3 px-4">
                    <div class="w-12 h-12 bg-brand-light rounded-full flex items-center justify-center text-brand-gold-dark mb-1">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" aria-hidden="true">
                                <rect x="7" y="2" width="10" height="20" rx="2" stroke="currentColor" stroke-width="1.6" fill="none" />
                                <circle cx="12" cy="18" r="0.8" fill="currentColor" />
                            </svg>
                        </div>
                    <h4 class="font-bold text-brand-dark">{{ __('Gratis Konsultasi') }}</h4>
                    <p class="text-sm text-gray-500">{{ __('Pilih Sesuai Kebutuhan') }}</p>
                </div>
            </div>
        </div>
    </section>

    @php $htmlBlocks = []; ob_start(); @endphp
    <!-- Categories / Quick Filter -->
    <section class="py-16">
        <div class="container mx-auto px-6">
            <div class="flex items-center justify-between mb-8">
                <div>
                    <h2 class="text-2xl font-extrabold text-brand-dark tracking-tight font-serif">{{ __('Kategori Pilihan Untuk Kamu') }}</h2>
                    <p class="text-gray-500 mt-1">{{ __('Jelajahi koleksi terlengkap dari brand terkemuka.') }}</p>
                </div>
            </div>
            
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-4">
                @php
                    $quickCategories = $categories ?? collect();
                @endphp
                @foreach($quickCategories as $index => $cat)
                    <a 
                        href="{{ route('category.show', $cat->slug) }}" 
                        class="flex flex-col items-center justify-center p-6 bg-white border border-brand-muted rounded-2xl hover:border-brand-gold hover:shadow-lg hover:-translate-y-1 transition-all group focus:outline-none"
                    >
                        <div class="w-16 h-16 bg-brand-light rounded-full mb-4 flex items-center justify-center text-brand-gold-dark group-hover:bg-brand-dark group-hover:scale-110 transition-all duration-300">
                            <svg aria-hidden="true" class="w-8 h-8" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 17.27 18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round" fill="none"/></svg>
                        </div>
                        <span class="font-bold text-brand-dark text-center text-sm group-hover:text-brand-gold-dark">{{ $cat->name }}</span>
                        <span class="text-xs text-gray-500 mt-1">{{ $cat->getProductsCountWithChildren() }} Products</span>
                    </a>
                @endforeach
            </div>
        </div>
    </section>

    @php $htmlBlocks['kategori'] = ob_get_clean(); ob_start(); @endphp
    <!-- Pilihan Brand -->
    @if(isset($brands) && $brands->isNotEmpty())
    <section class="py-16 bg-white border-t border-brand-muted/50">
        <div class="container mx-auto px-6">
            <div class="text-center mb-10">
                <h2 class="text-3xl font-extrabold text-brand-dark tracking-tight font-serif">{{ __('Pilihan Brand') }}</h2>
                <p class="text-gray-500 mt-2 text-lg">{{ __('Merek kasur dan perlengkapan tidur pilihan terbaik untuk istirahat Anda.') }}</p>
            </div>
            
            @php
                $pbSection = \Illuminate\Support\Facades\DB::table('homepage_sections')
                    ->where('section_key', 'pilihan_brand')
                    ->orWhere('title', 'like', '%Pilihan Brand%')
                    ->first();
                $pbMeta = ($pbSection && isset($pbSection->meta)) ? (is_string($pbSection->meta) ? json_decode($pbSection->meta, true) : (array)$pbSection->meta) : [];
            @endphp
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 md:gap-8">
                @foreach($brands as $brand)
                    @php
                        $safeName = \Illuminate\Support\Str::slug($brand->name);
                        $customLogo = $pbMeta['brands'][$safeName]['logo'] ?? null;
                        $customBanner = $pbMeta['brands'][$safeName]['banner'] ?? null;
                        
                        $logoUrl = $customLogo ?: ($brand->logo ? cms_asset($brand->logo) : null);
                        $bannerUrl = $customBanner ?: ($brand->banner_web ? cms_asset($brand->banner_web) : ($brand->banner ? cms_asset($brand->banner) : null));
                        if (!$bannerUrl && $logoUrl) {
                            $bannerUrl = $logoUrl;
                        }
                    @endphp
                    <a href="{{ route('brands.show', $brand->slug) }}" class="group relative block w-full h-[220px] md:h-[280px] rounded-3xl overflow-hidden shadow-lg hover:shadow-2xl transition-all duration-500 bg-white">
                        <!-- Banner Image -->
                        @if($bannerUrl)
                        <img src="{{ $bannerUrl }}" alt="{{ $brand->name }}" class="absolute inset-0 w-full h-full object-cover group-hover:scale-110 transition-transform duration-1000">
                        @endif
                        
                        <!-- Overlay Gradient -->
                        <div class="absolute inset-0 bg-gradient-to-t from-brand-dark/90 via-brand-dark/20 to-transparent"></div>
                        
                        <!-- Logo & Text Overlay -->
                        <div class="absolute bottom-0 left-0 right-0 p-6 flex flex-col items-start transform group-hover:-translate-y-2 transition-transform duration-500">
                            <!-- <div class="flex items-center gap-2 text-white/90 text-sm font-semibold group-hover:text-brand-gold transition-colors">
                                Lihat Koleksi Lengkap <i class="fa-solid fa-arrow-right"></i>
                            </div> -->
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    </section>

    @php $htmlBlocks['pilihan_brand'] = ob_get_clean(); ob_start(); @endphp
    <!-- Promo Brand (Tabbed UI) -->
    @php
        $promoSection = \Illuminate\Support\Facades\DB::table('homepage_sections')
            ->where('section_key', 'promo_brand')
            ->orWhere('title', 'like', '%Promo%')
            ->first();
        $promoMeta = ($promoSection && isset($promoSection->meta)) ? (is_string($promoSection->meta) ? json_decode($promoSection->meta, true) : (array)$promoSection->meta) : [];
    @endphp
    <section class="py-16 bg-gray-50/50 border-t border-gray-100 overflow-hidden">
        <div class="container mx-auto px-4 md:px-6" x-data="{ activePromoTab: '{{ $brands->first()->id ?? '' }}' }">
            <div class="flex flex-col md:flex-row items-center justify-between gap-6 mb-8">
                <div class="text-center md:text-left">
                    <h2 class="text-3xl font-extrabold text-brand-dark tracking-tight font-serif flex items-center justify-center md:justify-start gap-2">
                        {{ __('Promo Brand Spesial') }} <i class="fa-solid fa-tags text-brand-gold text-2xl"></i>
                    </h2>
                    <p class="text-gray-500 mt-2 text-base md:text-lg">{{ __('Penawaran eksklusif dan diskon besar dari merek favorit Anda.') }}</p>
                </div>
            </div>

            <!-- Tab Headers (Scrollable on Mobile) -->
            <div class="flex overflow-x-auto scrollbar-hide gap-3 pb-2 mb-8 snap-x snap-mandatory">
                @foreach($brands as $brand)
                <button 
                    @click="activePromoTab = '{{ $brand->id }}'"
                    class="snap-start shrink-0 px-6 py-2.5 md:py-3 rounded-full font-bold text-sm border-2 transition-all duration-300 focus:outline-none"
                    :class="activePromoTab === '{{ $brand->id }}' ? 'bg-brand-dark border-brand-dark text-white shadow-lg scale-105' : 'bg-white border-transparent text-gray-500 hover:bg-brand-light hover:text-brand-dark'"
                >
                    {{ $brand->name }}
                </button>
                @endforeach
            </div>

            <!-- Tab Contents -->
            <div class="relative w-full bg-white rounded-[2rem] shadow-xl border border-gray-100 overflow-hidden">
                @foreach($brands as $brand)
                <div 
                    x-show="activePromoTab === '{{ $brand->id }}'"
                    x-transition:enter="transition ease-out duration-700"
                    x-transition:enter-start="opacity-0 translate-y-8 md:translate-y-0 md:translate-x-8"
                    x-transition:enter-end="opacity-100 translate-y-0 md:translate-x-0"
                    style="display: none;"
                    class="w-full"
                >
                    <div class="flex flex-col md:flex-row w-full h-full p-4 md:p-6 lg:p-8 gap-6 md:gap-8 bg-brand-light/20">
                        
                        <!-- 3 Promo Products (Right) -->
                        <div class="w-full md:w-2/3 order-2 md:order-2 overflow-hidden">
                            <div class="flex items-center justify-between mb-5">
                                <h3 class="font-bold text-brand-dark text-lg md:text-2xl font-serif">{{ __('Koleksi Promo') }} {{ $brand->name }}</h3>
                            </div>
                            
                            @if(isset($brand->top_promo_products) && $brand->top_promo_products->isNotEmpty())
                                <div class="flex overflow-x-auto snap-x snap-mandatory scrollbar-hide gap-3 md:gap-4 pb-4 -mx-4 px-4 md:mx-0 md:px-0">
                                    @foreach($brand->top_promo_products as $product)
                                        <div class="snap-start shrink-0 w-[42vw] sm:w-[220px] md:w-[calc(50%-0.5rem)] lg:w-[calc(33.333%-0.75rem)]">
                                            @include('frontend.components.product-card-dynamic', ['product' => $product])
                                        </div>
                                    @endforeach
                                    
                                    <!-- Button Lihat Semua at the end -->
                                    <div class="snap-start shrink-0 w-[30vw] sm:w-[150px] flex items-center justify-center">
                                        <a href="{{ route('brands.show', $brand->slug) }}" class="flex flex-col items-center justify-center gap-3 text-brand-gold-dark hover:text-brand-dark transition-colors group h-full w-full bg-white/50 rounded-2xl border border-dashed border-brand-gold/30 hover:border-brand-dark hover:bg-white">
                                            <div class="w-12 h-12 rounded-full border-2 border-current flex items-center justify-center group-hover:scale-110 transition-transform shadow-sm">
                                                <i class="fa-solid fa-arrow-right"></i>
                                            </div>
                                            <span class="font-bold text-sm text-center">{!! __('Lihat<br>Semua') !!}</span>
                                        </a>
                                    </div>
                                </div>
                            @else
                                <div class="w-full h-full min-h-[250px] flex flex-col items-center justify-center text-gray-400 bg-white rounded-2xl border border-dashed border-gray-200 shadow-sm">
                                    <i class="fa-solid fa-box-open text-4xl mb-3 text-gray-300"></i>
                                    <p class="text-sm font-medium">{{ __('Belum ada produk promo.') }}</p>
                                </div>
                            @endif
                        </div>
                        
                        <!-- Promo Banner Image (Left) -->
                        @php
                            $safeName = \Illuminate\Support\Str::slug($brand->name);
                            $customLogo = $promoMeta['brands'][$safeName]['logo'] ?? null;
                            $customBanner = $promoMeta['brands'][$safeName]['banner'] ?? null;
                            
                            $logoUrl = $customLogo ?: ($brand->logo ? cms_asset($brand->logo) : null);
                            $bannerUrl = $customBanner ?: ($brand->banner_web ? cms_asset($brand->banner_web) : ($brand->banner ? cms_asset($brand->banner) : null));
                            if (!$bannerUrl && $logoUrl) {
                                $bannerUrl = $logoUrl;
                            }
                        @endphp
                        <div class="w-full md:w-1/3 relative h-[200px] sm:h-[300px] md:h-auto min-h-[300px] md:min-h-[400px] order-1 md:order-1 rounded-3xl overflow-hidden group shadow-lg flex-shrink-0 bg-brand-dark">
                            <!-- Banner Image -->
                            @if($bannerUrl)
                                <img src="{{ $bannerUrl }}" class="absolute inset-0 w-full h-full object-cover transition-transform duration-1000 group-hover:scale-110" alt="Promo {{ $brand->name }}">
                            @endif
                            
                            <!-- Overlay Gradient -->
                            <div class="absolute inset-0 bg-gradient-to-t from-brand-dark/95 via-brand-dark/40 to-transparent"></div>
                            
                            <!-- Banner Text & Logo -->
                            <div class="absolute inset-0 p-6 flex flex-col justify-end text-center items-center">
                                <h3 class="text-white font-extrabold text-2xl md:text-3xl font-serif leading-tight drop-shadow-md">
                                    {!! __('Diskon Ekstra<br>Terbatas') !!}
                                </h3>
                                <p class="text-white/80 text-sm mt-3 font-medium">{{ __('Dapatkan kualitas tidur premium dengan harga terbaik bulan ini.') }}</p>
                                <a href="{{ route('brands.show', $brand->slug) }}" class="mt-5 w-fit px-6 py-2.5 bg-brand-gold text-white rounded-full font-bold text-sm hover:bg-brand-gold-dark transition-colors shadow-lg">
                                    {{ __('Beli Sekarang') }}
                                </a>
                            </div>
                        </div>
                        
                    </div>
                </div>
                @endforeach
            </div>
            
        </div>
    </section>
    @endif

    @php $htmlBlocks['promo_brand'] = ob_get_clean(); ob_start(); @endphp
    <!-- Best Seller -->
    <section class="py-16 bg-brand-light/20 border-t border-brand-muted/50">
        <div class="container mx-auto px-6">
            <div class="flex flex-col md:flex-row items-end justify-between mb-10 gap-4">
                <div>
                    <h2 class="text-3xl font-extrabold text-brand-dark tracking-tight flex items-center gap-2 font-serif">
                        {{ __('Product Best Seller') }} <i class="fa-solid fa-award"></i>
                    </h2>
                    <p class="text-gray-500 mt-2 text-lg">{{ __('Produk paling laris dengan rating tertinggi minggu ini.') }}</p>
                </div>
                <a href="{{ route('categories') }}" class="font-bold text-brand-dark hover:text-brand-gold-dark transition-colors flex items-center gap-1 group">
                    {{ __('Lihat Semua') }} <i class="fa-solid fa-chevron-right w-4 h-4 group-hover:translate-x-1 transition-transform"></i>
                </a>
            </div>
            
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-6">
                @foreach($bestsellers as $product)
                    @include('frontend.components.product-card-dynamic', ['product' => $product])
                @endforeach
            </div>
        </div>
    </section>

    @php $htmlBlocks['best_seller'] = ob_get_clean(); ob_start(); @endphp
    @if($featured && $featured->slug)
    <!-- Special Spotlight (Featured Product) -->
    <section class="py-20 bg-brand-dark text-white relative overflow-hidden">
        <div class="absolute top-0 right-0 w-[500px] h-[500px] bg-brand-darker rounded-full blur-3xl -translate-y-1/2 translate-x-1/3 opacity-50"></div>
        
        <div class="container mx-auto px-6 relative z-10">
            <div class="text-center max-w-2xl mx-auto mb-12">
                <h2 class="text-3xl lg:text-4xl font-extrabold tracking-tight mb-4 font-serif text-brand-gold">{{ __('Spesial Buat Kamu Hari Ini') }}</h2>
                <p class="text-brand-light/90 text-lg">{{ __('Rekomendasi khusus berdasarkan preferensi dan tren pencarian terbaik untuk kenyamanan tidur Anda.') }}</p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 items-center max-w-5xl mx-auto bg-white/5 backdrop-blur border border-brand-gold/20 rounded-3xl p-6 lg:p-10 shadow-2xl">
                <div class="aspect-[4/3] rounded-2xl overflow-hidden shadow-2xl relative group border border-brand-gold/20">
                    <img 
                        src="{{ $featured->thumbnail_url ?: asset('images/Precise.jpg') }}" 
                        alt="Special Product" 
                        decoding="async"
                        class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700" 
                    />
                    <div class="absolute inset-0 bg-gradient-to-t from-gray-900/60 to-transparent"></div>
                    <div class="absolute bottom-6 left-6 flex gap-2">
                        <span class="bg-brand-gold text-brand-dark text-xs font-bold px-3 py-1.5 rounded-full uppercase tracking-wider">{{ __('Top Pick') }}</span>
                    </div>
                </div>
                
                <div class="space-y-6">
                    <div class="flex items-center gap-1.5 text-brand-gold">
                        <svg class="w-5 h-5 fill-current" viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg"><path d="M12 17.27 18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg>
                        <svg class="w-5 h-5 fill-current" viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg"><path d="M12 17.27 18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg>
                        <svg class="w-5 h-5 fill-current" viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg"><path d="M12 17.27 18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg>
                        <svg class="w-5 h-5 fill-current" viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg"><path d="M12 17.27 18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg>
                        <span class="text-brand-light/80 ml-2 text-sm">{{ __('(128 Ulasan)') }}</span>
                    </div>
                    
                    <h3 class="text-2xl lg:text-3xl font-bold leading-tight text-white">{{ $featured->name ?? 'No Product' }}</h3>
                    <p class="text-brand-light/70">{{ $featured->short_description ?? $featured->description ?? '' }}</p>
                    
                    <div class="pt-4 flex items-center gap-6 border-t border-brand-gold/20">
                        <div class="flex flex-col">
                            @if($featuredPromo)
                                <span class="text-sm text-brand-light/70 line-through">
                                    Rp {{ number_format($featuredOriginalPrice, 0, ',', '.') }}
                                    @if($featuredHasPriceRange) - Rp {{ number_format($featuredOriginalMaxPrice, 0, ',', '.') }} @endif
                                </span>
                                <span class="text-sm font-bold text-red-300">{{ __('Hemat') }} {{ $featuredPromo['label'] }}</span>
                            @endif
                            <span class="text-2xl font-extrabold text-brand-gold">Rp {{ number_format($featuredPrice, 0, ',', '.') }}@if($featuredHasPriceRange) - Rp {{ number_format($featuredPriceMax, 0, ',', '.') }}@endif</span>
                        </div>
                        <a 
                            href="{{ route('products.show', $featured->slug) }}"
                            class="bg-brand-gold text-brand-dark hover:bg-brand-light font-bold px-6 py-3 rounded-full shadow-lg transition-transform active:scale-95 flex items-center gap-2"
                        >
                            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M6 7h12l-1 12H7L6 7Z" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/><path d="M9 7V5a3 3 0 0 1 6 0v2" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
                            {{ __('Pilih Opsi') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>
    @endif

    @php $htmlBlocks['spesial'] = ob_get_clean(); ob_start(); @endphp
    <!-- Promo Brand (Tabbed Products Showcase) -->
    @if(isset($brands) && $brands->isNotEmpty())
    <section class="py-16 bg-white border-t border-brand-muted/50">
        <div class="container mx-auto px-6">
            <div class="text-center mb-10">
                <h2 class="text-3xl font-extrabold text-brand-dark tracking-tight font-serif">{{ __('Promo Brand') }}</h2>
                <p class="text-gray-500 mt-2 text-lg">{{ __('Temukan produk diskon dan promo menarik dari brand-brand andalan.') }}</p>
            </div>

            <!-- Tabs -->
            <div class="flex justify-center mb-10 overflow-x-auto pb-2">
                <div class="inline-flex bg-brand-light/50 p-1.5 rounded-full border border-brand-gold/10">
                    @php $firstTab = true; @endphp
                    @foreach($brands as $brand)
                        @if($brand->products->isNotEmpty())
                            <button 
                                type="button"
                                onclick="switchBrandTab('{{ $brand->slug }}')"
                                id="tab-btn-{{ $brand->slug }}"
                                class="brand-tab-btn px-6 py-2.5 rounded-full font-bold text-sm transition-all duration-300 {{ $firstTab ? 'bg-brand-dark text-white shadow-md' : 'text-brand-dark/70 hover:text-brand-dark hover:bg-white/50' }}"
                            >
                                {{ $brand->name }}
                            </button>
                            @php $firstTab = false; @endphp
                        @endif
                    @endforeach
                </div>
            </div>

            <!-- Tab Content Grid -->
            <div class="brand-tab-contents">
                @php $firstContent = true; @endphp
                @foreach($brands as $brand)
                    @if($brand->products->isNotEmpty())
                        <div 
                            id="tab-content-{{ $brand->slug }}" 
                            class="brand-tab-content-panel {{ $firstContent ? '' : 'hidden' }} transition-opacity duration-300"
                        >
                            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-6">
                                @foreach($brand->products as $product)
                                    @include('frontend.components.product-card-dynamic', ['product' => $product])
                                @endforeach
                            </div>
                        </div>
                        @php $firstContent = false; @endphp
                    @endif
                @endforeach
            </div>
        </div>
    </section>

    <script>
        function switchBrandTab(slug) {
            // Hide all tab content panels
            document.querySelectorAll('.brand-tab-content-panel').forEach(panel => {
                panel.classList.add('hidden');
            });
            
            // Show selected panel
            const targetPanel = document.getElementById('tab-content-' + slug);
            if (targetPanel) {
                targetPanel.classList.remove('hidden');
            }
            
            // Reset all tab button styles
            document.querySelectorAll('.brand-tab-btn').forEach(btn => {
                btn.classList.remove('bg-brand-dark', 'text-white', 'shadow-md');
                btn.classList.add('text-brand-dark/70', 'hover:text-brand-dark', 'hover:bg-white/50');
            });
            
            // Apply active styles to clicked tab
            const activeBtn = document.getElementById('tab-btn-' + slug);
            if (activeBtn) {
                activeBtn.classList.add('bg-brand-dark', 'text-white', 'shadow-md');
                activeBtn.classList.remove('text-brand-dark/70', 'hover:text-brand-dark', 'hover:bg-white/50');
            }
        }
    </script>
    @endif

    @php $htmlBlocks['promo_brand'] .= ob_get_clean(); ob_start(); @endphp
    <!-- Bundle Section -->
    @if(isset($bundles) && $bundles->isNotEmpty())
    <section class="py-16 bg-white border-t border-brand-muted/50">
        <div class="container mx-auto px-6">
            <div class="flex items-center justify-between mb-8">
                <div>
                    <h2 class="text-2xl font-extrabold text-brand-dark tracking-tight font-serif">{{ __('Paket Bundling Hemat') }}</h2>
                    <p class="text-gray-500 mt-1">{{ __('Dapatkan kombinasi produk pilihan dengan harga lebih hemat.') }}</p>
                </div>
                <a href="{{ route('bundling.index') }}" class="font-bold text-brand-dark hover:text-brand-gold-dark transition-colors flex items-center gap-1 group">
                    {{ __('Lihat Semua') }} <i class="fa-solid fa-chevron-right w-4 h-4 group-hover:translate-x-1 transition-transform"></i>
                </a>
            </div>

            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-6">
                @foreach($bundles as $bundle)
                    <div class="bg-white rounded-2xl shadow-sm hover:shadow-xl transition-shadow duration-300 overflow-hidden flex flex-col h-full group">
                        <a href="{{ route('bundling.show', $bundle->slug) }}" class="block">
                            <div class="relative aspect-[4/3] overflow-hidden bg-gray-100">
                                @if($bundle->thumbnail_url)
                                    <img src="{{ $bundle->thumbnail_url }}" alt="{{ $bundle->name }}" class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-105" loading="lazy" />
                                @else
                                    <div class="w-full h-full flex items-center justify-center bg-gray-200">
                                        <span class="text-gray-400">No image</span>
                                    </div>
                                @endif
                                @if($bundle->discount_percent > 0)
                                    <span class="absolute top-3 left-3 bg-red-600 text-white text-[11px] font-bold px-2.5 py-1 rounded-sm shadow-sm uppercase">
                                        {{ __('Diskon') }} {{ $bundle->discount_percent }}%
                                    </span>
                                @endif
                            </div>
                        </a>
                        <div class="p-5 flex-1 flex flex-col">
                            <h3 class="font-bold text-brand-dark text-lg mb-2 line-clamp-2">
                                <a href="{{ route('bundling.show', $bundle->slug) }}">{{ $bundle->name }}</a>
                            </h3>
                            <div class="mt-auto flex items-baseline gap-2">
                                <span class="font-bold text-xl text-red-600">
                                    Rp {{ number_format($bundle->total_price, 0, ',', '.') }}
                                </span>
                                @if($bundle->total_original > $bundle->total_price)
                                    <span class="text-sm text-gray-500 line-through">
                                        Rp {{ number_format($bundle->total_original, 0, ',', '.') }}
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
    @endif

    @php $htmlBlocks['bundling'] = ob_get_clean(); ob_start(); @endphp
    <!-- Product Recommendations -->
    <section class="py-16 bg-brand-light/30 border-t border-brand-muted/50">
        <div class="container mx-auto px-6">
            <div class="text-center mb-12">
                <h2 class="text-3xl font-extrabold text-brand-dark tracking-tight font-serif">{{ __('Rekomendasi Lainnya') }}</h2>
                <p class="text-gray-500 mt-2 text-lg">{{ __('Pilihan aksesori dan kasur populer untuk melengkapi kamar Anda.') }}</p>
            </div>
            
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-6 recommended-products-grid">
                @foreach($recommended as $product)
                    @include('frontend.components.product-card-dynamic', ['product' => $product])
                @endforeach
            </div>
            
            @if($recommendedTotal > 8)
            <div class="mt-12 text-center">
                <button 
                    type="button"
                    class="load-more-btn group px-8 py-3.5 rounded-full font-bold text-brand-darker bg-white border-2 border-brand-dark shadow-sm transition-all duration-300 hover:bg-brand-dark hover:text-white hover:border-brand-dark hover:shadow-xl focus:outline-none"
                    data-route="{{ route('home.load-more') }}"
                    data-offset="8"
                >
                    {!! __('Muat Lebih Banyak') !!} <span class="group-hover:translate-x-1 transition-transform inline-block">&rarr;</span>
                </button>
            </div>
            @endif
        </div>
    </section>

    @php $htmlBlocks['rekomendasi'] = ob_get_clean(); @endphp
    <!-- Dynamic Section Renderer -->
    @php
        $orderedKeys = isset($homepageSections) ? $homepageSections->pluck('section_key')->toArray() : [];
        $defaultKeys = ['kategori', 'pilihan_brand', 'promo_brand', 'best_seller', 'spesial', 'bundling', 'rekomendasi'];
        $finalKeys = array_unique(array_merge($orderedKeys, $defaultKeys));
    @endphp

    @foreach($finalKeys as $sectionKey)
        @php $lowerKey = strtolower($sectionKey); @endphp
        @if((str_contains($lowerKey, 'kategori') || str_contains($lowerKey, 'category')) && isset($htmlBlocks['kategori']))
            {!! $htmlBlocks['kategori'] !!}
            @php unset($htmlBlocks['kategori']); @endphp
        @elseif((str_contains($lowerKey, 'pilihan') || str_contains($lowerKey, 'pilihan brand') || str_contains($lowerKey, 'merek')) && isset($htmlBlocks['pilihan_brand']))
            {!! $htmlBlocks['pilihan_brand'] !!}
            @php unset($htmlBlocks['pilihan_brand']); @endphp
        @elseif(str_contains($lowerKey, 'promo') && isset($htmlBlocks['promo_brand']))
            {!! $htmlBlocks['promo_brand'] !!}
            @php unset($htmlBlocks['promo_brand']); @endphp
        @elseif(str_contains($lowerKey, 'best') && isset($htmlBlocks['best_seller']))
            {!! $htmlBlocks['best_seller'] !!}
            @php unset($htmlBlocks['best_seller']); @endphp
        @elseif((str_contains($lowerKey, 'spesial') || str_contains($lowerKey, 'special') || str_contains($lowerKey, 'sorotan')) && isset($htmlBlocks['spesial']))
            {!! $htmlBlocks['spesial'] !!}
            @php unset($htmlBlocks['spesial']); @endphp
        @elseif((str_contains($lowerKey, 'bundl') || str_contains($lowerKey, 'paket')) && isset($htmlBlocks['bundling']))
            {!! $htmlBlocks['bundling'] !!}
            @php unset($htmlBlocks['bundling']); @endphp
        @elseif((str_contains($lowerKey, 'rekomendasi') || str_contains($lowerKey, 'recommend')) && isset($htmlBlocks['rekomendasi']))
            {!! $htmlBlocks['rekomendasi'] !!}
            @php unset($htmlBlocks['rekomendasi']); @endphp
        @endif
    @endforeach

    <!-- Event Popup Modal -->
    @if(isset($eventPopups) && $eventPopups->isNotEmpty())
        @php
            // Menggabungkan semua popup dari event-event aktif dan ambil popup pertama
            $allPopups = $eventPopups->flatMap->popups->filter();
            $firstPopup = $allPopups->first();
        @endphp
        
        @if($firstPopup)
            <div 
                x-data="{ 
                    showPopup: false,
                    init() {
                        // Cek memori browser agar popup tidak spam (hanya muncul 1 kali per sesi)
                        if (!sessionStorage.getItem('eventPopupShown_{{ $firstPopup->id }}')) {
                            setTimeout(() => {
                                this.showPopup = true;
                            }, 1000); // Muncul setelah 1 detik
                        }
                    },
                    closePopup() {
                        this.showPopup = false;
                        sessionStorage.setItem('eventPopupShown_{{ $firstPopup->id }}', 'true');
                    }
                }"
                x-show="showPopup"
                style="display: none;"
                class="fixed inset-0 z-[100] flex items-center justify-center p-4 sm:p-6"
                role="dialog"
                aria-modal="true"
            >
                <!-- Latar Belakang (Backdrop) -->
                <div 
                    x-show="showPopup"
                    x-transition:enter="transition ease-out duration-300"
                    x-transition:enter-start="opacity-0"
                    x-transition:enter-end="opacity-100"
                    x-transition:leave="transition ease-in duration-200"
                    x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0"
                    class="absolute inset-0 bg-brand-dark/70 backdrop-blur-sm"
                    @click="closePopup()"
                ></div>

                <!-- Konten Pop-up -->
                <div 
                    x-show="showPopup"
                    x-transition:enter="transition ease-out duration-400 delay-100"
                    x-transition:enter-start="opacity-0 translate-y-12 scale-95"
                    x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                    x-transition:leave="transition ease-in duration-200"
                    x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                    x-transition:leave-end="opacity-0 translate-y-8 scale-95"
                    class="relative bg-white rounded-3xl shadow-2xl w-full max-w-lg overflow-hidden border border-brand-gold/30"
                >
                    <!-- Tombol Silang (Close) -->
                    <button 
                        @click="closePopup()" 
                        class="absolute top-4 right-4 z-10 w-8 h-8 flex items-center justify-center rounded-full bg-black/40 text-white hover:bg-black/70 hover:text-brand-gold transition-colors shadow-sm backdrop-blur-md focus:outline-none"
                        aria-label="Tutup popup"
                    >
                        <i class="fa-solid fa-xmark"></i>
                    </button>

                    <!-- Gambar Brosur/Banner -->
                    @if($firstPopup->image_url)
                        @if($firstPopup->link_url)
                            <a href="{{ $firstPopup->link_url }}" class="block w-full h-auto min-h-[300px] sm:min-h-[400px] bg-brand-light relative group">
                        @else
                            <div class="block w-full h-auto min-h-[300px] sm:min-h-[400px] bg-brand-light relative">
                        @endif
                        
                            <img src="{{ cms_asset($firstPopup->image_url) }}" alt="{{ $firstPopup->title }}" class="absolute inset-0 w-full h-full object-cover">
                            
                        @if($firstPopup->link_url)
                            </a>
                        @else
                            </div>
                        @endif
                    @endif

                    @if($firstPopup->title || $firstPopup->button_text)
                        <!-- Teks & Tombol Aksi Bawah -->
                        <div class="p-6 text-center">
                            @if($firstPopup->title)
                                <h3 class="text-xl md:text-2xl font-extrabold text-brand-dark font-serif mb-4">{{ $firstPopup->title }}</h3>
                            @endif
                            
                            @if($firstPopup->link_url && $firstPopup->button_text)
                                <a href="{{ $firstPopup->link_url }}" class="inline-block px-8 py-3 bg-brand-gold text-brand-dark font-bold rounded-full hover:bg-brand-gold-dark transition-colors shadow-lg w-full sm:w-auto transform active:scale-95">
                                    {{ $firstPopup->button_text }}
                                </a>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        @endif
    @endif
@endsection

