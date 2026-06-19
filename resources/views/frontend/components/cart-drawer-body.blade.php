@php
    $cart = $cart ?? session()->get('cart', []);
    $cartItemCount = collect($cart)->sum('quantity');
    $cartTotal = collect($cart)->sum(function($item) {
        return $item['price'] * $item['quantity'];
    });
    $cartProductIds = collect($cart)->pluck('product_id')->filter()->unique()->values()->all();
    $cartCategoryIds = \App\Models\Frontend\ProductsCatalog\Product::whereIn('id', $cartProductIds)->pluck('category_id')->unique()->values()->all();
    $cartCoupons = \App\Models\Frontend\Promo\Voucher::active()->with(['products', 'categories'])->get()->filter(function($coupon) use ($cartProductIds, $cartCategoryIds) {
        if ((int) $coupon->scope === 2) {
            return $coupon->products()->where('deleted', false)->whereIn('products.id', $cartProductIds)->exists();
        }

        if ((int) $coupon->scope === 3) {
            return $coupon->categories()->where('deleted', false)->whereIn('product_category.id', $cartCategoryIds)->exists();
        }

        return true;
    })->values();
@endphp

@if(count($cart) === 0)
    <div class="flex flex-col items-center justify-center h-full text-center space-y-4 py-12">
        <div class="w-20 h-20 bg-brand-light rounded-full flex items-center justify-center text-brand-gold">
            <svg class="w-8 h-8" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="6 7h12l-1 12H7L6 7Z" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/><path d="M9 7V5a3 3 0 0 1 6 0v2" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </div>
        <div>
            <h3 class="font-bold text-brand-dark text-lg mb-1">Keranjang Kosong</h3>
            <p class="text-gray-500 text-sm">Belum ada barang di keranjang Anda. Mulai belanja sekarang!</p>
        </div>
        <button
            @click="isCartOpen = false"
            class="px-6 py-2.5 bg-brand-dark text-brand-gold rounded-xl font-bold hover:bg-brand-darker transition-colors focus:outline-none"
        >
            Belanja Sekarang
        </button>
    </div>
@else
    <div class="flex-1 p-5 md:p-6 flex flex-col gap-6 overflow-y-auto">
        @foreach($cart as $item)
            <div data-cart-item-id="{{ $item['id'] }}" class="flex gap-4 p-4 border border-gray-100 rounded-2xl bg-white shadow-sm">
                <div class="w-24 h-24 bg-gray-50 rounded-xl overflow-hidden flex-shrink-0">
                    <img src="{{ $item['image'] }}" alt="{{ $item['name'] }}" class="w-full h-full object-cover" />
                </div>
                <div class="flex flex-col flex-1">
                    <div class="flex justify-between items-start">
                        <div>
                            <span class="text-[10px] uppercase font-bold tracking-wider text-gray-400">{{ $item['brand'] }}</span>
                            <h4 class="font-semibold text-gray-900 text-sm leading-snug line-clamp-2 mt-0.5">{{ $item['name'] }}</h4>
                            @if(isset($item['size']) && $item['size'])
                                <div class="text-xs text-brand-gold-dark mt-1 font-medium">{{ $item['size'] }}</div>
                            @endif
                        </div>
                        <form action="{{ route('cart.remove', $item['id']) }}" method="POST">
                            @csrf
                            <button type="submit" class="text-gray-400 hover:text-red-500 p-1 focus:outline-none" aria-label="Hapus item">
                                <i class="fa-solid fa-trash-can w-4 h-4"></i>
                            </button>
                        </form>
                            @if(!empty($item['item_note']))
                                <p class="mt-2 rounded-lg bg-brand-light p-2 text-xs text-gray-600">{{ $item['item_note'] }}</p>
                            @endif
                        </div>
                        <div class="mt-auto flex justify-between items-end">
                        <span class="font-bold text-brand-dark tracking-tight">Rp {{ number_format($item['price'], 0, ',', '.') }}</span>

                        <div class="flex items-center gap-3 bg-brand-light px-2 py-1 rounded-lg border border-brand-muted">
                            <button
                                type="button"
                                onclick="updateCartQuantity(this, -1)"
                                data-cart-id="{{ $item['id'] }}"
                                class="text-gray-500 hover:text-brand-gold w-5 h-5 flex items-center justify-center font-medium focus:outline-none"
                                aria-label="Kurangi jumlah"
                            >-</button>

                            <span class="cart-item-quantity text-sm font-semibold text-brand-darker">{{ $item['quantity'] }}</span>

                            <button
                                type="button"
                                onclick="updateCartQuantity(this, 1)"
                                data-cart-id="{{ $item['id'] }}"
                                class="text-gray-500 hover:text-brand-gold w-5 h-5 flex items-center justify-center font-medium focus:outline-none"
                                aria-label="Tambah jumlah"
                            >+</button>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div id="cart-footer" class="p-5 md:p-6 bg-brand-light border-t border-brand-muted space-y-4">
        <button
            type="button"
            onclick="toggleCartCouponPanel()"
            class="w-full flex justify-between items-center bg-white p-3 md:p-4 rounded-xl border border-brand-muted hover:border-brand-gold transition-colors group shadow-sm focus:outline-none"
            aria-label="Pilih kupon"
        >
            <div class="flex items-center gap-3 min-w-0">
                <div class="w-8 h-8 rounded-full bg-brand-light text-brand-gold flex items-center justify-center flex-shrink-0">
                    <span class="font-bold text-lg">%</span>
                </div>
                <div class="min-w-0 text-left">
                    <span id="cart-coupon-trigger-title" class="block font-bold text-gray-700 group-hover:text-brand-dark truncate">Pilih Kupon yang tersedia</span>
                    <span id="cart-coupon-trigger-code" class="block text-xs text-gray-500 truncate">Klik untuk melihat kupon aktif</span>
                </div>
            </div>
            <i id="cart-coupon-icon" class="fa-solid fa-chevron-right w-4 h-4 text-gray-400 group-hover:text-brand-gold transition-transform"></i>
        </button>

        <div id="cart-coupon-panel" class="hidden space-y-3">
            @foreach($cartCoupons as $coupon)
                @php
                    $typeLabel = match($coupon->type) {
                        1 => $coupon->value . '%',
                        2 => 'Rp ' . number_format($coupon->value, 0, ',', '.'),
                        3 => 'Gratis Ongkir',
                        default => 'Tidak diketahui',
                    };
                @endphp
<button
                     type="button"
                     onclick="selectCartCoupon(this)"
                     class="coupon-option w-full text-left rounded-2xl border bg-white p-4 transition-all hover:border-brand-gold hover:shadow-md focus:outline-none focus:ring-2 focus:ring-brand-gold"
                     data-code="{{ $coupon->code }}"
                     data-title="{{ $coupon->title }}"
                     data-description="{{ $coupon->description }}"
                     data-discount-type="{{ $coupon->type == 1 ? 'percentage' : ($coupon->type == 2 ? 'fixed' : 'shipping') }}"
                      data-discount-value="{{ floatval($coupon->value) }}"
                      data-max-discount="{{ $coupon->max_discount ?? '' }}"
                      data-allow-stacking="{{ $coupon->allow_stacking ? 1 : 0 }}">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="font-extrabold text-brand-dark">{{ $coupon->title }}</p>
                            <p class="text-xs text-gray-500 mt-1">{{ $coupon->description }}</p>
                        </div>
                        <span class="inline-flex items-center rounded-full bg-red-50 px-2.5 py-1 text-[10px] font-extrabold uppercase tracking-wider text-red-600 flex-shrink-0">{{ $typeLabel }}</span>
                    </div>
                    <div class="mt-3 flex flex-wrap gap-1.5">
                        <span class="inline-flex items-center rounded-full bg-brand-light px-2 py-0.5 text-[10px] font-extrabold uppercase tracking-wider text-brand-gold-dark">{{ $coupon->scopeLabel() }}</span>
                        <span class="inline-flex items-center rounded-full {{ $coupon->allow_stacking ? 'bg-green-50 text-green-700' : 'bg-gray-100 text-gray-500' }} px-2 py-0.5 text-[10px] font-extrabold uppercase tracking-wider">{{ $coupon->allow_stacking ? 'Bisa Digabung' : 'Single Voucher' }}</span>
                    </div>
                    <div class="mt-4 flex items-center justify-between">
                        <span class="font-mono text-sm font-bold text-brand-gold-dark">{{ $coupon->code }}</span>
                        <span class="coupon-option-label text-xs font-bold text-gray-400">Pilih</span>
                    </div>
                </button>
            @endforeach

            <div id="cart-selected-coupon" class="hidden rounded-xl bg-white border border-brand-gold/30 p-3 text-sm text-gray-700">
                <div class="flex justify-between gap-3">
                    <span>Kupon dipilih</span>
                    <strong id="cart-selected-title" class="text-brand-dark"></strong>
                </div>
                <div class="flex justify-between gap-3 mt-1 text-red-600">
                    <span>Estimasi hemat</span>
                    <strong id="cart-selected-discount"></strong>
                </div>
            </div>
        </div>

        <div class="space-y-2 pt-2">
            <div class="flex justify-between text-sm text-gray-500">
                <span>Subtotal</span>
                <span class="font-semibold text-gray-800">Rp {{ number_format($cartTotal, 0, ',', '.') }}</span>
            </div>
            <div id="cart-coupon-discount-row" class="hidden flex justify-between text-sm text-gray-500">
                <span>Diskon Kupon</span>
                <span id="cart-coupon-discount" class="font-semibold text-red-600"></span>
            </div>
            <div class="flex justify-between text-base md:text-lg font-bold text-brand-darker border-t border-brand-muted pt-3 mt-1">
                <span>Total</span>
                <span id="cart-total-with-discount" class="text-brand-dark text-xl md:text-2xl tracking-tight">Rp {{ number_format($cartTotal, 0, ',', '.') }}</span>
            </div>
        </div>

        <button
            @click="isCartOpen = false"
            onclick="window.location.href='{{ route('checkout') }}'"
            class="w-full py-4 text-center rounded-xl font-bold bg-brand-dark hover:bg-brand-darker text-brand-gold transition-transform active:scale-[0.98] shadow-lg shadow-brand-dark/20 mt-2 flex justify-center items-center gap-2 focus:outline-none"
        >
            Checkout Sekarang
            <i class="fa-solid fa-cart-shopping w-5 h-5"></i>
        </button>

        <p class="text-center text-xs text-gray-400">Pajak dan ongkos kirim dihitung saat checkout.</p>
    </div>
@endif