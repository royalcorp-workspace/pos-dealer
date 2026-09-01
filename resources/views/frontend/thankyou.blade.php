@extends('frontend.layouts.app')

@section('title', 'Pesanan Berhasil - IMG')

@php
    $orderId = $order?->order_number ?? 'ORD-' . date('Ymd') . '-' . rand(1000, 9999);
    $paymentMethod = $order?->payment_method ?? '-';
    $total = $order?->total ?? 0;
    $status = $order?->status ?? 1;
    $statusLabel = \App\Models\Frontend\Order::statusLabels()[$status] ?? 'Menunggu Pembayaran';
    $statusBadge = $order?->getStatusBadgeClassAttribute() ?? 'bg-yellow-100 text-yellow-700';
    $items = $order?->items ?? [];
@endphp

@section('content')
    <div class="container mx-auto px-4 pt-12 pb-24">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 max-w-6xl mx-auto">
            
            <!-- Left Column: Receipt / Order Details -->
            <div class="lg:col-span-7 space-y-6">
                <!-- Receipt Card -->
                <div class="bg-white rounded-3xl shadow-xl shadow-brand-dark/5 overflow-hidden border border-gray-100">
                    
                    <!-- Success Message Centered -->
                    <div class="text-center pt-10 pb-6 px-8">
                        <div class="inline-flex items-center justify-center w-20 h-20 bg-green-100 rounded-full mb-5">
                            <div class="w-14 h-14 bg-green-500 rounded-full flex items-center justify-center text-white">
                                <i class="fa-solid fa-check text-2xl"></i>
                            </div>
                        </div>
                        <h1 class="text-3xl font-extrabold text-brand-dark mb-3 font-serif">Pesanan Berhasil!</h1>
                        <p class="text-gray-500 text-sm max-w-md mx-auto">
                            Terima kasih atas kepercayaan Anda. Kami telah menerima pesanan Anda dan akan segera memprosesnya dengan penuh kehati-hatian.
                        </p>
                    </div>

                    @if($status == 1)
                    <div class="bg-amber-50 border-y border-amber-100 p-6 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                        <div>
                            <p class="text-brand-dark font-bold">Selesaikan pembayaran sebelum batas waktu berakhir</p>
                            <p class="text-sm text-gray-600 mt-1">Pesanan akan otomatis dibatalkan (void) jika melewati batas waktu.</p>
                        </div>
                        <div class="text-2xl font-bold text-red-600 font-mono tracking-widest bg-white px-4 py-2 rounded-xl shadow-sm border border-red-100" id="thankyou-countdown" data-created="{{ $order->meta['payment_started_at'] ?? ($order->created_at ? $order->created_at->toIso8601String() : now()->toIso8601String()) }}">
                            --:--:--
                        </div>
                    </div>
                    @endif
                </div>

                    <!-- Receipt Header -->
                    <div class="bg-gray-50 px-8 py-6 border-y border-dashed border-gray-300 flex justify-between items-center">
                        <div>
                            <p class="text-xs text-gray-500 uppercase tracking-wider font-bold mb-1">ID Pesanan</p>
                            <p class="text-xl font-mono font-extrabold text-brand-dark">{{ $orderId }}</p>
                        </div>
                        <div class="text-right">
                            <span class="inline-block px-4 py-1.5 rounded-full text-xs font-bold uppercase tracking-wider {{ $statusBadge }} shadow-sm">
                                {{ $statusLabel }}
                            </span>
                        </div>
                    </div>
                    
                    <!-- Receipt Body -->
                    <div class="p-8">
                        @if($order)
                            <div class="space-y-6">
                                <!-- Order Items -->
                                @if($items->count() > 0)
                                    <div>
                                        <h3 class="text-sm font-bold text-gray-400 uppercase tracking-wider mb-4 border-b pb-2">Detail Produk</h3>
                                        <div class="space-y-4">
                                            @foreach($items as $item)
                                                <div class="flex justify-between items-center group">
                                                    <div class="flex items-center gap-4">
                                                        <div class="w-12 h-12 bg-gray-100 rounded-xl flex items-center justify-center flex-shrink-0 group-hover:bg-brand-light transition-colors">
                                                            <i class="fa-solid fa-box text-gray-400 group-hover:text-brand-gold-dark"></i>
                                                        </div>
                                                        <div>
                                                            <p class="font-bold text-brand-dark leading-tight">{{ $item->name }}</p>
                                                            <p class="text-sm text-gray-500">Qty: {{ $item->quantity }}</p>
                                                        </div>
                                                    </div>
                                                    <span class="font-extrabold text-brand-dark">Rp {{ number_format($item->total, 0, ',', '.') }}</span>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                                
                                <!-- Payment Info -->
                                <div class="bg-brand-light/30 rounded-2xl p-6 border border-brand-muted">
                                    <div class="flex justify-between items-center mb-2">
                                        <span class="text-gray-600 font-medium">Metode Pembayaran</span>
                                        <span class="font-bold text-brand-dark text-right">{{ ucwords(str_replace(['_', '-'], ' ', $paymentMethod)) }}</span>
                                    </div>
                                    
                                    <div class="space-y-2 mt-4 pt-4 border-t border-brand-muted/50 text-sm">
                                        <div class="flex justify-between items-center">
                                            <span class="text-gray-500">Subtotal Produk</span>
                                            <span class="font-semibold text-gray-700">Rp {{ number_format($order->subtotal ?? 0, 0, ',', '.') }}</span>
                                        </div>
                                        
                                        @if(($order->shipping_cost ?? 0) > 0)
                                            <div class="flex justify-between items-center">
                                                <span class="text-gray-500">Ongkos Kirim</span>
                                                <span class="font-semibold text-gray-700">Rp {{ number_format($order->shipping_cost, 0, ',', '.') }}</span>
                                            </div>
                                        @endif
                                        
                                        @if(($order->transaction_fee ?? 0) > 0)
                                            <div class="flex justify-between items-center">
                                                <span class="text-gray-500">Biaya Layanan</span>
                                                <span class="font-semibold text-gray-700">Rp {{ number_format($order->transaction_fee, 0, ',', '.') }}</span>
                                            </div>
                                        @endif
                                        
                                        @if(($order->discount ?? 0) > 0)
                                            <div class="flex justify-between items-center text-red-600">
                                                <span class="font-medium">Diskon</span>
                                                <span class="font-semibold">- Rp {{ number_format($order->discount, 0, ',', '.') }}</span>
                                            </div>
                                        @endif
                                    </div>

                                    <div class="flex justify-between items-center pt-4 border-t border-brand-muted/50 mt-4">
                                        <span class="text-gray-800 font-bold">Total Pembayaran</span>
                                        <span class="text-2xl font-extrabold text-brand-gold-dark">Rp {{ number_format($total, 0, ',', '.') }}</span>
                                    </div>
                                </div>
                                
                                @php
                                    $vaNumber = $order->meta['va_number'] ?? null;
                                    $espayRef = $order->meta['espay_reference'] ?? null;
                                @endphp

                                @if($vaNumber)
                                    <div class="bg-blue-50 rounded-2xl p-6 border border-blue-200 mt-4">
                                        <h4 class="font-bold text-brand-dark mb-4 flex items-center gap-2">
                                            <i class="fa-solid fa-building-columns text-blue-500"></i> Informasi Virtual Account
                                        </h4>
                                        <div class="space-y-3 text-sm">
                                            <div class="flex flex-col bg-white p-4 rounded-xl border border-blue-100 items-center justify-center text-center">
                                                <span class="text-gray-500 mb-1">Nomor Virtual Account</span>
                                                <span class="font-mono font-extrabold text-blue-700 text-3xl tracking-widest select-all">{{ $vaNumber }}</span>
                                            </div>
                                        </div>

                                        @php
                                            $instructions = $order->meta['payment_instructions'] ?? [];
                                        @endphp
                                        
                                        @if(is_array($instructions) && count($instructions) > 0)
                                            <div class="mt-6 pt-5 border-t border-blue-200">
                                                <h4 class="font-bold text-brand-dark mb-3 text-sm">Tata Cara Pembayaran:</h4>
                                                <div class="space-y-3">
                                                    @foreach($instructions as $inst)
                                                        <details class="bg-white rounded-lg border border-gray-200 overflow-hidden">
                                                            <summary class="font-medium p-3 cursor-pointer bg-gray-50">{{ $inst['title'] ?? 'Langkah Pembayaran' }}</summary>
                                                            <div class="p-3 text-sm text-gray-600 border-t border-gray-100">
                                                                <ol class="list-decimal ml-4 space-y-1">
                                                                    @foreach($inst['steps'] ?? [] as $step)
                                                                        <li>{!! str_replace('Virtual Account', 'VA <b>'.$vaNumber.'</b>', $step) !!}</li>
                                                                    @endforeach
                                                                </ol>
                                                            </div>
                                                        </details>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                @endif
                                
                                @if($paymentMethod === 'transfer_manual')
                                    <div class="bg-amber-50 rounded-2xl p-6 border border-brand-gold/30">
                                        <h4 class="font-bold text-brand-dark mb-4 flex items-center gap-2">
                                            <i class="fa-solid fa-building-columns text-brand-gold"></i> Instruksi Pembayaran
                                        </h4>
                                        <div class="space-y-3 text-sm">
                                            <div class="flex justify-between items-center bg-white p-3 rounded-xl border border-amber-100">
                                                <span class="text-gray-500">Bank</span>
                                                <span class="font-bold text-brand-dark">BCA</span>
                                            </div>
                                            <div class="flex justify-between items-center bg-white p-3 rounded-xl border border-amber-100">
                                                <span class="text-gray-500">No. Rekening</span>
                                                <span class="font-mono font-bold text-brand-dark text-base">123-456-7890</span>
                                            </div>
                                            <div class="flex justify-between items-center bg-white p-3 rounded-xl border border-amber-100">
                                                <span class="text-gray-500">Atas Nama</span>
                                                <span class="font-bold text-brand-dark">PT RAS</span>
                                            </div>
                                        </div>
                                        
                                        @php
                                            $proof = $order->meta['payment_proof'] ?? null;
                                        @endphp
                                        @if($proof)
                                            <div class="mt-5 pt-5 border-t border-brand-gold/20">
                                                <h4 class="font-bold text-brand-dark mb-3 text-sm">Bukti Pembayaran Anda:</h4>
                                                <a href="{{ media_url($proof) }}" target="_blank" class="block w-32 rounded-xl overflow-hidden border-2 border-brand-gold hover:opacity-80 transition-opacity shadow-sm">
                                                    <img src="{{ media_url($proof) }}" alt="Bukti Transfer" loading="lazy" class="w-full h-auto object-cover">
                                                </a>
                                            </div>
                                        @else
                                            <div class="mt-5 bg-red-50 text-red-700 p-4 rounded-xl text-sm font-medium border border-red-100 flex items-start gap-3">
                                                <i class="fa-solid fa-circle-exclamation mt-0.5"></i>
                                                <p>Bukti transfer belum diupload. Silakan hubungi admin atau upload bukti di menu riwayat pesanan.</p>
                                            </div>
                                        @endif
                                    </div>
                                @endif
                                
                            </div>
                        @else
                            <div class="text-center py-12">
                                <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                    <i class="fa-solid fa-inbox text-gray-400 text-xl"></i>
                                </div>
                                <p class="text-gray-500 font-medium">Data pesanan tidak ditemukan.</p>
                                <p class="text-sm text-gray-400 mt-1">Silakan cek email Anda untuk detail pesanan.</p>
                            </div>
                        @endif
                    </div>
                    
                    <!-- Receipt Footer -->
                    <div class="bg-gray-50 px-8 py-5 border-t border-dashed border-gray-300 flex flex-col sm:flex-row gap-4 justify-between items-center">
                        <a href="{{ route('home') }}" class="w-full sm:w-auto px-6 py-2.5 rounded-full font-bold text-sm text-gray-600 hover:bg-gray-200 transition-colors text-center">
                            Kembali ke Home
                        </a>
                        @if(session()->get('is_logged_in'))
                        <a href="{{ route('dashboard', ['tab' => 'orders']) }}" class="w-full sm:w-auto px-6 py-2.5 bg-brand-dark text-brand-gold rounded-full font-bold text-sm hover:bg-brand-darker shadow-lg hover:shadow-xl transition-all text-center">
                            Lihat Status Pesanan <i class="fa-solid fa-arrow-right ml-1"></i>
                        </a>
                        @endif
                    </div>
                </div>
            </div>
            
            <!-- Right Column: Upsell / Reward -->
            <div class="lg:col-span-5 space-y-6">
                <!-- Voucher Gift Card 
                <div class="relative bg-gradient-to-br from-brand-gold to-yellow-500 rounded-3xl p-1 overflow-hidden shadow-2xl shadow-brand-gold/30 group">
                    <div class="absolute inset-0 bg-[url('https://www.transparenttextures.com/patterns/cubes.png')] opacity-10"></div>
                    <div class="bg-white rounded-[22px] h-full p-8 relative overflow-hidden flex flex-col items-center text-center">
                        
                        <div class="absolute top-0 right-0 -mr-4 -mt-4 text-brand-gold/20 text-6xl rotate-12"><i class="fa-solid fa-certificate"></i></div>
                        
                        <div class="w-16 h-16 bg-brand-light rounded-full flex items-center justify-center mb-5 text-brand-gold-dark z-10">
                            <i class="fa-solid fa-gift text-2xl"></i>
                        </div>
                        
                        <h3 class="text-2xl font-extrabold text-brand-dark font-serif mb-2 z-10">Kejutan Khusus!</h3>
                        <p class="text-gray-500 text-sm mb-6 z-10">Sebagai tanda terima kasih, nikmati potongan ekstra untuk pesanan Anda berikutnya.</p>
                        
                        <div class="w-full border-2 border-dashed border-brand-gold bg-brand-light/30 rounded-xl p-4 mb-6 relative z-10">
                            <p class="text-xs text-brand-dark/70 font-bold uppercase tracking-widest mb-1">Kode Voucher Anda</p>
                            <p class="text-3xl font-mono font-extrabold text-brand-gold-dark tracking-wider select-all">THX10</p>
                        </div>
                        
                        <ul class="text-left w-full space-y-2 mb-8 text-sm text-gray-600 z-10">
                            <li class="flex items-start gap-2">
                                <i class="fa-solid fa-check text-green-500 mt-1"></i> Diskon tambahan 10%
                            </li>
                            <li class="flex items-start gap-2">
                                <i class="fa-solid fa-check text-green-500 mt-1"></i> Berlaku untuk semua produk
                            </li>
                            <li class="flex items-start gap-2">
                                <i class="fa-solid fa-check text-green-500 mt-1"></i> Tanpa minimal belanja
                            </li>
                        </ul>
                        
                        <a href="{{ route('home') }}" class="w-full py-3.5 bg-brand-dark text-white rounded-xl font-bold hover:bg-brand-darker transition-colors shadow-lg shadow-brand-dark/20 z-10">
                            Belanja Lagi Sekarang
                        </a>
                    </div>
                </div>
                -->
                
                <!-- Support Banner -->
                <div class="bg-white rounded-3xl p-6 border border-brand-muted flex items-center gap-4 shadow-sm hover:shadow-md transition-shadow">
                    <div class="w-12 h-12 bg-blue-50 rounded-full flex items-center justify-center flex-shrink-0 text-blue-500">
                        <i class="fa-solid fa-headset text-xl"></i>
                    </div>
                    <div>
                        <h4 class="font-bold text-brand-dark text-sm">Butuh Bantuan?</h4>
                        <p class="text-xs text-gray-500 mt-0.5">Tim kami siap membantu Anda kapan saja. <a href="{{ route('help') }}" class="text-brand-gold-dark font-bold hover:underline">Hubungi Kami</a></p>
                    </div>
                </div>
                
            </div>
            
        </div>
    </div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Hapus data form checkout agar pesanan berikutnya tidak terisi data lama secara tidak sengaja
        sessionStorage.removeItem('checkout_form_data');
    });
</script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        var countdownEl = document.getElementById('thankyou-countdown');
        if (!countdownEl) return;
        
        var createdStr = countdownEl.getAttribute('data-created');
        if (!createdStr) return;
        
        // Set expiration to 24 hours after creation
        var createdAt = new Date(createdStr).getTime();
        var expireAt = createdAt + (24 * 60 * 60 * 1000);
        
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
@endpush

@push('tracking_events')
@if($order)
<script>
    window.dataLayer = window.dataLayer || [];
    dataLayer.push({ ecommerce: null });
    dataLayer.push({
        event: "purchase",
        ecommerce: {
            transaction_id: "{{ $orderId }}",
            value: {{ $total }},
            currency: "IDR",
            items: [
                @foreach($items as $item)
                {
                    item_id: "{{ $item->product_id ?? '' }}",
                    item_name: "{{ $item->name ?? '' }}",
                    price: {{ $item->unit_price ?? 0 }},
                    quantity: {{ $item->quantity ?? 1 }}
                }@if(!$loop->last),@endif
                @endforeach
            ]
        }
    });
</script>
@endif
@endpush