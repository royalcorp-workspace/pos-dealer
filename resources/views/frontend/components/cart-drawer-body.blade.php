@php
    if (!isset($cart)) {
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
    }
    $cartItemCount = collect($cart)->sum('quantity');
    
    $cartTotal = 0.0;
    foreach($cart as $item) {
        $isBundle = ($item['type'] ?? null) === 'bundle';
        $bundleData = $item['bundle_data'] ?? null;

        if ($isBundle && $bundleData) {
            $originalPrice = (float) ($bundleData['bundle_price'] ?? ($bundleData['bundle_total_original'] ?? 0));
        } else {
            $variantId = $item['variant_id'] ?? ($item['id'] !== $item['product_id'] ? $item['id'] : null);
            $originalPrice = 0.0;
            if ($variantId) {
                $variantModel = \App\Models\Frontend\ProductsCatalog\ProductVariant::find($variantId);
                if ($variantModel) {
                    $originalPrice = (float) $variantModel->price;
                }
            }
            if ($originalPrice <= 0.0) {
                $productModel = \App\Models\Frontend\ProductsCatalog\Product::find($item['product_id']);
                if ($productModel) {
                    $originalPrice = (float) $productModel->base_price;
                }
            }
            if ($originalPrice <= 0.0) {
                $originalPrice = (float) $item['price'];
            }
        }
        $res = \App\Services\StaticPromoService::calculateItemDiscounts($item, (int) $item['quantity'], $originalPrice);
        $cartTotal += $res['promotional_price'] * (int) $item['quantity'];
    }

    $cartProductIds = collect($cart)->pluck('product_id')->filter()->unique()->values()->all();
    $cartCategoryIds = \App\Models\Frontend\ProductsCatalog\Product::whereIn('id', $cartProductIds)->pluck('category_id')->unique()->values()->all();
    $userId = session()->get('is_logged_in') ? (session()->get('user')['id'] ?? session()->get('user')['sub'] ?? null) : null;
    $cartCoupons = \App\Models\Frontend\Promo\Voucher::active()->with(['products', 'categories'])->get()->filter(function($coupon) use ($cartProductIds, $cartCategoryIds) {
        if ((int) $coupon->scope === 2) {
            return $coupon->products()->where('deleted', false)->whereIn('products.id', $cartProductIds)->exists();
        }

        if ((int) $coupon->scope === 3) {
            return $coupon->categories()->where('deleted', false)->whereIn('product_category.id', $cartCategoryIds)->exists();
        }

        return true;
    })->map(function ($coupon) use ($userId) {
        $coupon->is_usable = $coupon->canBeUsedBy($userId);
        return $coupon;
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
            @php
                $isBundle = ($item['type'] ?? null) === 'bundle';
                $bundleData = $item['bundle_data'] ?? null;

                if ($isBundle && $bundleData) {
                    $originalPrice = (float) ($bundleData['bundle_price'] ?? 0);
                } else {
                    $variantId = $item['variant_id'] ?? ($item['id'] !== $item['product_id'] ? $item['id'] : null);
                    $originalPrice = 0.0;
                    if ($variantId) {
                        $variantModel = \App\Models\Frontend\ProductsCatalog\ProductVariant::find($variantId);
                        if ($variantModel) {
                            $originalPrice = (float) $variantModel->price;
                        }
                    }
                    if ($originalPrice <= 0.0) {
                        $productModel = \App\Models\Frontend\ProductsCatalog\Product::find($item['product_id']);
                        if ($productModel) {
                            $originalPrice = (float) $productModel->base_price;
                        }
                    }
                    if ($originalPrice <= 0.0) {
                        $originalPrice = (float) $item['price'];
                    }
                }
                $res = \App\Services\StaticPromoService::calculateItemDiscounts($item, (int) $item['quantity'], $originalPrice);
                $itemPrice = $res['promotional_price'];

                $promoSuggest = null;
                $currentQty = (int) $item['quantity'];
                if (!$isBundle) {
                    $itemVolumeSettings = \App\Models\Frontend\Promo\PriceProductSetting::active()
                        ->where('type', 2)
                        ->where(function($q) use ($item) {
                            $q->where('scope', 1)
                              ->orWhereHas('products', function($q2) use ($item) {
                                  $q2->where('products.id', $item['product_id']);
                              });
                        })
                        ->with('volumeTiers')
                        ->get();

                    foreach ($itemVolumeSettings as $vs) {
                        $tiers = $vs->volume_tiers ?? [];
                        if (!empty($tiers) && is_array($tiers)) {
                            usort($tiers, fn($a, $b) => $a['min_quantity'] <=> $b['min_quantity']);
                            foreach ($tiers as $tier) {
                                $minQty = (int) ($tier['min_quantity'] ?? 0);
                                $discountVal = $tier['discount_value'] ?? 0;
                                $discountType = $tier['discount_type'] ?? 1;
                                $discountStr = $discountType == 1 ? $discountVal . '%' : 'Rp ' . number_format((float) $discountVal, 0, ',', '.');
                                if ($currentQty < $minQty) {
                                    $neededQty = $minQty - $currentQty;
                                    $promoSuggest = "Beli {$neededQty} unit lagi untuk dapat diskon {$discountStr}!";
                                    break;
                                }
                            }
                        }
                        if ($promoSuggest) break;
                    }
                }
            @endphp
            <div data-cart-item-id="{{ $item['id'] }}" class="flex gap-4 p-4 border border-gray-100 rounded-2xl bg-white shadow-sm">
                <div class="w-24 h-24 bg-gray-50 rounded-xl overflow-hidden flex-shrink-0">
                    <img src="{{ $item['image'] }}" alt="{{ $isBundle ? ($bundleData['bundle_name'] ?? 'Bundle') : $item['name'] }}" loading="lazy" decoding="async" class="w-full h-full object-cover" />
                </div>
                <div class="flex flex-col flex-1">
                    <div class="flex justify-between items-start">
                        <div>
                            @if($isBundle)
                                <span class="text-[10px] uppercase font-bold tracking-wider text-purple-600 bg-purple-100 px-2 py-0.5 rounded">Bundling Hemat</span>
                            @else
                                <span class="text-[10px] uppercase font-bold tracking-wider text-gray-400">{{ $item['brand'] }}</span>
                            @endif
                            <h4 class="font-semibold text-gray-900 text-sm leading-snug line-clamp-2 mt-0.5">
                                @if($isBundle)
                                    {{ $bundleData['bundle_name'] ?? 'Bundle Product' }}
                                @else
                                    {{ $item['name'] }}
                                @endif
                            </h4>
                            @if($isBundle && $bundleData)
                                <div class="mt-1 text-xs text-gray-500 bg-gray-50 px-2 py-1 rounded">
                                    @foreach(($bundleData['items'] ?? []) as $bundleItem)
                                        <div class="flex justify-between">
                                            <span>{{ $bundleItem['product_name'] ?? 'Produk' }} ({{ $bundleItem['quantity'] ?? 1 }}x)</span>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                            @if(isset($item['color_name']) && $item['color_name'])
                                <div class="text-xs text-brand-gold-dark mt-1 font-medium">Warna: {{ $item['color_name'] }}</div>
                            @endif
                            @if(isset($item['size']) && $item['size'])
                                <div class="text-xs text-brand-gold-dark mt-1 font-medium">{{ $item['size'] }}</div>
                            @endif
                            
                            @if($promoSuggest)
                                <div class="mt-2 flex items-center gap-1.5 bg-brand-gold/10 text-brand-gold-dark px-2.5 py-1.5 rounded-lg text-[11px] font-semibold border border-brand-gold/20">
                                    <i class="fa-solid fa-gift text-xs text-brand-gold"></i>
                                    <span>{{ $promoSuggest }}</span>
                                </div>
                            @endif
                        </div>
                        <form action="{{ route('cart.remove', $item['id']) }}" method="POST">
                            @csrf
                            <button type="submit" class="text-gray-400 hover:text-red-500 p-1 focus:outline-none" aria-label="Hapus item">
                                <i class="fa-solid fa-trash-can w-4 h-4"></i>
                            </button>
                        </form>
                        @if($isBundle)
                            <p class="mt-2 rounded-lg bg-brand-light p-2 text-xs text-gray-600">
                                Simpan <strong>{{ $bundleData['bundle_name'] ?? '' }}</strong> — dapatkan {{ count($bundleData['items'] ?? []) }} produk dalam paket hemat.
                            </p>
                        @elseif(!empty($item['item_note']))
                            <p class="mt-2 rounded-lg bg-brand-light p-2 text-xs text-gray-600">{{ $item['item_note'] }}</p>
                        @endif
                    </div>
                    <div class="mt-auto flex justify-between items-end">
                    <div class="flex flex-col items-start">
                        @if($res['volume_discount'] > 0 || $res['static_discount'] > 0 || ($isBundle && $bundleData && (float)($bundleData['bundle_price'] ?? 0) > 0))
                            @php
                                $discountPercent = $originalPrice > 0 ? round((($originalPrice - $itemPrice) / $originalPrice) * 100) : 0;
                            @endphp
                            @if($discountPercent > 0)
                                <div class="flex items-center gap-1.5 mb-0.5">
                                    <span class="text-xs line-through text-gray-400 font-semibold">
                                        Rp {{ number_format($originalPrice, 0, ',', '.') }}
                                    </span>
                                    <span class="bg-red-50 text-red-600 text-[9px] font-extrabold px-1.5 py-0.5 rounded-full">{{ $discountPercent }}% OFF</span>
                                </div>
                            @endif
                        @endif
                        <span class="font-bold text-brand-dark tracking-tight">Rp {{ number_format($itemPrice, 0, ',', '.') }}</span>
                    </div>

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

    <div id="cart-footer" class="p-5 md:p-6 bg-brand-light border-t border-brand-muted space-y-4" data-product-ids='@json($cartProductIds)' data-category-ids='@json($cartCategoryIds)' data-cart-total="{{ $cartTotal }}">
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
            <div class="flex gap-2">
                <input type="text" id="manual-cart-voucher-input" placeholder="Kode voucher" class="flex-1 px-3 py-2 border border-brand-muted rounded-xl text-sm focus:outline-none focus:border-brand-gold uppercase" maxlength="20">
                <button type="button" onclick="validateAndApplyCartVoucher()" class="px-4 py-2 bg-brand-dark text-brand-gold rounded-xl font-bold text-xs hover:bg-brand-darker transition-colors whitespace-nowrap">Gunakan</button>
            </div>
            <div id="manual-cart-voucher-feedback" class="text-xs"></div>

            @foreach($cartCoupons as $coupon)
                @php
                    $typeLabel = match($coupon->type) {
                        1 => $coupon->value . '%',
                        2 => 'Rp ' . number_format((float) $coupon->value, 0, ',', '.'),
                        3 => 'Gratis Ongkir',
                        default => 'Tidak diketahui',
                    };
                @endphp
                @php
                    $isUsable = $coupon->is_usable ?? true;
                @endphp
                <button
                     type="button"
                     @if($isUsable)
                         onclick="selectCartCoupon(this)"
                         class="coupon-option w-full text-left rounded-2xl border bg-white p-4 transition-all hover:border-brand-gold hover:shadow-md focus:outline-none focus:ring-2 focus:ring-brand-gold"
                     @else
                         class="coupon-option opacity-50 bg-gray-100 border-gray-300 pointer-events-none cursor-not-allowed w-full text-left rounded-2xl border p-4 transition-all focus:outline-none"
                     @endif
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
                        @if($isUsable)
                            <span class="coupon-option-label text-xs font-bold text-gray-400">Pilih</span>
                        @else
                            <span class="coupon-option-label text-xs font-bold text-red-500 uppercase tracking-wider">Limit Habis</span>
                        @endif
                    </div>
                    @if(!$isUsable)
                        <div class="mt-2 text-[10px] font-bold text-red-600 uppercase tracking-wider border-t pt-2">
                            Kupon sudah pernah digunakan oleh Anda
                        </div>
                    @endif
                </button>
            @endforeach

            <div id="cart-selected-coupon" class="hidden rounded-xl bg-white border border-brand-gold/30 p-3 text-sm text-gray-700">
                <div class="flex justify-between items-center gap-3">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="text-xs text-gray-500">Kupon dipilih:</span>
                            <strong id="cart-selected-title" class="text-brand-dark"></strong>
                        </div>
                        <div class="flex items-center gap-2 mt-0.5 text-red-600">
                            <span class="text-xs">Estimasi hemat:</span>
                            <strong id="cart-selected-discount"></strong>
                        </div>
                    </div>
                    <button type="button" onclick="deselectCartCoupon()" class="text-xs font-bold text-red-500 hover:text-red-700 focus:outline-none px-2 py-1 rounded bg-red-50 hover:bg-red-100 transition-colors">
                        Hapus
                    </button>
                </div>
            </div>
        </div>

        <div class="space-y-2 pt-2">
            <div class="flex justify-between text-sm text-gray-500">
                <span>Subtotal</span>
                <span id="cart-drawer-subtotal" class="font-semibold text-gray-800">Rp {{ number_format($cartTotal, 0, ',', '.') }}</span>
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