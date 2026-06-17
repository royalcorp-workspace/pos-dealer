@extends('frontend.layouts.app')

@section('title', 'Terima Kasih - IMG')

@php
    $order = [
        'id' => session('order_id') ?? 'ORD-' . date('Ymd') . '-' . rand(1000, 9999),
    ];
@endphp

@section('content')
    <div class="container mx-auto px-4 md:px-6 py-20 min-h-[60vh] font-sans text-center">
        <div class="max-w-2xl mx-auto">
            <div class="w-24 h-24 bg-brand-gold/20 rounded-full flex items-center justify-center mx-auto mb-8">
                <i class="fa-solid fa-check w-12 h-12 text-brand-gold"></i>
            </div>
            
            <h1 class="text-4xl font-extrabold text-brand-dark mb-4 font-serif">Terima Kasih!</h1>
            <p class="text-gray-500 mb-8">
                Pesanan Anda berhasil dibuat. Kami akan segera memproses pembayaran Anda.
            </p>
            
            <div class="bg-white border border-brand-muted rounded-2xl p-8 mb-8 text-left">
                <h3 class="font-bold text-brand-dark mb-4">Detail Pesanan</h3>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-500">ID Pesanan</span>
                        <span class="font-mono font-bold text-brand-gold-dark">{{ $order['id'] }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Metode Pembayaran</span>
                        <span class="font-medium">Bank Transfer</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Status</span>
                        <span class="font-medium text-brand-gold">Menunggu Pembayaran</span>
                    </div>
                </div>
            </div>
            
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="{{ route('home') }}" class="px-8 py-3 bg-brand-dark text-brand-gold rounded-xl font-bold hover:bg-brand-darker transition-colors">
                    Kembali ke Home
                </a>
                <a href="{{ route('dashboard') }}" class="px-8 py-3 border border-brand-muted text-brand-dark rounded-xl font-bold hover:border-brand-gold transition-colors">
                    Lihat Pesanan
                </a>
            </div>
        </div>
    </div>
@endsection