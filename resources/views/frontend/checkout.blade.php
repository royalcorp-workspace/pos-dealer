@extends('frontend.layouts.app')

@section('title', 'Checkout - IMG')
@section('robots', 'noindex,nofollow')

@section('content')
    @php
        $cart = session()->get('cart', []);
        $cartTotal = collect($cart)->sum(fn($item) => $item['price'] * $item['quantity']);
        $form = $checkoutFormData ?? [];
        $selectedVoucherCodes = array_values(array_unique(array_map('strtoupper', (array) ($selectedVoucherCodes ?? []))));
        if (($selectedVoucher['code'] ?? null) && !in_array(strtoupper($selectedVoucher['code']), $selectedVoucherCodes, true)) {
            $selectedVoucherCodes[] = strtoupper($selectedVoucher['code']);
        }
        $cartProductIds = collect($cart)->pluck('product_id')->filter()->unique()->values()->all();
        $cartCategoryIds = \App\Models\Frontend\ProductsCatalog\Product::whereIn('id', $cartProductIds)->pluck('category_id')->unique()->values()->all();
    @endphp
    <div class="container mx-auto px-4 md:px-6 py-12 min-h-[60vh] font-sans">
        <h1 class="text-3xl font-extrabold text-brand-dark mb-8 font-serif">Checkout</h1>
        
        <form action="{{ route('checkout.process') }}" method="POST" id="checkout-form" data-is-logged-in="{{ session()->get('is_logged_in') ? 1 : 0 }}" class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            @csrf
            <div class="lg:col-span-2">
                @if(count($cart) === 0)
                    <div class="bg-white border border-brand-muted rounded-2xl p-8 text-center">
                        <p class="text-gray-500">Keranjang belanja kosong. <a href="{{ route('home') }}" class="text-brand-gold hover:underline">Lanjutkan belanja</a></p>
                    </div>
                @else
                    <!-- Customer Information -->
                    <div class="bg-white border border-brand-muted rounded-2xl p-6 mb-6">
                        <div class="flex justify-between items-center mb-4">
                            <h2 class="font-bold text-brand-dark">Informasi Pembeli</h2>
                            @if($savedAddresses->isNotEmpty())
                                <button type="button" onclick="toggleAddressSelector()" class="text-sm text-brand-gold font-semibold hover:underline">
                                    Pilih Alamat Lain
                                </button>
                            @endif
                        </div>

                        @if($savedAddresses->isNotEmpty())
                            @php
                                $defaultAddress = $savedAddresses->firstWhere('is_primary', true) ?? $savedAddresses->first();
                            @endphp
                            <div id="address-selector" class="hidden mb-4 p-4 bg-brand-light rounded-xl">
                                <p class="text-sm font-semibold text-brand-dark mb-3">Alamat Tersimpan</p>
                                <div class="space-y-2 max-h-60 overflow-y-auto">
                                    @foreach($savedAddresses as $addr)
                                        <label class="flex items-start gap-3 p-3 border border-brand-muted rounded-lg cursor-pointer hover:border-brand-gold transition-colors">
                                            <input type="radio" name="selected_address_id" value="{{ $addr->id }}" class="mt-1" onchange="fillAddress(this)" {{ $addr->id === $defaultAddress->id ? 'checked' : '' }}>
                                            <div>
                                                <span class="font-semibold text-brand-dark">{{ $addr->label }}</span>
                                                @if($addr->is_primary)
                                                    <span class="text-xs bg-brand-gold/15 text-brand-gold-dark px-1 rounded">Utama</span>
                                                @endif
                                                <p class="text-sm text-gray-600">{{ $addr->recipient_name }} | {{ $addr->phone }}</p>
                                                <p class="text-sm text-gray-500">{{ $addr->address }}, {{ $addr->subDistrict->city->name ?? '' }} {{ $addr->postal_code }}</p>
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
                            $defaultName = old('name', $form['name'] ?? $defaultAddress?->recipient_name ?? $sessionUser['name'] ?? '');
                            $defaultEmail = old('email', $form['email'] ?? $sessionUser['email'] ?? '');
                            $defaultPhone = old('phone', $form['phone'] ?? $defaultAddress?->phone ?? '');
                            $defaultCity = old('city', $form['city'] ?? $defaultAddress?->subDistrict->city->name ?? '');
                            $defaultAddressText = old('address', $form['address'] ?? $defaultAddress?->address ?? '');
                            $defaultPostal = old('postal_code', $form['postal_code'] ?? $defaultAddress?->postal_code ?? '');
                        @endphp

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Email</label>
                                <input type="email" name="email" value="{{ $defaultEmail }}" required class="w-full px-4 py-3 border border-brand-muted rounded-xl focus:outline-none focus:border-brand-gold" placeholder="email@example.com">
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Nama Lengkap</label>
                                <input type="text" name="name" value="{{ $defaultName }}" required class="w-full px-4 py-3 border border-brand-muted rounded-xl focus:outline-none focus:border-brand-gold" placeholder="Nama lengkap">
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Nomor Telepon</label>
                                <input type="tel" name="phone" value="{{ $defaultPhone }}" required class="w-full px-4 py-3 border border-brand-muted rounded-xl focus:outline-none focus:border-brand-gold" placeholder="08xx xxxx xxxx">
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Kecamatan / Kelurahan</label>
                                <select name="sub_district_id" required class="w-full px-4 py-3 border border-brand-muted rounded-xl focus:outline-none focus:border-brand-gold bg-white">
                                    <option value="">Pilih Kecamatan/Kelurahan</option>
                                    @foreach($subDistricts as $sd)
                                        <option value="{{ $sd['id'] }}" {{ (old('sub_district_id', $form['sub_district_id'] ?? $defaultAddress?->subDistrict->id ?? '') == $sd['id']) ? 'selected' : '' }}>
                                            {{ $sd['label'] }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Kota</label>
                                <input type="text" name="city" id="city-display" value="{{ $defaultCity }}" required class="w-full px-4 py-3 border border-brand-muted rounded-xl focus:outline-none focus:border-brand-gold bg-gray-50" placeholder="Kota akan terisi otomatis">
                            </div>
                        </div>
                        <div class="mt-4">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Alamat Lengkap</label>
                            <textarea name="address" required rows="3" class="w-full px-4 py-3 border border-brand-muted rounded-xl focus:outline-none focus:border-brand-gold" placeholder="Jl. Sudirman No. 123, Jakarta">{{ $defaultAddressText }}</textarea>
                        </div>
                        <div class="mt-4">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Kode Pos</label>
                            <input type="text" name="postal_code" value="{{ $defaultPostal }}" class="w-full px-4 py-3 border border-brand-muted rounded-xl focus:outline-none focus:border-brand-gold" placeholder="12345">
                        </div>
                        <div class="mt-4">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Ekspedisi</label>
                            <select name="courier" required class="w-full px-4 py-3 border border-brand-muted rounded-xl focus:outline-none focus:border-brand-gold bg-white">
                                <option value="">Pilih Ekspedisi</option>
                                @foreach($couriers as $courier)
                                    @php
                                        $defaultPrice = $courier->shippingAddresses->where('type', 1)->first()->price ?? ($courier->shippingAddresses->first()->price ?? 25000);
                                    @endphp
                                    <option value="{{ $courier->code }}" {{ (old('courier', $form['courier'] ?? '') == $courier->code) ? 'selected' : '' }}>{{ $courier->name }} - Rp {{ number_format($defaultPrice, 0, ',', '.') }}</option>
                                @endforeach
                            </select>
                            <script id="checkout-courier-shipping-prices" type="application/json">
                                @json($couriers->mapWithKeys(function($c) {
                                    return [$c->code => floatval($c->shippingAddresses->where('type', 1)->first()->price ?? ($c->shippingAddresses->first()->price ?? 25000))];
                                }))
                            </script>
                        </div>
                    </div>

                    <div class="bg-white border border-brand-muted rounded-2xl p-6 mb-6">
                        <div class="flex justify-between items-start gap-4 mb-5">
                            <div>
                                <h2 class="font-bold text-brand-dark">Pilih Kupon yang Tersedia</h2>
                                <p class="text-sm text-gray-500 mt-1">Voucher otomatis terbaca dari database.</p>
                            </div>
                            <span class="inline-flex items-center rounded-full bg-brand-gold/15 px-3 py-1 text-[10px] font-extrabold uppercase tracking-wider text-brand-gold-dark">
                                {{ $vouchers->count() }} Tersedia
                            </span>
                        </div>

                        <input type="hidden" name="voucher_code" id="voucher-code" value="{{ implode(',', $selectedVoucherCodes) }}">
                        <input type="hidden" name="voucher_codes" id="voucher-codes" value="{{ implode(',', $selectedVoucherCodes) }}">
                        <input type="hidden" name="voucher_discount" id="voucher-discount-value" value="{{ $selectedVoucher['discount'] ?? 0 }}">

                        <div class="flex gap-3 mb-4">
                            <input type="text" id="manual-voucher-input" placeholder="Masukkan kode voucher" class="flex-1 px-4 py-2.5 border border-brand-muted rounded-xl focus:outline-none focus:border-brand-gold text-sm uppercase" maxlength="20">
                            <button type="button" onclick="validateAndApplyVoucher()" class="px-4 py-2.5 bg-brand-dark text-brand-gold rounded-xl font-bold text-sm hover:bg-brand-darker transition-colors">Gunakan</button>
                        </div>
                        <div id="manual-voucher-feedback" class="text-sm mb-3"></div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            @foreach($vouchers as $voucher)
                                @php
                                    $typeLabel = match((int)$voucher->type) {
                                        1 => 'Persen',
                                        2 => 'Nominal',
                                        3 => 'Gratis Ongkir',
                                        4 => 'Bonus Produk',
                                        default => 'Tidak diketahui',
                                    };
                                @endphp
                                @php
                                    $isUsable = $voucher->is_usable ?? true;
                                @endphp
                                <button type="button"
                                    @if($isUsable)
                                        onclick="selectCoupon(this)"
                                        class="coupon-card cursor-pointer text-left rounded-2xl border border-brand-muted bg-white p-4 hover:border-brand-gold hover:shadow-lg transition-all focus:outline-none focus:ring-2 focus:ring-brand-gold {{ ($selectedVoucher['code'] ?? '') === $voucher->code ? 'border-brand-gold bg-brand-light' : '' }}"
                                    @else
                                        class="coupon-card opacity-50 bg-gray-100 border-gray-300 pointer-events-none cursor-not-allowed text-left rounded-2xl border p-4 transition-all focus:outline-none"
                                    @endif
                                    data-code="{{ $voucher->code }}"
                                    data-title="{{ $voucher->title }}"
                                    data-discount-type="{{ $voucher->type == 1 ? 'percentage' : ($voucher->type == 2 ? 'fixed' : ($voucher->type == 3 ? 'shipping' : 'bonus')) }}"
                                    data-discount-value="{{ floatval($voucher->value) }}"
                                    data-max-discount="{{ $voucher->max_discount ?? '' }}"
                                    data-allow-stacking="{{ $voucher->allow_stacking ? 1 : 0 }}"
                                    data-products='@json($voucher->products->pluck("name"))'>
                                    <div class="flex items-start justify-between gap-3">
                                        <div>
                                            <p class="font-extrabold text-brand-dark">{{ $voucher->title }}</p>
                                            <p class="text-xs text-gray-500 mt-1">{{ $voucher->description }}</p>
                                        </div>
                                        <span class="inline-flex items-center rounded-full bg-red-50 px-2.5 py-1 text-[10px] font-extrabold uppercase tracking-wider text-red-600">
                                            {{ $voucher->value }}{{ $voucher->type == 1 ? '%' : '' }} {{ $typeLabel }}
                                        </span>
                                    </div>
                                    <div class="mt-3 flex flex-wrap gap-1.5">
                                        <span class="inline-flex items-center rounded-full bg-brand-light px-2 py-0.5 text-[10px] font-extrabold uppercase tracking-wider text-brand-gold-dark">
                                            {{ $voucher->scopeLabel() }}
                                        </span>
                                        <span class="inline-flex items-center rounded-full {{ $voucher->allow_stacking ? 'bg-green-50 text-green-700' : 'bg-gray-100 text-gray-500' }} px-2 py-0.5 text-[10px] font-extrabold uppercase tracking-wider">
                                            {{ $voucher->allow_stacking ? 'Bisa Digabung' : 'Single Voucher' }}
                                        </span>
                                    </div>
                                    <div class="mt-4 flex items-center justify-between">
                                        <span class="font-mono text-sm font-bold text-brand-gold-dark">{{ $voucher->code }}</span>
                                        @if($isUsable)
                                            <span class="text-xs text-gray-400 select-coupon-label">{{ ($selectedVoucher['code'] ?? '') === $voucher->code ? 'Dipilih' : 'Pilih' }}</span>
                                        @else
                                            <span class="text-xs font-bold text-red-500 uppercase tracking-wider select-coupon-label">Limit Habis</span>
                                        @endif
                                    </div>
                                    @if(!$isUsable)
                                        <div class="mt-2 text-[10px] font-bold text-red-600 uppercase tracking-wider border-t pt-2">
                                            Kupon sudah pernah digunakan oleh Anda
                                        </div>
                                    @endif
                                </button>
                            @endforeach
                        </div>

                        <div id="selected-coupon-text" class="mt-4 rounded-xl bg-brand-light p-3 text-sm text-gray-600">
                            @if(count($selectedVoucherCodes) > 0)
                                Kupon dipilih: <strong class="text-brand-dark">{{ implode(', ', $selectedVoucherCodes) }}</strong>
                            @else
                                Belum ada kupon dipilih.
                            @endif
                        </div>
                        <div id="bonus-products-display" class="space-y-2 mt-2">
                            @foreach($selectedVouchers as $sv)
                                @if((int)$sv->type === 4 && $sv->products->isNotEmpty())
                                    <div class="mt-3 flex items-start gap-2.5 bg-green-50 text-green-800 p-3 rounded-xl border border-green-200 text-xs font-semibold">
                                        <i class="fa-solid fa-circle-check text-sm text-green-600 mt-0.5"></i>
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

                    <!-- Order Summary -->
                    <div class="bg-white border border-brand-muted rounded-2xl p-6">
                        <h2 class="font-bold text-brand-dark mb-4">Ringkasan Pesanan</h2>
                        <div class="space-y-4">
                            @foreach($cart as $item)
                                <div class="py-3 border-b">
                                    <div class="flex justify-between items-start gap-3">
                                        <div>
                                            <span class="font-medium text-brand-dark">{{ $item['name'] }}</span>
                                            <span class="text-sm text-gray-500"> x {{ $item['quantity'] }}</span>
                                        </div>
                                        <div class="text-right flex flex-col items-end">
                                            @if(($item['original_price'] ?? $item['price']) > $item['price'])
                                                <div class="flex items-center gap-1.5 justify-end mb-0.5">
                                                    @php
                                                        $op = $item['original_price'] ?? $item['price'];
                                                        $ip = $item['price'];
                                                        $discountPercent = round((($op - $ip) / $op) * 100);
                                                    @endphp
                                                    @if($discountPercent > 0)
                                                        <span class="bg-red-50 text-red-600 text-[9px] font-extrabold px-1.5 py-0.5 rounded-full">{{ $discountPercent }}% OFF</span>
                                                    @endif
                                                    <span class="text-xs text-gray-400 line-through">Rp {{ number_format($op * $item['quantity'], 0, ',', '.') }}</span>
                                                </div>
                                            @endif
                                            <span class="font-semibold text-brand-dark">Rp {{ number_format($item['price'] * $item['quantity'], 0, ',', '.') }}</span>
                                        </div>
                                    </div>
                                    <textarea
                                        name="item_notes[{{ $item['id'] }}]"
                                        rows="2"
                                        maxlength="500"
                                        class="mt-3 w-full rounded-xl border border-brand-muted px-3 py-2 text-sm focus:outline-none focus:border-brand-gold"
                                        placeholder="Catatan khusus untuk item ini, contoh: warna, ukuran tambahan, atau instruksi pengiriman"
                                    >{{ $item['item_note'] ?? '' }}</textarea>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
            
            <div class="lg:col-span-1">
                <div class="bg-white border border-brand-muted rounded-2xl p-6 sticky top-6">
                     <input type="hidden" id="checkout-subtotal" data-value="{{ $originalCartTotal }}">
                     <input type="hidden" id="checkout-promo-discount" data-value="{{ $totalPercentDiscount + $totalNominalDiscount }}">
                     <input type="hidden" id="checkout-shipping-cost" data-value="{{ $form['shipping_cost'] ?? 0 }}">
                     <input type="hidden" id="checkout-product-discount" data-value="{{ $priceProductSettingDiscount ?? 0 }}">
                     <input type="hidden" id="checkout-voucher-discount" data-value="{{ $selectedVoucher['discount'] ?? 0 }}">
                    <input type="hidden" id="checkout-selected-voucher-codes" data-value="{{ implode(',', $selectedVoucherCodes) }}">
                    <input type="hidden" id="checkout-product-ids" data-value='@json($cartProductIds)'>
                    <input type="hidden" id="checkout-category-ids" data-value='@json($cartCategoryIds)'>
                    <div id="checkout-form-data" data-has-existing-data="{{ !empty($form) ? 1 : 0 }}"></div>
                    <h3 class="font-bold text-brand-dark mb-4">Total Pembayaran</h3>
                    <div class="space-y-2 mb-6">
                        <div class="flex justify-between">
                            <span class="text-gray-500">Subtotal</span>
                            <span class="font-semibold text-brand-dark" id="checkout-subtotal-display">Rp {{ number_format($originalCartTotal, 0, ',', '.') }}</span>
                        </div>

                        @if(($totalPercentDiscount ?? 0) > 0)
                            <div class="flex justify-between text-red-600">
                                <span class="text-gray-500">Diskon Promo</span>
                                <span class="font-semibold">- Rp {{ number_format($totalPercentDiscount, 0, ',', '.') }}</span>
                            </div>
                        @endif

                        @if(($totalNominalDiscount ?? 0) > 0)
                            <div class="flex justify-between text-red-600">
                                <span class="text-gray-500">Diskon Promo</span>
                                <span class="font-semibold">- Rp {{ number_format($totalNominalDiscount, 0, ',', '.') }}</span>
                            </div>
                        @endif

                        <div class="flex justify-between text-red-600 {{ ($priceProductSettingDiscount ?? 0) > 0 ? '' : 'hidden' }}" id="product-discount-row">
                            <span class="text-gray-500">Diskon Volume</span>
                            <span class="font-semibold" id="product-discount">- Rp {{ number_format($priceProductSettingDiscount ?? 0, 0, ',', '.') }}</span>
                        </div>

                        <div class="flex justify-between">
                            <span class="text-gray-500" id="checkout-shipping-label">Shipping (Kurir)</span>
                            <span class="text-brand-dark font-semibold" id="shipping-cost">Rp {{ number_format($form['shipping_cost'] ?? 0, 0, ',', '.') }}</span>
                            <span id="checkout-shipping-cost" data-value="{{ $form['shipping_cost'] ?? 0 }}" class="hidden"></span>
                        </div>

                        <div class="flex justify-between text-red-600" id="checkout-voucher-row" style="{{ ($selectedVoucher['discount'] ?? 0) > 0 ? '' : 'display: none;' }}">
                            <span class="text-gray-500" id="checkout-voucher-label">Voucher ({{ implode(',', $selectedVoucherCodes) }})</span>
                            <span class="font-semibold" id="voucher-discount">- Rp {{ number_format($selectedVoucher['discount'] ?? 0, 0, ',', '.') }}</span>
                            <span id="checkout-voucher-discount" data-value="{{ $selectedVoucher['discount'] ?? 0 }}" class="hidden"></span>
                        </div>

                        <div class="flex justify-between text-lg font-bold border-t pt-2">
                            <span>Total</span>
                            <span class="text-brand-dark" id="total-cost">Rp {{ number_format(max(0, $cartTotal - ($priceProductSettingDiscount ?? 0) + ($form['shipping_cost'] ?? 0) - ($selectedVoucher['discount'] ?? 0)), 0, ',', '.') }}</span>
                        </div>
                    </div>
                    
                    <button type="submit" {{ count($cart) === 0 ? 'disabled' : '' }} class="w-full py-3 bg-brand-dark text-brand-gold rounded-xl font-bold hover:bg-brand-darker transition-colors flex justify-center items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed">
                        Lanjutkan ke Pembayaran <i class="fa-solid fa-arrow-right w-4 h-4"></i>
                    </button>
                </div>
            </div>
        </form>
    </div>
    
    <script id="checkout-subdistrict-map" type="application/json">
        @json($subDistricts->map(fn($sd) => ['city' => $sd['city'], 'postal_code' => $sd['postal_code']]))
    </script>
    <script id="checkout-saved-addresses" type="application/json">
        @json($savedAddressesSafe)
    </script>
    <script src="{{ asset('js/frontend/checkout.js') }}?v={{ filemtime(public_path('js/frontend/checkout.js')) }}"></script>
@endsection
