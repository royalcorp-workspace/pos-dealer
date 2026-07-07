@extends('frontend.layouts.app')

@section('title', 'Klaim Garansi - IMG')
@section('meta_description', 'Panduan dan informasi klaim garansi produk IMG. Dapatkan proses pengajuan garansi yang mudah dan cepat.')
@section('canonical', route('warranty'))

@section('content')
    <div class="container mx-auto px-4 md:px-6 py-12 min-h-[70vh] font-sans">
        <div class="max-w-4xl mx-auto">
            <h1 class="text-3xl md:text-4xl font-extrabold text-brand-dark font-serif mb-6">Klaim Garansi</h1>
            
            <div class="prose prose-lg max-w-none text-gray-600">
                <h2 class="text-xl font-bold text-brand-dark mt-6 mb-3">Prosedur Klaim Garansi</h2>
                <ol class="list-decimal pl-6 mb-4 space-y-2">
                    <li>Hubungi layanan pelanggan IMG melalui WhatsApp atau telepon</li>
                    <li>Siapkan bukti pembelian (invoice/struk)</li>
                    <li>Foto produk yang mengalami kerusakan</li>
                    <li>Isi formulir klaim garansi</li>
                    <li>Tim kami akan memverifikasi dan memandu proses selanjutnya</li>
                </ol>
                
                <h2 class="text-xl font-bold text-brand-dark mt-6 mb-3">Periode Garansi</h2>
                <p class="mb-4">Produk kami dilengkapi garansi sesuai ketentuan masing-masing brand, biasanya antara 5-10 tahun.</p>
            </div>
        </div>
    </div>
@endsection