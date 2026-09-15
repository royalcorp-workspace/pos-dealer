@extends('frontend.layouts.app')

@section('title', 'Pesanan Berhasil - ' . ($order?->order_number ?? 'IMG'))

@php
    $orderId = $order?->order_number ?? 'ORD-' . date('Ymd') . '-' . rand(1000, 9999);
    $paymentMethod = $order?->payment_method ?? '-';
    $total = $order?->total ?? 0;
    $status = $order?->status ?? 1;
    $statusLabel = \App\Models\Frontend\Order::statusLabels()[$status] ?? 'Menunggu Pembayaran';
    $statusBadge = $order?->getStatusBadgeClassAttribute() ?? 'bg-yellow-100 text-yellow-700';
    $items = $order?->items ?? [];
@endphp

@push('styles')
<style>
@media print {
    header, nav, footer, #floating-whatsapp, .no-print, [x-data*="toast"] {
        display: none !important;
    }
    body {
        background: #fff !important;
        color: #000 !important;
        padding: 0 !important;
        margin: 0 !important;
    }
    .print-receipt-wrapper {
        max-width: 100% !important;
        margin: 0 !important;
        padding: 0 !important;
        border: none !important;
        box-shadow: none !important;
    }
}
</style>
@endpush

@section('content')
    <div class="container mx-auto px-4 md:px-6 py-8 md:py-12 min-h-[70vh] font-sans">
        
        <!-- Progress / Step Indicator Wizard (No-Print) -->
        <div class="max-w-3xl mx-auto mb-10 no-print">
            <div class="relative flex items-center justify-between">
                <!-- Background track (Fully active to step 4) -->
                <div class="absolute left-0 top-1/2 -translate-y-1/2 h-1 bg-brand-gold w-full z-0 rounded-full"></div>

                <!-- Step 1: Keranjang (Completed) -->
                <a href="{{ route('home') }}" class="relative z-10 flex flex-col items-center group cursor-pointer" title="Keranjang Belanja">
                    <div class="w-10 h-10 rounded-full bg-brand-gold text-white flex items-center justify-center font-bold text-sm shadow-md transition-transform group-hover:scale-110">
                        <i class="fa-solid fa-check"></i>
                    </div>
                    <span class="text-xs font-semibold text-brand-dark mt-2 tracking-tight">Keranjang</span>
                </a>

                <!-- Step 2: Pengiriman (Completed) -->
                <div class="relative z-10 flex flex-col items-center">
                    <div class="w-10 h-10 rounded-full bg-brand-gold text-white flex items-center justify-center font-bold text-sm shadow-md">
                        <i class="fa-solid fa-check"></i>
                    </div>
                    <span class="text-xs font-semibold text-brand-dark mt-2 tracking-tight">Pengiriman</span>
                </div>

                <!-- Step 3: Pembayaran (Completed) -->
                <div class="relative z-10 flex flex-col items-center">
                    <div class="w-10 h-10 rounded-full bg-brand-gold text-white flex items-center justify-center font-bold text-sm shadow-md">
                        <i class="fa-solid fa-check"></i>
                    </div>
                    <span class="text-xs font-semibold text-brand-dark mt-2 tracking-tight">Pembayaran</span>
                </div>

                <!-- Step 4: Selesai (Active & Celebrated) -->
                <div class="relative z-10 flex flex-col items-center">
                    <div class="w-10 h-10 rounded-full bg-emerald-600 text-white border-2 border-emerald-400 flex items-center justify-center font-bold text-sm shadow-lg ring-4 ring-emerald-500/20 scale-105">
                        <i class="fa-solid fa-circle-check text-base"></i>
                    </div>
                    <span class="text-xs font-bold text-emerald-700 mt-2 tracking-tight">Pesanan Dibuat</span>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 max-w-6xl mx-auto print-receipt-wrapper">
            
            <!-- Left Column: Order Journey, Products, Delivery Details -->
            <div class="lg:col-span-8 space-y-6">
                
                <!-- Hero Banner Card -->
                <div class="bg-white rounded-3xl shadow-sm border border-gray-200/80 overflow-hidden">
                    <div class="text-center pt-8 pb-6 px-6 sm:px-8">
                        <div class="inline-flex items-center justify-center w-20 h-20 bg-emerald-50 border-2 border-emerald-200 rounded-full mb-4 shadow-sm">
                            <div class="w-14 h-14 bg-emerald-600 rounded-full flex items-center justify-center text-white shadow-sm">
                                <i class="fa-solid fa-check text-2xl"></i>
                            </div>
                        </div>
                        <h1 class="text-2xl sm:text-3xl font-extrabold text-brand-dark mb-2 font-serif tracking-tight">Pesanan Berhasil Dibuat!</h1>
                        <p class="text-gray-500 text-xs sm:text-sm max-w-lg mx-auto leading-relaxed">
                            Terima kasih atas kepercayaan Anda. Kami telah menerima pesanan Anda dan siap memprosesnya dengan penuh kehati-hatian.
                        </p>
                    </div>

                    <!-- Payment Deadline Countdown Notice (If Unpaid) -->
                    @if($status == 1)
                        <div class="bg-gradient-to-r from-amber-50 via-amber-50/80 to-amber-50 border-y border-amber-200/80 p-4 sm:p-5 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-full bg-amber-100 text-amber-700 flex items-center justify-center shrink-0">
                                    <i class="fa-solid fa-clock text-sm"></i>
                                </div>
                                <div>
                                    <p class="text-brand-dark font-bold text-xs sm:text-sm">Batas Waktu Pembayaran</p>
                                    <p class="text-[11px] sm:text-xs text-gray-500">Selesaikan pembayaran sebelum batas waktu agar pesanan tidak otomatis dibatalkan.</p>
                                </div>
                            </div>
                            <div class="self-end sm:self-center font-mono font-black text-lg sm:text-xl text-red-600 bg-white px-3 py-1.5 rounded-xl shadow-2xs border border-red-200 tracking-widest shrink-0" id="thankyou-countdown" data-created="{{ $order->meta['payment_started_at'] ?? ($order->created_at ? $order->created_at->toIso8601String() : now()->toIso8601String()) }}">
                                --:--:--
                            </div>
                        </div>
                    @endif

                    <!-- Receipt Header Strip -->
                    <div class="bg-gray-50/80 px-6 sm:px-8 py-4 border-b border-dashed border-gray-200 flex justify-between items-center flex-wrap gap-2">
                        <div>
                            <span class="text-[10px] text-gray-400 uppercase tracking-widest font-bold block">Nomor ID Pesanan</span>
                            <div class="flex items-center gap-2">
                                <span class="text-base sm:text-lg font-mono font-extrabold text-brand-dark">{{ $orderId }}</span>
                                <button type="button" onclick="navigator.clipboard.writeText('{{ $orderId }}'); window.dispatchEvent(new CustomEvent('show-toast', { detail: { type: 'success', message: 'Nomor pesanan berhasil disalin!' } }));" class="text-xs bg-brand-gold/15 hover:bg-brand-gold/25 text-brand-gold-dark font-bold px-2 py-0.5 rounded-md transition-colors" title="Salin ID Pesanan">
                                    <i class="fa-regular fa-copy text-[10px]"></i>
                                </button>
                            </div>
                        </div>
                        <div class="text-right">
                            <span class="inline-block px-3.5 py-1 rounded-full text-xs font-bold uppercase tracking-wider {{ $statusBadge }} shadow-2xs border border-black/5">
                                {{ $statusLabel }}
                            </span>
                        </div>
                    </div>

                    <!-- Order Lifecycle Tracker Timeline -->
                    <div class="p-6 sm:p-7 border-b border-gray-100 no-print">
                        <h3 class="text-xs font-bold uppercase tracking-wider text-gray-500 mb-4 flex items-center gap-1.5">
                            <i class="fa-solid fa-route text-brand-gold"></i> Status & Alur Pesanan
                        </h3>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                            <!-- Step 1: Pesanan Dibuat -->
                            <div class="flex items-center gap-2.5 p-2.5 rounded-xl bg-white border border-emerald-200 shadow-2xs">
                                <div class="w-7 h-7 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center text-xs font-bold shrink-0">
                                    <i class="fa-solid fa-check"></i>
                                </div>
                                <div class="min-w-0">
                                    <p class="text-[11px] font-bold text-brand-dark truncate">1. Pesanan Dibuat</p>
                                    <p class="text-[10px] text-emerald-600 font-semibold">Tercatat</p>
                                </div>
                            </div>

                            <!-- Step 2: Pembayaran -->
                            <div class="flex items-center gap-2.5 p-2.5 rounded-xl bg-white {{ $status >= 2 ? 'border-emerald-200' : 'border-amber-300 ring-2 ring-amber-100' }} shadow-2xs">
                                <div class="w-7 h-7 rounded-full {{ $status >= 2 ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700 animate-pulse' }} flex items-center justify-center text-xs font-bold shrink-0">
                                    <i class="fa-solid {{ $status >= 2 ? 'fa-check' : 'fa-hourglass-half' }}"></i>
                                </div>
                                <div class="min-w-0">
                                    <p class="text-[11px] font-bold text-brand-dark truncate">2. Pembayaran</p>
                                    <p class="text-[10px] {{ $status >= 2 ? 'text-emerald-600 font-semibold' : 'text-amber-700 font-bold' }}">
                                        {{ $status >= 2 ? 'Lunas' : 'Menunggu Bayar' }}
                                    </p>
                                </div>
                            </div>

                            <!-- Step 3: Proses Gudang -->
                            <div class="flex items-center gap-2.5 p-2.5 rounded-xl bg-white {{ $status >= 3 ? 'border-indigo-200 ring-2 ring-indigo-100' : 'border-gray-200 opacity-60' }} shadow-2xs">
                                <div class="w-7 h-7 rounded-full {{ $status >= 3 ? 'bg-indigo-100 text-indigo-700' : 'bg-gray-100 text-gray-400' }} flex items-center justify-center text-xs font-bold shrink-0">
                                    <i class="fa-solid {{ $status >= 4 ? 'fa-check' : 'fa-box' }}"></i>
                                </div>
                                <div class="min-w-0">
                                    <p class="text-[11px] font-bold text-brand-dark truncate">3. Proses Gudang</p>
                                    <p class="text-[10px] text-gray-500 font-medium">
                                        {{ $status >= 3 ? 'Sedang Diproses' : 'Menunggu' }}
                                    </p>
                                </div>
                            </div>

                            <!-- Step 4: Pengiriman -->
                            <div class="flex items-center gap-2.5 p-2.5 rounded-xl bg-white {{ $status >= 4 ? 'border-purple-200 ring-2 ring-purple-100' : 'border-gray-200 opacity-60' }} shadow-2xs">
                                <div class="w-7 h-7 rounded-full {{ $status >= 4 ? 'bg-purple-100 text-purple-700' : 'bg-gray-100 text-gray-400' }} flex items-center justify-center text-xs font-bold shrink-0">
                                    <i class="fa-solid {{ $status >= 5 ? 'fa-check' : 'fa-truck-fast' }}"></i>
                                </div>
                                <div class="min-w-0">
                                    <p class="text-[11px] font-bold text-brand-dark truncate">4. Pengiriman</p>
                                    <p class="text-[10px] text-gray-500 font-medium">
                                        {{ $status >= 5 ? 'Terkirim' : ($status == 4 ? 'Dalam Perjalanan' : 'Menunggu') }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Details (VA or Manual Transfer) -->
                    @php
                        $vaNumber = $order->meta['va_number'] ?? null;
                        $espayRef = $order->meta['espay_reference'] ?? null;
                        
                        $pmModel = \App\Models\PaymentMethod::where('code', $paymentMethod)->first();
                        $isBankTransfer = ($pmModel && (
                            $pmModel->isTypeBankTransfer() 
                            || (int)$pmModel->type === 1 
                            || strtolower((string)$pmModel->provider) !== 'espay'
                        )) || in_array($paymentMethod, ['transfer_manual', 'trf'], true);
                        $banks = $pmModel && !empty($pmModel->bank_info) ? $pmModel->bank_info : [];
                    @endphp

                    @if($vaNumber)
                        <div class="p-6 sm:p-7 bg-blue-50/50 border-b border-blue-100">
                            <div class="flex items-center justify-between gap-3 mb-4">
                                <div class="flex items-center gap-2.5">
                                    <div class="w-8 h-8 rounded-lg bg-blue-100 text-blue-700 flex items-center justify-center text-sm">
                                        <i class="fa-solid fa-building-columns"></i>
                                    </div>
                                    <div>
                                        <h4 class="font-bold text-brand-dark text-sm sm:text-base">Informasi Virtual Account</h4>
                                        <p class="text-xs text-gray-500">Saluran: {{ ucwords(str_replace(['_', '-'], ' ', $paymentMethod)) }}</p>
                                    </div>
                                </div>
                                <span class="text-[11px] font-bold text-blue-700 bg-blue-100 px-2.5 py-0.5 rounded-full">Otomatis / Instant</span>
                            </div>

                            <div class="bg-white rounded-2xl p-5 border border-blue-100 shadow-2xs space-y-4">
                                <div>
                                    <span class="text-xs text-gray-500 font-semibold block mb-1">Nomor Virtual Account:</span>
                                    <div class="flex items-center justify-between gap-2 p-3 bg-blue-50/40 rounded-xl border border-blue-100">
                                        <span class="font-mono font-black text-blue-800 text-xl sm:text-2xl tracking-widest select-all">{{ $vaNumber }}</span>
                                        <button type="button" onclick="navigator.clipboard.writeText('{{ $vaNumber }}'); window.dispatchEvent(new CustomEvent('show-toast', { detail: { type: 'success', message: 'Nomor Virtual Account {{ $vaNumber }} berhasil disalin!' } }));" class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-xs font-bold transition-colors inline-flex items-center gap-1.5 shadow-2xs shrink-0 cursor-pointer">
                                            <i class="fa-regular fa-copy text-xs"></i> Salin VA
                                        </button>
                                    </div>
                                </div>

                                <div class="flex items-center justify-between pt-2 border-t border-gray-100">
                                    <div>
                                        <span class="text-xs text-gray-500 font-semibold block">Total yang Harus Dibayar:</span>
                                        <span class="text-[11px] text-gray-400">Tepat hingga nominal akhir</span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <span class="font-black text-brand-dark text-lg sm:text-xl">Rp {{ number_format($total, 0, ',', '.') }}</span>
                                        <button type="button" onclick="navigator.clipboard.writeText('{{ round($total) }}'); window.dispatchEvent(new CustomEvent('show-toast', { detail: { type: 'success', message: 'Nominal transfer berhasil disalin!' } }));" class="p-1.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg text-xs transition-colors" title="Salin Nominal">
                                            <i class="fa-regular fa-copy text-xs"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            @php
                                $instructions = $order->meta['payment_instructions'] ?? [];
                            @endphp
                            @if(is_array($instructions) && count($instructions) > 0)
                                <div class="mt-4 pt-4 border-t border-blue-100 no-print">
                                    <h5 class="font-bold text-brand-dark text-xs uppercase tracking-wider mb-2.5 flex items-center gap-1.5">
                                        <i class="fa-solid fa-circle-info text-blue-500 text-xs"></i> Tata Cara Pembayaran Virtual Account:
                                    </h5>
                                    <div class="space-y-2">
                                        @foreach($instructions as $inst)
                                            <details class="bg-white rounded-xl border border-blue-100 overflow-hidden text-xs">
                                                <summary class="font-bold p-3 cursor-pointer bg-gray-50/70 hover:bg-gray-100/70 transition-colors flex items-center justify-between">
                                                    <span>{{ $inst['title'] ?? 'Langkah Pembayaran' }}</span>
                                                    <i class="fa-solid fa-chevron-down text-[10px] text-gray-400"></i>
                                                </summary>
                                                <div class="p-3 text-gray-600 border-t border-gray-100 leading-relaxed">
                                                    <ol class="list-decimal ml-4 space-y-1">
                                                        @foreach($inst['steps'] ?? [] as $step)
                                                            <li>{!! str_replace('Virtual Account', 'VA <strong class="text-blue-700">'.$vaNumber.'</strong>', $step) !!}</li>
                                                        @endforeach
                                                    </ol>
                                                </div>
                                            </details>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>
                    @elseif($isBankTransfer)
                        <div class="p-6 sm:p-7 bg-amber-50/50 border-b border-amber-100">
                            <div class="flex items-center justify-between gap-3 mb-4">
                                <div class="flex items-center gap-2.5">
                                    <div class="w-8 h-8 rounded-lg bg-amber-100 text-amber-700 flex items-center justify-center text-sm">
                                        <i class="fa-solid fa-building-columns"></i>
                                    </div>
                                    <div>
                                        <h4 class="font-bold text-brand-dark text-sm sm:text-base">Instruksi Transfer Bank Manual</h4>
                                        <p class="text-xs text-gray-500">Silakan lakukan transfer ke salah satu rekening bank kami</p>
                                    </div>
                                </div>
                                <span class="text-[11px] font-bold text-amber-800 bg-amber-100 px-2.5 py-0.5 rounded-full">Verifikasi Manual</span>
                            </div>

                            @if(!empty($banks) && is_array($banks))
                                <div class="space-y-3">
                                    @foreach($banks as $b)
                                        <div class="bg-white p-4.5 rounded-2xl border border-amber-100 space-y-2.5 shadow-2xs">
                                            <div class="flex justify-between items-center pb-2 border-b border-gray-100">
                                                <span class="text-gray-500 text-xs font-semibold">Nama Bank</span>
                                                <span class="font-extrabold text-brand-dark text-sm bg-brand-light px-2.5 py-0.5 rounded-md border border-brand-muted">{{ $b['bank_name'] ?? 'BCA' }}</span>
                                            </div>
                                            <div class="flex justify-between items-center pb-2 border-b border-gray-100">
                                                <span class="text-gray-500 text-xs font-semibold">Nomor Rekening</span>
                                                <div class="flex items-center gap-2">
                                                    <span class="font-mono font-bold text-brand-dark text-base tracking-wider">{{ $b['account_number'] ?? '-' }}</span>
                                                    <button type="button" onclick="navigator.clipboard.writeText('{{ $b['account_number'] ?? '' }}'); window.dispatchEvent(new CustomEvent('show-toast', { detail: { type: 'success', message: 'Nomor rekening disalin!' } }));" class="text-xs bg-brand-gold/15 hover:bg-brand-gold/25 text-brand-gold-dark font-bold px-2 py-0.5 rounded-lg transition-colors inline-flex items-center gap-1 cursor-pointer">
                                                        <i class="fa-regular fa-copy text-[10px]"></i> Salin
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="flex justify-between items-center pb-2 border-b border-gray-100">
                                                <span class="text-gray-500 text-xs font-semibold">Atas Nama</span>
                                                <span class="font-bold text-gray-800 text-sm">{{ $b['account_holder'] ?? '-' }}</span>
                                            </div>
                                            <div class="flex justify-between items-center pt-1">
                                                <span class="text-gray-500 text-xs font-semibold">Total Transfer</span>
                                                <div class="flex items-center gap-2">
                                                    <span class="font-black text-brand-gold-dark text-base sm:text-lg">Rp {{ number_format($total, 0, ',', '.') }}</span>
                                                    <button type="button" onclick="navigator.clipboard.writeText('{{ round($total) }}'); window.dispatchEvent(new CustomEvent('show-toast', { detail: { type: 'success', message: 'Nominal transfer disalin!' } }));" class="p-1.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg text-xs transition-colors" title="Salin Nominal">
                                                        <i class="fa-regular fa-copy text-xs"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="bg-white p-4 rounded-2xl border border-amber-100 space-y-2 shadow-2xs text-sm">
                                    <div class="flex justify-between items-center border-b border-gray-100 pb-2">
                                        <span class="text-gray-500 text-xs">Bank</span>
                                        <span class="font-bold text-brand-dark">BCA</span>
                                    </div>
                                    <div class="flex justify-between items-center border-b border-gray-100 pb-2">
                                        <span class="text-gray-500 text-xs">No. Rekening</span>
                                        <div class="flex items-center gap-2">
                                            <span class="font-mono font-bold text-brand-dark text-base">123-456-7890</span>
                                            <button type="button" onclick="navigator.clipboard.writeText('1234567890'); window.dispatchEvent(new CustomEvent('show-toast', { detail: { type: 'success', message: 'Nomor rekening disalin!' } }));" class="text-xs bg-brand-gold/15 text-brand-gold-dark font-bold px-2 py-0.5 rounded-lg">Salin</button>
                                        </div>
                                    </div>
                                    <div class="flex justify-between items-center pb-2 border-b border-gray-100">
                                        <span class="text-gray-500 text-xs">Atas Nama</span>
                                        <span class="font-bold text-brand-dark">PT IMG Store Indonesia</span>
                                    </div>
                                    <div class="flex justify-between items-center pt-1">
                                        <span class="text-gray-500 text-xs">Total Transfer</span>
                                        <span class="font-black text-brand-gold-dark text-base">Rp {{ number_format($total, 0, ',', '.') }}</span>
                                    </div>
                                </div>
                            @endif

                            @php
                                $proof = $order->meta['payment_proof'] ?? null;
                            @endphp
                            @if($proof)
                                <div class="mt-4 pt-4 border-t border-amber-200">
                                    <h5 class="font-bold text-brand-dark text-xs uppercase tracking-wider mb-2">Bukti Pembayaran Terunggah:</h5>
                                    <a href="{{ media_url($proof) }}" target="_blank" class="inline-block rounded-xl overflow-hidden border-2 border-brand-gold hover:opacity-90 transition-opacity shadow-sm">
                                        <img src="{{ media_url($proof) }}" alt="Bukti Transfer" loading="lazy" class="w-32 h-auto object-cover">
                                    </a>
                                </div>
                            @else
                                <div class="mt-4 bg-amber-100/60 text-amber-800 p-3.5 rounded-xl text-xs font-medium border border-amber-200 flex items-start gap-2.5">
                                    <i class="fa-solid fa-circle-info text-amber-600 mt-0.5"></i>
                                    <p>Setelah melakukan transfer, silakan simpan bukti transfer Anda. Anda dapat mengonfirmasikannya langsung via Live Chat ke tim Customer Support kami.</p>
                                </div>
                            @endif
                        </div>
                    @endif
                </div>

                <!-- Products Ordered Card (With Images) -->
                <div class="bg-white rounded-3xl border border-gray-200/80 p-6 sm:p-7 shadow-sm">
                    <div class="flex items-center justify-between pb-4 mb-4 border-b border-gray-100">
                        <div class="flex items-center gap-2.5">
                            <div class="w-8 h-8 rounded-lg bg-brand-gold/15 text-brand-gold-dark flex items-center justify-center text-sm">
                                <i class="fa-solid fa-bag-shopping"></i>
                            </div>
                            <h3 class="font-bold text-brand-dark text-base">Detail Produk yang Dipesan</h3>
                        </div>
                        <span class="text-xs font-bold text-gray-500 bg-gray-100 px-2.5 py-0.5 rounded-full">
                            {{ count($items) }} Item
                        </span>
                    </div>
                    
                    <div class="divide-y divide-gray-100">
                        @foreach($items as $item)
                            @php
                                $itemImage = null;
                                if (!empty($item->meta['image'])) {
                                    $itemImage = $item->meta['image'];
                                } elseif ($item->product) {
                                    $itemImage = !empty($item->product->thumbnail) ? media_url($item->product->thumbnail) : ($item->product->thumbnail_url ?? null);
                                }
                                if (empty($itemImage) && !empty($item->product_id)) {
                                    $pCatalog = \App\Models\Frontend\ProductsCatalog\Product::find($item->product_id);
                                    $itemImage = $pCatalog?->thumbnail_url;
                                }
                                $colorName = $item->meta['color_name'] ?? null;
                                $colorCode = $item->meta['color_code'] ?? null;
                            @endphp
                            <div class="flex items-center gap-4 py-4 first:pt-0 last:pb-0">
                                <!-- Product Image -->
                                <div class="w-16 h-16 sm:w-20 sm:h-20 rounded-2xl bg-white border border-gray-200/80 overflow-hidden flex-shrink-0 shadow-2xs p-1">
                                    @if(!empty($itemImage))
                                        <img src="{{ $itemImage }}" alt="{{ $item->name }}" loading="lazy" decoding="async" class="w-full h-full object-cover rounded-xl">
                                    @else
                                        <div class="w-full h-full flex items-center justify-center text-brand-gold bg-brand-light rounded-xl">
                                            <i class="fa-solid fa-box text-xl"></i>
                                        </div>
                                    @endif
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="font-bold text-sm sm:text-base text-brand-dark leading-snug">{{ $item->name }}</p>
                                    <div class="flex items-center gap-2 mt-1 text-xs text-gray-500 flex-wrap">
                                        <span>Qty: <strong class="text-brand-dark font-bold">{{ $item->quantity }}</strong></span>
                                        @if($colorName)
                                            <span class="inline-flex items-center gap-1 bg-gray-100 px-2 py-0.5 rounded-md font-medium text-[11px] text-gray-700">
                                                @if($colorCode)
                                                    <span class="w-2.5 h-2.5 rounded-full inline-block border border-black/15 shrink-0" style="background-color: {{ $colorCode }}"></span>
                                                @endif
                                                {{ $colorName }}
                                            </span>
                                        @endif
                                        <span class="text-gray-400">@ Rp {{ number_format($item->unit_price, 0, ',', '.') }}</span>
                                    </div>
                                </div>
                                <div class="text-right shrink-0">
                                    <span class="font-black text-sm sm:text-base text-brand-dark">Rp {{ number_format($item->total, 0, ',', '.') }}</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Shipping & Destination Address Card -->
                <div class="bg-white rounded-3xl border border-gray-200/80 p-6 sm:p-7 shadow-sm">
                    <div class="flex items-center gap-2.5 pb-4 mb-4 border-b border-gray-100">
                        <div class="w-8 h-8 rounded-lg bg-brand-gold/15 text-brand-gold-dark flex items-center justify-center text-sm">
                            <i class="fa-solid fa-location-dot"></i>
                        </div>
                        <h3 class="font-bold text-brand-dark text-base">Alamat Tujuan Pengiriman</h3>
                    </div>
                    
                    @php
                        $shippingData = $order->meta['shipping_address'] ?? [];
                        $customerData = $order->meta['customer'] ?? [];
                        
                        $recipientName = $shippingData['recipient_name'] ?? $customerData['name'] ?? $order->customer?->name ?? 'Pelanggan';
                        $recipientPhone = $shippingData['phone'] ?? $customerData['phone'] ?? $order->customer?->phone ?? '-';
                        $addressText = $shippingData['address'] ?? $customerData['address'] ?? $order->shippingAddressRelation?->address ?? $order->customer?->address ?? '-';
                        $subDistrict = $shippingData['sub_district'] ?? '';
                        $city = $shippingData['city'] ?? '';
                        $province = $shippingData['province'] ?? '';
                        $postalCode = $shippingData['postal_code'] ?? $customerData['postal_code'] ?? '';
                        
                        $fullAddress = trim($addressText . ($subDistrict ? ', Kec. ' . $subDistrict : '') . ($city ? ', ' . $city : '') . ($province ? ', ' . $province : '') . ($postalCode ? ' ' . $postalCode : ''));
                        $courierName = $order->courier?->name ?? strtoupper($order->meta['courier'] ?? 'Ekspedisi');
                    @endphp
                    
                    <div class="flex flex-col sm:flex-row sm:items-baseline justify-between gap-1 text-sm mb-2">
                        <p class="font-bold text-brand-dark text-base">{{ $recipientName }} <span class="font-normal text-gray-500 text-sm">({{ $recipientPhone }})</span></p>
                        <span class="text-xs font-semibold text-brand-gold-dark bg-brand-gold/10 border border-brand-gold/20 px-2.5 py-0.5 rounded-full inline-block w-fit">
                            <i class="fa-solid fa-truck-fast text-[10px] mr-1"></i> Kurir: {{ $courierName }}
                        </span>
                    </div>
                    <p class="text-xs sm:text-sm text-gray-600 leading-relaxed">{{ $fullAddress }}</p>
                    
                    @if(!empty($order->notes))
                        <div class="mt-3 pt-3 border-t border-gray-100 flex items-start gap-2 text-xs text-gray-500">
                            <i class="fa-solid fa-note-sticky text-brand-gold mt-0.5"></i>
                            <p><strong>Catatan Pesanan:</strong> {{ $order->notes }}</p>
                        </div>
                    @endif
                </div>

            </div>
            
            <!-- Right Column: Cost Summary & Quick Actions -->
            <div class="lg:col-span-4 space-y-6">
                
                <!-- Cost Summary Card -->
                <div class="bg-white rounded-3xl border border-gray-200/80 p-6 shadow-sm sticky top-6">
                    <div class="flex items-center gap-2.5 pb-4 mb-4 border-b border-gray-100">
                        <div class="w-8 h-8 rounded-lg bg-brand-gold/15 text-brand-gold-dark flex items-center justify-center text-sm">
                            <i class="fa-solid fa-receipt"></i>
                        </div>
                        <h3 class="font-bold text-brand-dark text-base">Rincian Pembayaran</h3>
                    </div>

                    <div class="space-y-3 text-sm">
                        <div class="flex justify-between items-center text-gray-600">
                            <span>Metode Pembayaran</span>
                            <span class="font-bold text-brand-dark text-right">{{ ucwords(str_replace(['_', '-'], ' ', $paymentMethod)) }}</span>
                        </div>

                        <div class="flex justify-between items-center text-gray-600">
                            <span>Subtotal Produk</span>
                            <span class="font-semibold text-gray-800">Rp {{ number_format($order->subtotal ?? 0, 0, ',', '.') }}</span>
                        </div>
                        
                        @if(($order->shipping_cost ?? 0) > 0)
                            <div class="flex justify-between items-center text-gray-600">
                                <span>Ongkos Kirim</span>
                                <span class="font-semibold text-gray-800">Rp {{ number_format($order->shipping_cost, 0, ',', '.') }}</span>
                            </div>
                        @endif
                        
                        @if(($order->transaction_fee ?? 0) > 0)
                            <div class="flex justify-between items-center text-gray-600">
                                <span>Biaya Layanan</span>
                                <span class="font-semibold text-gray-800">Rp {{ number_format($order->transaction_fee, 0, ',', '.') }}</span>
                            </div>
                        @endif
                        
                        @if(($order->discount ?? 0) > 0)
                            <div class="flex justify-between items-center text-red-600">
                                <span class="flex items-center gap-1"><i class="fa-solid fa-tag text-xs"></i> Diskon</span>
                                <span class="font-bold">- Rp {{ number_format($order->discount, 0, ',', '.') }}</span>
                            </div>
                        @endif

                        <div class="pt-4 border-t border-dashed border-gray-200 flex justify-between items-baseline">
                            <span class="font-bold text-brand-dark text-base">Total Pembayaran</span>
                            <span class="font-black text-2xl text-brand-gold-dark font-serif">Rp {{ number_format($total, 0, ',', '.') }}</span>
                        </div>
                    </div>

                    <!-- Quick Action Buttons -->
                    <div class="mt-6 pt-5 border-t border-gray-100 space-y-3 no-print">
                        <button 
                            type="button" 
                            onclick="window.print()" 
                            class="w-full py-3.5 bg-brand-dark hover:bg-brand-darker text-brand-gold hover:text-white rounded-xl font-bold text-sm transition-all duration-200 shadow-md hover:shadow-lg flex items-center justify-center gap-2 cursor-pointer"
                        >
                            <i class="fa-solid fa-print"></i> Cetak / Simpan Invoice (PDF)
                        </button>

                        @if(session()->get('is_logged_in'))
                            <a href="{{ route('dashboard', ['tab' => 'orders']) }}" class="w-full py-3 text-center text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-xl font-bold text-xs transition-all block">
                                <i class="fa-solid fa-receipt mr-1"></i> Lihat Riwayat Pesanan
                            </a>
                        @endif

                        <a href="{{ route('home') }}" class="w-full py-2.5 text-center text-gray-500 hover:text-brand-dark text-xs font-semibold block transition-colors">
                            <i class="fa-solid fa-arrow-left mr-1 text-[10px]"></i> Belanja Produk Lainnya
                        </a>
                    </div>

                    <!-- Live Chat Support Helpdesk Card -->
                    <div class="mt-6 pt-5 border-t border-gray-100 no-print">
                        <button 
                            type="button" 
                            onclick="window.dispatchEvent(new CustomEvent('open-chat', { detail: { message: 'Halo Admin, saya ingin menanyakan pesanan dengan nomor: {{ $orderId }}' } }));"
                            class="w-full text-left flex items-center gap-3.5 p-4 rounded-2xl bg-brand-gold/10 hover:bg-brand-gold/20 border border-brand-gold/30 transition-all text-brand-dark group cursor-pointer shadow-2xs"
                        >
                            <div class="w-10 h-10 rounded-xl bg-brand-gold text-brand-dark flex items-center justify-center shrink-0 shadow-sm group-hover:scale-105 transition-transform">
                                <i class="fa-solid fa-comments text-lg"></i>
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-2">
                                    <span class="font-bold text-xs block text-brand-dark">Butuh Bantuan Pesanan?</span>
                                    <span class="inline-flex items-center gap-1 text-[10px] font-bold text-emerald-700 bg-emerald-100 px-1.5 py-0.2 rounded-full">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span> Online
                                    </span>
                                </div>
                                <span class="text-[11px] text-gray-600 block mt-0.5">Tanyakan langsung via Live Chat Customer Service</span>
                            </div>
                            <i class="fa-solid fa-chevron-right text-xs text-brand-gold group-hover:translate-x-0.5 transition-transform shrink-0"></i>
                        </button>
                    </div>

                    <!-- Security & Trust Badges -->
                    <div class="mt-5 pt-4 border-t border-gray-100 space-y-2 text-[11px] text-gray-400 no-print">
                        <div class="flex items-center gap-2">
                            <i class="fa-solid fa-shield-halved text-emerald-600"></i>
                            <span>Pesanan Anda dilindungi jaminan garansi resmi</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <i class="fa-solid fa-truck-ramp-box text-brand-gold"></i>
                            <span>Barang dikemas rapi dengan standar keamanan tinggi</span>
                        </div>
                    </div>
                </div>
                
            </div>
            
        </div>
    </div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Hapus data form checkout agar pesanan berikutnya tidak terisi data lama
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