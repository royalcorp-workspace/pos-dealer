@extends('frontend.layouts.app')

@section('title', 'Buat Password Akun - IMG')
@section('robots', 'noindex,nofollow')

@section('content')
    <div class="min-h-[80vh] bg-brand-light/30 flex items-center justify-center px-4 py-12">
        <div class="w-full max-w-md bg-white rounded-3xl shadow-xl border border-brand-muted/70 p-8 sm:p-10" x-data="{
            password: '',
            password_confirmation: '',
            showPass: false,
            showConfirm: false,
            get hasMinLen() { return this.password.length >= 8; },
            get hasUpper() { return /[A-Z]/.test(this.password); },
            get hasLower() { return /[a-z]/.test(this.password); },
            get hasNumber() { return /[0-9]/.test(this.password); },
            get hasSymbol() { return /[@$!%*?&#^()_+\-=\[\]{};':\",.<>\/]/.test(this.password); },
            get isMatch() { return this.password && this.password === this.password_confirmation; },
            get isValid() { return this.hasMinLen && this.hasUpper && this.hasLower && this.hasNumber && this.hasSymbol && this.isMatch; }
        }">
            <div class="text-center mb-6">
                <div class="w-14 h-14 bg-brand-gold/15 text-brand-gold-dark rounded-2xl flex items-center justify-center mx-auto mb-4 text-2xl shadow-inner">
                    <i class="fa-solid fa-shield-halved"></i>
                </div>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-brand-dark tracking-tight">Atur Password Baru</h1>
                <p class="text-gray-500 text-xs sm:text-sm mt-2 leading-relaxed">
                    Demi keamanan akun Anda, silakan buat password yang kuat untuk melengkapi akun Google Anda.
                </p>
            </div>

            @if(session('error'))
                <div class="mb-5 p-3.5 bg-red-50 border border-red-200 text-red-700 rounded-xl text-xs flex items-center gap-2">
                    <i class="fa-solid fa-circle-exclamation text-red-500"></i>
                    <span>{{ session('error') }}</span>
                </div>
            @endif

            @if($errors->any())
                <div class="mb-5 p-3.5 bg-red-50 border border-red-200 text-red-700 rounded-xl text-xs space-y-1">
                    @foreach($errors->all() as $err)
                        <div class="flex items-center gap-2">
                            <i class="fa-solid fa-circle-exclamation text-red-500"></i>
                            <span>{{ $err }}</span>
                        </div>
                    @endforeach
                </div>
            @endif

            <form action="{{ route('auth.set-password.process') }}" method="POST" class="space-y-4">
                @csrf

                <!-- Password Input -->
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">
                        Password Baru <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <input
                            :type="showPass ? 'text' : 'password'"
                            name="password"
                            x-model="password"
                            required
                            placeholder="Minimal 8 karakter..."
                            class="w-full pl-4 pr-11 py-3 bg-brand-light/40 border border-brand-muted rounded-xl text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-brand-gold/50 focus:border-brand-gold transition-colors font-medium"
                        />
                        <button
                            type="button"
                            @click="showPass = !showPass"
                            class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-gray-400 hover:text-brand-dark focus:outline-none cursor-pointer"
                        >
                            <i class="fa-regular" :class="showPass ? 'fa-eye-slash' : 'fa-eye'"></i>
                        </button>
                    </div>
                </div>

                <!-- Password Requirements Checklist -->
                <div class="bg-gray-50 border border-gray-100 rounded-xl p-3 space-y-1.5 text-xs text-gray-600">
                    <p class="font-bold text-[11px] text-gray-500 uppercase tracking-wider mb-1">Kriteria Password Kuat:</p>
                    <div class="flex items-center gap-2" :class="hasMinLen ? 'text-green-600 font-semibold' : 'text-gray-400'">
                        <i class="fa-solid" :class="hasMinLen ? 'fa-circle-check text-green-500' : 'fa-circle-notch'"></i>
                        <span>Minimal 8 karakter</span>
                    </div>
                    <div class="flex items-center gap-2" :class="hasUpper ? 'text-green-600 font-semibold' : 'text-gray-400'">
                        <i class="fa-solid" :class="hasUpper ? 'fa-circle-check text-green-500' : 'fa-circle-notch'"></i>
                        <span>Mengandung setidaknya 1 huruf besar (A-Z)</span>
                    </div>
                    <div class="flex items-center gap-2" :class="hasLower ? 'text-green-600 font-semibold' : 'text-gray-400'">
                        <i class="fa-solid" :class="hasLower ? 'fa-circle-check text-green-500' : 'fa-circle-notch'"></i>
                        <span>Mengandung setidaknya 1 huruf kecil (a-z)</span>
                    </div>
                    <div class="flex items-center gap-2" :class="hasNumber ? 'text-green-600 font-semibold' : 'text-gray-400'">
                        <i class="fa-solid" :class="hasNumber ? 'fa-circle-check text-green-500' : 'fa-circle-notch'"></i>
                        <span>Mengandung setidaknya 1 angka (0-9)</span>
                    </div>
                    <div class="flex items-center gap-2" :class="hasSymbol ? 'text-green-600 font-semibold' : 'text-gray-400'">
                        <i class="fa-solid" :class="hasSymbol ? 'fa-circle-check text-green-500' : 'fa-circle-notch'"></i>
                        <span>Mengandung simbol khusus (contoh: @, #, $, !)</span>
                    </div>
                </div>

                <!-- Confirm Password Input -->
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">
                        Konfirmasi Password <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <input
                            :type="showConfirm ? 'text' : 'password'"
                            name="password_confirmation"
                            x-model="password_confirmation"
                            required
                            placeholder="Ulangi password..."
                            class="w-full pl-4 pr-11 py-3 bg-brand-light/40 border border-brand-muted rounded-xl text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-brand-gold/50 focus:border-brand-gold transition-colors font-medium"
                        />
                        <button
                            type="button"
                            @click="showConfirm = !showConfirm"
                            class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-gray-400 hover:text-brand-dark focus:outline-none cursor-pointer"
                        >
                            <i class="fa-regular" :class="showConfirm ? 'fa-eye-slash' : 'fa-eye'"></i>
                        </button>
                    </div>
                    <div x-show="password_confirmation.length > 0" class="mt-1.5 text-xs">
                        <span x-show="isMatch" class="text-green-600 font-semibold flex items-center gap-1">
                            <i class="fa-solid fa-circle-check"></i> Password cocok
                        </span>
                        <span x-show="!isMatch" class="text-red-500 font-semibold flex items-center gap-1">
                            <i class="fa-solid fa-circle-xmark"></i> Password tidak cocok
                        </span>
                    </div>
                </div>

                <div class="pt-2">
                    <button
                        type="submit"
                        :disabled="!isValid"
                        class="w-full py-3.5 bg-brand-dark hover:bg-brand-darker disabled:opacity-50 disabled:cursor-not-allowed text-brand-gold font-bold rounded-xl shadow-lg shadow-brand-dark/20 transition-all cursor-pointer"
                    >
                        Simpan & Lanjutkan ke Akun
                    </button>
                </div>
            </form>

            <div class="mt-6 text-center">
                <a href="{{ route('home') }}" class="text-xs font-semibold text-gray-500 hover:text-brand-dark transition-colors">&larr; Kembali ke Beranda</a>
            </div>
        </div>
    </div>
@endsection
