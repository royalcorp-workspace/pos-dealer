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

    <div class="container mx-auto px-4 md:px-6 py-12 min-h-[70vh]" x-data="{ activeCategory: null, activeBrand: null }">
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
                    <button class="px-3 py-2 bg-brand-light text-brand-dark focus:outline-none">
                        <i class="fa-solid fa-border-all w-4 h-4"></i>
                    </button>
                    <button class="px-3 py-2 text-gray-400 hover:text-brand-dark hover:bg-gray-50 transition-colors focus:outline-none">
                        <i class="fa-solid fa-list w-4 h-4"></i>
                    </button>
                </div>
            </div>
        </div>

        <div class="bg-white border border-brand-muted rounded-3xl p-6 mb-8">
            <p class="text-gray-600 leading-relaxed">
                {{ $filterDescription }} Gunakan filter di samping untuk menyaring produk berdasarkan kategori, brand, rentang harga, stok, dan atribut agar lebih mudah menemukan kasur yang sesuai.
            </p>
        </div>

        <div class="flex flex-col lg:flex-row gap-8">
            <!-- Sidebar Categories -->
            <aside class="w-full lg:w-64 flex-shrink-0">
                <div class="bg-white border border-brand-muted rounded-2xl p-6 shadow-sm sticky top-6 mb-6">
                    <form method="GET" class="space-y-6">
                        <input type="hidden" name="type" value="{{ $filterType }}">
                        <input type="hidden" name="value" value="{{ $filterValue }}">

                        <div>
                            <h3 class="font-bold text-brand-dark mb-3 text-sm uppercase tracking-wider">Kategori</h3>
                            @foreach($categories->take(10) as $category)
                                <label class="flex items-center gap-2 py-1 text-sm">
                                    <input type="checkbox" name="categories[]" value="{{ $category->slug }}"
                                        {{ in_array($category->slug, $filters['categories'] ?? []) ? 'checked' : '' }}
                                        class="rounded border-gray-300 text-brand-gold focus:ring-brand-gold">
                                    <span class="text-gray-700">{{ $category->name }}</span>
                                </label>
                            @endforeach
                        </div>

                        <div>
                            <h3 class="font-bold text-brand-dark mb-3 text-sm uppercase tracking-wider">Brand</h3>
                            @foreach($brands->take(10) as $brand)
                                <label class="flex items-center gap-2 py-1 text-sm">
                                    <input type="checkbox" name="brands[]" value="{{ $brand->slug }}"
                                        {{ in_array($brand->slug, $filters['brands'] ?? []) ? 'checked' : '' }}
                                        class="rounded border-gray-300 text-brand-gold focus:ring-brand-gold">
                                    <span class="text-gray-700">{{ $brand->name }}</span>
                                </label>
                            @endforeach
                        </div>

                        <div>
                            <h3 class="font-bold text-brand-dark mb-3 text-sm uppercase tracking-wider">Harga</h3>
                            <div class="grid grid-cols-2 gap-3">
                                <input type="number" name="min_price" placeholder="Min" value="{{ $filters['min_price'] ?? '' }}"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-brand-gold focus:border-brand-gold">
                                <input type="number" name="max_price" placeholder="Max" value="{{ $filters['max_price'] ?? '' }}"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-brand-gold focus:border-brand-gold">
                            </div>
                        </div>

                        <div>
                            <h3 class="font-bold text-brand-dark mb-3 text-sm uppercase tracking-wider">Stok</h3>
                            <label class="flex items-center gap-2 py-1 text-sm">
                                <input type="checkbox" name="in_stock" value="1"
                                    {{ ($filters['in_stock'] ?? '') === '1' ? 'checked' : '' }}
                                    class="rounded border-gray-300 text-brand-gold focus:ring-brand-gold">
                                <span class="text-gray-700">Tersedia</span>
                            </label>
                        </div>

                        <div>
                            <h3 class="font-bold text-brand-dark mb-3 text-sm uppercase tracking-wider">Atribut</h3>
                            @foreach($tags->take(10) as $tag)
                                <label class="flex items-center gap-2 py-1 text-sm">
                                    <input type="checkbox" name="tags[]" value="{{ $tag->slug }}"
                                        {{ in_array($tag->slug, $filters['tags'] ?? []) ? 'checked' : '' }}
                                        class="rounded border-gray-300 text-brand-gold focus:ring-brand-gold">
                                    <span class="text-gray-700">{{ $tag->name }}</span>
                                </label>
                            @endforeach
                        </div>

                        <button type="submit" class="w-full py-2 bg-brand-gold text-brand-dark rounded-lg font-bold text-sm hover:bg-brand-gold/80 transition">
                            Terapkan Filter
                        </button>
                    </form>
                </div>

                <div class="bg-white border border-brand-muted rounded-2xl p-6 shadow-sm sticky top-[calc(70vh+100px)]">
                    <h3 class="font-bold text-brand-dark mb-4 text-lg">Kategori</h3>
                    
                    @foreach($categories as $category)
                        @php
                            $hasChildren = $category->children->isNotEmpty();
                            $isActive = $filterType === 'category' && $filterValue === $category->slug;
                        @endphp
                        <div class="mb-3">
<a href="{{ route('category.show', $category->slug) }}"
                                class="flex items-center justify-between py-2 px-3 rounded-lg text-sm font-medium transition-colors {{ $isActive ? 'bg-brand-gold/20 text-brand-dark' : 'text-gray-700 hover:bg-brand-light hover:text-brand-dark' }}"
                            >
                                <span>{{ $category->name }}</span>
                            </a>
                            
                            @if($hasChildren)
                                <div class="ml-4 mt-2 space-y-1">
                                    @foreach($category->children->take(10) as $child)
                                        @php
                                            $childActive = $filterType === 'category' && $filterValue === $child->slug;
                                            $grandchildren = $child->children->take(5);
                                        @endphp
                                        <a href="{{ route('category.show', $child->slug) }}"
                                            class="block py-1.5 px-3 rounded text-xs transition-colors {{ $childActive ? 'bg-brand-gold/10 text-brand-dark font-semibold' : 'text-gray-500 hover:text-brand-dark' }}"
                                        >
                                            {{ $child->name }}
                                        </a>
                                        
                                        @if($grandchildren->isNotEmpty())
                                            <div class="ml-3 mt-1 space-y-1">
                                                @foreach($grandchildren as $grandchild)
<a href="{{ route('category.show', $grandchild->slug) }}"
                                                            class="block py-1 px-3 rounded text-xs text-gray-400 hover:text-brand-dark"
                                                        >
                                                        {{ $grandchild->name }}
                                                    </a>
                                                @endforeach
                                            </div>
                                        @endif
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </aside>

            <!-- Main Content -->
            <div class="flex-1">
                <!-- Products Grid -->
                @if($products->count() > 0)
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                        @foreach($products as $product)
                            @include('frontend.components.product-card-dynamic', ['product' => $product])
                        @endforeach
                    </div>

                    <!-- Pagination -->
                    <div class="mt-10">
                        {{ $products->withQueryString()->links('frontend.components.pagination') }}
                    </div>
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