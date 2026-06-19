@extends('frontend.layouts.app')

@section('title', 'Register Berhasil - IMG')
@section('robots', 'noindex,nofollow')

@section('content')
<div class="container mx-auto px-4 md:px-6 py-20 min-h-[60vh] font-sans">
    <div class="max-w-lg mx-auto text-center">
        <div class="w-20 h-20 bg-brand-gold/20 rounded-full flex items-center justify-center mx-auto mb-6">
            <i class="fa-solid fa-envelope w-10 h-10 text-brand-gold"></i>
        </div>
        <h1 class="text-3xl font-extrabold text-brand-dark font-serif mb-4">Register Berhasil!</h1>
        <p class="text-gray-600 mb-6">
            User created. Verification email sent to your inbox. Silakan cek email Anda untuk verifikasi akun.
        </p>
        <div class="flex gap-3 justify-center">
            <a href="{{ route('login') }}" class="px-6 py-3 bg-brand-dark text-brand-gold rounded-xl font-bold hover:bg-brand-darker transition-colors">
                Ke Login
            </a>
            <a href="{{ route('home') }}" class="px-6 py-3 border border-brand-muted rounded-xl font-bold text-brand-dark hover:border-brand-gold transition-colors">
                Ke Home
            </a>
        </div>
    </div>
</div>
@endsection