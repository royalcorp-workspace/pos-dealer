@extends('frontend.layouts.app')

@section('title', 'Pemilihan Pembayaran - IMG')
@section('robots', 'noindex,nofollow')

@section('content')
    <div class="container mx-auto px-4 md:px-6 py-12 min-h-[60vh] font-sans" id="payment-container" data-route-thankyou="{{ route('thankyou') }}" data-route-payment-process="{{ route('payment.process') }}">
        <h1 class="text-3xl font-extrabold text-brand-dark mb-8 font-serif">Pemilihan Pembayaran</h1>
        
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <div class="lg:col-span-2">
                @php
                    $customer = $orderData['customer'] ?? [];
                    $customerName = $customer['name'] ?? session()->get('user', [])['name'] ?? '';
                    $customerPhone = $customer['phone'] ?? session()->get('user', [])['phone'] ?? '';
                @endphp
                @if($address || $customerName)
                    <div class="bg-white border border-brand-muted rounded-2xl p-6 mb-6">
                        <h2 class="font-bold text-brand-dark mb-4">Alamat Pengiriman</h2>
                        <p class="text-gray-700">{{ $address->recipient_name ?? $customerName }}</p>
                        <p class="text-gray-500">{{ $address->phone ?? $customerPhone }}</p>
                        <p class="text-gray-500">{{ $address->address ?? ($customer['address'] ?? '') }}, {{ $address->subDistrict->city->name ?? '' }} {{ $address->postal_code ?? '' }}</p>
                    </div>
                @endif

                <div class="bg-white border border-brand-muted rounded-2xl p-6 mb-6">
                    <h2 class="font-bold text-brand-dark mb-4">Metode Pembayaran</h2>
                    
                    <div class="space-y-6">
                        <div>
                            <h3 class="text-sm font-semibold text-gray-500 mb-3 uppercase tracking-wider">Transfer Bank</h3>
                            <div class="grid grid-cols-2 gap-3">
                                @foreach(array_filter($paymentMethods, fn($m) => $m['type'] === 'transfer') as $method)
                                    <label class="flex items-center gap-3 p-4 border border-brand-muted rounded-xl cursor-pointer hover:border-brand-gold transition-colors">
                                        <input type="radio" name="payment_method" value="{{ $method['code'] }}" 
                                               data-is-manual="{{ $method['code'] === 'transfer_manual' ? '1' : '0' }}"
                                               data-banks='@json($method["bank_info"] ?? [])'
                                               class="w-4 h-4 text-brand-gold">
                                        <div class="flex items-center gap-2">
                                            <i class="fa-solid fa-building w-5 h-5 text-brand-dark"></i>
                                            <span class="font-medium">{{ $method['name'] }}</span>
                                        </div>
                                    </label>
                                @endforeach
                            </div>
                            
                            <div id="transfer-manual-details" data-order-total="{{ $orderData['total'] }}" class="mt-4 p-5 border border-brand-gold/40 bg-amber-50/30 rounded-2xl hidden transition-all">
                                <h4 class="font-bold text-brand-dark mb-3">Instruksi Transfer Bank Manual:</h4>
                                <div class="text-sm text-gray-700 space-y-4 mb-4">
                                    <p>Silakan melakukan pembayaran ke salah satu rekening berikut:</p>
                                    <div id="instructions-banks-container" class="space-y-4">
                                        <!-- Dynamic bank cards will be inserted here -->
                                    </div>
                                    <p class="text-xs text-red-500 italic mt-2">*Harap pastikan nominal transfer sama persis dengan Total Transfer di atas agar proses verifikasi berjalan lancar.</p>
                                </div>
                                
                                <div class="space-y-2">
                                    <label class="block text-sm font-bold text-brand-dark">Upload Bukti Transfer <span class="text-red-500">*</span></label>
                                    <input type="file" id="payment_proof" name="payment_proof" accept="image/*" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-bold file:bg-brand-dark file:text-brand-gold hover:file:bg-brand-darker cursor-pointer border border-brand-muted rounded-xl p-2 bg-white">
                                    <p class="text-xs text-gray-400">Format yang diterima: JPG, JPEG, PNG. Ukuran maksimal: 5MB.</p>
                                </div>
                            </div>
                        </div>

                        <div>
                            <h3 class="text-sm font-semibold text-gray-500 mb-3 uppercase tracking-wider">E-Wallet</h3>
                            <div class="grid grid-cols-2 gap-3">
                                @foreach(array_filter($paymentMethods, fn($m) => $m['type'] === 'ewallet') as $method)
                                    <label class="flex items-center gap-3 p-4 border border-brand-muted rounded-xl cursor-pointer hover:border-brand-gold transition-colors">
                                        <input type="radio" name="payment_method" value="{{ $method['code'] }}" class="w-4 h-4 text-brand-gold">
                                        <div class="flex items-center gap-2">
                                            <i class="fa-solid fa-wallet w-5 h-5 text-brand-dark"></i>
                                            <span class="font-medium">{{ $method['name'] }}</span>
                                        </div>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>

                @if(!empty($orderData['items']))
                    <div class="bg-white border border-brand-muted rounded-2xl p-6 mb-6">
                        <h2 class="font-bold text-brand-dark mb-4">Produk yang Dipesan</h2>
                        <div class="space-y-3">
                            @foreach($orderData['items'] as $item)
                                <div class="flex justify-between items-start py-3 border-b">
                                    <div class="flex-1">
                                        <p class="font-medium text-gray-700">{{ $item['name'] }}</p>
                                        <p class="text-xs text-gray-500">Qty: {{ $item['quantity'] }}</p>
                                    </div>
                                    <span class="font-semibold ml-4">Rp {{ number_format($item['price'] * $item['quantity'], 0, ',', '.') }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <div class="bg-white border border-brand-muted rounded-2xl p-6">
                    <h2 class="font-bold text-brand-dark mb-4">Detail Pesanan</h2>
                    @php
                        $items = $orderData['items'] ?? [];
                        $originalSubtotal = collect($items)->sum(fn($i) => ($i['price'] ?? 0) * ($i['quantity'] ?? 0));
                        
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
                    <div class="space-y-3">
                        @if($totalPercentDiscount > 0 || $totalNominalDiscount > 0)
                            <div class="flex justify-between items-center py-2 border-b">
                                <span class="text-gray-700">Subtotal</span>
                                <span class="font-semibold line-through text-gray-400">Rp {{ number_format($originalSubtotal, 0, ',', '.') }}</span>
                            </div>
                            <div class="flex justify-between items-center py-2 border-b">
                                <span class="text-gray-700">Harga</span>
                                <span class="font-semibold text-brand-dark">Rp {{ number_format($discountedSubtotal, 0, ',', '.') }}</span>
                            </div>
                        @else
                            <div class="flex justify-between items-center py-2 border-b">
                                <span class="text-gray-700">Subtotal</span>
                                <span class="font-semibold text-brand-dark">Rp {{ number_format($originalSubtotal, 0, ',', '.') }}</span>
                            </div>
                        @endif
                        
                        @if($totalPercentDiscount > 0)
                            <div class="flex justify-between items-center py-2 border-b text-red-600">
                                <span class="text-gray-700">Diskon Persen</span>
                                <span class="font-semibold">- Rp {{ number_format($totalPercentDiscount, 0, ',', '.') }}</span>
                            </div>
                        @endif

                        @if($totalNominalDiscount > 0)
                            <div class="flex justify-between items-center py-2 border-b text-red-600">
                                <span class="text-gray-700">Diskon Nominal</span>
                                <span class="font-semibold">- Rp {{ number_format($totalNominalDiscount, 0, ',', '.') }}</span>
                            </div>
                        @endif

                        <div class="flex justify-between items-center py-2 border-b">
                            <span class="text-gray-700">Shipping ({{ strtoupper($orderData['courier'] ?? 'Kurir') }})</span>
                            <span class="font-semibold">Rp {{ number_format($orderData['shipping_cost'] ?? 0, 0, ',', '.') }}</span>
                        </div>

                        @if(($orderData['voucher_discount'] ?? 0) > 0)
                            <div class="flex justify-between items-center py-2 border-b text-red-600">
                                <span class="text-gray-700">Voucher ({{ $orderData['voucher_code'] ?? 'Kupon' }})</span>
                                <span class="font-semibold">- Rp {{ number_format($orderData['voucher_discount'], 0, ',', '.') }}</span>
                            </div>
                        @endif
                        
                        @php
                            $selectedMethod = collect($paymentMethods)->firstWhere('code', $orderData['payment_method'] ?? null);
                            $charge = 0;
                            if ($selectedMethod && !empty($selectedMethod['has_charge'])) {
                                $totalBeforeCharge = $orderData['total'] ?? 0;
                                $charge = $selectedMethod['charge_type'] ?? 1 == 1 ? ($totalBeforeCharge * $selectedMethod['charge_value'] / 100) : $selectedMethod['charge_value'];
                            }
                        @endphp
                        @if($charge > 0)
                            <div class="flex justify-between items-center py-2 border-b">
                                <span class="text-gray-700">Biaya Tambahan</span>
                                <span class="font-semibold">Rp {{ number_format($charge, 0, ',', '.') }}</span>
                            </div>
                        @endif
                        
                        <div class="flex justify-between pt-4">
                            <span class="font-bold text-lg">Total</span>
                            <span class="font-bold text-xl text-brand-dark">Rp {{ number_format($orderData['total'] + $charge, 0, ',', '.') }}</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="lg:col-span-1">
                <div class="bg-white border border-brand-muted rounded-2xl p-6 sticky top-6">
                    <div id="payment-button-container">
                        <button 
                            type="button"
                            onclick="processPayment()"
                            class="w-full py-4 bg-brand-dark text-brand-gold rounded-xl font-bold text-lg hover:bg-brand-darker transition-colors mb-4"
                        >
                            Bayar Sekarang
                        </button>
                    </div>
                    
                    <!-- Espay Embed Kit iframe placeholder -->
                    <div id="espay-iframe-container" class="hidden w-full h-[500px] border border-brand-muted rounded-xl overflow-hidden mt-4">
                        <iframe id="sgoplus-iframe" src="" scrolling="yes" frameborder="0" class="w-full h-full"></iframe>
                    </div>
                    
                    @php $orderId = $orderData['id'] ?? null; @endphp
                    @if($orderId)
                        <!-- Cancel button removed per requirement -->
                    @endif
                    
                    <a href="{{ route('checkout') }}" class="w-full py-2 text-center text-gray-500 hover:text-brand-dark transition-colors text-sm block mt-2">
                        Kembali ke Checkout
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Pass Espay config to JS -->
    <script>
        window.espayConfig = {
            key: "{{ config('espay.merchant_key') }}",
            paymentId: "{{ $orderData['id'] ?? '' }}",
            backUrl: "{{ route('thankyou') }}"
        };
    </script>
    <script type="text/javascript" src="{{ config('espay.js_url') }}"></script>
    <script src="{{ asset('js/frontend/payment.js') }}?v={{ filemtime(public_path('js/frontend/payment.js')) }}"></script>
@endsection