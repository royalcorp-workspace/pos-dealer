<?php

declare(strict_types=1);

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Frontend\Customer\Address;
use App\Models\Frontend\Customer\Customer;
use App\Models\Frontend\Location\SubDistrict;
use App\Models\Frontend\Promo\PriceProductSetting;
use App\Models\Frontend\Promo\Voucher;
use App\Models\Frontend\Promo\VoucherUsage;
use App\Models\Frontend\Shipping\Courier;
use App\Models\Frontend\Shipping\ShippingAddress;
use App\Models\Frontend\ProductsCatalog\Product;
use App\Models\Frontend\ProductsCatalog\ProductCategory;
use App\Models\Frontend\Order;
use App\Models\Frontend\Order\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;

class CheckoutController extends Controller
{
    public function index()
    {
        $cart = Session::get('cart', []);
        $vouchers = $this->getAvailableVouchers($cart);
        $couriers = Courier::with('shippingAddresses')->get();
        $selectedVoucher = Session::get('selected_voucher');
        $selectedVoucherCodes = $this->getSelectedVoucherCodesFromSession();
        $savedAddresses = collect();
        $savedAddressesSafe = collect();
        $checkoutFormData = Session::get('checkout_form_data', []);
        $cartBackup = Session::get('cart_backup', []);
        $subDistricts = \App\Models\Frontend\Location\SubDistrict::with('city')
            ->orderBy('sub_district')
            ->get()
            ->map(fn($sd) => [
                'id' => $sd->id,
                'label' => $sd->sub_district . ', ' . ($sd->city->name ?? ''),
                'postal_code' => $sd->postal_code,
                'city' => $sd->city->name ?? '',
            ]);

        if (session()->get('is_logged_in')) {
            $user = session()->get('user', []);
            $userId = $user['id'] ?? $user['sub'] ?? null;
            $savedAddresses = Address::where('user_id', $userId)
                ->with('subDistrict.city')
                ->orderByDesc('is_primary')
                ->get();
            $savedAddressesSafe = $savedAddresses->map(function ($a) {
                return [
                    'id' => $a->id,
                    'recipient_name' => $a->recipient_name,
                    'phone' => $a->phone,
                    'city' => $a->subDistrict->city->name ?? '',
                    'address' => $a->address,
                    'postal_code' => $a->postal_code,
                    'sub_district_id' => $a->sub_district_id,
                ];
            });
        }

        if ($cartBackup) {
            Session::put('cart', array_merge($cart, $cartBackup));
            Session::forget('cart_backup');
        }

        return view('frontend.checkout', compact('cart', 'vouchers', 'couriers', 'selectedVoucher', 'selectedVoucherCodes', 'savedAddresses', 'savedAddressesSafe', 'checkoutFormData', 'subDistricts'));
    }

    public function store(Request $request)
    {
        $formFields = $request->only(['name', 'email', 'phone', 'address', 'postal_code', 'courier', 'voucher_code', 'selected_address_id', 'sub_district_id']);

        if (!session()->get('is_logged_in')) {
            $request->session()->put('checkout_form_data', $formFields);
            $request->session()->put('cart_backup', session()->get('cart', []));
            return redirect()->route('checkout')->with('show_login', true);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email',
            'phone' => 'required|string|max:20',
            'sub_district_id' => 'required|uuid',
            'address' => 'required|string|max:500',
            'courier' => 'required|string',
            'voucher_code' => 'nullable|string|max:200',
            'voucher_codes' => 'nullable|string|max:500',
            'selected_address_id' => 'nullable|uuid|exists:addresses,id',
            'item_notes' => 'nullable|array',
            'item_notes.*' => 'nullable|string|max:500',
        ]);

        $cart = Session::get('cart', []);
        $itemNotes = (array) $request->input('item_notes', []);
        $cartTotal = collect($cart)->sum(fn($item) => ($item['price'] ?? 0) * ($item['quantity'] ?? 0));

        $priceProductSettingDiscount = $this->calculatePriceProductSettingDiscount($cart, $cartTotal);

        $voucherDiscount = 0;
        $voucher = null;
        $appliedVouchers = [];
        $voucherCodes = $this->parseVoucherCodes($request);
        if ($voucherCodes) {
            $shippingCostForVoucher = $this->getShippingCost($request->courier, $request->sub_district_id);
            $voucherResult = $this->calculateVoucherDiscount($voucherCodes, $cart, $cartTotal, $shippingCostForVoucher);
            $voucherDiscount = $voucherResult['discount'];
            $voucher = $voucherResult['primary'];
            $appliedVouchers = $voucherResult['vouchers'];
        }

        $shippingCost = $this->getShippingCost($request->courier, $request->sub_district_id);
        $subtotal = $cartTotal;
        $totalDiscount = $priceProductSettingDiscount + $voucherDiscount;
        $total = max(0, $subtotal - $totalDiscount + $shippingCost);

        $addressId = $request->selected_address_id;
        $userId = null;

        if (session()->get('is_logged_in')) {
            $user = session()->get('user', []);
            $userId = $user['id'] ?? $user['sub'] ?? null;

            if ($addressId) {
                Address::where('id', $addressId)->update(['is_primary' => true]);
            } else {
                $subDistrict = SubDistrict::findOrFail($request->sub_district_id);
                Address::create([
                    'id' => Str::uuid(),
                    'user_id' => $userId,
                    'sub_district_id' => $request->sub_district_id,
                    'city_id' => $subDistrict->city_id,
                    'label' => 'Rumah',
                    'recipient_name' => $request->name,
                    'phone' => $request->phone,
                    'address' => $request->address,
                    'postal_code' => $subDistrict->postal_code,
                    'is_primary' => true,
                ]);
            }

            Customer::updateOrCreate(
                ['email' => $request->email],
                [
                    'user_id' => $userId,
                    'name' => $request->name,
                    'phone' => $request->phone,
                ]
            );
        }

        $orderId = 'ORD-' . date('Ymd') . '-' . rand(1000, 9999);
        $order = Order::create([
            'id' => Str::uuid(),
            'customer_id' => session()->get('is_logged_in') ? null : Str::uuid(),
            'status' => Order::STATUS_PENDING_APPROVAL,
            'payment_method' => null,
            'payment_status' => 1,
            'subtotal' => $subtotal,
            'tax' => 0,
            'discount' => $totalDiscount,
            'total' => $total,
            'notes' => null,
        ]);

        $productIds = collect($cart)->pluck('product_id')->filter()->unique()->toArray();
        [$globalSettings, $perProductSettings, $volumeSettings] = $this->getPriceProductSettings($productIds);

        foreach ($cart as $item) {
            $itemDiscount = $this->calculateItemPriceProductSettingDiscount($item, $globalSettings, $perProductSettings, $volumeSettings);
            OrderItem::create([
                'id' => Str::uuid(),
                'order_id' => $order->id,
                'product_id' => $item['product_id'],
                'product_variant_id' => $item['variant_id'] ?? null,
                'name' => $item['name'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['price'],
                'discount_nominal' => $itemDiscount['nominal'] ?? 0,
                'discount_percent' => $itemDiscount['percent'] ?? 0,
                'total' => $item['price'] * $item['quantity'],
                'item_notes' => $item['item_note'] ?? ($itemNotes[$item['id']] ?? ''),
            ]);
        }

        foreach ($appliedVouchers as $appliedVoucher) {
            VoucherUsage::create([
                'id' => Str::uuid(),
                'voucher_id' => $appliedVoucher['voucher']->id,
                'user_id' => $userId,
                'order_id' => $orderId,
                'discount_amount' => $appliedVoucher['discount'],
            ]);
        }

        Session::put('selected_voucher_codes', $voucherCodes);
        Session::put('order_data', [
            'id' => $orderId,
            'customer' => [
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'user_id' => $userId,
                'selected_address_id' => $addressId,
            ],
            'courier' => $request->courier,
            'shipping_cost' => $shippingCost,
            'subtotal' => $subtotal,
            'price_product_setting_discount' => $priceProductSettingDiscount,
            'voucher_discount' => $voucherDiscount,
            'total_discount' => $totalDiscount,
            'total' => $total,
            'voucher_code' => implode(',', $voucherCodes),
            'voucher_codes' => $voucherCodes,
            'voucher_id' => $voucher?->id,
            'voucher_ids' => collect($appliedVouchers)->pluck('voucher.id')->filter()->values()->all(),
            'items' => array_map(function ($item) use ($itemNotes) {
                $item['item_note'] = $item['item_note'] ?? ($itemNotes[$item['id']] ?? '');
                return $item;
            }, array_values($cart)),
        ]);

        Session::put('cart', []);

        return redirect()->route('payment');
    }

    public function payment()
    {
        if (!session()->get('is_logged_in')) {
            return redirect()->route('home')->with('show_login', true);
        }

        $orderData = session()->get('order_data', []);
        if (empty($orderData)) {
            return redirect()->route('checkout')->with('warning', 'Data order tidak ditemukan.');
        }

        $paymentMethods = \App\Models\PaymentMethod::active()
            ->orderBy('sort_order')
            ->get()
            ->map(function ($method) {
                return [
                    'code' => $method->code,
                    'name' => $method->name,
                    'type' => match($method->type) {
                        1, 2 => 'transfer',
                        3, 4, 7, 8 => 'ewallet',
                        default => 'other'
                    },
                    'has_charge' => $method->has_charge,
                    'charge_value' => $method->charge_value,
                ];
            })
            ->toArray();

        $user = session()->get('user', []);
        $userId = $user['id'] ?? $user['sub'] ?? null;
        $address = \App\Models\Frontend\Customer\Address::where('user_id', $userId)->where('is_primary', true)->first();

        return view('frontend.payment', compact('orderData', 'paymentMethods', 'address'));
    }

    public function cancelOrder(Request $request, string $orderId)
    {
        $order = \App\Models\Frontend\Order::where(function($q) use ($orderId) {
            $q->where('id', $orderId)
              ->orWhereRaw("CONCAT('ORD-', YEAR(created_at), DATE_FORMAT(created_at, '%m%d'), '-', LPAD(id, 4, '0')) = ?", [$orderId]);
        })->first();

        if (!$order) {
            return redirect()->back()->with('error', 'Order tidak ditemukan.');
        }

        // Only allow cancellation before processing
        if (!in_array($order->status, [\App\Models\Frontend\Order::STATUS_DRAFT, \App\Models\Frontend\Order::STATUS_PENDING_APPROVAL, \App\Models\Frontend\Order::STATUS_CONFIRMED])) {
            return redirect()->back()->with('error', 'Order tidak dapat dibatalkan pada status ini.');
        }

        $order->update([
            'status' => \App\Models\Frontend\Order::STATUS_CANCELLED,
            'notes' => ($order->notes ? $order->notes . ' | ' : '') . 'Order dibatalkan pelanggan pada ' . now()->format('d/m/Y H:i'),
        ]);

        return redirect()->route('dashboard')->with('success', 'Order berhasil dibatalkan.');
    }

    public function reorder(string $orderId)
    {
        $order = \App\Models\Frontend\Order::where(function($q) use ($orderId) {
            $q->where('id', $orderId)
              ->orWhereRaw("CONCAT('ORD-', YEAR(created_at), DATE_FORMAT(created_at, '%m%d'), '-', LPAD(id, 4, '0')) = ?", [$orderId]);
        })->first();

        if (!$order || $order->status !== \App\Models\Frontend\Order::STATUS_CANCELLED) {
            return redirect()->back()->with('error', 'Order tidak valid untuk di-reorder.');
        }

        // Restore cart items
        $cart = session()->get('cart', []);
        foreach ($order->items as $item) {
            $cart[] = [
                'id' => uniqid(),
                'product_id' => $item->product_id,
                'variant_id' => $item->product_variant_id,
                'name' => $item->name,
                'price' => $item->unit_price,
                'quantity' => $item->quantity,
            ];
        }

        session()->put('cart', $cart);
        session()->put('reorder_for', $orderId);

        return redirect()->route('checkout')->with('success', 'Silakan cek keranjang untuk order ulang.');
    }

    public function thankYou()
    {
        return view('frontend.thankyou');
    }

    public function registerSuccess()
    {
        return view('frontend.register-success');
    }

    public function passwordOtpSent()
    {
        return view('frontend.password-otp-sent', ['email' => request()->query('email', '')]);
    }

    public function orderPreview()
    {
        $preview = session()->get('order_preview');
        if (!$preview) {
            return redirect()->route('checkout')->with('warning', 'Silakan isi data checkout terlebih dahulu.');
        }
        return view('frontend.order-preview', compact('preview'));
    }

    private function getAvailableVouchers(array $cart): \Illuminate\Support\Collection
    {
        if (empty($cart)) {
            return collect();
        }

        $cartProductIds = collect($cart)->pluck('product_id')->filter()->unique()->values()->all();
        $cartCategoryIds = Product::whereIn('id', $cartProductIds)->pluck('category_id')->unique()->values()->all();

        return Voucher::active()
            ->with(['products', 'categories'])
            ->get()
            ->filter(function ($voucher) use ($cartProductIds, $cartCategoryIds) {
                return $this->voucherAppliesToCart($voucher, $cartProductIds, $cartCategoryIds);
            })
            ->values();
    }

    private function voucherAppliesToCart(Voucher $voucher, array $cartProductIds, array $cartCategoryIds): bool
    {
        if ((int) $voucher->scope === 2) {
            return $voucher->products()->where('deleted', false)->whereIn('products.id', $cartProductIds)->exists();
        }

        if ((int) $voucher->scope === 3) {
            return $voucher->categories()->where('deleted', false)->whereIn('product_category.id', $cartCategoryIds)->exists();
        }

        return true;
    }

    private function parseVoucherCodes(Request $request): array
    {
        $rawCodes = $request->input('voucher_codes') ?: $request->input('voucher_code');
        if (!$rawCodes) {
            return [];
        }

        return array_values(array_unique(array_filter(array_map('strtoupper', preg_split('/[,;]+/', (string) $rawCodes) ?: []))));
    }

    private function getSelectedVoucherCodesFromSession(): array
    {
        $codes = Session::get('selected_voucher_codes', []);
        if (is_string($codes)) {
            return array_values(array_unique(array_filter(array_map('strtoupper', preg_split('/[,;]+/', $codes) ?: []))));
        }

        return array_values(array_unique(array_filter(array_map('strtoupper', (array) $codes))));
    }

    private function calculateVoucherDiscount(array $codes, array $cart, float $cartTotal, ?float $shippingCost): array
    {
        $userId = session()->get('is_logged_in') ? (session()->get('user')['id'] ?? session()->get('user')['sub'] ?? null) : null;
        $vouchers = Voucher::active()
            ->with(['products', 'categories'])
            ->whereIn('code', $codes)
            ->get()
            ->keyBy('code');

        $orderedVouchers = collect($codes)->map(fn($code) => $vouchers->get($code))->filter()->values();
        if ($orderedVouchers->count() > 1 && $orderedVouchers->contains(fn($voucher) => !$voucher->isStackable())) {
            $orderedVouchers = $orderedVouchers->take(-1);
        }

        $appliedVouchers = [];
        $discount = 0;
        foreach ($orderedVouchers as $voucher) {
            if (!$voucher->canBeUsedBy($userId)) {
                continue;
            }

            $eligibleSubtotal = $this->getVoucherEligibleSubtotal($voucher, $cart);
            if ($eligibleSubtotal < $voucher->min_purchase) {
                continue;
            }

            $voucherDiscount = $this->calculateVoucherDiscountValue($voucher, $eligibleSubtotal, $shippingCost ?? 0);
            $appliedVouchers[] = [
                'voucher' => $voucher,
                'discount' => $voucherDiscount,
            ];
            $discount += $voucherDiscount;
        }

        return [
            'discount' => min($discount, $cartTotal + ($shippingCost ?? 0)),
            'primary' => $orderedVouchers->first(),
            'vouchers' => $appliedVouchers,
        ];
    }

    private function getVoucherEligibleSubtotal(Voucher $voucher, array $cart): float
    {
        if ((int) $voucher->scope === 2) {
            $productIds = $voucher->products()->where('deleted', false)->pluck('products.id')->unique()->toArray();
            return (float) collect($cart)
                ->filter(fn($item) => in_array($item['product_id'] ?? null, $productIds, true))
                ->sum(fn($item) => ($item['price'] ?? 0) * ($item['quantity'] ?? 0));
        }

        if ((int) $voucher->scope === 3) {
            $productIds = $voucher->categories()
                ->where('deleted', false)
                ->with('products')
                ->get()
                ->flatMap(fn($category) => $category->products->where('deleted', false)->pluck('id'))
                ->unique()
                ->toArray();

            return (float) collect($cart)
                ->filter(fn($item) => in_array($item['product_id'] ?? null, $productIds, true))
                ->sum(fn($item) => ($item['price'] ?? 0) * ($item['quantity'] ?? 0));
        }

        return (float) collect($cart)->sum(fn($item) => ($item['price'] ?? 0) * ($item['quantity'] ?? 0));
    }

    private function calculateVoucherDiscountValue(Voucher $voucher, float $eligibleSubtotal, float $shippingCost): float
    {
        if ($voucher->type == 1) {
            return min(($eligibleSubtotal * $voucher->value / 100), $voucher->max_discount ?? PHP_FLOAT_MAX);
        }

        if ($voucher->type == 2) {
            return min($voucher->value, $eligibleSubtotal);
        }

        if ($voucher->type == 3) {
            return min($voucher->value, $shippingCost);
        }

        if ($voucher->type == 4) {
            return 0; // Bonus produk ditangani terpisah
        }

        return 0;
    }

    private function calculatePriceProductSettingDiscount(array $cart, float $cartTotal): float
    {
        $productIds = collect($cart)->pluck('product_id')->filter()->unique()->toArray();
        [$globalSettings, $perProductSettings, $volumeSettings] = $this->getPriceProductSettings($productIds);

        $discount = 0;
        foreach ($cart as $item) {
            $discount += $this->calculateItemPriceProductSettingDiscount($item, $globalSettings, $perProductSettings, $volumeSettings)['total'];
        }
        return min($discount, $cartTotal);
    }

    private function getPriceProductSettings(array $productIds): array
    {
        $globalSettings = PriceProductSetting::active()->where('type', 1)->where('scope', 1)
            ->whereHas('products', fn($q) => $q->whereIn('products.id', $productIds))
            ->get();
        $perProductSettings = PriceProductSetting::active()->where('type', 1)->where('scope', 2)
            ->whereHas('products', fn($q) => $q->whereIn('products.id', $productIds))
            ->get();
        $volumeSettings = PriceProductSetting::active()->where('type', 2)->get();

        return [$globalSettings, $perProductSettings, $volumeSettings];
    }

    private function calculateItemPriceProductSettingDiscount(array $item, $globalSettings, $perProductSettings, $volumeSettings): array
    {
        $itemTotal = ($item['price'] ?? 0) * ($item['quantity'] ?? 0);
        $quantity = $item['quantity'] ?? 0;
        $discount = 0;
        $nominal = 0;

        foreach ($volumeSettings as $vs) {
            $volumeTiers = $vs->volume_tiers ?? [];
            if (!empty($volumeTiers) && is_array($volumeTiers)) {
                foreach ($volumeTiers as $tier) {
                    $minQty = $tier['min_quantity'] ?? 0;
                    $maxQty = $tier['max_quantity'] ?? PHP_INT_MAX;
                    if ($quantity >= $minQty && $quantity <= $maxQty) {
                        $discountAmount = $this->calculateDiscountValue(
                            (int) ($tier['discount_type'] ?? $vs->discount_type),
                            (float) ($tier['discount_value'] ?? $vs->discount_value),
                            $itemTotal,
                            (float) ($vs->max_discount ?? $itemTotal)
                        );
                        $discount += $discountAmount;
                        if ((int) ($tier['discount_type'] ?? $vs->discount_type) === 2) {
                            $nominal += $discountAmount;
                        }
                    }
                }
            }
        }

        foreach ($globalSettings as $pps) {
            $discountAmount = $this->calculateDiscountValue(
                $pps->discount_type,
                (float) $pps->discount_value,
                $itemTotal,
                (float) ($pps->max_discount ?? $itemTotal)
            );
            $discount += $discountAmount;
            if ((int) $pps->discount_type === 2) {
                $nominal += $discountAmount;
            }
        }

        $itemPps = $perProductSettings->filter(fn($p) => $p->products->contains('id', $item['product_id']));
        foreach ($itemPps as $pps) {
            $pivot = $pps->products->first(fn($p) => $p->id === $item['product_id'])->pivot;
            $discountAmount = $this->calculateDiscountValue(
                (int) ($pivot->discount_type ?? $pps->discount_type),
                (float) ($pivot->discount_value ?? $pps->discount_value),
                $itemTotal,
                (float) ($pps->max_discount ?? $itemTotal)
            );
            $discount += $discountAmount;
            if ((int) ($pivot->discount_type ?? $pps->discount_type) === 2) {
                $nominal += $discountAmount;
            }
        }

        $total = min($discount, $itemTotal);
        $percentAmount = max(0, $total - $nominal);
        $percent = $itemTotal > 0 ? round(($percentAmount / $itemTotal) * 100, 2) : 0;

        return [
            'total' => $total,
            'nominal' => $nominal,
            'percent' => $percent,
        ];
    }

    private function calculateDiscountValue($type, float $value, float $itemTotal, ?float $maxDiscount): float
    {
        $maxDiscount = $maxDiscount ?? $itemTotal;
        if ($type == 1) {
            return min($itemTotal * ($value / 100), $maxDiscount);
        }
        return min($value, $itemTotal, $maxDiscount);
    }

    private function getShippingCost(string $courier, string $subDistrictId): int
    {
        $courierModel = Courier::where('code', $courier)->first();
        if (!$courierModel) {
            return 25000;
        }

        $shipping = ShippingAddress::where('courier_id', $courierModel->id)
            ->where('sub_district_id', $subDistrictId)
            ->where('type', 1)
            ->first();

        return $shipping->price ?? 25000;
    }
}
