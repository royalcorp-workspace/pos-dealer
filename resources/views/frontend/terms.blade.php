@extends('frontend.layouts.app')

@section('title', 'Syarat dan Ketentuan - IMG')
@section('meta_description', 'Syarat dan ketentuan penggunaan layanan IMG International Mattress Gallery.')
@section('canonical', route('terms'))

@section('content')
<div class="container mx-auto px-4 md:px-6 py-12 min-h-[70vh] font-sans">
    <div class="max-w-4xl mx-auto">
        <h1 class="text-3xl md:text-4xl font-extrabold text-brand-dark font-serif mb-6">Syarat dan Ketentuan</h1>
        
        <div class="prose prose-lg max-w-none text-gray-600">
            <h2 class="text-xl font-bold text-brand-dark mt-6 mb-3">Ketentuan Umum</h2>
            <p class="mb-4">Dengan menggunakan layanan kami, Anda dianggap telah membaca dan menerima syarat serta ketentuan yang berlaku.</p>
            
            <h2 class="text-xl font-bold text-brand-dark mt-6 mb-3">Pemesanan</h2>
            <p class="mb-4">Pesanan dapat dibatalkan dalam 24 jam sejak pembayaran dengan syarat produk belum dikirim.</p>
            
            <h2 class="text-xl font-bold text-brand-dark mt-6 mb-3">Pengiriman</h2>
            <p class="mb-4">Kami tidak bertanggung jawab atas keterlambatan pengiriman yang diakibatkan oleh pihak ketiga.</p>
        </div>
    </div>
</div>
@endsection