@extends('frontend.layouts.app')

@section('title', 'Pemilihan Pembayaran - IMG')
@section('robots', 'noindex,nofollow')

@section('content')
    <div class="container mx-auto px-4 md:px-6 py-12 min-h-[60vh] font-sans">
        <h1 class="text-3xl font-extrabold text-brand-dark mb-8 font-serif">Pemilihan Pembayaran</h1>
        
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <div class="lg:col-span-2">
                <div class="bg-white border border-brand-muted rounded-2xl p-6 mb-6">
                    <h2 class="font-bold text-brand-dark mb-4">Metode Pembayaran</h2>
                    
                    <div class="space-y-6">
                        <div>
                            <h3 class="text-sm font-semibold text-gray-500 mb-3 uppercase tracking-wider">Transfer Bank</h3>
                            <div class="grid grid-cols-2 gap-3">
                                @foreach(array_filter($paymentMethods, fn($m) => $m['type'] === 'transfer') as $method)
                                    <label class="flex items-center gap-3 p-4 border border-brand-muted rounded-xl cursor-pointer hover:border-brand-gold transition-colors">
                                        <input type="radio" name="payment_method" value="{{ $method['code'] }}" class="w-4 h-4 text-brand-gold">
                                        <div class="flex items-center gap-2">
                                            <i class="fa-solid fa-building w-5 h-5 text-brand-dark"></i>
                                            <span class="font-medium">{{ $method['name'] }}</span>
                                        </div>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        <div>
                            <h3 class="text-sm font-semibold text-gray-500 mb-3 uppercase tracking-wider">E-Wallet</h3>
                            <div class="grid grid-cols-2 gap-3">
                                @foreach(array_filter($paymentMethods, fn($m) => $m['type'] === 'ewallet') as $method)
                                    <label class="flex items-center gap-3 p-4 border border-brand-muted rounded-xl cursor-pointer hover:border-brand-gold transition-colors">
                                        <input type="radio" name="payment_method" value="{{ $method['code'] }}" class="w-4 h-4 text-brand-gold">
                                        <div class="flex items-center gap-2">
                                            <i class="fa-solid fa-wallet w-5 h-5 text-brand-dark"></i>
                                            <span class="font-medium">{{ $method['name'] }}</span>
                                        </div>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white border border-brand-muted rounded-2xl p-6">
                    <h2 class="font-bold text-brand-dark mb-4">Detail Pesanan</h2>
                    <div class="space-y-3">
                        @foreach($cart as $item)
                            <div class="flex justify-between items-center py-2 border-b">
                                <span class="text-gray-700">{{ $item['name'] ?? 'Produk' }}</span>
                                <span class="font-semibold">Rp {{ number_format(($item['price'] ?? 0) * ($item['quantity'] ?? 1), 0, ',', '.') }}</span>
                            </div>
                        @endforeach
                        <div class="flex justify-between pt-4">
                            <span class="font-bold text-lg">Total</span>
                            <span class="font-bold text-xl text-brand-dark">Rp {{ number_format($total, 0, ',', '.') }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="lg:col-span-1">
                <div class="bg-white border border-brand-muted rounded-2xl p-6 sticky top-6">
                    <button 
                        type="button"
                        onclick="processPayment()"
                        class="w-full py-4 bg-brand-dark text-brand-gold rounded-xl font-bold text-lg hover:bg-brand-darker transition-colors mb-4"
                    >
                        Bayar Sekarang
                    </button>
                    <a href="{{ route('checkout') }}" class="w-full py-2 text-center text-gray-500 hover:text-brand-dark transition-colors text-sm block">
                        Kembali ke Checkout
                    </a>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
function processPayment() {
    const selectedMethod = document.querySelector('input[name="payment_method"]:checked');
    if (!selectedMethod) {
        alert('Pilih metode pembayaran terlebih dahulu');
        return;
    }
    showLoading();
    setTimeout(function() {
        window.location.href = '{{ route('thankyou') }}';
    }, 500);
}
</script>
@endpush