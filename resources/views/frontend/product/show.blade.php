@extends('frontend.layouts.app')

@section('title', $product->name . ' - IMG')

@section('meta_description', \Illuminate\Support\Str::limit($product->short_description ?: $product->description ?: $product->name, 160))
@section('canonical', route('products.show', $product->slug))
@section('og_image', $product->thumbnail_url ?: 'https://images.unsplash.com/photo-1540518614846-7eded433c457?auto=format&fit=crop&q=80&w=1200&h=800')
@section('og_type', 'product')

@section('content')
    @php
        $variantsData = $product->variants;
        $colorsData = $product->colors;
        $hasVariants = $variantsData->isNotEmpty();
        $hasColors = $colorsData->isNotEmpty();
        $basePrice = (float)($product->base_price ?? 0);
        $minPrice = $variantsData->min('price');
        $maxPrice = $variantsData->max('price');
        $hasMultiplePrices = $hasVariants && $minPrice != $maxPrice;
        $firstVariantName = $variantsData->first()->variant_name ?? '';
        $totalStock = $variantsData->sum('stock_qty');
        $originalPrice = $hasVariants ? (float) $variantsData->first()->price : $basePrice;
        $originalMaxPrice = $hasVariants && $maxPrice ? (float) $maxPrice : $originalPrice;
        $staticPromo = \App\Services\StaticPromoService::forProduct($product);
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
        <!-- Breadcrumbs -->
        <nav class="flex items-center gap-2 text-sm text-gray-500 mb-8 font-sans">
            <a href="{{ route('home') }}" class="hover:text-brand-dark transition-colors">Home</a>
            <i class="fa-solid fa-chevron-right w-4 h-4 text-gray-400"></i>
            <a href="{{ $product->category?->slug ? route('category.show', $product->category->slug) : route('categories') }}" class="hover:text-brand-dark transition-colors">
                {{ $product->category->name ?? 'Uncategorized' }}
            </a>
            <i class="fa-solid fa-chevron-right w-4 h-4 text-gray-400"></i>
            <a href="{{ $product->brand?->slug ? route('products.index', ['type' => 'brand', 'value' => $product->brand->slug]) : route('brands') }}" class="hover:text-brand-dark transition-colors">
                {{ $product->brand->name ?? 'Unknown Brand' }}
            </a>
            <i class="fa-solid fa-chevron-right w-4 h-4 text-gray-400"></i>
            <span class="text-brand-dark font-medium truncate">{{ $product->name }}</span>
        </nav>

        <div class="flex flex-col lg:flex-row gap-12">
            <!-- Left: Product Images -->
            <div class="w-full lg:w-1/2 space-y-4">
                <div class="aspect-[4/3] bg-brand-light rounded-3xl overflow-hidden border border-brand-muted relative">
                    <img 
                        src="{{ $product->thumbnail_url ?? 'https://via.placeholder.com/600x400' }}" 
                        alt="{{ $product->alt_text ?? $product->name }}" 
                        class="w-full h-full object-cover"
                    />
                    @if($product->best_seller)
                        <span class="absolute top-4 left-4 bg-brand-dark text-brand-gold text-xs font-bold px-3 py-1.5 rounded-sm uppercase tracking-wider">
                            Best Seller
                        </span>
                    @endif
                </div>
                
                <!-- Image Gallery -->
                @if($product->images->isNotEmpty())
                    <div class="grid grid-cols-4 gap-4">
                        @foreach($product->images as $image)
                            <div class="aspect-square bg-brand-light rounded-xl overflow-hidden border-2 cursor-pointer border-brand-gold">
                                <img src="{{ $image->image_url ?? ($image->image ? asset('storage/' . $image->image) : 'https://via.placeholder.com/150') }}" alt="{{ $image->alt_text }}" class="w-full h-full object-cover opacity-80 hover:opacity-100 transition-opacity" />
                            </div>
                        @endforeach
                    </div>
                @endif
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
                        $minPrice = $variantsData->min('price');
                        $maxPrice = $variantsData->max('price');
                        $hasMultiplePrices = $hasVariants && $minPrice != $maxPrice;
                        $firstVariantName = $variantsData->first()->variant_name ?? '';
                    @endphp
                    @if($staticPromo)
                        <div class="flex flex-col gap-1 mb-2">
                            <span class="text-sm text-gray-400 line-through">
                                Rp {{ number_format($promoOriginalPrice, 0, ',', '.') }}
                                @if($hasMultiplePrices) - Rp {{ number_format($promoOriginalMaxPrice, 0, ',', '.') }} @endif
                            </span>
                            <span class="text-sm font-bold text-red-600">Hemat {{ $staticPromo['label'] }}</span>
                        </div>
                    @endif
                    <span class="text-3xl font-extrabold text-brand-dark tracking-tight" id="product-price">Rp {{ number_format($price, 0, ',', '.') }}@if($hasMultiplePrices) - Rp {{ number_format($displayMaxPrice, 0, ',', '.') }}@endif</span>
                    <span class="block text-sm text-brand-gold-dark mt-2" id="price-label">Harga untuk ukuran: {{ $firstVariantName }}</span>
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
                                        class="py-3 px-4 rounded-xl font-semibold text-sm transition-all text-center focus:outline-none border-2 {{ $i === 0 ? 'border-brand-gold bg-brand-light text-brand-dark' : 'border-brand-muted bg-white text-gray-600' }}"
                                    >
                                        {{ $variant->variant_name }}
                                        @if($variant->stock_qty <= 0)
                                            <div class="text-xs text-gray-400 mt-1">Sold Out</div>
                                        @endif
                                    </button>
                                @endforeach
                            </div>
                        </div>
                        <input type="hidden" name="variant_id" id="variant-id-input" value="{{ $variantsData->first()->id }}">
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
                                        class="py-3 px-4 rounded-xl font-semibold text-sm transition-all text-center focus:outline-none border-2 {{ $i === 0 ? 'border-brand-gold bg-brand-light text-brand-dark' : 'border-brand-muted bg-white text-gray-600' }}"
                                    >
                                        {{ $color->color_name }}
                                        @if($color->color_code)
                                            <span class="block w-6 h-6 rounded-full mt-1 mx-auto border border-gray-300" style="background-color: {{ $color->color_code }};"></span>
                                        @endif
                                    </button>
                                @endforeach
                            </div>
                        </div>
                        <input type="hidden" name="color_id" id="color-id-input" value="{{ $colorsData->first()->id }}">
                    @endif

                    @php
                        $totalStock = $product->variants->sum('stock_qty');
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
                                        class="flex-1 h-12 rounded-xl font-bold flex items-center justify-center gap-2 bg-brand-dark text-brand-gold hover:bg-brand-darker shadow-lg shadow-brand-dark/20 transition-transform active:scale-[0.98] focus:outline-none"
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
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-8">
                    <div class="flex items-center gap-3 p-4 bg-brand-light rounded-xl border border-brand-muted/50">
                        <i class="fa-solid fa-shield-halved w-6 h-6 text-brand-gold flex-shrink-0"></i>
                        <span class="text-sm font-semibold text-brand-dark">Garansi Resmi 15 Tahun</span>
                    </div>
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
                            <dt class="text-xs font-bold uppercase tracking-wider text-gray-500">Merek</dt>
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
                            <dd class="mt-1 font-semibold text-brand-dark">Garansi resmi, konsultasi gratis, dan pengiriman Jabodetabek.</dd>
                        </div>
                    </dl>
                </div>
            </div>
        </div>
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
<script>
function selectVariant(el) {
    document.querySelectorAll('[data-variant-id]').forEach(btn => {
        btn.classList.remove('border-brand-gold', 'bg-brand-light', 'text-brand-dark');
        btn.classList.add('border-brand-muted', 'bg-white', 'text-gray-600');
    });
    el.classList.remove('border-brand-muted', 'bg-white', 'text-gray-600');
    el.classList.add('border-brand-gold', 'bg-brand-light', 'text-brand-dark');
    
    const priceEl = document.getElementById('product-price');
    const priceLabel = document.getElementById('price-label');
    
    if (priceEl && el.dataset.variantPrice) {
        priceEl.textContent = 'Rp ' + Number(el.dataset.variantPrice).toLocaleString('id-ID');
    }
    
    if (priceLabel) {
        const variantName = el.textContent.trim().split('\n')[0];
        priceLabel.textContent = 'Harga untuk ukuran: ' + variantName;
    }
    
    const variantInput = document.getElementById('variant-id-input');
    if (variantInput) {
        variantInput.value = el.dataset.variantId;
    }
}

function selectColor(el) {
    document.querySelectorAll('[data-color-id]').forEach(btn => {
        btn.classList.remove('border-brand-gold', 'bg-brand-light', 'text-brand-dark');
        btn.classList.add('border-brand-muted', 'bg-white', 'text-gray-600');
    });
    el.classList.remove('border-brand-muted', 'bg-white', 'text-gray-600');
    el.classList.add('border-brand-gold', 'bg-brand-light', 'text-brand-dark');
    
    const priceLabel = document.getElementById('price-label');
    if (priceLabel) {
        const selectedVariant = document.querySelector('[data-variant-id].border-brand-gold');
        const variantName = selectedVariant ? selectedVariant.textContent.trim().split('\n')[0] : '';
        const colorName = el.dataset.colorName;
        priceLabel.textContent = 'Harga untuk ukuran: ' + variantName + ', warna: ' + colorName;
    }
    
    const colorInput = document.getElementById('color-id-input');
    if (colorInput) {
        colorInput.value = el.dataset.colorId;
    }
}

function updateQty(change) {
    const input = document.getElementById('quantity-input');
    if (!input) return;
    let val = parseInt(input.value) || 1;
    val = Math.max(1, val + change);
    input.value = val;
}
</script>
@endpush