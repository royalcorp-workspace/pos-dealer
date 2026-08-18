@php
    $customerId = null;
    if (session()->get('is_logged_in')) {
        $user = session()->get('user', []);
        $userId = $user['id'] ?? $user['sub'] ?? null;
        $email = $user['email'] ?? null;
        if ($userId) {
            $customer = \App\Models\Frontend\Customer\Customer::where('user_id', $userId)->first();
            if (!$customer && $email) {
                $customer = \App\Models\Frontend\Customer\Customer::where('email', $email)->first();
            }
            $customerId = $customer?->id;
        }
    }
    $sessionId = session()->get('guest_session_id', session()->getId());
    
    $buffer = \App\Models\Frontend\Buffer\Buffer::where(function ($q) use ($customerId, $sessionId) {
        if ($customerId) {
            $q->where('customer_id', $customerId);
            if ($sessionId) {
                $q->orWhere('session_id', $sessionId);
            }
        } else if ($sessionId) {
            $q->where('session_id', $sessionId);
        }
    })->first();

    $cart = [];
    if ($buffer) {
        $cart = $buffer->items()
            ->with(['product.brand', 'variant'])
            ->get()
            ->map(function ($item) {
                $isBundle = str_starts_with($item->name ?? '', 'BUNDLE_');
                $bundleNotes = [];
                if ($isBundle && $item->item_notes) {
                    $bundleNotes = json_decode($item->item_notes, true) ?? [];
                }
                return [
                    'id' => $item->id,
                    'product_id' => $item->product_id,
                    'variant_id' => $item->product_variant_id,
                    'name' => $item->name,
                    'brand' => $item->product->brand->name ?? '',
                    'image' => $item->product->thumbnail_url ?? '',
                    'price' => (float) $item->unit_price,
                    'quantity' => (int) $item->quantity,
                    'item_note' => $item->item_notes ?? '',
                    'type' => $isBundle ? 'bundle' : 'product',
                    'bundle_data' => $bundleNotes,
                ];
            })
            ->toArray();
    }
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
                            {{ __('Shopping Cart') }} <span class="text-gray-400 font-normal text-base">({{ $cartItemCount }} {{ __('items') }})</span>
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
