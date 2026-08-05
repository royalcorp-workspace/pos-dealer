@extends('frontend.layouts.app')

@section('title', 'Blog Dan Tips Tidur - IMG')
@section('meta_description', 'Baca tips tidur, panduan memilih kasur, perawatan springbed, dan informasi produk terbaru dari IMG untuk istirahat yang lebih berkualitas.')
@section('canonical', route('blog'))

@section('content')
    @php
        $blogItems = collect($blogs)->map(function ($blog, $index) {
            return [
                '@type' => 'ListItem',
                'position' => $index + 1,
                'url' => route('blog.show', $blog->slug),
                'name' => $blog->title,
            ];
        })->values()->toArray();

        $articles = collect($blogs)->map(function ($blog) {
            return [
                '@context' => 'https://schema.org',
                '@type' => 'Article',
                'headline' => $blog->title,
                'articleSection' => 'Tips Tidur',
                'datePublished' => $blog->published_at?->format('Y-m-d') ?? $blog->created_at->format('Y-m-d'),
                'author' => [
                    '@type' => 'Organization',
                    'name' => 'IMG International Mattress Gallery',
                ],
                'publisher' => [
                    '@type' => 'Organization',
                    'name' => 'IMG International Mattress Gallery',
                    'url' => route('home'),
                ],
            ];
        })->values()->toArray();

        $blogCollectionSchema = [
            '@context' => 'https://schema.org',
            '@type' => 'CollectionPage',
            '@id' => route('blog'),
            'name' => 'Blog Dan Tips Tidur IMG',
            'description' => 'Artikel dan tips seputar kesehatan tidur, perawatan springbed, pemilihan kasur, serta informasi produk terbaru dari IMG.',
            'url' => route('blog'),
            'mainEntity' => [
                '@type' => 'ItemList',
                'name' => 'Artikel Blog IMG',
                'numberOfItems' => count($blogItems),
                'itemListElement' => $blogItems,
            ],
        ];

        $blogBreadcrumbSchema = [
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
                    'name' => 'Blog Dan Tips Tidur',
                ],
            ],
        ];
    @endphp

    @push('jsonld')
        <script type="application/ld+json">
        @json($blogCollectionSchema)
        </script>
        <script type="application/ld+json">
        @json($articles)
        </script>
        <script type="application/ld+json">
        @json($blogBreadcrumbSchema)
        </script>
    @endpush

        <div class="container mx-auto px-4 md:px-6 py-12 min-h-[60vh]">
            <div class="text-center mb-12">
                <h1 class="text-3xl md:text-4xl font-extrabold text-brand-dark mb-4 font-serif">Artikel Dan Blog</h1>
                <p class="text-gray-500 max-w-2xl mx-auto">Tips dan wawasan seputar kesehatan tidur, perawatan tempat tidur, dan info produk terbaru.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                @foreach($blogs as $blog)
                @php
                    $blogUrl = route('blog.show', $blog->slug);
                @endphp
                <article class="bg-white border text-left border-brand-muted hover:border-brand-gold hover:shadow-lg transition-all rounded-3xl overflow-hidden flex flex-col group blog-card" itemscope itemtype="https://schema.org/Article">
                        <a href="{{ $blogUrl }}" itemprop="url" class="block">
                        <div class="aspect-[4/3] bg-brand-light w-full relative overflow-hidden">
                            @if($blog->featured_image_url)
                                <img src="{{ $blog->featured_image_url }}" alt="{{ $blog->title }}" loading="lazy" decoding="async" class="absolute inset-0 w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                            @else
                                <div class="absolute inset-0 bg-gray-200"></div>
                                <i class="fa-regular fa-file-lines absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-12 h-12 text-gray-300"></i>
                            @endif
                            <div class="absolute top-4 left-4 bg-white text-xs font-bold px-3 py-1.5 rounded-full text-brand-dark z-10 shadow-sm" itemprop="articleSection">Tips Tidur</div>
                        </div>
                        </a>

                        <div class="p-6 flex flex-col flex-1">
                            <span class="text-xs text-brand-gold mb-3 font-semibold" itemprop="datePublished">{{ $blog->published_at?->format('d M Y') ?? $blog->created_at->format('d M Y') }}</span>
                            <h3 class="blog-card__title font-bold text-brand-dark mb-4 group-hover:text-brand-gold-dark transition-colors line-clamp-3 leading-snug" itemprop="headline">
                                <a href="{{ $blogUrl }}" class="hover:text-brand-gold-dark">{{ $blog->title }}</a>
                            </h3>
                            <div class="mt-auto flex items-center text-sm font-semibold blog-card__link text-brand-dark group-hover:text-brand-gold transition-colors">
                                <a href="{{ $blogUrl }}">Baca Selengkapnya <i class="fa-solid fa-arrow-right w-4 h-4 ml-2 group-hover:translate-x-1 transition-transform"></i></a>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
        </div>
@endsection

