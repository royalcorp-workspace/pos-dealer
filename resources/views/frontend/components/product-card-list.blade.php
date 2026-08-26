<div class="bg-white border border-brand-muted rounded-2xl flex gap-4 overflow-hidden hover:shadow-lg transition-shadow">
    <div class="w-48 h-48 bg-gray-50 flex-shrink-0">
        @if($product->image_url)
            <img src="{{ $product->image_url }}" alt="{{ $product->name }}" loading="lazy" decoding="async" class="w-full h-full object-cover" />
        @else
            <div class="w-full h-full flex items-center justify-center text-gray-300">
                <i class="fa-solid fa-bed w-12 h-12"></i>
            </div>
        @endif
    </div>
    <div class="flex-1 p-4 flex flex-col justify-between">
        <div>
            <span class="text-xs uppercase font-bold tracking-wider text-gray-400">{{ $product->brand->name ?? '' }}</span>
            <h3 class="font-semibold text-brand-dark text-lg mt-1 line-clamp-2">
                <a href="{{ route('products.show', $product->slug) }}" class="hover:text-brand-gold">{{ $product->name }}</a>
            </h3>
            <div class="mt-2 space-y-1">
                @php
                    $listDiscountedPrice = null;
                    $listPctBadge = null;
                    if ($product->discount_type === 'percentage' && $product->discount_value > 0) {
                        $listDiscountedPrice = $product->price - ($product->price * $product->discount_value / 100);
                        $listPctBadge = round($product->discount_value) . '% ' . __('OFF');
                    } elseif ($product->discount_type === 'fixed' && $product->discount_value > 0 && $product->price > 0) {
                        $listDiscountedPrice = $product->price - $product->discount_value;
                        $listPctBadge = round(($product->discount_value / $product->price) * 100) . '% ' . __('OFF');
                    }
                @endphp
                @if($listDiscountedPrice !== null)
                    <div class="flex items-center gap-2">
                        <span class="text-red-600 font-bold text-lg">Rp {{ number_format((float) $listDiscountedPrice, 0, ',', '.') }}</span>
                        <span class="text-gray-400 text-sm line-through">Rp {{ number_format((float) ($product->price ?? 0), 0, ',', '.') }}</span>
                        <span class="text-[10px] bg-red-50 text-red-600 px-1.5 py-0.5 rounded font-bold">{{ $listPctBadge }}</span>
                    </div>
                @else
                    <span class="font-bold text-brand-dark text-lg">Rp {{ number_format((float) ($product->price ?? 0), 0, ',', '.') }}</span>
                @endif
            </div>
        </div>
        
        <div class="mt-4">
            <button 
                type="button"
                onclick="addToCart('{{ $product->id }}')"
                class="px-4 py-2 bg-brand-dark text-brand-gold rounded-lg font-bold text-sm hover:bg-brand-darker transition-colors"
            >
                <i class="fa-solid fa-cart-plus w-4 h-4 mr-2"></i> {{ __('Tambah ke Keranjang') }}
            </button>
        </div>
    </div>
</div>