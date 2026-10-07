@extends('frontend.layouts.app')

@section('title', 'Pesanan Berhasil - ' . ($order?->order_number ?? 'IMG'))

@php
    $orderId = $order?->order_number ?? 'ORD.' . date('Ymd') . '.0001';
    $rawPmCode = $order?->payment_method ?? '-';
    $pmModel = \App\Models\PaymentMethod::where('code', $rawPmCode)->first()
        ?? \App\Models\PaymentMethod::find($rawPmCode);
    $paymentMethod = $order?->payment_method_name 
        ?? $pmModel?->name 
        ?? ($order?->meta['payment_method_name'] ?? null);
    if (empty($paymentMethod)) {
        $lowerPm = strtolower((string)$rawPmCode);
        if (in_array($lowerPm, ['transfer_manual', 'trf', 'manual'], true)) {
            $paymentMethod = 'Transfer Bank Manual';
        } elseif ($lowerPm === '014' || str_contains($lowerPm, 'bca')) {
            $paymentMethod = 'BCA Virtual Account';
        } elseif ($lowerPm === '008' || str_contains($lowerPm, 'mandiri')) {
            $paymentMethod = 'Mandiri Virtual Account';
        } elseif ($lowerPm === '002' || str_contains($lowerPm, 'bri')) {
            $paymentMethod = 'BRI Virtual Account';
        } elseif ($lowerPm === '009' || str_contains($lowerPm, 'bni')) {
            $paymentMethod = 'BNI Virtual Account';
        } elseif ($lowerPm === '022' || str_contains($lowerPm, 'cimb')) {
            $paymentMethod = 'CIMB Niaga Virtual Account';
        } elseif ($lowerPm === '011' || str_contains($lowerPm, 'danamon')) {
            $paymentMethod = 'Danamon Virtual Account';
        } elseif ($lowerPm === '016' || str_contains($lowerPm, 'maybank') || str_contains($lowerPm, 'bii')) {
            $paymentMethod = 'Maybank Virtual Account';
        } elseif ($lowerPm === '013' || str_contains($lowerPm, 'permata')) {
            $paymentMethod = 'Permata Virtual Account';
        } elseif (str_contains($lowerPm, 'gopay')) {
            $paymentMethod = 'GoPay';
        } elseif (str_contains($lowerPm, 'ovo')) {
            $paymentMethod = 'OVO';
        } elseif (str_contains($lowerPm, 'qris')) {
            $paymentMethod = 'QRIS';
        } elseif (str_contains($lowerPm, 'credit') || str_contains($lowerPm, 'card')) {
            $paymentMethod = 'Kartu Kredit / Debit';
        } else {
            $paymentMethod = ucwords(str_replace(['_', '-'], ' ', (string)$rawPmCode));
        }
    }
    $total = $order?->total ?? 0;
    $status = $order?->status ?? 1;
    $statusLabel = \App\Models\Frontend\Order::statusLabels()[$status] ?? 'Menunggu Pembayaran';
    $statusBadge = $order?->getStatusBadgeClassAttribute() ?? 'bg-yellow-100 text-yellow-700';
    $items = $order?->items;
    if (empty($items) || count($items) === 0) {
        $items = \App\Models\Frontend\Order\OrderItem::where('order_id', $order?->id)->get();
    }
    if ((empty($items) || count($items) === 0) && !empty($order?->meta['items'])) {
        $items = collect($order->meta['items'])->map(function($raw) {
            $unitPrice = (float)($raw['sell_price'] ?? ($raw['unit_price'] ?? 0));
            $qty = (int)($raw['quantity'] ?? 1);
            return (object) [
                'name' => $raw['name'] ?? 'Produk',
                'quantity' => $qty,
                'unit_price' => $unitPrice,
                'total' => (float)($raw['total'] ?? ($unitPrice * $qty)),
                'discount_nominal' => (float)($raw['discount_nominal'] ?? 0),
                'discount_percent' => (float)($raw['discount_percent'] ?? 0),
                'item_notes' => $raw['item_note'] ?? '',
                'meta' => ['image' => $raw['image'] ?? ''],
                'product' => null,
            ];
        });
    }

    $paymentStartedAt = !empty($order?->meta['payment_started_at']) 
        ? \Carbon\Carbon::parse($order->meta['payment_started_at']) 
        : ($order?->created_at ?? now());
    $expireAt = (clone $paymentStartedAt)->addHours(24);
    $remainingSeconds = max(0, (int) now()->diffInSeconds($expireAt, false));
    $initH = str_pad((string) floor($remainingSeconds / 3600), 2, '0', STR_PAD_LEFT);
    $initM = str_pad((string) floor(($remainingSeconds % 3600) / 60), 2, '0', STR_PAD_LEFT);
    $initS = str_pad((string) ($remainingSeconds % 60), 2, '0', STR_PAD_LEFT);
    $initialCountdownText = "{$initH}:{$initM}:{$initS}";
@endphp

@push('styles')
<style>
@media print {
    @page {
        size: auto;
        margin: 10mm 12mm;
    }

    /* 1. Reset root layout and prevent overflow clipping */
    html, body {
        background: #ffffff !important;
        background-color: #ffffff !important;
        color: #111827 !important;
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif !important;
        min-height: auto !important;
        height: auto !important;
        max-height: none !important;
        overflow: visible !important;
        margin: 0 !important;
        padding: 0 !important;
        display: block !important;
        position: static !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }

    main, #main-content {
        display: block !important;
        height: auto !important;
        min-height: auto !important;
        max-height: none !important;
        overflow: visible !important;
        margin: 0 !important;
        padding: 0 !important;
        position: static !important;
        float: none !important;
    }

    /* 2. Hide all non-printable chrome and fixed overlays */
    header, nav, footer, aside, #floating-whatsapp, .no-print, [x-data*="toast"], 
    #loading-overlay, .loading-overlay, div.fixed, [role="dialog"], #auth-modal, #cart-drawer, #review-modal,
    button:not(.print-keep), .sticky, a[href*="whatsapp"] {
        display: none !important;
    }

    /* 3. Make wrappers full width and linear */
    .container {
        max-width: 100% !important;
        width: 100% !important;
        margin: 0 !important;
        padding: 0 !important;
        min-height: auto !important;
    }

    .print-receipt-wrapper {
        display: block !important;
        max-width: 100% !important;
        width: 100% !important;
        margin: 0 !important;
        padding: 0 !important;
        border: none !important;
        box-shadow: none !important;
    }

    .lg\:col-span-8, .lg\:col-span-4 {
        display: block !important;
        width: 100% !important;
        max-width: 100% !important;
        position: static !important;
        padding: 0 !important;
        margin: 0 0 16px 0 !important;
    }

    /* 4. Ensure cards break cleanly across pages */
    .bg-white {
        background: #ffffff !important;
        border: 1px solid #e5e7eb !important;
        box-shadow: none !important;
        border-radius: 8px !important;
        page-break-inside: avoid !important;
        break-inside: avoid !important;
    }

    .print-invoice-header {
        display: block !important;
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

        <!-- Official Printable Invoice Header (Visible ONLY on print preview) -->
        <div class="print-invoice-header hidden mb-6 pb-4 border-b-2 border-gray-900">
            <div class="flex justify-between items-start">
                <div>
                    <h1 class="text-2xl font-black tracking-tight text-gray-900 font-serif">IMG STORE</h1>
                    <p class="text-xs text-gray-600 font-semibold tracking-wide">INVOICE PEMBELIAN RESMI</p>
                    <p class="text-[11px] text-gray-500 mt-0.5">Dokumen ini merupakan bukti transaksi yang sah</p>
                </div>
                <div class="text-right">
                    <p class="text-sm font-extrabold text-gray-900">NO. PESANAN: <span class="font-mono text-base">{{ $order->order_number }}</span></p>
                    <p class="text-xs text-gray-600 mt-0.5">Tanggal: {{ $order->created_at ? $order->created_at->format('d/m/Y, H:i') : date('d/m/Y, H:i') }} WIB</p>
                    <span class="inline-block mt-1 px-2.5 py-0.5 rounded text-xs font-bold uppercase tracking-wider border border-gray-400 bg-gray-100 text-gray-800">
                        STATUS: {{ $statusLabel }}
                    </span>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 max-w-6xl mx-auto print-receipt-wrapper">
            
            <!-- Left Column: Order Journey, Products, Delivery Details -->
            <div class="lg:col-span-8 space-y-6">
                
                <!-- Hero Banner Card -->
                <div class="bg-white rounded-3xl shadow-sm border border-gray-200/80 overflow-hidden">
                    <div class="text-center pt-8 pb-6 px-6 sm:px-8 no-print">
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
                        <div class="bg-gradient-to-r from-amber-50 via-amber-50/80 to-amber-50 border-y border-amber-200/80 p-4 sm:p-5 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 no-print">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-full bg-amber-100 text-amber-700 flex items-center justify-center shrink-0">
                                    <i class="fa-solid fa-clock text-sm"></i>
                                </div>
                                <div>
                                    <p class="text-brand-dark font-bold text-xs sm:text-sm">Batas Waktu Pembayaran</p>
                                    <p class="text-[11px] sm:text-xs text-gray-500">Selesaikan pembayaran sebelum batas waktu agar pesanan tidak otomatis dibatalkan.</p>
                                </div>
                            </div>
                            <div class="self-end sm:self-center font-mono font-black text-lg sm:text-xl text-red-600 bg-white px-3 py-1.5 rounded-xl shadow-2xs border border-red-200 tracking-widest shrink-0" id="thankyou-countdown" data-remaining="{{ $remainingSeconds }}" data-created="{{ $paymentStartedAt->toIso8601String() }}">
                                {{ $initialCountdownText }}
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
                                        {{-- <p class="text-xs text-gray-500">Saluran: {{ ucwords(str_replace(['_', '-'], ' ', $paymentMethod)) }}</p> --}}
                                    </div>
                                </div>
                                <span class="text-[11px] font-bold text-blue-700 bg-blue-100 px-2.5 py-0.5 rounded-full">Otomatis / Instant</span>
                            </div>

                            <div class="bg-white rounded-2xl p-5 border border-blue-100 shadow-2xs space-y-4">
                                <div>
                                    <span class="text-xs text-gray-500 font-semibold block mb-1">Nomor Virtual Account:</span>
                                    <div class="flex items-center justify-between gap-2 p-3 bg-blue-50/40 rounded-xl border border-blue-100">
                                        <span class="font-mono font-black text-blue-800 text-xl sm:text-2xl tracking-widest select-all" id="va-number-text">{{ $vaNumber }}</span>
                                        <button type="button" id="btn-copy-va" onclick="copyVaNumber('{{ $vaNumber }}', this)" class="px-3.5 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-xs font-bold transition-all inline-flex items-center gap-1.5 shadow-2xs shrink-0 cursor-pointer">
                                            <i class="fa-regular fa-copy text-xs"></i> <span>Salin VA</span>
                                        </button>
                                    </div>
                                    <div id="va-copy-success-message" class="hidden mt-2 text-xs font-semibold text-emerald-800 bg-emerald-50 border border-emerald-200 px-3 py-1.5 rounded-lg flex items-center gap-1.5">
                                        <i class="fa-solid fa-circle-check text-emerald-600"></i>
                                        <span>Nomor Virtual Account <strong>{{ $vaNumber }}</strong> berhasil disalin ke clipboard!</span>
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
                                        <span class="text-gray-400">Harga Asli: Rp {{ number_format($unitPrice, 0, ',', '.') }}</span>
                                        @if($discNom > 0)
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-red-50 text-red-600 font-bold text-[11px]">
                                                <span>Diskon {{ (float) $discPct }}%</span>
                                                <span>(-Rp {{ number_format($discNom, 0, ',', '.') }})</span>
                                            </span>
                                        @endif
                                    </div>
                                </div>
                                <div class="text-right shrink-0">
                                    @if($discNom > 0 && $subtotalAsli > $item->total)
                                        <span class="text-xs text-gray-400 line-through block">Rp {{ number_format($subtotalAsli, 0, ',', '.') }}</span>
                                    @endif
                                    <span class="font-black text-sm sm:text-base text-brand-dark">Rp {{ number_format($item->total, 0, ',', '.') }}</span>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    @php
                        $orderMeta = is_array($order?->meta) ? $order->meta : (json_decode($order?->meta ?? '[]', true) ?: []);
                        
                        // Subtotal Produk Asli
                        $originalCartTotal = (float) ($orderMeta['original_cart_total'] ?? 0);
                        if ($originalCartTotal <= 0) {
                            $originalCartTotal = (float) collect($items)->sum(function($it) {
                                $meta = is_array($it->meta) ? $it->meta : (json_decode($it->meta ?? '[]', true) ?: []);
                                $unitP = (float) ($it->unit_price ?? $meta['original_price'] ?? $it->sell_price ?? 0);
                                return $unitP * (int) ($it->quantity ?? 1);
                            });
                        }
                        if ($originalCartTotal <= 0) {
                            $originalCartTotal = (float) ($order?->subtotal ?? 0);
                        }

                        // Diskon Promo & Volume
                        $priceProductSettingDiscount = (float) ($orderMeta['price_product_setting_discount'] ?? 0);
                        $staticPromoDiscount = (float) ($orderMeta['total_static_discount'] ?? 0);
                        if ($staticPromoDiscount <= 0 && !empty($orderMeta['promo_discount'])) {
                            $staticPromoDiscount = max(0, (float) $orderMeta['promo_discount'] - $priceProductSettingDiscount);
                        }
                        
                        // Voucher Diskon Produk
                        $productVoucherDiscount = (float) ($orderMeta['product_voucher_discount'] ?? 0);
                        $shippingVoucherDiscount = (float) ($orderMeta['shipping_voucher_discount'] ?? ($order?->shipping_cost_subsidy ?? 0));
                        $voucherNominal = (float) ($order?->voucher_nominal ?? ($orderMeta['voucher_discount'] ?? 0));
                        
                        if ($productVoucherDiscount <= 0 && $voucherNominal > 0) {
                            $productVoucherDiscount = max(0, $voucherNominal - $shippingVoucherDiscount);
                        }
                        
                        $productVoucherCode = null;
                        if (!empty($orderMeta['applied_vouchers'])) {
                            $appliedProductVouchers = collect($orderMeta['applied_vouchers'])->filter(fn($av) => empty($av['is_shipping']));
                            $productVoucherCode = $appliedProductVouchers->pluck('code')->first();
                        }
                        if (empty($productVoucherCode) && !empty($orderMeta['voucher_codes'])) {
                            $productVoucherCode = collect($orderMeta['voucher_codes'])->filter(fn($c) => !str_contains(strtoupper($c), 'ONGKIR'))->first();
                        }
                        if (empty($productVoucherCode) && !empty($orderMeta['voucher_code'])) {
                            $productVoucherCode = $orderMeta['voucher_code'];
                        }

                        $shippingCost = (float) ($order?->shipping_cost ?? ($orderMeta['shipping_cost'] ?? 0));
                        $shippingServiceName = $orderMeta['shipping_service_name'] ?? null;
                        $courierDisplayName = strtoupper($orderMeta['courier'] ?? ($order?->courier?->name ?? 'Kurir'));
                        if ($shippingServiceName) {
                            $courierDisplayName .= ' (' . $shippingServiceName . ')';
                        }

                        $transactionFee = (float) ($order?->transaction_fee ?? 0);
                        $orderFinalTotal = (float) ($order?->total ?? 0);
                    @endphp

                    <!-- Summary / Sinkronisasi Total Pesanan -->
                    <div class="mt-6 pt-5 border-t border-dashed border-gray-200 space-y-2.5 text-xs sm:text-sm">
                        {{-- 1. Subtotal Produk --}}
                        <div class="flex justify-between items-center text-gray-600 gap-2">
                            <span class="min-w-0">Subtotal Produk</span>
                            <span class="font-semibold text-gray-800 shrink-0 whitespace-nowrap text-right">Rp {{ number_format($originalCartTotal, 0, ',', '.') }}</span>
                        </div>

                        {{-- 2. Diskon Promo Katalog --}}
                        @if($staticPromoDiscount > 0)
                            <div class="flex justify-between items-center text-red-600 gap-2">
                                <span class="flex items-center gap-1.5 min-w-0 truncate"><i class="fa-solid fa-tag text-xs text-red-500 shrink-0"></i> <span class="truncate">Diskon Promo</span></span>
                                <span class="font-semibold shrink-0 whitespace-nowrap text-right">- Rp {{ number_format($staticPromoDiscount, 0, ',', '.') }}</span>
                            </div>
                        @endif

                        {{-- 3. Diskon Volume Tier --}}
                        @if($priceProductSettingDiscount > 0)
                            <div class="flex justify-between items-center text-red-600 gap-2">
                                <span class="flex items-center gap-1.5 min-w-0 truncate"><i class="fa-solid fa-boxes-stacked text-xs text-red-500 shrink-0"></i> <span class="truncate">Diskon Volume</span></span>
                                <span class="font-semibold shrink-0 whitespace-nowrap text-right">- Rp {{ number_format($priceProductSettingDiscount, 0, ',', '.') }}</span>
                            </div>
                        @endif

                        {{-- 4. Voucher Diskon Produk --}}
                        @if($productVoucherDiscount > 0)
                            <div class="flex justify-between items-center text-emerald-700 bg-emerald-50/70 px-3 py-2 rounded-xl border border-emerald-200/60 gap-2">
                                <span class="flex items-center gap-1.5 min-w-0 font-medium truncate">
                                    <i class="fa-solid fa-ticket text-xs text-emerald-600 shrink-0"></i>
                                    <span class="truncate">Voucher Diskon{{ $productVoucherCode ? ' (' . $productVoucherCode . ')' : '' }}</span>
                                </span>
                                <span class="font-bold shrink-0 whitespace-nowrap text-right">- Rp {{ number_format($productVoucherDiscount, 0, ',', '.') }}</span>
                            </div>
                        @endif

                        {{-- 5. Biaya Pengiriman --}}
                        <div class="flex justify-between items-center text-gray-600 gap-2">
                            <span class="min-w-0 truncate">Biaya Pengiriman <span class="text-xs text-gray-500 font-normal">({{ $courierDisplayName }})</span></span>
                            <span class="font-semibold text-gray-800 shrink-0 whitespace-nowrap text-right">Rp {{ number_format($shippingCost, 0, ',', '.') }}</span>
                        </div>

                        {{-- 6. Voucher Gratis Ongkir --}}
                        @if($shippingVoucherDiscount > 0)
                            <div class="flex justify-between items-center text-emerald-700 bg-emerald-50/70 px-3 py-2 rounded-xl border border-emerald-200/60 gap-2">
                                <span class="flex items-center gap-1.5 min-w-0 font-medium truncate">
                                    <i class="fa-solid fa-truck-fast text-xs text-emerald-600 shrink-0"></i>
                                    <span class="truncate">Voucher Gratis Ongkir</span>
                                </span>
                                <span class="font-bold shrink-0 whitespace-nowrap text-right">- Rp {{ number_format($shippingVoucherDiscount, 0, ',', '.') }}</span>
                            </div>
                        @endif

                        {{-- 7. Biaya Layanan / Transaksi jika ada --}}
                        @if($transactionFee > 0)
                            <div class="flex justify-between items-center text-gray-600 gap-2">
                                <span class="min-w-0">Biaya Layanan</span>
                                <span class="font-semibold text-gray-800 shrink-0 whitespace-nowrap text-right">Rp {{ number_format($transactionFee, 0, ',', '.') }}</span>
                            </div>
                        @endif

                        {{-- 8. Total Pesanan (Sinkron dengan Total Pembayaran di kartu kanan) --}}
                        <div class="pt-3.5 mt-2 border-t-2 border-gray-100 flex justify-between items-baseline gap-2">
                            <div class="min-w-0">
                                <span class="font-bold text-brand-dark text-sm sm:text-base block truncate">Total Pesanan</span>
                                <span class="block text-[11px] text-gray-400 font-normal truncate">Sudah termasuk diskon & ongkos kirim</span>
                            </div>
                            <span class="font-black text-xl sm:text-2xl text-brand-gold-dark font-serif shrink-0 whitespace-nowrap text-right">
                                Rp {{ number_format($orderFinalTotal, 0, ',', '.') }}
                            </span>
                        </div>
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
                        $deliveryRecord = $order->delivery ?? \App\Models\Frontend\Shipping\Delivery::where('order_id', $order->id)->first();
                        $etaLabel = $deliveryRecord?->eta_label ?? ($order->meta['shipping_eta_label'] ?? ($order->meta['eta_label'] ?? null));
                    @endphp
                    
                    <div class="flex flex-col sm:flex-row sm:items-baseline justify-between gap-1 text-sm mb-2">
                        <p class="font-bold text-brand-dark text-base">{{ $recipientName }} <span class="font-normal text-gray-500 text-sm">({{ $recipientPhone }})</span></p>
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="text-xs font-semibold text-brand-gold-dark bg-brand-gold/10 border border-brand-gold/20 px-2.5 py-0.5 rounded-full inline-block w-fit">
                                <i class="fa-solid fa-truck-fast text-[10px] mr-1"></i> Kurir: {{ $courierName }}
                            </span>
                            @if(!empty($etaLabel))
                                <span class="text-xs font-semibold text-blue-700 bg-blue-50 border border-blue-200 px-2.5 py-0.5 rounded-full inline-block w-fit">
                                    <i class="fa-solid fa-clock text-[10px] mr-1"></i> Estimasi Tiba: {{ $etaLabel }}
                                </span>
                            @endif
                        </div>
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

                    <div class="space-y-3.5 text-sm">
                        <div class="flex justify-between items-center text-gray-600 gap-2">
                            <span class="min-w-0">Metode Pembayaran</span>
                            <span class="font-bold text-brand-dark text-right shrink-0">{{ $paymentMethod }}</span>
                        </div>

                        <div class="pt-3.5 border-t border-dashed border-gray-200 flex justify-between items-baseline gap-2">
                            <span class="font-bold text-brand-dark text-base min-w-0">Total Pembayaran</span>
                            <span class="font-black text-2xl text-brand-gold-dark font-serif shrink-0 whitespace-nowrap text-right">Rp {{ number_format($total, 0, ',', '.') }}</span>
                        </div>
                    </div>

                    <!-- Quick Action Buttons -->
                    <div class="mt-6 pt-5 border-t border-gray-100 space-y-3 no-print">
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
    (function() {
        try {
            sessionStorage.removeItem('checkout_form_data');
            localStorage.removeItem('checkout_form_data');
            localStorage.removeItem('selectedCartCoupon');
            localStorage.removeItem('selectedCartCoupons');
        } catch(e) {}
    })();

    window.copyVaNumber = function(text, btn) {
        if (!text) return;
        
        function onSuccess() {
            if (btn) {
                var originalHtml = btn.dataset.originalHtml || btn.innerHTML;
                btn.dataset.originalHtml = originalHtml;
                btn.innerHTML = '<i class="fa-solid fa-check text-xs"></i> <span>Tersalin!</span>';
                btn.classList.remove('bg-blue-600', 'hover:bg-blue-700');
                btn.classList.add('bg-emerald-600', 'hover:bg-emerald-700');
                
                setTimeout(function() {
                    btn.innerHTML = originalHtml;
                    btn.classList.remove('bg-emerald-600', 'hover:bg-emerald-700');
                    btn.classList.add('bg-blue-600', 'hover:bg-blue-700');
                }, 3000);
            }

            var successMsg = document.getElementById('va-copy-success-message');
            if (successMsg) {
                successMsg.classList.remove('hidden');
                setTimeout(function() {
                    successMsg.classList.add('hidden');
                }, 4000);
            }

            window.dispatchEvent(new CustomEvent('show-toast', { 
                detail: { type: 'success', message: 'Nomor Virtual Account ' + text + ' berhasil disalin!', duration: 3500 } 
            }));
        }

        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(text).then(onSuccess).catch(function() {
                fallbackCopyText(text, onSuccess);
            });
        } else {
            fallbackCopyText(text, onSuccess);
        }
    };

    function fallbackCopyText(text, cb) {
        var textArea = document.createElement("textarea");
        textArea.value = text;
        textArea.style.position = "fixed";
        textArea.style.top = "-9999px";
        textArea.style.left = "-9999px";
        document.body.appendChild(textArea);
        textArea.focus();
        textArea.select();
        try {
            document.execCommand('copy');
            if (cb) cb();
        } catch (err) {
            console.error('Fallback copy failed', err);
            window.dispatchEvent(new CustomEvent('show-toast', { 
                detail: { type: 'info', message: 'Silakan salin nomor VA secara manual: ' + text } 
            }));
        }
        document.body.removeChild(textArea);
    }
</script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        var countdownEl = document.getElementById('thankyou-countdown');
        if (!countdownEl) return;
        
        var remainingAttr = countdownEl.getAttribute('data-remaining');
        var remainingSeconds = remainingAttr !== null ? parseInt(remainingAttr, 10) : null;
        var startLocalTime = Date.now();
        
        var expireAt = null;
        if (remainingSeconds === null || isNaN(remainingSeconds)) {
            var createdStr = countdownEl.getAttribute('data-created');
            if (createdStr) {
                var createdAt = new Date(createdStr).getTime();
                expireAt = createdAt + (24 * 60 * 60 * 1000);
            }
        }
        
        function updateTimer() {
            var currentRemaining;
            if (remainingSeconds !== null && !isNaN(remainingSeconds)) {
                var elapsed = Math.floor((Date.now() - startLocalTime) / 1000);
                currentRemaining = remainingSeconds - elapsed;
            } else if (expireAt) {
                var distance = expireAt - Date.now();
                currentRemaining = Math.floor(distance / 1000);
            } else {
                return;
            }
            
            if (currentRemaining <= 0) {
                countdownEl.innerHTML = "00:00:00";
                countdownEl.classList.add('text-gray-400');
                countdownEl.classList.remove('text-red-600');
                return;
            }
            
            var hours = Math.floor(currentRemaining / 3600);
            var minutes = Math.floor((currentRemaining % 3600) / 60);
            var seconds = currentRemaining % 60;
            
            var hStr = hours < 10 ? "0" + hours : hours;
            var mStr = minutes < 10 ? "0" + minutes : minutes;
            var sStr = seconds < 10 ? "0" + seconds : seconds;
            
            countdownEl.innerHTML = hStr + ":" + mStr + ":" + sStr;
        }
        
        updateTimer();
        setInterval(updateTimer, 1000);

        try {
            localStorage.removeItem('selectedCartCoupon');
            localStorage.removeItem('selectedCartCoupons');
        } catch (e) {}
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