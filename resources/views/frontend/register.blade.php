@extends('frontend.layouts.app')

@section('title', 'Daftar Akun - IMG')
@section('robots', 'noindex,nofollow')

@section('content')
@php
$locationData = \App\Models\Frontend\Location\SubDistrict::with(['city.province'])->get()->map(function($sd) {
    return [
        'id' => $sd->id,
        'label' => $sd->sub_district,
        'city' => $sd->city->name ?? '',
        'province' => $sd->city->province->name ?? ''
    ];
})->toArray();
@endphp

<script type="application/json" id="address-location-options">
@json($locationData)
</script>

    <div class="min-h-screen bg-brand-light/40 flex items-center justify-center px-4 py-12">
        <div class="w-full max-w-md bg-white rounded-3xl shadow-xl p-8 sm:p-10 relative overflow-hidden">
            <div class="text-center mb-8">
                <h1 class="text-3xl font-extrabold text-brand-dark tracking-tight">Daftar Akun</h1>
                <p class="text-gray-500 text-sm mt-3">Buat akun baru untuk berbelanja.</p>
            </div>

            <div x-data="{
                     name: '{{ request()->query('name', '') }}',
                     email: '{{ request()->query('email', '') }}',
                     password: '',
                     password_confirmation: '',
                     phone: '',
                     address: '',
                     sub_district_id: '',
                     google_id: '{{ request()->query('google_id', '') }}',
                     firebase_token: '{{ request()->query('firebase_token', '') }}',
                     loading: false,
                     errorMessage: '',
                     district_search: '',
                     district_open: false,
                     district_options: {},
                     district_filtered: [],
                     showPassword: false,
                     showConfirmPassword: false,

                     get hasMinLength() { return this.password.length >= 8; },
                     get hasUppercase() { return /[A-Z]/.test(this.password); },
                     get hasLowercase() { return /[a-z]/.test(this.password); },
                     get hasNumber() { return /[0-9]/.test(this.password); },
                     get hasSpecial() { return /[\W_]/.test(this.password); },
                     get isPasswordMatch() {
                         return this.password && this.password_confirmation && this.password === this.password_confirmation;
                     },

                     get passwordScore() {
                         if (!this.password) return 0;
                         let score = 0;
                         if (this.hasMinLength) score++;
                         if (this.hasUppercase && this.hasLowercase) score++;
                         if (this.hasNumber) score++;
                         if (this.hasSpecial) score++;
                         return score;
                     },

                     get isPasswordStrong() {
                         return this.hasMinLength && this.hasUppercase && this.hasLowercase && this.hasNumber && this.hasSpecial;
                     },

                     getBarColor(index) {
                         if (this.passwordScore < index) return 'bg-gray-200';
                         if (this.passwordScore <= 1) return 'bg-red-500';
                         if (this.passwordScore === 2) return 'bg-amber-500';
                         if (this.passwordScore === 3) return 'bg-yellow-500';
                         return 'bg-emerald-500';
                     },

                     get strengthLabel() {
                         if (!this.password) return '';
                         switch(this.passwordScore) {
                             case 1: return 'Sangat Lemah';
                             case 2: return 'Cukup Lemah';
                             case 3: return 'Sedang (Hampir Memenuhi)';
                             case 4: return 'Sangat Kuat & Aman';
                             default: return 'Terlalu Pendek';
                         }
                     },

                     get strengthColorClass() {
                         if (!this.password) return 'text-gray-400';
                         switch(this.passwordScore) {
                             case 1: return 'text-red-500';
                             case 2: return 'text-amber-500';
                             case 3: return 'text-yellow-600';
                             case 4: return 'text-emerald-600';
                             default: return 'text-red-500';
                         }
                     },

                     init() {
                         const opts = JSON.parse(document.getElementById('address-location-options').textContent || '[]');
                         this.district_options = opts.reduce((groups, item) => {
                             if (!groups[item.province]) groups[item.province] = { province: item.province, cities: {} };
                             if (!groups[item.province].cities[item.city]) {
                                 groups[item.province].cities[item.city] = { city: item.city, options: [] };
                             }
                             groups[item.province].cities[item.city].options.push({ id: item.id, label: item.label });
                             return groups;
                         }, {});
                         this.district_filtered = Object.values(this.district_options).map(p => ({
                             province: p.province,
                             cities: Object.values(p.cities)
                         }));
                     },
                     filterDistrict() {
                         this.district_filtered = Object.values(this.district_options).map(p => ({
                             province: p.province,
                             cities: Object.values(p.cities).map(c => ({
                                 city: c.city,
                                 options: c.options.filter(o => o.label.toLowerCase().includes(this.district_search.toLowerCase()) || c.city.toLowerCase().includes(this.district_search.toLowerCase()))
                             })).filter(c => c.options.length)
                         })).filter(p => p.cities.length);
                     },
                     selectDistrict(option) {
                         this.sub_district_id = option.id;
                         this.district_search = option.label;
                         this.district_open = false;
                     },
                     async handleSubmit() {
                        this.errorMessage = '';

                        if (!this.google_id) {
                            if (!this.isPasswordStrong) {
                                this.errorMessage = 'Password belum memenuhi kriteria keamanan: minimal 8 karakter dengan kombinasi huruf besar, huruf kecil, angka, dan simbol khusus.';
                                return;
                            }
                            if (this.password !== this.password_confirmation) {
                                this.errorMessage = 'Konfirmasi password tidak cocok dengan password yang dimasukkan.';
                                return;
                            }
                        }

                        this.loading = true;

                        try {
                            const response = await fetch('/api/auth/register', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                                },
                                body: JSON.stringify({
                                    name: this.name,
                                    email: this.email,
                                    password: this.password,
                                    password_confirmation: this.password_confirmation,
                                    phone: this.phone,
                                    address: this.address,
                                    sub_district_id: this.sub_district_id,
                                    google_id: this.google_id,
                                    firebase_token: this.firebase_token,
                                }),
                            });

                            const data = await response.json();

                            if (!response.ok) {
                                if (data.errors) {
                                    this.errorMessage = Object.values(data.errors).flat().join('<br>');
                                } else {
                                    this.errorMessage = data.message || 'Terjadi kesalahan. Silakan coba lagi.';
                                }
                                return;
                            }

                            if (data.access_token && data.refresh_token) {
                                try {
                                    await fetch('/auth/google/session', {
                                        method: 'POST',
                                        headers: {
                                            'Content-Type': 'application/json',
                                            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                                            'Accept': 'application/json'
                                        },
                                        body: JSON.stringify({
                                            access_token: data.access_token,
                                            refresh_token: data.refresh_token
                                        })
                                    });
                                } catch (e) {
                                    console.error('Failed to set web session', e);
                                }
                            }

                            if (data.redirect) {
                                window.location.href = data.redirect;
                            } else {
                                window.location.href = '/register-success';
                            }
                        } catch (e) {
                            this.errorMessage = 'Koneksi gagal. Periksa internet Anda dan coba lagi.';
                        } finally {
                            this.loading = false;
                        }
                    }
                }"
            >
                <!-- Interactive Full-Card Registration Loading Overlay -->
                <div 
                    x-show="loading" 
                    x-transition:enter="transition ease-out duration-300"
                    x-transition:enter-start="opacity-0 backdrop-blur-none"
                    x-transition:enter-end="opacity-100 backdrop-blur-md"
                    x-transition:leave="transition ease-in duration-200"
                    x-transition:leave-start="opacity-100 backdrop-blur-md"
                    x-transition:leave-end="opacity-0 backdrop-blur-none"
                    x-cloak 
                    class="absolute inset-0 bg-white/95 backdrop-blur-md rounded-3xl z-50 flex flex-col items-center justify-center p-8 text-center"
                >
                    <!-- Animated Multi-layer Rings Spinner -->
                    <div class="relative w-24 h-24 mb-6 flex items-center justify-center">
                        <div class="absolute inset-0 rounded-full border-4 border-brand-gold/20 animate-ping"></div>
                        <div class="absolute inset-0 rounded-full border-4 border-brand-gold/30 border-t-brand-dark animate-spin"></div>
                        <div class="absolute inset-2.5 rounded-full border-4 border-transparent border-b-brand-gold border-l-brand-gold animate-spin" style="animation-direction: reverse; animation-duration: 1.2s;"></div>
                        <div class="relative w-12 h-12 rounded-full bg-brand-light flex items-center justify-center shadow-inner">
                            <i class="fa-solid fa-user-check text-brand-dark text-xl"></i>
                        </div>
                    </div>

                    <h3 class="text-xl font-black text-brand-dark tracking-tight mb-1.5">
                        Menyiapkan Akun Anda...
                    </h3>
                    <p class="text-xs text-gray-500 max-w-xs leading-relaxed mb-5">
                        Mohon tunggu sebentar, sistem sedang memverifikasi data dan mendaftarkan akun belanja Anda.
                    </p>

                    <!-- Animated Progress Bar -->
                    <div class="w-full max-w-xs bg-gray-100 rounded-full h-2 overflow-hidden relative shadow-inner mb-4">
                        <div class="h-full bg-brand-gold rounded-full w-3/4 animate-pulse"></div>
                    </div>

                    <!-- Bouncing Dots -->
                    <div class="flex items-center gap-1.5 text-brand-gold">
                        <span class="inline-block w-2 h-2 rounded-full bg-brand-gold animate-bounce" style="animation-delay: 0ms;"></span>
                        <span class="inline-block w-2 h-2 rounded-full bg-brand-dark animate-bounce" style="animation-delay: 150ms;"></span>
                        <span class="inline-block w-2 h-2 rounded-full bg-brand-gold animate-bounce" style="animation-delay: 300ms;"></span>
                    </div>
                </div>

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
                    <span x-html="errorMessage"></span>
                </div>

                <form class="space-y-4" @submit.prevent="handleSubmit">
                    <div>
                        <label class="block text-xs font-bold text-brand-darker uppercase tracking-wider mb-1">Nama Lengkap</label>
                        <input type="text" x-model="name" required placeholder="Nama lengkap Anda" class="w-full px-4 py-3 bg-brand-light border border-brand-muted rounded-xl text-gray-800 focus:outline-none focus:ring-2 focus:ring-brand-gold/50 focus:border-brand-gold transition-colors" />
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-brand-darker uppercase tracking-wider mb-1">Email Address</label>
                        <input type="email" x-model="email" required placeholder="you@example.com" class="w-full px-4 py-3 bg-brand-light border border-brand-muted rounded-xl text-gray-800 focus:outline-none focus:ring-2 focus:ring-brand-gold/50 focus:border-brand-gold transition-colors" />
                    </div>

                    <!-- Password Field with Strength Indicator -->
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label class="block text-xs font-bold text-brand-darker uppercase tracking-wider">Password</label>
                            <span x-show="password.length > 0" class="text-[11px] font-bold" :class="strengthColorClass" x-text="strengthLabel"></span>
                        </div>
                        <div class="relative">
                            <input 
                                :type="showPassword ? 'text' : 'password'" 
                                x-model="password" 
                                :required="!google_id" 
                                placeholder="Minimal 8 karakter (huruf, angka, simbol)" 
                                minlength="8" 
                                class="w-full px-4 py-3 bg-brand-light border border-brand-muted rounded-xl text-gray-800 focus:outline-none focus:ring-2 focus:ring-brand-gold/50 focus:border-brand-gold transition-colors pr-11" 
                            />
                            <button 
                                type="button" 
                                @click="showPassword = !showPassword" 
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-brand-dark transition-colors p-1"
                                tabindex="-1"
                                :title="showPassword ? 'Sembunyikan password' : 'Lihat password'"
                            >
                                <i class="fa-solid" :class="showPassword ? 'fa-eye-slash' : 'fa-eye'"></i>
                            </button>
                        </div>

                        <!-- 4-Segment Strength Indicator Bar (Red - Yellow - Green) -->
                        <div x-show="password.length > 0" class="grid grid-cols-4 gap-1.5 mt-2 transition-all">
                            <div class="h-1.5 rounded-full transition-all duration-300" :class="getBarColor(1)"></div>
                            <div class="h-1.5 rounded-full transition-all duration-300" :class="getBarColor(2)"></div>
                            <div class="h-1.5 rounded-full transition-all duration-300" :class="getBarColor(3)"></div>
                            <div class="h-1.5 rounded-full transition-all duration-300" :class="getBarColor(4)"></div>
                        </div>

                        <!-- Detailed Password Criteria Checklist -->
                        <div x-show="password.length > 0 && !isPasswordStrong" class="mt-2.5 p-2.5 bg-gray-50 border border-gray-200/80 rounded-xl space-y-1 text-[11px] text-gray-600 transition-all">
                            <div class="font-semibold text-gray-700 mb-1 flex items-center gap-1.5">
                                <i class="fa-solid fa-shield-halved text-brand-gold text-xs"></i>
                                <span>Kriteria Keamanan Password:</span>
                            </div>
                            <div class="flex items-center gap-2" :class="hasMinLength ? 'text-emerald-600 font-semibold' : 'text-gray-500'">
                                <i class="fa-solid" :class="hasMinLength ? 'fa-circle-check text-emerald-500' : 'fa-circle-dot text-gray-300'"></i>
                                <span>Minimal 8 karakter</span>
                            </div>
                            <div class="flex items-center gap-2" :class="(hasUppercase && hasLowercase) ? 'text-emerald-600 font-semibold' : 'text-gray-500'">
                                <i class="fa-solid" :class="(hasUppercase && hasLowercase) ? 'fa-circle-check text-emerald-500' : 'fa-circle-dot text-gray-300'"></i>
                                <span>Huruf besar (A-Z) & huruf kecil (a-z)</span>
                            </div>
                            <div class="flex items-center gap-2" :class="hasNumber ? 'text-emerald-600 font-semibold' : 'text-gray-500'">
                                <i class="fa-solid" :class="hasNumber ? 'fa-circle-check text-emerald-500' : 'fa-circle-dot text-gray-300'"></i>
                                <span>Minimal 1 angka (0-9)</span>
                            </div>
                            <div class="flex items-center gap-2" :class="hasSpecial ? 'text-emerald-600 font-semibold' : 'text-gray-500'">
                                <i class="fa-solid" :class="hasSpecial ? 'fa-circle-check text-emerald-500' : 'fa-circle-dot text-gray-300'"></i>
                                <span>Minimal 1 simbol / karakter khusus (!@#$%^&*)</span>
                            </div>
                        </div>
                    </div>

                    <!-- Confirm Password Field -->
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label class="block text-xs font-bold text-brand-darker uppercase tracking-wider">Konfirmasi Password</label>
                            <span x-show="password_confirmation.length > 0" class="text-[11px] font-semibold flex items-center gap-1" :class="isPasswordMatch ? 'text-emerald-600' : 'text-red-500'">
                                <i class="fa-solid" :class="isPasswordMatch ? 'fa-circle-check' : 'fa-circle-xmark'"></i>
                                <span x-text="isPasswordMatch ? 'Password cocok' : 'Password belum sesuai'"></span>
                            </span>
                        </div>
                        <div class="relative">
                            <input 
                                :type="showConfirmPassword ? 'text' : 'password'" 
                                x-model="password_confirmation" 
                                :required="!google_id" 
                                placeholder="Ulangi password di atas" 
                                minlength="8" 
                                class="w-full px-4 py-3 bg-brand-light border border-brand-muted rounded-xl text-gray-800 focus:outline-none focus:ring-2 focus:ring-brand-gold/50 focus:border-brand-gold transition-colors pr-11" 
                            />
                            <button 
                                type="button" 
                                @click="showConfirmPassword = !showConfirmPassword" 
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-brand-dark transition-colors p-1"
                                tabindex="-1"
                                :title="showConfirmPassword ? 'Sembunyikan password' : 'Lihat password'"
                            >
                                <i class="fa-solid" :class="showConfirmPassword ? 'fa-eye-slash' : 'fa-eye'"></i>
                            </button>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-brand-darker uppercase tracking-wider mb-1">No. Telepon (WhatsApp)</label>
                        <input type="tel" x-model="phone" required placeholder="08xxxxxxxxxx" class="w-full px-4 py-3 bg-brand-light border border-brand-muted rounded-xl text-gray-800 focus:outline-none focus:ring-2 focus:ring-brand-gold/50 focus:border-brand-gold transition-colors" />
                    </div>

                    <div class="relative" @click.outside="district_open = false">
                        <label class="block text-xs font-bold text-brand-darker uppercase tracking-wider mb-1">Kota/Kelurahan</label>
                        <input type="hidden" x-model="sub_district_id" required>
                        <input type="text" x-model="district_search" @input="filterDistrict()" @focus="district_open = true" placeholder="Cari kelurahan..." required class="w-full px-4 py-3 bg-brand-light border border-brand-muted rounded-xl text-gray-800 focus:outline-none focus:ring-2 focus:ring-brand-gold/50 focus:border-brand-gold transition-colors">
                        <div x-show="district_open" class="absolute z-10 mt-1 w-full bg-white border border-gray-200 shadow-xl rounded-xl max-h-60 overflow-y-auto" x-cloak>
                            <template x-for="province in district_filtered" :key="province.province">
                                <div>
                                    <div class="px-3 py-2 font-bold text-brand-dark bg-gray-100 text-sm" x-text="province.province"></div>
                                    <template x-for="city in province.cities" :key="city.city">
                                        <div>
                                            <div class="px-3 py-2 font-semibold text-gray-600 bg-gray-50 pl-4 text-sm" x-text="city.city"></div>
                                            <template x-for="option in city.options" :key="option.id">
                                                <div @click="selectDistrict(option)" class="px-3 py-2 pl-8 cursor-pointer hover:bg-brand-light text-sm" x-text="option.label"></div>
                                            </template>
                                        </div>
                                    </template>
                                </div>
                            </template>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-brand-darker uppercase tracking-wider mb-1">Alamat Lengkap</label>
                        <textarea x-model="address" required rows="2" placeholder="Nama jalan, gedung, no. rumah..." class="w-full px-4 py-3 bg-brand-light border border-brand-muted rounded-xl text-gray-800 focus:outline-none focus:ring-2 focus:ring-brand-gold/50 focus:border-brand-gold transition-colors"></textarea>
                    </div>

                    <button
                        type="submit"
                        :disabled="loading"
                        class="w-full py-3.5 bg-brand-dark hover:bg-brand-darker text-brand-gold font-bold rounded-xl shadow-lg shadow-brand-dark/20 transition-transform active:scale-[0.98] focus:outline-none disabled:opacity-60 disabled:cursor-not-allowed flex items-center justify-center gap-2 mt-2"
                    >
                        <svg x-show="loading" class="animate-spin h-5 w-5" fill="none" viewBox="0 0 24 24" x-cloak>
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
                        </svg>
                        <span x-text="loading ? 'Memproses Pendaftaran...' : 'Daftar & Lanjutkan'"></span>
                    </button>
                </form>
            </div>

            <div class="mt-6 text-center space-y-2">
                <p class="text-sm text-gray-600">
                    Sudah memiliki akun? 
                    <a href="{{ route('login.show') }}" class="font-bold text-brand-gold hover:text-brand-dark transition-colors">Masuk di sini</a>
                </p>
                <div>
                    <a href="{{ route('home') }}" class="text-xs font-semibold text-gray-400 hover:text-brand-dark transition-colors">&larr; Kembali ke Beranda</a>
                </div>
            </div>
        </div>
    </div>
@endsection