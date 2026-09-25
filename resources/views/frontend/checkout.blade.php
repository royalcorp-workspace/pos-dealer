@extends('frontend.layouts.app')

@section('title', 'Checkout - IMG')
@section('robots', 'noindex,nofollow')

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
/* Custom Select2 Styling matching Tailwind rounded-xl checkout theme */
.select2-container {
    width: 100% !important;
}
.select2-container--default .select2-selection--single {
    height: 46px !important;
    background-color: rgb(249 250 251 / 0.5) !important;
    border: 1px solid rgb(229 231 235) !important;
    border-radius: 0.75rem !important; /* rounded-xl */
    display: flex !important;
    align-items: center !important;
    padding-left: 0.5rem !important;
    padding-right: 0.5rem !important;
    transition: all 0.2s ease !important;
}
.select2-container--default .select2-selection--single .select2-selection__rendered {
    color: #1f2937 !important;
    font-size: 0.875rem !important; /* text-sm */
    line-height: 1.25rem !important;
    padding-left: 0.5rem !important;
}
.select2-container--default .select2-selection--single .select2-selection__arrow {
    height: 44px !important;
    right: 10px !important;
}
.select2-container--default.select2-container--focus .select2-selection--single,
.select2-container--default.select2-container--open .select2-selection--single {
    border-color: #c09d6b !important;
    background-color: #ffffff !important;
    box-shadow: 0 0 0 2px rgba(192, 157, 107, 0.2) !important;
    outline: none !important;
}
.select2-container--default .select2-selection--single.select2-selection--disabled {
    background-color: #f3f4f6 !important;
    cursor: not-allowed !important;
    opacity: 0.7 !important;
}
.select2-dropdown {
    border: 1px solid rgb(229 231 235) !important;
    border-radius: 0.75rem !important;
    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1) !important;
    overflow: hidden !important;
    z-index: 9999 !important;
    background-color: #ffffff !important;
}
.select2-search--dropdown {
    padding: 8px !important;
}
.select2-search--dropdown .select2-search__field {
    border: 1px solid #e5e7eb !important;
    border-radius: 0.5rem !important;
    padding: 6px 12px !important;
    font-size: 0.875rem !important;
    outline: none !important;
}
.select2-search--dropdown .select2-search__field:focus {
    border-color: #c09d6b !important;
    box-shadow: 0 0 0 2px rgba(192, 157, 107, 0.2) !important;
}
.select2-results__option {
    padding: 8px 14px !important;
    font-size: 0.875rem !important;
}
.select2-container--default .select2-results__option--highlighted.select2-results__option--selectable {
    background-color: #c09d6b !important;
    color: #ffffff !important;
}
.select2-container--default .select2-results__option--selected {
    background-color: #fdfbf7 !important;
    color: #ad8a58 !important;
    font-weight: 600 !important;
}
</style>
@endpush

@section('content')
    @php
        $cart = $cart ?? session()->get('cart', []);
        $cartTotal = $cartTotal ?? collect($cart)->sum(fn($item) => $item['sell_price'] * $item['quantity']);
        $form = $checkoutFormData ?? [];
        $selectedVoucherCodes = array_values(array_unique(array_map('strtoupper', (array) ($selectedVoucherCodes ?? []))));
        if (($selectedVoucher['code'] ?? null) && !in_array(strtoupper($selectedVoucher['code']), $selectedVoucherCodes, true)) {
            $selectedVoucherCodes[] = strtoupper($selectedVoucher['code']);
        }
        $cartProductIds = collect($cart)->pluck('product_id')->filter()->unique()->values()->all();
        $cartCategoryIds = \App\Models\Frontend\ProductsCatalog\Product::whereIn('id', $cartProductIds)->pluck('category_id')->unique()->values()->all();
    @endphp
    <div class="container mx-auto px-4 md:px-6 py-8 md:py-12 min-h-[60vh] font-sans">
        <!-- Progress / Step Indicator Wizard -->
        <div class="max-w-3xl mx-auto mb-10">
            <div class="relative flex items-center justify-between">
                <!-- Background track -->
                <div class="absolute left-0 top-1/2 -translate-y-1/2 h-1 bg-gray-200 w-full z-0 rounded-full"></div>
                <!-- Active track: Step 1 to Step 2 -->
                <div class="absolute left-0 top-1/2 -translate-y-1/2 h-1 bg-brand-gold w-1/3 z-0 rounded-full transition-all duration-500"></div>

                <!-- Step 1: Keranjang (Completed) -->
                <a href="{{ route('home') }}" class="relative z-10 flex flex-col items-center group cursor-pointer" title="Kembali ke Beranda / Keranjang">
                    <div class="w-10 h-10 rounded-full bg-brand-gold text-white flex items-center justify-center font-bold text-sm shadow-md transition-transform group-hover:scale-110">
                        <i class="fa-solid fa-check"></i>
                    </div>
                    <span class="text-xs font-semibold text-brand-dark mt-2 tracking-tight">Keranjang</span>
                </a>

                <!-- Step 2: Pengiriman (Active) -->
                <div class="relative z-10 flex flex-col items-center">
                    <div class="w-10 h-10 rounded-full bg-brand-dark text-brand-gold border-2 border-brand-gold flex items-center justify-center font-bold text-sm shadow-lg ring-4 ring-brand-gold/20 scale-105">
                        <i class="fa-solid fa-truck-fast"></i>
                    </div>
                    <span class="text-xs font-bold text-brand-dark mt-2 tracking-tight">Pengiriman</span>
                </div>

                <!-- Step 3: Pembayaran -->
                <div class="relative z-10 flex flex-col items-center">
                    <div class="w-10 h-10 rounded-full bg-white border-2 border-gray-300 text-gray-400 flex items-center justify-center font-bold text-sm shadow-2xs">
                        <i class="fa-solid fa-credit-card"></i>
                    </div>
                    <span class="text-xs font-medium text-gray-400 mt-2 tracking-tight">Pembayaran</span>
                </div>

                <!-- Step 4: Selesai -->
                <div class="relative z-10 flex flex-col items-center">
                    <div class="w-10 h-10 rounded-full bg-white border-2 border-gray-300 text-gray-400 flex items-center justify-center font-bold text-sm shadow-2xs">
                        <i class="fa-solid fa-circle-check"></i>
                    </div>
                    <span class="text-xs font-medium text-gray-400 mt-2 tracking-tight">Selesai</span>
                </div>
            </div>
        </div>

        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
            <div>
                <h1 class="text-2xl md:text-3xl font-extrabold text-brand-dark font-serif tracking-tight">Checkout Pesanan</h1>
                <p class="text-xs md:text-sm text-gray-500 mt-1">Lengkapi alamat pengiriman dan pilih ekspedisi kurir untuk pesanan Anda</p>
            </div>
            <a href="{{ route('home') }}" class="inline-flex items-center gap-2 text-xs font-semibold text-brand-gold-dark hover:text-brand-dark transition-colors">
                <i class="fa-solid fa-arrow-left"></i> Lanjutkan Belanja
            </a>
        </div>
        
        <form action="{{ route('checkout.process') }}" method="POST" id="checkout-form" data-is-logged-in="{{ session()->get('is_logged_in') ? 1 : 0 }}" class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            @csrf
            <div class="lg:col-span-2 space-y-6">
                @if(count($cart) === 0)
                    <div class="bg-white border border-brand-muted/80 rounded-2xl p-12 text-center shadow-sm">
                        <div class="w-16 h-16 bg-brand-light rounded-full flex items-center justify-center mx-auto mb-4 text-brand-gold text-2xl">
                            <i class="fa-solid fa-bag-shopping"></i>
                        </div>
                        <h3 class="text-lg font-bold text-brand-dark mb-2">Keranjang Belanja Kosong</h3>
                        <p class="text-sm text-gray-500 mb-6">Pilih produk favorit Anda terlebih dahulu sebelum melanjutkan checkout.</p>
                        <a href="{{ route('home') }}" class="inline-flex items-center gap-2 px-6 py-3 bg-brand-dark text-brand-gold hover:text-white rounded-xl font-bold text-sm transition-all shadow-md">
                            Mulai Belanja <i class="fa-solid fa-arrow-right text-xs"></i>
                        </a>
                    </div>
                @else
                    <!-- Customer Information -->
                    <div class="bg-white border border-brand-muted/80 rounded-2xl p-6 sm:p-7 shadow-sm hover:shadow-md transition-shadow">
                        <div class="flex flex-wrap justify-between items-center gap-3 mb-6 pb-4 border-b border-gray-100">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-xl bg-brand-gold/15 text-brand-gold-dark flex items-center justify-center font-bold text-sm shadow-2xs">
                                    <i class="fa-solid fa-user text-xs"></i>
                                </div>
                                <div>
                                    <h2 class="text-base font-bold text-brand-dark">1. Informasi Penerima</h2>
                                    <p class="text-xs text-gray-500">Data kontak dan alamat lengkap pengiriman</p>
                                </div>
                            </div>
                            @if($savedAddresses->isNotEmpty())
                                <button type="button" onclick="toggleAddressSelector()" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold text-brand-dark bg-brand-gold/15 hover:bg-brand-gold/25 transition-colors">
                                    <i class="fa-solid fa-map-location-dot text-brand-gold-dark"></i>
                                    Pilih Alamat Tersimpan
                                </button>
                            @endif
                        </div>

                        @if($savedAddresses->isNotEmpty())
                            @php
                                $defaultAddress = $savedAddresses->firstWhere('is_primary', true) ?? $savedAddresses->first();
                            @endphp
                            <div id="address-selector" class="hidden mb-6 p-4 bg-brand-light rounded-2xl border border-brand-muted/60">
                                <div class="flex items-center justify-between mb-3">
                                    <span class="text-xs font-bold uppercase tracking-wider text-brand-dark">Daftar Alamat Tersimpan</span>
                                    <span class="text-[11px] text-gray-400">Klik untuk mengisi formulir otomatis</span>
                                </div>
                                <div class="space-y-2.5 max-h-60 overflow-y-auto pr-1">
                                    @foreach($savedAddresses as $addr)
                                        <label class="flex items-start gap-3 p-3.5 bg-white border border-gray-200 rounded-xl cursor-pointer hover:border-brand-gold hover:bg-amber-50/20 transition-all shadow-2xs">
                                            <input type="radio" name="selected_address_id" value="{{ $addr->id }}" class="mt-1 accent-brand-gold" onchange="fillAddress(this)" {{ $addr->id === $defaultAddress->id ? 'checked' : '' }}>
                                            <div class="flex-1 min-w-0">
                                                <div class="flex items-center gap-2">
                                                    <span class="font-bold text-sm text-brand-dark">{{ $addr->label }}</span>
                                                    @if($addr->is_primary)
                                                        <span class="text-[10px] font-extrabold bg-brand-gold/20 text-brand-gold-dark px-2 py-0.5 rounded-full">Utama</span>
                                                    @endif
                                                </div>
                                                <p class="text-xs font-semibold text-gray-700 mt-0.5">{{ $addr->recipient_name }} &bull; <span class="font-normal">{{ $addr->phone }}</span></p>
                                                <p class="text-xs text-gray-500 mt-0.5 leading-relaxed">{{ $addr->address }}, {{ $addr->subDistrict->city->name ?? '' }} {{ $addr->postal_code }}</p>
                                            </div>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        @else
                            @php
                                $defaultAddress = null;
                            @endphp
                        @endif

                        @php
                            $sessionUser = session()->get('user', []);
                            $defaultName = old('name', $form['name'] ?? $defaultAddress?->recipient_name ?? ($defaultCustomerAddr['name'] ?? ($sessionUser['name'] ?? '')));
                            $defaultEmail = old('email', $form['email'] ?? ($defaultCustomerAddr['email'] ?? ($sessionUser['email'] ?? '')));
                            $defaultPhone = old('phone', $form['phone'] ?? $defaultAddress?->phone ?? ($defaultCustomerAddr['phone'] ?? ($sessionUser['phone'] ?? '')));
                            $defaultCity = old('city', $form['city'] ?? $defaultAddress?->subDistrict->city->name ?? ($defaultCustomerAddr['city_name'] ?? ''));
                            $defaultAddressText = old('address', $form['address'] ?? $defaultAddress?->address ?? ($defaultCustomerAddr['address'] ?? ''));
                            $defaultPostal = old('postal_code', $form['postal_code'] ?? $defaultAddress?->postal_code ?? ($defaultCustomerAddr['postal_code'] ?? ''));
                        @endphp

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-gray-600 mb-1.5 flex items-center gap-1.5">
                                    <i class="fa-regular fa-envelope text-brand-gold"></i> Email <span class="text-red-500">*</span>
                                </label>
                                <input type="email" name="email" value="{{ $defaultEmail }}" required class="w-full px-4 py-3 bg-gray-50/50 border border-gray-200 rounded-xl focus:outline-none focus:border-brand-gold focus:ring-2 focus:ring-brand-gold/20 focus:bg-white text-sm transition-all" placeholder="email@example.com">
                            </div>
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-gray-600 mb-1.5 flex items-center gap-1.5">
                                    <i class="fa-regular fa-user text-brand-gold"></i> Nama Lengkap <span class="text-red-500">*</span>
                                </label>
                                <input type="text" name="name" value="{{ $defaultName }}" required class="w-full px-4 py-3 bg-gray-50/50 border border-gray-200 rounded-xl focus:outline-none focus:border-brand-gold focus:ring-2 focus:ring-brand-gold/20 focus:bg-white text-sm transition-all" placeholder="Nama lengkap penerima">
                            </div>
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-gray-600 mb-1.5 flex items-center gap-1.5">
                                    <i class="fa-solid fa-phone text-brand-gold text-[11px]"></i> Nomor Telepon / WA <span class="text-red-500">*</span>
                                </label>
                                <input type="tel" name="phone" value="{{ $defaultPhone }}" required class="w-full px-4 py-3 bg-gray-50/50 border border-gray-200 rounded-xl focus:outline-none focus:border-brand-gold focus:ring-2 focus:ring-brand-gold/20 focus:bg-white text-sm transition-all" placeholder="08xx xxxx xxxx">
                            </div>
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-gray-600 mb-1.5 flex items-center gap-1.5">
                                    <i class="fa-solid fa-map-location-dot text-brand-gold text-[11px]"></i> Provinsi <span class="text-red-500">*</span>
                                </label>
                                <select name="province_id" id="checkout-province" required class="w-full px-4 py-3 bg-gray-50/50 border border-gray-200 rounded-xl focus:outline-none focus:border-brand-gold focus:ring-2 focus:ring-brand-gold/20 focus:bg-white text-sm transition-all">
                                    <option value="">Pilih Provinsi</option>
                                    @foreach($provinces as $prov)
                                        <option value="{{ $prov->id }}" {{ (old('province_id', $selectedProvinceId ?? '') == $prov->id) ? 'selected' : '' }}>
                                            {{ $prov->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-gray-600 mb-1.5 flex items-center gap-1.5">
                                    <i class="fa-solid fa-city text-brand-gold text-[11px]"></i> Kota / Kabupaten <span class="text-red-500">*</span>
                                </label>
                                <select name="city_id" id="checkout-city" required class="w-full px-4 py-3 bg-gray-50/50 border border-gray-200 rounded-xl focus:outline-none focus:border-brand-gold focus:ring-2 focus:ring-brand-gold/20 focus:bg-white text-sm transition-all" {{ empty($cities) || $cities->isEmpty() ? 'disabled' : '' }}>
                                    <option value="">{{ empty($cities) || $cities->isEmpty() ? 'Pilih Provinsi Terlebih Dahulu' : 'Pilih Kota/Kabupaten' }}</option>
                                    @foreach($cities as $c)
                                        <option value="{{ $c->id }}" {{ (old('city_id', $selectedCityId ?? '') == $c->id) ? 'selected' : '' }}>
                                            {{ $c->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-gray-600 mb-1.5 flex items-center gap-1.5">
                                    <i class="fa-solid fa-building text-brand-gold text-[11px]"></i> Kecamatan / Kelurahan <span class="text-red-500">*</span>
                                </label>
                                <select name="sub_district_id" id="checkout-sub-district" required class="w-full px-4 py-3 bg-gray-50/50 border border-gray-200 rounded-xl focus:outline-none focus:border-brand-gold focus:ring-2 focus:ring-brand-gold/20 focus:bg-white text-sm transition-all" {{ empty($subDistricts) || $subDistricts->isEmpty() ? 'disabled' : '' }}>
                                    <option value="">{{ empty($subDistricts) || $subDistricts->isEmpty() ? 'Pilih Kota Terlebih Dahulu' : 'Pilih Kecamatan/Kelurahan' }}</option>
                                    @foreach($subDistricts as $sd)
                                        <option value="{{ $sd['id'] }}" data-postal="{{ $sd['postal_code'] ?? '' }}" {{ (old('sub_district_id', $form['sub_district_id'] ?? $selectedSubDistrictId ?? '') == $sd['id']) ? 'selected' : '' }}>
                                            {{ $sd['label'] }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="mt-4">
                            <label class="block text-xs font-bold uppercase tracking-wider text-gray-600 mb-1.5 flex items-center gap-1.5">
                                <i class="fa-solid fa-location-dot text-brand-gold text-[11px]"></i> Alamat Lengkap <span class="text-red-500">*</span>
                            </label>
                            <textarea name="address" required rows="3" class="w-full px-4 py-3 bg-gray-50/50 border border-gray-200 rounded-xl focus:outline-none focus:border-brand-gold focus:ring-2 focus:ring-brand-gold/20 focus:bg-white text-sm transition-all" placeholder="Jl. Sudirman No. 123, Blok A, RT/RW, Patokan Lokasi">{{ $defaultAddressText }}</textarea>
                        </div>
                        <div class="mt-4">
                            <label class="block text-xs font-bold uppercase tracking-wider text-gray-600 mb-1.5 flex items-center gap-1.5">
                                <i class="fa-solid fa-envelopes-bulk text-brand-gold text-[11px]"></i> Kode Pos
                            </label>
                            <input type="text" name="postal_code" value="{{ $defaultPostal }}" class="w-full px-4 py-3 bg-gray-50/50 border border-gray-200 rounded-xl focus:outline-none focus:border-brand-gold focus:ring-2 focus:ring-brand-gold/20 focus:bg-white text-sm transition-all" placeholder="12345">
                        </div>
                    </div>

                    <!-- Section 2: Courier / Shipping Options -->
                    <div class="bg-white border border-brand-muted/80 rounded-2xl p-6 sm:p-7 shadow-sm hover:shadow-md transition-shadow">
                        <div class="flex items-center gap-3 mb-5 pb-4 border-b border-gray-100">
                            <div class="w-9 h-9 rounded-xl bg-brand-gold/15 text-brand-gold-dark flex items-center justify-center font-bold text-sm shadow-2xs">
                                <i class="fa-solid fa-truck text-xs"></i>
                            </div>
                            <div>
                                <h2 class="text-base font-bold text-brand-dark">2. Opsi Pengiriman</h2>
                                <p class="text-xs text-gray-500">Pilih ekspedisi atau kurir pengiriman untuk tujuan Anda</p>
                            </div>
                        </div>

                        @if(isset($enforcedCourierType) && $enforcedCourierType)
                            <div class="mb-4 p-3.5 bg-amber-50/80 border border-amber-200/80 rounded-xl flex items-start gap-2.5 text-xs text-amber-900">
                                <i class="fa-solid fa-triangle-exclamation text-amber-600 mt-0.5 text-sm shrink-0"></i>
                                <span>
                                    Produk dalam keranjang Anda diset khusus hanya mendukung pengiriman melalui <strong>{{ $enforcedCourierType === 'toko' ? 'Kurir Toko' : 'Kurir Ekspedisi' }}</strong>.
                                </span>
                            </div>
                        @endif

                        <div>
                            <select name="courier" required class="w-full px-4 py-3.5 bg-gray-50/50 border border-gray-200 rounded-xl focus:outline-none focus:border-brand-gold focus:ring-2 focus:ring-brand-gold/20 focus:bg-white text-sm transition-all">
                                <option value="">-- Pilih Ekspedisi / Kurir Pengiriman --</option>
                                @foreach($couriers as $courier)
                                    @php
                                        $details = $courierPrices[$courier->code] ?? null;
                                        $isAvailable = $details['is_available'] ?? true;
                                        $calculatedCost = $details['shipping_cost'] ?? ($courier->shippingAddresses->where('type', 1)->first()->price ?? ($courier->shippingAddresses->first()->price ?? 25000));
                                        $isCalculated = $details['is_calculated'] ?? false;
                                        $billableWeight = $details['billable_weight'] ?? 1;
                                        $hasFixed = $details['has_fixed_items'] ?? false;
                                        $hasDim = $details['has_dimension_items'] ?? false;
                                    @endphp
                                    <option value="{{ $courier->code }}" 
                                            data-courier-name="{{ $courier->name }}"
                                            data-base-price="{{ $details['base_price'] ?? $calculatedCost }}"
                                            data-available="{{ $isAvailable ? '1' : '0' }}"
                                            {{ !$isAvailable ? 'disabled' : '' }}
                                            {{ (old('courier', $form['courier'] ?? '') == $courier->code) ? 'selected' : '' }}>
                                        @if(!$isAvailable)
                                            {{ $courier->name }} - Di Luar Jangkauan (Tidak Melayani Wilayah Ini)
                                        @else
                                            {{ $courier->name }} - Rp {{ number_format($calculatedCost, 0, ',', '.') }}
                                            @if($hasFixed && $hasDim)
                                                (Tetap + {{ $billableWeight }} kg)
                                            @elseif($hasFixed)
                                                (Ongkir Tetap)
                                            @elseif($isCalculated && $billableWeight > 0)
                                                ({{ $billableWeight }} kg)
                                            @else
                                                (Tarif Tetap)
                                            @endif
                                            @if(!empty($details['eta_label']))
                                                | Estimasi tiba: {{ $details['eta_label'] }}
                                            @endif
                                        @endif
                                    </option>
                                @endforeach
                            </select>
                            @php
                                $selectedCourierCode = old('courier', $form['courier'] ?? '');
                                $selectedCourierDetails = $courierPrices[$selectedCourierCode] ?? null;
                                $selectedEta = $selectedCourierDetails['eta_label'] ?? null;
                                $selectedEtaSource = $selectedCourierDetails['eta_source'] ?? null;
                            @endphp
                            <div id="courier-eta-badge" class="{{ empty($selectedEta) ? 'hidden' : '' }} mt-3 p-3 bg-blue-50/80 border border-blue-200/80 rounded-xl text-xs text-blue-900 flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <i class="fa-solid fa-clock text-blue-600"></i>
                                    <span>Estimasi Tiba: <strong id="courier-eta-text">{{ $selectedEta ?? '' }}</strong></span>
                                </div>
                                <span id="courier-eta-source" class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-blue-200/60 text-blue-800 uppercase tracking-wider">
                                    {{ $selectedEtaSource === 'biteship' ? 'Biteship' : ($selectedEtaSource === 'store' ? 'Kurir Toko' : 'Estimasi') }}
                                </span>
                            </div>
                            <div id="courier-unavailable-alert" class="hidden mt-2 p-3 bg-amber-50 border border-amber-200 rounded-xl text-xs text-amber-800 flex items-start gap-2">
                                <i class="fa-solid fa-triangle-exclamation mt-0.5 text-amber-600 shrink-0"></i>
                                <span id="courier-unavailable-message">Kurir yang dipilih belum melayani pengiriman ke kota/wilayah tujuan ini. Silakan pilih kurir lain atau ganti alamat tujuan.</span>
                            </div>
                            @error('courier')
                                <p class="text-xs text-red-500 mt-1.5 flex items-center gap-1">
                                    <i class="fa-solid fa-circle-exclamation"></i> {{ $message }}
                                </p>
                            @enderror
                        </div>

                        <div id="checkout-dimension-weight-info" class="mt-3 text-xs">
                            @if(!empty($cartWeightDetails['has_fixed_items']) && !empty($cartWeightDetails['has_dimension_items']))
                                <div class="flex items-start gap-2 text-emerald-800 bg-emerald-50/80 border border-emerald-200/80 rounded-xl px-3.5 py-2.5">
                                    <i class="fa-solid fa-scale-balanced mt-0.5 text-emerald-600 shrink-0"></i>
                                    <span>Kombinasi Ongkir: Produk bertarif tetap (Rp {{ number_format($cartWeightDetails['fixed_shipping_cost'] ?? 0, 0, ',', '.') }}) + Produk dimensi (<strong>{{ $cartWeightDetails['chargeable_weight'] }} kg</strong>, Fisik: {{ $cartWeightDetails['actual_weight'] }} kg, Volumetrik: {{ $cartWeightDetails['volumetric_weight'] }} kg).</span>
                                </div>
                            @elseif(!empty($cartWeightDetails['has_fixed_items']))
                                <div class="flex items-start gap-2 text-blue-800 bg-blue-50/80 border border-blue-200/80 rounded-xl px-3.5 py-2.5">
                                    <i class="fa-solid fa-box text-blue-600 mt-0.5 shrink-0"></i>
                                    <span>Ongkos kirim menggunakan tarif tetap produk/varian (Total: <strong>Rp {{ number_format($cartWeightDetails['fixed_shipping_cost'] ?? 0, 0, ',', '.') }}</strong>).</span>
                                </div>
                            @elseif(!empty($cartWeightDetails['is_calculable']))
                                <div class="flex items-start gap-2 text-emerald-800 bg-emerald-50/80 border border-emerald-200/80 rounded-xl px-3.5 py-2.5">
                                    <i class="fa-solid fa-calculator text-emerald-600 mt-0.5 shrink-0"></i>
                                    <span>Perhitungan berat total: <strong>{{ $cartWeightDetails['chargeable_weight'] }} kg</strong> (Berat fisik: {{ $cartWeightDetails['actual_weight'] }} kg, Volumetrik: {{ $cartWeightDetails['volumetric_weight'] }} kg). Ongkir dikalkulasikan berdasarkan berat ini.</span>
                                </div>
                            @else
                                <div class="flex items-start gap-2 text-gray-600 bg-gray-50/80 border border-gray-200/80 rounded-xl px-3.5 py-2.5">
                                    <i class="fa-solid fa-circle-info text-gray-400 mt-0.5 shrink-0"></i>
                                    <span>Dimensi/berat produk tidak tersedia. Ongkir menggunakan <strong>Tarif Tetap (Flat Rate)</strong>.</span>
                                </div>
                            @endif
                        </div>
                        <script id="checkout-courier-shipping-prices" type="application/json">
                            @json(collect($courierPrices)->mapWithKeys(function($val, $key) {
                                return [$key => floatval($val['shipping_cost'] ?? 0)];
                            }))
                        </script>
                        <script id="checkout-courier-shipping-details" type="application/json">
                            @json($courierPrices)
                        </script>
                    </div>

                    <!-- Section 3: Voucher & Discount Coupons -->
                    <div class="bg-white border border-brand-muted/80 rounded-2xl p-6 sm:p-7 shadow-sm hover:shadow-md transition-shadow">
                        <div class="flex justify-between items-center gap-4 mb-5 pb-4 border-b border-gray-100">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-xl bg-brand-gold/15 text-brand-gold-dark flex items-center justify-center font-bold text-sm shadow-2xs">
                                    <i class="fa-solid fa-ticket text-xs"></i>
                                </div>
                                <div>
                                    <h2 class="text-base font-bold text-brand-dark">3. Kupon & Voucher Diskon</h2>
                                    <p class="text-xs text-gray-500">Gunakan voucher diskon belanja Anda</p>
                                </div>
                            </div>
                            <span class="inline-flex items-center gap-1 rounded-full bg-brand-gold/15 px-3 py-1 text-xs font-bold text-brand-gold-dark">
                                <i class="fa-solid fa-tag text-[10px]"></i> {{ $vouchers->count() }} Tersedia
                            </span>
                        </div>

                        <input type="hidden" name="voucher_code" id="voucher-code" value="{{ implode(',', $selectedVoucherCodes) }}">
                        <input type="hidden" name="voucher_codes" id="voucher-codes" value="{{ implode(',', $selectedVoucherCodes) }}">
                        <input type="hidden" name="voucher_discount" id="voucher-discount-value" value="{{ $selectedVoucher['discount'] ?? 0 }}">

                        <div class="flex gap-2.5 mb-4">
                            <div class="relative flex-1">
                                <i class="fa-solid fa-barcode absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
                                <input type="text" id="manual-voucher-input" placeholder="Masukkan kode voucher..." class="w-full pl-9 pr-4 py-3 bg-gray-50/50 border border-gray-200 rounded-xl focus:outline-none focus:border-brand-gold focus:ring-2 focus:ring-brand-gold/20 focus:bg-white text-sm uppercase tracking-wider font-mono transition-all" maxlength="20">
                            </div>
                            <button type="button" onclick="validateAndApplyVoucher()" class="px-5 py-3 bg-brand-dark text-brand-gold hover:text-white rounded-xl font-bold text-sm hover:bg-brand-darker transition-colors shadow-sm whitespace-nowrap">
                                Gunakan
                            </button>
                        </div>
                        <div id="manual-voucher-feedback" class="text-xs mb-3 font-medium"></div>

                        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-3.5">
                            @foreach($vouchers as $voucher)
                                @php
                                    $typeLabel = match((int)$voucher->type) {
                                        1 => 'Persen',
                                        2 => 'Nominal',
                                        3 => 'Gratis Ongkir',
                                        4 => 'Bonus Produk',
                                        default => 'Tidak diketahui',
                                    };
                                    $isUsable = $voucher->is_usable ?? true;
                                    $isSelected = in_array(strtoupper($voucher->code), $selectedVoucherCodes, true) || (($selectedVoucher['code'] ?? '') === $voucher->code);
                                @endphp
                                <button type="button"
                                    @if($isUsable)
                                        onclick="selectCoupon(this)"
                                        class="coupon-card group cursor-pointer text-left rounded-2xl border transition-all duration-200 p-4 relative overflow-hidden focus:outline-none {{ $isSelected ? 'border-brand-gold bg-brand-light shadow-md ring-2 ring-brand-gold/30' : 'border-gray-200 bg-white hover:border-brand-gold/60 hover:shadow-md' }}"
                                    @else
                                        class="coupon-card opacity-50 bg-gray-50 border-gray-200 pointer-events-none cursor-not-allowed text-left rounded-2xl border p-4 transition-all focus:outline-none"
                                    @endif
                                    data-code="{{ $voucher->code }}"
                                    data-title="{{ $voucher->title }}"
                                    data-discount-type="{{ $voucher->type == 1 ? 'percentage' : ($voucher->type == 2 ? 'fixed' : ($voucher->type == 3 ? 'shipping' : 'bonus')) }}"
                                    data-discount-value="{{ floatval($voucher->value) }}"
                                    data-max-discount="{{ $voucher->max_discount ?? '' }}"
                                    data-min-purchase="{{ (float)($voucher->min_purchase ?? 0) }}"
                                    data-allow-stacking="{{ $voucher->allow_stacking ? 1 : 0 }}"
                                    data-products="[]">
                                    <div class="flex items-start justify-between gap-2">
                                        <div class="min-w-0 flex-1">
                                            <p class="font-bold text-sm text-brand-dark truncate group-hover:text-brand-gold-dark transition-colors">{{ $voucher->title }}</p>
                                            <p class="text-[11px] text-gray-500 mt-0.5 line-clamp-2 leading-relaxed">{{ $voucher->description }}</p>
                                        </div>
                                        <span class="inline-flex items-center rounded-full bg-red-50 px-2 py-0.5 text-[10px] font-extrabold uppercase tracking-wider text-red-600 shrink-0 border border-red-100">
                                            {{ $voucher->value }}{{ $voucher->type == 1 ? '%' : '' }} {{ $typeLabel }}
                                        </span>
                                    </div>
                                    <div class="mt-3 flex flex-wrap items-center gap-1.5">
                                        <span class="inline-flex items-center rounded-md bg-brand-light px-2 py-0.5 text-[10px] font-bold text-brand-gold-dark border border-brand-muted">
                                            {{ $voucher->scopeLabel() }}
                                        </span>
                                        <span class="inline-flex items-center rounded-md {{ $voucher->allow_stacking ? 'bg-emerald-50 text-emerald-700 border-emerald-100' : 'bg-gray-100 text-gray-600 border-gray-200' }} border px-2 py-0.5 text-[10px] font-bold">
                                            {{ $voucher->allow_stacking ? 'Bisa Digabung' : 'Single' }}
                                        </span>
                                        @if((float)($voucher->min_purchase ?? 0) > 0)
                                            <span class="inline-flex items-center rounded-md bg-amber-50 text-amber-800 border border-amber-200/60 px-2 py-0.5 text-[10px] font-bold">
                                                Min. Rp {{ number_format($voucher->min_purchase, 0, ',', '.') }}
                                            </span>
                                        @endif
                                    </div>
                                    <div class="mt-3 pt-2.5 border-t border-dashed border-gray-200 flex items-center justify-between">
                                        <span class="font-mono text-xs font-bold text-brand-dark bg-gray-100 px-2 py-0.5 rounded border border-gray-200">{{ $voucher->code }}</span>
                                        @if($isUsable)
                                            <span class="text-xs font-bold text-brand-gold-dark select-coupon-label">{{ $isSelected ? 'Dipilih' : 'Pilih' }}</span>
                                        @else
                                            <span class="text-[10px] font-bold text-red-500 uppercase tracking-wider select-coupon-label">Limit Habis</span>
                                        @endif
                                    </div>
                                    @if(!$isUsable)
                                        <div class="mt-2 text-[10px] font-bold text-red-600 uppercase tracking-wider border-t pt-1">
                                            Kupon sudah pernah digunakan
                                        </div>
                                    @endif
                                </button>
                            @endforeach
                        </div>

                        <div id="selected-coupon-text" class="mt-4 rounded-xl bg-brand-light p-3 text-xs text-gray-700 border border-brand-muted flex items-center gap-2">
                            <i class="fa-solid fa-circle-check text-brand-gold-dark"></i>
                            @if(count($selectedVoucherCodes) > 0)
                                <span>Kupon dipilih: <strong class="text-brand-dark font-mono font-bold">{{ implode(', ', $selectedVoucherCodes) }}</strong></span>
                            @else
                                <span>Belum ada kupon dipilih.</span>
                            @endif
                        </div>
                        <div id="bonus-products-display" class="space-y-2 mt-2">
                            @foreach($selectedVouchers as $sv)
                                @if((int)$sv->type === 4 && !empty($sv->products) && $sv->products->isNotEmpty())
                                    <div class="mt-3 flex items-start gap-2.5 bg-green-50 text-green-800 p-3 rounded-xl border border-green-200 text-xs font-semibold">
                                        <i class="fa-solid fa-gift text-sm text-green-600 mt-0.5 shrink-0"></i>
                                        <div>
                                            <p class="font-extrabold">Selamat! Anda mendapatkan Bonus Produk:</p>
                                            <ul class="list-disc pl-4 mt-1 space-y-0.5">
                                                @foreach($sv->products as $bp)
                                                    <li>{{ (int)$sv->value }}x {{ $bp->name }}</li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    </div>

                    <!-- Section 4: Order Summary (Products List) -->
                    <div class="bg-white border border-brand-muted/80 rounded-2xl p-6 sm:p-7 shadow-sm hover:shadow-md transition-shadow">
                        <div class="flex justify-between items-center mb-5 pb-4 border-b border-gray-100">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-xl bg-brand-gold/15 text-brand-gold-dark flex items-center justify-center font-bold text-sm shadow-2xs">
                                    <i class="fa-solid fa-bag-shopping text-xs"></i>
                                </div>
                                <div>
                                    <h2 class="text-base font-bold text-brand-dark">4. Produk Dipesan</h2>
                                    <p class="text-xs text-gray-500">Periksa kembali daftar item pesanan Anda</p>
                                </div>
                            </div>
                            <span class="text-xs font-bold text-gray-500 bg-gray-100 px-2.5 py-1 rounded-full">
                                {{ count($cart) }} Item
                            </span>
                        </div>

                        <div class="space-y-4">
                            @foreach($cart as $item)
                                @php
                                    $itemImage = $item['image'] ?? null;
                                    if (empty($itemImage)) {
                                        if (!empty($item['product_id'])) {
                                            $pModel = \App\Models\Frontend\ProductsCatalog\Product::find($item['product_id']);
                                            $itemImage = $pModel?->thumbnail_url;
                                        } elseif (($item['type'] ?? '') === 'bundle' && !empty($item['bundle_data']['bundle_id'])) {
                                            $bModel = \App\Models\Frontend\ProductsCatalog\ProductBundling::find($item['bundle_data']['bundle_id']);
                                            $itemImage = $bModel?->thumbnail_url;
                                        }
                                    }
                                @endphp
                                <div class="p-4 bg-gray-50/40 rounded-2xl border border-gray-100 hover:border-brand-gold/40 transition-colors">
                                    <div class="flex gap-3 sm:gap-4 items-start">
                                        <!-- Product Image Thumbnail -->
                                        <div class="w-16 h-16 sm:w-20 sm:h-20 rounded-xl bg-white overflow-hidden flex-shrink-0 border border-gray-200 shadow-2xs">
                                            @if(!empty($itemImage))
                                                <img src="{{ $itemImage }}" alt="{{ $item['name'] ?? 'Produk' }}" loading="lazy" decoding="async" class="w-full h-full object-cover">
                                            @else
                                                <div class="w-full h-full flex items-center justify-center text-brand-gold bg-brand-light">
                                                    <i class="fa-solid fa-box text-xl"></i>
                                                </div>
                                            @endif
                                        </div>

                                        <!-- Product Info & Pricing -->
                                        <div class="flex-1 min-w-0 flex flex-col sm:flex-row sm:justify-between items-start gap-2">
                                            <div class="min-w-0 flex-1">
                                                @if(($item['type'] ?? '') === 'bundle' || !empty($item['bundle_data']) || str_starts_with($item['name'] ?? '', 'BUNDLE_'))
                                                    <div class="font-bold text-brand-dark flex items-center gap-2 text-sm sm:text-base">
                                                        {{ $item['bundle_data']['bundle_name'] ?? ($item['name'] ?? 'Paket Bundling') }}
                                                        <span class="text-[10px] font-extrabold text-purple-700 bg-purple-100 px-2 py-0.5 rounded-full uppercase tracking-wider">Bundling</span>
                                                    </div>
                                                    <div class="mt-1.5 pl-2.5 border-l-2 border-purple-300 text-xs text-gray-500 space-y-1">
                                                        @foreach(($item['bundle_data']['items'] ?? []) as $bundleItem)
                                                            <div>
                                                                &bull; {{ $bundleItem['product_name'] ?? 'Produk' }} ({{ $bundleItem['quantity'] ?? 1 }}x)
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                    <div class="text-xs text-gray-500 mt-2 font-medium">Qty: <strong class="text-brand-dark">{{ $item['quantity'] }} Paket</strong></div>
                                                @else
                                                    <h3 class="font-bold text-brand-dark text-sm sm:text-base leading-snug">{{ $item['name'] }}</h3>
                                                    <span class="inline-block mt-0.5 text-xs font-semibold text-gray-500 bg-white border border-gray-200 px-2 py-0.5 rounded-md">Qty: {{ $item['quantity'] }}</span>
                                                    @if(!empty($item['color_name']))
                                                        <div class="text-xs text-brand-gold-dark mt-1 font-medium flex items-center gap-1">
                                                            <i class="fa-solid fa-palette text-[10px]"></i> Warna: {{ $item['color_name'] }}
                                                        </div>
                                                    @endif
                                                    @php
                                                        $itemVariantId = $item['variant_id'] ?? (($item['id'] ?? null) !== ($item['product_id'] ?? null) ? ($item['id'] ?? null) : null);
                                                        $iVar = $itemVariantId ? \App\Models\Frontend\ProductsCatalog\ProductVariant::find($itemVariantId) : null;
                                                        $iProd = !empty($item['product_id']) ? \App\Models\Frontend\ProductsCatalog\Product::find($item['product_id']) : null;
                                                        $iLen = (float)($iVar->length ?? $iProd->length ?? ($iVar->attributes['length'] ?? 0));
                                                        $iWid = (float)($iVar->width ?? $iProd->width ?? ($iVar->attributes['width'] ?? 0));
                                                        $iHei = (float)($iVar->height ?? $iProd->height ?? ($iVar->attributes['height'] ?? 0));
                                                        $iWei = (float)($iVar->weight ?? $iProd->weight ?? ($iVar->attributes['weight'] ?? 0));
                                                    @endphp
                                                    @if($iWei > 0 || ($iLen > 0 && $iWid > 0 && $iHei > 0))
                                                        <div class="text-[11px] text-gray-400 mt-1 flex flex-wrap items-center gap-2">
                                                            @if($iLen > 0 && $iWid > 0 && $iHei > 0)
                                                                <span><i class="fa-solid fa-ruler-combined text-[9px]"></i> {{ $iLen }}×{{ $iWid }}×{{ $iHei }} cm</span>
                                                            @endif
                                                            @if($iWei > 0)
                                                                <span><i class="fa-solid fa-weight-hanging text-[9px]"></i> {{ $iWei }} kg</span>
                                                            @endif
                                                        </div>
                                                    @endif
                                                @endif
                                            </div>
                                            <div class="text-left sm:text-right flex flex-col sm:items-end flex-shrink-0">
                                                @php
                                                    $isBundle = ($item['type'] ?? null) === 'bundle';
                                                    $bundleData = $item['bundle_data'] ?? null;
                                                    
                                                    if ($isBundle && $bundleData) {
                                                        $basePrice = (float) ($bundleData['bundle_price'] ?? 0);
                                                        $sellPrice = (float) $item['sell_price'];
                                                    } else {
                                                        $variantId = $item['variant_id'] ?? ($item['id'] !== $item['product_id'] ? $item['id'] : null);
                                                        $basePrice = 0.0;
                                                        $sellPrice = (float) $item['sell_price'];
                                                        if ($variantId) {
                                                            $variantModel = \App\Models\Frontend\ProductsCatalog\ProductVariant::find($variantId);
                                                            if ($variantModel) {
                                                                $basePrice = (float) $variantModel->base_price;
                                                            }
                                                        }
                                                        if ($basePrice <= 0.0) {
                                                            $productModel = \App\Models\Frontend\ProductsCatalog\Product::find($item['product_id']);
                                                            if ($productModel) {
                                                                $minVariant = $productModel->variants->where('status', true)->sortBy('sell_price')->first();
                                                                if ($minVariant) {
                                                                    $basePrice = (float) $minVariant->base_price;
                                                                }
                                                            }
                                                        }
                                                    }
                                                    
                                                    if ($basePrice <= 0.0) {
                                                        $basePrice = (float) ($item['original_price'] ?? $sellPrice);
                                                    }
                                                    
                                                    $hasDefaultDiscount = $basePrice > $sellPrice;
                                                    $op = $hasDefaultDiscount ? $basePrice : ($item['original_price'] ?? $sellPrice);
                                                    $ip = $sellPrice;
                                                    $discountPercent = $op > 0 ? round((($op - $ip) / $op) * 100) : 0;
                                                @endphp
                                                @if($op > $ip)
                                                    <div class="flex items-center gap-1.5 sm:justify-end mb-0.5">
                                                        @if($discountPercent > 0)
                                                            <span class="bg-red-50 text-red-600 text-[9px] font-extrabold px-1.5 py-0.5 rounded-full">{{ $discountPercent }}% OFF</span>
                                                        @endif
                                                        <span class="text-xs text-gray-400 line-through">Rp {{ number_format($op * $item['quantity'], 0, ',', '.') }}</span>
                                                    </div>
                                                @endif

                                                <span class="font-extrabold text-brand-dark text-base">Rp {{ number_format($item['sell_price'] * $item['quantity'], 0, ',', '.') }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mt-3 pt-2.5 border-t border-gray-200/60">
                                        @php
                                            $cleanItemNote = $item['item_note'] ?? '';
                                            if (is_array($cleanItemNote)) {
                                                $cleanItemNote = '';
                                            } elseif (is_string($cleanItemNote)) {
                                                $trimmedN = trim($cleanItemNote);
                                                if (str_starts_with($trimmedN, '{') || str_starts_with($trimmedN, '[')) {
                                                    $dec = json_decode($trimmedN, true);
                                                    $cleanItemNote = is_array($dec) ? ($dec['user_note'] ?? '') : '';
                                                }
                                            }
                                        @endphp
                                        <textarea
                                            name="item_notes[{{ $item['id'] }}]"
                                            rows="1"
                                            maxlength="500"
                                            class="w-full rounded-xl border border-gray-200 bg-white px-3 py-2 text-xs focus:outline-none focus:border-brand-gold focus:ring-1 focus:ring-brand-gold transition-all"
                                            placeholder="Catatan khusus untuk item ini (opsional: warna cadangan, lantai pengiriman, dll)"
                                        >{{ $cleanItemNote }}</textarea>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
            
            <!-- Sticky Right Sidebar: Ringkasan Pembayaran -->
            <div class="lg:col-span-1">
                <div class="bg-white border border-brand-muted/80 rounded-2xl p-6 shadow-sm sticky top-6">
                    <input type="hidden" id="checkout-subtotal" data-value="{{ $originalCartTotal }}">
                    <input type="hidden" id="checkout-promo-discount" data-value="{{ $totalPercentDiscount + $totalNominalDiscount }}">
                    <input type="hidden" id="checkout-shipping-cost" data-value="{{ $form['shipping_cost'] ?? 0 }}">
                    <input type="hidden" id="checkout-product-discount" data-value="{{ $priceProductSettingDiscount ?? 0 }}">
                    <input type="hidden" id="checkout-voucher-discount" data-value="{{ $selectedVoucher['discount'] ?? 0 }}">
                    <input type="hidden" id="checkout-selected-voucher-codes" data-value="{{ implode(',', $selectedVoucherCodes) }}">
                    <input type="hidden" id="checkout-product-ids" data-value='@json($cartProductIds)'>
                    <input type="hidden" id="checkout-category-ids" data-value='@json($cartCategoryIds)'>
                    <div id="checkout-form-data" data-has-existing-data="{{ !empty($form) ? 1 : 0 }}"></div>

                    <div class="flex items-center gap-2.5 mb-5 pb-3.5 border-b border-gray-100">
                        <i class="fa-solid fa-receipt text-brand-gold"></i>
                        <h3 class="font-bold text-brand-dark text-base">Ringkasan Biaya</h3>
                    </div>

                    <div class="space-y-3 mb-6 text-sm">
                        <div class="flex justify-between items-center text-gray-600">
                            <span>Subtotal Produk</span>
                            <span class="font-bold text-brand-dark" id="checkout-subtotal-display">Rp {{ number_format($originalCartTotal, 0, ',', '.') }}</span>
                        </div>

                        @if(($totalPercentDiscount ?? 0) > 0)
                            <div class="flex justify-between items-center text-red-600">
                                <span class="text-gray-600 flex items-center gap-1.5"><i class="fa-solid fa-tag text-xs text-red-500"></i> Diskon Promo</span>
                                <span class="font-bold">- Rp {{ number_format($totalPercentDiscount, 0, ',', '.') }}</span>
                            </div>
                        @endif

                        @if(($totalNominalDiscount ?? 0) > 0)
                            <div class="flex justify-between items-center text-red-600">
                                <span class="text-gray-600 flex items-center gap-1.5"><i class="fa-solid fa-tag text-xs text-red-500"></i> Diskon Promo</span>
                                <span class="font-bold">- Rp {{ number_format($totalNominalDiscount, 0, ',', '.') }}</span>
                            </div>
                        @endif

                        <div class="flex justify-between items-center text-red-600 {{ ($priceProductSettingDiscount ?? 0) > 0 ? '' : 'hidden' }}" id="product-discount-row">
                            <span class="text-gray-600 flex items-center gap-1.5"><i class="fa-solid fa-boxes-stacked text-xs text-red-500"></i> Diskon Volume</span>
                            <span class="font-bold" id="product-discount">- Rp {{ number_format($priceProductSettingDiscount ?? 0, 0, ',', '.') }}</span>
                        </div>

                        <div class="flex justify-between items-center text-gray-600">
                            <span id="checkout-shipping-label">Ongkos Kirim</span>
                            <span class="text-brand-dark font-bold" id="shipping-cost">Rp {{ number_format($form['shipping_cost'] ?? 0, 0, ',', '.') }}</span>
                            <span id="checkout-shipping-cost" data-value="{{ $form['shipping_cost'] ?? 0 }}" class="hidden"></span>
                        </div>

                        <div class="flex justify-between items-center text-xs text-blue-700 bg-blue-50/60 px-2.5 py-1.5 rounded-lg {{ empty($selectedEta) ? 'hidden' : '' }}" id="checkout-shipping-eta-row">
                            <span class="flex items-center gap-1.5"><i class="fa-solid fa-clock text-[11px] text-blue-500"></i> Estimasi Tiba</span>
                            <span class="font-semibold" id="checkout-shipping-eta-val">{{ $selectedEta ?? '' }}</span>
                        </div>

                        <div class="flex justify-between items-center text-red-600" id="checkout-voucher-row" style="{{ ($selectedVoucher['discount'] ?? 0) > 0 ? '' : 'display: none;' }}">
                            <span class="text-gray-600 flex items-center gap-1.5" id="checkout-voucher-label">Voucher ({{ implode(',', $selectedVoucherCodes) }})</span>
                            <span class="font-bold" id="voucher-discount">- Rp {{ number_format($selectedVoucher['discount'] ?? 0, 0, ',', '.') }}</span>
                            <span id="checkout-voucher-discount" data-value="{{ $selectedVoucher['discount'] ?? 0 }}" class="hidden"></span>
                        </div>

                        <div class="pt-4 border-t border-dashed border-gray-200">
                            <div class="flex justify-between items-baseline">
                                <div>
                                    <span class="text-xs uppercase tracking-wider font-bold text-gray-500 block">Total Tagihan</span>
                                    <span class="text-[11px] text-gray-400">Termasuk PPN & Biaya Kirim</span>
                                </div>
                                <span class="text-2xl font-black text-brand-dark font-serif" id="total-cost">
                                    Rp {{ number_format(max(0, $originalCartTotal - ($totalPercentDiscount ?? 0) - ($totalNominalDiscount ?? 0) - ($priceProductSettingDiscount ?? 0) + ($form['shipping_cost'] ?? 0) - ($selectedVoucher['discount'] ?? 0)), 0, ',', '.') }}
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <button type="submit" {{ count($cart) === 0 ? 'disabled' : '' }} class="w-full py-4 bg-brand-dark hover:bg-brand-darker text-brand-gold hover:text-white rounded-xl font-bold text-sm tracking-wide uppercase transition-all duration-200 shadow-md hover:shadow-lg flex justify-center items-center gap-2.5 group disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer">
                        <span>Lanjut ke Pembayaran</span>
                        <i class="fa-solid fa-arrow-right text-xs group-hover:translate-x-1 transition-transform"></i>
                    </button>

                    <!-- Trust & Guarantees -->
                    <div class="mt-6 pt-5 border-t border-gray-100 space-y-2.5 text-xs text-gray-500">
                        <div class="flex items-center gap-2.5">
                            <div class="w-6 h-6 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                                <i class="fa-solid fa-shield-halved text-[11px]"></i>
                            </div>
                            <span>Transaksi Terlindungi & Enkripsi SSL</span>
                        </div>
                        <div class="flex items-center gap-2.5">
                            <div class="w-6 h-6 rounded-full bg-brand-gold/15 text-brand-gold-dark flex items-center justify-center shrink-0">
                                <i class="fa-solid fa-medal text-[11px]"></i>
                            </div>
                            <span>100% Produk Original & Bergaransi Resmi</span>
                        </div>
                        <div class="flex items-center gap-2.5">
                            <div class="w-6 h-6 rounded-full bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
                                <i class="fa-solid fa-truck-shield text-[11px]"></i>
                            </div>
                            <span>Jaminan Asuransi & Keamanan Pengiriman</span>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
    
    <script id="checkout-subdistrict-map" type="application/json">
        @json($subDistricts->mapWithKeys(fn($sd) => [$sd['id'] => ['city' => $sd['city'] ?? '', 'postal_code' => $sd['postal_code'] ?? '']]))
    </script>
    <script id="checkout-saved-addresses" type="application/json">
        @json($savedAddressesSafe)
    </script>
    <!-- jQuery & Select2 JS -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="{{ asset('js/frontend/checkout.js') }}?v={{ filemtime(public_path('js/frontend/checkout.js')) }}"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const emailInput = document.querySelector('input[name="email"]');
            const phoneInput = document.querySelector('input[name="phone"]');
            const nameInput = document.querySelector('input[name="name"]');
            
            let searchTimeout = null;
            
            function showLoading(inputElement) {
                let loadingEl = document.getElementById('customer-loading-indicator');
                if (!loadingEl) {
                    loadingEl = document.createElement('span');
                    loadingEl.id = 'customer-loading-indicator';
                    loadingEl.className = 'absolute right-3 top-1/2 -translate-y-1/2 text-brand-gold text-xs font-semibold animate-pulse bg-white px-1';
                    loadingEl.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-1"></i> Checking...';
                }
                // Pastikan selalu dipindahkan ke input yang sedang aktif
                inputElement.parentNode.style.position = 'relative';
                inputElement.parentNode.appendChild(loadingEl);
                loadingEl.style.display = 'block';
            }

            function hideLoading() {
                const loadingEl = document.getElementById('customer-loading-indicator');
                if (loadingEl) loadingEl.style.display = 'none';
            }

            function handleCustomerSearch(e) {
                const val = e.target.value.trim();
                
                if (val.length >= 4) {
                    showLoading(e.target);
                    fetch(`/checkout/search-user?term=${encodeURIComponent(val)}`)
                        .then(res => res.json())
                        .then(data => {
                            hideLoading();
                            if (data && data.length > 0) {
                                // Ambil hasil pertama yang paling cocok
                                const customer = data[0];
                                
                                // Isi otomatis nama, email, telepon
                                if(emailInput && emailInput !== e.target && !emailInput.value) { 
                                    emailInput.value = customer.email; 
                                    emailInput.dispatchEvent(new Event('input')); 
                                }
                                if(phoneInput && phoneInput !== e.target && !phoneInput.value) { 
                                    phoneInput.value = customer.phone; 
                                    phoneInput.dispatchEvent(new Event('input')); 
                                }
                                if(nameInput && !nameInput.value) { 
                                    nameInput.value = customer.name; 
                                    nameInput.dispatchEvent(new Event('input')); 
                                }
                                
                                if (typeof window.applyAddressData === 'function') {
                                    window.applyAddressData(customer);
                                } else {
                                    if (customer.address) {
                                        const addrInput = document.querySelector('textarea[name="address"]');
                                        if(addrInput && !addrInput.value) { addrInput.value = customer.address; addrInput.dispatchEvent(new Event('input')); }
                                    }
                                    if (customer.postal_code) {
                                        const postalInput = document.querySelector('input[name="postal_code"]');
                                        if(postalInput && !postalInput.value) { postalInput.value = customer.postal_code; postalInput.dispatchEvent(new Event('input')); }
                                    }
                                    if (customer.sub_district_id) {
                                        if (customer.province_id && typeof window.loadCities === 'function') {
                                            const provSelect = document.getElementById('checkout-province');
                                            if (provSelect) {
                                                provSelect.value = customer.province_id;
                                                window.loadCities(customer.province_id, customer.city_id, function() {
                                                    if (customer.city_id) {
                                                        window.loadSubDistricts(customer.city_id, customer.sub_district_id, function() {
                                                            const subSelect = document.getElementById('checkout-sub-district');
                                                            if (subSelect) {
                                                                subSelect.value = customer.sub_district_id;
                                                                subSelect.dispatchEvent(new Event('change'));
                                                            }
                                                        });
                                                    }
                                                });
                                            }
                                        } else {
                                            const subSelect = document.getElementById('checkout-sub-district') || document.querySelector('select[name="sub_district_id"]');
                                            if(subSelect && !subSelect.value) {
                                                subSelect.value = customer.sub_district_id;
                                                subSelect.dispatchEvent(new Event('change'));
                                                subSelect.dispatchEvent(new Event('input'));
                                            }
                                        }
                                    }
                                }
                            }
                        })
                        .catch(() => hideLoading());
                }
            }

            function debouncedCustomerSearch(e) {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(function() {
                    handleCustomerSearch(e);
                }, 400);
            }

            if (emailInput) {
                emailInput.addEventListener('input', debouncedCustomerSearch);
                emailInput.addEventListener('change', handleCustomerSearch);
            }
            if (phoneInput) {
                phoneInput.addEventListener('input', debouncedCustomerSearch);
                phoneInput.addEventListener('change', handleCustomerSearch);
            }

            // Trigger auto-completion on load if email exists (misal user login) dan sub-district belum terpilih
            if (emailInput && emailInput.value && emailInput.value.trim().length >= 4) {
                const subDistrictSelect = document.getElementById('checkout-sub-district');
                if (subDistrictSelect && !subDistrictSelect.value) {
                    setTimeout(() => {
                        handleCustomerSearch({ target: emailInput });
                    }, 200);
                }
            }
            const formInputs = document.querySelectorAll('input[name="email"], input[name="name"], input[name="phone"], select[name="province_id"], select[name="city_id"], select[name="sub_district_id"], textarea[name="address"], input[name="postal_code"]');
            
            // 1. Muat ulang data dari sessionStorage jika ada (kecuali jika ada error validasi dari server)
            const savedFormData = JSON.parse(sessionStorage.getItem('checkout_form_data') || '{}');
            formInputs.forEach(input => {
                // Jika input kosong (artinya bukan kembalian error dari server) dan ada data tersimpan
                if (!input.value && savedFormData[input.name]) {
                    input.value = savedFormData[input.name];
                    if (input.name === 'sub_district_id' || input.name === 'province_id' || input.name === 'city_id') {
                        input.dispatchEvent(new Event('change'));
                    }
                }
                
                // 2. Simpan setiap perubahan ke sessionStorage
                const saveChange = (e) => {
                    const currentData = JSON.parse(sessionStorage.getItem('checkout_form_data') || '{}');
                    currentData[e.target.name] = e.target.value;
                    sessionStorage.setItem('checkout_form_data', JSON.stringify(currentData));
                };
                input.addEventListener('input', saveChange);
                input.addEventListener('change', saveChange);
            });

            // 3. Bersihkan memori saat form sukses di-submit (opsional, ditaruh di event submit)
            const checkoutForm = document.getElementById('checkout-form') || document.querySelector('form');
            if (checkoutForm) {
                checkoutForm.addEventListener('submit', () => {
                    // Kita bisa membiarkannya agar kalau back masih ada, atau menghapusnya:
                    // sessionStorage.removeItem('checkout_form_data'); 
                });
            }

            document.addEventListener('click', function(e) {
                const dropdown = document.getElementById('customer-suggestions');
                if (dropdown && !e.target.closest('div.relative')) {
                    dropdown.style.display = 'none';
                }
            });
        });
    </script>
@endsection

@push('tracking_events')
<script>
    window.dataLayer = window.dataLayer || [];
    dataLayer.push({ ecommerce: null });
    dataLayer.push({
        event: "begin_checkout",
        ecommerce: {
            currency: "IDR",
            value: {{ collect($cart)->sum(fn($item) => $item['sell_price'] * $item['quantity']) }},
            items: [
                @foreach($cart as $id => $item)
                {
                    item_id: "{{ $item['product_id'] ?? '' }}",
                    item_name: "{{ $item['name'] ?? '' }}",
                    price: {{ $item['sell_price'] ?? 0 }},
                    quantity: {{ $item['quantity'] ?? 1 }}
                }@if(!$loop->last),@endif
                @endforeach
            ]
        }
    });
</script>
@endpush
