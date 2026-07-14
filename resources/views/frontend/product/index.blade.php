@extends('frontend.layouts.app')

@php
    $title = 'Semua Produk';
    if ($filterType === 'brand' && $filterValue) {
        $brand = $brands->first(fn($b) => $b->slug === $filterValue);
        $title = $brand ? 'Brand: ' . $brand->name : 'Brand: ' . $filterValue;
    } elseif ($filterType === 'category' && $filterValue) {
        $category = $categories->first(fn($c) => $c->slug === $filterValue);
        $title = $category ? 'Kategori: ' . $category->name : 'Kategori: ' . $filterValue;
    } elseif ($filterType === 'search' && $filterValue) {
        $title = 'Pencarian: "' . $filterValue . '"';
    }
@endphp

@section('title', $title . ' - IMG')
@section('meta_description', 'Temukan ' . strtolower($title) . ' di IMG. Bandingkan kasur, springbed, bantal, dan perlengkapan tidur premium berdasarkan kategori, brand, harga, dan stok.')
@section('canonical', request()->fullUrl())
@section('og_type', 'website')

@section('content')
    @php
        $offset = ($products->currentPage() - 1) * $products->perPage();
        $productItems = $products->map(function ($product, $index) use ($offset) {
            return [
                '@type' => 'ListItem',
                'position' => $offset + $index + 1,
                'url' => route('products.show', $product->slug),
                'name' => $product->name,
            ];
        })->values()->toArray();

        $filterName = $title;
        if ($filterType === 'brand' && $filterValue) {
            $filterDescription = 'Koleksi produk dari brand ' . ($brand?->name ?? $filterValue) . ' di IMG, termasuk kasur, springbed, bantal, dan aksesori tidur premium.';
        } elseif ($filterType === 'category' && $filterValue) {
            $filterDescription = 'Koleksi produk kategori ' . ($category?->name ?? $filterValue) . ' di IMG dengan pilihan springbed dan perlengkapan tidur berkualitas.';
        } elseif ($filterType === 'search' && $filterValue) {
            $filterDescription = 'Hasil pencarian produk IMG untuk "' . e($filterValue) . '". Temukan kasur dan perlengkapan tidur yang sesuai kebutuhan Anda.';
        } else {
            $filterDescription = 'Katalog lengkap produk IMG dengan pilihan kasur, springbed, bantal, dan aksesori tidur premium.';
        }

        $breadcrumbItems = [
            [
                '@type' => 'ListItem',
                'position' => 1,
                'name' => 'Home',
                'item' => route('home'),
            ],
            [
                '@type' => 'ListItem',
                'position' => 2,
                'name' => $filterName,
            ],
        ];

        $productCollectionSchema = [
            '@context' => 'https://schema.org',
            '@type' => 'CollectionPage',
            '@id' => request()->fullUrl(),
            'name' => $title,
            'description' => $filterDescription,
            'url' => request()->fullUrl(),
            'isPartOf' => [
                '@type' => 'WebSite',
                'name' => 'IMG International Mattress Gallery',
                'url' => route('home'),
            ],
            'mainEntity' => [
                '@type' => 'ItemList',
                'name' => $title,
                'numberOfItems' => $products->total(),
                'itemListElement' => $productItems,
            ],
        ];

        $productBreadcrumbSchema = [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => $breadcrumbItems,
        ];
    @endphp

    @push('jsonld')
        <script type="application/ld+json">
            @json($productCollectionSchema)
        </script>
        <script type="application/ld+json">
            @json($productBreadcrumbSchema)
        </script>
    @endpush

    <div class="container mx-auto px-4 md:px-6 py-12 min-h-[70vh]" x-data="{ viewMode: localStorage.getItem('productViewMode') || 'grid', isFilterOpen: false }" 
        @product-view-mode.window="viewMode = $event.detail" @open-filter.window="isFilterOpen = true">
        <!-- Listing Header -->
        <div class="flex flex-col md:flex-row justify-between items-end mb-8 gap-4 border-b border-gray-100 pb-6 font-sans">
            <div>
                <h1 class="text-3xl font-extrabold text-brand-dark tracking-tight font-serif mb-2">
                    {{ $title }}
                </h1>
                <p class="text-gray-500">
                    Menampilkan {{ $products->total() }} produk
                </p>
            </div>
            
            <div class="flex items-center gap-3">
                <button @click="$dispatch('open-filter')" class="flex items-center gap-2 px-4 py-2 border border-brand-muted rounded-lg text-sm font-semibold text-brand-dark hover:border-brand-gold transition-colors bg-white focus:outline-none">
                    <i class="fa-solid fa-filter w-4 h-4"></i> Filter
                </button>
                <div class="flex items-center border border-brand-muted rounded-lg overflow-hidden bg-white">
                    <button type="button" @click="viewMode = 'grid'; localStorage.setItem('productViewMode', 'grid')" 
                        :class="{'bg-brand-light text-brand-dark': viewMode === 'grid', 'text-gray-400 hover:text-brand-dark hover:bg-gray-50': viewMode !== 'grid'}" 
                        class="px-3 py-2 focus:outline-none transition-colors" aria-label="Tampilan grid">
                        <i class="fa-solid fa-border-all w-4 h-4"></i>
                    </button>
                    <button type="button" @click="viewMode = 'list'; localStorage.setItem('productViewMode', 'list')" 
                        :class="{'bg-brand-light text-brand-dark': viewMode === 'list', 'text-gray-400 hover:text-brand-dark hover:bg-gray-50': viewMode !== 'list'}" 
                        class="px-3 py-2 focus:outline-none transition-colors" aria-label="Tampilan list">
                        <i class="fa-solid fa-list w-4 h-4"></i>
                    </button>
                </div>
            </div>
        </div>

        <div class="flex flex-col lg:flex-row gap-8">
            <!-- Sidebar Categories (Desktop) -->
            <aside class="hidden lg:block lg:w-64 flex-shrink-0">
                <div class="bg-white border border-brand-muted rounded-2xl p-6 shadow-sm sticky top-6 mb-6">
                    @include('frontend.product.sidebar-filters')
                </div>

                <div class="bg-white border border-brand-muted rounded-2xl p-6 shadow-sm sticky top-[calc(70vh+100px)]">
                    @include('frontend.product.sidebar-categories')
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
                    <!-- Overlay -->
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

                    <!-- Slide Panel -->
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
                            <div class="h-full flex flex-col bg-white shadow-2xl overflow-y-scroll">
                                <div class="flex items-center justify-between p-5 border-b border-brand-muted bg-brand-light">
                                    <h2 class="text-lg font-bold text-brand-dark flex items-center gap-2">
                                        <i class="fa-solid fa-filter"></i> Filter Produk
                                    </h2>
                                    <button 
                                        @click="isFilterOpen = false" 
                                        class="p-2 text-gray-400 hover:text-brand-dark bg-white hover:bg-brand-muted rounded-full transition-colors flex items-center justify-center focus:outline-none"
                                    >
                                        <i class="fa-solid fa-xmark w-4 h-4"></i>
                                    </button>
                                </div>
                                <div class="flex-1 p-5 space-y-6">
                                    <div class="bg-white border border-brand-muted rounded-2xl p-5 shadow-sm">
                                        @include('frontend.product.sidebar-filters')
                                    </div>
                                    <div class="bg-white border border-brand-muted rounded-2xl p-5 shadow-sm">
                                        @include('frontend.product.sidebar-categories')
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="flex-1">
                <!-- Products Grid/List -->
                @if($products->count() > 0)
                    <!-- Grid View -->
                    <div x-show="viewMode === 'grid'" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 catalog-products-grid">
                        @foreach($products as $product)
                            @include('frontend.components.product-card-dynamic', ['product' => $product])
                        @endforeach
                    </div>

                    <!-- List View -->
                    <div x-show="viewMode === 'list'" class="flex flex-col gap-4 catalog-products-list" style="display: none;">
                        @foreach($products as $product)
                            @include('frontend.components.product-card-list', ['product' => $product])
                        @endforeach
                    </div>

                    <!-- Pagination / Load More -->
                    @if($filterType === 'category' || $filterType === 'brand')
                        @if($products->hasMorePages())
                            <div class="mt-12 text-center" id="catalog-load-more-container">
                                <button 
                                    type="button"
                                    id="catalog-load-more-btn"
                                    class="group px-8 py-3.5 rounded-full font-bold text-brand-darker bg-white border-2 border-brand-dark shadow-sm transition-all duration-300 hover:bg-brand-dark hover:text-white hover:border-brand-dark hover:shadow-xl focus:outline-none"
                                    data-next-page-url="{{ $products->nextPageUrl() }}"
                                >
                                    Muat Lebih Banyak <span class="group-hover:translate-x-1 transition-transform inline-block">&rarr;</span>
                                </button>
                            </div>
                        @endif
                    @else
                        <div class="mt-10">
                            {{ $products->withQueryString()->links('frontend.components.pagination') }}
                        </div>
                    @endif
                @else
                    <div class="bg-white border border-brand-muted rounded-2xl p-12 text-center shadow-sm font-sans">
                        <div class="w-20 h-20 bg-brand-light rounded-full flex items-center justify-center text-brand-gold mx-auto mb-4">
                            <i class="fa-solid fa-border-all w-10 h-10"></i>
                        </div>
                        <h2 class="text-xl font-bold text-brand-dark mb-2">Produk Tidak Ditemukan</h2>
                        <p class="text-gray-500 max-w-md mx-auto">
                            Maaf, kami belum memiliki produk untuk {{ $filterType === 'brand' ? 'brand' : ($filterType === 'category' ? 'kategori' : 'pencarian') }} "{{ $filterValue }}". Silakan lihat pilihan lain.
                        </p>
                        <div class="mt-6">
                            <a href="{{ route('home') }}" class="inline-block px-6 py-2.5 bg-brand-dark text-brand-gold rounded-xl font-bold hover:bg-brand-darker transition-colors">
                                Kembali ke Home
                            </a>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection