@extends('frontend.layouts.app')

@section('title', 'Paket Bundling - Diskon Hemat')

@section('content')
<div class="py-6 md:py-12 mb-6 min-h-[70vh]" x-data="{ viewMode: localStorage.getItem('bundlingViewMode') || 'grid', isFilterOpen: false }">
    <div class="container mx-auto px-4 md:px-6">
        <!-- Listing Header -->
        <div class="flex flex-col md:flex-row justify-between items-start md:items-end mb-6 md:mb-8 gap-4 border-b border-gray-100 pb-4 md:pb-6 font-sans">
            <div>
                <h1 class="text-xl md:text-4xl font-extrabold text-brand-dark tracking-tight font-serif mb-1 md:mb-2 leading-tight">
                    Paket Bundling
                </h1>
            </div>

            <div class="flex items-center gap-3 font-sans">
                <form method="GET" action="{{ route('bundling.index') }}" class="inline-block" id="sort-form">
                    @foreach(request()->except('sort') as $key => $val)
                        <input type="hidden" name="{{ $key }}" value="{{ $val }}">
                    @endforeach
                    <select name="sort" onchange="this.form.submit()" class="border border-brand-muted rounded-lg px-3 py-2 text-sm font-semibold text-brand-dark bg-white focus:ring-brand-gold focus:border-brand-gold cursor-pointer focus:outline-none">
                        <option value="newest" {{ ($sort ?? '') === 'newest' ? 'selected' : '' }}>Terbaru</option>
                        <option value="price_asc" {{ ($sort ?? '') === 'price_asc' ? 'selected' : '' }}>Harga: Terendah</option>
                        <option value="price_desc" {{ ($sort ?? '') === 'price_desc' ? 'selected' : '' }}>Harga: Tertinggi</option>
                    </select>
                </form>
                <button @click="isFilterOpen = true" class="flex items-center gap-2 px-4 py-2 border border-brand-muted rounded-lg text-sm font-semibold text-brand-dark hover:border-brand-gold transition-colors bg-white focus:outline-none">
                    <i class="fa-solid fa-filter w-4 h-4"></i> Filter
                </button>
                <div class="flex items-center border border-brand-muted rounded-lg overflow-hidden bg-white">
                    <button type="button" @click="viewMode = 'grid'; localStorage.setItem('bundlingViewMode', 'grid')" 
                        :class="{'bg-brand-light text-brand-dark': viewMode === 'grid', 'text-gray-400 hover:text-brand-dark hover:bg-gray-50': viewMode !== 'grid'}" 
                        class="px-3 py-2 focus:outline-none transition-colors" aria-label="Tampilan grid">
                        <i class="fa-solid fa-border-all w-4 h-4"></i>
                    </button>
                    <button type="button" @click="viewMode = 'list'; localStorage.setItem('bundlingViewMode', 'list')" 
                        :class="{'bg-brand-light text-brand-dark': viewMode === 'list', 'text-gray-400 hover:text-brand-dark hover:bg-gray-50': viewMode !== 'list'}" 
                        class="px-3 py-2 focus:outline-none transition-colors" aria-label="Tampilan list">
                        <i class="fa-solid fa-list w-4 h-4"></i>
                    </button>
                </div>
            </div>
        </div>

        <div class="flex flex-col lg:flex-row gap-8">
            <!-- Sidebar Filters (Desktop) -->
            <aside class="hidden lg:block lg:w-64 flex-shrink-0">
                <div class="bg-white border border-brand-muted rounded-2xl p-6 shadow-sm sticky top-6 mb-6">
                    <form method="GET" action="{{ route('bundling.index') }}" class="space-y-6">
                        @if(request('sort'))
                            <input type="hidden" name="sort" value="{{ request('sort') }}">
                        @endif
                        
                        <div>
                            <h3 class="font-bold text-brand-dark mb-3 text-sm uppercase tracking-wider">Cari Bundling</h3>
                            <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari bundling..." class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-brand-gold focus:border-brand-gold focus:outline-none">
                        </div>

                        <div>
                            <h3 class="font-bold text-brand-dark mb-3 text-sm uppercase tracking-wider">Rentang Harga</h3>
                            <div class="grid grid-cols-2 gap-3">
                                <input type="number" name="min_price" placeholder="Min" value="{{ request('min_price') }}"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-brand-gold focus:border-brand-gold">
                                <input type="number" name="max_price" placeholder="Max" value="{{ request('max_price') }}"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-brand-gold focus:border-brand-gold">
                            </div>
                        </div>

                        <div class="flex flex-col gap-2 pt-2">
                            <button type="submit" class="w-full py-2 bg-brand-gold text-brand-dark rounded-lg font-bold text-sm hover:bg-brand-gold/80 transition">
                                Terapkan Filter
                            </button>
                            @if(request()->anyFilled(['search', 'min_price', 'max_price']))
                                <a href="{{ route('bundling.index') }}" class="w-full py-2 border border-gray-300 hover:bg-gray-50 text-gray-500 hover:text-brand-dark font-bold text-sm rounded-lg text-center transition">
                                    Reset Filter
                                </a>
                            @endif
                        </div>
                    </form>
                </div>
            </aside>

            <!-- Mobile Filter Drawer (Mobile) -->
            <div 
                x-show="isFilterOpen" 
                x-cloak 
                class="fixed inset-0 z-[100] overflow-hidden font-sans lg:hidden"
                role="dialog" 
                aria-modal="true"
            >
                <div class="absolute inset-0 overflow-hidden">
                    <div 
                        x-show="isFilterOpen"
                        x-transition:enter="ease-in-out duration-300"
                        x-transition:enter-start="opacity-0"
                        x-transition:enter-end="opacity-100"
                        x-transition:leave="ease-in-out duration-300"
                        x-transition:leave-start="opacity-100"
                        x-transition:leave-end="opacity-0"
                        @click="isFilterOpen = false"
                        class="absolute inset-0 bg-gray-900/40 backdrop-blur-sm transition-opacity"
                    ></div>

                    <div class="fixed inset-y-0 left-0 pr-10 max-w-full flex">
                        <div 
                            x-show="isFilterOpen"
                            x-transition:enter="transform transition ease-in-out duration-300"
                            x-transition:enter-start="-translate-x-full"
                            x-transition:enter-end="translate-x-0"
                            x-transition:leave="transform transition ease-in-out duration-300"
                            x-transition:leave-start="translate-x-0"
                            x-transition:leave-end="-translate-x-full"
                            class="w-screen max-w-xs"
                        >
                            <div class="h-full flex flex-col bg-white shadow-2xl overflow-y-auto p-6">
                                <div class="flex items-center justify-between border-b pb-4 mb-6">
                                    <h2 class="text-lg font-bold text-brand-dark">Filter</h2>
                                    <button @click="isFilterOpen = false" class="text-gray-400 hover:text-gray-600">
                                        <i class="fa-solid fa-xmark text-lg"></i>
                                    </button>
                                </div>
                                <form method="GET" action="{{ route('bundling.index') }}" class="space-y-6">
                                    @if(request('sort'))
                                        <input type="hidden" name="sort" value="{{ request('sort') }}">
                                    @endif
                                    
                                    <div>
                                        <h3 class="font-bold text-brand-dark mb-3 text-sm uppercase tracking-wider">Cari Bundling</h3>
                                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari bundling..." class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-brand-gold focus:border-brand-gold focus:outline-none">
                                    </div>

                                    <div>
                                        <h3 class="font-bold text-brand-dark mb-3 text-sm uppercase tracking-wider">Rentang Harga</h3>
                                        <div class="grid grid-cols-2 gap-3">
                                            <input type="number" name="min_price" placeholder="Min" value="{{ request('min_price') }}"
                                                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-brand-gold focus:border-brand-gold">
                                            <input type="number" name="max_price" placeholder="Max" value="{{ request('max_price') }}"
                                                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-brand-gold focus:border-brand-gold">
                                        </div>
                                    </div>

                                    <div class="flex flex-col gap-2 pt-2">
                                        <button type="submit" class="w-full py-2.5 bg-brand-gold text-brand-dark rounded-lg font-bold text-sm hover:bg-brand-gold/80 transition">
                                            Terapkan Filter
                                        </button>
                                        @if(request()->anyFilled(['search', 'min_price', 'max_price']))
                                            <a href="{{ route('bundling.index') }}" class="w-full py-2.5 border border-gray-300 hover:bg-gray-50 text-gray-500 hover:text-brand-dark font-bold text-sm rounded-lg text-center transition">
                                                Reset Filter
                                            </a>
                                        @endif
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="flex-1">
                @if($bundlings->isEmpty())
                    <div class="bg-white border border-brand-muted rounded-2xl p-12 text-center shadow-sm font-sans">
                        <div class="w-20 h-20 bg-brand-light rounded-full flex items-center justify-center text-brand-gold mx-auto mb-4">
                            <i class="fa-solid fa-gift w-10 h-10"></i>
                        </div>
                        <h2 class="text-xl font-bold text-brand-dark mb-2">Paket Tidak Ditemukan</h2>
                        <p class="text-gray-500 max-w-md mx-auto">
                            Maaf, kami belum memiliki paket bundling untuk kriteria pencarian Anda. Silakan lihat pilihan lain.
                        </p>
                    </div>
                @else
            <!-- Grid View -->
            <div x-show="viewMode === 'grid'" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 sm:gap-6">
                @foreach($bundlings as $bundle)
                    @php
                        $bundleImg = $bundle->thumbnail_url ?: ($bundle->banner_image ? cms_asset($bundle->banner_image) : 'https://via.placeholder.com/400x300');
                        $savings = (float)($bundle->total_savings ?? 0);
                    @endphp
                    <div class="product-card group relative bg-white border-2 border-amber-100 hover:border-amber-400 rounded-3xl overflow-hidden shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-300 flex flex-col h-full font-sans">
                        <!-- Poster Visual Area -->
                        <div class="relative aspect-[4/3] bg-gradient-to-br from-amber-50 to-gray-50 overflow-hidden">
                            <a href="{{ route('bundling.show', $bundle->slug) }}" class="block w-full h-full">
                                <img
                                    src="{{ $bundleImg }}"
                                    alt="{{ $bundle->name }}"
                                    class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-105"
                                    loading="lazy"
                                />
                            </a>

                            <!-- Floating Badges -->
                            <div class="absolute top-2.5 left-2.5 flex flex-col gap-1.5 z-10">
                                <span class="bg-gradient-to-r from-amber-600 to-amber-500 text-white text-[10px] sm:text-xs font-black px-2.5 py-1 rounded-lg shadow-md uppercase tracking-wider flex items-center gap-1.5">
                                    <i class="fa-solid fa-tags text-[9px]"></i> PROMO BUNDLING
                                </span>
                                @if($savings > 0)
                                    <span class="bg-emerald-600 text-white text-[10px] sm:text-xs font-black px-2.5 py-1 rounded-lg shadow-md uppercase tracking-wider">
                                        HEMAT Rp {{ number_format($savings, 0, ',', '.') }}
                                    </span>
                                @endif
                            </div>
                        </div>

                        <!-- Poster Content -->
                        <div class="p-4 sm:p-5 flex flex-col flex-1">
                            <div class="mb-1 text-[10px] sm:text-xs font-extrabold text-amber-700 uppercase tracking-widest flex items-center gap-1">
                                <i class="fa-solid fa-sparkles text-amber-500"></i> Paket Spesial Hemat
                            </div>

                            <h3 class="font-extrabold text-brand-dark text-sm sm:text-base leading-snug mb-2 hover:text-amber-700 transition-colors line-clamp-2">
                                <a href="{{ route('bundling.show', $bundle->slug) }}">
                                    {{ $bundle->name }}
                                </a>
                            </h3>

                            <!-- Combo Preview Highlights -->
                            <div class="space-y-1.5 my-2 p-2.5 bg-amber-50/50 rounded-xl border border-amber-100 text-xs">
                                <div class="text-gray-700 flex items-center gap-1.5 font-medium truncate">
                                    <i class="fa-solid fa-bed text-amber-700 text-[11px] shrink-0"></i>
                                    <span class="truncate">Utama: <strong>{{ $bundle->main_product?->name ?? 'Kasur Pilihan' }}</strong></span>
                                </div>
                                @if($bundle->suggest_items && $bundle->suggest_items->isNotEmpty())
                                    <div class="text-amber-900 flex items-center gap-1.5 font-medium truncate">
                                        <i class="fa-solid fa-gift text-emerald-600 text-[11px] shrink-0"></i>
                                        <span class="truncate">Pelengkap: <strong>{{ $bundle->suggest_items->pluck('product.name')->filter()->join(', ') }}</strong></span>
                                    </div>
                                @endif
                            </div>

                            <!-- Pricing Block -->
                            <div class="flex flex-col gap-0.5 mt-auto pt-2">
                                <span class="text-[10px] uppercase font-bold text-gray-400 tracking-wider">Mulai Dari (Total Paket)</span>
                                <div class="flex items-baseline gap-2">
                                    <span class="font-black text-base sm:text-xl text-amber-900 tracking-tight">
                                        Rp {{ number_format($bundle->start_price ?? 0, 0, ',', '.') }}
                                    </span>
                                    @if(($bundle->start_original_price ?? 0) > ($bundle->start_price ?? 0))
                                        <span class="text-[11px] sm:text-xs text-gray-400 line-through">
                                            Rp {{ number_format($bundle->start_original_price, 0, ',', '.') }}
                                        </span>
                                    @endif
                                </div>
                            </div>

                            <!-- Eyecatching Action CTA Button -->
                            <div class="mt-4 pt-3 border-t border-gray-100 space-y-1.5">
                                @if(!empty($bundle->main_product_slug))
                                    <a
                                        href="{{ route('products.show', $bundle->main_product_slug) }}#bundling-addon"
                                        class="w-full py-2.5 sm:py-3 rounded-xl font-extrabold text-xs sm:text-sm flex justify-center items-center gap-2 bg-gradient-to-r from-amber-600 via-amber-500 to-amber-600 hover:from-amber-500 hover:to-amber-400 text-white transition-all duration-300 shadow-md shadow-amber-500/20 transform hover:-translate-y-0.5"
                                    >
                                        <i class="fa-solid fa-cart-shopping text-white text-xs"></i>
                                        <span>Ambil Promo Bundling</span>
                                        <i class="fa-solid fa-arrow-right text-[10px]"></i>
                                    </a>
                                @else
                                    <a
                                        href="{{ route('bundling.show', $bundle->slug) }}"
                                        class="w-full py-2.5 sm:py-3 rounded-xl font-bold text-xs sm:text-sm flex justify-center items-center gap-2 bg-brand-dark text-white hover:bg-brand-gold hover:text-brand-dark transition-all duration-300 shadow-md"
                                    >
                                        <span>Lihat Detail Promo</span>
                                        <i class="fa-solid fa-arrow-right text-[10px]"></i>
                                    </a>
                                @endif
                                <a href="{{ route('bundling.show', $bundle->slug) }}" class="text-[11px] text-center text-gray-500 hover:text-amber-800 font-semibold block transition">
                                    Detail Rincian Paket →
                                </a>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- List View -->
            <div x-show="viewMode === 'list'" class="flex flex-col gap-4" style="display: none;">
                @foreach($bundlings as $bundle)
                    @php
                        $bundleImg = $bundle->thumbnail_url ?: ($bundle->banner_image ? cms_asset($bundle->banner_image) : 'https://via.placeholder.com/400x300');
                        $savings = (float)($bundle->total_savings ?? 0);
                    @endphp
                    <div class="bg-white border-2 border-amber-100 hover:border-amber-400 rounded-3xl flex flex-col sm:flex-row gap-4 overflow-hidden shadow-sm hover:shadow-xl transition-all p-3 sm:p-0">
                        <div class="w-full sm:w-60 aspect-[4/3] sm:aspect-auto bg-gray-50 flex-shrink-0 relative overflow-hidden rounded-2xl sm:rounded-none">
                            <a href="{{ route('bundling.show', $bundle->slug) }}" class="block w-full h-full">
                                <img src="{{ $bundleImg }}" alt="{{ $bundle->name }}" loading="lazy" decoding="async" class="w-full h-full object-cover" />
                            </a>
                            <div class="absolute top-3 left-3 flex flex-col gap-1.5">
                                <span class="bg-amber-600 text-white text-[10px] font-black px-2.5 py-1 rounded-lg shadow-md uppercase tracking-wider">
                                    PROMO BUNDLING
                                </span>
                                @if($savings > 0)
                                    <span class="bg-emerald-600 text-white text-[10px] font-bold px-2 py-0.5 rounded-lg shadow-md uppercase tracking-wider">
                                        HEMAT Rp {{ number_format($savings, 0, ',', '.') }}
                                    </span>
                                @endif
                            </div>
                        </div>
                        <div class="flex-1 p-3 sm:p-6 flex flex-col justify-between">
                            <div>
                                <span class="text-xs uppercase font-extrabold tracking-wider text-amber-700">Paket Hemat Spesial</span>
                                <h3 class="font-extrabold text-brand-dark text-lg sm:text-xl mt-1 line-clamp-2">
                                    <a href="{{ route('bundling.show', $bundle->slug) }}" class="hover:text-amber-700">{{ $bundle->name }}</a>
                                </h3>
                                @if($bundle->description)
                                    <p class="text-xs sm:text-sm text-gray-500 mt-2 line-clamp-2">{{ $bundle->description }}</p>
                                @endif

                                <div class="space-y-1 my-3 p-3 bg-amber-50/50 rounded-xl border border-amber-100 text-xs">
                                    <div class="text-gray-700 font-medium">🛏️ Produk Utama: <strong>{{ $bundle->main_product?->name ?? 'Kasur Pilihan' }}</strong> ({{ $bundle->main_price_range_text }})</div>
                                    @if($bundle->suggest_items && $bundle->suggest_items->isNotEmpty())
                                        <div class="text-amber-900 font-medium">🎁 Produk Pelengkap Diskon: <strong>{{ $bundle->suggest_items->pluck('product.name')->filter()->join(', ') }}</strong></div>
                                    @endif
                                </div>
                            </div>

                            <div class="mt-3 pt-3 border-t border-gray-100 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                                <div>
                                    <span class="text-[10px] uppercase font-bold text-gray-400 block">Mulai Dari (Total Paket)</span>
                                    <div class="flex items-baseline gap-2">
                                        <span class="font-black text-xl text-amber-900">
                                            Rp {{ number_format($bundle->start_price ?? 0, 0, ',', '.') }}
                                        </span>
                                        @if(($bundle->start_original_price ?? 0) > ($bundle->start_price ?? 0))
                                            <span class="text-xs text-gray-400 line-through">
                                                Rp {{ number_format($bundle->start_original_price, 0, ',', '.') }}
                                            </span>
                                        @endif
                                    </div>
                                </div>
                                <div class="flex items-center gap-2 w-full sm:w-auto">
                                    <a href="{{ route('bundling.show', $bundle->slug) }}" class="px-4 py-2.5 bg-white border border-gray-200 text-gray-700 hover:text-amber-700 rounded-xl font-bold text-xs transition">
                                        Lihat Poster
                                    </a>
                                    @if(!empty($bundle->main_product_slug))
                                        <a href="{{ route('products.show', $bundle->main_product_slug) }}#bundling-addon" class="flex-1 sm:flex-none px-5 py-2.5 bg-gradient-to-r from-amber-600 via-amber-500 to-amber-600 hover:from-amber-500 hover:to-amber-400 text-white rounded-xl font-bold text-xs transition flex items-center justify-center gap-1.5 shadow-md shadow-amber-500/20 transform hover:-translate-y-0.5">
                                            <i class="fa-solid fa-cart-shopping text-white text-xs"></i>
                                            <span>Ambil Promo & Beli</span>
                                            <i class="fa-solid fa-arrow-right text-[10px]"></i>
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-8">
                {{ $bundlings->links('frontend.components.pagination') }}
            </div>
        @endif
            </div>
        </div>
    </div>
</div>

@once('bundling-scripts')
@push('scripts')
<script>
function addToCartBundling(bundleId, quantity) {
    if (window.showLoading) window.showLoading();
    fetch('{{ route('bundling.add-to-cart') }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Content-Type': 'application/json',
            'Accept': 'application/json',
        },
        body: JSON.stringify({ bundling_id: bundleId, quantity: quantity })
    })
    .then(r => r.json())
    .then(data => {
        if (window.hideLoading) window.hideLoading();
        if (data.success) {
            if (window.updateCartHeader) {
                window.updateCartHeader(data.cart_count || 0, data.cart_total || 0);
            }
            if (window.updateCartDrawer) {
                window.updateCartDrawer(data.cart_drawer_html || '');
            }
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
                text: data.error || 'Gagal menambahkan bundle ke keranjang',
                confirmButtonColor: '#bc9c22'
            });
        }
    })
    .catch(err => {
        if (window.hideLoading) window.hideLoading();
        console.error(err);
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
@endsection
