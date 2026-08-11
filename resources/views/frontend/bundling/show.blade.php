@extends('frontend.layouts.app')

@section('title', $bundle->name . ' - Paket Bundling - IMG')
@section('meta_description', $bundle->description ?? 'Dapatkan paket bundling hemat premium dari IMG. Lebih hemat dengan menggabungkan produk-produk pilihan kasur dan perlengkapan tidur berkualitas.')

@section('content')
@php
    $totalOriginal = (float) $bundle->total_original;
    $totalPrice = (float) $bundle->total_price;
    $discountAmt = $totalOriginal - $totalPrice;
    $discountPct = $totalOriginal > 0 ? round(($discountAmt / $totalOriginal) * 100) : 0;
    $bundleImage = $bundle->thumbnail_url ?: 'https://via.placeholder.com/400x300';
@endphp

<div class="container mx-auto px-4 md:px-6 py-12 font-sans min-h-[70vh]">
    <!-- Breadcrumbs -->
    <div class="mb-8 flex items-center gap-2 text-sm text-gray-500">
        <a href="{{ route('home') }}" class="hover:text-brand-gold transition-colors">Home</a>
        <span class="text-gray-300">/</span>
        <a href="{{ route('bundling.index') }}" class="hover:text-brand-gold transition-colors">Paket Bundling</a>
        <span class="text-gray-300">/</span>
        <span class="text-brand-dark font-medium">{{ $bundle->name }}</span>
    </div>

    <!-- Main Container -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-12 mb-16">
        
        <!-- Left Side: Image Gallery -->
        <div class="lg:col-span-6 space-y-4">
            <div class="relative overflow-hidden rounded-3xl border border-gray-100 bg-white shadow-md aspect-[4/3] sm:aspect-[1/1] flex items-center justify-center group">
                <img
                    src="{{ $bundleImage }}"
                    alt="{{ $bundle->name }}"
                    class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-105"
                    id="bundle-main-image"
                />
                @if($discountPct > 0)
                    <div class="absolute top-4 left-4 bg-red-600 text-white font-bold px-3 py-1.5 rounded-lg text-xs tracking-wider uppercase shadow-md">
                        Hemat {{ $discountPct }}%
                    </div>
                @endif
            </div>
        </div>

        <!-- Right Side: Bundle Description and Order Form -->
        <div class="lg:col-span-6 flex flex-col justify-between space-y-6">
            <div>
                <!-- Brand / Category Badge -->
                <div class="inline-flex items-center gap-1.5 bg-brand-light/50 border border-brand-muted text-brand-gold-dark text-[11px] font-bold tracking-widest uppercase px-3 py-1.5 rounded-full mb-4">
                    <i class="fa-solid fa-gift"></i> Special Bundle Deal
                </div>

                <h1 class="text-3xl md:text-4xl font-extrabold text-brand-dark tracking-tight font-serif mb-4 leading-tight">
                    {{ $bundle->name }}
                </h1>

                <!-- Pricing Block -->
                <div class="bg-gray-50 border border-gray-100 rounded-2xl p-5 mb-6 flex flex-wrap items-baseline gap-4">
                    <div>
                        <span class="text-xs text-gray-400 block uppercase font-bold tracking-wider mb-1">Harga Bundle</span>
                        <span class="font-bold text-3xl text-red-600 tracking-tight">
                            Rp {{ number_format($totalPrice, 0, ',', '.') }}
                        </span>
                    </div>
                    @if($discountAmt > 0)
                        <div class="border-l border-gray-200 pl-4">
                            <span class="text-xs text-gray-400 block uppercase font-bold tracking-wider mb-1">Harga Normal</span>
                            <span class="text-xl text-gray-500 line-through">
                                Rp {{ number_format($totalOriginal, 0, ',', '.') }}
                            </span>
                        </div>
                        <div class="w-full mt-3">
                            <span class="inline-flex items-center gap-1.5 text-xs font-bold text-green-700 bg-green-50 border border-green-200 rounded-lg px-2.5 py-1">
                                <i class="fa-solid fa-circle-check"></i> Lebih hemat Rp {{ number_format($discountAmt, 0, ',', '.') }}
                            </span>
                        </div>
                    @endif
                </div>

                <!-- Description -->
                <div class="prose prose-sm text-gray-600 mb-8 max-w-none">
                    <p class="leading-relaxed">{{ $bundle->description ?? 'Dapatkan kombinasi produk tidur premium pilihan kami dalam satu paket hemat eksklusif.' }}</p>
                </div>

                <!-- Items Included List -->
                <div class="space-y-4 mb-8">
                    <h3 class="font-bold text-brand-dark text-sm uppercase tracking-wider mb-3">Produk dalam Paket Ini:</h3>
                    <div class="grid grid-cols-2 lg:grid-cols-3 gap-3 md:gap-4">
                        @foreach($bundle->items as $item)
                            <div class="flex flex-col md:flex-row items-center md:items-start gap-2 md:gap-4 bg-white border border-gray-100 p-3 rounded-2xl shadow-sm hover:border-brand-gold/30 transition-all text-center md:text-left">
                                <div class="w-16 h-16 md:w-20 md:h-20 rounded-xl bg-gray-50 overflow-hidden flex-shrink-0 border border-gray-100 flex items-center justify-center p-1 mx-auto md:mx-0">
                                    @php
                                        $itemThumb = $item->product?->thumbnail_url ?? 'https://via.placeholder.com/100x100';
                                    @endphp
                                    <img src="{{ $itemThumb }}" alt="{{ $item->product?->name }}" class="max-w-full max-h-full object-contain" />
                                </div>
                                <div class="flex-1 min-w-0 w-full">
                                    <h4 class="font-bold text-gray-800 text-[11px] sm:text-sm truncate w-full" title="{{ $item->product?->name ?? 'Produk Premium' }}">
                                        {{ $item->product?->name ?? 'Produk Premium' }}
                                    </h4>
                                    @if($item->variant)
                                        <p class="text-[9px] sm:text-xs text-brand-gold-dark font-medium mt-0.5">
                                            Varian: {{ $item->variant->variant_name }}
                                        </p>
                                    @endif
                                    <span class="inline-block md:inline-flex items-center gap-1 text-[10px] sm:text-[11px] font-bold text-gray-500 mt-1.5 bg-gray-100 px-2 py-0.5 rounded">
                                        Qty: {{ $item->quantity }}x
                                    </span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Action Area -->
            <div class="pt-6 border-t border-gray-100">
                <button
                    type="button"
                    onclick="addToCartBundling('{{ $bundle->id }}', 1)"
                    class="w-full py-4 rounded-2xl font-bold text-base flex justify-center items-center gap-2 bg-brand-dark text-white hover:bg-brand-gold hover:text-brand-dark transition-all duration-300 shadow-lg shadow-brand-dark/10 hover:shadow-brand-gold/20 transform hover:-translate-y-0.5"
                >
                    <i class="fa-solid fa-cart-shopping"></i>
                    Beli Paket Bundling
                </button>
            </div>
        </div>
    </div>

    <!-- Related Products -->
    @if(isset($relatedProducts) && $relatedProducts->isNotEmpty())
        <div class="mt-16 pt-12 border-t border-gray-100">
            <div class="flex items-center justify-between mb-8">
                <h2 class="text-2xl font-serif font-extrabold text-brand-dark tracking-tight">Rekomendasi Produk Lainnya</h2>
                <a href="{{ route('products.index') }}" class="text-sm font-bold text-brand-gold-dark hover:text-brand-dark transition-colors flex items-center gap-1">
                    Semua Produk <i class="fa-solid fa-arrow-right text-[12px]"></i>
                </a>
            </div>
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-6">
                @foreach($relatedProducts as $product)
                    @include('frontend.components.product-card-dynamic', ['product' => $product])
                @endforeach
            </div>
        </div>
    @endif
</div>
@endsection

@once('bundling-detail-scripts')
@push('scripts')
<script>
function addToCartBundling(bundleId, quantity) {
    // Show loading state
    const btn = document.querySelector('button[onclick*="addToCartBundling"]');
    const origHtml = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Memproses...';

    fetch('{{ route('bundling.add-to-cart') }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json'
        },
        body: JSON.stringify({
            bundling_id: bundleId,
            quantity: quantity
        })
    })
    .then(response => response.json())
    .then(data => {
        btn.disabled = false;
        btn.innerHTML = origHtml;
        
        if (data.success) {
            if (window.updateCartHeader) {
                window.updateCartHeader(data.cart_count || 0, data.cart_total || 0);
            }
            if (window.updateCartDrawer) {
                window.updateCartDrawer(data.cart_drawer_html || '');
            }
            
            // Dispatch event to open cart drawer
            window.dispatchEvent(new CustomEvent('open-cart', { bubbles: true }));
            
            Swal.fire({
                icon: 'success',
                title: 'Berhasil',
                text: 'Paket bundling telah dimasukkan ke keranjang belanja!',
                confirmButtonColor: '#bc9c22'
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Gagal',
                text: data.error || 'Terjadi kesalahan saat menambahkan paket bundling.',
                confirmButtonColor: '#bc9c22'
            });
        }
    })
    .catch(error => {
        btn.disabled = false;
        btn.innerHTML = origHtml;
        console.error('Error adding bundle to cart:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Gagal menghubungi server.',
            confirmButtonColor: '#bc9c22'
        });
    });
}
</script>
@endpush
@endonce
