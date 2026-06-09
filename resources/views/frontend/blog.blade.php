@extends('frontend.layouts.app')

@section('title', 'Blog & Tips Tidur - IMG')

@section('content')
        <div class="container mx-auto px-4 md:px-6 py-12 min-h-[60vh]">
            <div class="text-center mb-12">
                <h1 class="text-3xl md:text-4xl font-extrabold text-brand-dark mb-4 font-serif">Artikel &amp; Blog</h1>
                <p class="text-gray-500 max-w-2xl mx-auto">Tips dan wawasan seputar kesehatan tidur, perawatan tempat tidur, dan info produk terbaru.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                @foreach($blogs as $blog)
<article class="bg-white border text-left border-brand-muted hover:border-brand-gold hover:shadow-lg transition-all rounded-3xl overflow-hidden flex flex-col group blog-card cursor-pointer">
                        <div class="aspect-[4/3] bg-brand-light w-full relative overflow-hidden">
                            <div class="absolute inset-0 bg-gray-200"></div>
                            <i class="fa-regular fa-file-lines absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-12 h-12 text-gray-300"></i>
                            <div class="absolute top-4 left-4 bg-white text-xs font-bold px-3 py-1.5 rounded-full text-brand-dark z-10 shadow-sm">{{ $blog['category'] }}</div>
                        </div>

                        <div class="p-6 flex flex-col flex-1">
                            <span class="text-xs text-brand-gold mb-3 font-semibold">{{ $blog['date'] }}</span>
                            <h3 class="blog-card__title font-bold text-brand-dark mb-4 group-hover:text-brand-gold-dark transition-colors line-clamp-3 leading-snug">{{ $blog['title'] }}</h3>
                            <div class="mt-auto flex items-center text-sm font-semibold blog-card__link text-brand-dark group-hover:text-brand-gold transition-colors">
                                Baca Selengkapnya <i class="fa-solid fa-arrow-right w-4 h-4 ml-2 group-hover:translate-x-1 transition-transform"></i>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
        </div>
@endsection

