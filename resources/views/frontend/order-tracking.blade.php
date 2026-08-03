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
                                <h2 class="text-2xl font-extrabold text-brand-dark mt-1">{{ $order->order_number }}</h2>
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

                        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mt-6">
                            <div class="rounded-2xl bg-brand-light p-4">
                                <p class="text-xs text-gray-400 uppercase tracking-widest font-bold">Nama</p>
                                <p class="font-semibold text-brand-dark mt-1">{{ $order->customer->name ?? '-' }}</p>
                            </div>
                            <div class="rounded-2xl bg-brand-light p-4">
                                <p class="text-xs text-gray-400 uppercase tracking-widest font-bold">Email</p>
                                <p class="font-semibold text-brand-dark mt-1">{{ $order->customer->email ?? '-' }}</p>
                            </div>
                            <div class="rounded-2xl bg-brand-light p-4">
                                <p class="text-xs text-gray-400 uppercase tracking-widest font-bold">Total</p>
                                <p class="font-semibold text-brand-dark mt-1">{{ $formatRupiah($order->total ?? 0) }}</p>
                            </div>
                            <div class="rounded-2xl bg-brand-light p-4">
                                <p class="text-xs text-gray-400 uppercase tracking-widest font-bold">Items</p>
                                <p class="font-semibold text-brand-dark mt-1">{{ $order->items->count() }} Produk</p>
                            </div>
                        </div>
                    </div>

                    @if($shipment)
                        <div class="bg-white border border-brand-muted rounded-3xl p-6 shadow-sm">
                            <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-4">
                                <div>
                                    <p class="text-xs text-gray-400 uppercase tracking-widest font-bold">Detail Pengiriman</p>
                                    <h2 class="text-2xl font-extrabold text-brand-dark mt-1">Status dari Ekspedisi</h2>
                                    <p class="text-sm text-gray-500 mt-2">Timeline ini hanya menampilkan status setelah paket diterima ekspedisi.</p>
                                </div>
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
                                        <div class="pb-6 flex-1">
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
                                            <p class="text-xs text-gray-400 mt-2">{{ $event->date->format('d M Y H:i') }}</p>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @else
                        <div class="bg-white border border-brand-muted rounded-3xl p-6 shadow-sm">
                            <p class="text-sm text-gray-500">Detail pengiriman belum tersedia sampai pesanan masuk status shipped.</p>
                        </div>
                    @endif

                    <div class="bg-white border border-brand-muted rounded-3xl p-6 shadow-sm">
                        <h2 class="text-xl font-bold text-brand-dark mb-5">Detail Pesanan</h2>
                        <div class="space-y-4">
                            @foreach($order->items as $item)
                                <div class="flex gap-4 border-b border-brand-muted pb-4 last:border-0 last:pb-0">
                                    <div class="w-16 h-16 rounded-xl bg-brand-light overflow-hidden flex-shrink-0">
                                        @if($item->product?->thumbnail_url)
                                            <img src="{{ $item->product->thumbnail_url }}" alt="{{ $item->name }}" loading="lazy" decoding="async" class="w-full h-full object-cover">
                                        @else
                                            <div class="w-full h-full flex items-center justify-center text-brand-gold">
                                                <i class="fa-solid fa-box w-6 h-6"></i>
                                            </div>
                                        @endif
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <p class="font-bold text-brand-dark">{{ $item->name }}</p>
                                        <p class="text-sm text-gray-500 mt-1">Qty {{ $item->quantity }} × {{ $formatRupiah($item->unit_price ?? 0) }}</p>
                                        @if(($item->discount_percent ?? 0) > 0)
                                            <p class="text-xs text-red-500 mt-1">Diskon {{ $item->discount_percent }}%</p>
                                        @endif
                                        @if(($item->discount_nominal ?? 0) > 0)
                                            <p class="text-xs text-red-500 mt-1">Diskon: -{{ $formatRupiah($item->discount_nominal) }}</p>
                                        @endif
                                        @if(!empty($item->item_notes))
                                            <p class="mt-2 rounded-lg bg-brand-light p-2 text-xs text-gray-600">{{ $item->item_notes }}</p>
                                        @endif
                                    </div>
                                    <div class="text-right">
                                        @php
                                            $discountedPrice = max(0, ($item->total ?? 0) - (($item->total ?? 0) * ($item->discount_percent ?? 0) / 100) - ($item->discount_nominal ?? 0));
                                        @endphp
                                        @if(($item->discount_percent ?? 0) > 0 || ($item->discount_nominal ?? 0) > 0)
                                            <p class="text-xs text-gray-400 line-through">{{ $formatRupiah($item->total ?? 0) }}</p>
                                        @endif
                                        @if(($item->discount_percent ?? 0) > 0 && ($item->discount_nominal ?? 0) > 0)
                                            <p class="text-xs text-red-500">-{{ $formatRupiah(($item->total ?? 0) * ($item->discount_percent ?? 0) / 100 + ($item->discount_nominal ?? 0)) }}</p>
                                        @elseif(($item->discount_percent ?? 0) > 0)
                                            <p class="text-xs text-red-500">-{{ $formatRupiah(($item->total ?? 0) * ($item->discount_percent ?? 0) / 100) }}</p>
                                        @elseif(($item->discount_nominal ?? 0) > 0)
                                            <p class="text-xs text-red-500">-{{ $formatRupiah($item->discount_nominal) }}</p>
                                        @endif
                                        <p class="font-bold text-brand-dark">{{ $formatRupiah($discountedPrice) }}</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
