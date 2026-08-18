@extends('frontend.layouts.app')

@section('title', $product->name . ' - IMG')

@section('meta_description', \Illuminate\Support\Str::limit($product->short_description ?: $product->description ?: $product->name, 160))
@section('canonical', route('products.show', $product->slug))
@section('og_image', $product->thumbnail_url ?: 'https://images.unsplash.com/photo-1540518614846-7eded433c457?auto=format&fit=crop&q=80&w=1200&h=800')
@section('og_type', 'product')

@section('content')
    @php
        $variantsData = $product->variants->sortBy(function($variant) {
            preg_match('/\d+/', $variant->variant_name, $matches);
            return $matches ? (int) $matches[0] : 999999;
        })->values();
        $validVariants = $variantsData->where('price', '>', 0);
        $colorsData = $product->colors->sortBy('color_name', SORT_NATURAL | SORT_FLAG_CASE)->values();
        $hasVariants = $validVariants->isNotEmpty();
        $hasColors = $colorsData->isNotEmpty();
        $basePrice = (float)($product->base_price ?? 0);
        $minPrice = $hasVariants ? $validVariants->min('price') : null;
        $maxPrice = $hasVariants ? $validVariants->max('price') : null;
        $hasMultiplePrices = $hasVariants && $minPrice != $maxPrice;
        $firstVariantName = $hasVariants ? $validVariants->first()->variant_name : '';
        $totalStock = 999;
        $originalPrice = $hasVariants && $minPrice ? (float) $minPrice : $basePrice;
        $originalMaxPrice = $hasVariants && $maxPrice ? (float) $maxPrice : $originalPrice;
        $staticPromo = \App\Services\StaticPromoService::forProduct($product, $originalPrice);
        $price = \App\Services\StaticPromoService::discountedPrice($originalPrice, $staticPromo);
        $displayMaxPrice = $hasMultiplePrices ? \App\Services\StaticPromoService::discountedPrice($originalMaxPrice, $staticPromo) : null;
        $promoOriginalPrice = $staticPromo ? $originalPrice : null;
        $promoOriginalMaxPrice = $staticPromo && $hasMultiplePrices ? $originalMaxPrice : null;
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
        $brandUrl = $product->brand?->slug ? route('products.index', ['type' => 'brand', 'value' => $product->brand->slug]) : route('brands');
        $productUrl = route('products.show', $product->slug);
        $wishlist = session()->get('wishlist', []);
        $isInWishlist = in_array($product->id, $wishlist);
        $productSchema = [
            '@context' => 'https://schema.org/',
            '@type' => 'Product',
            'name' => $product->name,
            'image' => $images,
            'description' => $product->short_description ?: $product->description ?: $product->name,
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
    <div class="container mx-auto px-4 md:px-6 py-8">
        <!-- Breadcrumbs removed for cleaner aesthetic -->

        <div class="flex flex-col lg:flex-row gap-12">
            <!-- Left: Product Images -->
            <div class="w-full lg:w-1/2 space-y-4" x-data="{ mainImage: '{{ $product->thumbnail_url }}' }">
                <div class="aspect-[4/3] bg-brand-light rounded-3xl overflow-hidden border border-brand-muted relative">
                    <img 
                        :src="mainImage" 
                        alt="{{ $product->alt_text ?? $product->name }}" 
                        decoding="async"
                        class="w-full h-full object-cover transition-all duration-300"
                        onerror="this.onerror=null;this.src='{{ asset('images/dummy/header.jpg') }}';"
                    />
                    @if($product->best_seller)
                        <span class="absolute top-4 left-4 bg-brand-dark text-brand-gold text-xs font-bold px-3 py-1.5 rounded-sm uppercase tracking-wider shadow-sm">
                            Best Seller
                        </span>
                    @endif
                </div>
                
                <!-- Image Gallery -->
                @php
                    $galleryImages = $product->images->isNotEmpty() 
                        ? $product->images->map(fn($i) => $i->image_url ?? ($i->image ? asset('storage/' . $i->image) : asset('images/dummy/detail-1.jpg')))->toArray()
                        : [
                            asset('images/dummy/detail-1.jpg'),
                            asset('images/dummy/detail-2.jpg'),
                            asset('images/dummy/detail-3.jpg'),
                            asset('images/dummy/detail-4.jpg'),
                            asset('images/dummy/detail-5.jpg'),
                            asset('images/dummy/detail-6.jpg'),
                        ];
                @endphp
                <div class="flex overflow-x-auto gap-3 pb-3 snap-x scrollbar-hide">
                    <div class="aspect-square bg-brand-light rounded-xl overflow-hidden border-2 cursor-pointer flex-shrink-0 w-24 border-brand-gold transition-colors snap-start hover:opacity-100 opacity-100" @click="mainImage = '{{ $product->thumbnail_url }}'; $el.parentNode.querySelectorAll('div').forEach(d => { d.classList.remove('border-brand-gold'); d.classList.add('border-transparent', 'opacity-70'); }); $el.classList.add('border-brand-gold'); $el.classList.remove('border-transparent', 'opacity-70');">
                        <img src="{{ $product->thumbnail_url }}" alt="Thumbnail" loading="lazy" decoding="async" class="w-full h-full object-cover" onerror="this.onerror=null;this.src='{{ asset('images/dummy/header.jpg') }}';" />
                    </div>
                    @foreach($galleryImages as $index => $image)
                        <div class="aspect-square bg-brand-light rounded-xl overflow-hidden border-2 cursor-pointer border-transparent flex-shrink-0 w-24 opacity-70 hover:opacity-100 transition-colors snap-start" @click="mainImage = '{{ $image }}'; $el.parentNode.querySelectorAll('div').forEach(d => { d.classList.remove('border-brand-gold'); d.classList.add('border-transparent', 'opacity-70'); }); $el.classList.add('border-brand-gold'); $el.classList.remove('border-transparent', 'opacity-70');">
                            <img src="{{ $image }}" alt="Gallery {{ $index }}" loading="lazy" decoding="async" class="w-full h-full object-cover" onerror="this.onerror=null;this.src='{{ asset('images/dummy/detail-' . (($index % 6) + 1) . '.jpg') }}';" />
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Right: Product Info -->
            <div class="w-full lg:w-1/2 flex flex-col font-sans">
                <div class="mb-2 text-sm font-bold text-brand-gold-dark uppercase tracking-widest">
                    {{ $product->brand->name ?? 'Unknown Brand' }}
                </div>
                
                <h1 class="text-3xl md:text-4xl font-extrabold text-brand-dark mb-4 leading-tight font-serif">
                    {{ $product->name }}
                </h1>

                @if($product->tags->isNotEmpty())
                    <div class="flex flex-wrap gap-2 mb-4">
                        @foreach($product->tags as $tag)
                            <a href="{{ url('products/' . $tag->slug) }}"
                                class="text-xs px-3 py-1 bg-brand-light text-brand-gold-dark rounded-full hover:bg-brand-gold hover:text-white transition-colors border-2 border-brand-gold/20 hover:border-brand-gold"
                            >
                                {{ $tag->name }}
                            </a>
                        @endforeach
                    </div>
                @endif

                <!-- Rating -->
                <div class="flex items-center gap-1.5 mb-6">
                    <div class="flex items-center text-brand-gold">
                        <i class="fa-solid fa-star w-5 h-5 fill-current"></i>
                    </div>
                    <span class="font-bold text-brand-dark">{{ number_format($product->average_rating, 1) }}</span>
                    <span class="text-sm text-gray-400 hover:text-brand-gold-dark underline-offset-2 hover:underline">({{ $product->review_count }} Ulasan)</span>
                </div>

                <!-- Price Card -->
                <div class="mb-8 p-6 bg-brand-muted/30 rounded-2xl border border-brand-light">
                    @php
                        $minPrice = $hasVariants ? $validVariants->min('price') : null;
                        $maxPrice = $hasVariants ? $validVariants->max('price') : null;
                        $hasMultiplePrices = $hasVariants && $minPrice != $maxPrice;
                        $firstVariantName = $hasVariants ? $validVariants->first()->variant_name : '';
                    @endphp
                    @if($staticPromo)
                        <div class="flex flex-col gap-1 mb-2">
                            <span class="text-sm text-gray-500 line-through">
                                Rp {{ number_format($promoOriginalPrice, 0, ',', '.') }}
                                @if($hasMultiplePrices) - Rp {{ number_format($promoOriginalMaxPrice, 0, ',', '.') }} @endif
                            </span>
                            <span class="text-sm font-bold text-red-600">Hemat {{ $staticPromo['label'] }}</span>
                        </div>
                    @endif
                    <span class="text-3xl font-extrabold text-brand-dark tracking-tight" id="product-price">Rp {{ number_format($price, 0, ',', '.') }}@if($hasMultiplePrices) - Rp {{ number_format($displayMaxPrice, 0, ',', '.') }}@endif</span>
                    <span class="block text-sm text-brand-gold-dark mt-2" id="price-label">
                        @if($hasMultiplePrices)
                            Silakan pilih ukuran terlebih dahulu
                        @else
                            Harga untuk ukuran: {{ $firstVariantName }}
                        @endif
                    </span>
                </div>


                <!-- Product Purchase Form -->
                <form action="{{ route('cart.add') }}" method="POST">
                    @csrf
                    <input type="hidden" name="product_id" value="{{ $product->id }}">

                    <!-- Options (Variants) -->
                    @if($hasVariants)
                        <div class="mb-8">
                            <h3 class="font-bold text-brand-dark mb-4">Pilih Ukuran</h3>
                            <div class="grid grid-cols-2 lg:grid-cols-3 gap-3">
                                @foreach($variantsData as $i => $variant)
                                    <button 
                                        type="button"
                                        data-variant-id="{{ $variant->id }}"
                                        data-variant-price="{{ \App\Services\StaticPromoService::discountedPrice((float) $variant->price, $staticPromo) }}"
                                        data-variant-original-price="{{ $variant->price }}"
                                        onclick="selectVariant(this)"
                                        class="py-3 px-4 rounded-xl font-semibold text-sm transition-all text-center focus:outline-none border-2 border-brand-muted bg-white text-gray-600"
                                    >
                                        {{ $variant->variant_name }}
                                        @if(false)
                                            <div class="text-xs text-gray-400 mt-1">Sold Out</div>
                                        @endif
                                    </button>
                                @endforeach
                            </div>
                        </div>
                        <input type="hidden" name="variant_id" id="variant-id-input" value="">
                    @endif

                    <!-- Options (Colors) -->
                    @if($hasColors)
                        <div class="mb-8">
                            <h3 class="font-bold text-brand-dark mb-4">Pilih Warna</h3>
                            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
                                @foreach($colorsData as $i => $color)
                                    <button 
                                        type="button"
                                        data-color-id="{{ $color->id }}"
                                        data-color-name="{{ $color->color_name }}"
                                        data-color-code="{{ $color->color_code ?? '' }}"
                                        onclick="selectColor(this)"
                                        class="py-3 px-4 rounded-xl font-semibold text-sm transition-all text-center focus:outline-none border-2 border-brand-muted bg-white text-gray-600"
                                    >
                                        {{ $color->color_name }}
                                        @if($color->color_code)
                                            <span class="block w-6 h-6 rounded-full mt-1 mx-auto border border-gray-300" style="background-color: {{ $color->color_code }};"></span>
                                        @endif
                                    </button>
                                @endforeach
                            </div>
                        </div>
                        <input type="hidden" name="color_id" id="color-id-input" value="">
                    @endif

                    @php
                        $totalStock = 999;
                    @endphp

                    <!-- Action Buttons -->
                    <div class="flex flex-col sm:flex-row gap-4 mb-8 pt-6 border-t border-brand-muted">
                        @if($totalStock > 0)
                            <div class="flex gap-2 w-full">
                                <div class="w-1/5">
                                    <div class="flex items-center gap-1">
                                        <button type="button" onclick="updateQty(-1)" class="w-full h-12 rounded-lg border border-brand-muted bg-white hover:bg-brand-light flex items-center justify-center text-lg font-bold text-brand-dark">-</button>
                                        <input type="number" name="quantity" id="quantity-input" value="1" min="1" max="{{ $totalStock }}" class="w-full h-12 border border-brand-muted rounded-lg text-center font-bold text-brand-dark">
                                        <button type="button" onclick="updateQty(1)" class="w-full h-12 rounded-lg border border-brand-muted bg-white hover:bg-brand-light flex items-center justify-center text-lg font-bold text-brand-dark">+</button>
                                    </div>
                                </div>
                                <div class="w-4/5 flex gap-2">
                                    <button 
                                        type="submit"
                                        id="add-to-cart-btn"
                                        class="flex-1 h-12 rounded-xl font-bold flex items-center justify-center gap-2 bg-brand-dark text-brand-gold hover:bg-brand-darker shadow-lg shadow-brand-dark/20 transition-transform active:scale-[0.98] focus:outline-none disabled:opacity-50 disabled:cursor-not-allowed disabled:active:scale-100"
                                    >
                                        <i class="fa-solid fa-cart-shopping w-4 h-4"></i>
                                        Tambah ke Keranjang
                                    </button>
                                    <button 
                                        type="button"
                                        class="h-12 w-12 flex items-center justify-center border-2 border-brand-muted rounded-xl {{ $isInWishlist ? 'text-brand-gold border-brand-gold' : 'text-gray-400' }} hover:border-brand-gold hover:text-brand-gold transition-colors focus:outline-none"
                                        data-product-id="{{ $product->id }}"
                                        onclick="toggleWishlist(this)"
                                        title="Favorit"
                                    >
                                        <i class="fa-{{ $isInWishlist ? 'solid' : 'regular' }} fa-heart w-5 h-5"></i>
                                    </button>
                                </div>
                            </div>
                        @else
                            <button 
                                disabled
                                class="flex-1 h-14 rounded-xl font-bold flex items-center justify-center gap-2 bg-gray-100 text-gray-400 cursor-not-allowed"
                            >
                                Sold Out
                            </button>
                        @endif
                    </div>
                </form>

                <!-- Features list -->
                @php
                    $hasWarranty = $product->category->has_warranty ?? false;
                    $warrantyDuration = $product->warranty_duration ?? '15 Tahun';
                @endphp
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-8">
                    <div class="flex items-center gap-3 p-4 bg-brand-light rounded-xl border border-brand-muted/50">
                        <i class="fa-solid fa-truck w-6 h-6 text-brand-gold flex-shrink-0"></i>
                        <span class="text-sm font-semibold text-brand-dark">Gratis Ongkir Jabodetabek</span>
                    </div>
                    <div class="flex items-center gap-3 p-4 bg-brand-light rounded-xl border border-brand-muted/50">
                        <i class="fa-solid fa-rotate-left w-6 h-6 text-brand-gold flex-shrink-0"></i>
                        <span class="text-sm font-semibold text-brand-dark">100 Malam Uji Coba Tidur</span>
                    </div>
                    <div class="flex items-center gap-3 p-4 bg-brand-light rounded-xl border border-brand-muted/50">
                        <i class="fa-solid fa-comment-dots w-6 h-6 text-brand-gold flex-shrink-0"></i>
                        <span class="text-sm font-semibold text-brand-dark">Konsultasi Gratis</span>
                    </div>
                </div>

                <!-- Reviews Section -->
                <div class="border-t border-brand-muted pt-8 mt-8">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-xl font-bold text-brand-dark">Ulasan Produk</h3>
                        <div class="flex gap-1">
                            <button type="button" onclick="filterReviews(0)" class="px-2 py-1 text-xs rounded-full bg-brand-gold text-white">Semua</button>
                            <button type="button" onclick="filterReviews(5)" class="px-2 py-1 text-xs rounded-full bg-brand-light hover:bg-brand-gold">5 Bintang</button>
                            <button type="button" onclick="filterReviews(4)" class="px-2 py-1 text-xs rounded-full bg-brand-light hover:bg-brand-gold">4 Bintang</button>
                        </div>
                    </div>
                    <div id="reviews-list" class="space-y-4">
                        <p class="text-gray-500">Belum ada ulasan untuk produk ini.</p>
                    </div>
                </div>

                <!-- Description -->
                <div class="border-t border-brand-muted pt-8">
                    <h3 class="text-xl font-bold text-brand-dark mb-4">Deskripsi Produk</h3>
                    <div class="prose prose-sm text-gray-600 max-w-none leading-relaxed">
                        <p class="mb-4">
                            {{ $product->description }}
                        </p>
                    </div>
                </div>


                <div class="border-t border-brand-muted pt-8 mt-8">
                    <h3 class="text-xl font-bold text-brand-dark mb-4">Informasi Penting</h3>
                    <dl class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                        <div class="bg-brand-light rounded-xl p-4">
                            <dt class="text-xs font-bold uppercase tracking-wider text-gray-500">Brand</dt>
                            <dd class="mt-1 font-semibold text-brand-dark">{{ $brandName }}</dd>
                        </div>
                        <div class="bg-brand-light rounded-xl p-4">
                            <dt class="text-xs font-bold uppercase tracking-wider text-gray-500">Kategori</dt>
                            <dd class="mt-1 font-semibold text-brand-dark">{{ $categoryName }}</dd>
                        </div>
                        <div class="bg-brand-light rounded-xl p-4">
                            <dt class="text-xs font-bold uppercase tracking-wider text-gray-500">Ketersediaan</dt>
                            <dd class="mt-1 font-semibold text-brand-dark">{{ $totalStock > 0 ? 'Tersedia untuk dipesan' : 'Stok sedang habis' }}</dd>
                        </div>
                        <div class="bg-brand-light rounded-xl p-4">
                            <dt class="text-xs font-bold uppercase tracking-wider text-gray-500">Layanan</dt>
                            <dd class="mt-1 font-semibold text-brand-dark">Konsultasi gratis dan pengiriman Jabodetabek.</dd>
                        </div>
                        <div class="bg-brand-light rounded-xl p-4">
                            <dt class="text-xs font-bold uppercase tracking-wider text-gray-500">Garansi</dt>
                            <dd class="mt-1 font-semibold text-brand-dark">{{ $hasWarranty ? "Resmi $warrantyDuration" : 'Tidak ada garansi' }}</dd>
                        </div>
                    </dl>
                </div>
            </div>
        </div>

        @if($product->suggestedProducts->isNotEmpty())
        <!-- Suggested Products -->
        <div class="border-t border-brand-muted pt-12 mt-16">
            <h3 class="text-2xl md:text-3xl font-extrabold text-brand-dark mb-8 font-serif">Mungkin Anda Juga Suka</h3>
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 md:gap-6">
                @foreach($product->suggestedProducts as $suggestedProduct)
                    @include('frontend.components.product-card-dynamic', ['product' => $suggestedProduct])
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

@push('scripts')
<script src="{{ asset('js/frontend/product-detail.js') }}?v={{ filemtime(public_path('js/frontend/product-detail.js')) }}"></script>
@endpush