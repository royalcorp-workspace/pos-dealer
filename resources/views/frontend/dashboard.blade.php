@extends('frontend.layouts.app')

@php
    $user = session()->get('user', [
        'name' => 'Budi Santoso',
        'email' => 'budi@gmail.com',
        'type' => 'Member Premium'
    ]);
    $activeTab = request()->query('tab', 'devices');
    $wishlistProducts = App\Models\Frontend\ProductsCatalog\Product::whereIn('id', session()->get('wishlist', []))
        ->with(['brand', 'images', 'variants'])
        ->get();
@endphp

@section('title', 'Dashboard Akun Saya - IMG')
@section('robots', 'noindex,nofollow')

@section('content')
    <div class="container mx-auto px-4 md:px-6 py-12 min-h-[70vh] font-sans">
        <div class="flex flex-col lg:flex-row gap-8">
            <!-- Left: Profile sidebar -->
            <div class="w-full lg:w-1/3 bg-white border border-brand-muted rounded-3xl p-6 sm:p-8 shadow-sm space-y-6 self-start">
                <div class="flex items-center gap-4">
                    <div class="w-16 h-16 bg-brand-dark text-brand-gold font-bold flex items-center justify-center rounded-full text-2xl shadow-md">
                        {{ substr($user['name'], 0, 1) }}
                    </div>
                    <div>
                        <h2 class="font-extrabold text-brand-dark text-xl leading-tight">{{ $user['name'] }}</h2>
                        <span class="inline-block bg-brand-gold/20 text-brand-gold-dark text-xs font-bold px-2.5 py-0.5 rounded mt-1.5 uppercase tracking-wider">{{ $user['type'] }}</span>
                    </div>
                </div>

                <div class="border-t border-brand-muted pt-6 space-y-4">
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-400">Email Address</span>
                        <span class="font-semibold text-brand-dark">{{ $user['email'] }}</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-400">Phone Number</span>
                        <span class="font-semibold text-brand-dark">0812-3456-7890</span>
                    </div>
                </div>

                <div class="border-t border-brand-muted pt-6 space-y-2">
                    <a href="{{ route('dashboard', ['tab' => 'devices']) }}"
                        class="flex items-center gap-3 px-4 py-3 rounded-xl font-semibold text-sm transition-colors {{ $activeTab === 'devices' ? 'bg-brand-light text-brand-dark' : 'text-gray-600 hover:bg-brand-light' }}">
                        <i class="fa-solid fa-devices w-4 h-4"></i> Perangkat Aktif
                    </a>
                    <a href="{{ route('dashboard', ['tab' => 'wishlist']) }}"
                        class="flex items-center gap-3 px-4 py-3 rounded-xl font-semibold text-sm transition-colors {{ $activeTab === 'wishlist' ? 'bg-brand-light text-brand-dark' : 'text-gray-600 hover:bg-brand-light' }}">
                        <i class="fa-solid fa-heart w-4 h-4"></i> Wishlist
                    </a>
                    <a href="{{ route('dashboard', ['tab' => 'orders']) }}"
                        class="flex items-center gap-3 px-4 py-3 rounded-xl font-semibold text-sm transition-colors {{ $activeTab === 'orders' ? 'bg-brand-light text-brand-dark' : 'text-gray-600 hover:bg-brand-light' }}">
                        <i class="fa-solid fa-shopping-bag w-4 h-4"></i> Riwayat Pesanan
                    </a>
                </div>

                <div class="border-t border-brand-muted pt-6">
                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button 
                            type="submit"
                            class="w-full py-3 text-center bg-red-50 hover:bg-red-100 text-red-600 hover:text-red-700 font-bold rounded-xl transition-colors flex justify-center items-center gap-2 focus:outline-none"
                        >
                            <i class="fa-solid fa-right-from-bracket w-4 h-4"></i>
                            Keluar dari Akun
                        </button>
                    </form>
                </div>
            </div>

            <!-- Right: Account activity -->
            <div class="w-full lg:w-2/3 space-y-8">
                @if($activeTab === 'wishlist')
                    <div class="bg-white border border-brand-muted rounded-3xl p-6 sm:p-8 shadow-sm">
                        <h3 class="text-xl font-extrabold text-brand-dark mb-6">Wishlist Produk</h3>

                        @if($wishlistProducts->isEmpty())
                            <div class="text-center py-12">
                                <div class="w-16 h-16 bg-brand-light rounded-full flex items-center justify-center text-brand-gold mx-auto mb-4">
                                    <i class="fa-regular fa-heart w-8 h-8"></i>
                                </div>
                                <p class="text-gray-500">Belum ada produk di wishlist. Tambahkan produk favorit Anda!</p>
                            </div>
                        @else
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                                @foreach($wishlistProducts as $product)
                                    @include('frontend.components.product-card-dynamic', ['product' => $product])
                                @endforeach
                            </div>
                        @endif
                    </div>
                @else
                <div class="bg-gradient-to-r from-brand-dark to-brand-darker text-white p-6 sm:p-8 rounded-3xl shadow-xl flex flex-col md:flex-row justify-between items-center gap-6 border border-brand-gold/20">
                    <div class="space-y-2 text-center md:text-left">
                        <h3 class="text-xl font-bold text-brand-gold">Spesial Loyalty Member!</h3>
                        <p class="text-sm text-brand-light/70 max-w-md">Gunakan voucher cashback Rp 500k khusus untuk transaksi kedua kasur springbed.</p>
                    </div>
                    <a href="{{ route('promos') }}" class="px-6 py-3 bg-brand-gold text-brand-dark hover:bg-brand-light font-bold rounded-xl transition-all shadow shadow-brand-dark/20 text-sm">Lihat Voucher</a>
                </div>

                <div class="bg-white border border-brand-muted rounded-3xl p-6 sm:p-8 shadow-sm">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                        <div>
                            <h3 class="text-xl font-extrabold text-brand-dark">Perangkat Aktif</h3>
                            <p class="text-sm text-gray-500 mt-1">Maksimal 5 perangkat aktif. Perangkat terlama akan otomatis dikeluarkan saat batas terlampaui.</p>
                        </div>
                        <span class="inline-flex w-fit items-center rounded-full bg-brand-gold/15 px-3 py-1 text-xs font-extrabold text-brand-gold-dark">
                            {{ $activeDeviceSessions->count() }}/5
                        </span>
                    </div>

                    <div class="mt-5 space-y-3">
                        @forelse($activeDeviceSessions as $device)
                            <div class="flex flex-col sm:flex-row sm:items-center gap-4 rounded-2xl border p-4 {{ $device['is_current'] ? 'border-brand-gold bg-brand-light' : 'border-brand-muted' }}">
                                <div class="flex items-start gap-3 min-w-0 flex-1">
                                    <div class="w-10 h-10 rounded-full bg-brand-dark text-brand-gold flex items-center justify-center flex-shrink-0">
                                        <i class="fa-solid fa-mobile-screen-button"></i>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <div class="flex flex-col sm:flex-row sm:items-center gap-2">
                                            <p class="font-extrabold text-brand-dark truncate">{{ $device['device_name'] }}</p>
                                            @if($device['is_current'])
                                                <span class="inline-flex w-fit items-center rounded-full bg-green-100 px-2.5 py-0.5 text-[10px] font-extrabold uppercase tracking-wider text-green-700">Perangkat Ini</span>
                                            @endif
                                        </div>
                                        <p class="text-sm text-gray-500">{{ $device['ip_address'] ?? 'IP tidak tersedia' }}</p>
                                        <p class="text-sm text-gray-500 truncate">{{ $device['user_agent'] ? (strlen($device['user_agent']) > 90 ? substr($device['user_agent'], 0, 90) . '...' : $device['user_agent']) : 'Browser tidak terdeteksi' }}</p>
                                        <p class="text-xs text-gray-400 mt-1">Terakhir aktif {{ $device['last_active_at'] ? $device['last_active_at']->diffForHumans() : 'baru saja' }}</p>
                                    </div>
                                </div>

                                @if(!$device['is_current'])
                                    <form action="{{ route('devices.logout', $device['id']) }}" method="POST" onsubmit="return confirm('Keluarkan perangkat ini dari akun Anda?');">
                                        @csrf
                                        <button type="submit" class="w-full sm:w-auto px-4 py-2 rounded-xl border border-red-200 bg-red-50 text-red-600 hover:bg-red-100 hover:text-red-700 text-sm font-extrabold transition-colors">
                                            Keluarkan
                                        </button>
                                    </form>
                                @endif
                            </div>
                        @empty
                            <div class="rounded-2xl border border-brand-muted bg-brand-light p-4 text-sm text-gray-500">
                                Belum ada perangkat aktif yang tercatat.
                            </div>
                        @endforelse
                    </div>
                </div>

                <div class="bg-white border border-brand-muted rounded-3xl p-6 sm:p-8 shadow-sm">
                    <h3 class="text-xl font-extrabold text-brand-dark mb-6">Riwayat Pesanan</h3>

                    <div class="overflow-x-auto -mx-6 sm:-mx-8">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-brand-light text-gray-400 text-xs font-bold uppercase tracking-wider border-b border-brand-muted">
                                    <th class="py-4 px-6 sm:px-8">ID Pesanan</th>
                                    <th class="py-4 px-4">Tanggal</th>
                                    <th class="py-4 px-4">Produk</th>
                                    <th class="py-4 px-4">Total</th>
                                    <th class="py-4 px-6 sm:px-8">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 text-sm text-brand-dark">
                                <tr class="hover:bg-brand-light/30 transition-colors">
                                    <td class="py-5 px-6 sm:px-8 font-mono font-bold text-brand-gold-dark">ORD-2026-001</td>
                                    <td class="py-5 px-4 text-gray-500 whitespace-nowrap">01 Juni 2026</td>
                                    <td class="py-5 px-4">
                                        <div class="flex items-center gap-3">
                                            <div class="w-10 h-10 rounded bg-gray-50 overflow-hidden flex-shrink-0">
                                                <img src="{{ $mockProduct['image'] }}" alt="" class="w-full h-full object-cover" />
                                            </div>
                                            <span class="font-semibold leading-snug line-clamp-1 max-w-[200px]">{{ $mockProduct['name'] }}</span>
                                        </div>
                                    </td>
                                    <td class="py-5 px-4 font-bold">Rp 3.500.000</td>
                                    <td class="py-5 px-6 sm:px-8">
                                        <span class="bg-green-100 text-green-700 text-[10px] font-bold px-2 py-1 rounded-full uppercase tracking-wider">Dikirim</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif
    </div>
@endsection

