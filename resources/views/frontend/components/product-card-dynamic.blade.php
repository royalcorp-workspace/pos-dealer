@props(['product'])

@php
    $validVariants = $product->variants->where('price', '>', 0);
    $isVariable = $validVariants->isNotEmpty();
    $hasStock = true;
    $isSoldOut = false;
    $minPrice = $isVariable ? $validVariants->min('price') : null;
    $maxPrice = $isVariable ? $validVariants->max('price') : null;
    $originalMinPrice = $isVariable && $minPrice ? (float) $minPrice : (float) ($product->base_price ?? 0);
    $originalMaxPrice = $isVariable && $maxPrice ? (float) $maxPrice : $originalMinPrice;
    $hasPriceRange = $isVariable && $minPrice && $maxPrice && $minPrice !== $maxPrice;
    $staticPromo = \App\Services\StaticPromoService::forProduct($product, $originalMinPrice);
    $price = $originalMinPrice;
    $displayOriginalPrice = $hasPriceRange ? $originalMaxPrice : null;
    $promoOriginalMinPrice = null;
    $promoOriginalMaxPrice = null;

    if ($staticPromo) {
        $price = \App\Services\StaticPromoService::discountedPrice($originalMinPrice, $staticPromo);
        $displayOriginalPrice = $hasPriceRange ? \App\Services\StaticPromoService::discountedPrice($originalMaxPrice, $staticPromo) : null;
        $promoOriginalMinPrice = $originalMinPrice;
        $promoOriginalMaxPrice = $originalMaxPrice;
    }

    $reviewMinPrice = $isVariable ? \App\Services\StaticPromoService::discountedPrice((float) ($minPrice ?? 0), $staticPromo) : (float) $price;
    $reviewMaxPrice = $isVariable && $hasPriceRange ? \App\Services\StaticPromoService::discountedPrice((float) ($maxPrice ?? 0), $staticPromo) : (float) $price;

    $reviewPayload = [
        'id' => (string) $product->id,
        'name' => $product->name,
        'image' => $product->thumbnail_url ?? 'https://via.placeholder.com/400x300',
        'rating' => (float) ($product->rating ?? 0),
        'reviewsCount' => (int) ($product->reviewsCount ?? 0),
        'isVariable' => $isVariable,
        'price' => (float) $price,
        'minPrice' => $reviewMinPrice,
        'maxPrice' => $reviewMaxPrice,
        'originalPrice' => $originalMinPrice,
        'originalMinPrice' => $originalMinPrice,
        'originalMaxPrice' => $originalMaxPrice,
        'hasDiscount' => (bool) $staticPromo,
        'discountLabel' => $staticPromo['label'] ?? null,
        'reviews' => [],
    ];
@endphp

@php
    $wishlist = session()->get('wishlist', []);
    $isInWishlist = in_array($product->id, $wishlist);
@endphp
<div 
    class="product-card group relative bg-white border border-gray-100 rounded-2xl overflow-hidden shadow-sm hover:shadow-xl hover:scale-[1.02] hover:-translate-y-1 transition-all duration-300 flex flex-col h-full font-sans {{ $isSoldOut ? 'opacity-80' : '' }}"
    itemscope
    itemtype="https://schema.org/Product"
>
    <!-- Product Image Container -->
    <div class="relative aspect-[4/3] bg-brand-light overflow-hidden">
        <a href="{{ route('products.show', $product->slug) }}" class="block w-full h-full">
            <img 
                src="{{ $product->thumbnail_url ?? 'https://via.placeholder.com/400x300' }}" 
                alt="{{ $product->alt_text ?? $product->name }}" 
                itemprop="image"
                loading="lazy"
                decoding="async"
                class="product-card__image w-full h-full object-cover transition-transform duration-700 group-hover:scale-105 {{ $isSoldOut ? 'grayscale' : '' }}"
                onerror="this.onerror=null;this.src='{{ asset('images/dummy/header.jpg') }}';"
            />
        </a>
        
        <!-- Badges -->
        <div class="absolute top-1.5 left-1.5 sm:top-3 sm:left-3 flex flex-col gap-1 sm:gap-2 z-10">
            @if($staticPromo)
                <span class="bg-red-600 text-white text-[8px] sm:text-[11px] font-bold px-1.5 sm:px-2.5 py-0.5 sm:py-1 rounded-sm shadow-sm tracking-widest sm:tracking-wider uppercase">
                    Diskon {{ $staticPromo['label'] }}
                </span>
            @endif
            @if($product->best_seller)
                <span class="bg-brand-dark text-white text-[8px] sm:text-[11px] font-bold px-1.5 sm:px-2.5 py-0.5 sm:py-1 rounded-sm shadow-sm tracking-widest sm:tracking-wider uppercase">
                    Best Seller
                </span>
            @endif
            @if($product->is_new)
                <span class="bg-brand-gold text-brand-dark text-[8px] sm:text-[11px] font-bold px-1.5 sm:px-2.5 py-0.5 sm:py-1 rounded-sm shadow-sm tracking-widest sm:tracking-wider uppercase">
                    New
                </span>
            @endif
            @if($product->is_bundle)
                <span class="bg-purple-600 text-white text-[8px] sm:text-[11px] font-bold px-1.5 sm:px-2.5 py-0.5 sm:py-1 rounded-sm shadow-sm tracking-widest sm:tracking-wider uppercase">
                    Bundling Hemat
                </span>
            @endif
            @if($isSoldOut)
                <span class="bg-gray-800 text-white text-[8px] sm:text-[11px] font-bold px-1.5 sm:px-2.5 py-0.5 sm:py-1 rounded-sm shadow-sm tracking-widest sm:tracking-wider uppercase">
                    Sold Out
                </span>
            @endif
        </div>
        
<!-- Hover Action (Desktop) -->
        <div class="product-card__actions absolute top-3 right-3 opacity-0 group-hover:opacity-100 transition-opacity translate-x-4 group-hover:translate-x-0 duration-300 flex flex-col gap-2 z-10">
            <button 
                type="button"
                data-product-id="{{ $product->id }}"
                onclick="toggleWishlist(this)"
                class="w-10 h-10 bg-white rounded-full flex items-center justify-center text-gray-700 shadow-md hover:bg-brand-gold hover:text-white transition-colors focus:outline-none"
                aria-label="Tambah ke favorit"
            >
                <i class="fa-{{ $isInWishlist ? 'solid' : 'regular' }} fa-heart w-4 h-4 {{ $isInWishlist ? 'text-brand-gold' : '' }}"></i>
            </button>
            <button 
                type="button"
                data-product-review="{{ json_encode($reviewPayload, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_HEX_TAG) }}"
                data-product-id="{{ $product->id }}"
                @click="$dispatch('open-review', JSON.parse($el.dataset.productReview))"
                class="w-10 h-10 bg-white rounded-full flex items-center justify-center text-gray-700 shadow-md hover:bg-brand-gold hover:text-white transition-colors focus:outline-none"
                aria-label="Lihat ulasan"
            >
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </button>
        </div>
    </div>

    <!-- Product Info -->
    <div class="p-3 sm:p-5 flex flex-col flex-1">
        <div class="mb-1 text-[10px] sm:text-xs font-semibold text-brand-gold-dark uppercase tracking-widest" itemprop="brand" itemscope itemtype="https://schema.org/Brand">
            <span itemprop="name">{{ $product->brand->name ?? 'Unknown Brand' }}</span>
        </div>
        
        <h3 class="product-card__title font-semibold text-brand-dark text-sm sm:text-base leading-snug mb-2 hover:text-brand-gold transition-colors cursor-pointer line-clamp-2" itemprop="name">
            <a href="{{ route('products.show', $product->slug) }}" itemprop="url">
                {{ $product->name }}
            </a>
        </h3>

        @if($product->tags->isNotEmpty())
            <div class="flex flex-wrap gap-1 mb-2">
                @foreach($product->tags->take(3) as $tag)
                    <a href="{{ url('products/' . $tag->slug) }}"
                        class="text-[10px] px-2 py-0.5 bg-white text-gray-600 rounded-full hover:bg-brand-gold hover:text-white transition-colors border-2 border-gray-200 hover:border-brand-gold"
                    >
                        {{ $tag->name }}
                    </a>
                @endforeach
            </div>
        @endif

        <!-- Rating -->
        <div 
            class="product-card__rating flex items-center gap-1.5 mb-auto cursor-pointer hover:bg-brand-light p-1 -ml-1 rounded transition-colors w-fit"
            data-product-review="{{ json_encode($reviewPayload, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_HEX_TAG) }}"
            data-product-id="{{ $product->id }}"
            @click="$dispatch('open-review', JSON.parse($el.dataset.productReview))"
        >
            <div class="flex items-center text-brand-gold-dark">
                <svg class="w-3 sm:w-4 h-3 sm:h-4 fill-current" viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg"><path d="M12 2.5 15.09 9.26 22.5 9.96 17.25 14.7 18.82 22.03 12 18.55 5.18 22.03 6.75 14.7 1.5 9.96 8.91 9.26 12 2.5Z"/></svg>
            </div>
            <span class="text-xs sm:text-sm font-medium text-gray-700">{{ $reviewPayload['rating'] }}</span>
            <span class="text-[10px] sm:text-xs text-gray-500 hover:text-brand-gold-dark underline-offset-2 hover:underline">({{ $reviewPayload['reviewsCount'] }} Ulasan)</span>
        </div>

        <!-- Pricing -->
        <div class="flex flex-col gap-0.5 mt-2" itemprop="offers" itemscope itemtype="https://schema.org/Offer">
            <meta itemprop="priceCurrency" content="IDR" />
            <meta itemprop="price" content="{{ number_format($price, 0, ',', '.') }}" />
            <link itemprop="availability" href="{{ $hasStock ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock' }}" />
            @if($staticPromo)
                <span class="text-[10px] sm:text-xs text-gray-500 line-through">
                    Rp {{ number_format($promoOriginalMinPrice, 0, ',', '.') }}
                    @if($hasPriceRange) - Rp {{ number_format($promoOriginalMaxPrice, 0, ',', '.') }} @endif
                </span>
            @endif
            <span class="font-bold text-sm sm:text-lg {{ $staticPromo ? 'text-red-600' : 'text-brand-dark' }} tracking-tight">
                Rp {{ number_format($price, 0, ',', '.') }}
                @if($hasPriceRange) - Rp {{ number_format($displayOriginalPrice, 0, ',', '.') }} @endif
            </span>
        </div>

        <!-- Action Button -->
        <div class="mt-3 sm:mt-5 pt-3 sm:pt-4 border-t border-gray-100">
            @if($isSoldOut)
                <button 
                    disabled
                    class="w-full py-2.5 rounded-xl font-bold text-sm flex justify-center items-center gap-2 bg-gray-100 text-gray-400 cursor-not-allowed"
                >
                    Sold Out
                </button>
            @elseif($isVariable)
                <a 
                    href="{{ route('products.show', $product->slug) }}"
                    class="product-card__btn w-full py-1.5 sm:py-2.5 rounded-lg sm:rounded-xl font-bold text-xs sm:text-sm flex justify-center items-center gap-2 bg-white border-2 border-brand-dark text-brand-dark hover:bg-brand-dark hover:text-white group-hover:bg-brand-dark group-hover:text-white shadow-sm transition-all duration-300 text-center"
                >
                    Pilih Opsi
                </a>
            @else
                <form action="{{ route('cart.add') }}" method="POST">
                    @csrf
                    <input type="hidden" name="product_id" value="{{ $product->id }}">
                    <input type="hidden" name="quantity" value="1">
                    <button 
                        type="submit"
                        class="product-card__btn w-full py-1.5 sm:py-2.5 rounded-lg sm:rounded-xl font-bold text-[11px] sm:text-sm flex justify-center items-center gap-1.5 bg-white border-2 border-brand-dark text-brand-dark hover:bg-brand-dark hover:text-white group-hover:bg-brand-dark group-hover:text-white shadow-sm transition-all duration-300 focus:outline-none"
                    >
                        <svg class="w-3 sm:w-4 h-3 sm:h-4" viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg"><path d="M6 6h15l-1 12h-12L4 4H2" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/><circle cx="10" cy="20" r="1" fill="currentColor"/><circle cx="18" cy="20" r="1" fill="currentColor"/></svg>
                        + Keranjang
                    </button>
                </form>
            @endif
        </div>
    </div>
</div>