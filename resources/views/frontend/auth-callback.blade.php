@extends('frontend.layouts.app')

@section('title', 'Memproses Login Google - IMG')
@section('robots', 'noindex,nofollow')

@section('content')
    <div class="container mx-auto px-4 md:px-6 py-12 min-h-[70vh] font-sans" data-route-home="{{ route('home') }}" data-route-auth-google-session="{{ route('auth.google.session') }}">
        <div class="max-w-xl mx-auto text-center bg-white border border-brand-muted rounded-3xl p-8 shadow-sm">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-brand-light text-brand-gold mb-6">
                <i class="fa-solid fa-circle-notch w-7 h-7 animate-spin"></i>
            </div>

            <h1 class="text-2xl md:text-3xl font-extrabold text-brand-dark font-serif mb-3">
                Memproses Login Google
            </h1>

            <p class="text-gray-500">
                Mohon tunggu, kami sedang menyimpan sesi login Anda.
            </p>
        </div>
    </div>
@endsection
