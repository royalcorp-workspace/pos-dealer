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
                $userNote = '';
                if ($item->item_notes) {
                    $decodedNotes = is_string($item->item_notes) ? json_decode($item->item_notes, true) : (is_array($item->item_notes) ? $item->item_notes : null);
                    if (is_array($decodedNotes) && (isset($decodedNotes['bundle_id']) || isset($decodedNotes['bundle_name']) || isset($decodedNotes['items']))) {
                        $isBundle = true;
                        $bundleNotes = $decodedNotes;
                        $userNote = $decodedNotes['user_note'] ?? '';
                    } else {
                        $userNote = is_string($item->item_notes) ? $item->item_notes : '';
                    }
                }
                $displayName = $item->name;
                if ($isBundle && !empty($bundleNotes['bundle_name'])) {
                    $displayName = $bundleNotes['bundle_name'];
                }
                return [
                    'id' => $item->id,
                    'product_id' => $item->product_id,
                    'variant_id' => $item->product_variant_id,
                    'name' => $displayName,
                    'brand' => $item->product->brand->name ?? '',
                    'image' => $item->product->thumbnail_url ?? '',
                    'sell_price' => (float) $item->unit_price,
                    'quantity' => (int) $item->quantity,
                    'item_note' => $userNote,
                    'type' => $isBundle ? 'bundle' : 'product',
                    'bundle_data' => $bundleNotes,
                ];
            })
            ->toArray();
    }
    $cartItemCount = collect($cart)->sum('quantity');
    $cartTotal = 0.0;
    foreach ($cart as $item) {
        $isBundle = ($item['type'] ?? null) === 'bundle' || str_starts_with($item['name'] ?? '', 'BUNDLE_');
        $bundleData = $item['bundle_data'] ?? null;
        if ($isBundle && $bundleData) {
            $originalPrice = (float) ($bundleData['bundle_price'] ?? ($bundleData['bundle_total_original'] ?? ($item['sell_price'] ?? 0)));
        } else {
            $variantId = $item['variant_id'] ?? ($item['id'] !== ($item['product_id'] ?? null) ? $item['id'] : null);
            $originalPrice = 0.0;
            if ($variantId) {
                $variantModel = \App\Models\Frontend\ProductsCatalog\ProductVariant::find($variantId);
                if ($variantModel) {
                    $originalPrice = (float) $variantModel->sell_price;
                }
            }
            if ($originalPrice <= 0.0 && !empty($item['product_id'])) {
                $productModel = \App\Models\Frontend\ProductsCatalog\Product::find($item['product_id']);
                if ($productModel) {
                    $originalPrice = (float) ($productModel->variants->where('status', true)->min('sell_price') ?? 0);
                }
            }
            if ($originalPrice <= 0.0) {
                $originalPrice = (float) ($item['sell_price'] ?? 0);
            }
        }
        $res = \App\Services\StaticPromoService::calculateItemDiscounts($item, (int) ($item['quantity'] ?? 1), $originalPrice);
        $cartTotal += (float)$res['promotional_price'] * (int) ($item['quantity'] ?? 1);
    }
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
                            {{ __('Shopping Cart') }} <span class="text-gray-400 font-normal text-base">(<span id="cart-drawer-count">{{ $cartItemCount }}</span> {{ __('items') }})</span>
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
