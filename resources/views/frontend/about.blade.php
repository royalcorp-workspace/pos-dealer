@extends('frontend.layouts.app')

@section('title', 'Tentang Kami - IMG')
@section('meta_description', 'IMG International Mattress Gallery - Toko kasur dan perlengkapan tidur premium dengan koleksi lengkap springbed, bantal, dan aksesori tidur berkualitas.')
@section('canonical', route('about'))

@section('content')
    <div class="container mx-auto px-4 md:px-6 py-12 min-h-[70vh] font-sans">
        <div class="max-w-4xl mx-auto">
            <h1 class="text-3xl md:text-4xl font-extrabold text-brand-dark font-serif mb-6">Tentang Kami</h1>
            
            <div class="prose prose-lg max-w-none text-gray-600">
                <p class="mb-4">IMG International Mattress Gallery adalah toko kasur dan perlengkapan tidur premium yang berkomitmen menyediakan kenyamanan tidur terbaik untuk Anda.</p>
                
                <p class="mb-4">Kami menampilkan koleksi produk dari berbagai brand matras dan perlengkapan tidur terkemuka dunia dengan standar kualitas internasional.</p>
                
                <h2 class="text-xl font-bold text-brand-dark mt-8 mb-3">Visi & Misi</h2>
                <p class="mb-4">Visi kami adalah menjadi mitra utama Anda dalam menciptakan kualitas tidur yang optimal. Kami menyediakan produk dengan garansi resmi, layanan konsultasi gratis, dan program cicilan 0%.</p>
            </div>
        </div>
    </div>
@endsection