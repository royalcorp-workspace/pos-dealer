@extends('frontend.layouts.app')

@section('title', $product->name . ' - IMG')

@section('meta_description', \Illuminate\Support\Str::limit(strip_tags($product->short_description ?: $product->description ?: $product->name), 160))
@section('canonical', route('products.show', $product->slug))
@section('og_image', $product->thumbnail_url ?: 'https://images.unsplash.com/photo-1540518614846-7eded433c457?auto=format&fit=crop&q=80&w=1200&h=800')
@section('og_type', 'product')

@section('content')
    @php
        $variantsData = $product->variants->sortBy(function($variant) {
            preg_match('/\d+/', $variant->variant_name, $matches);
            return $matches ? (int) $matches[0] : 999999;
        })->values();
        $validVariants = $variantsData->filter(function($v) { return (float) $v->sell_price > 0; });
        $colorsData = $product->colors->sortBy('color_name', SORT_NATURAL | SORT_FLAG_CASE)->values();
        $hasVariants = $validVariants->isNotEmpty();
        $hasColors = $colorsData->isNotEmpty();
        $firstVariantName = $hasVariants ? $validVariants->first()->variant_name : '';
        $totalStock = 999;
        
        $minPrice = $hasVariants ? $validVariants->min('sell_price') : null;
        $maxPrice = $hasVariants ? $validVariants->max('sell_price') : null;
        $minBasePrice = $hasVariants ? $validVariants->min('base_price') : null;
        $maxBasePrice = $hasVariants ? $validVariants->max('base_price') : null;

        $originalMinPrice = $hasVariants && $minPrice ? (float) $minPrice : (float) (0);
        $originalMaxPrice = $hasVariants && $maxPrice ? (float) $maxPrice : $originalMinPrice;
        $originalMinBasePrice = $hasVariants && $minBasePrice ? (float) $minBasePrice : $originalMinPrice;
        $originalMaxBasePrice = $hasVariants && $maxBasePrice ? (float) $maxBasePrice : $originalMaxPrice;

        $hasMultiplePrices = $hasVariants && $minPrice && $maxPrice && $minPrice !== $maxPrice;
        $hasDefaultDiscount = $originalMinBasePrice > $originalMinPrice;
        $defaultDiscountPct = $hasDefaultDiscount ? round((($originalMinBasePrice - $originalMinPrice) / $originalMinBasePrice) * 100) : 0;

        $staticPromo = \App\Services\StaticPromoService::forProduct($product, $originalMinPrice);
        
        $price = $originalMinPrice;
        $displayMaxPrice = $hasMultiplePrices ? $originalMaxPrice : null;
        $strikeMinPrice = $hasDefaultDiscount ? $originalMinBasePrice : null;
        $strikeMaxPrice = $hasDefaultDiscount && $hasMultiplePrices ? $originalMaxBasePrice : null;
        $discountBadge = null;

        $defaultDiscountBadge = $hasDefaultDiscount && $defaultDiscountPct > 0 ? $defaultDiscountPct . '% OFF' : null;
        $ppsDiscountBadge = null;

        if ($staticPromo) {
            $price = \App\Services\StaticPromoService::discountedPrice($originalMinPrice, $staticPromo);
            $displayMaxPrice = $hasMultiplePrices ? \App\Services\StaticPromoService::discountedPrice($originalMaxPrice, $staticPromo) : null;
            
            $strikeMinPrice = $hasDefaultDiscount ? $originalMinBasePrice : $originalMinPrice;
            $strikeMaxPrice = $hasMultiplePrices ? ($hasDefaultDiscount ? $originalMaxBasePrice : $originalMaxPrice) : null;
            
            if ($strikeMinPrice > 0) {
                $totalDiscountPct = round((($strikeMinPrice - $price) / $strikeMinPrice) * 100);
                if ($hasDefaultDiscount && $defaultDiscountPct > 0) {
                    $ppsDiscountPct = round((($originalMinPrice - $price) / $originalMinPrice) * 100);
                    $ppsDiscountBadge = 'EXTRA ' . $ppsDiscountPct . '% OFF';
                } else {
                    $defaultDiscountBadge = $totalDiscountPct . '% OFF';
                }
            }
        }
        
        // Setup alias for schema & Alpine component
        $originalPrice = $price;
        $promoOriginalPrice = $strikeMinPrice;
        $promoOriginalMaxPrice = $strikeMaxPrice;

        $availability = $totalStock > 0 ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock';
        $images = collect([$product->thumbnail_url])
            ->merge($product->images->map(fn($image) => $image->image_url ?? ($image->image ? asset('storage/' . $image->image) : null)))
            ->filter()
            ->values()
            ->take(8)
            ->toArray();
        $brandName = $product->brand->name ?? 'IMG';
        $categoryName = $product->category->name ?? 'Kategori';
        $categoryUrl = $product->category?->slug ? route('category.show', $product->category->slug) : route('categories');
        $brandUrl = $product->brand?->slug ? route('brands.show', $product->brand->slug) : route('brands');
        $productUrl = route('products.show', $product->slug);
        $wishlist = session()->get('wishlist', []);
        $isInWishlist = in_array($product->id, $wishlist);
        $productSchema = [
            '@context' => 'https://schema.org/',
            '@type' => 'Product',
            'name' => $product->name,
            'image' => $images,
            'description' => strip_tags($product->short_description ?: $product->description ?: $product->name),
            'sku' => $product->sku ?? $product->id,
            'brand' => [
                '@type' => 'Brand',
                'name' => $brandName
            ],
            'offers' => [
                '@type' => 'Offer',
                'url' => $productUrl,
                'priceCurrency' => 'IDR',
                'price' => $price,
                'availability' => $availability,
                'seller' => [
                    '@type' => 'Organization',
                    'name' => 'IMG'
                ]
            ]
        ];
        $breadcrumbSchema = [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => [
                [
                    '@type' => 'ListItem',
                    'position' => 1,
                    'name' => 'Home',
                    'item' => route('home')
                ],
                [
                    '@type' => 'ListItem',
                    'position' => 2,
                    'name' => $product->category->name ?? 'Kategori',
                    'item' => $categoryUrl
                ],
                [
                    '@type' => 'ListItem',
                    'position' => 3,
                    'name' => $product->brand->name ?? 'Unknown Brand',
                    'item' => $brandUrl
                ],
                [
                    '@type' => 'ListItem',
                    'position' => 4,
                    'name' => $product->name,
                    'item' => $productUrl
                ]
            ]
        ];
        @endphp
    <div class="container mx-auto px-4 md:px-6 py-6 sm:py-10">
        <div class="flex flex-col lg:flex-row gap-8 lg:gap-14 items-start">
            
            <!-- Left Column: Sticky Product Media Gallery (Luxury Studio Viewer) -->
            @php
                $dbImages = $product->images->isNotEmpty() 
                    ? $product->images->map(fn($i) => $i->image_url ?? ($i->image ? asset('storage/' . $i->image) : null))->filter()->values()->toArray()
                    : [];

                $allImages = collect([$product->thumbnail_url ?: asset('images/dummy/header.jpg')])
                    ->merge($dbImages)
                    ->toArray();

                // If only 1 image exists in database, supply curated studio perspective angles
                if (count($allImages) < 2) {
                    $allImages = array_merge($allImages, [
                        asset('images/dummy/detail-1.jpg'),
                        asset('images/dummy/detail-2.jpg'),
                        asset('images/dummy/detail-3.jpg'),
                        asset('images/dummy/detail-4.jpg'),
                    ]);
                }
            @endphp
            <div 
                class="w-full lg:w-1/2 lg:sticky lg:top-28 space-y-3.5" 
                x-data="{ 
                    images: {{ json_encode($allImages) }},
                    currentIndex: 0,
                    get currentImage() { return this.images[this.currentIndex] || this.images[0]; },
                    nextImage() { this.currentIndex = (this.currentIndex + 1) % this.images.length; },
                    prevImage() { this.currentIndex = (this.currentIndex - 1 + this.images.length) % this.images.length; }
                }"
            >
                <!-- Main Stage Image with Smooth Hover Zoom & Navigation -->
                <div class="aspect-[4/3] bg-gradient-to-b from-[#FAF8F5] to-[#F3F1EC] rounded-3xl overflow-hidden border border-[#EFECE6] relative shadow-sm group">
                    <img 
                        :src="currentImage" 
                        alt="{{ $product->alt_text ?? $product->name }}" 
                        decoding="async"
                        class="w-full h-full object-cover transition-transform duration-700 ease-out group-hover:scale-110 cursor-zoom-in"
                        onerror="this.onerror=null;this.src='{{ asset('images/dummy/header.jpg') }}';"
                    />
                    
                    <!-- Floating Brand & Status Badges (Top Left) -->
                    <div class="absolute top-4 left-4 flex flex-col gap-2 z-10">
                        @if($product->best_seller)
                            <span class="inline-flex items-center gap-1.5 bg-brand-dark text-brand-gold text-[11px] font-black px-3 py-1.5 rounded-full uppercase tracking-wider shadow-md border border-brand-gold/30">
                                <i class="fa-solid fa-crown text-[10px]"></i>
                                {{ __('Best Seller') }}
                            </span>
                        @endif
                        @if($staticPromo)
                            <span class="inline-flex items-center bg-red-600 text-white text-[11px] font-bold px-3 py-1 rounded-full uppercase tracking-wider shadow-md">
                                {{ __('Hemat') }} {{ $staticPromo['label'] }}
                            </span>
                        @endif
                    </div>

                    <!-- Next & Previous Arrows (Always Available) -->
                    <div class="absolute inset-x-3 top-1/2 -translate-y-1/2 flex items-center justify-between pointer-events-none z-10">
                        <button 
                            type="button" 
                            @click="prevImage()" 
                            class="w-10 h-10 rounded-full bg-white/90 backdrop-blur-md text-brand-dark hover:bg-brand-dark hover:text-white shadow-md border border-white/40 flex items-center justify-center transition-all opacity-80 group-hover:opacity-100 pointer-events-auto cursor-pointer focus:outline-none"
                            aria-label="Foto Sebelumnya"
                        >
                            <i class="fa-solid fa-chevron-left text-xs"></i>
                        </button>
                        <button 
                            type="button" 
                            @click="nextImage()" 
                            class="w-10 h-10 rounded-full bg-white/90 backdrop-blur-md text-brand-dark hover:bg-brand-dark hover:text-white shadow-md border border-white/40 flex items-center justify-center transition-all opacity-80 group-hover:opacity-100 pointer-events-auto cursor-pointer focus:outline-none"
                            aria-label="Foto Berikutnya"
                        >
                            <i class="fa-solid fa-chevron-right text-xs"></i>
                        </button>
                    </div>

                    <!-- Image Counter Badge (Bottom Right) -->
                    <div class="absolute bottom-4 right-4 px-2.5 py-1 rounded-full bg-black/60 backdrop-blur-md text-white text-[10px] font-extrabold shadow-sm tracking-wider z-10">
                        <span x-text="currentIndex + 1"></span> / <span x-text="images.length"></span>
                    </div>
                </div>
                
                <!-- Thumbnails Strip (Always Visible) -->
                <div class="flex items-center gap-3 overflow-x-auto pb-2 pt-1 scrollbar-hide snap-x snap-mandatory">
                    <template x-for="(img, idx) in images" :key="idx">
                        <button 
                            type="button"
                            class="aspect-square bg-white rounded-2xl overflow-hidden border-2 cursor-pointer shrink-0 w-20 h-20 transition-all duration-300 snap-start shadow-xs focus:outline-none"
                            :class="currentIndex === idx ? 'border-brand-gold ring-2 ring-brand-gold/30 shadow-md scale-102 opacity-100' : 'border-gray-200 opacity-60 hover:opacity-100'"
                            @click="currentIndex = idx"
                            :aria-label="'Pilih Foto ' + (idx + 1)"
                        >
                            <img :src="img" :alt="'Thumbnail ' + (idx + 1)" loading="lazy" decoding="async" class="w-full h-full object-cover" onerror="this.onerror=null;this.src='{{ asset('images/dummy/header.jpg') }}';" />
                        </button>
                    </template>
                </div>
            </div>

            <!-- Right Column: Product Detail, Price, Customizer & Specs -->
            <div class="w-full lg:w-1/2 flex flex-col font-sans">
                <!-- Brand Meta & Title -->
                <div class="mb-3 flex items-center justify-between gap-4">
                    <a href="{{ $brandUrl }}" class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-brand-light/80 border border-brand-gold/30 text-xs font-bold text-brand-gold-dark uppercase tracking-widest hover:bg-brand-dark hover:text-brand-gold transition-colors">
                        <span>{{ $product->brand->name ?? 'IMG Official' }}</span>
                    </a>

                    <!-- Rating & Reviews -->
                    <div class="flex items-center gap-1.5 text-xs font-semibold">
                        <div class="flex items-center text-amber-500">
                            <i class="fa-solid fa-star text-xs"></i>
                        </div>
                        <span class="font-extrabold text-brand-dark">{{ number_format((float)($product->average_rating ?? 5.0), 1) }}</span>
                        <span class="text-gray-400">({{ $product->review_count ?? 0 }} {{ __('ulasan') }})</span>
                    </div>
                </div>
                
                <h1 class="text-2xl sm:text-3xl lg:text-[34px] font-extrabold text-brand-dark mb-4 leading-tight font-serif">
                    {{ $product->name }}
                </h1>

                <!-- Price Box Card -->
                <div class="mb-6 p-5 sm:p-6 bg-gradient-to-br from-[#FCFAF7] to-[#F7F5F0] rounded-3xl border border-[#EFECE6] shadow-xs">
                    @php
                        $minPrice = $hasVariants ? $validVariants->min('price') : null;
                        $maxPrice = $hasVariants ? $validVariants->max('price') : null;
                        $hasMultiplePrices = $hasVariants && $minPrice != $maxPrice;
                        $firstVariantName = $hasVariants ? $validVariants->first()->variant_name : '';
                    @endphp
                    
                    @if($staticPromo)
                        <div class="flex items-center gap-2 mb-1.5">
                            <span class="text-xs sm:text-sm text-gray-400 line-through">
                                Rp {{ number_format($promoOriginalPrice, 0, ',', '.') }}
                                @if($hasMultiplePrices) - Rp {{ number_format($promoOriginalMaxPrice, 0, ',', '.') }} @endif
                            </span>
                            <span class="text-[11px] font-extrabold text-red-600 bg-red-50 px-2.5 py-0.5 rounded-full border border-red-200">
                                {{ __('Hemat') }} {{ $staticPromo['label'] }}
                            </span>
                        </div>
                    @endif

                    <div class="flex flex-col">
                        <span class="text-3xl sm:text-4xl font-extrabold text-brand-dark tracking-tight font-sans" id="product-price">
                            Rp {{ number_format($price, 0, ',', '.') }}@if($hasMultiplePrices) - Rp {{ number_format($displayMaxPrice, 0, ',', '.') }}@endif
                        </span>
                        <span class="text-xs font-semibold text-brand-gold-dark mt-1.5" id="price-label">
                            @if($hasMultiplePrices)
                                {{ __('Pilih ukuran matras di bawah untuk melihat harga akurat') }}
                            @else
                                {{ __('Harga resmi untuk ukuran') }}: {{ $firstVariantName }}
                            @endif
                        </span>
                    </div>
                </div>

                <!-- Product Purchase Form -->
                <form action="{{ route('cart.add') }}" method="POST">
                    @csrf
                    <input type="hidden" name="product_id" value="{{ $product->id }}">

                    <!-- Options (Variants / Sizes) -->
                    @if($hasVariants)
                        @if(!empty($attributeGroups))
                            @foreach($attributeGroups as $groupName => $options)
                                <div class="mb-6 attribute-group-container" data-group="{{ $groupName }}">
                                    <div class="flex items-center justify-between mb-3">
                                        <h3 class="text-xs uppercase tracking-wider font-bold text-gray-500">{{ __('Pilih') }} {{ $groupName }}</h3>
                                        <span class="text-[11px] text-gray-400 font-medium">{{ count($options) }} {{ __('opsi tersedia') }}</span>
                                    </div>
                                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-2.5">
                                        @foreach($options as $option)
                                            <button 
                                                type="button"
                                                data-attribute-group="{{ $groupName }}"
                                                data-attribute-value="{{ $option }}"
                                                onclick="selectAttribute(this)"
                                                class="py-3 px-3 rounded-2xl font-bold text-xs sm:text-sm transition-all duration-200 text-center focus:outline-none border-2 border-gray-200 bg-white text-gray-700 hover:border-brand-gold/60 shadow-2xs attribute-btn cursor-pointer"
                                            >
                                                {{ $option }}
                                            </button>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        @else
                            <!-- Legacy fallback for variants without attributes -->
                            <div class="mb-6">
                                <h3 class="text-xs uppercase tracking-wider font-bold text-gray-500 mb-3">{{ __('Pilih Ukuran / Tipe') }}</h3>
                                <div class="grid grid-cols-2 sm:grid-cols-3 gap-2.5">
                                    @foreach($validVariants as $i => $variant)
                                        <button 
                                            type="button"
                                            data-variant-id="{{ $variant->id }}"
                                            data-variant-price="{{ \App\Services\StaticPromoService::discountedPrice((float) $variant->sell_price, $staticPromo) }}"
                                            data-variant-original-price="{{ $variant->sell_price }}"
                                            onclick="selectVariant(this)"
                                            class="py-3 px-3 rounded-2xl font-bold text-xs sm:text-sm transition-all duration-200 text-center focus:outline-none border-2 border-gray-200 bg-white text-gray-700 hover:border-brand-gold/60 shadow-2xs legacy-variant-btn cursor-pointer"
                                        >
                                            {{ $variant->variant_name }}
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                        <input type="hidden" name="variant_id" id="variant-id-input" value="">
                    @endif

                    <!-- Dual Action Luxury CTA Section -->
                    @php
                        $isDisabledByOptions = $hasVariants || $hasColors;
                    @endphp
                    <div class="mb-8 pt-5 border-t border-brand-muted/40">
                        <!-- Quantity Header with Live Ready Stock Indicator -->
                        <div class="flex items-center justify-between mb-2.5 px-0.5">
                            <span class="text-xs uppercase tracking-wider font-bold text-gray-500">{{ __('Jumlah') }}</span>
                            <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-emerald-50 border border-emerald-200/80 text-[11px] font-bold text-emerald-800 shadow-2xs">
                                <span class="relative flex h-2 w-2">
                                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                    <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-600"></span>
                                </span>
                                <span>{{ __('Ready Stock • Siap Kirim') }}</span>
                            </div>
                        </div>

                        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
                            <!-- Qty Selector (Clean Stepper without default browser spinner) -->
                            <div class="flex items-center border-2 border-gray-200 rounded-2xl bg-white p-1 shrink-0 w-full sm:w-32 justify-between shadow-2xs">
                                <button type="button" onclick="updateQty(-1)" id="qty-minus-btn" class="w-10 h-10 rounded-xl hover:bg-gray-100 flex items-center justify-center text-lg font-bold text-brand-dark disabled:opacity-40 cursor-pointer focus:outline-none" {{ $isDisabledByOptions ? 'disabled' : '' }}>-</button>
                                <input type="number" name="quantity" id="quantity-input" value="1" min="1" max="999" class="w-12 text-center font-extrabold text-sm text-brand-dark border-none focus:outline-none disabled:opacity-40 [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none" {{ $isDisabledByOptions ? 'disabled' : '' }}>
                                <button type="button" onclick="updateQty(1)" id="qty-plus-btn" class="w-10 h-10 rounded-xl hover:bg-gray-100 flex items-center justify-center text-lg font-bold text-brand-dark disabled:opacity-40 cursor-pointer focus:outline-none" {{ $isDisabledByOptions ? 'disabled' : '' }}>+</button>
                            </div>

                            <!-- Primary Add to Cart Button with Dynamic Context Text -->
                            <button 
                                type="submit"
                                id="add-to-cart-btn"
                                class="flex-1 h-12 sm:h-13 rounded-2xl font-bold text-sm flex items-center justify-center gap-2.5 bg-brand-dark text-white hover:bg-black transition-all shadow-md active:scale-98 focus:outline-none disabled:opacity-40 disabled:cursor-not-allowed cursor-pointer"
                                disabled
                            >
                                <i class="fa-solid fa-bag-shopping text-brand-gold text-base" id="add-to-cart-icon"></i>
                                <span id="add-to-cart-text">{{ $isDisabledByOptions ? __('Pilih Ukuran Terlebih Dahulu') : __('Tambah ke Keranjang') }}</span>
                            </button>

                            <!-- Wishlist Heart Button -->
                            <button 
                                type="button"
                                class="h-12 w-12 sm:h-13 sm:w-13 rounded-2xl flex items-center justify-center border-2 border-gray-200 bg-white hover:border-brand-gold hover:text-brand-gold transition-all duration-200 shrink-0 cursor-pointer shadow-2xs {{ $isInWishlist ? 'text-red-500 border-red-200' : 'text-gray-400' }}"
                                data-product-id="{{ $product->id }}"
                                onclick="toggleWishlist(this)"
                                title="Simpan ke Wishlist"
                                aria-label="Wishlist"
                            >
                                <i class="fa-{{ $isInWishlist ? 'solid' : 'regular' }} fa-heart text-lg"></i>
                            </button>
                        </div>
                    </div>
                </form>

                <!-- Clean Trust Points Strip -->
                @php
                    $warrantyDuration = $product->warranty_duration;
                    $hasWarranty = !empty($warrantyDuration) || !empty($product->category->has_warranty);
                    $warrantyText = $warrantyDuration ? $warrantyDuration : '15 Tahun';
                @endphp
                <div class="grid grid-cols-3 gap-2.5 mb-8 p-4 rounded-2xl bg-white border border-[#EFECE6] shadow-2xs">
                    <div class="flex flex-col items-center text-center p-2">
                        <i class="fa-solid fa-shield-halved text-brand-gold text-lg mb-1.5"></i>
                        <span class="text-[11px] font-bold text-brand-dark leading-tight">{{ __('Garansi Resmi') }}</span>
                        <span class="text-[10px] text-gray-400 mt-0.5">{{ $hasWarranty ? $warrantyText : 'Pabrik' }}</span>
                    </div>
                    <div class="flex flex-col items-center text-center p-2 border-x border-gray-100">
                        <i class="fa-solid fa-truck-fast text-brand-gold text-lg mb-1.5"></i>
                        <span class="text-[11px] font-bold text-brand-dark leading-tight">{{ __('Pengiriman Cepat') }}</span>
                        <span class="text-[10px] text-gray-400 mt-0.5">{{ __('Handling Aman') }}</span>
                    </div>
                    <div class="flex flex-col items-center text-center p-2">
                        <i class="fa-solid fa-headset text-brand-gold text-lg mb-1.5"></i>
                        <span class="text-[11px] font-bold text-brand-dark leading-tight">{{ __('Konsultasi') }}</span>
                        <span class="text-[10px] text-gray-400 mt-0.5">{{ __('Gratis') }}</span>
                    </div>
                </div>

                <!-- Structured Product Tabs / Information Accordions (Independent & Stable Viewport) -->
                <div class="space-y-4" x-data="{ openDesc: true, openSpecs: true }">
                    <!-- Tab: Deskripsi Produk (Progressive Disclosure Rich Text) -->
                    <div class="border border-[#EFECE6] rounded-2xl overflow-hidden bg-white shadow-2xs" x-data="{ isExpanded: false, hasOverflow: false }" x-init="$nextTick(() => { const el = $refs.descContent; if (el && el.scrollHeight > 220) { hasOverflow = true; } })">
                        <button 
                            type="button" 
                            @click="openDesc = !openDesc"
                            class="w-full px-5 py-4 flex items-center justify-between text-left font-bold text-brand-dark text-sm bg-white hover:bg-gray-50/70 transition-colors focus:outline-none cursor-pointer"
                        >
                            <span class="font-serif text-base">{{ __('Deskripsi & Keunggulan Produk') }}</span>
                            <i class="fa-solid fa-chevron-down text-xs transition-transform duration-300" :class="openDesc ? 'rotate-180 text-brand-gold-dark' : 'text-gray-400'"></i>
                        </button>
                        <div x-show="openDesc" x-collapse class="px-5 pb-5 pt-2 text-sm text-gray-600 leading-relaxed border-t border-gray-100">
                            <!-- Rich Text Content Container with Smooth Height Limiter -->
                            <div class="relative">
                                <div 
                                    x-ref="descContent"
                                    class="prose prose-sm max-w-none text-gray-600 prose-headings:font-serif prose-headings:text-brand-dark prose-p:leading-relaxed prose-strong:text-brand-dark prose-ul:list-disc prose-ul:pl-4 prose-li:my-1 transition-all duration-500 overflow-hidden"
                                    :class="(!isExpanded && hasOverflow) ? 'max-h-52' : 'max-h-none'"
                                >
                                    {!! $product->description !!}
                                </div>

                                <!-- Gradient Overlay when collapsed -->
                                <div 
                                    x-show="!isExpanded && hasOverflow" 
                                    class="absolute bottom-0 inset-x-0 h-20 bg-gradient-to-t from-white via-white/80 to-transparent pointer-events-none"
                                ></div>
                            </div>

                            <!-- Expand / Collapse Toggle Button -->
                            <template x-if="hasOverflow">
                                <div class="mt-3 text-center border-t border-gray-100/80 pt-3">
                                    <button 
                                        type="button" 
                                        @click="isExpanded = !isExpanded"
                                        class="inline-flex items-center gap-1.5 text-xs font-bold text-brand-dark hover:text-brand-gold-dark bg-gray-50 hover:bg-brand-light px-4 py-2 rounded-full border border-gray-200 transition-all cursor-pointer shadow-2xs focus:outline-none"
                                    >
                                        <span x-text="isExpanded ? 'Tutup Sebagian' : 'Baca Selengkapnya'"></span>
                                        <i class="fa-solid text-[10px] transition-transform duration-300" :class="isExpanded ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
                                    </button>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Tab: Spesifikasi & Garansi (Independent Expandable) -->
                    <div class="border border-[#EFECE6] rounded-2xl overflow-hidden bg-white shadow-2xs">
                        <button 
                            type="button" 
                            @click="openSpecs = !openSpecs"
                            class="w-full px-5 py-4 flex items-center justify-between text-left font-bold text-brand-dark text-sm bg-white hover:bg-gray-50/70 transition-colors focus:outline-none cursor-pointer"
                        >
                            <span class="font-serif text-base">{{ __('Spesifikasi & Jaminan Pabrik') }}</span>
                            <i class="fa-solid fa-chevron-down text-xs transition-transform duration-300" :class="openSpecs ? 'rotate-180 text-brand-gold-dark' : 'text-gray-400'"></i>
                        </button>
                        <div x-show="openSpecs" x-collapse class="px-5 pb-5 pt-3 border-t border-gray-100">
                            <dl class="grid grid-cols-2 gap-3 text-xs">
                                <div class="p-3 bg-gray-50 rounded-xl">
                                    <dt class="text-gray-400 font-bold uppercase tracking-wider text-[10px]">{{ __('Merek Resmi') }}</dt>
                                    <dd class="mt-0.5 font-bold text-brand-dark text-sm">{{ $brandName }}</dd>
                                </div>
                                <div class="p-3 bg-gray-50 rounded-xl">
                                    <dt class="text-gray-400 font-bold uppercase tracking-wider text-[10px]">{{ __('Kategori') }}</dt>
                                    <dd class="mt-0.5 font-bold text-brand-dark text-sm">{{ $categoryName }}</dd>
                                </div>
                                <div class="p-3 bg-gray-50 rounded-xl">
                                    <dt class="text-gray-400 font-bold uppercase tracking-wider text-[10px]">{{ __('Garansi') }}</dt>
                                    <dd class="mt-0.5 font-bold text-brand-dark text-sm">{{ $hasWarranty ? "Resmi $warrantyText" : __('Garansi Toko') }}</dd>
                                </div>
                                <div class="p-3 bg-gray-50 rounded-xl">
                                    <dt class="text-gray-400 font-bold uppercase tracking-wider text-[10px]">{{ __('Ketersediaan') }}</dt>
                                    <dd class="mt-0.5 font-bold text-emerald-700 text-sm">{{ __('Ready Stock') }}</dd>
                                </div>
                            </dl>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        @if(isset($relatedProducts) && $relatedProducts->isNotEmpty())
        <!-- Suggested / Related Products (Luxury Ambient Showcase 5-Grid) -->
        <div class="mt-14 sm:mt-18 p-6 sm:p-8 md:p-10 rounded-3xl bg-[#FAF8F5] border border-[#EFECE6] shadow-2xs">
            <div class="mb-6 sm:mb-8 flex flex-col sm:flex-row sm:items-end justify-between gap-4 border-b border-[#EFECE6] pb-4">
                <div>
                    <span class="text-xs font-bold uppercase tracking-widest text-brand-gold-dark block mb-1">Rekomendasi Pilihan</span>
                    <h3 class="text-2xl sm:text-3xl font-extrabold text-brand-dark font-serif">Mungkin Anda Juga Suka</h3>
                </div>
                @if($product->category)
                    <a 
                        href="{{ route('category.show', $product->category->slug) }}" 
                        class="inline-flex items-center gap-1.5 text-xs font-bold text-brand-dark hover:text-brand-gold-dark group transition-colors self-start sm:self-auto"
                    >
                        <span>{{ __('Lihat Semua') }} {{ $product->category->name }}</span>
                        <i class="fa-solid fa-arrow-right text-[10px] transition-transform duration-300 group-hover:translate-x-1"></i>
                    </a>
                @else
                    <a 
                        href="{{ route('products.index') }}" 
                        class="inline-flex items-center gap-1.5 text-xs font-bold text-brand-dark hover:text-brand-gold-dark group transition-colors self-start sm:self-auto"
                    >
                        <span>{{ __('Lihat Semua Koleksi') }}</span>
                        <i class="fa-solid fa-arrow-right text-[10px] transition-transform duration-300 group-hover:translate-x-1"></i>
                    </a>
                @endif
            </div>
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-3 sm:gap-4 lg:gap-5">
                @foreach($relatedProducts as $relatedProduct)
                    <div class="flex flex-col h-full">
                        @include('frontend.components.product-card-dynamic', ['product' => $relatedProduct])
                    </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>

    @push('jsonld')
        <script type="application/ld+json">
        @json($productSchema)
        </script>
        <script type="application/ld+json">
        @json($breadcrumbSchema)
        </script>

    @endpush
@endsection

@push('tracking_events')
<script>
    window.dataLayer = window.dataLayer || [];
    
    // 1. Trigger: view_item (Melihat Detail Produk)
    dataLayer.push({ ecommerce: null }); // Clear the previous ecommerce object
    dataLayer.push({
        event: "view_item",
        ecommerce: {
            currency: "IDR",
            value: {{ $price ?? 0 }},
            items: [{
                item_id: "{{ $product->code ?? $product->id }}",
                item_name: "{{ $product->name }}",
                item_category: "{{ $categoryName ?? '' }}",
                item_brand: "{{ $brandName ?? '' }}",
                price: {{ $price ?? 0 }}
            }]
        }
    });

    // 2. Trigger: add_to_cart (Tambah ke Keranjang)
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.querySelector('form[action*="cart"]');
        if(form) {
            form.addEventListener('submit', function(e) {
                if (e.defaultPrevented) return;
                
                // Ensure validation from product-detail.js passes before firing
                const variantInput = document.getElementById('variant-id-input');
                const hasVariants = document.querySelector('[data-variant-id]') !== null;
                const colorInput = document.getElementById('color-id-input');
                const hasColors = document.querySelector('[data-color-id]') !== null;
                
                if (hasVariants && variantInput && !variantInput.value) return;
                if (hasColors && colorInput && !colorInput.value) return;
                
                const qty = parseInt(document.getElementById('quantity-input')?.value || 1);
                
                let selectedVariantPrice = {{ $price ?? 0 }};
                const activeVariant = document.querySelector('[data-variant-id].border-brand-gold');
                if (activeVariant && activeVariant.dataset.variantPrice) {
                    selectedVariantPrice = parseFloat(activeVariant.dataset.variantPrice) || selectedVariantPrice;
                }
                
                dataLayer.push({ ecommerce: null });
                dataLayer.push({
                    event: "add_to_cart",
                    ecommerce: {
                        currency: "IDR",
                        value: selectedVariantPrice * qty,
                        items: [{
                            item_id: "{{ $product->code ?? $product->id }}",
                            item_name: "{{ $product->name }}",
                            item_category: "{{ $categoryName ?? '' }}",
                            item_brand: "{{ $brandName ?? '' }}",
                            price: selectedVariantPrice,
                            quantity: qty
                        }]
                    }
                });
            });
        }
    });
</script>
@endpush

@push('scripts')
@php
    $mappedVariants = collect($validVariants ?? [])->map(function($v) {
        $rawAttrs = $v->getRawOriginal('attributes');
        $parsedAttrs = [];
        if ($rawAttrs) {
            $parsedAttrs = is_string($rawAttrs) ? json_decode($rawAttrs, true) : $rawAttrs;
            if (is_array($parsedAttrs)) {
                $ignoredKeys = ['width', 'length', 'height', 'weight', 'status'];
                
                // Cek apakah ada atribut selain yang diabaikan
                $hasOther = false;
                foreach ($parsedAttrs as $key => $val) {
                    if (!in_array(strtolower($key), $ignoredKeys)) {
                        $hasOther = true;
                        break;
                    }
                }
                
                if (!$hasOther && isset($parsedAttrs['width']) && isset($parsedAttrs['length'])) {
                    $parsedAttrs['Ukuran'] = $parsedAttrs['width'] . ' x ' . $parsedAttrs['length'];
                }
                
                foreach ($ignoredKeys as $ik) {
                    unset($parsedAttrs[$ik]);
                }
            }
        }
        return [
            'id' => $v->id,
            'price' => $v->sell_price,
            'base_price' => $v->base_price,
            'variant_name' => $v->variant_name,
            'attributes' => $parsedAttrs
        ];
    })->values()->all();
@endphp
<script>
    window.productVariants = @json($mappedVariants);
    window.staticPromo = @json($staticPromo ?? null);
</script>
<script src="{{ asset('js/frontend/product-detail.js') }}?v={{ filemtime(public_path('js/frontend/product-detail.js')) }}"></script>
@endpush