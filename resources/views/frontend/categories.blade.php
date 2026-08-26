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

    @php
        $categoryIconMap = [
            'kasur-spring-bed' => 'fa-solid fa-bed',
            'kasur-busa-foam' => 'fa-solid fa-layer-group',
            'bantal-guling' => 'fa-solid fa-cloud',
            'aksesoris-tidur' => 'fa-solid fa-shield-halved',
        ];
    @endphp

    <div class="container mx-auto px-4 md:px-6 py-10 md:py-16 min-h-[60vh] font-sans">
        <!-- Header -->
        <div class="text-center max-w-2xl mx-auto mb-10 md:mb-14">
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-brand-gold/15 text-brand-gold-dark border border-brand-gold/30 text-xs font-extrabold tracking-widest uppercase mb-3">
                <span class="w-1.5 h-1.5 rounded-full bg-brand-gold"></span>
                <span>{{ __('Koleksi Pilihan') }}</span>
            </div>
            <h1 class="text-3xl sm:text-4xl md:text-5xl font-extrabold text-brand-dark mb-3 font-serif tracking-tight">
                {{ __('Kategori Produk') }}
            </h1>
            <p class="text-stone-500 text-sm sm:text-base font-normal leading-relaxed">
                {{ __('Eksplorasi rangkaian kasur, spring bed, bantal, dan perlengkapan tidur premium yang didesain khusus untuk kesempurnaan tidur Anda.') }}
            </p>
        </div>

        <!-- Categories Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-2 xl:grid-cols-4 gap-5 sm:gap-6">
            @foreach($categories as $category)
                @php
                    $iconClass = $categoryIconMap[$category->slug] ?? 'fa-solid fa-star';
                @endphp
                <a 
                    href="{{ route('category.show', $category->slug) }}" 
                    class="bg-white border border-[#EFEBE4] hover:border-brand-gold hover:shadow-xl hover:-translate-y-1 transition-all duration-300 rounded-3xl p-6 sm:p-7 flex flex-col justify-between group relative overflow-hidden min-h-[250px] shadow-2xs"
                >
                    <!-- Ambient Glow -->
                    <div class="absolute -right-12 -bottom-12 w-36 h-36 bg-brand-gold/10 rounded-full blur-2xl group-hover:scale-150 transition-transform duration-500 pointer-events-none"></div>

                    <!-- Top Card: Icon & Badge -->
                    <div class="relative z-10">
                        <div class="flex items-start justify-between mb-5">
                            <div class="w-13 h-13 rounded-2xl bg-[#FAF8F5] border border-[#E5DFC9] text-brand-gold-dark group-hover:bg-brand-gold group-hover:text-brand-dark flex items-center justify-center transition-all duration-300 shadow-2xs">
                                <i class="{{ $iconClass }} text-xl"></i>
                            </div>
                            <span class="text-[11px] font-bold px-2.5 py-1 rounded-full bg-stone-100 text-stone-600 group-hover:bg-brand-gold/20 group-hover:text-brand-dark transition-colors">
                                {{ $category->products_count }} {{ __('Produk') }}
                            </span>
                        </div>

                        <h2 class="text-lg sm:text-xl font-extrabold text-brand-dark group-hover:text-brand-gold-dark font-serif transition-colors leading-snug mb-2">
                            {{ html_entity_decode($category->name) }}
                        </h2>

                        @if(!empty($category->description))
                            <p class="text-xs text-stone-500 line-clamp-2 leading-relaxed font-normal">
                                {{ $category->description }}
                            </p>
                        @endif
                    </div>

                    <!-- Bottom Action Link -->
                    <div class="relative z-10 pt-5 mt-auto flex items-center justify-between border-t border-[#EFEBE4]/70">
                        <span class="text-xs font-bold text-brand-gold-dark group-hover:text-brand-dark transition-colors flex items-center gap-1.5">
                            <span>{{ __('Jelajahi Koleksi') }}</span>
                            <i class="fa-solid fa-arrow-right text-[10px] group-hover:translate-x-1 transition-transform"></i>
                        </span>
                        <div class="w-7 h-7 rounded-full bg-[#FAF8F5] group-hover:bg-brand-gold text-brand-gold-dark group-hover:text-brand-dark flex items-center justify-center text-xs transition-all duration-300">
                            <i class="fa-solid fa-chevron-right text-[10px]"></i>
                        </div>
                    </div>
                </a>
            @endforeach
        </div>
    </div>
@endsection