@extends('frontend.layouts.app')

@php
    $user = session()->get('user', []);
    $activeTab = request()->query('tab', 'devices');
    $wishlistProducts = App\Models\Frontend\ProductsCatalog\Product::whereIn('id', session()->get('wishlist', []))
        ->with(['brand', 'images', 'variants'])
        ->get();
    $orderStatusLabels = \App\Models\Frontend\Order::statusLabels();
    $paymentStatusLabels = [
        1 => 'Belum Dibayar',
        2 => 'Dibayar',
        3 => 'Gagal',
        4 => 'Refund',
    ];
    $addresses = $addresses ?? collect();
    $formatRupiah = fn($value) => 'Rp ' . number_format((float) $value, 0, ',', '.');
@endphp

@section('title', 'Dashboard Akun Saya - IMG')
@section('robots', 'noindex,nofollow')

@section('content')
    <div class="container mx-auto px-4 md:px-6 py-12 min-h-[70vh] font-sans">
        @if(session('success'))
            <div class="mb-6 p-4 bg-green-50 border border-green-200 text-green-700 rounded-2xl text-sm font-medium flex items-center gap-2">
                <i class="fa-solid fa-circle-check text-green-500 text-base"></i>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @if(session('error'))
            <div class="mb-6 p-4 bg-red-50 border border-red-200 text-red-700 rounded-2xl text-sm font-medium flex items-center gap-2">
                <i class="fa-solid fa-circle-xmark text-red-500 text-base"></i>
                <span>{{ session('error') }}</span>
            </div>
        @endif

        @if($errors->any())
            <div class="mb-6 p-4 bg-red-50 border border-red-200 text-red-700 rounded-2xl text-sm font-medium">
                <div class="flex items-center gap-2 mb-2 font-bold">
                    <i class="fa-solid fa-circle-xmark text-red-500 text-base"></i>
                    <span>Terdapat beberapa kesalahan:</span>
                </div>
                <ul class="list-disc list-inside space-y-1 text-xs">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

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
                        <span class="font-semibold text-brand-dark">{{ $customer->phone ?? $user['phone'] ?? '-' }}</span>
                    </div>
                </div>

                <div class="border-t border-brand-muted pt-6 space-y-2">
                    <a href="{{ route('dashboard', ['tab' => 'profile']) }}"
                        class="flex items-center gap-3 px-4 py-3 rounded-xl font-semibold text-sm transition-colors {{ $activeTab === 'profile' ? 'bg-brand-light text-brand-dark' : 'text-gray-600 hover:bg-brand-light' }}">
                        <i class="fa-solid fa-user-pen w-4 h-4"></i> Edit Profil
                    </a>
                    <a href="{{ route('dashboard', ['tab' => 'devices']) }}"
                        class="flex items-center gap-3 px-4 py-3 rounded-xl font-semibold text-sm transition-colors {{ $activeTab === 'devices' ? 'bg-brand-light text-brand-dark' : 'text-gray-600 hover:bg-brand-light' }}">
                        <i class="fa-solid fa-devices w-4 h-4"></i> Perangkat Aktif
                    </a>
                    <a href="{{ route('dashboard', ['tab' => 'orders']) }}"
                        class="flex items-center gap-3 px-4 py-3 rounded-xl font-semibold text-sm transition-colors {{ $activeTab === 'orders' ? 'bg-brand-light text-brand-dark' : 'text-gray-600 hover:bg-brand-light' }}">
                        <i class="fa-solid fa-bag-shopping w-4 h-4"></i> Riwayat Pesanan
                    </a>
                    <a href="{{ route('dashboard', ['tab' => 'wishlist']) }}"
                        class="flex items-center gap-3 px-4 py-3 rounded-xl font-semibold text-sm transition-colors {{ $activeTab === 'wishlist' ? 'bg-brand-light text-brand-dark' : 'text-gray-600 hover:bg-brand-light' }}">
                        <i class="fa-solid fa-heart w-4 h-4"></i> Wishlist
                    </a>
                    <a href="{{ route('dashboard', ['tab' => 'vouchers']) }}"
                        class="flex items-center gap-3 px-4 py-3 rounded-xl font-semibold text-sm transition-colors {{ $activeTab === 'vouchers' ? 'bg-brand-light text-brand-dark' : 'text-gray-600 hover:bg-brand-light' }}">
                        <i class="fa-solid fa-ticket w-4 h-4"></i> Voucher Saya
                    </a>
                    <a href="{{ route('dashboard', ['tab' => 'addresses']) }}"
                        class="flex items-center gap-3 px-4 py-3 rounded-xl font-semibold text-sm transition-colors {{ $activeTab === 'addresses' ? 'bg-brand-light text-brand-dark' : 'text-gray-600 hover:bg-brand-light' }}">
                        <i class="fa-solid fa-location-dot w-4 h-4"></i> Alamat Saya
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
                @if($activeTab === 'profile')
                    <div class="bg-white border border-brand-muted rounded-3xl p-6 sm:p-8 shadow-sm space-y-6">
                        <div>
                            <h3 class="text-xl font-extrabold text-brand-dark">Edit Profil</h3>
                            <p class="text-sm text-gray-500 mt-1">Perbarui informasi data diri Anda.</p>
                        </div>

                        <form action="{{ route('dashboard.profile.update') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
                            @csrf
                            @method('PUT')

                            <!-- Avatar Upload Section -->
                            <div class="flex flex-col sm:flex-row items-center gap-5 p-4 rounded-2xl bg-brand-light/30 border border-brand-muted" x-data="{
                                avatarPreview: '{{ $user['avatar'] ?? $user['photo_url'] ?? '' }}',
                                fileChosen(event) {
                                    const file = event.target.files[0];
                                    if (file) {
                                        this.avatarPreview = URL.createObjectURL(file);
                                    }
                                }
                            }">
                                <div class="relative w-20 h-20 rounded-full overflow-hidden bg-brand-gold/15 border-2 border-brand-gold/40 flex items-center justify-center shrink-0 shadow-inner">
                                    <template x-if="avatarPreview">
                                        <img :src="avatarPreview" alt="Avatar" class="w-full h-full object-cover">
                                    </template>
                                    <template x-if="!avatarPreview">
                                        <span class="text-2xl font-black text-brand-dark uppercase">
                                            {{ substr($user['name'] ?? 'U', 0, 1) }}
                                        </span>
                                    </template>
                                </div>
                                <div class="flex-1 text-center sm:text-left space-y-1.5">
                                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider">Foto Profil</label>
                                    <p class="text-xs text-gray-500">Upload foto profil terbaik Anda. Format: JPG, PNG, atau WEBP (Maksimal 2MB).</p>
                                    <div class="pt-1">
                                        <input type="file" name="avatar" id="avatar" accept="image/png, image/jpeg, image/jpg, image/webp" @change="fileChosen" class="hidden">
                                        <label for="avatar" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-white border border-brand-muted hover:border-brand-gold text-xs font-bold text-brand-dark cursor-pointer transition-colors shadow-2xs hover:bg-brand-light/50">
                                            <i class="fa-solid fa-camera"></i>
                                            <span>Pilih Foto Baru</span>
                                        </label>
                                    </div>
                                    @error('avatar')
                                        <p class="text-xs text-red-500 font-semibold">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <!-- Identitas Profil (Hanya Identitas, Tanpa Alamat) -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div class="sm:col-span-2">
                                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Nama Lengkap <span class="text-red-500">*</span></label>
                                    <input type="text" name="name" value="{{ old('name', $customer->name ?? $user['name'] ?? '') }}" required class="w-full bg-brand-light/40 border border-brand-muted rounded-xl px-4 py-3 text-sm text-brand-dark font-medium focus:outline-none focus:ring-2 focus:ring-brand-gold/50 focus:bg-white transition-all">
                                    @error('name')
                                        <p class="text-xs text-red-500 font-semibold mt-1">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Nomor Telepon / WhatsApp</label>
                                    <input type="tel" name="phone" value="{{ old('phone', $customer->phone ?? $user['phone'] ?? '') }}" placeholder="Contoh: 08123456789" class="w-full bg-brand-light/40 border border-brand-muted rounded-xl px-4 py-3 text-sm text-brand-dark font-medium focus:outline-none focus:ring-2 focus:ring-brand-gold/50 focus:bg-white transition-all">
                                    @error('phone')
                                        <p class="text-xs text-red-500 font-semibold mt-1">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Email Address</label>
                                    <input type="email" value="{{ $user['email'] ?? '' }}" disabled class="w-full bg-gray-100 border border-gray-200 rounded-xl px-4 py-3 text-sm text-gray-500 cursor-not-allowed">
                                    <p class="text-[11px] text-gray-400 mt-1">Email terhubung dengan akun login Anda.</p>
                                </div>
                            </div>

                            <!-- Ubah Password (Opsional) -->
                            <div class="border-t border-brand-muted pt-5" x-data="{ changePw: {{ $errors->has('current_password') || $errors->has('new_password') ? 'true' : 'false' }} }">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <h4 class="text-sm font-bold text-brand-dark">Keamanan & Password</h4>
                                        <p class="text-xs text-gray-500">Ubah password akun login Anda jika diperlukan.</p>
                                    </div>
                                    <button type="button" @click="changePw = !changePw" class="px-3.5 py-1.5 rounded-lg border border-brand-muted text-xs font-bold text-brand-dark hover:bg-brand-light transition-colors">
                                        <span x-text="changePw ? 'Batal Ubah Password' : 'Ubah Password'"></span>
                                    </button>
                                </div>

                                <div x-show="changePw" x-cloak class="mt-4 space-y-4 p-4 rounded-2xl bg-gray-50 border border-gray-200">
                                    <div>
                                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">Password Saat Ini</label>
                                        <input type="password" name="current_password" placeholder="Masukkan password saat ini" class="w-full bg-white border border-gray-300 rounded-xl px-4 py-2.5 text-sm text-brand-dark focus:outline-none focus:ring-2 focus:ring-brand-gold/50">
                                        @error('current_password')
                                            <p class="text-xs text-red-500 font-semibold mt-1">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">Password Baru</label>
                                            <input type="password" name="new_password" placeholder="Minimal 8 karakter" class="w-full bg-white border border-gray-300 rounded-xl px-4 py-2.5 text-sm text-brand-dark focus:outline-none focus:ring-2 focus:ring-brand-gold/50">
                                            @error('new_password')
                                                <p class="text-xs text-red-500 font-semibold mt-1">{{ $message }}</p>
                                            @enderror
                                        </div>
                                        <div>
                                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">Konfirmasi Password Baru</label>
                                            <input type="password" name="new_password_confirmation" placeholder="Ulangi password baru" class="w-full bg-white border border-gray-300 rounded-xl px-4 py-2.5 text-sm text-brand-dark focus:outline-none focus:ring-2 focus:ring-brand-gold/50">
                                        </div>
                                    </div>
                                    <div class="text-[11px] text-gray-500 bg-white p-3 rounded-lg border border-gray-200 space-y-0.5">
                                        <p class="font-semibold text-gray-700">Syarat password baru:</p>
                                        <ul class="list-disc list-inside text-gray-500 space-y-0.5">
                                            <li>Minimal 8 karakter</li>
                                            <li>Mengandung huruf besar (A-Z) dan huruf kecil (a-z)</li>
                                            <li>Mengandung angka (0-9) dan simbol khusus (!@#$%^&* dll)</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>

                            <div class="pt-2">
                                <button type="submit" class="px-7 py-3 rounded-xl bg-brand-dark text-white hover:bg-brand-gold hover:text-brand-dark font-extrabold text-sm transition-all shadow-sm cursor-pointer inline-flex items-center gap-2">
                                    <i class="fa-solid fa-check"></i>
                                    <span>Simpan Perubahan</span>
                                </button>
                            </div>
                        </form>
                    </div>
                @endif

                @if($activeTab === 'addresses')
                    <div class="bg-white border border-brand-muted rounded-3xl p-6 sm:p-8 shadow-sm space-y-6">
                        <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
                            <div>
                                <h3 class="text-xl font-extrabold text-brand-dark">Alamat Saya</h3>
                                <p class="text-sm text-gray-500 mt-1">Kelola alamat pengiriman Anda.</p>
                            </div>
                            <button type="button" onclick="openAddressModal()" class="inline-flex justify-center items-center gap-2 px-5 py-3 rounded-xl bg-brand-dark text-white hover:bg-brand-gold hover:text-brand-dark font-extrabold text-sm transition-colors">
                                <i class="fa-solid fa-plus w-4 h-4"></i>
                                Tambah Alamat
                            </button>
                        </div>

                        <div class="grid gap-4">
                            @forelse($addresses as $address)
                                <div class="relative rounded-2xl border {{ $address->is_primary ? 'border-brand-gold bg-brand-light/40' : 'border-brand-muted bg-white' }} p-5 transition-colors">
                                    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
                                        <div class="space-y-2">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <h4 class="font-extrabold text-brand-dark">{{ $address->label }}</h4>
                                                @if($address->is_primary)
                                                    <span class="inline-flex items-center rounded-full bg-brand-gold/20 text-brand-gold-dark text-[10px] font-extrabold px-2.5 py-1 uppercase tracking-wider">Utama</span>
                                                @endif
                                            </div>
                                            <div class="text-sm text-gray-700">
                                                <p>{{ $address->recipient_name }}</p>
                                                <p>{{ $address->phone }}</p>
                                                <p>{{ $address->address }}</p>
                                                <p>{{ $address->postal_code }} {{ $address->subDistrict->name ?? '' }}, {{ $address->city->name ?? '' }}</p>
                                            </div>
                                        </div>
                                        <div class="flex flex-wrap gap-2 sm:flex-col sm:items-end">
                                            <button type="button" onclick="editAddress('{{ $address->id }}', '{{ $address->label }}', '{{ $address->recipient_name }}', '{{ $address->phone }}', '{{ $address->address }}')" class="px-4 py-2 rounded-xl border border-brand-muted bg-white text-brand-dark hover:bg-brand-light text-sm font-extrabold transition-colors">
                                                Ubah
                                            </button>
                                            @unless($address->is_primary)
                                                <form action="{{ route('dashboard.addresses.primary', $address->id) }}" method="POST" onsubmit="return confirm('Jadikan alamat ini sebagai utama?');">
                                                    @csrf
                                                    <button type="submit" class="px-4 py-2 rounded-xl border border-brand-gold/30 bg-brand-gold/10 text-brand-gold-dark hover:bg-brand-gold/20 text-sm font-extrabold transition-colors">
                                                        Jadikan Utama
                                                    </button>
                                                </form>
                                            @endunless
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="text-center py-12">
                                    <p class="text-gray-500">Belum ada alamat tersimpan.</p>
                                </div>
                            @endforelse
                        </div>
                    </div>

                    @include('frontend.dashboard-addresses')
                @elseif($activeTab === 'wishlist')
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
                @elseif($activeTab === 'vouchers')
                    <div class="bg-gradient-to-r from-brand-dark to-brand-darker text-white p-6 sm:p-8 rounded-3xl shadow-xl flex flex-col md:flex-row justify-between items-center gap-6 border border-brand-gold/20">
                        <div class="space-y-2 text-center md:text-left">
                            <h3 class="text-xl font-bold text-brand-gold">Spesial Loyalty Member!</h3>
                            <p class="text-sm text-brand-light/70 max-w-md">Dapatkan promo diskon dan gratis ongkir untuk transaksi berikutnya.</p>
                        </div>
                        <a href="{{ route('promos') }}" class="px-6 py-3 bg-brand-gold text-brand-dark hover:bg-brand-light font-bold rounded-xl transition-all shadow shadow-brand-dark/20 text-sm">Lihat Voucher</a>
                    </div>
                @elseif($activeTab === 'orders')
                    <div class="bg-white border border-brand-muted rounded-3xl p-6 sm:p-8 shadow-sm">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">
                            <div>
                                <h3 class="text-xl font-extrabold text-brand-dark">Riwayat Pesanan</h3>
                                <p class="text-sm text-gray-500 mt-1">Order ulang produk dari pesanan sebelumnya dengan satu klik.</p>
                            </div>
                        </div>

                        @forelse($orders as $order)
                            <div class="rounded-2xl border border-brand-muted p-4 sm:p-5 mb-4 last:mb-0 hover:border-brand-gold/40 transition-colors">
                                <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                                    <div class="min-w-0 flex-1">
                                        <div class="flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-4 mb-3">
                                            <span class="font-mono font-extrabold text-brand-gold-dark">{{ $order->order_number ?? $order->id }}</span>
                                            <span class="text-xs text-gray-400">{{ $order->created_at ? $order->created_at->format('d M Y H:i') : '-' }}</span>
                                        </div>

                                        <div class="space-y-2 text-sm text-brand-dark">
                                            @forelse($order->items as $item)
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
                                                <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-1 sm:gap-3">
                                                    <div class="min-w-0 flex-1">
                                                        <span class="truncate block font-semibold text-brand-dark">{{ $item->quantity }}x {{ $item->name }}</span>
                                                        <span class="text-xs text-gray-500">Harga Asli: {{ $formatRupiah($unitPrice) }} / item</span>
                                                    </div>
                                                    <div class="text-left sm:text-right">
                                                        @if($discNom > 0)
                                                            <span class="inline-flex items-center gap-1 text-[11px] font-bold text-red-600 bg-red-50 px-1.5 py-0.5 rounded">
                                                                @if($discPct > 0)
                                                                    <span>Diskon {{ (float) $discPct }}%</span>
                                                                @endif
                                                                <span>(-{{ $formatRupiah($discNom) }})</span>
                                                            </span>
                                                        @endif
                                                        <div class="mt-0.5">
                                                            @if($discNom > 0 && $subtotalAsli > $item->total)
                                                                <span class="text-xs text-gray-400 line-through mr-1">{{ $formatRupiah($subtotalAsli) }}</span>
                                                            @endif
                                                            <span class="text-brand-dark font-bold">{{ $formatRupiah($item->total ?? 0) }}</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            @empty
                                                <p class="text-gray-500">Tidak ada item pada pesanan ini.</p>
                                            @endforelse
                                        </div>

                                        @if($order->payment_method === 'transfer_manual')
                                            <div class="mt-4 p-4 border border-brand-gold/20 bg-amber-50/20 rounded-xl space-y-3">
                                                <div class="flex flex-col sm:flex-row sm:justify-between sm:items-start gap-2">
                                                    <div>
                                                        <p class="text-xs font-extrabold text-brand-dark uppercase tracking-wider">Transfer Bank Manual</p>
                                                        <p class="text-[11px] text-gray-500 mt-0.5">BCA: 123-456-7890 a/n PT RAS</p>
                                                    </div>
                                                    
                                                    @php
                                                        $proof = $order->meta['payment_proof'] ?? null;
                                                    @endphp
                                                    
                                                    @if($proof)
                                                        <div class="flex items-center gap-2">
                                                            <span class="text-xs text-green-600 bg-green-50 px-2 py-1 rounded-md font-semibold flex items-center gap-1">
                                                                <i class="fa-solid fa-circle-check"></i> Bukti Uploaded
                                                            </span>
                                                            <a href="{{ media_url($proof) }}" target="_blank" class="text-xs text-brand-gold-dark hover:underline font-bold">
                                                                Lihat Bukti
                                                            </a>
                                                        </div>
                                                    @else
                                                        <span class="text-xs text-amber-600 bg-amber-50 px-2 py-1 rounded-md font-semibold flex items-center gap-1">
                                                            <i class="fa-solid fa-circle-exclamation"></i> Menunggu Bukti Transfer
                                                        </span>
                                                    @endif
                                                </div>

                                                <!-- Upload / Re-upload form -->
                                                <form action="{{ route('order.upload-payment-proof', $order->id) }}" method="POST" enctype="multipart/form-data" class="flex flex-col sm:flex-row items-center gap-2 mt-2">
                                                    @csrf
                                                    <input type="file" name="payment_proof" accept="image/*" required class="block w-full text-xs text-gray-500 file:mr-2 file:py-1 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-brand-dark file:text-brand-gold hover:file:bg-brand-darker cursor-pointer border border-brand-muted rounded-lg p-1.5 bg-white">
                                                    <button type="submit" class="w-full sm:w-auto px-4 py-1.5 bg-brand-gold text-brand-dark text-xs font-bold rounded-lg hover:bg-brand-dark hover:text-brand-gold transition-colors whitespace-nowrap">
                                                        {{ $proof ? 'Ganti Bukti' : 'Upload Bukti' }}
                                                    </button>
                                                </form>
                                            </div>
                                        @endif
                                    </div>

                                    <div class="flex flex-col sm:items-end gap-3 lg:min-w-[220px]">
                                        <div class="flex flex-wrap justify-center sm:justify-end gap-2">
                                            <span class="bg-brand-gold/15 text-brand-gold-dark text-[10px] font-extrabold px-2.5 py-1 rounded-full uppercase tracking-wider">
                                                {{ $orderStatusLabels[$order->status] ?? 'Unknown' }}
                                            </span>
                                            <span class="bg-gray-100 text-gray-600 text-[10px] font-extrabold px-2.5 py-1 rounded-full uppercase tracking-wider">
                                                {{ $paymentStatusLabels[$order->payment_status] ?? 'Unknown' }}
                                            </span>
                                        </div>

                                        <div class="text-right">
                                            <p class="text-xs text-gray-500">Total</p>
                                            <p class="text-lg font-extrabold text-brand-dark">{{ $formatRupiah($order->total) }}</p>
                                        </div>

                                        <div class="flex flex-col sm:flex-row gap-2 w-full sm:w-auto">
                                            {{-- Action: Lihat Detail for ALL orders --}}
                                            <a href="{{ route('track-order.detail', $order->id) }}" class="w-full sm:w-auto px-3.5 py-2 rounded-xl border border-brand-muted bg-white text-brand-dark hover:bg-brand-light font-bold text-xs transition-colors flex items-center justify-center gap-1.5 shadow-sm">
                                                <i class="fa-regular fa-eye text-xs"></i>
                                                Lihat Detail
                                            </a>

                                            {{-- Action: Bayar Sekarang & Batalkan for UNPAID orders --}}
                                            @if((int)$order->payment_status === 1 && (int)$order->status !== 6 && (int)$order->status !== 8)
                                                <a href="{{ route('payment', ['order_id' => $order->id]) }}" class="w-full sm:w-auto px-3.5 py-2 rounded-xl bg-brand-gold text-brand-dark hover:bg-brand-dark hover:text-brand-gold font-extrabold text-xs transition-colors flex items-center justify-center gap-1.5 shadow-sm">
                                                    <i class="fa-solid fa-credit-card text-xs"></i>
                                                    Bayar Sekarang
                                                </a>
                                                <form action="{{ route('order.cancel', $order->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin membatalkan pesanan ini agar dapat memesan kembali?');" class="inline">
                                                    @csrf
                                                    <button type="submit" class="w-full sm:w-auto px-3.5 py-2 rounded-xl bg-red-50 text-red-600 hover:bg-red-100 border border-red-200 font-bold text-xs transition-colors flex items-center justify-center gap-1.5 cursor-pointer" title="Batalkan pesanan yang belum dibayar">
                                                        <i class="fa-solid fa-ban text-xs"></i>
                                                        Batalkan
                                                    </button>
                                                </form>
                                            @endif

                                            @if((int)$order->status === 6 || (int)$order->status === 7 || (int)$order->status === 8)
                                                <form action="{{ route('order.reorder', $order->id) }}" method="POST" onsubmit="return confirm('Order ulang produk dari pesanan ini?');" class="inline">
                                                    @csrf
                                                    <button type="submit" class="w-full sm:w-auto px-3.5 py-2 rounded-xl bg-brand-dark text-white hover:bg-brand-gold hover:text-brand-dark font-extrabold text-xs transition-colors flex items-center justify-center gap-1.5 cursor-pointer">
                                                        <i class="fa-solid fa-rotate-right text-xs"></i>
                                                        Order Ulang
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="rounded-2xl border border-brand-muted bg-brand-light p-6 text-center text-sm text-gray-500">
                                Belum ada riwayat pesanan.
                            </div>
                        @endforelse
                    </div>
                @else
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
                @endif
            </div>
        </div>
    </div>
@endsection
