@extends('frontend.layouts.app')

@section('title', 'Lacak Pesanan - IMG')

@section('content')
<div class="container mx-auto px-4 py-16 min-h-[70vh] flex items-center justify-center">
    <div class="max-w-md w-full bg-white rounded-3xl p-8 border border-gray-100 shadow-xl shadow-brand-dark/5">
        
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-brand-light rounded-2xl mb-4 text-brand-gold-dark">
                <i class="fa-solid fa-magnifying-glass text-2xl"></i>
            </div>
            <h1 class="text-2xl font-bold text-brand-dark font-serif">Lacak Pesanan Anda</h1>
            <p class="text-gray-500 text-sm mt-2">Masukkan nomor pesanan beserta email atau nomor HP yang Anda gunakan saat checkout.</p>
        </div>

        @if(session('error'))
            <div class="bg-red-50 text-red-600 p-4 rounded-xl mb-6 text-sm font-medium border border-red-100 flex items-center gap-2">
                <i class="fa-solid fa-circle-exclamation"></i>
                {{ session('error') }}
            </div>
        @endif

        <form action="{{ route('track-order.process') }}" method="POST" class="space-y-5">
            @csrf
            
            <div class="space-y-1.5">
                <label class="text-sm font-bold text-gray-700">Nomor Pesanan <span class="text-red-500">*</span></label>
                <input type="text" name="order_number" class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-brand-gold focus:ring-1 focus:ring-brand-gold outline-none transition-all" placeholder="Contoh: ORD12345678" required>
            </div>
            
            <div class="space-y-1.5">
                <label class="text-sm font-bold text-gray-700">Email / Nomor HP <span class="text-red-500">*</span></label>
                <input type="text" name="contact" class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-brand-gold focus:ring-1 focus:ring-brand-gold outline-none transition-all" placeholder="Email atau Nomor HP saat pesan" required>
            </div>
            
            <button type="submit" class="w-full py-3.5 bg-brand-dark text-brand-gold rounded-xl font-bold hover:bg-brand-darker transition-colors shadow-lg">
                Cari Pesanan
            </button>
        </form>
    </div>
</div>
@endsection
