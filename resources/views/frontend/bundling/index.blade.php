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
                            <h3 class="font-bold text-brand-dark mb-3 text-sm uppercase tracking-wider">Cari Paket</h3>
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
                                        <h3 class="font-bold text-brand-dark mb-3 text-sm uppercase tracking-wider">Cari Paket</h3>
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
            <div x-show="viewMode === 'grid'" class="grid grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3 sm:gap-6">
                @foreach($bundlings as $bundle)
                    @php
                        $discountPercent = ($bundle->total_original > 0 && $bundle->total_price > 0)
                            ? round((($bundle->total_original - $bundle->total_price) / $bundle->total_original) * 100)
                            : 0;
                    @endphp
                    <div class="product-card group relative bg-white border border-gray-100 rounded-2xl overflow-hidden shadow-sm hover:shadow-xl hover:scale-[1.02] hover:-translate-y-1 transition-all duration-300 flex flex-col h-full font-sans">
                        <div class="relative aspect-[4/3] bg-brand-light overflow-hidden">
                            <a href="{{ route('bundling.show', $bundle->slug) }}" class="block w-full h-full">
                                @if($bundle->thumbnail_url)
                                    <img
                                        src="{{ $bundle->thumbnail_url }}"
                                        alt="{{ $bundle->name }}"
                                        class="product-card__image w-full h-full object-cover transition-transform duration-700 group-hover:scale-105"
                                        loading="lazy"
                                    />
                                @else
                                    <div class="w-full h-full flex items-center justify-center bg-gray-200">
                                        <i class="fa-solid fa-gift w-8 h-8 sm:w-12 sm:h-12 text-gray-300"></i>
                                    </div>
                                @endif
                            </a>

                            <!-- Badges -->
                            <div class="absolute top-1.5 left-1.5 sm:top-3 sm:left-3 flex flex-col gap-1 sm:gap-2 z-10">
                                @if($discountPercent > 0)
                                    <span class="bg-red-600 text-white text-[8px] sm:text-[11px] font-bold px-1.5 sm:px-2.5 py-0.5 sm:py-1 rounded-sm shadow-sm tracking-widest sm:tracking-wider uppercase">
                                        Diskon {{ $discountPercent }}%
                                    </span>
                                @endif
                                <span class="bg-purple-600 text-white text-[8px] sm:text-[11px] font-bold px-1.5 sm:px-2.5 py-0.5 sm:py-1 rounded-sm shadow-sm tracking-widest sm:tracking-wider uppercase">
                                    Paket Bundling
                                </span>
                            </div>
                        </div>

                        <!-- Product Info -->
                        <div class="p-3 sm:p-5 flex flex-col flex-1">
                            <div class="mb-1 text-[10px] sm:text-xs font-semibold text-brand-gold-dark uppercase tracking-widest">
                                <span>Paket Spesial</span>
                            </div>

                            <h3 class="product-card__title font-semibold text-brand-dark text-sm sm:text-base leading-snug mb-2 hover:text-brand-gold transition-colors cursor-pointer line-clamp-2">
                                <a href="{{ route('bundling.show', $bundle->slug) }}">
                                    {{ $bundle->name }}
                                </a>
                            </h3>

                            <div class="flex flex-col gap-0.5 mt-auto">
                                @if($bundle->total_original > $bundle->total_price)
                                    <span class="text-[10px] sm:text-xs text-gray-500 line-through">
                                        Rp {{ number_format($bundle->total_original, 0, ',', '.') }}
                                    </span>
                                @endif
                                <span class="font-bold text-sm sm:text-lg text-red-600 tracking-tight">
                                    Rp {{ number_format($bundle->total_price, 0, ',', '.') }}
                                </span>
                            </div>

                            <!-- Action Button -->
                            <div class="mt-3 sm:mt-5 pt-3 sm:pt-4 border-t border-gray-100">
                                <button
                                    type="button"
                                    onclick="addToCartBundling('{{ $bundle->id }}', 1)"
                                    class="product-card__btn w-full py-1.5 sm:py-2.5 rounded-lg sm:rounded-xl font-bold text-[11px] sm:text-sm flex justify-center items-center gap-1.5 bg-white border-2 border-brand-dark text-brand-dark hover:bg-brand-dark hover:text-white group-hover:bg-brand-dark group-hover:text-white shadow-sm transition-all duration-300 focus:outline-none"
                                >
                                    <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4" viewBox="0 0 24 24" fill="currentColor"><path d="M6 6h15l-1 12h-12L4 4H2" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/><circle cx="10" cy="20" r="1" fill="currentColor"/><circle cx="18" cy="20" r="1" fill="currentColor"/></svg>
                                    <span>Tambah ke Keranjang</span>
                                </button>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- List View -->
            <div x-show="viewMode === 'list'" class="flex flex-col gap-4" style="display: none;">
                @foreach($bundlings as $bundle)
                    @php
                        $discountPercent = ($bundle->total_original > 0 && $bundle->total_price > 0)
                            ? round((($bundle->total_original - $bundle->total_price) / $bundle->total_original) * 100)
                            : 0;
                    @endphp
                    <div class="bg-white border border-brand-muted rounded-2xl flex gap-4 overflow-hidden hover:shadow-lg transition-shadow">
                        <div class="w-48 h-48 bg-gray-50 flex-shrink-0 relative">
                            @if($bundle->thumbnail_url)
                                <img src="{{ $bundle->thumbnail_url }}" alt="{{ $bundle->name }}" loading="lazy" decoding="async" class="w-full h-full object-cover" />
                            @else
                                <div class="w-full h-full flex items-center justify-center text-gray-300">
                                    <i class="fa-solid fa-gift w-12 h-12"></i>
                                </div>
                            @endif
                            @if($discountPercent > 0)
                                <div class="absolute top-3 left-3 flex flex-col gap-2">
                                    <span class="bg-brand-dark text-white text-[11px] font-bold px-2.5 py-1 rounded-sm shadow-sm tracking-wider uppercase">
                                        -{{ $discountPercent }}%
                                    </span>
                                </div>
                            @endif
                        </div>
                        <div class="flex-1 p-5 flex flex-col justify-between">
                            <div>
                                <span class="text-xs uppercase font-bold tracking-wider text-gray-400">Bundling</span>
                                <h3 class="font-semibold text-brand-dark text-lg mt-1 line-clamp-2">
                                    <a href="{{ route('bundling.show', $bundle->slug) }}" class="hover:text-brand-gold">{{ $bundle->name }}</a>
                                </h3>
                                <p class="text-sm text-gray-500 mt-2 line-clamp-2">{{ $bundle->description }}</p>
                                <div class="mt-3 flex flex-col gap-0.5">
                                    @if($bundle->total_original > $bundle->total_price)
                                        <span class="text-sm text-gray-500 line-through decoration-gray-300">
                                            Rp {{ number_format($bundle->total_original, 0, ',', '.') }}
                                        </span>
                                    @endif
                                    <span class="font-bold text-lg text-red-600 tracking-tight">
                                        Rp {{ number_format($bundle->total_price, 0, ',', '.') }}
                                    </span>
                                </div>
                            </div>
                            
                            <div class="mt-5 pt-4 border-t border-gray-100 flex gap-3">
                                <a href="{{ route('bundling.show', $bundle->slug) }}" class="px-5 py-2.5 bg-white border-2 border-brand-dark text-brand-dark hover:bg-brand-dark hover:text-white rounded-xl font-bold text-sm transition-all duration-300">
                                    Lihat Detail Paket
                                </a>
                                <button
                                    type="button"
                                    onclick="addToCartBundling('{{ $bundle->id }}', 1)"
                                    class="px-5 py-2.5 bg-brand-dark text-white hover:bg-brand-dark/90 rounded-xl font-bold text-sm transition-colors flex items-center gap-2"
                                >
                                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor"><path d="M6 6h15l-1 12h-12L4 4H2" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/><circle cx="10" cy="20" r="1" fill="currentColor"/><circle cx="18" cy="20" r="1" fill="currentColor"/></svg>
                                    Tambah ke Keranjang
                                </button>
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
