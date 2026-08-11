@extends('frontend.layouts.app')

@section('title', 'Brand - IMG')
@section('meta_description', 'Belanja kasur dan perlengkapan tidur berdasarkan brand premium di IMG. Temukan Lady Americana, King Koil, serta merek springbed berkualitas lainnya.')
@section('canonical', route('brands'))

@section('content')
    @php
        $brandItems = $brandsWithProducts->map(function ($brand, $index) {
            return [
                '@type' => 'ListItem',
                'position' => $index + 1,
                'url' => route('products.index', ['type' => 'brand', 'value' => $brand->slug]),
                'name' => $brand->name,
            ];
        })->values()->toArray();

        $brandCollectionSchema = [
            '@context' => 'https://schema.org',
            '@type' => 'CollectionPage',
            '@id' => route('brands'),
            'name' => 'Brand Kasur Premium IMG',
            'description' => 'Daftar brand kasur, springbed, dan perlengkapan tidur premium yang tersedia di International Mattress Gallery.',
            'url' => route('brands'),
            'mainEntity' => [
                '@type' => 'ItemList',
                'name' => 'Brand Kasur Premium IMG',
                'numberOfItems' => count($brandItems),
                'itemListElement' => $brandItems,
            ],
        ];

        $brandBreadcrumbSchema = [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => [
                [
                    '@type' => 'ListItem',
                    'position' => 1,
                    'name' => 'Home',
                    'item' => route('home'),
                ],
                [
                    '@type' => 'ListItem',
                    'position' => 2,
                    'name' => 'Brand',
                ],
            ],
        ];
    @endphp

    @push('jsonld')
        <script type="application/ld+json">
        @json($brandCollectionSchema)
        </script>
        <script type="application/ld+json">
        @json($brandBreadcrumbSchema)
        </script>
    @endpush

    <div class="container mx-auto px-4 md:px-6 py-12 min-h-[60vh] font-sans">
        <div class="text-center mb-12">
            <h1 class="text-3xl md:text-4xl font-extrabold text-brand-dark mb-4 font-serif">Brand</h1>
            <p class="text-gray-500 max-w-2xl mx-auto font-medium">Temukan berbagai koleksi dari brand matras dan perlengkapan tidur terkemuka dunia dengan standar kualitas internasional.</p>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-6">
            @foreach($brandsWithProducts as $brand)
                <a 
                    href="{{ route('products.index', ['type' => 'brand', 'value' => $brand->slug]) }}" 
                    class="bg-white border border-brand-muted hover:border-brand-gold hover:shadow-lg transition-all rounded-2xl p-8 flex flex-col items-center justify-center cursor-pointer group h-40"
                >
                    @if($brand->logo)
                        <img src="{{ cms_asset($brand->logo) }}" alt="{{ $brand->name }}" loading="lazy" decoding="async" class="w-16 h-16 object-contain mb-3" />
                    @else
                        <div class="w-10 h-10 bg-brand-light rounded-full flex items-center justify-center text-brand-gold/50 group-hover:text-brand-gold mb-3 transition-colors">
                            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M7 7h10l2 4c0 1.1-.9 2-2 2H9l-2 4V7z" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </div>
                    @endif
                    <h3 class="font-bold text-brand-dark text-center group-hover:text-brand-gold-dark transition-colors">{{ $brand->name }}</h3>
                    <span class="text-xs text-gray-400 mt-2">{{ $brand->products_count }} Produk</span>
                </a>
            @endforeach
        </div>
    </div>
@endsection

