@extends('frontend.layouts.app')

@section('title', 'Daftar Akun - IMG')
@section('robots', 'noindex,nofollow')

@section('content')
    <div class="min-h-screen bg-brand-light/40 flex items-center justify-center px-4 py-12">
        <div class="w-full max-w-md bg-white rounded-3xl shadow-xl p-8 sm:p-10">
            <div class="text-center mb-8">
                <h1 class="text-3xl font-extrabold text-brand-dark tracking-tight">Daftar Akun</h1>
                <p class="text-gray-500 text-sm mt-3">Buat akun baru untuk berbelanja.</p>
            </div>

            <div
                x-data="{
                    email: '',
                    password: '',
                    loading: false,
                    errorMessage: '',
                    async handleSubmit() {
                        this.loading = true;
                        this.errorMessage = '';

                        try {
                            const response = await fetch('/api/auth/register', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                                },
                                body: JSON.stringify({
                                    email: this.email,
                                    password: this.password,
                                }),
                            });

                            const data = await response.json();

                            if (!response.ok) {
                                this.errorMessage = data.message || 'Terjadi kesalahan. Silakan coba lagi.';
                                return;
                            }

                            // Success — redirect to register-success or verify email page
                            window.location.href = '/register-success';
                        } catch (e) {
                            this.errorMessage = 'Koneksi gagal. Periksa internet Anda dan coba lagi.';
                        } finally {
                            this.loading = false;
                        }
                    }
                }"
            >
                {{-- Error Alert --}}
                <div
                    x-show="errorMessage"
                    x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0 -translate-y-2"
                    x-transition:enter-end="opacity-100 translate-y-0"
                    x-cloak
                    class="mb-5 flex items-start gap-3 bg-red-50 border border-red-200 text-red-700 rounded-xl px-4 py-3 text-sm"
                    role="alert"
                >
                    <svg class="h-5 w-5 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <circle cx="12" cy="12" r="10" stroke-width="1.6"/>
                        <path stroke-linecap="round" stroke-width="1.6" d="M12 8v4m0 4h.01"/>
                    </svg>
                    <span x-text="errorMessage"></span>
                </div>

                <form class="space-y-5" @submit.prevent="handleSubmit">
                    <div>
                        <label class="block text-xs font-bold text-brand-darker uppercase tracking-wider mb-2">Email Address</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-gray-400" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M3 7.5v9a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-9a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2Z" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/><path d="m3 7.5 9 6 9-6" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            </div>
                            <input
                                type="email"
                                name="email"
                                x-model="email"
                                required
                                placeholder="you@example.com"
                                class="w-full pl-11 pr-4 py-3 bg-brand-light border border-brand-muted rounded-xl text-gray-800 focus:outline-none focus:ring-2 focus:ring-brand-gold/50 focus:border-brand-gold transition-colors"
                            />
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-brand-darker uppercase tracking-wider mb-2">Password</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-gray-400" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><rect x="5" y="11" width="14" height="9" rx="2" stroke="currentColor" stroke-width="1.6"/><path d="M8 11V8a4 4 0 0 1 8 0v3" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
                            </div>
                            <input
                                type="password"
                                name="password"
                                x-model="password"
                                required
                                placeholder="Minimal 6 karakter"
                                minlength="6"
                                class="w-full pl-11 pr-4 py-3 bg-brand-light border border-brand-muted rounded-xl text-gray-800 focus:outline-none focus:ring-2 focus:ring-brand-gold/50 focus:border-brand-gold transition-colors"
                            />
                        </div>
                    </div>

                    <button
                        type="submit"
                        :disabled="loading"
                        class="w-full py-3.5 bg-brand-dark hover:bg-brand-darker text-brand-gold font-bold rounded-xl shadow-lg shadow-brand-dark/20 transition-transform active:scale-[0.98] focus:outline-none disabled:opacity-60 disabled:cursor-not-allowed flex items-center justify-center gap-2"
                    >
                        <svg x-show="loading" class="animate-spin h-5 w-5" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
                        </svg>
                        <span x-text="loading ? 'Mendaftar...' : 'Create Account'"></span>
                    </button>
                </form>
            </div>

            <div class="mt-6 text-center">
                <a href="{{ route('home') }}" class="text-sm font-semibold text-brand-gold hover:text-brand-dark transition-colors">&larr; Kembali ke Beranda</a>
            </div>
        </div>
    </div>
@endsection