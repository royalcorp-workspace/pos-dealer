@extends('frontend.layouts.app')

@section('title', 'Detail Pesanan - IMG')

@php
    $orderId = $order?->order_number ?? '-';
    $paymentMethod = $order?->payment_method ?? '-';
    $total = $order?->total ?? 0;
    $status = $order?->status ?? 1;
    $paymentStatus = $order?->payment_status ?? 1; // 1 = unpaid, 2 = paid
    $statusLabel = \App\Models\Frontend\Order::statusLabels()[$status] ?? 'Menunggu Pembayaran';
    $statusBadge = $order?->getStatusBadgeClassAttribute() ?? 'bg-yellow-100 text-yellow-700';
    $items = $order?->items ?? [];
    
    $isUnpaid = $paymentStatus == 1;
    $vaNumber = $order->meta['va_number'] ?? null;
    $instructions = $order->meta['payment_instructions'] ?? [];
    
    $pmModel = \App\Models\PaymentMethod::where('code', $paymentMethod)->first();
    $isBankTransfer = ($pmModel && (
        $pmModel->isTypeBankTransfer() 
        || (int)$pmModel->type === 1 
        || strtolower((string)$pmModel->provider) !== 'espay'
    )) || in_array($paymentMethod, ['transfer_manual', 'trf'], true);
    $banks = $pmModel && !empty($pmModel->bank_info) ? $pmModel->bank_info : [];
@endphp

@section('content')
    <div class="container mx-auto px-4 pt-12 pb-24">
        <h1 class="text-3xl font-extrabold text-brand-dark mb-8 font-serif text-center">Detail Pesanan Anda</h1>

        <div class="max-w-4xl mx-auto space-y-6">
            
            @if($isUnpaid)
                <!-- UNPAID SECTION -->
                <div class="bg-amber-50 border-2 border-brand-gold rounded-3xl p-6 md:p-8 text-center shadow-lg">
                    <h2 class="text-2xl font-bold text-brand-dark mb-2">Menunggu Pembayaran</h2>
                    <p class="text-gray-600 mb-6">Selesaikan pembayaran sebelum waktu habis agar pesanan dapat diproses.</p>
                    
                    <div class="inline-block bg-white px-6 py-3 rounded-2xl shadow-sm border border-brand-muted mb-8">
                        <p class="text-sm text-gray-500 font-bold mb-1 uppercase tracking-wider">Sisa Waktu Pembayaran</p>
                        <div class="text-3xl font-bold text-red-600 font-mono tracking-widest" id="order-countdown" data-created="{{ $order->created_at ? $order->created_at->toIso8601String() : now()->toIso8601String() }}">
                            --:--:--
                        </div>
                    </div>

                    @if($vaNumber)
                        <div class="bg-white rounded-2xl p-6 border border-blue-200 max-w-lg mx-auto">
                            <h4 class="font-bold text-brand-dark mb-4 text-left flex items-center gap-2">
                                <i class="fa-solid fa-building-columns text-blue-500"></i> Informasi Virtual Account
                            </h4>
                            <div class="bg-blue-50 p-4 rounded-xl border border-blue-100 mb-4">
                                <p class="text-gray-500 text-sm mb-1">Nomor Virtual Account</p>
                                <p class="font-mono font-extrabold text-blue-700 text-3xl tracking-widest select-all">{{ $vaNumber }}</p>
                            </div>
                            
                            @if(is_array($instructions) && count($instructions) > 0)
                                <div class="text-left">
                                    <p class="font-bold text-sm text-gray-700 mb-2">Cara Pembayaran:</p>
                                    <ul class="text-sm text-gray-600 space-y-1 list-disc pl-5">
                                        @foreach($instructions as $inst)
                                            <li>{{ is_array($inst) ? ($inst['title'] ?? json_encode($inst)) : $inst }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                        </div>
                    @elseif($isBankTransfer)
                        <div class="bg-white rounded-2xl p-6 border border-amber-200 max-w-lg mx-auto text-left shadow-sm">
                            <h4 class="font-bold text-brand-dark mb-4 flex items-center gap-2">
                                <i class="fa-solid fa-building-columns text-brand-gold"></i> Instruksi Transfer Bank
                            </h4>
                            @if(!empty($banks) && is_array($banks))
                                <div class="space-y-3 text-sm">
                                    @foreach($banks as $b)
                                        <div class="bg-amber-50/50 p-4 rounded-xl border border-amber-100 space-y-2">
                                            <div class="flex justify-between items-center border-b border-gray-100 pb-2">
                                                <span class="text-gray-500 text-xs">Bank</span>
                                                <span class="font-bold text-brand-dark">{{ $b['bank_name'] ?? 'BCA' }}</span>
                                            </div>
                                            <div class="flex justify-between items-center border-b border-gray-100 pb-2">
                                                <span class="text-gray-500 text-xs">No. Rekening</span>
                                                <span class="font-mono font-bold text-brand-dark text-base select-all">{{ $b['account_number'] ?? '-' }}</span>
                                            </div>
                                            <div class="flex justify-between items-center border-b border-gray-100 pb-2">
                                                <span class="text-gray-500 text-xs">Atas Nama</span>
                                                <span class="font-bold text-brand-dark">{{ $b['account_holder'] ?? '-' }}</span>
                                            </div>
                                            <div class="flex justify-between items-center">
                                                <span class="text-gray-500 text-xs">Total Transfer</span>
                                                <span class="font-extrabold text-brand-gold-dark text-base">Rp {{ number_format($total, 0, ',', '.') }}</span>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="space-y-3 text-sm">
                                    <div class="flex justify-between items-center bg-gray-50 p-3 rounded-xl border border-gray-100">
                                        <span class="text-gray-500">Bank</span>
                                        <span class="font-bold text-brand-dark">BCA</span>
                                    </div>
                                    <div class="flex justify-between items-center bg-gray-50 p-3 rounded-xl border border-gray-100">
                                        <span class="text-gray-500">No. Rekening</span>
                                        <span class="font-mono font-bold text-brand-dark text-base select-all">123-456-7890</span>
                                    </div>
                                    <div class="flex justify-between items-center bg-gray-50 p-3 rounded-xl border border-gray-100">
                                        <span class="text-gray-500">Atas Nama</span>
                                        <span class="font-bold text-brand-dark">PT IMG</span>
                                    </div>
                                    <div class="flex justify-between items-center bg-gray-50 p-3 rounded-xl border border-gray-100">
                                        <span class="text-gray-500">Total Transfer</span>
                                        <span class="font-extrabold text-brand-gold-dark text-base">Rp {{ number_format($total, 0, ',', '.') }}</span>
                                    </div>
                                </div>
                            @endif

                            @if(!empty($order->meta['payment_proof']))
                                <div class="mt-4 pt-4 border-t border-gray-100 text-xs text-green-700 font-medium flex items-center gap-2">
                                    <i class="fa-solid fa-circle-check"></i> Bukti transfer sudah diunggah dan sedang diverifikasi.
                                </div>
                            @endif
                        </div>
                    @else
                        <div class="bg-white rounded-2xl p-6 border border-brand-muted max-w-lg mx-auto text-left">
                            <p class="text-gray-700">Silakan ikuti instruksi pembayaran untuk <strong>{{ $paymentMethod }}</strong> sesuai yang telah diinformasikan.</p>
                        </div>
                    @endif
                </div>
            @endif

            <!-- ORDER SUMMARY CARD (For both Paid and Unpaid) -->
            <div class="bg-white rounded-3xl shadow-md border border-gray-100 overflow-hidden">
                <!-- Header -->
                <div class="bg-gray-50 px-6 py-4 flex flex-col md:flex-row justify-between items-start md:items-center border-b border-gray-100 gap-4">
                    <div>
                        <p class="text-xs text-gray-500 uppercase tracking-wider font-bold mb-1">ID Pesanan</p>
                        <p class="font-bold text-brand-dark text-lg font-mono">{{ $orderId }}</p>
                    </div>
                    <div class="text-right">
                        <span class="inline-flex items-center px-4 py-1.5 rounded-full text-sm font-bold {{ $statusBadge }}">
                            {{ $statusLabel }}
                        </span>
                    </div>
                </div>

                <!-- Body (Items) -->
                <div class="p-6 space-y-4">
                    <h3 class="font-bold text-brand-dark mb-4">Item Pesanan</h3>
                    @foreach($items as $item)
                        @php
                            $itemImage = $item->product?->thumbnail_url ?? null;
                        @endphp
                        <div class="flex gap-4 items-start pb-4 border-b border-gray-50 last:border-0 last:pb-0">
                            <div class="w-16 h-16 sm:w-20 sm:h-20 bg-gray-100 rounded-xl overflow-hidden flex items-center justify-center flex-shrink-0 border border-gray-100">
                                @if(!empty($itemImage))
                                    <img src="{{ $itemImage }}" alt="{{ $item->name }}" class="w-full h-full object-cover">
                                @else
                                    <i class="fa-solid fa-box text-gray-400 text-2xl"></i>
                                @endif
                            </div>
                            <div class="flex-1 min-w-0">
                                @php
                                    $itemMeta = is_array($item->meta) ? $item->meta : (json_decode($item->meta ?? '[]', true) ?: []);
                                    $unitPrice = (float) ($item->unit_price ?? $itemMeta['original_price'] ?? 0);
                                    $discNom = (float) ($item->discount_nominal ?? $itemMeta['discount_nominal'] ?? 0);
                                    $discPct = (float) ($item->discount_percent ?? $itemMeta['discount_percent'] ?? 0);
                                    $subtotalAsli = $unitPrice * (int) $item->quantity;
                                    if ($discPct > 0 && $discNom <= 0) {
                                        $discNom = ($subtotalAsli * $discPct) / 100;
                                    }
                                    if ($discNom > 0 && $discPct <= 0 && $subtotalAsli > 0) {
                                        $discPct = round(($discNom / $subtotalAsli) * 100, 1);
                                    }
                                @endphp
                                <h4 class="font-bold text-gray-800 line-clamp-2 leading-tight mb-1">{{ $item->name }}</h4>
                                <div class="flex items-center gap-2 text-xs text-gray-500 mb-1 flex-wrap">
                                    <span>{{ $item->quantity }} x Rp {{ number_format($unitPrice, 0, ',', '.') }}</span>
                                    @if($discNom > 0)
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-red-50 text-red-600 font-bold text-[11px]">
                                            <span>Diskon {{ (float) $discPct }}%</span>
                                            <span>(-Rp {{ number_format($discNom, 0, ',', '.') }})</span>
                                        </span>
                                    @endif
                                </div>
                                <div class="flex items-center gap-2">
                                    @if($discNom > 0 && $subtotalAsli > $item->total)
                                        <span class="text-xs text-gray-400 line-through">Rp {{ number_format($subtotalAsli, 0, ',', '.') }}</span>
                                    @endif
                                    <span class="font-bold text-brand-dark">
                                        Rp {{ number_format($item->total, 0, ',', '.') }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
                
                <!-- Footer (Totals) -->
                <div class="bg-gray-50 p-6 border-t border-gray-100">
                    <div class="flex justify-between items-center">
                        <span class="font-bold text-gray-600">Total Pembayaran</span>
                        <span class="text-2xl font-extrabold text-brand-gold-dark">Rp {{ number_format($total, 0, ',', '.') }}</span>
                    </div>
                </div>
            </div>

            @if(!$isUnpaid)
                <!-- PAID SECTION: TRACKING TIMELINE -->
                <div class="bg-white rounded-3xl shadow-md border border-gray-100 p-6 md:p-8">
                    <h3 class="text-xl font-bold text-brand-dark mb-8 flex items-center gap-2">
                        <i class="fa-solid fa-truck-fast text-brand-gold"></i> Lacak Pengiriman
                    </h3>

                    <!-- Shopee-style Timeline -->
                    <div class="relative pl-6 space-y-8 before:absolute before:inset-0 before:ml-[1.125rem] before:-translate-x-px md:before:mx-auto md:before:translate-x-0 before:h-full before:w-0.5 before:bg-gradient-to-b before:from-transparent before:via-brand-gold/30 before:to-transparent">
                        
                        <!-- Timeline Item 1 (Paid) -->
                        <div class="relative flex items-center justify-between md:justify-normal md:odd:flex-row-reverse group is-active">
                            <div class="flex items-center justify-center w-8 h-8 rounded-full border-4 border-white bg-brand-gold text-white shadow shrink-0 md:order-1 md:group-odd:-translate-x-1/2 md:group-even:translate-x-1/2">
                                <i class="fa-solid fa-wallet text-xs"></i>
                            </div>
                            <div class="w-[calc(100%-3rem)] md:w-[calc(50%-2.5rem)] p-4 rounded-xl border border-brand-gold/30 shadow-sm bg-brand-light/20">
                                <h4 class="font-bold text-gray-800 mb-1">Pembayaran Berhasil</h4>
                                <p class="text-sm text-gray-500">Pembayaran telah diverifikasi.</p>
                            </div>
                        </div>

                        <!-- Timeline Item 2 (Processed) -->
                        <div class="relative flex items-center justify-between md:justify-normal md:odd:flex-row-reverse group is-active">
                            <div class="flex items-center justify-center w-8 h-8 rounded-full border-4 border-white shadow shrink-0 md:order-1 md:group-odd:-translate-x-1/2 md:group-even:translate-x-1/2 {{ $status >= \App\Models\Frontend\Order::STATUS_PROCESSING ? 'bg-brand-gold text-white border-brand-gold/20' : 'bg-gray-200 text-gray-400' }}">
                                <i class="fa-solid fa-box-open text-xs"></i>
                            </div>
                            <div class="w-[calc(100%-3rem)] md:w-[calc(50%-2.5rem)] p-4 rounded-xl border border-gray-100 shadow-sm {{ $status >= \App\Models\Frontend\Order::STATUS_PROCESSING ? 'bg-brand-light/20 border-brand-gold/30' : 'bg-gray-50' }}">
                                <h4 class="font-bold text-gray-800 mb-1">Pesanan Diproses</h4>
                                <p class="text-sm text-gray-500">Pesanan sedang dikemas oleh penjual.</p>
                            </div>
                        </div>

                        <!-- Timeline Item 3 (Shipped) -->
                        <div class="relative flex items-center justify-between md:justify-normal md:odd:flex-row-reverse group is-active">
                            <div class="flex items-center justify-center w-8 h-8 rounded-full border-4 border-white shadow shrink-0 md:order-1 md:group-odd:-translate-x-1/2 md:group-even:translate-x-1/2 {{ $status >= \App\Models\Frontend\Order::STATUS_SHIPPED ? 'bg-brand-gold text-white border-brand-gold/20' : 'bg-gray-200 text-gray-400' }}">
                                <i class="fa-solid fa-truck text-xs"></i>
                            </div>
                            <div class="w-[calc(100%-3rem)] md:w-[calc(50%-2.5rem)] p-4 rounded-xl border border-gray-100 shadow-sm {{ $status >= \App\Models\Frontend\Order::STATUS_SHIPPED ? 'bg-brand-light/20 border-brand-gold/30' : 'bg-gray-50' }}">
                                <h4 class="font-bold text-gray-800 mb-1">Sedang Dikirim</h4>
                                <p class="text-sm text-gray-500">Paket sedang dalam perjalanan kurir.</p>
                            </div>
                        </div>

                        <!-- Timeline Item 4 (Delivered) -->
                        <div class="relative flex items-center justify-between md:justify-normal md:odd:flex-row-reverse group is-active">
                            <div class="flex items-center justify-center w-8 h-8 rounded-full border-4 border-white shadow shrink-0 md:order-1 md:group-odd:-translate-x-1/2 md:group-even:translate-x-1/2 {{ $status >= \App\Models\Frontend\Order::STATUS_DELIVERED ? 'bg-green-500 text-white border-green-100' : 'bg-gray-200 text-gray-400' }}">
                                <i class="fa-solid fa-house-chimney text-xs"></i>
                            </div>
                            <div class="w-[calc(100%-3rem)] md:w-[calc(50%-2.5rem)] p-4 rounded-xl border border-gray-100 shadow-sm {{ $status >= \App\Models\Frontend\Order::STATUS_DELIVERED ? 'bg-green-50 border-green-200' : 'bg-gray-50' }}">
                                <div class="flex flex-col md:flex-row md:items-center justify-between mb-1">
                                    <h4 class="font-bold text-gray-800">Pesanan Diterima</h4>
                                </div>
                                <p class="text-sm text-gray-500">Paket telah diterima oleh pelanggan.</p>
                            </div>
                        </div>

                    </div>
                </div>
            @endif

        </div>
    </div>
@endsection

@push('scripts')
@if($isUnpaid)
<script>
    document.addEventListener('DOMContentLoaded', function() {
        var countdownEl = document.getElementById('order-countdown');
        if (!countdownEl) return;
        
        var createdStr = countdownEl.getAttribute('data-created');
        if (!createdStr) return;
        
        var createdAt = new Date(createdStr).getTime();
        var expireAt = createdAt + (24 * 60 * 60 * 1000); // 24 hours
        
        function updateTimer() {
            var now = new Date().getTime();
            var distance = expireAt - now;
            
            if (distance < 0) {
                countdownEl.innerHTML = "00:00:00";
                countdownEl.classList.add('text-gray-400');
                countdownEl.classList.remove('text-red-600');
                return;
            }
            
            var hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            var minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
            var seconds = Math.floor((distance % (1000 * 60)) / 1000);
            
            hours = hours < 10 ? "0" + hours : hours;
            minutes = minutes < 10 ? "0" + minutes : minutes;
            seconds = seconds < 10 ? "0" + seconds : seconds;
            
            countdownEl.innerHTML = hours + ":" + minutes + ":" + seconds;
        }
        
        updateTimer();
        setInterval(updateTimer, 1000);
    });
</script>
@endif
@endpush
