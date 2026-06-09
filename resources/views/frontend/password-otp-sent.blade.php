@extends('frontend.layouts.app')

@section('content')
    <div class="min-h-screen bg-brand-light/40 flex items-center justify-center px-4 py-12">
        <div class="w-full max-w-md bg-white rounded-3xl shadow-xl p-8 sm:p-10 text-center">
            <div class="mb-6">
                <svg class="w-16 h-16 mx-auto text-green-500" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="1.6"/>
                    <path d="M6 12l3 3 6-6" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </div>
            <h1 class="text-2xl font-extrabold text-brand-dark mb-3">Cek Email Anda</h1>
            <p class="text-gray-600 mb-6">Kami telah mengirimkan link reset password ke email <strong x-text="email"></strong>. Klik link di email Anda untuk melanjutkan.</p>
            <div class="space-y-3">
                <a href="{{ route('reset-password.show', ['email' => request()->query('email', '')]) }}" 
                   class="block w-full py-3 bg-brand-dark hover:bg-brand-darker text-brand-gold font-bold rounded-xl shadow-lg transition-transform active:scale-[0.98]">
                    Lanjutkan ke Reset Password
                </a>
                <a href="{{ route('forgot-password.show') }}" class="block w-full py-2 text-sm font-semibold text-gray-600 hover:text-brand-dark transition-colors">
                    &larr; Kirim ulang kode OTP
                </a>
            </div>
        </div>
    </div>
@endsection