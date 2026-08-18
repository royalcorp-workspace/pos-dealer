@props(['product'])

@php
    $isSoldOut = $product['isSoldOut'] ?? false;
    $isVariable = $product['isVariable'] ?? false;
    $reviewProduct = $product;
    $reviewProduct['reviews'] = $reviewProduct['reviews'] ?? [];
    $reviewProduct['reviewsCount'] = $reviewProduct['reviewsCount'] ?? 0;
    $reviewProduct['originalPrice'] = $reviewProduct['originalPrice'] ?? ($reviewProduct['isVariable'] ? ($reviewProduct['minPrice'] ?? $reviewProduct['price']) : $reviewProduct['price']);
    $reviewProduct['originalMinPrice'] = $reviewProduct['originalMinPrice'] ?? $reviewProduct['originalPrice'];
    $reviewProduct['originalMaxPrice'] = $reviewProduct['originalMaxPrice'] ?? $reviewProduct['originalPrice'];
    $reviewProduct['hasDiscount'] = isset($reviewProduct['originalPrice']) && ($reviewProduct['originalPrice'] != $reviewProduct['price'] || isset($reviewProduct['discountBadge']));
    $reviewProduct['discountLabel'] = $reviewProduct['discountLabel'] ?? ($reviewProduct['discountBadge'] ? trim(str_replace('Hot', '', $reviewProduct['discountBadge'])) : null);
    $hasPriceRange = $isVariable && isset($product['minPrice']) && isset($product['maxPrice']) && $product['minPrice'] != $product['maxPrice'];
@endphp

<div 
    class="product-card group relative bg-white border border-gray-100 rounded-2xl overflow-hidden shadow-sm hover:shadow-xl hover:scale-[1.02] hover:-translate-y-1 transition-all duration-300 flex flex-col h-full font-sans {{ $isSoldOut ? 'opacity-80' : '' }}"
>
    <!-- Product Image Container -->
    <div class="relative aspect-[4/3] bg-brand-light overflow-hidden">
        <a href="{{ route('products.show', $product['id']) }}" class="block w-full h-full">
            <img 
                src="{{ $product['image'] }}" 
                alt="{{ $product['name'] }}" 
                loading="lazy"
                decoding="async"
                class="product-card__image w-full h-full object-cover transition-transform duration-700 group-hover:scale-105 {{ $isSoldOut ? 'grayscale' : '' }}"
                onerror="this.onerror=null;this.src='{{ asset('images/dummy/header.jpg') }}';"
            />
        </a>
        
        <!-- Badges -->
        <div class="absolute top-3 left-3 flex flex-col gap-2">
            @if(isset($product['discountBadge']) && !$isSoldOut)
                <span class="bg-brand-dark text-white text-[11px] font-bold px-2.5 py-1 rounded-sm shadow-sm tracking-wider uppercase">
                    {{ $product['discountBadge'] }}
                </span>
            @endif
            @if($isSoldOut)
                <span class="bg-gray-800 text-white text-[11px] font-bold px-2.5 py-1 rounded-sm shadow-sm tracking-wider uppercase">
                    Sold Out
                </span>
            @endif
        </div>
        
        <!-- Hover Action (Desktop) -->
        @if(!$isSoldOut)
            <div class="product-card__actions absolute top-3 right-3 opacity-0 group-hover:opacity-100 transition-opacity translate-x-4 group-hover:translate-x-0 duration-300 flex flex-col gap-2 z-10">
                <button class="w-10 h-10 bg-white rounded-full flex items-center justify-center text-gray-700 shadow-md hover:bg-brand-gold hover:text-white transition-colors focus:outline-none" aria-label="Tambah ke favorit">
                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg"><path d="M12 2.5 15.09 9.26 22.5 9.96 17.25 14.7 18.82 22.03 12 18.55 5.18 22.03 6.75 14.7 1.5 9.96 8.91 9.26 12 2.5Z"/></svg>
                </button>
                <button 
                    data-product-review="{{ json_encode($reviewProduct, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_HEX_TAG) }}"
                    data-product-id="{{ $product['id'] }}"
                    @click="$dispatch('open-review', JSON.parse($el.dataset.productReview))"
                    class="w-10 h-10 bg-white rounded-full flex items-center justify-center text-gray-700 shadow-md hover:bg-brand-gold hover:text-white transition-colors focus:outline-none"
                    aria-label="Lihat ulasan"
                >
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </button>
            </div>
        @endif
    </div>

    <!-- Product Info -->
    <div class="p-5 flex flex-col flex-1">
        <div class="mb-1 text-xs font-semibold text-gray-600 uppercase tracking-widest">
            {{ $product['brand'] }}
        </div>
        
        <h3 class="product-card__title font-semibold text-brand-dark text-base leading-snug mb-3 hover:text-brand-gold transition-colors cursor-pointer line-clamp-2">
            <a href="{{ route('products.show', $product['id']) }}">
                {{ $product['name'] }}
            </a>
        </h3>
        
        <!-- Rating -->
        <div 
            class="product-card__rating flex items-center gap-1.5 mb-auto cursor-pointer hover:bg-brand-light p-1 -ml-1 rounded transition-colors w-fit"
            data-product-review="{{ json_encode($product, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_HEX_TAG) }}"
            data-product-id="{{ $product['id'] }}"
            @click="$dispatch('open-review', JSON.parse($el.dataset.productReview))"
        >
            <div class="flex items-center text-brand-gold-dark">
                <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg"><path d="M12 2.5 15.09 9.26 22.5 9.96 17.25 14.7 18.82 22.03 12 18.55 5.18 22.03 6.75 14.7 1.5 9.96 8.91 9.26 12 2.5Z"/></svg>
            </div>
            <span class="text-sm font-medium text-gray-700">{{ $product['rating'] }}</span>
            <span class="text-xs text-gray-500 hover:text-brand-gold-dark underline-offset-2 hover:underline">({{ $product['reviewsCount'] }} Ulasan)</span>
        </div>

        <!-- Pricing -->
        <div class="flex flex-col gap-0.5 mt-2">
            @if($isVariable && $hasPriceRange)
                <span class="text-sm font-medium text-gray-500">Rentang Harga</span>
                <span class="font-bold text-lg text-brand-dark tracking-tight">
                    Rp {{ number_format($product['minPrice'], 0, ',', '.') }} - Rp {{ number_format($product['maxPrice'], 0, ',', '.') }}
                </span>
            @else
                @if(isset($product['originalPrice']) && $product['originalPrice'] != ($product['price'] ?? $product['minPrice'] ?? 0))
                    <span class="text-sm text-gray-500 line-through decoration-gray-300">
                        Rp {{ number_format($product['originalPrice'], 0, ',', '.') }}
                    </span>
                @endif
                <span class="font-bold text-lg text-brand-dark tracking-tight">
                    Rp {{ number_format($product['price'] ?? $product['minPrice'] ?? 0, 0, ',', '.') }}
                </span>
            @endif
        </div>

        <!-- Action Button -->
        <div class="mt-5 pt-4 border-t border-gray-100">
            @if($isSoldOut)
                <button 
                    disabled
                    class="w-full py-2.5 rounded-xl font-bold text-sm flex justify-center items-center gap-2 bg-gray-100 text-gray-400 cursor-not-allowed"
                >
                    Sold Out
                </button>
            @elseif($isVariable)
                <a 
                    href="{{ route('products.show', $product['id']) }}"
                    class="product-card__btn w-full py-2.5 rounded-xl font-bold text-sm flex justify-center items-center gap-2 bg-white border-2 border-brand-dark text-brand-dark hover:bg-brand-dark hover:text-white group-hover:bg-brand-dark group-hover:text-white shadow-sm transition-all duration-300 text-center"
                >
                    Pilih Opsi
                </a>
            @else
                <form action="{{ route('cart.add') }}" method="POST">
                    @csrf
                    <input type="hidden" name="product_id" value="{{ $product['id'] }}">
                    <input type="hidden" name="quantity" value="1">
                    <button 
                        type="submit"
                        class="product-card__btn w-full py-2.5 rounded-xl font-bold text-sm flex justify-center items-center gap-2 bg-white border-2 border-brand-dark text-brand-dark hover:bg-brand-dark hover:text-white group-hover:bg-brand-dark group-hover:text-white shadow-sm transition-all duration-300 focus:outline-none"
                    >
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg"><path d="M6 6h15l-1.5 9h-12L4 4H2" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/><circle cx="10" cy="20" r="1" fill="currentColor"/><circle cx="18" cy="20" r="1" fill="currentColor"/></svg>
                        Tambah ke Keranjang
                    </button>
                </form>
            @endif
        </div>
    </div>
</div>
