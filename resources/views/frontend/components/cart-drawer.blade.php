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

                    <div id="cart-drawer-body" class="flex-1 flex flex-col bg-white shadow-2xl overflow-y-scroll" data-cart-total="{{ $cartTotal }}">
                        @include('frontend.components.cart-drawer-body')
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
