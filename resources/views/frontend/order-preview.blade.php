@extends('frontend.layouts.app')

@section('title', 'Preview Pesanan - IMG')
@section('robots', 'noindex,nofollow')

@section('content')
    @php
        $formatRupiah = fn($value) => 'Rp ' . number_format((float) $value, 0, ',', '.');
        $customer = $preview['customer'] ?? [];
        $items = $preview['items'] ?? [];
    @endphp

    <div class="container mx-auto px-4 md:px-6 py-12 min-h-[60vh] font-sans">
        <div class="mb-8">
            <a href="{{ route('checkout') }}" class="text-sm text-brand-gold font-semibold hover:underline">
                <i class="fa-solid fa-arrow-left w-4 h-4 mr-1"></i>
                Kembali ke Checkout
            </a>
            <h1 class="text-3xl font-extrabold text-brand-dark mt-4 font-serif">Preview Pesanan</h1>
            <p class="text-gray-500 mt-2">Pastikan data pembeli, alamat, produk, ongkir, dan total sudah benar sebelum lanjut ke pembayaran.</p>
        </div>

        <form action="{{ route('checkout.process') }}" method="POST" class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            @csrf
            <div class="lg:col-span-2 space-y-6">
                <div class="bg-white border border-brand-muted rounded-2xl p-6">
                    <h2 class="font-bold text-brand-dark mb-5">Data Pembeli</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div>
                            <p class="text-xs text-gray-400 uppercase tracking-wider font-bold mb-1">Nama Lengkap</p>
                            <p class="font-semibold text-brand-dark">{{ $customer['name'] ?? '-' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400 uppercase tracking-wider font-bold mb-1">Email</p>
                            <p class="font-semibold text-brand-dark">{{ $customer['email'] ?? '-' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400 uppercase tracking-wider font-bold mb-1">Nomor Telepon</p>
                            <p class="font-semibold text-brand-dark">{{ $customer['phone'] ?? '-' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400 uppercase tracking-wider font-bold mb-1">Kota</p>
                            <p class="font-semibold text-brand-dark">{{ $customer['city'] ?? '-' }}</p>
                        </div>
                    </div>
                    <div class="mt-5">
                        <p class="text-xs text-gray-400 uppercase tracking-wider font-bold mb-1">Alamat Lengkap</p>
                        <p class="font-semibold text-brand-dark">{{ $customer['address'] ?? '-' }}</p>
                        <p class="text-sm text-gray-500 mt-1">{{ $customer['city'] ?? '' }} {{ $customer['postal_code'] ?? '' }}</p>
                    </div>
                </div>

                <div class="bg-white border border-brand-muted rounded-2xl p-6">
                    <h2 class="font-bold text-brand-dark mb-5">Ringkasan Produk</h2>
                    <div class="space-y-4">
                        @forelse($items as $item)
                            <div class="flex gap-4 border-b border-brand-muted pb-4 last:border-0 last:pb-0">
                                <div class="w-16 h-16 rounded-xl bg-brand-light overflow-hidden flex-shrink-0">
                                    @if(!empty($item['image']))
                                        <img src="{{ $item['image'] }}" alt="{{ $item['name'] ?? 'Produk' }}" loading="lazy" decoding="async" class="w-full h-full object-cover">
                                    @else
                                        <div class="w-full h-full flex items-center justify-center text-brand-gold">
                                            <i class="fa-solid fa-box w-6 h-6"></i>
                                        </div>
                                    @endif
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="font-bold text-brand-dark truncate">{{ $item['name'] ?? 'Produk' }}</p>
                                    <p class="text-sm text-gray-500 mt-1">{{ $item['brand'] ? $item['brand'] . ' · ' : '' }}Qty {{ $item['quantity'] ?? 1 }}</p>
                                    <p class="text-sm text-gray-500 mt-1">{{ $formatRupiah($item['sell_price'] ?? 0) }} / pcs</p>
                                    @if(!empty($item['item_note']))
                                        <p class="mt-2 rounded-lg bg-brand-light p-2 text-xs text-gray-600">{{ $item['item_note'] }}</p>
                                    @endif
                                </div>
                                <div class="text-right flex-shrink-0">
                                    <p class="font-bold text-brand-dark">{{ $formatRupiah(($item['sell_price'] ?? 0) * ($item['quantity'] ?? 1)) }}</p>
                                </div>
                            </div>
                        @empty
                            <p class="text-gray-500">Tidak ada produk di keranjang.</p>
                        @endforelse
                    </div>
                </div>

                <div class="bg-white border border-brand-muted rounded-2xl p-6">
                    <h2 class="font-bold text-brand-dark mb-5">Pengiriman</h2>
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <div class="rounded-xl bg-brand-light p-4">
                                <p class="text-xs text-gray-400 uppercase tracking-wider font-bold mb-1">Ekspedisi</p>
                                <p class="font-semibold text-brand-dark">{{ $preview['courier_label'] ?? '-' }}</p>
                            </div>
                            <div class="rounded-xl bg-brand-light p-4">
                                <p class="text-xs text-gray-400 uppercase tracking-wider font-bold mb-1">Ongkir</p>
                                <p class="font-semibold text-brand-dark">{{ $formatRupiah($preview['shipping_cost'] ?? 0) }}</p>
                            </div>
                            <div class="rounded-xl bg-brand-light p-4">
                                <p class="text-xs text-gray-400 uppercase tracking-wider font-bold mb-1">Diskon Kupon</p>
                                <p class="font-semibold text-red-600">- {{ $formatRupiah($preview['voucher_discount'] ?? 0) }}</p>
                                @if(!empty($preview['voucher_codes']))
                                <p class="text-xs text-gray-500 mt-1">{{ implode(', ', $preview['voucher_codes']) }}</p>
                            @elseif(!empty($preview['voucher_code']))
                                <p class="text-xs text-gray-500 mt-1">{{ $preview['voucher_code'] }}</p>
                            @endif
                            </div>
                            <div class="rounded-xl bg-brand-light p-4">
                                <p class="text-xs text-gray-400 uppercase tracking-wider font-bold mb-1">Estimasi Total</p>
                                <p class="font-semibold text-brand-dark">{{ $formatRupiah($preview['total'] ?? 0) }}</p>
                            </div>
                        </div>
                </div>
            </div>

            <div class="lg:col-span-1">
                <div class="bg-white border border-brand-muted rounded-2xl p-6 sticky top-6 space-y-5">
                    <div>
                        <h2 class="font-bold text-brand-dark mb-5">Total Pembayaran</h2>
                        <div class="space-y-3">
                            <div class="flex justify-between text-gray-500">
                                <span>Subtotal</span>
                                <span>{{ $formatRupiah($preview['subtotal'] ?? 0) }}</span>
                            </div>
                            <div class="flex justify-between text-gray-500">
                                <span>Ongkir</span>
                                <span>{{ $formatRupiah($preview['shipping_cost'] ?? 0) }}</span>
                            </div>
                            <div class="flex justify-between text-gray-500">
                                <span>Diskon Kupon</span>
                                <span class="text-red-600">- {{ $formatRupiah($preview['voucher_discount'] ?? 0) }}</span>
                            </div>
                            @if(!empty($preview['voucher_codes']))
                                <p class="text-xs text-gray-500">{{ implode(', ', $preview['voucher_codes']) }}</p>
                            @elseif(!empty($preview['voucher_code']))
                                <p class="text-xs text-gray-500">{{ $preview['voucher_code'] }}</p>
                            @endif
                            <div class="border-t border-brand-muted pt-4 flex justify-between items-center">
                                <span class="font-bold text-brand-dark">Total</span>
                                <span class="font-extrabold text-xl text-brand-dark">{{ $formatRupiah($preview['total'] ?? 0) }}</span>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="w-full py-3 bg-brand-dark text-brand-gold rounded-xl font-bold hover:bg-brand-darker transition-colors">
                        Lanjut ke Pembayaran
                    </button>
                    <a href="{{ route('checkout') }}" class="w-full py-3 text-center border border-brand-muted text-brand-dark rounded-xl font-bold hover:bg-brand-light transition-colors">
                        Edit Data Checkout
                    </a>
                </div>
            </div>
        </form>
    </div>
@endsection
