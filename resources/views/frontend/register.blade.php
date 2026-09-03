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
        <div class="w-full max-w-md bg-white rounded-3xl shadow-xl p-8 sm:p-10">
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

                        <div>
                            <label class="block text-xs font-bold text-brand-darker uppercase tracking-wider mb-1">Password</label>
                            <input type="password" x-model="password" :required="!google_id" placeholder="Minimal 8 karakter, terdiri dari huruf besar, angka, dan karakter khusus" minlength="8" class="w-full px-4 py-3 bg-brand-light border border-brand-muted rounded-xl text-gray-800 focus:outline-none focus:ring-2 focus:ring-brand-gold/50 focus:border-brand-gold transition-colors" />
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-brand-darker uppercase tracking-wider mb-1">Konfirmasi Password</label>
                            <input type="password" x-model="password_confirmation" :required="!google_id" placeholder="Ulangi password" minlength="8" class="w-full px-4 py-3 bg-brand-light border border-brand-muted rounded-xl text-gray-800 focus:outline-none focus:ring-2 focus:ring-brand-gold/50 focus:border-brand-gold transition-colors" />
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
                        <span x-text="loading ? 'Memproses...' : 'Daftar & Lanjutkan'"></span>
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