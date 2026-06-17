@extends('frontend.layouts.app')

@section('title', 'Checkout - IMG')
@section('robots', 'noindex,nofollow')

@section('content')
    @php
        $cart = session()->get('cart', []);
        $cartTotal = collect($cart)->sum(fn($item) => $item['price'] * $item['quantity']);
        $savedAddresses = [
            ['id' => '1', 'label' => 'Rumah', 'name' => 'Budi Santoso', 'phone' => '0812-3456-7890', 'city' => 'Jakarta', 'address' => 'Jl. Sudirman No. 123, Jakarta', 'postal_code' => '12190'],
            ['id' => '2', 'label' => 'Kantor', 'name' => 'Budi Santoso', 'phone' => '0812-3456-7890', 'city' => 'Jakarta', 'address' => 'Jl. Thamrin No. 45, Jakarta', 'postal_code' => '10230'],
        ];
    @endphp
    <div class="container mx-auto px-4 md:px-6 py-12 min-h-[60vh] font-sans">
        <h1 class="text-3xl font-extrabold text-brand-dark mb-8 font-serif">Checkout</h1>
        
        <form action="{{ route('checkout.process') }}" method="POST" class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            @csrf
            <div class="lg:col-span-2">
                @if(count($cart) === 0)
                    <div class="bg-white border border-brand-muted rounded-2xl p-8 text-center">
                        <p class="text-gray-500">Keranjang belanja kosong. <a href="{{ route('home') }}" class="text-brand-gold hover:underline">Lanjutkan belanja</a></p>
                    </div>
                @else
                    <!-- Customer Information -->
                    <div class="bg-white border border-brand-muted rounded-2xl p-6 mb-6">
                        <div class="flex justify-between items-center mb-4">
                            <h2 class="font-bold text-brand-dark">Informasi Pembeli</h2>
                            <button type="button" onclick="toggleAddressSelector()" class="text-sm text-brand-gold font-semibold hover:underline">
                                Pilih Alamat Lain
                            </button>
                        </div>

                        <div id="address-selector" class="hidden mb-4 p-4 bg-brand-light rounded-xl">
                            <p class="text-sm font-semibold text-brand-dark mb-3">Alamat Tersimpan</p>
                            <div class="space-y-2 max-h-60 overflow-y-auto">
                                @foreach($savedAddresses as $addr)
                                    <label class="flex items-start gap-3 p-3 border border-brand-muted rounded-lg cursor-pointer hover:border-brand-gold transition-colors">
                                        <input type="radio" name="selected_address" value="{{ $addr['id'] }}" class="mt-1" onchange="fillAddress(this)">
                                        <div>
                                            <span class="font-semibold text-brand-dark">{{ $addr['label'] }}</span>
                                            <p class="text-sm text-gray-600">{{ $addr['name'] }} | {{ $addr['phone'] }}</p>
                                            <p class="text-sm text-gray-500">{{ $addr['address'] }}, {{ $addr['city'] }} {{ $addr['postal_code'] }}</p>
                                        </div>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Nama Lengkap</label>
                                <input type="text" name="name" required class="w-full px-4 py-3 border border-brand-muted rounded-xl focus:outline-none focus:border-brand-gold" placeholder="Nama lengkap">
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Email</label>
                                <input type="email" name="email" required class="w-full px-4 py-3 border border-brand-muted rounded-xl focus:outline-none focus:border-brand-gold" placeholder="email@example.com">
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Nomor Telepon</label>
                                <input type="tel" name="phone" required class="w-full px-4 py-3 border border-brand-muted rounded-xl focus:outline-none focus:border-brand-gold" placeholder="08xx xxxx xxxx">
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Kota</label>
                                <input type="text" name="city" required class="w-full px-4 py-3 border border-brand-muted rounded-xl focus:outline-none focus:border-brand-gold" placeholder="Jakarta">
                            </div>
                        </div>
                        <div class="mt-4">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Alamat Lengkap</label>
                            <textarea name="address" required rows="3" class="w-full px-4 py-3 border border-brand-muted rounded-xl focus:outline-none focus:border-brand-gold" placeholder="Jl. Sudirman No. 123, Jakarta"></textarea>
                        </div>
                        <div class="mt-4">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Kode Pos</label>
                            <input type="text" name="postal_code" class="w-full px-4 py-3 border border-brand-muted rounded-xl focus:outline-none focus:border-brand-gold" placeholder="12345">
                        </div>
                        <div class="mt-4">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Ekspedisi</label>
                            <select name="courier" required class="w-full px-4 py-3 border border-brand-muted rounded-xl focus:outline-none focus:border-brand-gold bg-white">
                                <option value="">Pilih Ekspedisi</option>
                                <option value="jne">JNE - Rp 25.000</option>
                                <option value="tiki">TIKI - Rp 20.000</option>
                                <option value="pos">POS Indonesia - Rp 18.000</option>
                                <option value="wahana">Wahana Express - Rp 22.000</option>
                                <option value="sicepat">SiCepat - Rp 23.000</option>
                                <option value="ninja">Ninja Xpress - Rp 24.000</option>
</select>
                        </div>
                    </div>

                    <!-- Order Summary -->
                    <div class="bg-white border border-brand-muted rounded-2xl p-6">
                        <h2 class="font-bold text-brand-dark mb-4">Ringkasan Pesanan</h2>
                        <div class="space-y-4">
                            @foreach($cart as $item)
                                <div class="flex justify-between items-center py-3 border-b">
                                    <div>
                                        <span class="font-medium text-brand-dark">{{ $item['name'] }}</span>
                                        <span class="text-sm text-gray-500"> x {{ $item['quantity'] }}</span>
                                    </div>
                                    <span class="font-semibold text-brand-dark">Rp {{ number_format($item['price'] * $item['quantity'], 0, ',', '.') }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
            
            <div class="lg:col-span-1">
                <div class="bg-white border border-brand-muted rounded-2xl p-6 sticky top-6">
                    <h3 class="font-bold text-brand-dark mb-4">Total Pembayaran</h3>
                    <div class="space-y-2 mb-6">
                        <div class="flex justify-between">
                            <span class="text-gray-500">Subtotal</span>
                            <span class="font-semibold">Rp {{ number_format($cartTotal, 0, ',', '.') }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500">Ongkir</span>
                            <span class="font-semibold" id="shipping-cost">Rp 25.000</span>
                        </div>
                        <div class="flex justify-between text-lg font-bold border-t pt-2">
                            <span>Total</span>
                            <span class="text-brand-dark" id="total-cost">Rp {{ number_format($cartTotal + 25000, 0, ',', '.') }}</span>
                        </div>
                    </div>
                    
                    <button type="submit" class="w-full py-3 bg-brand-dark text-brand-gold rounded-xl font-bold hover:bg-brand-darker transition-colors flex justify-center items-center gap-2">
                        Bayar Sekarang <i class="fa-solid fa-arrow-right w-4 h-4"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const courierSelect = document.querySelector('select[name="courier"]');
    const shippingCost = document.getElementById('shipping-cost');
    const totalCost = document.getElementById('total-cost');
    const subtotal = {{ $cartTotal }};

    if (courierSelect) {
        courierSelect.addEventListener('change', function() {
            const courierNames = {
                'jne': 'JNE',
                'tiki': 'TIKI',
                'pos': 'POS Indonesia',
                'wahana': 'Wahana Express',
                'sicepat': 'SiCepat',
                'ninja': 'Ninja Xpress'
            };
            const shippingPrices = {
                'jne': 25000,
                'tiki': 20000,
                'pos': 18000,
                'wahana': 22000,
                'sicepat': 23000,
                'ninja': 24000
            };
            const cost = shippingPrices[this.value] || 0;
            const name = courierNames[this.value] || '';
            shippingCost.innerHTML = name + ' - <span class="text-brand-dark">Rp ' + cost.toLocaleString('id-ID') + '</span>';
            totalCost.textContent = 'Rp ' + (subtotal + cost).toLocaleString('id-ID');
        });
    }
});

function toggleAddressSelector() {
    document.getElementById('address-selector').classList.toggle('hidden');
}

function fillAddress(el) {
    const addresses = @json($savedAddresses);
    const selected = addresses.find(a => a.id == el.value);
    if (selected) {
        document.querySelector('input[name="name"]').value = selected.name;
        document.querySelector('input[name="phone"]').value = selected.phone;
        document.querySelector('input[name="city"]').value = selected.city;
        document.querySelector('textarea[name="address"]').value = selected.address;
        document.querySelector('input[name="postal_code"]').value = selected.postal_code;
        document.getElementById('address-selector').classList.add('hidden');
    }
}
</script>
@endpush