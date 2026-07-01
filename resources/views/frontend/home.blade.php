@extends('frontend.layouts.app')

@section('title', 'Toko Kasur Dan Perlengkapan Tidur Premium - IMG')
@section('meta_description', 'Toko kasur dan perlengkapan tidur premium di IMG. Temukan springbed, kasur, bantal, dan aksesori tidur terbaik dengan garansi resmi, cicilan 0%, serta konsultasi gratis.')
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
            'description' => 'Toko kasur dan perlengkapan tidur premium dengan koleksi springbed, bantal, dan aksesori tidur berkualitas.',
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

        $featuredPromo = $featured ? \App\Services\StaticPromoService::forProduct($featured) : null;
        $featuredOriginalPrice = $featured ? (($featured->variants->isNotEmpty() ? (float) $featured->variants->min('price') : (float) ($featured->base_price ?? 0))) : 0;
        $featuredPrice = $featured ? \App\Services\StaticPromoService::discountedPrice($featuredOriginalPrice, $featuredPromo) : 0;
        $featuredOriginalMaxPrice = $featured && $featured->variants->isNotEmpty() && $featured->variants->max('price') ? (float) $featured->variants->max('price') : $featuredOriginalPrice;
        $featuredPriceMax = $featured ? \App\Services\StaticPromoService::discountedPrice($featuredOriginalMaxPrice, $featuredPromo) : 0;
        $featuredHasPriceRange = $featured && $featured->variants->isNotEmpty() && $featured->variants->min('price') != $featured->variants->max('price');
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
    <section class="w-full relative bg-brand-light overflow-hidden font-sans">
        <div class="container mx-auto px-4 sm:px-6 py-8 sm:py-12 lg:py-20 flex flex-col lg:flex-row items-center gap-8 lg:gap-12 relative z-10">
            <!-- Text Content -->
            <div class="w-full lg:w-1/2 space-y-6 text-center lg:text-left pt-8 lg:pt-0">
                <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-brand-muted text-brand-dark text-xs font-bold uppercase tracking-wider mb-2 border border-brand-gold/20 motion-enter motion-enter-delay-0 hero-badge">
                    <span class="w-2 h-2 rounded-full bg-brand-gold animate-pulse"></span>
                    EXTRA DISCOUNT UP TO 40%
                </div>
                
                <h1 class="text-4xl sm:text-5xl lg:text-6xl font-extrabold text-brand-dark tracking-tight leading-tight font-serif motion-enter motion-enter-delay-100 hero-title">
                    Tingkatkan <span class="text-transparent bg-clip-text bg-gradient-to-r from-brand-gold to-brand-gold-dark">Kualitas Tidur Anda</span> Hari Ini.
                </h1>
                
                <p class="text-lg text-brand-dark/80 max-w-xl mx-auto lg:mx-0 font-medium motion-enter motion-enter-delay-200 hero-copy">
                    Temukan koleksi kasur premium dan perlengkapan tidur terbaik dari brand pilihan untuk istirahat yang lebih maksimal.
                </p>
                
                <div class="flex flex-col sm:flex-row items-center gap-4 pt-4 justify-center lg:justify-start motion-enter motion-enter-delay-300 hero-cta">
                    <a href="{{ route('categories') }}" class="w-full sm:w-auto px-8 py-4 bg-brand-dark hover:bg-brand-darker text-white font-bold rounded-full transition-all shadow-lg shadow-brand-dark/20 flex justify-center items-center gap-2 group">
                        Belanja Sekarang
                        <svg class="w-5 h-5 group-hover:translate-x-1 transition-transform" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M9 6l6 6-6 6" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </a>
                    <a href="{{ route('promos') }}" class="w-full sm:w-auto px-8 py-4 bg-white hover:bg-brand-light text-brand-dark font-bold rounded-full transition-all shadow-sm border border-brand-gold/30 hover:border-brand-gold text-center">
                        Lihat Promo
                    </a>
                </div>
            </div>

            <!-- Image Grid / Banner -->
            <div class="w-full lg:w-1/2 relative h-[300px] sm:h-[400px] lg:h-[500px] rounded-3xl overflow-hidden shadow-2xl motion-scale-enter motion-enter-delay-200 hero-image">
                <!-- Main Image -->
                <div class="absolute inset-0 bg-brand-muted">
                    <img 
                        src="https://images.unsplash.com/photo-1540518614846-7eded433c457?auto=format&fit=crop&q=80&w=1200&h=800" 
                        alt="Comfortable Bed" 
                        class="w-full h-full object-cover"
                    />
                    <div class="absolute inset-0 bg-gradient-to-t from-brand-dark/80 to-transparent"></div>
                </div>

                <!-- Floating Badge -->
                <div class="absolute bottom-6 left-6 right-6 sm:bottom-8 sm:left-8 sm:right-auto bg-white/95 backdrop-blur-sm p-4 sm:p-5 rounded-2xl shadow-xl max-w-sm border border-white/20">
                    <h3 class="font-bold text-brand-dark mb-1 leading-tight">Lady Americana Legacy</h3>
                    <p class="text-sm text-gray-500 mb-3">Springbed dengan teknologi support terbaik.</p>
                    <div class="flex items-center gap-3">
                        <span class="text-xs font-bold px-2 py-1 bg-brand-dark text-brand-gold rounded">HOT</span>
                        <span class="font-bold text-lg text-brand-darker">Mulai Rp 5.2Jt</span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Decorative background circle -->
        <div class="absolute top-1/2 right-0 -translate-y-1/2 translate-x-1/3 w-[800px] h-[800px] rounded-full bg-brand-gold/10 blur-3xl -z-0"></div>
    </section>

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
                    <h4 class="font-bold text-brand-dark">Garansi Resmi</h4>
                    <p class="text-sm text-gray-500">Hingga 15 Tahun</p>
                </div>
                <div class="flex flex-col items-center gap-3 px-4">
                    <div class="w-12 h-12 bg-brand-light rounded-full flex items-center justify-center text-brand-gold-dark mb-1">
                            <i class="fa-solid fa-award w-6 h-6"></i>
                        </div>
                    <h4 class="font-bold text-brand-dark">Produk Original</h4>
                    <p class="text-sm text-gray-500">100% Produk Asli</p>
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
                    <h4 class="font-bold text-brand-dark">Cicilan 0%</h4>
                    <p class="text-sm text-gray-500">Tanpa Kartu Kredit</p>
                </div>
                <div class="flex flex-col items-center gap-3 px-4">
                    <div class="w-12 h-12 bg-brand-light rounded-full flex items-center justify-center text-brand-gold-dark mb-1">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" aria-hidden="true">
                                <rect x="7" y="2" width="10" height="20" rx="2" stroke="currentColor" stroke-width="1.6" fill="none" />
                                <circle cx="12" cy="18" r="0.8" fill="currentColor" />
                            </svg>
                        </div>
                    <h4 class="font-bold text-brand-dark">Gratis Konsultasi</h4>
                    <p class="text-sm text-gray-500">Pilih Sesuai Kebutuhan</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Categories / Quick Filter -->
    <section class="py-16">
        <div class="container mx-auto px-6">
            <div class="flex items-center justify-between mb-8">
                <div>
                    <h2 class="text-2xl font-extrabold text-brand-dark tracking-tight">Kategori Spesial</h2>
                    <p class="text-gray-500 mt-1">Jelajahi koleksi terlengkap dari brand terkemuka.</p>
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
                            <svg class="w-8 h-8" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 17.27 18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round" fill="none"/></svg>
                        </div>
                        <span class="font-bold text-brand-dark text-center text-sm group-hover:text-brand-gold-dark">{{ $cat->name }}</span>
                        <span class="text-xs text-gray-500 mt-1">{{ $cat->children->count() + 10 }} Products</span>
                    </a>
                @endforeach
            </div>
        </div>
    </section>

    <!-- Best Seller -->
    <section class="py-16 bg-white border-t border-brand-muted/50">
        <div class="container mx-auto px-6">
            <div class="flex flex-col md:flex-row items-end justify-between mb-10 gap-4">
                <div>
                    <h2 class="text-3xl font-extrabold text-brand-dark tracking-tight flex items-center gap-2">
                        Best Seller <i class="fa-solid fa-award"></i>
                    </h2>
                    <p class="text-gray-500 mt-2 text-lg">Produk paling laris dengan rating tertinggi minggu ini.</p>
                </div>
                <a href="{{ route('categories') }}" class="font-bold text-brand-gold-dark hover:text-brand-dark transition-colors flex items-center gap-1 group">
                    Lihat Semua <i class="fa-solid fa-chevron-right w-4 h-4 group-hover:translate-x-1 transition-transform"></i>
                </a>
            </div>
            
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                @foreach($bestsellers as $product)
                    @include('frontend.components.product-card-dynamic', ['product' => $product])
                @endforeach
            </div>
        </div>
    </section>

    <!-- Special Spotlight (Featured Product) -->
    <section class="py-20 bg-brand-dark text-white relative overflow-hidden">
        <div class="absolute top-0 right-0 w-[500px] h-[500px] bg-brand-darker rounded-full blur-3xl -translate-y-1/2 translate-x-1/3 opacity-50"></div>
        
        <div class="container mx-auto px-6 relative z-10">
            <div class="text-center max-w-2xl mx-auto mb-12">
                <h2 class="text-3xl lg:text-4xl font-extrabold tracking-tight mb-4 font-serif text-brand-gold">Spesial Buat Kamu Hari Ini</h2>
                <p class="text-brand-light/90 text-lg">Rekomendasi khusus berdasarkan preferensi dan tren pencarian terbaik untuk kenyamanan tidur Anda.</p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 items-center max-w-5xl mx-auto bg-white/5 backdrop-blur border border-brand-gold/20 rounded-3xl p-6 lg:p-10 shadow-2xl">
                <div class="aspect-[4/3] rounded-2xl overflow-hidden shadow-2xl relative group border border-brand-gold/20">
                    <img 
                        src="https://images.unsplash.com/photo-1584622650111-993a426fbf0a?auto=format&fit=crop&q=80&w=800&h=600" 
                        alt="Special Product" 
                        class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700" 
                    />
                    <div class="absolute inset-0 bg-gradient-to-t from-gray-900/60 to-transparent"></div>
                    <div class="absolute bottom-6 left-6 flex gap-2">
                        <span class="bg-brand-gold text-brand-dark text-xs font-bold px-3 py-1.5 rounded-full uppercase tracking-wider">Top Pick</span>
                    </div>
                </div>
                
                <div class="space-y-6">
                    <div class="flex items-center gap-1.5 text-brand-gold">
                        <svg class="w-5 h-5 fill-current" viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg"><path d="M12 17.27 18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg>
                        <svg class="w-5 h-5 fill-current" viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg"><path d="M12 17.27 18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg>
                        <svg class="w-5 h-5 fill-current" viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg"><path d="M12 17.27 18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg>
                        <svg class="w-5 h-5 fill-current" viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg"><path d="M12 17.27 18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg>
                        <span class="text-brand-light/80 ml-2 text-sm">(128 Ulasan)</span>
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
                                <span class="text-sm font-bold text-red-300">Hemat {{ $featuredPromo['label'] }}</span>
                            @endif
                            <span class="text-2xl font-extrabold text-brand-gold">Rp {{ number_format($featuredPrice, 0, ',', '.') }}@if($featuredHasPriceRange) - Rp {{ number_format($featuredPriceMax, 0, ',', '.') }}@endif</span>
                        </div>
                        <a 
                            href="{{ route('products.show', $featured->slug ?? '') }}"
                            class="bg-brand-gold text-brand-dark hover:bg-brand-light font-bold px-6 py-3 rounded-full shadow-lg transition-transform active:scale-95 flex items-center gap-2"
                        >
                            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M6 7h12l-1 12H7L6 7Z" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/><path d="M9 7V5a3 3 0 0 1 6 0v2" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
                            Pilih Opsi
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Product Recommendations -->
    <section class="py-16 bg-brand-light/50">
        <div class="container mx-auto px-6">
            <div class="text-center mb-12">
                <h2 class="text-3xl font-extrabold text-brand-dark tracking-tight">Rekomendasi Lainnya</h2>
                <p class="text-gray-500 mt-2 text-lg">Pilihan aksesori dan kasur populer untuk melengkapi kamar Anda.</p>
            </div>
            
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 recommended-products-grid">
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
                    Muat Lebih Banyak <span class="group-hover:translate-x-1 transition-transform inline-block">&rarr;</span>
                </button>
            </div>
            @endif
        </div>
    </section>
@endsection

