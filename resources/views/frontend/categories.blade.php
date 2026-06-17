@extends('frontend.layouts.app')

@section('title', 'Kategori Produk - IMG')
@section('meta_description', 'Jelajahi kategori kasur, springbed, bantal, dan perlengkapan tidur premium di IMG. Temukan koleksi berdasarkan kebutuhan tidur dan kenyamanan rumah Anda.')
@section('canonical', route('categories'))

@section('content')
    @php
        $categoryItems = $categories->map(function ($category, $index) {
            return [
                '@type' => 'ListItem',
                'position' => $index + 1,
                'url' => route('category.show', $category->slug),
                'name' => $category->name,
            ];
        })->values()->toArray();

        $categoryCollectionSchema = [
            '@context' => 'https://schema.org',
            '@type' => 'CollectionPage',
            '@id' => route('categories'),
            'name' => 'Kategori Produk IMG',
            'description' => 'Daftar kategori kasur, springbed, bantal, dan perlengkapan tidur premium di International Mattress Gallery.',
            'url' => route('categories'),
            'mainEntity' => [
                '@type' => 'ItemList',
                'name' => 'Kategori Produk IMG',
                'numberOfItems' => $categories->count(),
                'itemListElement' => $categoryItems,
            ],
        ];

        $categoryBreadcrumbSchema = [
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
                    'name' => 'Kategori Produk',
                ],
            ],
        ];
    @endphp

    @push('jsonld')
        <script type="application/ld+json">
        @json($categoryCollectionSchema)
        </script>
        <script type="application/ld+json">
        @json($categoryBreadcrumbSchema)
        </script>
    @endpush

    <div class="container mx-auto px-4 md:px-6 py-12 min-h-[60vh] font-sans">
        <div class="text-center mb-12">
            <h1 class="text-3xl md:text-4xl font-extrabold text-brand-dark mb-4 font-serif">Kategori Produk</h1>
            <p class="text-gray-500 max-w-2xl mx-auto font-medium">Eksplorasi rangkaian produk lengkap yang didesain khusus untuk meningkatkan kualitas tidur dan kenyamanan rumah Anda.</p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($categories as $category)
                <div class="category-card bg-white border-2 border-brand-muted hover:border-brand-gold hover:shadow-lg transition-all rounded-2xl p-6 flex flex-col justify-between cursor-pointer group h-48 relative overflow-hidden">
                    <div class="relative z-10 w-full">
                        <div class="mb-4">
<div class="category-icon-box w-12 h-12 rounded-xl flex items-center justify-center transition-all overflow-hidden relative group-hover:bg-brand-gold">
                            <svg class="w-6 h-6 transition-colors group-hover:text-white" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <rect class="fill-current" x="3" y="3" width="7" height="7" rx="1"/>
                                <rect class="fill-current" x="14" y="3" width="7" height="7" rx="1"/>
                                <rect class="fill-current" x="3" y="14" width="7" height="7" rx="1"/>
                                <rect class="fill-current" x="14" y="14" width="7" height="7" rx="1"/>
                            </svg>
                        </div>
                        </div>
                        <h3 class="text-xl font-bold mb-2 group-hover:text-brand-gold">
                            <a href="{{ route('category.show', $category->slug) }}" class="block w-full transition-colors group-hover:text-brand-gold">
                                {{ $category->name }}
                            </a>
                        </h3>
                    </div>
                    
                    @if($category->children->isNotEmpty())
                        <div class="relative z-10 flex flex-wrap gap-x-4 gap-y-1 mt-1 mb-3 text-xs text-gray-500">
                            @foreach($category->children->take(4) as $child)
                                <a href="{{ route('category.show', $child->slug) }}" class="hover:text-brand-gold">{{ $child->name }}</a>
                            @endforeach
                            @if($category->children->count() > 4)
                                <span class="text-brand-gold">+{{ $category->children->count() - 4 }}</span>
                            @endif
                        </div>
                    @endif
                    
                    <div class="relative z-10 flex items-center text-sm font-semibold text-gray-500 group-hover:text-brand-gold transition-colors mt-auto">
                        <a href="{{ route('category.show', $category->slug) }}" class="flex items-center">
                            Jelajahi Koleksi <svg class="w-4 h-4 ml-1" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M9 18l6-6-6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </a>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endsection