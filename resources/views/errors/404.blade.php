@extends('frontend.layouts.app')

@section('title', '404 - Halaman Tidak Ditemukan - IMG')

@section('content')
<div class="container mx-auto px-4 md:px-6 py-12 min-h-[70vh] font-sans">
    <div class="max-w-4xl mx-auto text-center">
        <div class="inline-flex items-center justify-center w-24 h-24 rounded-full bg-brand-light text-brand-gold mb-8 shadow-sm">
            <i class="fa-solid fa-magnifying-glass-location w-10 h-10"></i>
        </div>

        <p class="text-brand-gold-dark font-bold tracking-[0.3em] uppercase text-sm mb-4">404 - Halaman Tidak Ditemukan</p>

        <h1 class="text-4xl md:text-6xl font-extrabold text-brand-dark tracking-tight font-serif mb-6">
            Ups, halaman yang Anda cari tidak ditemukan.
        </h1>

        <p class="text-gray-500 text-lg max-w-2xl mx-auto leading-relaxed">
            Mungkin halaman sudah dipindahkan, dihapus, atau URL yang dimasukkan kurang tepat.
        </p>

        <div class="flex flex-col sm:flex-row items-center justify-center gap-4 mt-10">
            <a href="{{ route('home') }}" class="px-8 py-4 rounded-full font-bold text-white bg-brand-dark hover:bg-brand-darker transition-all shadow-lg shadow-brand-dark/20">
                Kembali ke Home
            </a>
            <a href="{{ route('products.index') }}" class="px-8 py-4 rounded-full font-bold text-brand-dark bg-white border-2 border-brand-dark hover:bg-brand-dark hover:text-white transition-all">
                Lihat Produk
            </a>
        </div>
    </div>
</div>
@endsection
