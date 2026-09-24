@extends('frontend.layouts.app')

@section('title', 'Tracking Order - IMG')
@section('robots', 'noindex,nofollow')

@section('content')
    @php
        $formatRupiah = fn($value) => 'Rp ' . number_format((float) $value, 0, ',', '.');
    @endphp

    <div class="container mx-auto px-4 md:px-6 py-12 min-h-[70vh] font-sans">
        <div class="mb-8">
            <a href="{{ route('home') }}" class="text-sm text-brand-gold font-semibold hover:underline">
                <i class="fa-solid fa-arrow-left w-4 h-4 mr-1"></i>
                Kembali ke Beranda
            </a>
            <h1 class="text-3xl md:text-4xl font-extrabold text-brand-dark mt-4 font-serif">Order Status Tracking</h1>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-1">
                <div class="bg-white border border-brand-muted rounded-3xl p-6 shadow-sm">
                    <h2 class="text-xl font-bold text-brand-dark mb-4">Cari Pesanan</h2>
                    <form action="{{ route('order.tracking') }}" method="GET" class="space-y-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Order ID</label>
                            <input type="text" name="order_id" value="{{ $orderId }}" placeholder="Contoh: ORD-20260619-1234" class="w-full px-4 py-3 border border-brand-muted rounded-xl focus:outline-none focus:border-brand-gold" />
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Email Pembeli</label>
                            <input type="email" name="email" value="{{ $email }}" placeholder="email@contoh.com" class="w-full px-4 py-3 border border-brand-muted rounded-xl focus:outline-none focus:border-brand-gold" />
                        </div>
                        <button type="submit" class="w-full py-3 bg-brand-dark text-brand-gold rounded-xl font-bold hover:bg-brand-darker transition-colors">
                            Lacak Pesanan
                        </button>
                    </form>

                    @if(session()->get('is_logged_in') && !$order)
                        <p class="text-sm text-gray-500 mt-4">Login aktif, tetapi belum ada pesanan yang ditemukan untuk akun ini.</p>
                    @endif
                </div>
            </div>

            <div class="lg:col-span-2 space-y-6">
                @if(!$order)
                    <div class="bg-white border border-brand-muted rounded-3xl p-8 text-center">
                        <div class="w-16 h-16 bg-brand-light rounded-full flex items-center justify-center text-brand-gold mx-auto mb-4">
                            <i class="fa-solid fa-magnifying-glass-location w-7 h-7"></i>
                        </div>
                        <h2 class="text-xl font-bold text-brand-dark">Belum ada pesanan dipilih</h2>
                        <p class="text-gray-500 mt-2">Masukkan Order ID dan email pembeli untuk melihat status tracking.</p>
                        <a href="{{ route('order.tracking', ['dummy' => 1]) }}" class="inline-flex mt-4 px-5 py-3 bg-brand-gold text-brand-dark rounded-xl font-bold hover:bg-brand-dark hover:text-white transition-colors">
                            Lihat Dummy Tracking
                        </a>
                    </div>
                @else
                    <div class="bg-white border border-brand-muted rounded-3xl p-6 shadow-sm">
                        <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-4">
                            <div>
                                <p class="text-xs text-gray-400 uppercase tracking-widest font-bold">Order ID</p>
                                <h2 class="text-2xl font-extrabold text-brand-dark mt-1">{{ $order->order_number ?? $order->id ?? '-' }}</h2>
                                <p class="text-sm text-gray-500 mt-2">Dibuat {{ $order->created_at ? $order->created_at->format('d M Y H:i') : '-' }}</p>
                            </div>
                            <span class="inline-flex items-center rounded-full {{ $shipment ? 'bg-brand-gold/15 text-brand-gold-dark' : 'bg-gray-100 text-gray-500' }} px-4 py-2 text-xs font-extrabold uppercase tracking-wider">
                                {{ $shipment ? 'Shipped' : 'Belum Dikirim' }}
                            </span>
                            @if(str_contains($order->id, 'DUMMY'))
                                <span class="inline-flex items-center rounded-full bg-red-50 px-4 py-2 text-xs font-extrabold uppercase tracking-wider text-red-600">
                                    Dummy
                                </span>
                            @endif
                        </div>

                        @php
                            $courierName = $order->courier?->name 
                                ?? data_get($order->meta, 'biteship_shipment.courier.company') 
                                ?? data_get($order->meta, 'biteship_payload.courier_company') 
                                ?? ($shipment['courier'] ?? null);
                            $voucherNom = (float) ($order->voucher_nominal ?? 0);
                        @endphp
                        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 mt-6">
                            <div class="rounded-2xl bg-brand-light p-4">
                                <p class="text-[10px] text-gray-400 uppercase tracking-widest font-bold">Nama</p>
                                <p class="font-semibold text-brand-dark text-sm mt-1 truncate" title="{{ $order->customer->name ?? '-' }}">{{ $order->customer->name ?? '-' }}</p>
                            </div>
                            <div class="rounded-2xl bg-brand-light p-4">
                                <p class="text-[10px] text-gray-400 uppercase tracking-widest font-bold">Email</p>
                                <p class="font-semibold text-brand-dark text-sm mt-1 truncate" title="{{ $order->customer->email ?? '-' }}">{{ $order->customer->email ?? '-' }}</p>
                            </div>
                            <div class="rounded-2xl bg-brand-light p-4">
                                <p class="text-[10px] text-gray-400 uppercase tracking-widest font-bold">Kurir</p>
                                <p class="font-semibold text-brand-dark text-sm mt-1 uppercase">{{ $courierName ? strtoupper($courierName) : '-' }}</p>
                            </div>
                            <div class="rounded-2xl bg-brand-light p-4">
                                <p class="text-[10px] text-gray-400 uppercase tracking-widest font-bold">Ongkir</p>
                                <p class="font-semibold text-brand-dark text-sm mt-1">{{ $formatRupiah($order->shipping_cost ?? 0) }}</p>
                            </div>
                            <div class="rounded-2xl bg-brand-light p-4">
                                <p class="text-[10px] text-gray-400 uppercase tracking-widest font-bold">Voucher</p>
                                @if($voucherNom > 0)
                                    <p class="font-semibold text-emerald-600 text-sm mt-1">-{{ $formatRupiah($voucherNom) }}</p>
                                @else
                                    <p class="font-semibold text-gray-400 text-sm mt-1">-</p>
                                @endif
                            </div>
                            <div class="rounded-2xl bg-brand-light p-4">
                                <p class="text-[10px] text-gray-400 uppercase tracking-widest font-bold">Total</p>
                                <p class="font-extrabold text-brand-dark text-sm mt-1">{{ $formatRupiah($order->total ?? 0) }}</p>
                            </div>
                        </div>
                    </div>

                    @php
                        $displayEta = $etaLabel ?? ($shipment['eta_label'] ?? null);
                        $etaSrc = $delivery->eta_source ?? ($shipment['eta_source'] ?? null);
                        $etaNotes = $delivery->eta_notes ?? ($shipment['eta_notes'] ?? null);
                    @endphp

                    @if(!empty($displayEta))
                        <div class="bg-gradient-to-r from-blue-50 to-indigo-50/60 border border-blue-200/80 rounded-3xl p-5 shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                            <div class="flex items-center gap-3.5">
                                <div class="w-12 h-12 rounded-2xl bg-blue-600 text-white flex items-center justify-center text-lg shadow-sm shrink-0">
                                    <i class="fa-solid fa-truck-fast"></i>
                                </div>
                                <div>
                                    <p class="text-xs font-bold text-blue-600 uppercase tracking-wider">Estimasi Tiba (ETA)</p>
                                    <p class="text-lg sm:text-xl font-extrabold text-blue-950 mt-0.5">{{ $displayEta }}</p>
                                    @if(!empty($etaNotes) && $etaNotes !== $displayEta)
                                        <p class="text-xs text-blue-700/80 mt-0.5">{{ $etaNotes }}</p>
                                    @endif
                                </div>
                            </div>
                            <div class="flex items-center gap-2 self-start sm:self-auto">
                                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold bg-white text-blue-800 border border-blue-200 shadow-2xs">
                                    <i class="fa-solid {{ $etaSrc === 'biteship' ? 'fa-satellite-dish text-emerald-600' : ($etaSrc === 'store' ? 'fa-store text-amber-600' : 'fa-clock text-blue-600') }} text-[11px]"></i>
                                    {{ $etaSrc === 'biteship' ? 'Biteship (Ekspedisi)' : ($etaSrc === 'store' ? 'Jadwal Toko' : 'Estimasi Pengiriman') }}
                                </span>
                            </div>
                        </div>
                    @endif

                    @if($shipment)
                        <div class="bg-white border border-brand-muted rounded-3xl p-6 shadow-sm">
                            <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-4">
                                <div>
                                    <p class="text-xs text-gray-400 uppercase tracking-widest font-bold">Detail Pengiriman</p>
                                    <h2 class="text-2xl font-extrabold text-brand-dark mt-1">Status dari Ekspedisi</h2>
                                    <p class="text-sm text-gray-500 mt-2">Timeline ini menampilkan pembaruan status pelacakan pengiriman resi.</p>
                                </div>
                                @if(!empty($shipment['waybill_id']))
                                    <div class="bg-brand-light/70 border border-brand-gold/30 rounded-2xl p-3 px-4 shrink-0">
                                        <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Nomor Resi / Waybill</p>
                                        <div class="flex items-center gap-2 mt-0.5" x-data="{ copied: false }">
                                            <span class="font-mono font-extrabold text-brand-dark text-base select-all">{{ $shipment['waybill_id'] }}</span>
                                            <button 
                                                type="button" 
                                                @click="navigator.clipboard.writeText('{{ $shipment['waybill_id'] }}'); copied = true; setTimeout(() => copied = false, 2000)" 
                                                class="text-brand-gold-dark hover:text-brand-dark transition-colors text-xs" 
                                                title="Salin Nomor Resi">
                                                <i class="fa-regular" :class="copied ? 'fa-check text-green-600' : 'fa-copy'"></i>
                                            </button>
                                        </div>
                                        @if(!empty($shipment['courier']))
                                            <p class="text-xs text-gray-500 font-medium mt-0.5">{{ $shipment['courier'] }}</p>
                                        @endif
                                    </div>
                                @endif
                            </div>

                            <div class="mt-8 space-y-4">
                                @foreach($shipment['events'] as $index => $event)
                                    <div class="flex gap-4">
                                        <div class="flex flex-col items-center">
                                            <div class="w-8 h-8 rounded-full flex items-center justify-center {{ $index === 0 ? 'bg-brand-gold text-white' : 'bg-brand-light text-brand-gold-dark' }}">
                                                <i class="fa-solid {{ $index === 0 ? 'fa-location-dot' : 'fa-check' }} w-4 h-4"></i>
                                            </div>
                                            @if(!$loop->last)
                                                <div class="w-px h-full bg-brand-light my-1"></div>
                                            @endif
                                        </div>
                                        <div class="pb-6 flex-1" x-data="{ showPayload: false }">
                                            <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-1">
                                                <div>
                                                    <p class="font-bold text-brand-dark">{{ $event->title }}</p>
                                                    <p class="text-sm text-gray-500 mt-1">{{ $event->location }}</p>
                                                </div>
                                                <span class="inline-flex w-fit items-center rounded-full bg-brand-gold/15 px-3 py-1 text-[10px] font-extrabold uppercase tracking-wider text-brand-gold-dark">
                                                    {{ $event->status }}
                                                </span>
                                            </div>
                                            <p class="text-sm text-gray-600 mt-2">{{ $event->description }}</p>
                                            <div class="flex items-center justify-between mt-2">
                                                <p class="text-xs text-gray-400">{{ $event->date->format('d M Y H:i') }}</p>
                                                @if(!empty($event->payload))
                                                    <button type="button" @click="showPayload = !showPayload" class="text-xs font-semibold text-brand-gold-dark hover:underline inline-flex items-center gap-1">
                                                        <i class="fa-solid fa-code"></i>
                                                        <span x-text="showPayload ? 'Tutup Payload' : 'Lihat Payload Resi'">Lihat Payload Resi</span>
                                                    </button>
                                                @endif
                                            </div>
                                            @if(!empty($event->payload))
                                                <div x-show="showPayload" x-cloak class="mt-3 bg-gray-900 text-gray-100 rounded-xl p-3 text-xs font-mono overflow-x-auto shadow-inner border border-gray-800">
                                                    <pre class="whitespace-pre-wrap">{{ json_encode($event->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            @if(!empty($shipment['latest_payload']))
                                <div class="mt-6 pt-6 border-t border-brand-muted/60" x-data="{ openRaw: false }">
                                    <button type="button" @click="openRaw = !openRaw" class="w-full flex items-center justify-between text-xs font-bold text-gray-500 hover:text-brand-dark transition-colors py-1">
                                        <span class="flex items-center gap-2">
                                            <i class="fa-solid fa-file-lines text-brand-gold"></i>
                                            <span>Raw Payload Tracking Resi (Biteship / Ekspedisi)</span>
                                        </span>
                                        <i class="fa-solid text-xs transition-transform duration-200" :class="openRaw ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
                                    </button>
                                    <div x-show="openRaw" x-cloak class="mt-3 bg-gray-900 text-emerald-400 rounded-2xl p-4 text-xs font-mono overflow-x-auto shadow-inner border border-gray-800">
                                        <pre class="whitespace-pre-wrap">{{ json_encode($shipment['latest_payload'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                                    </div>
                                </div>
                            @endif
                        </div>
                    @else
                        <div class="bg-white border border-brand-muted rounded-3xl p-6 shadow-sm">
                            <p class="text-sm text-gray-500">Detail pengiriman belum tersedia sampai pesanan masuk status shipped.</p>
                        </div>
                    @endif

                    <div class="bg-white border border-brand-muted rounded-3xl p-6 shadow-sm">
                        <h2 class="text-xl font-bold text-brand-dark mb-5">Detail Pesanan</h2>
                        <div class="space-y-5">
                            @foreach($order->items as $item)
                                @php
                                    $variantName = $item->variant?->name 
                                        ?? $item->variant?->variant_name
                                        ?? data_get($item->meta, 'variant_name') 
                                        ?? data_get($item->meta, 'variation_name');
                                    $sku = $item->variant?->sku 
                                        ?? data_get($item->meta, 'sku') 
                                        ?? $item->product?->code;

                                    $basePrice = (float) ($item->variant?->base_price ?? data_get($item->meta, 'base_price') ?? 0);
                                    $sellPrice = (float) ($item->variant?->sell_price ?? data_get($item->meta, 'sell_price') ?? $item->unit_price ?? 0);
                                    $unitPrice = (float) ($item->unit_price ?? $sellPrice);
                                    $qty = max(1, (int) ($item->quantity ?? 1));

                                    $discNominal = (float) ($item->discount_nominal ?? 0);
                                    $discPercent = (float) ($item->discount_percent ?? 0);
                                    $adjAmount = (float) (data_get($item->meta, 'adjustment_amount') ?? 0);
                                    $hasBaseDiff = ($basePrice > 0 && $basePrice > $sellPrice);

                                    $subtotalRaw = (float) ($item->total ?? ($unitPrice * $qty));
                                    $totalDisc = ($subtotalRaw * $discPercent / 100) + $discNominal + $adjAmount;
                                    $finalPrice = max(0, $subtotalRaw - $totalDisc);
                                @endphp
                                <div class="flex flex-col sm:flex-row sm:items-start gap-4 border-b border-brand-muted pb-5 last:border-0 last:pb-0">
                                    <div class="w-16 h-16 sm:w-20 sm:h-20 rounded-2xl bg-brand-light overflow-hidden shrink-0 border border-brand-muted/60">
                                        @if($item->product?->thumbnail_url)
                                            <img src="{{ $item->product->thumbnail_url }}" alt="{{ $item->name }}" loading="lazy" decoding="async" class="w-full h-full object-cover">
                                        @else
                                            <div class="w-full h-full flex items-center justify-center text-brand-gold">
                                                <i class="fa-solid fa-box w-6 h-6"></i>
                                            </div>
                                        @endif
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <p class="font-bold text-brand-dark text-base">{{ $item->name }}</p>
                                        @if($variantName || $sku)
                                            <div class="flex flex-wrap items-center gap-1.5 mt-1">
                                                @if($variantName)
                                                    <span class="inline-flex items-center gap-1 text-[11px] font-semibold text-brand-gold-dark bg-brand-light/80 px-2 py-0.5 rounded-md border border-brand-gold/20">
                                                        <i class="fa-solid fa-layer-group text-[10px]"></i>
                                                        <span>Variasi: {{ $variantName }}</span>
                                                    </span>
                                                @endif
                                                @if($sku)
                                                    <span class="inline-flex items-center text-[10px] font-mono text-gray-500 bg-gray-50 px-1.5 py-0.5 rounded border border-gray-200">
                                                        SKU: {{ $sku }}
                                                    </span>
                                                @endif
                                            </div>
                                        @endif

                                        {{-- Breakdown Harga Produk: Asli, Jual, Diskon/Adjust, Final --}}
                                        <div class="mt-2.5 grid grid-cols-2 sm:grid-cols-4 gap-2 text-xs bg-slate-50/90 p-2.5 rounded-xl border border-slate-200/60">
                                            <div>
                                                <span class="text-gray-400 block text-[10px] uppercase font-bold">Harga Asli</span>
                                                <span class="font-semibold text-gray-500 {{ $hasBaseDiff ? 'line-through' : '' }}">
                                                    {{ $formatRupiah($basePrice > 0 ? $basePrice : $sellPrice) }}
                                                </span>
                                            </div>
                                            <div>
                                                <span class="text-gray-400 block text-[10px] uppercase font-bold">Harga Jual</span>
                                                <span class="font-semibold text-brand-dark">
                                                    {{ $formatRupiah($sellPrice) }}
                                                </span>
                                            </div>
                                            <div>
                                                <span class="text-gray-400 block text-[10px] uppercase font-bold">Penyesuaian / Diskon</span>
                                                @if($hasBaseDiff || $discNominal > 0 || $discPercent > 0 || $adjAmount > 0)
                                                    <span class="font-semibold text-emerald-600">
                                                        @if($hasBaseDiff && $discNominal == 0 && $discPercent == 0 && $adjAmount == 0)
                                                            -{{ $formatRupiah($basePrice - $sellPrice) }}
                                                        @elseif($discPercent > 0)
                                                            -{{ $discPercent }}%
                                                        @elseif($discNominal > 0)
                                                            -{{ $formatRupiah($discNominal) }}
                                                        @elseif($adjAmount > 0)
                                                            -{{ $formatRupiah($adjAmount) }}
                                                        @endif
                                                    </span>
                                                @else
                                                    <span class="text-gray-400 font-medium">-</span>
                                                @endif
                                            </div>
                                            <div>
                                                <span class="text-gray-400 block text-[10px] uppercase font-bold">Qty × Unit</span>
                                                <span class="font-semibold text-brand-dark">
                                                    {{ $qty }} × {{ $formatRupiah($unitPrice) }}
                                                </span>
                                            </div>
                                        </div>

                                        @php
                                            $dispNotes = $item->item_notes ?? '';
                                            if (is_string($dispNotes) && (str_starts_with(trim($dispNotes), '{') || str_starts_with(trim($dispNotes), '['))) {
                                                $dNotes = json_decode(trim($dispNotes), true);
                                                $dispNotes = is_array($dNotes) ? ($dNotes['user_note'] ?? '') : '';
                                            }
                                        @endphp
                                        @if(!empty($dispNotes))
                                            <p class="mt-2 rounded-lg bg-brand-light p-2 text-xs text-gray-600">{{ $dispNotes }}</p>
                                        @endif
                                    </div>
                                    <div class="sm:text-right shrink-0 flex sm:flex-col justify-between items-center sm:items-end border-t sm:border-t-0 pt-2 sm:pt-0">
                                        <span class="text-xs text-gray-400 sm:block">Total Item:</span>
                                        <div>
                                            @if($totalDisc > 0 || ($basePrice * $qty > $finalPrice))
                                                <p class="text-xs text-gray-400 line-through sm:text-right">
                                                    {{ $formatRupiah($hasBaseDiff ? ($basePrice * $qty) : $subtotalRaw) }}
                                                </p>
                                            @endif
                                            <p class="font-extrabold text-base sm:text-lg text-brand-dark">{{ $formatRupiah($finalPrice) }}</p>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        {{-- Ringkasan Biaya Keseluruhan --}}
                        <div class="mt-6 pt-5 border-t border-brand-muted/80 space-y-2 text-sm">
                            <div class="flex justify-between text-gray-600">
                                <span>Subtotal Produk</span>
                                <span class="font-semibold text-brand-dark">{{ $formatRupiah($order->subtotal ?? 0) }}</span>
                            </div>
                            <div class="flex justify-between text-gray-600">
                                <span>Biaya Pengiriman ({{ $courierName ? strtoupper($courierName) : 'Kurir' }})</span>
                                <span class="font-semibold text-brand-dark">{{ $formatRupiah($order->shipping_cost ?? 0) }}</span>
                            </div>
                            @if(($order->shipping_cost_subsidy ?? 0) > 0)
                                <div class="flex justify-between text-emerald-600">
                                    <span>Subsidi Pengiriman</span>
                                    <span class="font-semibold">-{{ $formatRupiah($order->shipping_cost_subsidy) }}</span>
                                </div>
                            @endif
                            @if(($order->voucher_nominal ?? 0) > 0)
                                <div class="flex justify-between text-emerald-600">
                                    <span>Diskon Voucher</span>
                                    <span class="font-semibold">-{{ $formatRupiah($order->voucher_nominal) }}</span>
                                </div>
                            @endif
                            @if(($order->tax ?? 0) > 0)
                                <div class="flex justify-between text-gray-600">
                                    <span>Pajak (Tax)</span>
                                    <span class="font-semibold text-brand-dark">{{ $formatRupiah($order->tax) }}</span>
                                </div>
                            @endif
                            @if(($order->transaction_fee ?? 0) > 0)
                                <div class="flex justify-between text-gray-600">
                                    <span>Biaya Transaksi</span>
                                    <span class="font-semibold text-brand-dark">{{ $formatRupiah($order->transaction_fee) }}</span>
                                </div>
                            @endif
                            <div class="flex justify-between text-base font-extrabold text-brand-dark pt-3 border-t border-dashed border-brand-muted">
                                <span>Total Pembayaran</span>
                                <span class="text-brand-gold-dark text-lg">{{ $formatRupiah($order->total ?? 0) }}</span>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
