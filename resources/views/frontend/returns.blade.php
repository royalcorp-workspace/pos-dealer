@extends('frontend.layouts.app')

@section('title', $returns->meta_title ?? 'How To Return - IMG')
@section('meta_description', $returns->meta_description ?? 'Panduan langkah demi langkah untuk pengembalian produk IMG.')
@section('canonical', route('returns'))

@section('content')
    <div class="container mx-auto px-4 md:px-6 py-12 min-h-[70vh] font-sans">
        <div class="max-w-4xl mx-auto">
            @if($returns)
                <h1 class="text-3xl md:text-4xl font-extrabold text-brand-dark font-serif mb-6">{{ $returns->title }}</h1>
                
                @if($returns->featured_image)
                    <div class="mb-8 rounded-2xl overflow-hidden aspect-video shadow-md">
                        <img src="{{ asset('storage/' . $returns->featured_image) }}" alt="{{ $returns->title }}" class="w-full h-full object-cover">
                    </div>
                @endif
                
                <div class="prose prose-lg max-w-none text-gray-600 space-y-6">
                    <div>
                        {!! $returns->content !!}
                    </div>
                    
                    @if(!empty($returns->steps))
                        <div>
                            <h2 class="text-xl font-bold text-brand-dark mt-6 mb-3">Langkah-langkah Pengembalian</h2>
                            <ol class="list-decimal pl-6 mb-4 space-y-2">
                                @foreach($returns->steps as $step)
                                    <li>{{ $step }}</li>
                                @endforeach
                            </ol>
                        </div>
                    @endif
                </div>
            @else
                <h1 class="text-3xl md:text-4xl font-extrabold text-brand-dark font-serif mb-6">How To Return</h1>
                
                <div class="prose prose-lg max-w-none text-gray-600">
                    <h2 class="text-xl font-bold text-brand-dark mt-6 mb-3">Langkah-langkah Pengembalian</h2>
                    <ol class="list-decimal pl-6 mb-4 space-y-2">
                        <li>Hubungi customer service dalam 7 hari sejak penerimaan barang</li>
                        <li>Sertakan foto/video dalam kondisi tidak terpakai</li>
                        <li>Packing kembali seperti semula dengan semua kelengkapan</li>
                        <li>Tim kami akan mengirim kurir untuk pengambilan barang</li>
                        <li>Refund atau penggantian akan diproses setelah verifikasi</li>
                    </ol>
                    
                    <h2 class="text-xl font-bold text-brand-dark mt-6 mb-3">Produk yang Tidak Bisa Dikembalikan</h2>
                    <p class="mb-4">Produk yang sudah dipakai atau terkena cairan tidak dapat dikembalikan.</p>
                </div>
            @endif
        </div>
    </div>
@endsection