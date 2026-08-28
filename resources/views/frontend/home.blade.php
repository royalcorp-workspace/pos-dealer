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

    <!-- Top Promotional Strip (IMG Signature Style) -->
    <!-- <section class="w-full bg-brand-dark relative overflow-hidden font-sans border-b border-brand-gold/20 mt-0">
        
        <div class="absolute inset-0 opacity-10" style="background-image: linear-gradient(to right, #c09d6b 1px, transparent 1px), linear-gradient(to bottom, #c09d6b 1px, transparent 1px); background-size: 80px 80px; transform: rotate(-3deg) scale(1.5);"></div>
        
        <div class="container mx-auto px-4 py-8 relative z-10 flex flex-col md:flex-row items-center justify-between gap-8 md:gap-4">
            
            <div class="flex-1 text-center md:text-left flex flex-col items-center md:items-start">
                <h3 class="text-white text-3xl md:text-4xl font-serif font-extrabold leading-tight mb-2">Diskon hingga 30%*</h3>
                <p class="text-gray-300 text-base mb-4 font-light">Prioritas utama: kualitas tidur terbaik.</p>
                <a href="{{ route('categories') }}" class="inline-block px-8 py-2.5 bg-brand-gold text-white font-bold rounded-full hover:bg-brand-gold-dark hover:shadow-lg hover:-translate-y-0.5 transition-all duration-300 text-sm tracking-wide">Pilih Kasur</a>
            </div>

            <div class="flex-shrink-0 z-20">
                <div class="inline-block px-10 py-3 rounded-full border-2 border-brand-gold bg-brand-light text-brand-dark font-extrabold text-xl md:text-3xl transform -rotate-2 shadow-[0_4px_20px_rgba(192,157,107,0.3)] tracking-tight whitespace-nowrap">
                    SPECIAL PROMO
                </div>
            </div>


            <div class="flex-1 text-center md:text-right flex flex-col items-center md:items-end">
                <h3 class="text-white text-3xl md:text-4xl font-serif font-extrabold leading-tight mb-2">Ekstra Hemat 15%*</h3>
                <p class="text-gray-300 text-base mb-4 font-light">Kenyamanan paripurna dengan Bundling.</p>
                <a href="{{ route('bundling.index') }}" class="inline-block px-8 py-2.5 bg-white text-brand-dark font-bold rounded-full hover:bg-brand-muted hover:shadow-lg hover:-translate-y-0.5 transition-all duration-300 text-sm tracking-wide">Beli Bundling</a>
            </div>
        </div>
    </section> -->

    <!-- Hero Section / Dynamic Banner Slider -->
    @if($sliderImages->isNotEmpty())
        <section class="w-full pt-4 sm:pt-6 pb-2 font-sans">
            <div class="container mx-auto px-4 sm:px-6">
                <div class="relative w-full rounded-2xl sm:rounded-3xl overflow-hidden shadow-lg group" x-data="{ activeSlide: 0, slidesCount: {{ count($sliderImages) }} }" x-init="setInterval(() => activeSlide = (activeSlide + 1) % slidesCount, 6000)">
                    <div class="relative w-full aspect-[16/9] sm:aspect-[21/9] md:aspect-[24/9]">
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
                                    <div class="w-full h-full hidden md:block">
                                        {!! $img['web'] !!}
                                    </div>
                                    <div class="w-full h-full block md:hidden">
                                        {!! $img['mobile'] !!}
                                    </div>
                                @else
                                    <div class="w-full h-full hidden md:block">
                                        <img src="{{ cms_asset($img['web']) }}" alt="{{ $img['title'] }}" class="w-full h-full object-cover object-center">
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
                    
                    <!-- Prev / Next Navigation Arrows -->
                    @if(count($sliderImages) > 1)
                        <button 
                            type="button"
                            aria-label="Previous Slide" 
                            @click="activeSlide = (activeSlide - 1 + slidesCount) % slidesCount" 
                            class="absolute left-3 sm:left-4 top-1/2 -translate-y-1/2 w-9 h-9 sm:w-11 sm:h-11 rounded-full bg-brand-dark/40 hover:bg-brand-dark/80 text-white backdrop-blur-xs border border-white/20 flex items-center justify-center transition-all duration-300 opacity-0 group-hover:opacity-100 focus:opacity-100 z-20 shadow-md hover:scale-105"
                        >
                            <svg class="w-4 h-4 sm:w-5 sm:h-5 -translate-x-0.5" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M15 19l-7-7 7-7" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </button>
                        <button 
                            type="button"
                            aria-label="Next Slide" 
                            @click="activeSlide = (activeSlide + 1) % slidesCount" 
                            class="absolute right-3 sm:right-4 top-1/2 -translate-y-1/2 w-9 h-9 sm:w-11 sm:h-11 rounded-full bg-brand-dark/40 hover:bg-brand-dark/80 text-white backdrop-blur-xs border border-white/20 flex items-center justify-center transition-all duration-300 opacity-0 group-hover:opacity-100 focus:opacity-100 z-20 shadow-md hover:scale-105"
                        >
                            <svg class="w-4 h-4 sm:w-5 sm:h-5 translate-x-0.5" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M9 5l7 7-7 7" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </button>
                    @endif

                    <!-- Slide Indicators -->
                    @if(count($sliderImages) > 1)
                        <div class="absolute bottom-4 left-1/2 transform -translate-x-1/2 flex gap-2 z-20">
                            @foreach($sliderImages as $index => $img)
                                <button aria-label="Slide {{ $index + 1 }}" @click="activeSlide = {{ $index }}" class="h-2 rounded-full transition-all duration-300" :class="activeSlide === {{ $index }} ? 'bg-brand-gold w-6' : 'bg-white/60 w-2 hover:bg-white'"></button>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </section>
    @else
        <!-- Fallback Compact Hero Banner Carousel -->
        <section class="w-full pt-4 sm:pt-6 pb-2 font-sans">
            <div class="container mx-auto px-4 sm:px-6">
                <div class="relative w-full aspect-[16/9] sm:aspect-[21/9] md:aspect-[24/9] rounded-2xl sm:rounded-3xl overflow-hidden shadow-lg bg-brand-dark group" x-data="{ activeSlide: 0 }" x-init="setInterval(() => activeSlide = (activeSlide + 1) % 2, 5000)">
                    <!-- Slide 1 -->
                    <div 
                        x-show="activeSlide === 0" 
                        x-transition:enter="transition ease-out duration-700" 
                        x-transition:enter-start="opacity-0" 
                        x-transition:enter-end="opacity-100" 
                        x-transition:leave="transition ease-in duration-500" 
                        x-transition:leave-start="opacity-100" 
                        x-transition:leave-end="opacity-0"
                        class="absolute inset-0 w-full h-full"
                    >
                        <img 
                            src="https://images.unsplash.com/photo-1540518614846-7eded433c457?auto=format&fit=crop&q=80&w=1600&h=600" 
                            alt="International Mattress Gallery - Luxury Bedding" 
                            class="w-full h-full object-cover object-center"
                        >
                        <div class="absolute inset-0 bg-gradient-to-r from-brand-darker/90 via-brand-dark/40 to-transparent"></div>
                        <div class="absolute inset-0 flex items-center">
                            <div class="container mx-auto px-6 sm:px-12 max-w-xl space-y-3">
                                <span class="inline-block px-2.5 py-0.5 rounded-full bg-brand-gold text-brand-dark text-[11px] font-bold uppercase tracking-wider">
                                    Official Gallery
                                </span>
                                <h2 class="text-2xl sm:text-3xl lg:text-4xl font-extrabold text-white font-serif leading-tight">
                                    {{ __('Koleksi Kasur & Perlengkapan Tidur') }}
                                </h2>
                                <p class="text-brand-light/90 text-xs sm:text-sm font-medium line-clamp-2">
                                    {{ __('Dapatkan pengalaman tidur hotel bintang lima dengan pilihan kasur dan aksesori terlengkap.') }}
                                </p>
                                <div>
                                    <a href="{{ route('categories') }}" class="inline-flex items-center gap-1.5 px-5 py-2.5 bg-brand-gold hover:bg-brand-gold-dark text-brand-dark font-bold text-xs sm:text-sm rounded-full transition-all shadow-md">
                                        <span>{{ __('Jelajahi Koleksi') }}</span>
                                        <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M9 6l6 6-6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Slide 2 -->
                    <div 
                        x-show="activeSlide === 1" 
                        x-transition:enter="transition ease-out duration-700" 
                        x-transition:enter-start="opacity-0" 
                        x-transition:enter-end="opacity-100" 
                        x-transition:leave="transition ease-in duration-500" 
                        x-transition:leave-start="opacity-100" 
                        x-transition:leave-end="opacity-0"
                        style="display: none;"
                        class="absolute inset-0 w-full h-full"
                    >
                        <img 
                            src="https://images.unsplash.com/photo-1505693416388-ac5ce068fe85?auto=format&fit=crop&q=80&w=1600&h=600" 
                            alt="Royal Foam Premium Series" 
                            class="w-full h-full object-cover object-center"
                        >
                        <div class="absolute inset-0 bg-gradient-to-r from-brand-darker/90 via-brand-dark/40 to-transparent"></div>
                        <div class="absolute inset-0 flex items-center">
                            <div class="container mx-auto px-6 sm:px-12 max-w-xl space-y-3">
                                <span class="inline-block px-2.5 py-0.5 rounded-full bg-red-600 text-white text-[11px] font-bold uppercase tracking-wider">
                                    Special Offer
                                </span>
                                <h2 class="text-2xl sm:text-3xl lg:text-4xl font-extrabold text-white font-serif leading-tight">
                                    {{ __('Penawaran Promo & Bundling') }}
                                </h2>
                                <p class="text-brand-light/90 text-xs sm:text-sm font-medium line-clamp-2">
                                    {{ __('Kombinasi set kasur lengkap dengan bonus bantal dan perlengkapan tidur pilihan.') }}
                                </p>
                                <div>
                                    <a href="{{ route('promos') }}" class="inline-flex items-center gap-1.5 px-5 py-2.5 bg-brand-gold hover:bg-brand-gold-dark text-brand-dark font-bold text-xs sm:text-sm rounded-full transition-all shadow-md">
                                        <span>{{ __('Lihat Promo') }}</span>
                                        <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M9 6l6 6-6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Prev / Next Navigation Arrows -->
                    <button 
                        type="button"
                        aria-label="Previous Slide" 
                        @click="activeSlide = (activeSlide - 1 + 2) % 2" 
                        class="absolute left-3 sm:left-4 top-1/2 -translate-y-1/2 w-9 h-9 sm:w-11 sm:h-11 rounded-full bg-brand-dark/40 hover:bg-brand-dark/80 text-white backdrop-blur-xs border border-white/20 flex items-center justify-center transition-all duration-300 opacity-0 group-hover:opacity-100 focus:opacity-100 z-20 shadow-md hover:scale-105"
                    >
                        <svg class="w-4 h-4 sm:w-5 sm:h-5 -translate-x-0.5" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M15 19l-7-7 7-7" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </button>
                    <button 
                        type="button"
                        aria-label="Next Slide" 
                        @click="activeSlide = (activeSlide + 1) % 2" 
                        class="absolute right-3 sm:right-4 top-1/2 -translate-y-1/2 w-9 h-9 sm:w-11 sm:h-11 rounded-full bg-brand-dark/40 hover:bg-brand-dark/80 text-white backdrop-blur-xs border border-white/20 flex items-center justify-center transition-all duration-300 opacity-0 group-hover:opacity-100 focus:opacity-100 z-20 shadow-md hover:scale-105"
                    >
                        <svg class="w-4 h-4 sm:w-5 sm:h-5 translate-x-0.5" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M9 5l7 7-7 7" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </button>

                    <!-- Slider Controls -->
                    <div class="absolute bottom-3 left-1/2 transform -translate-x-1/2 flex gap-2 z-20">
                        <button aria-label="Slide 1" @click="activeSlide = 0" class="h-1.5 rounded-full transition-all duration-300" :class="activeSlide === 0 ? 'bg-brand-gold w-6' : 'bg-white/50 w-1.5 hover:bg-white'"></button>
                        <button aria-label="Slide 2" @click="activeSlide = 1" class="h-1.5 rounded-full transition-all duration-300" :class="activeSlide === 1 ? 'bg-brand-gold w-6' : 'bg-white/50 w-1.5 hover:bg-white'"></button>
                    </div>
                </div>
            </div>
        </section>
    @endif

    <!-- Trust Section (Aligned with Hero Banner Width, Warm Cashmere & Gold) -->
    <section class="py-3 sm:py-4 font-sans">
        <div class="container mx-auto px-4 sm:px-6">
            <div class="w-full rounded-2xl sm:rounded-3xl bg-[#FAF8F5] border border-[#EFEBE4] px-5 py-4 sm:px-8 sm:py-5 lg:px-10 lg:py-5 shadow-2xs">
                <!-- Desktop: Full width flex with equal justify-between distribution & elegant hairlines -->
                <div class="hidden lg:flex items-center justify-between gap-4">
                    <!-- Card 1: Garansi Pabrik -->
                    <div class="flex items-center gap-3.5 bg-transparent group">
                        <div class="w-9 h-9 flex items-center justify-center shrink-0 text-brand-gold-dark group-hover:text-brand-dark group-hover:scale-110 transition-all duration-300">
                            <svg class="w-7 h-7" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M9 12l2 2 4-4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <h4 class="font-bold text-brand-dark text-sm leading-snug group-hover:text-brand-gold-dark transition-colors">{{ __('Garansi Pabrik') }}</h4>
                            <p class="text-xs text-stone-500 font-normal mt-0.5">{{ __('Proteksi s/d 15-20 Thn') }}</p>
                        </div>
                    </div>

                    <div class="w-px h-8 bg-[#E5DFC9]/60"></div>

                    <!-- Card 2: Pengiriman Aman -->
                    <div class="flex items-center gap-3.5 bg-transparent group">
                        <div class="w-9 h-9 flex items-center justify-center shrink-0 text-brand-gold-dark group-hover:text-brand-dark group-hover:scale-110 transition-all duration-300">
                            <svg class="w-7 h-7" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <rect x="1" y="3" width="15" height="13" rx="2" stroke="currentColor" stroke-width="1.8"/>
                                <path d="M16 8h4l3 3v5h-7V8z" stroke="currentColor" stroke-width="1.8"/>
                                <circle cx="5.5" cy="18.5" r="2.5" stroke="currentColor" stroke-width="1.8"/>
                                <circle cx="18.5" cy="18.5" r="2.5" stroke="currentColor" stroke-width="1.8"/>
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <h4 class="font-bold text-brand-dark text-sm leading-snug group-hover:text-brand-gold-dark transition-colors">{{ __('Pengiriman Aman') }}</h4>
                            <p class="text-xs text-stone-500 font-normal mt-0.5">{{ __('Handling profesional') }}</p>
                        </div>
                    </div>

                    <div class="w-px h-8 bg-[#E5DFC9]/60"></div>

                    <!-- Card 3: Cicilan 0% -->
                    <div class="flex items-center gap-3.5 bg-transparent group">
                        <div class="w-9 h-9 flex items-center justify-center shrink-0 text-brand-gold-dark group-hover:text-brand-dark group-hover:scale-110 transition-all duration-300">
                            <svg class="w-7 h-7" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <rect x="2" y="5" width="20" height="14" rx="2" stroke="currentColor" stroke-width="1.8"/>
                                <line x1="2" y1="10" x2="22" y2="10" stroke="currentColor" stroke-width="1.8"/>
                                <path d="M7 15h2" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <h4 class="font-bold text-brand-dark text-sm leading-snug group-hover:text-brand-gold-dark transition-colors">{{ __('Cicilan 0%') }}</h4>
                            <p class="text-xs text-stone-500 font-normal mt-0.5">{{ __('Banyak pilihan bayar') }}</p>
                        </div>
                    </div>

                    <div class="w-px h-8 bg-[#E5DFC9]/60"></div>

                    <!-- Card 4: Pilihan Lengkap -->
                    <div class="flex items-center gap-3.5 bg-transparent group">
                        <div class="w-9 h-9 flex items-center justify-center shrink-0 text-brand-gold-dark group-hover:text-brand-dark group-hover:scale-110 transition-all duration-300">
                            <svg class="w-7 h-7" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <rect x="3" y="3" width="7" height="7" rx="1.5" stroke="currentColor" stroke-width="1.8"/>
                                <rect x="14" y="3" width="7" height="7" rx="1.5" stroke="currentColor" stroke-width="1.8"/>
                                <rect x="3" y="14" width="7" height="7" rx="1.5" stroke="currentColor" stroke-width="1.8"/>
                                <rect x="14" y="14" width="7" height="7" rx="1.5" stroke="currentColor" stroke-width="1.8"/>
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <h4 class="font-bold text-brand-dark text-sm leading-snug group-hover:text-brand-gold-dark transition-colors">{{ __('Pilihan Lengkap') }}</h4>
                            <p class="text-xs text-stone-500 font-normal mt-0.5">{{ __('Brand kasur terbaik') }}</p>
                        </div>
                    </div>
                </div>

                <!-- Mobile / Tablet: Balanced 2-Column Grid -->
                <div class="grid grid-cols-2 gap-4 sm:gap-6 lg:hidden">
                    <!-- Card 1: Garansi Pabrik -->
                    <div class="flex items-center gap-2.5 sm:gap-3 bg-transparent group">
                        <div class="w-7 h-7 flex items-center justify-center shrink-0 text-brand-gold-dark">
                            <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M9 12l2 2 4-4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <h4 class="font-bold text-brand-dark text-xs sm:text-sm leading-snug">{{ __('Garansi Pabrik') }}</h4>
                            <p class="text-[11px] sm:text-xs text-stone-500 font-normal mt-0.5 truncate">{{ __('Proteksi s/d 15-20 Thn') }}</p>
                        </div>
                    </div>

                    <!-- Card 2: Pengiriman Aman -->
                    <div class="flex items-center gap-2.5 sm:gap-3 bg-transparent group">
                        <div class="w-7 h-7 flex items-center justify-center shrink-0 text-brand-gold-dark">
                            <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <rect x="1" y="3" width="15" height="13" rx="2" stroke="currentColor" stroke-width="1.8"/>
                                <path d="M16 8h4l3 3v5h-7V8z" stroke="currentColor" stroke-width="1.8"/>
                                <circle cx="5.5" cy="18.5" r="2.5" stroke="currentColor" stroke-width="1.8"/>
                                <circle cx="18.5" cy="18.5" r="2.5" stroke="currentColor" stroke-width="1.8"/>
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <h4 class="font-bold text-brand-dark text-xs sm:text-sm leading-snug">{{ __('Pengiriman Aman') }}</h4>
                            <p class="text-[11px] sm:text-xs text-stone-500 font-normal mt-0.5 truncate">{{ __('Handling profesional') }}</p>
                        </div>
                    </div>

                    <!-- Card 3: Cicilan 0% -->
                    <div class="flex items-center gap-2.5 sm:gap-3 bg-transparent group">
                        <div class="w-7 h-7 flex items-center justify-center shrink-0 text-brand-gold-dark">
                            <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <rect x="2" y="5" width="20" height="14" rx="2" stroke="currentColor" stroke-width="1.8"/>
                                <line x1="2" y1="10" x2="22" y2="10" stroke="currentColor" stroke-width="1.8"/>
                                <path d="M7 15h2" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <h4 class="font-bold text-brand-dark text-xs sm:text-sm leading-snug">{{ __('Cicilan 0%') }}</h4>
                            <p class="text-[11px] sm:text-xs text-stone-500 font-normal mt-0.5 truncate">{{ __('Banyak pilihan bayar') }}</p>
                        </div>
                    </div>

                    <!-- Card 4: Pilihan Lengkap -->
                    <div class="flex items-center gap-2.5 sm:gap-3 bg-transparent group">
                        <div class="w-7 h-7 flex items-center justify-center shrink-0 text-brand-gold-dark">
                            <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <rect x="3" y="3" width="7" height="7" rx="1.5" stroke="currentColor" stroke-width="1.8"/>
                                <rect x="14" y="3" width="7" height="7" rx="1.5" stroke="currentColor" stroke-width="1.8"/>
                                <rect x="3" y="14" width="7" height="7" rx="1.5" stroke="currentColor" stroke-width="1.8"/>
                                <rect x="14" y="14" width="7" height="7" rx="1.5" stroke="currentColor" stroke-width="1.8"/>
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <h4 class="font-bold text-brand-dark text-xs sm:text-sm leading-snug">{{ __('Pilihan Lengkap') }}</h4>
                            <p class="text-[11px] sm:text-xs text-stone-500 font-normal mt-0.5 truncate">{{ __('Brand kasur terbaik') }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Categories / Casper-Style "Shop by Sleep Needs" -->
    @php ob_start(); @endphp
    <section class="pt-6 pb-9 lg:pt-8 lg:pb-12 bg-[#fcfaf6] border-b border-brand-muted/40 font-sans">
        <div class="container mx-auto px-4 sm:px-6">
            <!-- Pure Minimalist Editorial Heading -->
            <div class="mb-6 lg:mb-8 flex flex-col sm:flex-row sm:items-end justify-between gap-4 border-b border-brand-muted/40 pb-5">
                <div class="space-y-1.5">
                    <div class="inline-flex items-center gap-2">
                        <span class="w-5 h-px bg-brand-gold-dark/60"></span>
                        <span class="text-xs uppercase tracking-[0.25em] text-brand-gold-dark font-bold">{{ __('Koleksi Terlengkap') }}</span>
                    </div>
                    <h2 class="text-2xl sm:text-3xl lg:text-[36px] font-extrabold text-brand-dark tracking-tight font-serif leading-tight">
                        {{ __('Pilih Sesuai Kebutuhan Istirahat') }}
                    </h2>
                </div>
                <div class="hidden sm:flex items-center gap-2 text-xs font-bold text-gray-400 uppercase tracking-widest pb-1 shrink-0">
                    <span class="w-1.5 h-1.5 rounded-full bg-brand-gold"></span>
                    <span>{{ count($categories ?? []) }} {{ __('Kategori Pilihan') }}</span>
                </div>
            </div>
            
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
                @php
                    $quickCategories = $categories ?? collect();
                    $categoryVisuals = [
                        'kasur-spring-bed' => [
                            'image' => 'https://images.unsplash.com/photo-1540518614846-7eded433c457?auto=format&fit=crop&q=80&w=600&h=400',
                            
                            'chips' => ['Pocket Spring', 'Orthopedic', 'Pillow Top'],
                        ],
                        'kasur-busa-foam' => [
                            'image' => 'https://images.unsplash.com/photo-1505693416388-ac5ce068fe85?auto=format&fit=crop&q=80&w=600&h=400',
                            
                            'chips' => ['High Density', 'Anti-Kempes', 'Sanitized'],
                        ],
                        'bantal-guling' => [
                            'image' => 'https://images.unsplash.com/photo-1584100936595-c0654b55a2e2?auto=format&fit=crop&q=80&w=600&h=400',
                            
                            'chips' => ['Microfiber', 'Memory Foam', 'Silikon'],
                        ],
                        'aksesoris-tidur' => [
                            'image' => 'https://images.unsplash.com/photo-1522771739844-6a9f6d5f14af?auto=format&fit=crop&q=80&w=600&h=400',
                            
                            'chips' => ['Matras Topper', 'Sprei Katun', 'Pelindung Kasur'],
                        ],
                    ];
                @endphp
                @foreach($quickCategories as $index => $cat)
                    @php
                        $visual = $categoryVisuals[$cat->slug] ?? [
                            'image' => 'https://images.unsplash.com/photo-1631049307264-da0ec9d70304?auto=format&fit=crop&q=80&w=600&h=400',
                            
                            'chips' => ['Koleksi Pilihan'],
                        ];
                        
                        $catTagline = !empty($cat->tagline) ? $cat->tagline : 'Kenyamanan Tidur Terbaik';
                        $catImage = $visual['image'];
                        if (!empty($cat->banner_web)) {
                            $catImage = cms_asset($cat->banner_web);
                        }
                    @endphp
                    <a 
                        href="{{ route('category.show', $cat->slug) }}" 
                        class="group flex flex-col bg-white rounded-2xl sm:rounded-3xl border border-brand-muted/80 overflow-hidden shadow-xs hover:border-brand-gold hover:shadow-xl hover:-translate-y-1.5 transition-all duration-300 focus:outline-hidden"
                    >
                        <!-- Visual Image Container -->
                        <div class="relative w-full aspect-[4/3] bg-brand-muted overflow-hidden">
                            <img 
                                src="{{ $catImage }}" 
                                alt="{{ $cat->name }}" 
                                class="w-full h-full object-cover group-hover:scale-108 transition-transform duration-700 ease-out"
                                loading="lazy"
                            >
                            <div class="absolute inset-0 bg-gradient-to-t from-brand-dark/50 via-brand-dark/10 to-transparent opacity-60 group-hover:opacity-40 transition-opacity"></div>
                            
                            <!-- Tabular Micro Badge Count -->
                            <span class="absolute top-3 right-3 px-2.5 py-1 rounded-full bg-white/95 backdrop-blur-md text-brand-dark text-[10px] sm:text-[11px] font-bold border border-brand-gold/30 shadow-xs tabular-nums tracking-wide">
                                {{ $cat->getProductsCountWithChildren() }} {{ __('Produk') }}
                            </span>
                        </div>

                        <!-- Card Info & Typeset Details -->
                        <div class="p-4 sm:p-5 flex flex-col justify-between flex-1 gap-2.5">
                            <div>
                                <span class="text-[10px] sm:text-[11px] font-bold text-brand-gold-dark uppercase tracking-[0.15em] block">
                                    {{ $catTagline }}
                                </span>
                                <h3 class="font-bold text-brand-dark text-base sm:text-lg group-hover:text-brand-gold-dark transition-colors mt-0.5 leading-snug font-serif">
                                    {{ $cat->name }}
                                </h3>
                            </div>

                            <!-- Subcategory Chips (Visual Clue) -->
                            <div class="flex flex-wrap gap-1 pt-1 border-t border-gray-100/80">
                                @foreach($visual['chips'] as $chip)
                                    <span class="text-[9px] sm:text-[10px] px-2 py-0.5 rounded-md bg-brand-light/50 text-brand-dark/70 font-medium group-hover:bg-brand-gold/10 group-hover:text-brand-gold-dark transition-colors">
                                        {{ $chip }}
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    </a>

                @endforeach
            </div>
        </div>
    </section>

    @php $htmlBlocks['kategori'] = ob_get_clean(); ob_start(); @endphp
    <!-- Best Seller Showcase (10 Items Horizontal Slide Carousel) -->
    @if(isset($bestsellers) && $bestsellers->isNotEmpty())
    <section class="py-9 lg:py-12 bg-white border-b border-brand-muted/40 font-sans overflow-hidden" x-data="{
        scrollLeft() {
            $refs.bestSellerSlider.scrollBy({ left: -300, behavior: 'smooth' });
        },
        scrollRight() {
            $refs.bestSellerSlider.scrollBy({ left: 300, behavior: 'smooth' });
        }
    }">
        <div class="container mx-auto px-4 sm:px-6">
            <!-- Section Header with Title & Navigation Arrows -->
            <div class="mb-6 lg:mb-8 flex items-end justify-between gap-4 border-b border-brand-muted/40 pb-5">
                <div class="space-y-1.5">
                    <div class="inline-flex items-center gap-2">
                        <span class="w-5 h-px bg-brand-gold-dark/60"></span>
                        <span class="text-xs uppercase tracking-[0.25em] text-brand-gold-dark font-bold">{{ __('Koleksi Terpopuler') }}</span>
                    </div>
                    <h2 class="text-2xl sm:text-3xl lg:text-[36px] font-extrabold text-brand-dark tracking-tight font-serif leading-tight">
                        {{ __('Produk Terlaris') }}
                    </h2>
                </div>

                <!-- Prev / Next Slider Arrows -->
                <div class="flex items-center gap-2">
                    <button 
                        type="button"
                        @click="scrollLeft()"
                        aria-label="Previous Products"
                        class="w-8 h-8 sm:w-9 sm:h-9 rounded-full bg-white hover:bg-brand-dark text-brand-dark hover:text-brand-gold border border-gray-200 hover:border-brand-dark flex items-center justify-center transition-all duration-300 shadow-2xs hover:scale-105 active:scale-95 cursor-pointer"
                    >
                        <i class="fa-solid fa-chevron-left text-xs"></i>
                    </button>
                    <button 
                        type="button"
                        @click="scrollRight()"
                        aria-label="Next Products"
                        class="w-8 h-8 sm:w-9 sm:h-9 rounded-full bg-white hover:bg-brand-dark text-brand-dark hover:text-brand-gold border border-gray-200 hover:border-brand-dark flex items-center justify-center transition-all duration-300 shadow-2xs hover:scale-105 active:scale-95 cursor-pointer"
                    >
                        <i class="fa-solid fa-chevron-right text-xs"></i>
                    </button>
                </div>
            </div>

            <!-- Horizontal Slider (10 Items Snap Slider + End Card) -->
            <div 
                x-ref="bestSellerSlider"
                class="flex gap-4 sm:gap-5 overflow-x-auto scrollbar-none snap-x snap-mandatory scroll-smooth pb-3 pt-1 -mx-4 px-4 sm:mx-0 sm:px-0"
            >
                @foreach($bestsellers as $product)
                    <div class="snap-start shrink-0 w-[230px] sm:w-[260px] lg:w-[280px] flex flex-col h-full">
                        @include('frontend.components.product-card-dynamic', ['product' => $product])
                    </div>
                @endforeach

                <!-- Explore All Card (Next to the last product) -->
                <div class="snap-start shrink-0 w-[200px] sm:w-[230px] lg:w-[250px] flex flex-col self-stretch">
                    <a 
                        href="{{ route('products.index', ['sort' => 'best_seller']) }}" 
                        class="w-full h-full min-h-[320px] rounded-2xl bg-[#FAF8F5] border border-dashed border-brand-gold/40 hover:border-brand-gold hover:bg-brand-gold/10 transition-all duration-300 p-6 flex flex-col items-center justify-center text-center group shadow-2xs hover:shadow-md"
                    >
                        <div class="w-12 h-12 sm:w-14 sm:h-14 rounded-full bg-white text-brand-dark group-hover:bg-brand-dark group-hover:text-brand-gold border border-brand-gold/30 flex items-center justify-center mb-4 transition-all duration-300 shadow-2xs group-hover:scale-110">
                            <i class="fa-solid fa-arrow-right text-sm transition-transform duration-300 group-hover:translate-x-1"></i>
                        </div>
                        <span class="font-serif font-bold text-brand-dark text-base sm:text-lg group-hover:text-brand-darker transition-colors">{{ __('Lihat Semua') }}</span>
                        <span class="text-xs text-stone-500 font-normal mt-1">{{ __('Koleksi Terlaris') }}</span>
                        <span class="mt-4 text-xs font-bold text-brand-gold-dark inline-flex items-center gap-1 group-hover:gap-1.5 transition-all">
                            <span>{{ __('Jelajahi') }}</span>
                            <i class="fa-solid fa-chevron-right text-[10px]"></i>
                        </span>
                    </a>
                </div>
            </div>
        </div>
    </section>
    @endif

    @php $htmlBlocks['best_seller'] = ob_get_clean(); ob_start(); @endphp
    <!-- Brand (Official Brand Logo Strip / Trust Bar) -->
    @if(isset($brands) && $brands->isNotEmpty())
    <section class="py-6 sm:py-8 bg-white border-y border-brand-muted/40 font-sans">
        <div class="container mx-auto px-4 sm:px-6">
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-5 lg:gap-8">
                <!-- Trust Label Left -->
                <div class="flex items-center gap-2.5 shrink-0">
                    <span class="w-2.5 h-2.5 rounded-full bg-brand-gold shadow-xs animate-pulse"></span>
                    <span class="text-xs uppercase tracking-[0.22em] font-extrabold text-brand-dark">{{ __('Official Brand Partners') }}</span>
                    <span class="hidden sm:inline-block text-gray-300">|</span>
                    <span class="hidden sm:inline-block text-xs text-gray-500 font-medium">{{ __('Distributor Resmi Garansi Pabrik') }}</span>
                </div>

                <!-- Brand Vector Logos Strip (Static) -->
                <div class="flex-1 flex flex-wrap items-center justify-start lg:justify-end gap-6 sm:gap-8 py-2 w-full">
                    @foreach($brands as $brand)
                        <a href="{{ route('brands.show', $brand->slug) }}" class="block shrink-0 transition-transform duration-300 hover:scale-105" title="{{ $brand->name }}">
                            @if($brand->logo)
                                <img src="{{ cms_asset($brand->logo) }}" alt="{{ $brand->name }}" class="h-9 sm:h-12 w-auto object-contain grayscale opacity-60 hover:grayscale-0 hover:opacity-100 transition-all duration-300 drop-shadow-sm hover:drop-shadow-md">
                            @else
                                <div class="h-9 sm:h-12 px-4 rounded-xl border border-gray-200 flex items-center justify-center bg-gray-50 transition-colors hover:border-brand-gold hover:bg-brand-light/30">
                                    <span class="font-serif font-bold text-xs text-gray-700 hover:text-brand-dark">{{ $brand->name }}</span>
                                </div>
                            @endif
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
    </section>
    @endif

    @php $htmlBlocks['pilihan_brand'] = ob_get_clean(); ob_start(); @endphp
    <!-- Promo Brand (Tabbed UI) -->
    @php
        $promoSection = \Illuminate\Support\Facades\DB::table('homepage_sections')
            ->where('section_key', 'promo_brand')
            ->orWhere('title', 'like', '%Promo%')
            ->first();
        $promoMeta = ($promoSection && isset($promoSection->meta)) ? (is_string($promoSection->meta) ? json_decode($promoSection->meta, true) : (array)$promoSection->meta) : [];
    @endphp
    @if(isset($brands) && $brands->isNotEmpty())
    <section class="py-9 lg:py-12 bg-[#f8f8fa] border-t border-gray-100 overflow-hidden font-sans">
        <div class="container mx-auto px-4 md:px-6" x-data="{ activePromoTab: '{{ $brands->first()->id ?? '' }}' }">
            <!-- Pure Minimalist Section Header -->
            <div class="flex flex-col md:flex-row md:items-end justify-between gap-6 mb-8 border-b border-brand-muted/40 pb-5">
                <div class="space-y-1.5">
                    <div class="inline-flex items-center gap-2 text-xs uppercase tracking-[0.25em] font-bold text-brand-gold-dark">
                        <span class="w-5 h-px bg-brand-gold-dark/60"></span>
                        <span>{{ __('Penawaran Spesial') }}</span>
                    </div>
                    <h2 class="text-3xl sm:text-4xl lg:text-[40px] font-extrabold text-brand-dark tracking-tight font-serif leading-[1.15]">
                        {{ __('Promo Brand Pilihan') }}
                    </h2>
                </div>

                <!-- Tab Headers (Scrollable on Mobile, Luxury Pills on Desktop) -->
                <div class="flex overflow-x-auto scrollbar-hide gap-2 pb-1 snap-x snap-mandatory">
                    @foreach($brands as $brand)
                    <button 
                        type="button"
                        @click="activePromoTab = '{{ $brand->id }}'"
                        class="snap-start shrink-0 px-5 py-2 sm:px-6 sm:py-2.5 rounded-full font-bold text-xs sm:text-sm border transition-all duration-300 cursor-pointer focus:outline-none"
                        :class="activePromoTab === '{{ $brand->id }}' ? 'bg-brand-dark border-brand-dark text-white shadow-md scale-102' : 'bg-white border-gray-200 text-gray-600 hover:bg-brand-light hover:text-brand-dark hover:border-brand-gold/40'"
                    >
                        {{ $brand->name }}
                    </button>
                    @endforeach
                </div>
            </div>

            <!-- Tab Contents -->
            <div class="relative w-full bg-white rounded-3xl sm:rounded-[2rem] shadow-xl border border-gray-100/80 overflow-hidden">
                @foreach($brands as $brand)
                <div 
                    x-show="activePromoTab === '{{ $brand->id }}'"
                    x-transition:enter="transition ease-out duration-500"
                    x-transition:enter-start="opacity-0 translate-y-4 md:translate-y-0 md:translate-x-4"
                    x-transition:enter-end="opacity-100 translate-y-0 md:translate-x-0"
                    style="display: none;"
                    class="w-full"
                >
                    <div class="flex flex-col md:flex-row w-full h-full p-4 sm:p-6 lg:p-8 gap-6 md:gap-8 bg-[#f8f8fa]">
                        
                        <!-- 3 Promo Products (Right) -->
                        <div class="w-full md:w-2/3 order-2 md:order-2 overflow-hidden">
                            <div class="mb-4">
                                <span class="text-[10px] sm:text-[11px] font-bold text-brand-gold-dark uppercase tracking-widest block">{{ __('Katalog Diskon') }}</span>
                                <h3 class="font-bold text-brand-dark text-lg sm:text-2xl font-serif mt-0.5">{{ $brand->name }} {{ __('Edition') }}</h3>
                            </div>
                            
                            @if(isset($brand->top_promo_products) && $brand->top_promo_products->isNotEmpty())
                                <div class="flex overflow-x-auto snap-x snap-mandatory scrollbar-hide gap-3 md:gap-4 pb-3 -mx-4 px-4 md:mx-0 md:px-0">
                                    @foreach($brand->top_promo_products as $product)
                                        <div class="snap-start shrink-0 w-[68vw] sm:w-[220px] md:w-[calc(50%-0.5rem)] lg:w-[calc(33.333%-0.75rem)] flex flex-col">
                                            @include('frontend.components.product-card-dynamic', ['product' => $product])
                                        </div>
                                    @endforeach
                                    
                                    <!-- Button Lihat Semua at the end -->
                                    <div class="snap-start shrink-0 w-[180px] sm:w-[200px] flex flex-col self-stretch">
                                        <a href="{{ route('brands.show', $brand->slug) }}" class="flex flex-col items-center justify-center text-center group h-full w-full min-h-[240px] bg-white rounded-2xl border border-dashed border-brand-gold/40 hover:border-brand-gold hover:bg-brand-gold/10 transition-all duration-300 shadow-2xs hover:shadow-md p-5">
                                            <div class="w-12 h-12 rounded-full bg-brand-light/80 text-brand-dark group-hover:bg-brand-dark group-hover:text-brand-gold border border-brand-gold/30 flex items-center justify-center mb-3.5 transition-all duration-300 shadow-2xs group-hover:scale-110">
                                                <i class="fa-solid fa-arrow-right text-sm transition-transform duration-300 group-hover:translate-x-1"></i>
                                            </div>
                                            <span class="font-serif font-bold text-brand-dark text-base group-hover:text-brand-darker transition-colors">{{ __('Lihat Semua') }}</span>
                                            <span class="text-xs text-stone-500 font-normal mt-1">{{ __('Koleksi :brand', ['brand' => $brand->name]) }}</span>
                                            <span class="mt-4 text-xs font-bold text-brand-gold-dark inline-flex items-center gap-1 group-hover:gap-1.5 transition-all">
                                                <span>{{ __('Jelajahi') }}</span>
                                                <i class="fa-solid fa-chevron-right text-[10px]"></i>
                                            </span>
                                        </a>
                                    </div>
                                </div>
                            @elseif($brand->products->isNotEmpty())
                                <div class="flex overflow-x-auto snap-x snap-mandatory scrollbar-hide gap-3 md:gap-4 pb-3 -mx-4 px-4 md:mx-0 md:px-0">
                                    @foreach($brand->products->take(3) as $product)
                                        <div class="snap-start shrink-0 w-[68vw] sm:w-[220px] md:w-[calc(50%-0.5rem)] lg:w-[calc(33.333%-0.75rem)] flex flex-col">
                                            @include('frontend.components.product-card-dynamic', ['product' => $product])
                                        </div>
                                    @endforeach
                                    
                                    <div class="snap-start shrink-0 w-[180px] sm:w-[200px] flex flex-col self-stretch">
                                        <a href="{{ route('brands.show', $brand->slug) }}" class="flex flex-col items-center justify-center text-center group h-full w-full min-h-[240px] bg-white rounded-2xl border border-dashed border-brand-gold/40 hover:border-brand-gold hover:bg-brand-gold/10 transition-all duration-300 shadow-2xs hover:shadow-md p-5">
                                            <div class="w-12 h-12 rounded-full bg-brand-light/80 text-brand-dark group-hover:bg-brand-dark group-hover:text-brand-gold border border-brand-gold/30 flex items-center justify-center mb-3.5 transition-all duration-300 shadow-2xs group-hover:scale-110">
                                                <i class="fa-solid fa-arrow-right text-sm transition-transform duration-300 group-hover:translate-x-1"></i>
                                            </div>
                                            <span class="font-serif font-bold text-brand-dark text-base group-hover:text-brand-darker transition-colors">{{ __('Lihat Semua') }}</span>
                                            <span class="text-xs text-stone-500 font-normal mt-1">{{ __('Koleksi :brand', ['brand' => $brand->name]) }}</span>
                                            <span class="mt-4 text-xs font-bold text-brand-gold-dark inline-flex items-center gap-1 group-hover:gap-1.5 transition-all">
                                                <span>{{ __('Jelajahi') }}</span>
                                                <i class="fa-solid fa-chevron-right text-[10px]"></i>
                                            </span>
                                        </a>
                                    </div>
                                </div>
                            @else
                                <div class="w-full h-full min-h-[220px] flex flex-col items-center justify-center text-gray-400 bg-white rounded-2xl border border-dashed border-gray-200 shadow-2xs">
                                    <i class="fa-solid fa-box-open text-4xl mb-2 text-gray-300"></i>
                                    <p class="text-sm font-medium">{{ __('Belum ada produk promo untuk brand ini.') }}</p>
                                </div>
                            @endif
                        </div>
                        
                        <!-- Promo Banner Image (Left) -->
                        @php
                            $safeName = \Illuminate\Support\Str::slug($brand->name);
                            $customBanner = $promoMeta['brands'][$safeName]['banner'] ?? null;
                            $bannerUrl = $customBanner ?: ($brand->banner_web ? cms_asset($brand->banner_web) : ($brand->banner ? cms_asset($brand->banner) : null));
                        @endphp
                        <div class="w-full md:w-1/3 relative h-[220px] sm:h-[300px] md:h-auto min-h-[280px] md:min-h-[360px] order-1 md:order-1 rounded-2xl sm:rounded-3xl overflow-hidden group shadow-lg flex-shrink-0 bg-brand-dark">
                            <!-- Banner Image -->
                            @if($bannerUrl)
                                <img src="{{ $bannerUrl }}" class="absolute inset-0 w-full h-full object-cover transition-transform duration-1000 group-hover:scale-108" alt="Promo {{ $brand->name }}">
                            @endif
                            
                            <!-- Overlay Gradient -->
                            <div class="absolute inset-0 bg-gradient-to-t from-brand-dark/95 via-brand-dark/60 to-brand-dark/10"></div>
                            
                            <!-- Banner Text & CTA -->
                            <div class="absolute inset-0 p-6 sm:p-7 flex flex-col justify-end text-center items-center">
                                <span class="text-xs uppercase tracking-[0.2em] text-brand-gold font-bold mb-1">{{ __('Penawaran Eksklusif') }}</span>
                                <h3 class="text-white font-extrabold text-2xl sm:text-3xl font-serif leading-tight drop-shadow-md">
                                    {{ $brand->name }}
                                </h3>
                                <p class="text-white/80 text-xs sm:text-sm mt-2 font-normal max-w-xs">{{ __('Dapatkan kualitas tidur premium dengan harga terbaik bulan ini.') }}</p>
                                <a href="{{ route('brands.show', $brand->slug) }}" class="mt-4 px-6 py-2.5 bg-brand-gold text-brand-dark hover:bg-white rounded-full font-bold text-xs sm:text-sm transition-all shadow-md active:scale-95">
                                    {{ __('Jelajahi Brand') }} &rarr;
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
    <!-- Product Recommendations (5-Grid Curated Catalog) -->
    <section class="py-9 lg:py-12 bg-[#faf9f6] border-t border-brand-muted/50 font-sans">
        <div class="container mx-auto px-4 sm:px-6">
            <!-- Pure Minimalist Section Header -->
            <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4 mb-8 border-b border-brand-muted/40 pb-5">
                <div class="space-y-1.5">
                    <div class="inline-flex items-center gap-2 text-xs uppercase tracking-[0.25em] font-bold text-brand-gold-dark">
                        <span class="w-5 h-px bg-brand-gold-dark/60"></span>
                        <span>{{ __('Katalog Pilihan') }}</span>
                    </div>
                    <h2 class="text-3xl sm:text-4xl lg:text-[40px] font-extrabold text-brand-dark tracking-tight font-serif leading-[1.15]">
                        {{ __('Rekomendasi Lainnya') }}
                    </h2>
                </div>
                <div class="hidden sm:flex items-center gap-2 text-xs font-bold text-gray-400 uppercase tracking-widest pb-1 shrink-0">
                    <span class="w-1.5 h-1.5 rounded-full bg-brand-gold"></span>
                    <span>{{ __('Koleksi Unggulan Lengkap') }}</span>
                </div>
            </div>
            
            <!-- 5 Columns Product Grid -->
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-3 sm:gap-4 lg:gap-5 recommended-products-grid">
                @foreach($recommended as $product)
                    <div class="flex flex-col h-full">
                        @include('frontend.components.product-card-dynamic', ['product' => $product])
                    </div>
                @endforeach
            </div>
            
            @if($recommendedTotal > 10)
            <div class="mt-10 text-center">
                <button 
                    type="button"
                    class="load-more-btn group px-8 py-3 rounded-full font-bold text-sm text-brand-dark bg-white border border-brand-dark/30 shadow-xs transition-all duration-300 hover:bg-brand-dark hover:text-white hover:border-brand-dark hover:shadow-lg focus:outline-none cursor-pointer active:scale-95"
                    data-route="{{ route('home.load-more') }}"
                    data-offset="10"
                >
                    {!! __('Muat Lebih Banyak Produk') !!} <span class="group-hover:translate-x-1 transition-transform inline-block">&rarr;</span>
                </button>
            </div>
            @endif
        </div>
    </section>
    @php $htmlBlocks['rekomendasi'] = ob_get_clean(); @endphp
    <!-- Dynamic Section Renderer -->
    @php
        $orderedKeys = isset($homepageSections) ? $homepageSections->pluck('section_key')->toArray() : [];
        $defaultKeys = ['kategori', 'best_seller', 'pilihan_brand', 'promo_brand', 'spesial', 'bundling', 'rekomendasi'];
        $finalKeys = array_unique(array_merge($orderedKeys, $defaultKeys));
    @endphp

    @foreach($finalKeys as $sectionKey)
        @php $lowerKey = strtolower($sectionKey); @endphp
        @if((str_contains($lowerKey, 'kategori') || str_contains($lowerKey, 'category')) && isset($htmlBlocks['kategori']))
            {!! $htmlBlocks['kategori'] !!}
            @php unset($htmlBlocks['kategori']); @endphp
        @elseif((str_contains($lowerKey, 'pilihan') || str_contains($lowerKey, 'Brand') || str_contains($lowerKey, 'merek')) && isset($htmlBlocks['pilihan_brand']))
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

    {{-- Render any remaining blocks --}}
    @foreach($htmlBlocks as $remainingHtml)
        {!! $remainingHtml !!}
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

