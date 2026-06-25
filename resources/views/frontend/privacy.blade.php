@extends('frontend.layouts.app')

@section('title', 'Kebijakan Privacy - IMG')
@section('meta_description', 'Kebijakan privasi data pribadi pengguna layanan IMG.')
@section('canonical', route('privacy'))

@section('content')
<div class="container mx-auto px-4 md:px-6 py-12 min-h-[70vh] font-sans">
    <div class="max-w-4xl mx-auto">
        <h1 class="text-3xl md:text-4xl font-extrabold text-brand-dark font-serif mb-6">Kebijakan Privacy</h1>
        
        <div class="prose prose-lg max-w-none text-gray-600">
            <h2 class="text-xl font-bold text-brand-dark mt-6 mb-3">Pengumpulan Data</h2>
            <p class="mb-4">Kami hanya mengumpulkan data yang diperlukan untuk proses pemesanan dan pengiriman.</p>
            
            <h2 class="text-xl font-bold text-brand-dark mt-6 mb-3">Penggunaan Data</h2>
            <p class="mb-4">Data Anda digunakan semata untuk keperluan internal perusahaan dan tidak akan dipublikasikan tanpa izin.</p>
            
            <h2 class="text-xl font-bold text-brand-dark mt-6 mb-3">Keamanan Data</h2>
            <p class="mb-4">Kami menggunakan enkripsi SSL dan sistem keamanan modern untuk melindungi data Anda.</p>
        </div>
    </div>
</div>
@endsection