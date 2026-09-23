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

                <!-- Step 2: Pengiriman (Completed) -->
                <a href="{{ route('checkout') }}" class="relative z-10 flex flex-col items-center group cursor-pointer" title="Kembali ke Pengiriman">
                    <div class="w-10 h-10 rounded-full bg-brand-gold text-white flex items-center justify-center font-bold text-sm shadow-md transition-transform group-hover:scale-110">
                        <i class="fa-solid fa-check"></i>
                    </div>
                    <span class="text-xs font-semibold text-brand-dark mt-2 tracking-tight">Pengiriman</span>
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

        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
            <div>
                <h1 class="text-2xl md:text-3xl font-extrabold text-brand-dark font-serif tracking-tight">Metode Pembayaran</h1>
                <p class="text-xs md:text-sm text-gray-500 mt-1">Pilih metode pembayaran yang paling nyaman untuk menyelesaikan transaksi</p>
            </div>
            <a href="{{ route('checkout') }}" class="inline-flex items-center gap-2 text-xs font-semibold text-brand-gold-dark hover:text-brand-dark transition-colors">
                <i class="fa-solid fa-arrow-left"></i> Kembali ke Alamat Pengiriman
            </a>
        </div>
        
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <div class="lg:col-span-2">
                @php
                    $customer = $orderData['customer'] ?? [];
                    $customerName = $customer['name'] ?? session()->get('user', [])['name'] ?? '';
                    $customerPhone = $customer['phone'] ?? session()->get('user', [])['phone'] ?? '';
                @endphp
                @if($address || $customerName)
                    <div class="bg-white border border-brand-muted/80 rounded-2xl p-6 sm:p-7 mb-6 shadow-sm hover:shadow-md transition-shadow">
                        <div class="flex items-center justify-between gap-3 mb-4 pb-3 border-b border-gray-100">
                            <div class="flex items-center gap-2.5">
                                <div class="w-8 h-8 rounded-lg bg-brand-gold/15 text-brand-gold-dark flex items-center justify-center text-sm">
                                    <i class="fa-solid fa-location-dot"></i>
                                </div>
                                <h2 class="font-bold text-brand-dark text-base">Alamat Tujuan Pengiriman</h2>
                            </div>
                            <a href="{{ route('checkout') }}" class="text-xs font-bold text-brand-gold-dark hover:underline flex items-center gap-1">
                                <i class="fa-solid fa-pen-to-square text-[10px]"></i> Ubah
                            </a>
                        </div>
                        <div class="flex flex-col sm:flex-row sm:items-baseline justify-between gap-1 text-sm">
                            <p class="font-bold text-brand-dark">{{ $address->recipient_name ?? $customerName }} <span class="font-normal text-gray-500">({{ $address->phone ?? $customerPhone }})</span></p>
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
                        <p class="text-xs sm:text-sm text-gray-600 mt-1 leading-relaxed">{{ $address->address ?? ($customer['address'] ?? '') }}, {{ $address->subDistrict->city->name ?? '' }} {{ $address->postal_code ?? '' }}</p>
                    </div>
                @endif

                <div class="bg-white border border-brand-muted/80 rounded-2xl p-6 sm:p-7 mb-6 shadow-sm hover:shadow-md transition-shadow">
                    <div class="flex items-center gap-2.5 mb-6 pb-3.5 border-b border-gray-100">
                        <div class="w-8 h-8 rounded-lg bg-brand-gold/15 text-brand-gold-dark flex items-center justify-center text-sm">
                            <i class="fa-solid fa-wallet"></i>
                        </div>
                        <div>
                            <h2 class="font-bold text-brand-dark text-base">Pilih Saluran Pembayaran</h2>
                            <p class="text-xs text-gray-500">Tersedia transfer manual dan kanal pembayaran otomatis</p>
                        </div>
                    </div>
                    
                    <div class="space-y-4">
                        <!-- Validation Error Banner (Point 9) -->
                        <div id="payment-method-validation-error" class="hidden p-4 rounded-xl bg-red-50 border-2 border-red-300 text-red-800 text-xs sm:text-sm font-medium shadow-sm transition-all duration-300">
                            <div class="flex items-start gap-3">
                                <div class="w-7 h-7 rounded-full bg-red-100 flex items-center justify-center shrink-0 text-red-600 mt-0.5">
                                    <i class="fa-solid fa-circle-exclamation text-base"></i>
                                </div>
                                <div class="flex-1">
                                    <h4 class="font-bold text-red-900 text-sm mb-0.5">Metode Pembayaran Belum Dipilih</h4>
                                    <p class="text-xs text-red-700 leading-relaxed">
                                        Silakan pilih salah satu saluran pembayaran di bawah ini (Transfer Bank / E-Wallet / QRIS / Kartu Kredit) sebelum melanjutkan ke proses pembayaran.
                                    </p>
                                </div>
                            </div>
                        </div>

                        @php
                            $groupedMethods = collect($paymentMethods)->groupBy('type');
                        @endphp
                        
                        <div class="space-y-3.5 transition-all duration-300" id="payment-accordions-wrapper">
                            @foreach($groupedMethods as $type => $methods)
                                @php
                                    $typeLower = strtolower($type);
                                    $catIcon = 'fa-solid fa-building-columns';
                                    $catColor = 'bg-blue-50 text-blue-600 border border-blue-100';
                                    $catSubtitle = 'BCA, Mandiri, BRI, CIMB, Danamon, Bank Saqu, Maybank';
                                    
                                    if (str_contains($typeLower, 'transfer') || str_contains($typeLower, 'manual')) {
                                        $catIcon = 'fa-solid fa-money-bill-transfer';
                                        $catColor = 'bg-amber-50 text-amber-700 border border-amber-100';
                                        $catSubtitle = 'Transfer manual ke rekening resmi kami (verifikasi bukti transfer)';
                                    } elseif (str_contains($typeLower, 'wallet') || str_contains($typeLower, 'qris')) {
                                        $catIcon = 'fa-solid fa-wallet';
                                        $catColor = 'bg-emerald-50 text-emerald-600 border border-emerald-100';
                                        $catSubtitle = 'GoPay, OVO, QRIS Plus (Bayar instan via aplikasi)';
                                    } elseif (str_contains($typeLower, 'card') || str_contains($typeLower, 'kartu')) {
                                        $catIcon = 'fa-regular fa-credit-card';
                                        $catColor = 'bg-purple-50 text-purple-600 border border-purple-100';
                                        $catSubtitle = 'Visa, MasterCard, JCB dengan proteksi 3D Secure';
                                    }
                                @endphp
                                <div class="payment-accordion-group bg-white border border-gray-200 rounded-2xl overflow-hidden transition-all duration-200 shadow-2xs hover:border-brand-gold/70" data-group-type="{{ $type }}">
                                    <!-- Header (Shopee Accordion Dropdown) -->
                                    <div class="payment-accordion-header px-4 sm:px-5 py-3.5 sm:py-4 flex items-center justify-between cursor-pointer select-none bg-white hover:bg-gray-50/80 transition-colors">
                                        <div class="flex items-center gap-3 sm:gap-3.5 min-w-0">
                                            <div class="w-10 h-10 rounded-xl {{ $catColor }} flex items-center justify-center text-base shrink-0 shadow-2xs">
                                                <i class="{{ $catIcon }}"></i>
                                            </div>
                                            <div class="min-w-0">
                                                <div class="flex items-center gap-2 flex-wrap">
                                                    <span class="font-extrabold text-sm sm:text-base text-brand-dark tracking-tight">{{ $type }}</span>
                                                    <span class="text-[11px] font-bold text-gray-500 bg-gray-100 px-2 py-0.5 rounded-full">{{ count($methods) }} Pilihan</span>
                                                </div>
                                                <p class="text-xs text-gray-500 truncate mt-0.5">{{ $catSubtitle }}</p>
                                            </div>
                                        </div>
                                        
                                        <div class="flex items-center gap-2.5 sm:gap-3 shrink-0 ml-2">
                                            <!-- Selected Method Badge on Header -->
                                            <span class="category-selected-badge hidden text-xs font-bold text-brand-gold-dark bg-brand-gold/15 border border-brand-gold/30 px-2.5 py-1 rounded-lg items-center gap-1.5 shadow-2xs transition-all">
                                                <i class="fa-solid fa-circle-check text-brand-gold text-xs"></i>
                                                <span class="badge-text truncate max-w-[110px] sm:max-w-[160px]"></span>
                                            </span>
                                            <div class="w-8 h-8 rounded-full bg-gray-100 flex items-center justify-center text-gray-400 accordion-chevron transition-transform duration-300">
                                                <i class="fa-solid fa-chevron-down text-xs"></i>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Accordion Content / Options -->
                                    <div class="payment-accordion-content border-t border-gray-100 divide-y divide-gray-100 hidden bg-white">
                                        @foreach($methods as $method)
                                            @php
                                                $mCode = strtoupper($method['code']);
                                                $mName = strtoupper($method['name']);
                                            @endphp
                                            <label for="payment_method_{{ $method['code'] }}" class="payment-method-label flex items-center justify-between px-4 sm:px-5 py-3.5 sm:py-4 cursor-pointer hover:bg-brand-light/30 transition-all">
                                                <input type="radio" id="payment_method_{{ $method['code'] }}" name="payment_method" value="{{ $method['code'] }}" 
                                                    data-is-manual="{{ !empty($method['is_manual']) ? '1' : '0' }}"
                                                    data-banks='@json($method["bank_info"] ?? [])'
                                                    data-has-charge="{{ ($method['has_charge'] ?? false) ? '1' : '0' }}"
                                                    data-charge-type="{{ $method['charge_type'] ?? 2 }}"
                                                    data-charge-value="{{ $method['charge_value'] ?? 0 }}"
                                                    data-category-type="{{ $type }}"
                                                    data-method-name="{{ $method['name'] }}"
                                                    class="sr-only">
                                                
                                                <div class="flex items-center gap-3 sm:gap-3.5 min-w-0 flex-1">
                                                    <!-- Official Logo / Styled Badge -->
                                                    <div class="w-14 h-9 sm:w-16 sm:h-10 flex items-center justify-center bg-gray-50 rounded-xl border border-gray-200/80 shrink-0 p-1">
                                                        @if(!empty($method['image']))
                                                            <img src="{{ cms_asset($method['image']) }}" alt="{{ $method['name'] }}" class="max-h-6 max-w-12 sm:max-w-14 object-contain">
                                                        @elseif(str_contains($mCode, 'BCA') || str_contains($mName, 'BCA'))
                                                            <span class="px-2 py-0.5 rounded-md bg-[#00529C] text-white font-black text-[11px] tracking-wider shadow-2xs">BCA</span>
                                                        @elseif(str_contains($mCode, 'BRI') || str_contains($mName, 'BRI'))
                                                            <span class="px-2 py-0.5 rounded-md bg-[#00529C] text-white font-black text-[11px] tracking-wider shadow-2xs">BRI</span>
                                                        @elseif(str_contains($mCode, 'MANDIRI') || str_contains($mName, 'MANDIRI'))
                                                            <span class="px-1.5 py-0.5 rounded-md bg-[#003d79] text-[#FFB700] font-black text-[10px] tracking-wider shadow-2xs">MANDIRI</span>
                                                        @elseif(str_contains($mCode, 'CIMB') || str_contains($mName, 'CIMB'))
                                                            <span class="px-2 py-0.5 rounded-md bg-[#800000] text-white font-black text-[11px] tracking-wider shadow-2xs">CIMB</span>
                                                        @elseif(str_contains($mCode, 'DANAMON') || str_contains($mName, 'DANAMON'))
                                                            <span class="px-1.5 py-0.5 rounded-md bg-[#F15A24] text-white font-black text-[10px] tracking-wider shadow-2xs">DANAMON</span>
                                                        @elseif(str_contains($mCode, 'SAQU') || str_contains($mName, 'SAQU'))
                                                            <span class="px-2 py-0.5 rounded-md bg-[#008080] text-white font-black text-[11px] tracking-wider shadow-2xs">SAQU</span>
                                                        @elseif(str_contains($mCode, 'BII') || str_contains($mName, 'BII') || str_contains($mName, 'MAYBANK'))
                                                            <span class="px-1.5 py-0.5 rounded-md bg-[#FFCC00] text-black font-black text-[10px] tracking-wider shadow-2xs">MAYBANK</span>
                                                        @elseif(str_contains($mCode, 'GOPAY') || str_contains($mName, 'GOPAY'))
                                                            <span class="px-2 py-0.5 rounded-md bg-[#00AED6] text-white font-black text-[10px] tracking-wider flex items-center gap-1 shadow-2xs">
                                                                <i class="fa-solid fa-wallet text-[9px]"></i> GoPay
                                                            </span>
                                                        @elseif(str_contains($mCode, 'OVO') || str_contains($mName, 'OVO'))
                                                            <span class="px-2 py-0.5 rounded-md bg-[#4C3299] text-white font-black text-[11px] tracking-wider shadow-2xs">OVO</span>
                                                        @elseif(str_contains($mCode, 'QRIS') || str_contains($mName, 'QRIS'))
                                                            <span class="px-2 py-0.5 rounded-md bg-[#ED1C24] text-white font-black text-[10px] tracking-wider flex items-center gap-1 shadow-2xs">
                                                                <i class="fa-solid fa-qrcode text-[9px]"></i> QRIS
                                                            </span>
                                                        @elseif(str_contains($mCode, 'CREDIT') || str_contains($mName, 'CREDIT') || str_contains($mName, 'CARD'))
                                                            <div class="flex items-center gap-1">
                                                                <i class="fa-brands fa-cc-visa text-blue-700 text-base"></i>
                                                                <i class="fa-brands fa-cc-mastercard text-rose-500 text-base"></i>
                                                                <i class="fa-brands fa-cc-jcb text-emerald-600 text-base"></i>
                                                            </div>
                                                        @elseif(!empty($method['is_manual']))
                                                            <span class="px-1.5 py-0.5 rounded-md bg-amber-100 text-amber-800 font-bold text-[10px] flex items-center gap-0.5">
                                                                <i class="fa-solid fa-building-columns text-[10px]"></i> TRF
                                                            </span>
                                                        @else
                                                            <i class="fa-solid fa-building-columns text-brand-dark text-base"></i>
                                                        @endif
                                                    </div>
                                                    
                                                    <!-- Name & Information -->
                                                    <div class="min-w-0 flex-1">
                                                        <div class="flex items-center gap-2 flex-wrap">
                                                            <span class="font-bold text-sm text-brand-dark truncate">{{ $method['name'] }}</span>
                                                            @if(!empty($method['is_manual']))
                                                                <span class="text-[10px] text-amber-700 bg-amber-50 border border-amber-200/80 px-1.5 py-0.2 rounded font-bold">Verifikasi Manual</span>
                                                            @else
                                                                <span class="text-[10px] text-emerald-700 bg-emerald-50 border border-emerald-200/80 px-1.5 py-0.2 rounded font-bold inline-flex items-center gap-0.5">
                                                                    <i class="fa-solid fa-bolt text-[8px]"></i> Otomatis
                                                                </span>
                                                            @endif
                                                            @if(($method['has_charge'] ?? false) && ($method['charge_value'] ?? 0) > 0)
                                                                <span class="text-[10px] text-gray-500 bg-gray-100 px-1.5 py-0.2 rounded font-medium">
                                                                    +Biaya {{ ($method['charge_type'] ?? 2) == 1 ? $method['charge_value'].'%' : 'Rp '.number_format($method['charge_value'], 0, ',', '.') }}
                                                                </span>
                                                            @endif
                                                        </div>
                                                        <p class="text-[11px] text-gray-400 mt-0.5 truncate">
                                                            {{ !empty($method['is_manual']) ? 'Upload bukti transfer setelah pembayaran dilakukan' : 'Pembayaran terverifikasi instan tanpa bukti transfer' }}
                                                        </p>
                                                    </div>
                                                </div>
                                                
                                                <!-- Custom Radio Circle Checkmark -->
                                                <div class="w-5 h-5 rounded-full border-2 border-gray-300 flex items-center justify-center transition-all shadow-2xs shrink-0 ml-3">
                                                    <svg class="w-3 h-3 text-white opacity-0 transition-opacity duration-200" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                                                        <polyline points="20 6 9 17 4 12"></polyline>
                                                    </svg>
                                                </div>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
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

                @if(!empty($orderData['items']))
                    <div class="bg-white border border-brand-muted/80 rounded-2xl p-6 sm:p-7 mb-6 shadow-sm hover:shadow-md transition-shadow">
                        <div class="flex items-center justify-between mb-4 pb-3 border-b border-gray-100">
                            <div class="flex items-center gap-2.5">
                                <div class="w-8 h-8 rounded-lg bg-brand-gold/15 text-brand-gold-dark flex items-center justify-center text-sm">
                                    <i class="fa-solid fa-bag-shopping"></i>
                                </div>
                                <h2 class="font-bold text-brand-dark text-base">Produk yang Dipesan</h2>
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

                <div class="bg-white border border-brand-muted/80 rounded-2xl p-6 sm:p-7 shadow-sm hover:shadow-md transition-shadow">
                    <div class="flex items-center gap-2.5 mb-4 pb-3 border-b border-gray-100">
                        <div class="w-8 h-8 rounded-lg bg-brand-gold/15 text-brand-gold-dark flex items-center justify-center text-sm">
                            <i class="fa-solid fa-receipt"></i>
                        </div>
                        <h2 class="font-bold text-brand-dark text-base">Rincian Perhitungan Pesanan</h2>
                    </div>
                    @php
                        $items = $orderData['items'] ?? [];
                        $originalSubtotal = collect($items)->sum(fn($i) => ($i['sell_price'] ?? 0) * ($i['quantity'] ?? 0));
                        
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

                        $discountedSubtotal = max(0, $originalSubtotal - $totalPercentDiscount - $totalNominalDiscount);
                    @endphp
                    <div class="space-y-3 text-sm">
                        @if($totalPercentDiscount > 0 || $totalNominalDiscount > 0)
                            <div class="flex justify-between items-center text-gray-600">
                                <span>Subtotal Awal</span>
                                <span class="font-medium line-through text-gray-400">Rp {{ number_format($originalSubtotal, 0, ',', '.') }}</span>
                            </div>
                            <div class="flex justify-between items-center text-gray-600">
                                <span>Harga Setelah Diskon Item</span>
                                <span class="font-bold text-brand-dark">Rp {{ number_format($discountedSubtotal, 0, ',', '.') }}</span>
                            </div>
                        @else
                            <div class="flex justify-between items-center text-gray-600">
                                <span>Subtotal</span>
                                <span class="font-bold text-brand-dark">Rp {{ number_format($originalSubtotal, 0, ',', '.') }}</span>
                            </div>
                        @endif
                        
                        @if($totalPercentDiscount > 0)
                            <div class="flex justify-between items-center text-red-600">
                                <span class="text-gray-600 flex items-center gap-1.5"><i class="fa-solid fa-tag text-xs text-red-500"></i> Diskon Persen</span>
                                <span class="font-bold">- Rp {{ number_format($totalPercentDiscount, 0, ',', '.') }}</span>
                            </div>
                        @endif

                        @if($totalNominalDiscount > 0)
                            <div class="flex justify-between items-center text-red-600">
                                <span class="text-gray-600 flex items-center gap-1.5"><i class="fa-solid fa-tag text-xs text-red-500"></i> Diskon Nominal</span>
                                <span class="font-bold">- Rp {{ number_format($totalNominalDiscount, 0, ',', '.') }}</span>
                            </div>
                        @endif

                        <div class="flex justify-between items-center text-gray-600">
                            <span>Shipping ({{ strtoupper($orderData['courier'] ?? 'Kurir') }})</span>
                            <span class="font-bold text-brand-dark">Rp {{ number_format($orderData['shipping_cost'] ?? 0, 0, ',', '.') }}</span>
                        </div>
                        @if(!empty($orderData['eta_label']))
                            <div class="flex justify-between items-center text-xs text-blue-700 bg-blue-50/60 px-2.5 py-1.5 rounded-lg">
                                <span class="flex items-center gap-1.5"><i class="fa-solid fa-clock text-[11px] text-blue-500"></i> Estimasi Tiba</span>
                                <span class="font-semibold">{{ $orderData['eta_label'] }}</span>
                            </div>
                        @endif

                        @if(($orderData['voucher_discount'] ?? 0) > 0)
                            <div class="flex justify-between items-center text-red-600">
                                <span class="text-gray-600 flex items-center gap-1.5"><i class="fa-solid fa-ticket text-xs text-red-500"></i> Voucher ({{ $orderData['voucher_code'] ?? 'Kupon' }})</span>
                                <span class="font-bold">- Rp {{ number_format($orderData['voucher_discount'], 0, ',', '.') }}</span>
                            </div>
                        @endif
                        
                        <div id="charge-row" class="flex justify-between items-center text-gray-600 hidden">
                            <span>Biaya Tambahan / Layanan</span>
                            <span id="charge-amount" class="font-bold text-brand-dark">Rp 0</span>
                        </div>
                        
                        <div class="flex justify-between items-baseline pt-4 border-t border-dashed border-gray-200">
                            <span class="font-bold text-base text-gray-800">Total Pembayaran</span>
                            <span id="final-total" class="font-black text-2xl text-brand-dark font-serif" data-base-total="{{ $orderData['total'] ?? 0 }}">Rp {{ number_format($orderData['total'] ?? 0, 0, ',', '.') }}</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="lg:col-span-1">
                <div class="bg-white border border-brand-muted/80 rounded-2xl p-6 shadow-sm sticky top-6">
                    <div class="flex items-center gap-2 mb-4 pb-3 border-b border-gray-100">
                        <i class="fa-solid fa-shield-halved text-brand-gold"></i>
                        <h3 class="font-bold text-brand-dark text-base">Konfirmasi Pembayaran</h3>
                    </div>

                    <div id="payment-button-container">
                        <button 
                            type="button"
                            onclick="processPayment()"
                            class="w-full py-4 bg-brand-dark hover:bg-brand-darker text-brand-gold hover:text-white rounded-xl font-bold text-base tracking-wide uppercase transition-all duration-200 shadow-md hover:shadow-lg flex justify-center items-center gap-2.5 group cursor-pointer mb-3"
                        >
                            <i class="fa-solid fa-lock text-xs"></i>
                            <span>Bayar Sekarang</span>
                        </button>
                    </div>
                    
                    <a href="{{ route('checkout') }}" class="w-full py-2.5 text-center text-gray-500 hover:text-brand-dark hover:bg-gray-50 rounded-xl transition-all text-xs font-semibold block">
                        <i class="fa-solid fa-arrow-left text-[10px] mr-1"></i> Ubah Data Checkout
                    </a>

                    <!-- Security Badges -->
                    <div class="mt-6 pt-5 border-t border-gray-100 space-y-2.5 text-xs text-gray-500">
                        <div class="flex items-center gap-2.5">
                            <div class="w-6 h-6 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                                <i class="fa-solid fa-shield-check text-[11px]"></i>
                            </div>
                            <span>Jaminan Transaksi Aman & Terenkripsi</span>
                        </div>
                        <div class="flex items-center gap-2.5">
                            <div class="w-6 h-6 rounded-full bg-brand-gold/15 text-brand-gold-dark flex items-center justify-center shrink-0">
                                <i class="fa-solid fa-bolt text-[11px]"></i>
                            </div>
                            <span>Proses Cepat & Otomatis</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="{{ asset('js/frontend/payment.js') }}?v={{ filemtime(public_path('js/frontend/payment.js')) }}"></script>
@endsection