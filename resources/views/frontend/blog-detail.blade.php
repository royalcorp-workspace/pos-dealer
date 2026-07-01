@extends('frontend.layouts.app')

@section('title', $blogPost->meta_title ?? $blogPost->title . ' - IMG')
@section('meta_description', $blogPost->meta_description ?? $blogPost->excerpt)
@section('canonical', route('blog.show', $blogPost))

@section('content')
    @if (!$blogPost)

        <div class="container mx-auto px-4 md:px-6 py-12 min-h-[70vh] font-sans">
            <div class="max-w-4xl mx-auto text-center">
                <div class="inline-flex items-center justify-center w-24 h-24 rounded-full bg-brand-light text-brand-gold mb-8 shadow-sm">
                    <i class="fa-solid fa-file-lines w-10 h-10"></i>
                </div>

                <h1 class="text-3xl md:text-4xl font-extrabold text-brand-dark font-serif mb-6">Artikel Tidak Ditemukan</h1>

                <p class="text-gray-500 text-lg max-w-2xl mx-auto leading-relaxed mb-8">
                    Mohon maaf, artikel yang Anda cari tidak ditemukan.
                </p>

                <a href="{{ route('blog') }}" class="px-8 py-4 rounded-full font-bold text-white bg-brand-dark hover:bg-brand-darker transition-all shadow-lg shadow-brand-dark/20 inline-block">
                    Kembali ke Blog
                </a>
            </div>
        </div>
    @else

        @push('jsonld')

            <script type="application/ld+json">
            @json($structuredData)
            </script>

        @endpush

        <div class="container mx-auto px-4 md:px-6 py-12 min-h-[70vh] font-sans">
            <div class="max-w-4xl mx-auto">
                <nav class="mb-6">
                    <a href="{{ route('blog') }}" class="text-brand-gold hover:text-brand-gold-dark transition-colors">&laquo; Kembali ke Blog</a>
                </nav>

                <article class="bg-white rounded-3xl p-8 shadow-sm">
                    @if ($blogPost->featured_image_url)

                        <div class="aspect-[16/9] bg-brand-light w-full relative overflow-hidden rounded-2xl mb-6">
                            <img src="{{ $blogPost->featured_image_url }}" alt="{{ $blogPost->title }}" class="absolute inset-0 w-full h-full object-cover">
                        </div>

                    @endif

                    <h1 class="text-3xl md:text-4xl font-extrabold text-brand-dark font-serif mb-4">{{ $blogPost->title }}</h1>

                    <div class="flex items-center gap-4 text-sm text-gray-500 mb-6">
                        @if ($blogPost->author_name)

                            <span><i class="fa-regular fa-user mr-1"></i>{{ $blogPost->author_name }}</span>

                        @endif
                        <span><i class="fa-regular fa-calendar mr-1"></i>{{ $blogPost->
                            published_at?->
                            format('d M Y') ?? $blogPost->
                            created_at->
                            format('d M Y') }}</span>
                    </div>

                    @if ($blogPost->excerpt)

                        <div class="text-lg text-gray-600 italic mb-6 border-l-4 border-brand-gold pl-4">{{ $blogPost->excerpt }}</div>

                    @endif

                    <div class="prose prose-lg max-w-none text-gray-600">
                        {!! $blogPost->content !!}
                    </div>
                </article>
            </div>
        </div>
    @endif

@endsection
