@extends('frontend.layouts.app')

@section('title', 'Belanja Berdasarkan Brand - IMG')

@section('content')
    <div class="container mx-auto px-4 md:px-6 py-12 min-h-[60vh] font-sans">
        <div class="text-center mb-12">
            <h1 class="text-3xl md:text-4xl font-extrabold text-brand-dark mb-4 font-serif">Belanja Berdasarkan Brand</h1>
            <p class="text-gray-500 max-w-2xl mx-auto font-medium">Temukan berbagai koleksi dari brand matras dan perlengkapan tidur terkemuka dunia dengan standar kualitas internasional.</p>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-6">
            @foreach($brands as $index => $brand)
                <a 
                    href="{{ route('products.index', ['type' => 'brand', 'value' => $brand]) }}" 
                    class="bg-white border border-brand-muted hover:border-brand-gold hover:shadow-lg transition-all rounded-2xl p-8 flex flex-col items-center justify-center cursor-pointer group h-40"
                >
                    <div class="w-10 h-10 bg-brand-light rounded-full flex items-center justify-center text-brand-gold/50 group-hover:text-brand-gold mb-3 transition-colors">
                        <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M7 7h10l2 4c0 1.1-.9 2-2 2H9l-2 4V7z" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </div>
                    <h3 class="font-bold text-brand-dark text-center group-hover:text-brand-gold-dark transition-colors">{{ $brand }}</h3>
                    <span class="text-xs text-gray-400 mt-2">{{ 20 + $index * 5 }} Produk</span>
                </a>
            @endforeach
        </div>
    </div>
@endsection

