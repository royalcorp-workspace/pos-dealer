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
    <!-- Product Image Container (Sleek 16/11 aspect ratio) -->
    <div class="relative aspect-[16/11] bg-brand-light overflow-hidden">
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
        
        <!-- Elegant Floating Badges -->
        <div class="absolute top-2.5 left-2.5 sm:top-3 sm:left-3 flex flex-wrap gap-1.5 z-10 max-w-[85%]">
            @if($staticPromo)
                <span class="bg-red-600 text-white text-[9px] sm:text-[11px] font-bold px-2 py-0.5 sm:py-1 rounded-full shadow-xs tracking-wider uppercase backdrop-blur-xs">
                    -{{ $staticPromo['label'] }}
                </span>
            @endif
            @if($product->best_seller)
                <span class="bg-brand-dark/95 text-brand-gold-light text-[9px] sm:text-[11px] font-bold px-2.5 py-0.5 sm:py-1 rounded-full shadow-xs tracking-wider uppercase border border-brand-gold/30">
                    ⭐ Best Seller
                </span>
            @elseif($product->is_new)
                <span class="bg-brand-gold text-brand-darker text-[9px] sm:text-[11px] font-bold px-2.5 py-0.5 sm:py-1 rounded-full shadow-xs tracking-wider uppercase">
                    New
                </span>
            @elseif($product->is_bundle)
                <span class="bg-purple-700 text-white text-[9px] sm:text-[11px] font-bold px-2.5 py-0.5 sm:py-1 rounded-full shadow-xs tracking-wider uppercase">
                    Bundling
                </span>
            @elseif($isSoldOut)
                <span class="bg-gray-800/90 text-white text-[9px] sm:text-[11px] font-bold px-2.5 py-0.5 sm:py-1 rounded-full shadow-xs tracking-wider uppercase">
                    Habis
                </span>
            @endif
        </div>
        
        <!-- Wishlist Button (Desktop & Mobile) -->
        <div class="product-card__actions absolute top-2.5 right-2.5 sm:top-3 sm:right-3 transition-opacity duration-300 z-10">
            <button 
                type="button"
                data-product-id="{{ $product->id }}"
                onclick="toggleWishlist(this)"
                class="w-8 h-8 sm:w-9 sm:h-9 bg-white/90 backdrop-blur-xs rounded-full flex items-center justify-center text-gray-700 shadow-xs hover:bg-brand-gold hover:text-white transition-colors focus:outline-hidden"
                aria-label="Tambah ke favorit"
            >
                <i class="fa-{{ $isInWishlist ? 'solid' : 'regular' }} fa-heart text-xs sm:text-sm {{ $isInWishlist ? 'text-brand-gold' : '' }}"></i>
            </button>
        </div>
    </div>

    <!-- Product Info -->
    <div class="p-3.5 sm:p-4 flex flex-col flex-1 justify-between gap-3">
        <div>
            <div class="mb-1 text-[9px] sm:text-[10px] font-bold text-brand-gold-dark uppercase tracking-[0.18em]" itemprop="brand" itemscope itemtype="https://schema.org/Brand">
                <span itemprop="name">{{ $product->brand->name ?? 'Unknown Brand' }}</span>
            </div>
            
            <h3 class="product-card__title font-bold text-brand-dark text-xs sm:text-sm leading-snug hover:text-brand-gold-dark transition-colors line-clamp-2" itemprop="name">
                <a href="{{ route('products.show', $product->slug) }}" itemprop="url" class="hover:underline decoration-brand-gold/50 underline-offset-2">
                    {{ $product->name }}
                </a>
            </h3>

            @if($product->tags->isNotEmpty())
                <div class="flex flex-wrap gap-1 mt-2">
                    @foreach($product->tags->take(2) as $tag)
                        <span 
                            class="text-[9px] px-2 py-0.5 bg-gray-50 text-gray-500 rounded-md font-medium border border-gray-100"
                        >
                            {{ $tag->name }}
                        </span>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="pt-2 border-t border-gray-50 space-y-1.5">
            <!-- Rating & Social Proof -->
            <div 
                class="product-card__rating flex items-center gap-1.5 cursor-pointer hover:bg-brand-light/50 p-1 -ml-1 rounded transition-colors w-fit"
                data-product-review="{{ json_encode($reviewPayload, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_HEX_TAG) }}"
                data-product-id="{{ $product->id }}"
                @click="$dispatch('open-review', JSON.parse($el.dataset.productReview))"
            >
                <div class="flex items-center text-amber-500">
                    <svg class="w-3.5 h-3.5 fill-current" viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg"><path d="M12 2.5 15.09 9.26 22.5 9.96 17.25 14.7 18.82 22.03 12 18.55 5.18 22.03 6.75 14.7 1.5 9.96 8.91 9.26 12 2.5Z"/></svg>
                </div>
                <span class="text-xs font-bold text-gray-800 tabular-nums">{{ $reviewPayload['rating'] }}</span>
                <span class="text-[10px] text-gray-400 font-medium">({{ $reviewPayload['reviewsCount'] }})</span>
            </div>

            <!-- Pricing Hierarchy -->
            <div class="flex flex-col" itemprop="offers" itemscope itemtype="https://schema.org/Offer">
                <meta itemprop="priceCurrency" content="IDR" />
                <meta itemprop="price" content="{{ number_format($price, 0, ',', '.') }}" />
                <link itemprop="availability" href="{{ $hasStock ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock' }}" />
                @if($staticPromo)
                    <span class="text-[10px] text-gray-400 line-through tabular-nums">
                        Rp {{ number_format($promoOriginalMinPrice, 0, ',', '.') }}
                        @if($hasPriceRange) - Rp {{ number_format($promoOriginalMaxPrice, 0, ',', '.') }} @endif
                    </span>
                @endif
                <div class="flex items-baseline justify-between gap-1">
                    <span class="font-extrabold text-sm sm:text-base {{ $staticPromo ? 'text-red-600' : 'text-brand-dark' }} tracking-tight font-serif tabular-nums leading-tight">
                        Rp {{ number_format($price, 0, ',', '.') }}
                        @if($hasPriceRange) - Rp {{ number_format($displayOriginalPrice, 0, ',', '.') }} @endif
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>