@props(['product'])

@php
    $validVariants = $product->variants ? $product->variants->filter(fn($v) => (float)$v->sell_price > 0)->values() : collect([]);
    $isVariable = $validVariants->isNotEmpty();
    
    $minVariant = $isVariable ? $validVariants->sortBy(fn($v) => (float)$v->sell_price)->first() : null;
    $maxVariant = $isVariable ? $validVariants->sortByDesc(fn($v) => (float)$v->sell_price)->first() : null;

    $originalMinPrice = $minVariant ? (float)$minVariant->sell_price : (float)($product->price ?? 0);
    $originalMaxPrice = $maxVariant ? (float)$maxVariant->sell_price : $originalMinPrice;
    
    $positiveBaseVariants = $validVariants->filter(fn($v) => (float)$v->base_price > 0);
    $originalMinBasePrice = ($minVariant && (float)$minVariant->base_price > 0)
        ? (float)$minVariant->base_price
        : (float)($positiveBaseVariants->min('base_price') ?? $originalMinPrice);
    $originalMaxBasePrice = ($maxVariant && (float)$maxVariant->base_price > 0)
        ? (float)$maxVariant->base_price
        : (float)($positiveBaseVariants->max('base_price') ?? $originalMaxPrice);

    $hasPriceRange = $isVariable && $originalMinPrice > 0 && $originalMaxPrice > 0 && $originalMinPrice !== $originalMaxPrice;
    
    $hasDefaultDiscount = $originalMinBasePrice > $originalMinPrice;
    $defaultDiscountPct = $hasDefaultDiscount ? round((($originalMinBasePrice - $originalMinPrice) / $originalMinBasePrice) * 100) : 0;

    $staticPromo = \App\Services\StaticPromoService::forProduct($product, $originalMinPrice);
    
    $price = $originalMinPrice;
    $displayOriginalPrice = $hasPriceRange ? $originalMaxPrice : null;
    
    $strikeMinPrice = $hasDefaultDiscount ? $originalMinBasePrice : null;
    $strikeMaxPrice = $hasDefaultDiscount && $hasPriceRange ? $originalMaxBasePrice : null;

    $defaultDiscountBadge = $hasDefaultDiscount && $defaultDiscountPct > 0 ? $defaultDiscountPct . '% OFF' : null;
    $ppsDiscountBadge = null;
    $ppsDiscountPct = 0;

    if ($staticPromo) {
        $price = \App\Services\StaticPromoService::discountedPrice($originalMinPrice, $staticPromo);
        $displayOriginalPrice = $hasPriceRange ? \App\Services\StaticPromoService::discountedPrice($originalMaxPrice, $staticPromo) : null;
        
        $strikeMinPrice = $hasDefaultDiscount ? $originalMinBasePrice : $originalMinPrice;
        $strikeMaxPrice = $hasPriceRange ? ($hasDefaultDiscount ? $originalMaxBasePrice : $originalMaxPrice) : null;
        
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
    
    
    $isSoldOut = false;
    $hasStock = true;
    
    // Data untuk review component
    $reviewPayload = [
        'id' => $product->id,
        'name' => $product->name,
        'image' => $product->thumbnail_url ?? '',
        'brand' => $product->brand->name ?? '',
        'rating' => number_format($product->average_rating ?? 0, 1),
        'reviewsCount' => $product->review_count ?? 0,
        'slug' => $product->slug,
    ];
@endphp



<div class="bg-white border border-brand-muted rounded-2xl flex gap-4 overflow-hidden hover:shadow-lg transition-shadow relative">
    <div class="w-48 h-48 bg-gray-50 flex-shrink-0 relative">
        <a href="{{ route('products.show', $product->slug) }}" class="block w-full h-full">
            <img src="{{ $product->thumbnail_url ?? asset('images/dummy/header.jpg') }}" alt="{{ $product->name }}" loading="lazy" decoding="async" class="w-full h-full object-cover" />
        </a>
        <div class="absolute top-1.5 left-1.5 flex flex-col gap-1 z-10">
            @if($defaultDiscountBadge)
                <span class="bg-red-600 text-white text-[9px] font-bold px-1.5 py-0.5 rounded-sm shadow-sm tracking-widest uppercase">
                    {{ $defaultDiscountBadge }}
                </span>
            @endif

            @if($product->best_seller)
                <span class="bg-brand-dark text-white text-[9px] font-bold px-1.5 py-0.5 rounded-sm shadow-sm tracking-widest uppercase">
                    {{ __('Best Seller') }}
                </span>
            @endif
            @if($product->is_new)
                <span class="bg-brand-gold text-brand-dark text-[9px] font-bold px-1.5 py-0.5 rounded-sm shadow-sm tracking-widest uppercase">
                    {{ __('New') }}
                </span>
            @endif
            @if($product->is_bundle)
                <span class="bg-purple-600 text-white text-[9px] font-bold px-1.5 py-0.5 rounded-sm shadow-sm tracking-widest uppercase">
                    {{ __('Bundling') }}
                </span>
            @endif
        </div>

    </div>
    <div class="flex-1 p-4 flex flex-col justify-between">
        <div>
            <span class="text-xs uppercase font-bold tracking-wider text-gray-400">{{ $product->brand->name ?? '' }}</span>
            <h3 class="font-semibold text-brand-dark text-lg mt-1 line-clamp-2">
                <a href="{{ route('products.show', $product->slug) }}" class="hover:text-brand-gold">{{ $product->name }}</a>
            </h3>
            <div class="mt-2 space-y-1">
                @if(($hasDefaultDiscount || $staticPromo) && $strikeMinPrice)
                    <div class="flex items-center gap-2">
                        <span class="text-red-600 font-bold text-lg">Rp {{ number_format($price, 0, ',', '.') }}@if($hasPriceRange) - Rp {{ number_format($displayOriginalPrice, 0, ',', '.') }}@endif</span>
                        <span class="text-gray-400 text-sm line-through">Rp {{ number_format($strikeMinPrice, 0, ',', '.') }}@if($hasPriceRange && $strikeMaxPrice) - Rp {{ number_format($strikeMaxPrice, 0, ',', '.') }}@endif</span>
                        @if($defaultDiscountBadge)
                            <span class="text-[10px] bg-red-50 text-red-600 px-1.5 py-0.5 rounded font-bold">{{ $defaultDiscountBadge }}</span>
                        @endif
                    </div>
                @else
                    <span class="font-bold text-brand-dark text-lg">Rp {{ number_format($price, 0, ',', '.') }}@if($hasPriceRange) - Rp {{ number_format($displayOriginalPrice, 0, ',', '.') }}@endif</span>
                @endif
            </div>
        </div>
        
        

    </div>
        @if($ppsDiscountBadge)
        <div class="absolute top-0 right-4 sm:right-6 z-10 flex justify-center">
            <div class="bg-red-600 text-white text-[8px] sm:text-[10px] font-extrabold px-2 sm:px-3 pt-2 sm:pt-3 pb-3 sm:pb-4 rounded-b-md shadow-md text-center leading-tight">
                EXTRA<br/><span class="text-[10px] sm:text-xs">{{ $ppsDiscountPct }}%</span><br/>OFF
            </div>
        </div>
        @endif
</div>