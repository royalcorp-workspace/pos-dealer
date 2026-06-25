@extends('frontend.layouts.app')

@section('title', 'Verifikasi Email - IMG')
@section('robots', 'noindex,nofollow')

@section('content')
    <div class="min-h-screen bg-brand-light/40 flex items-center justify-center px-4 py-12">
        <div class="w-full max-w-md bg-white rounded-3xl shadow-xl p-8 sm:p-10 text-center">
            <div class="w-20 h-20 mx-auto mb-6 rounded-full bg-brand-gold/10 flex items-center justify-center text-brand-gold text-4xl">
                ✉
            </div>
            
            <h1 class="text-3xl font-extrabold text-brand-dark tracking-tight mb-3">Verifikasi Email Diperlukan</h1>
            <p class="text-gray-500 text-sm mb-6">
                Kami telah mengirimkan link verifikasi ke email Anda.<br>
                Silakan cek inbox atau folder spam untuk mengaktifkan akun.
            </p>
            
            <div class="p-4 bg-brand-light/50 rounded-xl mb-6">
                <p class="text-sm text-gray-500">Tidak menerima email?</p>
                <p class="text-xs text-gray-400 mt-1">
                    • Periksa folder spam<br>
                    • Pastikan alamat email benar<br>
                    • Tunggu beberapa menit
                </p>
            </div>
            
            <a href="{{ route('home') }}" class="text-sm font-semibold text-brand-gold hover:text-brand-dark transition-colors">
                &larr; Kembali ke Beranda
            </a>
        </div>
    </div>
@endsection