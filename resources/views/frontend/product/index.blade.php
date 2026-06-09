@extends('frontend.layouts.app')

@php
    $title = 'Semua Produk';
    if ($filterType === 'brand' && $filterValue) {
        $title = 'Brand: ' . $filterValue;
    } elseif ($filterType === 'category' && $filterValue) {
        $title = 'Kategori: ' . $filterValue;
    } elseif ($filterType === 'search' && $filterValue) {
        $title = 'Pencarian: "' . $filterValue . '"';
    }
@endphp

@section('title', $title . ' - IMG')

@section('content')
    <div class="container mx-auto px-4 md:px-6 py-12 min-h-[70vh]">
        <!-- Listing Header -->
        <div class="flex flex-col md:flex-row justify-between items-end mb-8 gap-4 border-b border-gray-100 pb-6 font-sans">
            <div>
                <h1 class="text-3xl font-extrabold text-brand-dark tracking-tight font-serif mb-2">
                    {{ $title }}
                </h1>
                <p class="text-gray-500">
                    Menampilkan {{ count($products) }} produk
                </p>
            </div>
            
            <div class="flex items-center gap-3">
                <button class="flex items-center gap-2 px-4 py-2 border border-brand-muted rounded-lg text-sm font-semibold text-brand-dark hover:border-brand-gold transition-colors bg-white focus:outline-none">
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

        <!-- Products Grid -->
        @if(count($products) > 0)
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                @foreach($products as $product)
                    @include('frontend.components.product-card', ['product' => $product])
                @endforeach
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
@endsection

