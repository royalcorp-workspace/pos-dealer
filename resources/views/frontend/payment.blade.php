@extends('frontend.layouts.app')

@section('title', 'Pemilihan Pembayaran - IMG')
@section('robots', 'noindex,nofollow')

@section('content')
    <div class="container mx-auto px-4 md:px-6 py-8 md:py-12 min-h-[60vh] font-sans" id="payment-container" data-order-id="{{ $orderData['id'] ?? '' }}" data-route-thankyou="{{ route('thankyou') }}" data-route-payment-process="{{ route('payment.process') }}">
        <!-- Progress / Step Indicator Wizard -->
        <div class="max-w-3xl mx-auto mb-10">
            <div class="relative flex items-center justify-between">
                <!-- Background track -->
                <div class="absolute left-0 top-1/2 -translate-y-1/2 h-1 bg-gray-200 w-full z-0 rounded-full"></div>
                <!-- Active track: Step 1 to Step 3 -->
                <div class="absolute left-0 top-1/2 -translate-y-1/2 h-1 bg-brand-gold w-2/3 z-0 rounded-full transition-all duration-500"></div>

                <!-- Step 1: Keranjang (Completed) -->
                <a href="{{ route('home') }}" class="relative z-10 flex flex-col items-center group cursor-pointer" title="Keranjang Belanja">
                    <div class="w-10 h-10 rounded-full bg-brand-gold text-white flex items-center justify-center font-bold text-sm shadow-md transition-transform group-hover:scale-110">
                        <i class="fa-solid fa-check"></i>
                    </div>
                    <span class="text-xs font-semibold text-brand-dark mt-2 tracking-tight">Keranjang</span>
                </a>

                <!-- Step 2: Pilih Pengiriman (Completed) -->
                <a href="{{ route('checkout') }}" class="relative z-10 flex flex-col items-center group cursor-pointer" title="Kembali ke Pilih Pengiriman">
                    <div class="w-10 h-10 rounded-full bg-brand-gold text-white flex items-center justify-center font-bold text-sm shadow-md transition-transform group-hover:scale-110">
                        <i class="fa-solid fa-check"></i>
                    </div>
                    <span class="text-xs font-semibold text-brand-dark mt-2 tracking-tight">Alamat & Pilih Pengiriman</span>
                </a>

                <!-- Step 3: Pembayaran (Active) -->
                <div class="relative z-10 flex flex-col items-center">
                    <div class="w-10 h-10 rounded-full bg-brand-dark text-brand-gold border-2 border-brand-gold flex items-center justify-center font-bold text-sm shadow-lg ring-4 ring-brand-gold/20 scale-105">
                        <i class="fa-solid fa-credit-card"></i>
                    </div>
                    <span class="text-xs font-bold text-brand-dark mt-2 tracking-tight">Pembayaran</span>
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

        <div class="max-w-4xl mx-auto flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
            <div>
                <h1 class="text-2xl md:text-3xl font-extrabold text-brand-dark font-serif tracking-tight">Pilih Pengiriman & Pembayaran</h1>
                <p class="text-xs md:text-sm text-gray-500 mt-1">Periksa kembali detail pesanan Anda dan pilih metode pembayaran</p>
            </div>
            <a href="{{ route('checkout') }}" class="inline-flex items-center gap-2 text-xs font-semibold text-brand-gold-dark hover:text-brand-dark transition-colors">
                <i class="fa-solid fa-arrow-left"></i> Kembali ke Pilih Pengiriman
            </a>
        </div>
        
        <!-- Single Column Layout (Sidebar removed as requested) -->
        <div class="max-w-4xl mx-auto space-y-6">
            
            {{-- 1. ALAMAT PENGIRIMAN --}}
            @php
                $shippingAddr = $orderData['shipping_address'] ?? [];
                $customer = $orderData['customer'] ?? [];
                $recipientName = $shippingAddr['recipient_name'] ?? $customer['name'] ?? session()->get('user', [])['name'] ?? '';
                $phone = $shippingAddr['phone'] ?? $customer['phone'] ?? session()->get('user', [])['phone'] ?? '';
                $addressText = $shippingAddr['address'] ?? $customer['address'] ?? '';
                $subDistrict = $shippingAddr['sub_district'] ?? '';
                $city = $shippingAddr['city'] ?? '';
                $province = $shippingAddr['province'] ?? '';
                $postalCode = $shippingAddr['postal_code'] ?? ($customer['postal_code'] ?? '');

                $locationParts = array_filter([$addressText, $subDistrict, $city, $province, $postalCode]);
                $fullAddressString = implode(', ', $locationParts);
            @endphp
            @if(!empty($recipientName) || !empty($addressText))
                <div class="bg-white border border-brand-muted/80 rounded-2xl p-6 sm:p-7 shadow-sm hover:shadow-md transition-shadow">
                    <div class="flex items-center justify-between gap-3 mb-4 pb-3 border-b border-gray-100">
                        <div class="flex items-center gap-2.5">
                            <div class="w-8 h-8 rounded-lg bg-brand-gold/15 text-brand-gold-dark flex items-center justify-center text-sm">
                                <i class="fa-solid fa-location-dot"></i>
                            </div>
                            <h2 class="font-bold text-brand-dark text-base">Alamat Pengiriman</h2>
                        </div>
                        <a href="{{ route('checkout') }}" class="text-xs font-bold text-brand-gold-dark hover:underline flex items-center gap-1">
                            <i class="fa-solid fa-pen-to-square text-[10px]"></i> Ubah
                        </a>
                    </div>
                    <div class="flex flex-col sm:flex-row sm:items-baseline justify-between gap-1 text-sm">
                        <p class="font-bold text-brand-dark">{{ $recipientName }} <span class="font-normal text-gray-500">({{ $phone }})</span></p>
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="text-xs font-semibold text-brand-gold-dark bg-brand-gold/10 px-2.5 py-0.5 rounded-full inline-block w-fit">
                                Kurir: {{ strtoupper($orderData['courier'] ?? 'Ekspedisi') }}
                            </span>
                            @if(!empty($orderData['eta_label']))
                                <span class="text-xs font-semibold text-blue-700 bg-blue-50 border border-blue-200 px-2.5 py-0.5 rounded-full inline-block w-fit">
                                    <i class="fa-solid fa-clock text-[10px] mr-1"></i> Estimasi Tiba: {{ $orderData['eta_label'] }}
                                </span>
                            @endif
                        </div>
                    </div>
                    <p class="text-xs sm:text-sm text-gray-600 mt-1 leading-relaxed">{{ $fullAddressString }}</p>
                </div>
            @endif

            {{-- 2. ITEM YANG DIBELI --}}
            @if(!empty($orderData['items']))
                <div class="bg-white border border-brand-muted/80 rounded-2xl p-6 sm:p-7 shadow-sm hover:shadow-md transition-shadow">
                    <div class="flex items-center justify-between mb-4 pb-3 border-b border-gray-100">
                        <div class="flex items-center gap-2.5">
                            <div class="w-8 h-8 rounded-lg bg-brand-gold/15 text-brand-gold-dark flex items-center justify-center text-sm">
                                <i class="fa-solid fa-bag-shopping"></i>
                            </div>
                            <h2 class="font-bold text-brand-dark text-base">Item yang Dibeli</h2>
                        </div>
                        <span class="text-xs font-bold text-gray-500 bg-gray-100 px-2.5 py-0.5 rounded-full">
                            {{ count($orderData['items']) }} Item
                        </span>
                    </div>
                    <div class="space-y-3">
                        @foreach($orderData['items'] as $item)
                            @php
                                $itemImage = $item['image'] ?? null;
                                if (empty($itemImage) && !empty($item['product_id'])) {
                                    $pModel = \App\Models\Frontend\ProductsCatalog\Product::find($item['product_id']);
                                    $itemImage = $pModel?->thumbnail_url;
                                }
                            @endphp
                            <div class="flex items-center gap-3.5 py-3 border-b last:border-b-0">
                                <div class="w-14 h-14 rounded-xl bg-white overflow-hidden flex-shrink-0 border border-gray-200 shadow-2xs">
                                    @if(!empty($itemImage))
                                        <img src="{{ $itemImage }}" alt="{{ $item['name'] ?? 'Produk' }}" loading="lazy" decoding="async" class="w-full h-full object-cover">
                                    @else
                                        <div class="w-full h-full flex items-center justify-center text-brand-gold bg-brand-light">
                                            <i class="fa-solid fa-box text-base"></i>
                                        </div>
                                    @endif
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="font-bold text-sm text-gray-800 truncate">{{ $item['name'] }}</p>
                                    <p class="text-xs text-gray-500 mt-0.5 font-medium">Qty: {{ $item['quantity'] }}</p>
                                </div>
                                <span class="font-bold text-sm text-brand-dark ml-4 flex-shrink-0">Rp {{ number_format($item['sell_price'] * $item['quantity'], 0, ',', '.') }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- 3. PERHITUNGAN HARGA (SUB TOTAL SAMPAI TOTAL AKHIR) --}}
            <div class="bg-white border border-brand-muted/80 rounded-2xl p-6 sm:p-7 shadow-sm hover:shadow-md transition-shadow">
                <div class="flex items-center gap-2.5 mb-4 pb-3 border-b border-gray-100">
                    <div class="w-8 h-8 rounded-lg bg-brand-gold/15 text-brand-gold-dark flex items-center justify-center text-sm">
                        <i class="fa-solid fa-receipt"></i>
                    </div>
                    <h2 class="font-bold text-brand-dark text-base">Perhitungan Harga</h2>
                </div>
                @php
                    $items = $orderData['items'] ?? [];
                    $originalSubtotal = (float) ($orderData['original_cart_total'] ?? 0);
                    if ($originalSubtotal <= 0) {
                        $originalSubtotal = (float) collect($items)->sum(fn($i) => ($i['sell_price'] ?? 0) * ($i['quantity'] ?? 0));
                    }
                    
                    $totalPercentDiscount = 0.0;
                    $totalNominalDiscount = 0.0;
                    foreach ($items as $i) {
                        $itemDiscountTotal = ($i['discount_nominal'] ?? 0) * ($i['quantity'] ?? 0);
                        if (($i['discount_percent'] ?? 0) > 0) {
                            $totalPercentDiscount += $itemDiscountTotal;
                        } else {
                            $totalNominalDiscount += $itemDiscountTotal;
                        }
                    }
                    $priceSettingDiscount = (float) ($orderData['price_product_setting_discount'] ?? 0);
                    $staticPromoDiscount = (float) ($orderData['total_static_discount'] ?? ($totalPercentDiscount + $totalNominalDiscount));
                    if ($staticPromoDiscount <= 0 && !empty($orderData['promo_discount'])) {
                        $staticPromoDiscount = max(0, (float) $orderData['promo_discount'] - $priceSettingDiscount);
                    }

                    $productVoucherDiscount = (float) ($orderData['product_voucher_discount'] ?? 0);
                    $shippingVoucherDiscount = (float) ($orderData['shipping_voucher_discount'] ?? 0);
                    $voucherDiscount = (float) ($orderData['voucher_discount'] ?? ($productVoucherDiscount + $shippingVoucherDiscount));

                    if ($productVoucherDiscount == 0 && $shippingVoucherDiscount == 0 && $voucherDiscount > 0) {
                        $applied = $orderData['applied_vouchers'] ?? [];
                        if (!empty($applied)) {
                            foreach ($applied as $av) {
                                if (!empty($av['is_shipping'])) {
                                    $shippingVoucherDiscount += (float) ($av['discount'] ?? 0);
                                } else {
                                    $productVoucherDiscount += (float) ($av['discount'] ?? 0);
                                }
                            }
                        } else {
                            $productVoucherDiscount = $voucherDiscount;
                        }
                    }

                    $shippingCost = (float) ($orderData['shipping_cost'] ?? 0);
                    $shippingVoucherDiscount = min($shippingVoucherDiscount, $shippingCost);

                    $calculatedTotal = max(0, $originalSubtotal - $staticPromoDiscount - $priceSettingDiscount - $productVoucherDiscount) + max(0, $shippingCost - $shippingVoucherDiscount);
                    $displayTotal = (float) ($orderData['total'] ?? $calculatedTotal);
                    if ($displayTotal <= 0) {
                        $displayTotal = $calculatedTotal;
                    }
                @endphp
                <div class="space-y-3 text-sm">
                    {{-- 1. Sub Total Produk --}}
                    <div class="flex justify-between items-center text-gray-600">
                        <span>Sub Total Produk</span>
                        <span class="font-bold text-brand-dark">Rp {{ number_format($originalSubtotal, 0, ',', '.') }}</span>
                    </div>

                    {{-- 2. Diskon Promo --}}
                    @if($staticPromoDiscount > 0)
                        <div class="flex justify-between items-center text-red-600">
                            <span class="text-gray-600 flex items-center gap-1.5"><i class="fa-solid fa-tag text-xs text-red-500"></i> Diskon Promo</span>
                            <span class="font-bold">- Rp {{ number_format($staticPromoDiscount, 0, ',', '.') }}</span>
                        </div>
                    @endif

                    {{-- 3. Diskon Volume --}}
                    @if($priceSettingDiscount > 0)
                        <div class="flex justify-between items-center text-red-600">
                            <span class="text-gray-600 flex items-center gap-1.5"><i class="fa-solid fa-boxes-stacked text-xs text-red-500"></i> Diskon Volume</span>
                            <span class="font-bold">- Rp {{ number_format($priceSettingDiscount, 0, ',', '.') }}</span>
                        </div>
                    @endif

                    {{-- 4. Voucher Diskon (gabungan all voucher diskon) --}}
                    @if($productVoucherDiscount > 0)
                        @php
                            $appliedProductVouchers = collect($orderData['applied_vouchers'] ?? [])->filter(fn($av) => empty($av['is_shipping']));
                            $productVoucherCode = $appliedProductVouchers->pluck('code')->first() 
                                ?? collect($orderData['voucher_codes'] ?? [])->filter(fn($c) => !str_contains(strtoupper($c), 'ONGKIR'))->first();
                        @endphp
                        <div class="flex justify-between items-center text-red-600">
                            <span class="text-gray-600 flex items-center gap-1.5"><i class="fa-solid fa-ticket text-xs text-red-500"></i> Voucher Diskon{{ $productVoucherCode ? ' (' . $productVoucherCode . ')' : '' }}</span>
                            <span class="font-bold">- Rp {{ number_format($productVoucherDiscount, 0, ',', '.') }}</span>
                        </div>
                    @endif

                    {{-- 5. Shipping --}}
                    <div class="flex justify-between items-center text-gray-600">
                        <span>Shipping ({{ strtoupper($orderData['courier'] ?? 'Kurir') }})</span>
                        <span class="font-bold text-brand-dark">Rp {{ number_format($shippingCost, 0, ',', '.') }}</span>
                    </div>
                    @if(!empty($orderData['eta_label']))
                        <div class="flex justify-between items-center text-xs text-blue-700 bg-blue-50/60 px-2.5 py-1.5 rounded-lg">
                            <span class="flex items-center gap-1.5"><i class="fa-solid fa-clock text-[11px] text-blue-500"></i> Estimasi Tiba</span>
                            <span class="font-semibold">{{ $orderData['eta_label'] }}</span>
                        </div>
                    @endif

                    {{-- 6. Voucher Gratis Ongkir (motong biaya kirim only, jangan sampai potong harga barang) --}}
                    @if($shippingVoucherDiscount > 0)
                        <div class="flex justify-between items-center text-red-600">
                            <span class="text-gray-600 flex items-center gap-1.5"><i class="fa-solid fa-truck-fast text-xs text-emerald-600"></i> Voucher Gratis Ongkir</span>
                            <span class="font-bold">- Rp {{ number_format($shippingVoucherDiscount, 0, ',', '.') }}</span>
                        </div>
                    @endif
                    
                    {{-- Biaya Layanan / Tambahan (jika ada charge) --}}
                    <div id="charge-row" class="flex justify-between items-center text-gray-600 hidden">
                        <span>Biaya Tambahan / Layanan</span>
                        <span id="charge-amount" class="font-bold text-brand-dark">Rp 0</span>
                    </div>
                    
                    {{-- Total Pembayaran (Total Akhir yang harus dibayar) --}}
                    <div class="flex justify-between items-baseline pt-4 border-t border-dashed border-gray-200">
                        <span class="font-bold text-base text-gray-800">Total Pembayaran</span>
                        <span id="final-total" class="font-black text-2xl text-brand-dark font-serif" data-base-total="{{ $displayTotal }}">Rp {{ number_format($displayTotal, 0, ',', '.') }}</span>
                    </div>
                </div>
            </div>

            {{-- 4. METODE PEMBAYARAN --}}
            <div class="bg-white border border-brand-muted/80 rounded-2xl p-6 sm:p-7 shadow-sm hover:shadow-md transition-shadow">
                <div class="flex items-center gap-2.5 mb-6 pb-3.5 border-b border-gray-100">
                    <div class="w-8 h-8 rounded-lg bg-brand-gold/15 text-brand-gold-dark flex items-center justify-center text-sm">
                        <i class="fa-solid fa-wallet"></i>
                    </div>
                    <div>
                        <h2 class="font-bold text-brand-dark text-base">Metode Pembayaran</h2>
                        <p class="text-xs text-gray-500">Pilih salah satu metode pembayaran di bawah ini</p>
                    </div>
                </div>
                
                <div class="space-y-4">
                    <!-- Validation Error Banner -->
                    <div id="payment-method-validation-error" class="hidden p-4 rounded-xl bg-red-50 border-2 border-red-300 text-red-800 text-xs sm:text-sm font-medium shadow-sm transition-all duration-300">
                        <div class="flex items-start gap-3">
                            <div class="w-7 h-7 rounded-full bg-red-100 flex items-center justify-center shrink-0 text-red-600 mt-0.5">
                                <i class="fa-solid fa-circle-exclamation text-base"></i>
                            </div>
                            <div class="flex-1">
                                <h4 class="font-bold text-red-900 text-sm mb-0.5">Metode Pembayaran Belum Dipilih</h4>
                                <p class="text-xs text-red-700 leading-relaxed">
                                    Silakan pilih salah satu metode pembayaran di bawah ini (Transfer Bank / Virtual Account / E-Wallet / QRIS / Kartu Debit / Kartu Kredit) sebelum melanjutkan ke proses pembayaran.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- 1. Trigger Card: Belum Dipilih -->
                    <div id="payment-method-trigger-card" onclick="openPaymentMethodModal()" class="group bg-gradient-to-r from-amber-50/40 via-white to-amber-50/20 border-2 border-dashed border-brand-gold/60 hover:border-brand-gold rounded-2xl p-5 sm:p-6 cursor-pointer transition-all duration-300 hover:shadow-md flex items-center justify-between gap-4">
                        <div class="flex items-center gap-3.5 sm:gap-4 min-w-0">
                            <div class="w-12 h-12 rounded-2xl bg-brand-gold/15 text-brand-gold-dark flex items-center justify-center text-xl shrink-0 group-hover:scale-105 transition-transform shadow-2xs">
                                <i class="fa-solid fa-wallet"></i>
                            </div>
                            <div class="min-w-0">
                                <div class="flex items-center gap-2">
                                    <h3 class="font-extrabold text-base sm:text-lg text-brand-dark group-hover:text-brand-gold-dark transition-colors">Pilih Metode Pembayaran</h3>
                                    <span class="text-[10px] font-bold text-amber-800 bg-amber-100/90 px-2 py-0.5 rounded-full uppercase tracking-wider">Wajib Dipilih</span>
                                </div>
                                <p class="text-xs text-gray-500 mt-1 truncate">Klik di sini untuk memilih Virtual Account, Transfer Bank, QRIS, atau Kartu Kredit</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2 shrink-0">
                            <button type="button" class="px-4 sm:px-5 py-2.5 bg-brand-dark group-hover:bg-brand-darker text-brand-gold rounded-xl font-bold text-xs sm:text-sm tracking-wide uppercase transition-all shadow-sm flex items-center gap-2 cursor-pointer">
                                <span>Pilih</span>
                                <i class="fa-solid fa-chevron-right text-xs"></i>
                            </button>
                        </div>
                    </div>

                    <!-- 2. Selected Card: Sudah Dipilih -->
                    <div id="payment-method-selected-card" onclick="openPaymentMethodModal()" class="hidden bg-white border-2 border-brand-gold rounded-2xl p-5 sm:p-6 cursor-pointer transition-all duration-300 shadow-sm hover:shadow-md flex items-center justify-between gap-4">
                        <div class="flex items-center gap-3.5 sm:gap-4 min-w-0 flex-1">
                            <div id="selected-method-logo-display" class="w-14 h-10 sm:w-16 sm:h-11 rounded-xl bg-gray-50 border border-gray-200 flex items-center justify-center shrink-0 p-1 shadow-2xs">
                                <!-- Dynamic logo inserted -->
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <h3 id="selected-method-name-display" class="font-extrabold text-sm sm:text-base text-brand-dark truncate"></h3>
                                    <span id="selected-method-badge-display" class="text-[10px] text-emerald-700 bg-emerald-50 border border-emerald-200 px-2 py-0.5 rounded-full font-bold"></span>
                                    <span id="selected-method-charge-display" class="hidden text-[10px] text-gray-500 bg-gray-100 px-2 py-0.5 rounded-full font-medium"></span>
                                </div>
                                <p id="selected-method-sub-display" class="text-xs text-gray-500 mt-0.5 truncate"></p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2 shrink-0">
                            <button type="button" class="text-xs font-bold text-brand-gold-dark hover:text-brand-dark bg-brand-gold/15 hover:bg-brand-gold/25 px-3.5 py-2 rounded-xl transition-all flex items-center gap-1.5 cursor-pointer">
                                <i class="fa-solid fa-pen-to-square text-[11px]"></i>
                                <span>Ubah Metode</span>
                            </button>
                        </div>
                    </div>

                    <!-- 3. Popup Modal Pemilihan Metode Pembayaran (Popup Snap Style) -->
                    <div id="payment-method-modal" class="fixed inset-0 z-50 hidden bg-black/75 backdrop-blur-xs flex items-center justify-center p-2.5 sm:p-4 transition-all duration-300">
                        <div class="bg-white rounded-3xl shadow-2xl w-full max-w-2xl overflow-hidden border border-gray-100 flex flex-col max-h-[90vh] relative animate-in fade-in zoom-in-95 duration-200">
                            <!-- Modal Header -->
                            <div class="px-5 sm:px-6 py-4 bg-brand-dark text-white flex items-center justify-between border-b border-gray-800 shrink-0">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-xl bg-brand-gold/20 flex items-center justify-center text-brand-gold text-base">
                                        <i class="fa-solid fa-wallet"></i>
                                    </div>
                                    <div>
                                        <h3 class="font-bold text-base sm:text-lg tracking-tight text-white flex items-center gap-2">
                                            <span>Pilih Metode Pembayaran</span>
                                        </h3>
                                        <p class="text-xs text-gray-400">Silakan pilih cara pembayaran yang Anda inginkan</p>
                                    </div>
                                </div>
                                <button type="button" onclick="closePaymentMethodModal()" class="w-8 h-8 rounded-full bg-white/10 hover:bg-white/20 text-gray-300 hover:text-white flex items-center justify-center transition-colors cursor-pointer" title="Tutup">
                                    <i class="fa-solid fa-xmark text-sm"></i>
                                </button>
                            </div>

                            <!-- Modal Body (Scrollable List) -->
                            <div class="overflow-y-auto p-4 sm:p-6 space-y-5 flex-1 divide-y divide-gray-100" id="payment-modal-body">
                                @php
                                    $groupedMethods = collect($paymentMethods)->groupBy('type');
                                @endphp
                                @foreach($groupedMethods as $type => $methods)
                                    @php
                                        $typeLower = strtolower($type);
                                        $catIcon = 'fa-solid fa-building-columns';
                                        $catColor = 'bg-blue-50 text-blue-600 border border-blue-100';
                                        
                                        if (str_contains($typeLower, 'transfer') || str_contains($typeLower, 'manual')) {
                                            $catIcon = 'fa-solid fa-money-bill-transfer';
                                            $catColor = 'bg-amber-50 text-amber-700 border border-amber-100';
                                        } elseif (str_contains($typeLower, 'wallet') || str_contains($typeLower, 'qris')) {
                                            $catIcon = 'fa-solid fa-wallet';
                                            $catColor = 'bg-emerald-50 text-emerald-600 border border-emerald-100';
                                        } elseif (str_contains($typeLower, 'debit')) {
                                            $catIcon = 'fa-solid fa-credit-card';
                                            $catColor = 'bg-teal-50 text-teal-600 border border-teal-100';
                                        } elseif (str_contains($typeLower, 'card') || str_contains($typeLower, 'kartu') || str_contains($typeLower, 'credit')) {
                                            $catIcon = 'fa-regular fa-credit-card';
                                            $catColor = 'bg-purple-50 text-purple-600 border border-purple-100';
                                        }
                                    @endphp
                                    <div class="pt-4 first:pt-0">
                                        <div class="flex items-center gap-2 mb-3">
                                            <div class="w-6 h-6 rounded-lg {{ $catColor }} flex items-center justify-center text-xs shrink-0">
                                                <i class="{{ $catIcon }}"></i>
                                            </div>
                                            <h4 class="font-extrabold text-sm text-brand-dark tracking-tight">{{ $type }}</h4>
                                            <span class="text-[10px] font-bold text-gray-400 bg-gray-100 px-2 py-0.2 rounded-full">{{ count($methods) }} Pilihan</span>
                                        </div>

                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                                            @foreach($methods as $method)
                                                @php
                                                    $mCode = strtoupper($method['code']);
                                                    $mName = strtoupper($method['name']);
                                                    $isManual = !empty($method['is_manual']);
                                                @endphp
                                                <div onclick="selectPaymentMethodOption('{{ $method['code'] }}')" class="payment-method-card bg-white border border-gray-200 hover:border-brand-gold rounded-2xl p-3 sm:p-3.5 cursor-pointer transition-all duration-200 hover:shadow-xs flex items-center justify-between gap-3 group relative select-none" data-method-code="{{ $method['code'] }}">
                                                    <input type="radio" id="payment_method_{{ $method['code'] }}" name="payment_method" value="{{ $method['code'] }}" 
                                                        data-is-manual="{{ $isManual ? '1' : '0' }}"
                                                        data-banks='@json($method["bank_info"] ?? [])'
                                                        data-has-charge="{{ ($method['has_charge'] ?? false) ? '1' : '0' }}"
                                                        data-charge-type="{{ $method['charge_type'] ?? 2 }}"
                                                        data-charge-value="{{ $method['charge_value'] ?? 0 }}"
                                                        data-category-type="{{ $method['type_id'] ?? $type }}"
                                                        data-product-code="{{ $method['product_code'] ?? '' }}"
                                                        data-bank-code="{{ $method['bank_code'] ?? '' }}"
                                                        data-method-name="{{ $method['name'] }}"
                                                        data-method-subtitle="{{ $isManual ? 'Transfer manual & upload bukti transfer' : 'Otomatis terverifikasi instan' }}"
                                                        data-method-badge="{{ $isManual ? 'Verifikasi Manual' : 'Otomatis' }}"
                                                        class="sr-only">
                                                    
                                                    <div class="flex items-center gap-3 min-w-0 flex-1">
                                                        <!-- Official Logo / Badge -->
                                                        <div class="method-logo-badge w-12 h-9 sm:w-14 sm:h-10 flex items-center justify-center bg-gray-50 rounded-xl border border-gray-200/80 shrink-0 p-1 group-hover:border-brand-gold/50 transition-colors">
                                                            @if(!empty($method['image']))
                                                                <img src="{{ cms_asset($method['image']) }}" alt="{{ $method['name'] }}" class="max-h-6 max-w-10 sm:max-w-12 object-contain">
                                                            @elseif(str_contains($mCode, 'BCA') || str_contains($mName, 'BCA'))
                                                                <span class="px-1.5 py-0.5 rounded bg-[#00529C] text-white font-black text-[10px] tracking-wider">BCA</span>
                                                            @elseif(str_contains($mCode, 'BRI') || str_contains($mName, 'BRI'))
                                                                <span class="px-1.5 py-0.5 rounded bg-[#00529C] text-white font-black text-[10px] tracking-wider">BRI</span>
                                                            @elseif(str_contains($mCode, 'MANDIRI') || str_contains($mName, 'MANDIRI'))
                                                                <span class="px-1 py-0.5 rounded bg-[#003d79] text-[#FFB700] font-black text-[9px] tracking-wider">MANDIRI</span>
                                                            @elseif(str_contains($mCode, 'CIMB') || str_contains($mName, 'CIMB'))
                                                                <span class="px-1.5 py-0.5 rounded bg-[#800000] text-white font-black text-[10px] tracking-wider">CIMB</span>
                                                            @elseif(str_contains($mCode, 'DANAMON') || str_contains($mName, 'DANAMON'))
                                                                <span class="px-1 py-0.5 rounded bg-[#F15A24] text-white font-black text-[9px] tracking-wider">DANAMON</span>
                                                            @elseif(str_contains($mCode, 'SAQU') || str_contains($mName, 'SAQU'))
                                                                <span class="px-1.5 py-0.5 rounded bg-[#008080] text-white font-black text-[10px] tracking-wider">SAQU</span>
                                                            @elseif(str_contains($mCode, 'BII') || str_contains($mName, 'BII') || str_contains($mName, 'MAYBANK'))
                                                                <span class="px-1 py-0.5 rounded bg-[#FFCC00] text-black font-black text-[9px] tracking-wider">MAYBANK</span>
                                                            @elseif(str_contains($mCode, 'GOPAY') || str_contains($mName, 'GOPAY'))
                                                                <span class="px-1.5 py-0.5 rounded bg-[#00AED6] text-white font-black text-[9px] tracking-wider">GoPay</span>
                                                            @elseif(str_contains($mCode, 'OVO') || str_contains($mName, 'OVO'))
                                                                <span class="px-1.5 py-0.5 rounded bg-[#4C3299] text-white font-black text-[10px] tracking-wider">OVO</span>
                                                            @elseif(str_contains($mCode, 'QRIS') || str_contains($mName, 'QRIS'))
                                                                <span class="px-1.5 py-0.5 rounded bg-[#ED1C24] text-white font-black text-[9px] tracking-wider">QRIS</span>
                                                            @elseif(str_contains($mCode, 'DEBIT') || str_contains($mName, 'DEBIT'))
                                                                <span class="px-1.5 py-0.5 rounded bg-[#008080] text-white font-black text-[9px] tracking-wider">DEBIT</span>
                                                            @elseif(str_contains($mCode, 'CREDIT') || str_contains($mName, 'CREDIT') || str_contains($mName, 'CARD'))
                                                                <div class="flex items-center gap-0.5">
                                                                    <i class="fa-brands fa-cc-visa text-blue-700 text-sm"></i>
                                                                    <i class="fa-brands fa-cc-mastercard text-rose-500 text-sm"></i>
                                                                </div>
                                                            @elseif(!empty($method['is_manual']))
                                                                <span class="px-1.5 py-0.5 rounded bg-amber-100 text-amber-800 font-bold text-[9px]">TRF</span>
                                                            @else
                                                                <i class="fa-solid fa-building-columns text-brand-dark text-sm"></i>
                                                            @endif
                                                        </div>

                                                        <!-- Method Details -->
                                                        <div class="min-w-0 flex-1">
                                                            <div class="font-bold text-xs sm:text-sm text-brand-dark truncate">{{ $method['name'] }}</div>
                                                            <div class="flex items-center gap-1.5 mt-0.5 flex-wrap">
                                                                @if($isManual)
                                                                    <span class="text-[9px] text-amber-700 bg-amber-50 px-1.5 py-0.2 rounded font-medium">Manual</span>
                                                                @else
                                                                    <span class="text-[9px] text-emerald-700 bg-emerald-50 px-1.5 py-0.2 rounded font-medium">Instan</span>
                                                                @endif
                                                                @if(($method['has_charge'] ?? false) && ($method['charge_value'] ?? 0) > 0)
                                                                    <span class="text-[9px] text-gray-500 bg-gray-100 px-1.5 py-0.2 rounded">
                                                                        +{{ ($method['charge_type'] ?? 2) == 1 ? $method['charge_value'].'%' : 'Rp '.number_format($method['charge_value'], 0, ',', '.') }}
                                                                    </span>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <!-- Radio Checkmark Indicator -->
                                                    <div class="method-radio-indicator w-5 h-5 rounded-full border-2 border-gray-300 flex items-center justify-center shrink-0 transition-all">
                                                        <div class="method-radio-dot w-2.5 h-2.5 rounded-full bg-brand-gold opacity-0 transition-opacity"></div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            <!-- Modal Footer -->
                            <div class="px-5 sm:px-6 py-3.5 bg-gray-50 border-t border-gray-100 flex items-center justify-between gap-3 text-xs shrink-0">
                                <div class="flex items-center gap-1.5 text-gray-500 text-[11px]">
                                    <i class="fa-solid fa-shield-check text-emerald-600 text-xs"></i>
                                    <span>Terenkripsi &amp; Berlisensi Bank Indonesia</span>
                                </div>
                                <button type="button" onclick="closePaymentMethodModal()" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-xl font-bold text-xs transition-colors cursor-pointer">
                                    Tutup
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div id="transfer-manual-details" data-order-total="{{ $orderData['total'] }}" class="mt-4 p-5 border border-brand-gold/40 bg-amber-50/40 rounded-2xl hidden transition-all">
                        <div class="flex items-center gap-2 mb-3">
                            <i class="fa-solid fa-circle-info text-amber-600 text-sm"></i>
                            <h4 class="font-bold text-brand-dark text-sm sm:text-base">Instruksi Transfer Bank Manual:</h4>
                        </div>
                        <div class="text-sm text-gray-700 space-y-4 mb-4">
                            <p class="text-xs text-gray-600 leading-relaxed">Silakan melakukan transfer sesuai nominal tepat ke salah satu rekening bank resmi kami:</p>
                            <div id="instructions-banks-container" class="space-y-3">
                                <!-- Dynamic bank cards will be inserted here -->
                            </div>
                            <div class="mt-4 p-4.5 bg-white rounded-xl border border-gray-200/80 shadow-2xs">
                                <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1.5">Upload Bukti Pembayaran <span class="text-[11px] font-normal text-gray-400 capitalize">(Opsional / Dapat diunggah nanti)</span></label>
                                <input type="file" id="payment_proof" name="payment_proof" accept="image/*" class="w-full text-xs text-gray-500 file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-brand-gold/15 file:text-brand-dark hover:file:bg-brand-gold/25 transition-all">
                                <p class="text-[11px] text-gray-400 mt-1">Format: JPG, PNG, atau WEBP. Maks 5MB.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- 5. TOMBOL BAYAR SEKARANG (PALING BAWAH) --}}
            <div class="bg-white border border-brand-muted/80 rounded-2xl p-6 sm:p-7 shadow-sm hover:shadow-md transition-shadow">
                <button 
                    type="button"
                    onclick="processPayment()"
                    class="w-full py-4 bg-brand-dark hover:bg-brand-darker text-brand-gold hover:text-white rounded-xl font-bold text-base tracking-wide uppercase transition-all duration-200 shadow-md hover:shadow-lg flex justify-center items-center gap-2.5 group cursor-pointer"
                >
                    <i class="fa-solid fa-lock text-sm"></i>
                    <span>Bayar Sekarang</span>
                </button>
                <div class="flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-gray-500 mt-4 pt-3 border-t border-gray-100">
                    <a href="{{ route('checkout') }}" class="font-semibold text-brand-gold-dark hover:text-brand-dark transition-colors flex items-center gap-1.5">
                        <i class="fa-solid fa-arrow-left text-[10px]"></i> Ubah Data Checkout / Alamat Pengiriman
                    </a>
                    <div class="flex items-center gap-2 text-emerald-600 font-medium">
                        <i class="fa-solid fa-shield-check"></i>
                        <span>Jaminan Transaksi Aman & Terenkripsi</span>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- Espay Snap Payment Modal Dialog -->
    <div id="espay-snap-modal" class="fixed inset-0 z-50 hidden bg-black/75 backdrop-blur-xs flex items-center justify-center p-2 sm:p-4 transition-all duration-300">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl overflow-hidden border border-gray-100 flex flex-col max-h-[95vh] relative">
            <!-- Modal Header -->
            <div class="px-5 py-4 bg-brand-dark text-white flex items-center justify-between border-b border-gray-800">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-xl bg-brand-gold/20 flex items-center justify-center text-brand-gold">
                        <i class="fa-solid fa-shield-halved text-sm"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-sm sm:text-base tracking-tight text-white flex items-center gap-2">
                            <span>Pembayaran Aman</span>
                            <span class="text-[10px] font-semibold bg-brand-gold/20 text-brand-gold border border-brand-gold/30 px-2 py-0.5 rounded-full">Espay Snap</span>
                        </h3>
                        <p class="text-xs text-gray-400">Selesaikan transaksi Anda melalui panel di bawah ini</p>
                    </div>
                </div>
                <button type="button" id="close-espay-modal-btn" class="w-8 h-8 rounded-full bg-white/10 hover:bg-white/20 text-gray-300 hover:text-white flex items-center justify-center transition-colors cursor-pointer" title="Tutup">
                    <i class="fa-solid fa-xmark text-sm"></i>
                </button>
            </div>

            <!-- Iframe Container with Loader -->
            <div class="relative flex-1 w-full bg-gray-50 min-h-[480px] sm:min-h-[560px] flex items-center justify-center overflow-hidden">
                <!-- Loader inside Iframe Area -->
                <div id="espay-iframe-loader" class="absolute inset-0 flex flex-col items-center justify-center bg-white z-10 transition-opacity duration-300">
                    <div class="w-10 h-10 border-3 border-brand-gold border-t-transparent rounded-full animate-spin mb-3"></div>
                    <p class="text-sm font-semibold text-gray-700">Memuat Saluran Pembayaran...</p>
                    <p class="text-xs text-gray-400 mt-1">Mohon tunggu, jangan tutup halaman ini</p>
                </div>

                <!-- Iframe for Espay Snap / SGO Plus -->
                <iframe id="sgoplus-iframe" name="sgoplus-iframe" class="w-full h-[500px] sm:h-[580px] border-0 z-20 relative" src="" allowfullscreen allow="payment"></iframe>
            </div>

            <!-- Modal Footer -->
            <div class="px-5 py-3.5 bg-gray-50 border-t border-gray-100 flex flex-col sm:flex-row items-center justify-between gap-2.5 text-xs">
                <div class="flex items-center gap-1.5 text-gray-500 text-[11px]">
                    <i class="fa-solid fa-lock text-emerald-600 text-xs"></i>
                    <span>Terenkripsi 256-bit SSL & Berlisensi Bank Indonesia</span>
                </div>
                <div class="flex items-center gap-3">
                    <a id="espay-manual-redirect-btn" href="#" class="font-bold text-brand-gold-dark hover:text-brand-dark hover:underline flex items-center gap-1 text-xs">
                        <span>Lihat Rincian Pesanan</span>
                        <i class="fa-solid fa-arrow-right text-[10px]"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="{{ config('espay.js_url', 'https://sandbox-kit.espay.id/public/signature/js') }}"></script>
    <script src="{{ asset('js/frontend/payment.js') }}?v={{ filemtime(public_path('js/frontend/payment.js')) }}"></script>
@endsection