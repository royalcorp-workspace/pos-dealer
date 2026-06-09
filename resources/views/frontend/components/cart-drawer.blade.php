@php
    $cart = session()->get('cart', []);
    $cartItemCount = collect($cart)->sum('quantity');
    $cartTotal = collect($cart)->sum(function($item) {
        return $item['price'] * $item['quantity'];
    });
@endphp

<div 
    x-show="isCartOpen"
    x-cloak
    class="fixed inset-0 z-50 overflow-hidden font-sans"
    aria-labelledby="slide-over-title"
    role="dialog"
    aria-modal="true"
>
    <div class="absolute inset-0 overflow-hidden">
        <!-- Backdrop -->
        <div 
            x-show="isCartOpen"
            x-transition:enter="ease-in-out duration-500"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="ease-in-out duration-500"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            @click="isCartOpen = false"
            class="absolute inset-0 bg-gray-900/40 backdrop-blur-sm transition-opacity"
        ></div>

        <!-- Sliding Panel -->
        <div class="fixed inset-y-0 right-0 pl-10 max-w-full flex">
            <div 
                x-show="isCartOpen"
                x-transition:enter="transform transition ease-in-out duration-500 sm:duration-700"
                x-transition:enter-start="translate-x-full"
                x-transition:enter-end="translate-x-0"
                x-transition:leave="transform transition ease-in-out duration-500 sm:duration-700"
                x-transition:leave-start="translate-x-0"
                x-transition:leave-end="translate-x-full"
                class="w-screen max-w-md"
            >
                <div class="h-full flex flex-col bg-white shadow-2xl overflow-y-scroll">
                    <!-- Header -->
                    <div class="flex items-center justify-between p-5 md:p-6 border-b border-brand-muted">
                        <h2 class="text-xl font-bold text-brand-dark flex items-center gap-2">
                            Shopping Cart <span class="text-gray-400 font-normal text-base">({{ $cartItemCount }} items)</span>
                        </h2>
                        <button 
                            @click="isCartOpen = false"
                            class="p-2 text-gray-400 hover:text-brand-dark bg-brand-light hover:bg-brand-muted rounded-full transition-colors flex items-center gap-2 focus:outline-none"
                        >
                            <i class="fa-solid fa-xmark w-5 h-5"></i>
                            <span class="sr-only">Close</span>
                        </button>
                    </div>

                    <!-- Cart Content -->
                    <div class="flex-1 p-5 md:p-6 flex flex-col gap-6 overflow-y-auto">
                        @if(count($cart) === 0)
                            <div class="flex flex-col items-center justify-center h-full text-center space-y-4 py-12">
                                <div class="w-20 h-20 bg-brand-light rounded-full flex items-center justify-center text-brand-gold">
                                    <svg class="w-8 h-8" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M6 7h12l-1 12H7L6 7Z" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/><path d="M9 7V5a3 3 0 0 1 6 0v2" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
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
                            @foreach($cart as $item)
                                <div class="flex gap-4 p-4 border border-gray-100 rounded-2xl bg-white shadow-sm">
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
                                                <button type="submit" class="text-gray-400 hover:text-red-500 p-1 focus:outline-none">
                                                    <i class="fa-solid fa-trash-can w-4 h-4"></i>
                                                </button>
                                            </form>
                                        </div>
                                        <div class="mt-auto flex justify-between items-end">
                                            <span class="font-bold text-brand-dark tracking-tight">Rp {{ number_format($item['price'], 0, ',', '.') }}</span>
                                            
                                            <div class="flex items-center gap-3 bg-brand-light px-2 py-1 rounded-lg border border-brand-muted">
                                                <!-- Decrement Form -->
                                                <form action="{{ route('cart.update', $item['id']) }}" method="POST" class="inline">
                                                    @csrf
                                                    <input type="hidden" name="quantity" value="{{ $item['quantity'] - 1 }}">
                                                    <button type="submit" class="text-gray-500 hover:text-brand-gold w-5 h-5 flex items-center justify-center font-medium focus:outline-none">-</button>
                                                </form>
                                                
                                                <span class="text-sm font-semibold text-brand-darker">{{ $item['quantity'] }}</span>
                                                
                                                <!-- Increment Form -->
                                                <form action="{{ route('cart.update', $item['id']) }}" method="POST" class="inline">
                                                    @csrf
                                                    <input type="hidden" name="quantity" value="{{ $item['quantity'] + 1 }}">
                                                    <button type="submit" class="text-gray-500 hover:text-brand-gold w-5 h-5 flex items-center justify-center font-medium focus:outline-none">+</button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        @endif
                    </div>

                    <!-- Footer / Checkout -->
                    @if(count($cart) > 0)
                        <div class="p-5 md:p-6 bg-brand-light border-t border-brand-muted space-y-4">
                            <!-- Coupon Trigger -->
                            <button class="w-full flex justify-between items-center bg-white p-3 md:p-4 rounded-xl border border-brand-muted hover:border-brand-gold transition-colors group shadow-sm focus:outline-none">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-full bg-brand-light text-brand-gold flex items-center justify-center">
                                        <span class="font-bold text-lg">%</span>
                                    </div>
                                    <span class="font-bold text-gray-700 group-hover:text-brand-dark">Pilih Kupon yang tersedia</span>
                                </div>
                                <i class="fa-solid fa-arrow-right w-4 h-4 text-gray-400 group-hover:text-brand-gold transition-transform group-hover:translate-x-1"></i>
                            </button>
                            
                            <div class="space-y-2 pt-2">
                                <div class="flex justify-between text-sm text-gray-500">
                                    <span>Subtotal</span>
                                    <span class="font-semibold text-gray-800">Rp {{ number_format($cartTotal, 0, ',', '.') }}</span>
                                </div>
                                <div class="flex justify-between text-base md:text-lg font-bold text-brand-darker border-t border-brand-muted pt-3 mt-1">
                                    <span>Total</span>
                                    <span class="text-brand-dark text-xl md:text-2xl tracking-tight">Rp {{ number_format($cartTotal, 0, ',', '.') }}</span>
                                </div>
                            </div>

                            <button class="w-full py-4 text-center rounded-xl font-bold bg-brand-dark hover:bg-brand-darker text-brand-gold transition-transform active:scale-[0.98] shadow-lg shadow-brand-dark/20 mt-2 flex justify-center items-center gap-2 focus:outline-none">
                                Checkout Sekarang
                                <i class="fa-solid fa-cart-shopping w-5 h-5"></i>
                            </button>
                            
                            <p class="text-center text-xs text-gray-400">Pajak dan ongkos kirim dihitung saat checkout.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
