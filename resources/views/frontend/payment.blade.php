@extends('frontend.layouts.app')

@section('title', 'Pemilihan Pembayaran - IMG')
@section('robots', 'noindex,nofollow')

@section('content')
    <div class="container mx-auto px-4 md:px-6 py-12 min-h-[60vh] font-sans" data-route-thankyou="{{ route('thankyou') }}">
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
                                        <input type="radio" name="payment_method" value="{{ $method['code'] }}" class="w-4 h-4 text-brand-gold">
                                        <div class="flex items-center gap-2">
                                            <i class="fa-solid fa-building w-5 h-5 text-brand-dark"></i>
                                            <span class="font-medium">{{ $method['name'] }}</span>
                                        </div>
                                    </label>
                                @endforeach
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

                <div class="bg-white border border-brand-muted rounded-2xl p-6">
                    <h2 class="font-bold text-brand-dark mb-4">Detail Pesanan</h2>
                    <div class="space-y-3">
                        <div class="flex justify-between items-center py-2 border-b">
                            <span class="text-gray-700">Subtotal</span>
                            <span class="font-semibold">Rp {{ number_format($orderData['subtotal'] ?? 0, 0, ',', '.') }}</span>
                        </div>
                        <div class="flex justify-between items-center py-2 border-b">
                            <span class="text-gray-700">Ongkos Kirim</span>
                            <span class="font-semibold">Rp {{ number_format($orderData['shipping_cost'] ?? 0, 0, ',', '.') }}</span>
                        </div>
                        <div class="flex justify-between items-center py-2 border-b">
                            <span class="text-gray-700">Diskon</span>
                            <span class="font-semibold text-red-600">- Rp {{ number_format($orderData['total_discount'] ?? 0, 0, ',', '.') }}</span>
                        </div>
                        
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
                    <button 
                        type="button"
                        onclick="processPayment()"
                        class="w-full py-4 bg-brand-dark text-brand-gold rounded-xl font-bold text-lg hover:bg-brand-darker transition-colors mb-4"
                    >
                        Bayar Sekarang
                    </button>
                    
                    @php $orderId = $orderData['id'] ?? null; @endphp
                    @if($orderId)
                        <form method="POST" action="{{ route('order.cancel', $orderId) }}" onsubmit="return confirm('Batalkan order ini?');">
                            @csrf
                            <button type="submit" class="w-full py-2 text-center text-red-600 hover:text-red-700 transition-colors text-sm">
                                Batalkan Order
                            </button>
                        </form>
                    @endif
                    
                    <a href="{{ route('checkout') }}" class="w-full py-2 text-center text-gray-500 hover:text-brand-dark transition-colors text-sm block mt-2">
                        Kembali ke Checkout
                    </a>
                </div>
            </div>
        </div>
    </div>
@endsection