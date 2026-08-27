@extends('frontend.layouts.app')

@section('title', 'Pemilihan Pembayaran - IMG')
@section('robots', 'noindex,nofollow')

@section('content')
    <div class="container mx-auto px-4 md:px-6 py-12 min-h-[60vh] font-sans" id="payment-container" data-order-id="{{ $orderData['id'] ?? '' }}" data-route-thankyou="{{ route('thankyou') }}" data-route-payment-process="{{ route('payment.process') }}">
        <h1 class="text-3xl font-extrabold text-brand-dark mb-4 font-serif">Pemilihan Pembayaran</h1>
        
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
                        @php
                            $groupedMethods = collect($paymentMethods)->groupBy('type');
                        @endphp
                        
                        @foreach($groupedMethods as $type => $methods)
                            <div>
                                <h3 class="text-sm font-semibold text-gray-500 mb-3 uppercase tracking-wider">{{ $type }}</h3>
                                <div class="grid grid-cols-2 gap-3">
                                    @foreach($methods as $method)
                                        <label class="relative flex items-center gap-3 p-4 border border-brand-muted rounded-xl cursor-pointer hover:border-brand-gold transition-all duration-200 payment-method-label has-[:checked]:border-brand-gold has-[:checked]:bg-brand-gold/5">
                                            <input type="radio" name="payment_method" value="{{ $method['code'] }}" 
                                                data-is-manual="{{ $method['code'] === 'transfer_manual' ? '1' : '0' }}"
                                                data-banks='@json($method["bank_info"] ?? [])'
                                                data-has-charge="{{ ($method['has_charge'] ?? false) ? '1' : '0' }}"
                                                data-charge-type="{{ $method['charge_type'] ?? 2 }}"
                                                data-charge-value="{{ $method['charge_value'] ?? 0 }}"
                                                class="peer sr-only">
                                            
                                            <!-- Custom SVG Checkmark -->
                                            <div class="w-5 h-5 rounded-full border-2 border-gray-300 flex items-center justify-center peer-checked:border-brand-gold peer-checked:bg-brand-gold transition-colors shadow-sm">
                                                <svg class="w-3 h-3 text-white opacity-0 peer-checked:opacity-100 transition-opacity duration-200" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                                                    <polyline points="20 6 9 17 4 12"></polyline>
                                                </svg>
                                            </div>
                                            <div class="flex items-center gap-2">
                                                @if(!empty($method['image']))
                                                    <img src="{{ cms_asset($method['image']) }}" alt="{{ $method['name'] }}" class="h-6 w-auto object-contain">
                                                @else
                                                    @if(str_contains(strtolower($type), 'wallet') || str_contains(strtolower($type), 'qris'))
                                                        <i class="fa-solid fa-wallet w-5 h-5 text-brand-dark flex items-center justify-center"></i>
                                                    @elseif(str_contains(strtolower($type), 'card'))
                                                        <i class="fa-solid fa-credit-card w-5 h-5 text-brand-dark flex items-center justify-center"></i>
                                                    @else
                                                        <i class="fa-solid fa-building w-5 h-5 text-brand-dark flex items-center justify-center"></i>
                                                    @endif
                                                @endif
                                                <span class="font-medium">{{ $method['name'] }}</span>
                                            </div>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                        
                        <div id="transfer-manual-details" data-order-total="{{ $orderData['total'] }}" class="mt-4 p-5 border border-brand-gold/40 bg-amber-50/30 rounded-2xl hidden transition-all">
                            <h4 class="font-bold text-brand-dark mb-3">Instruksi Transfer Bank Manual:</h4>
                            <div class="text-sm text-gray-700 space-y-4 mb-4">
                                <p>Silakan melakukan pembayaran ke salah satu rekening berikut:</p>
                                <div id="instructions-banks-container" class="space-y-4">
                                    <!-- Dynamic bank cards will be inserted here -->
                                </div>
                                <div class="mt-4 p-4 bg-white rounded-xl border border-gray-200">
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Upload Bukti Transfer</label>
                                    <input type="file" id="payment_proof" name="payment_proof" accept="image/*" class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-semibold file:bg-brand-gold/10 file:text-brand-dark hover:file:bg-brand-gold/20">
                                </div>
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
                                    <span class="font-semibold ml-4">Rp {{ number_format($item['sell_price'] * $item['quantity'], 0, ',', '.') }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <div class="bg-white border border-brand-muted rounded-2xl p-6">
                    <h2 class="font-bold text-brand-dark mb-4">Detail Pesanan</h2>
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
                        
                        <div id="charge-row" class="flex justify-between items-center py-2 border-b hidden">
                            <span class="text-gray-700">Biaya Tambahan</span>
                            <span id="charge-amount" class="font-semibold">Rp 0</span>
                        </div>
                        
                        <div class="flex justify-between pt-4">
                            <span class="font-bold text-lg">Total</span>
                            <span id="final-total" class="font-bold text-xl text-brand-dark" data-base-total="{{ $orderData['total'] ?? 0 }}">Rp {{ number_format($orderData['total'] ?? 0, 0, ',', '.') }}</span>
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
                    
                    <!-- Placeholder untuk instruksi lain jika diperlukan -->
                    
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

    <script src="{{ asset('js/frontend/payment.js') }}?v={{ filemtime(public_path('js/frontend/payment.js')) }}"></script>
@endsection