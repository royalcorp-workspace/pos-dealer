@props(['product'])

@php
    $isVariable = $product->variants->isNotEmpty();
    $hasStock = $product->variants->sum('stock_qty') > 0;
    $isSoldOut = !$isVariable ? false : !$hasStock;
    $minPrice = $product->variants->min('price');
    $maxPrice = $product->variants->max('price');
    $price = $isVariable && $minPrice ? $minPrice : $product->base_price ?? 0;
    $originalPrice = $isVariable && $maxPrice && $minPrice !== $maxPrice ? $maxPrice : null;
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
                class="product-card__image w-full h-full object-cover transition-transform duration-700 group-hover:scale-105 {{ $isSoldOut ? 'grayscale' : '' }}"
            />
        </a>
        
        <!-- Badges -->
        <div class="absolute top-3 left-3 flex flex-col gap-2">
            @if($product->best_seller)
                <span class="bg-brand-dark text-white text-[11px] font-bold px-2.5 py-1 rounded-sm shadow-sm tracking-wider uppercase">
                    Best Seller
                </span>
            @endif
            @if($product->is_new)
                <span class="bg-brand-gold text-brand-dark text-[11px] font-bold px-2.5 py-1 rounded-sm shadow-sm tracking-wider uppercase">
                    New
                </span>
            @endif
            @if($isSoldOut)
                <span class="bg-gray-800 text-white text-[11px] font-bold px-2.5 py-1 rounded-sm shadow-sm tracking-wider uppercase">
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
                <i class="fa-regular fa-heart w-4 h-4"></i>
            </button>
            <button 
                type="button"
                class="w-10 h-10 bg-white rounded-full flex items-center justify-center text-gray-700 shadow-md hover:bg-brand-gold hover:text-white transition-colors focus:outline-none"
                aria-label="Lihat ulasan"
            >
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </button>
        </div>
    </div>

    <!-- Product Info -->
    <div class="p-5 flex flex-col flex-1">
        <div class="mb-1 text-xs font-semibold text-brand-gold-dark uppercase tracking-widest" itemprop="brand" itemscope itemtype="https://schema.org/Brand">
            <span itemprop="name">{{ $product->brand->name ?? 'Unknown Brand' }}</span>
        </div>
        
        <h3 class="product-card__title font-semibold text-brand-dark text-base leading-snug mb-2 hover:text-brand-gold transition-colors cursor-pointer line-clamp-2" itemprop="name">
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
        <div class="flex items-center gap-1.5 mb-auto cursor-pointer hover:bg-brand-light p-1 -ml-1 rounded transition-colors w-fit">
            <div class="flex items-center text-brand-gold-dark">
                <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg"><path d="M12 2.5 15.09 9.26 22.5 9.96 17.25 14.7 18.82 22.03 12 18.55 5.18 22.03 6.75 14.7 1.5 9.96 8.91 9.26 12 2.5Z"/></svg>
            </div>
            <span class="text-sm font-medium text-gray-700">{{ $product->rating ?? 0 }}</span>
            <span class="text-xs text-gray-500 hover:text-brand-gold-dark underline-offset-2 hover:underline">(0 Ulasan)</span>
        </div>

        <!-- Pricing -->
        <div class="flex flex-col gap-0.5 mt-2" itemprop="offers" itemscope itemtype="https://schema.org/Offer">
            <meta itemprop="priceCurrency" content="IDR" />
            <meta itemprop="price" content="{{ number_format($price, 0, ',', '.') }}" />
            <link itemprop="availability" href="{{ $hasStock ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock' }}" />
            <span class="font-bold text-lg text-brand-dark tracking-tight">
                Rp {{ number_format($price, 0, ',', '.') }}
            </span>
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
                    href="{{ route('products.show', $product->slug) }}"
                    class="product-card__btn w-full py-2.5 rounded-xl font-bold text-sm flex justify-center items-center gap-2 bg-white border-2 border-brand-dark text-brand-dark hover:bg-brand-dark hover:text-white group-hover:bg-brand-dark group-hover:text-white shadow-sm transition-all duration-300 text-center"
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
                        class="product-card__btn w-full py-2.5 rounded-xl font-bold text-sm flex justify-center items-center gap-2 bg-white border-2 border-brand-dark text-brand-dark hover:bg-brand-dark hover:text-white group-hover:bg-brand-dark group-hover:text-white shadow-sm transition-all duration-300 focus:outline-none"
                    >
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg"><path d="M6 6h15l-1 12h-12L4 4H2" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/><circle cx="10" cy="20" r="1" fill="currentColor"/><circle cx="18" cy="20" r="1" fill="currentColor"/></svg>
                        Tambah ke Keranjang
                    </button>
                </form>
            @endif
        </div>
    </div>
</div>