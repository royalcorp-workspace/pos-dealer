@extends('frontend.layouts.app', ['title' => $priceProductSetting->title])

@section('content')
<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="mb-6">
            <a href="{{ route('price-product-settings.index') }}" class="text-brand-gold hover:text-brand-gold-dark text-sm">&larr; Kembali ke Promo</a>
            <h1 class="text-2xl font-bold text-brand-dark mt-2">{{ $priceProductSetting->title }}</h1>
            <p class="text-gray-600">{{ $priceProductSetting->description }}</p>
            
            @if($priceProductSetting->isVolumeDiscount() && !empty($priceProductSetting->volume_tiers))
                <div class="mt-4 bg-brand-gold/10 p-4 rounded-lg">
                    <h3 class="font-bold text-brand-dark mb-2">Diskon Volume:</h3>
                    <ul class="space-y-1 text-sm">
                        @foreach($priceProductSetting->volume_tiers as $tier)
                            <li>Beli {{ $tier['min_quantity'] ?? 1 }}{{ isset($tier['max_quantity']) ? '-' . $tier['max_quantity'] : '+' }}: 
                                Diskon {{ $tier['discount_value'] ?? 0 }} {{ ($tier['discount_type'] ?? 1) == 1 ? '%' : 'Rp' }}
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            @forelse($products as $product)
                @include('frontend.components.product-card-dynamic', ['product' => $product])
            @empty
                <p class="text-gray-500 col-span-full">Belum ada produk dalam promo ini.</p>
            @endforelse
        </div>

        <div class="mt-6">
            {{ $products->links() }}
        </div>
    </div>
</div>
@endsection