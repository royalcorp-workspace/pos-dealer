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
        $selectedVouchers = \App\Models\Frontend\Promo\Voucher::active()
            ->whereIn('code', $selectedVoucherCodes)
            ->with('products')
            ->get();
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

        $originalCartTotal = 0.0;
        $totalPercentDiscount = 0.0;
        $totalNominalDiscount = 0.0;
        $priceProductSettingDiscount = 0.0;

        foreach ($cart as $key => $item) {
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
            $cart[$key]['original_price'] = $originalPrice;

            $quantity = (int) $item['quantity'];
            $itemSubtotal = $originalPrice * $quantity;
            $originalCartTotal += $itemSubtotal;

            $res = \App\Services\StaticPromoService::calculateItemDiscounts($item, $quantity, $originalPrice);
            $totalPercentDiscount += $res['static_discount'];
            $priceProductSettingDiscount += $res['volume_discount'];

            // Recalculate cart item price for voucher calculations and checkout displays
            $cart[$key]['price'] = $res['promotional_price'];
        }

        $cartTotal = collect($cart)->sum(fn($item) => $item['price'] * $item['quantity']);

        return view('frontend.checkout', compact('cart', 'vouchers', 'couriers', 'selectedVoucher', 'selectedVoucherCodes', 'savedAddresses', 'savedAddressesSafe', 'checkoutFormData', 'subDistricts', 'priceProductSettingDiscount', 'originalCartTotal', 'totalPercentDiscount', 'totalNominalDiscount', 'cartTotal', 'selectedVouchers'));
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

        $resolvedItems = [];
        $originalCartTotal = 0.0;
        $totalStaticDiscount = 0.0;
        $priceProductSettingDiscount = 0.0;

        foreach ($cart as $key => $item) {
            $variantId = $item['variant_id'] ?? ($item['id'] !== $item['product_id'] ? $item['id'] : null);
            
            // 1. Get original price
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

            $quantity = (int) $item['quantity'];
            $originalSubtotal = $originalPrice * $quantity;
            $originalCartTotal += $originalSubtotal;

            $res = \App\Services\StaticPromoService::calculateItemDiscounts($item, $quantity, $originalPrice);
            $itemStaticDiscount = $res['static_discount'];
            $itemVolumeDiscount = $res['volume_discount'];

            $totalStaticDiscount += $itemStaticDiscount;
            $priceProductSettingDiscount += $itemVolumeDiscount;

            // Recalculate cart item price for voucher calculations and database persistence
            $cart[$key]['price'] = $res['promotional_price'];

            $resolvedItems[] = [
                'item' => $item,
                'variant_id' => $variantId,
                'original_price' => $originalPrice,
                'original_subtotal' => $originalSubtotal,
                'static_promo_discount' => $itemStaticDiscount,
                'volume_promo_discount' => $itemVolumeDiscount,
            ];
        }

        $cartTotal = collect($cart)->sum(fn($item) => ($item['price'] ?? 0) * ($item['quantity'] ?? 0));

        $subDistrictId = $request->sub_district_id;
        $addressId = $request->selected_address_id;
        if (session()->get('is_logged_in') && $addressId) {
            $savedAddress = Address::find($addressId);
            if ($savedAddress) {
                $subDistrictId = $savedAddress->sub_district_id;
            }
        }

        $voucherDiscount = 0;
        $voucher = null;
        $appliedVouchers = [];
        $voucherCodes = $this->parseVoucherCodes($request);
        if ($voucherCodes) {
            $shippingCostForVoucher = $this->getShippingCost($request->courier, $subDistrictId ?? '');
            $voucherResult = $this->calculateVoucherDiscount($voucherCodes, $cart, $cartTotal, $shippingCostForVoucher);
            $voucherDiscount = $voucherResult['discount'];
            $voucher = $voucherResult['primary'];
            $appliedVouchers = $voucherResult['vouchers'];
        }

        $shippingCost = $this->getShippingCost($request->courier, $subDistrictId ?? '');
        $subtotal = $originalCartTotal;
        $totalDiscount = $totalStaticDiscount + $priceProductSettingDiscount + $voucherDiscount;
        $total = max(0, $subtotal - $totalDiscount + $shippingCost);

        $addressId = $request->selected_address_id;
        $userId = null;
        $customer = null;

        if (session()->get('is_logged_in')) {
            $user = session()->get('user', []);
            $userId = $user['id'] ?? $user['sub'] ?? null;

            if ($addressId) {
                Address::where('id', $addressId)->update(['is_primary' => true]);
            } else {
                $subDistrict = SubDistrict::findOrFail($request->sub_district_id);
                Address::create([
                    'id' => Str::uuid()->toString(),
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

            $customer = Customer::updateOrCreate(
                ['email' => $request->email],
                [
                    'user_id' => $userId,
                    'name' => $request->name,
                    'phone' => $request->phone,
                ]
            );
        } else {
            $customer = Customer::where('email', $request->email)->first();
            if (!$customer) {
                $customer = Customer::create([
                    'name' => $request->name,
                    'email' => $request->email,
                    'phone' => $request->phone,
                ]);
            }
        }

        $shippingCostSubsidy = 0;
        if (!empty($appliedVouchers)) {
            foreach ($appliedVouchers as $av) {
                if ($av['voucher']->type == 3) {
                    $shippingCostSubsidy += $av['discount'];
                }
            }
        }

        $courierModel = Courier::where('code', $request->courier)->first();
        $shippingAddressRecord = null;
        if ($courierModel) {
            $shippingAddressRecord = \App\Models\Frontend\Shipping\ShippingAddress::where('courier_id', $courierModel->id)
                ->where('sub_district_id', $request->sub_district_id)
                ->where('type', 1)
                ->first();
        }
        $shippingAddressesId = $shippingAddressRecord ? $shippingAddressRecord->id : null;

        $dbSubtotal = $originalCartTotal - $totalStaticDiscount - $priceProductSettingDiscount;
        $dbDiscount = $voucherDiscount;
        $dbTotal = max(0, $dbSubtotal - $dbDiscount + $shippingCost);

        $orderId = 'ORD-' . date('Ymd') . '-' . rand(1000, 9999);
        $order = Order::create([
            'id' => Str::uuid()->toString(),
            'order_number' => $orderId,
            'customer_id' => $customer ? $customer->id : null,
            'courier_id' => $courierModel ? $courierModel->id : null,
            'status' => Order::STATUS_PENDING_APPROVAL,
            'payment_method' => null,
            'payment_status' => 1,
            'subtotal' => $dbSubtotal,
            'tax' => 0,
            'discount' => $dbDiscount,
            'total' => $dbTotal,
            'notes' => null,
            'voucher_id' => $voucher ? $voucher->id : null,
            'voucher_nominal' => $voucherDiscount,
            'shipping_cost' => $shippingCost,
            'shipping_cost_subsidy' => $shippingCostSubsidy,
            'shipping_addresses_id' => $shippingAddressesId,
        ]);

        $productIds = collect($cart)->pluck('product_id')->filter()->unique()->toArray();
        [$globalSettings, $perProductSettings, $volumeSettings] = $this->getPriceProductSettings($productIds);

        foreach ($resolvedItems as $resolved) {
            $item = $resolved['item'];
            $variantId = $resolved['variant_id'];
            $originalPrice = $resolved['original_price'];
            $originalSubtotal = $resolved['original_subtotal'];
            $staticPromoDiscountTotal = $resolved['static_promo_discount'];

            $productDiscountNominal = $staticPromoDiscountTotal + (float) $resolved['volume_promo_discount'];
            $discountPercent = $originalSubtotal > 0 ? round(($productDiscountNominal / $originalSubtotal) * 100, 2) : 0.0;

            OrderItem::create([
                'id' => Str::uuid(),
                'order_id' => $order->id,
                'product_id' => $item['product_id'],
                'product_variant_id' => $variantId,
                'name' => $item['name'],
                'quantity' => $item['quantity'],
                'unit_price' => $originalPrice,
                'discount_nominal' => $productDiscountNominal,
                'discount_percent' => $discountPercent,
                'total' => max(0, $originalSubtotal - $productDiscountNominal),
                'item_notes' => $item['item_note'] ?? ($itemNotes[$item['id']] ?? ''),
            ]);
        }

        foreach ($appliedVouchers as $appliedVoucher) {
            $appliedVoucherModel = $appliedVoucher['voucher'];
            VoucherUsage::create([
                'id' => Str::uuid(),
                'voucher_id' => $appliedVoucherModel->id,
                'user_id' => $userId,
                'order_id' => $order->id,
                'discount_amount' => $appliedVoucher['discount'],
            ]);

            if ((int)$appliedVoucherModel->type === 4) {
                foreach ($appliedVoucherModel->products as $bp) {
                    OrderItem::create([
                        'id' => Str::uuid(),
                        'order_id' => $order->id,
                        'product_id' => $bp->id,
                        'product_variant_id' => null,
                        'name' => $bp->name . ' (Bonus)',
                        'quantity' => (int) $appliedVoucherModel->value,
                        'unit_price' => 0.0,
                        'discount_nominal' => 0.0,
                        'discount_percent' => 0.0,
                        'total' => 0.0,
                        'item_notes' => 'Bonus Voucher: ' . $appliedVoucherModel->code,
                    ]);
                }
            }
        }

        Session::put('selected_voucher_codes', $voucherCodes);
        Session::put('order_data', [
            'id' => $order->id,
            'order_number' => $order->order_number,
            'customer' => [
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'user_id' => $userId,
                'selected_address_id' => $addressId,
            ],
            'courier' => $request->courier,
            'shipping_cost' => $shippingCost,
            'subtotal' => $dbSubtotal,
            'price_product_setting_discount' => 0.0,
            'voucher_discount' => $voucherDiscount,
            'total_discount' => $voucherDiscount,
            'total' => $dbTotal,
            'transaction_fee' => 0.0,
            'voucher_code' => implode(',', $voucherCodes),
            'voucher_codes' => $voucherCodes,
            'voucher_id' => $voucher?->id,
            'voucher_ids' => collect($appliedVouchers)->pluck('voucher.id')->filter()->values()->all(),
            'items' => array_map(function ($item) use ($itemNotes) {
                $originalPrice = (float) ($item['original_price'] ?? $item['price']);
                $price = (float) $item['price'];
                $discountNominal = $originalPrice - $price;
                $discountPercent = $originalPrice > 0 ? round(($discountNominal / $originalPrice) * 100, 2) : 0.0;

                return [
                    'id' => $item['id'],
                    'product_id' => $item['product_id'],
                    'variant_id' => $item['variant_id'] ?? ($item['id'] !== $item['product_id'] ? $item['id'] : null),
                    'name' => $item['name'],
                    'price' => $originalPrice,
                    'quantity' => (int) $item['quantity'],
                    'item_note' => $item['item_note'] ?? ($itemNotes[$item['id']] ?? ''),
                    'discount_nominal' => $discountNominal,
                    'discount_percent' => $discountPercent,
                    'total' => $price * (int) $item['quantity'],
                ];
            }, array_values($cart)),
        ]);

        return redirect()->route('payment');
    }

    public function payment(Request $request)
    {
        if (!session()->get('is_logged_in')) {
            return redirect()->route('home')->with('show_login', true);
        }

        $orderData = session()->get('order_data', []);
        $orderIdFromUrl = $request->query('order_id');
        
        // If order_data is missing from session, try to retrieve from URL parameter or database
        if (empty($orderData) && $orderIdFromUrl) {
            $order = $this->getOrderFromIdentifier($orderIdFromUrl);
            if ($order) {
                $orderData = $this->formatOrderDataFromModel($order);
                session()->put('order_data', $orderData);
            }
        }
        
        // If still no order data, try to get most recent pending order for logged-in user
        if (empty($orderData)) {
            $user = session()->get('user', []);
            $userId = $user['id'] ?? $user['sub'] ?? null;
            
            if ($userId) {
                $order = Order::with(['customer', 'courier', 'items', 'voucher'])
                    ->whereHas('customer', function ($q) use ($userId) {
                        $q->where('user_id', $userId);
                    })
                    ->where('status', Order::STATUS_PENDING_APPROVAL)
                    ->latest()
                    ->first();
                
                if ($order) {
                    $orderData = $this->formatOrderDataFromModel($order);
                    session()->put('order_data', $orderData);
                }
            }
        }
        
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

    public function processPayment(Request $request)
    {
        $orderData = session()->get('order_data');
        if (!$orderData || empty($orderData['id'])) {
            return response()->json([
                'success' => false,
                'message' => 'Data order tidak ditemukan.'
            ], 404);
        }

        $paymentMethod = $request->input('payment_method');
        if (!$paymentMethod) {
            return response()->json([
                'success' => false,
                'message' => 'Silakan pilih metode pembayaran.'
            ], 400);
        }

        $orderId = $orderData['id'];
        $order = $this->getOrderFromIdentifier($orderId);

        if ($order) {
            $paymentMethodModel = \App\Models\PaymentMethod::where('code', $paymentMethod)->first();
            $charge = 0;
            if ($paymentMethodModel && $paymentMethodModel->has_charge) {
                $charge = (int) $paymentMethodModel->charge_type === 1 
                    ? ($order->total * $paymentMethodModel->charge_value / 100) 
                    : $paymentMethodModel->charge_value;
            }

            $order->update([
                'payment_method' => $paymentMethod,
                'payment_status' => 2, // Terbayar / Menunggu verifikasi
                'transaction_fee' => $charge,
                'total' => $order->total + $charge,
            ]);

            session()->forget('order_data');
            session()->forget('selected_voucher_codes');
            session()->put('thankyou_order_id', $order->id);
            session()->put('cart', []);

            return response()->json([
                'success' => true,
                'redirect_url' => route('thankyou', ['order_id' => $order->id])
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Order tidak ditemukan.'
        ], 404);
    }

    public function cancelOrder(Request $request, string $orderId)
    {
        $order = null;
        if (\Illuminate\Support\Str::isUuid($orderId)) {
            $order = \App\Models\Frontend\Order::where('id', $orderId)->first();
        }
        if (!$order) {
            $order = \App\Models\Frontend\Order::where('order_number', $orderId)->first();
        }

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
        $order = null;
        if (\Illuminate\Support\Str::isUuid($orderId)) {
            $order = \App\Models\Frontend\Order::where('id', $orderId)->first();
        }
        if (!$order) {
            $order = \App\Models\Frontend\Order::where('order_number', $orderId)->first();
        }

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

    public function thankYou(Request $request)
    {
        $orderId = session('thankyou_order_id');
        $orderIdFromUrl = $request->query('order_id');
        $order = null;
        
        // Try to get order from session ID first, then from URL parameter
        if ($orderId) {
            $order = Order::with(['customer', 'courier', 'items', 'voucher'])->find($orderId);
        } elseif ($orderIdFromUrl) {
            $order = $this->getOrderFromIdentifier($orderIdFromUrl);
        } else {
            // As last resort, get most recent order for logged-in user
            if (session()->get('is_logged_in')) {
                $user = session()->get('user', []);
                $userId = $user['id'] ?? $user['sub'] ?? null;
                
                if ($userId) {
                    $order = Order::with(['customer', 'courier', 'items', 'voucher'])
                        ->whereHas('customer', function ($q) use ($userId) {
                            $q->where('user_id', $userId);
                        })
                        ->latest()
                        ->first();
                }
            }
        }
        
        return view('frontend.thankyou', compact('order'));
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

        $userId = session()->get('is_logged_in') ? (session()->get('user')['id'] ?? session()->get('user')['sub'] ?? null) : null;

        return Voucher::active()
            ->with(['products', 'categories'])
            ->get()
            ->filter(function ($voucher) use ($cartProductIds, $cartCategoryIds) {
                return $this->voucherAppliesToCart($voucher, $cartProductIds, $cartCategoryIds);
            })
            ->map(function ($voucher) use ($userId) {
                $voucher->is_usable = $voucher->canBeUsedBy($userId);
                return $voucher;
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
            ->where(function ($query) use ($codes) {
                foreach ($codes as $code) {
                    $query->orWhereRaw('LOWER(code) = ?', [strtolower($code)]);
                }
            })
            ->get()
            ->keyBy(fn($v) => strtoupper($v->code));

        $orderedVouchers = collect($codes)->map(fn($code) => $vouchers->get(strtoupper($code)))->filter()->values();
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
            $maxDiscount = ($voucher->max_discount !== null && (float) $voucher->max_discount > 0) ? (float) $voucher->max_discount : PHP_FLOAT_MAX;
            return min(($eligibleSubtotal * $voucher->value / 100), $maxDiscount);
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
        $globalSettings = collect();
        $perProductSettings = collect();
        $volumeSettings = PriceProductSetting::active()->where('type', 2)->with(['volumeTiers', 'products'])->get();

        return [$globalSettings, $perProductSettings, $volumeSettings];
    }

    private function calculateItemPriceProductSettingDiscount(array $item, $globalSettings, $perProductSettings, $volumeSettings): array
    {
        $itemTotal = ($item['price'] ?? 0) * ($item['quantity'] ?? 0);
        $quantity = $item['quantity'] ?? 0;
        $discount = 0;
        $nominal = 0;

        foreach ($volumeSettings as $vs) {
            // Check scope: if specific products (scope == 2), verify product belongs to this setting
            if ($vs->scope == 2) {
                $hasProduct = $vs->products->contains('id', $item['product_id']);
                if (!$hasProduct) continue;
            }

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
            return 0;
        }

        $shipping = ShippingAddress::where('courier_id', $courierModel->id)
            ->where('sub_district_id', $subDistrictId)
            ->first();

        if (!$shipping) {
            $shipping = ShippingAddress::where('courier_id', $courierModel->id)
                ->first();
        }

        return $shipping ? $shipping->price : 0;
    }

    /**
     * Get order from ID or order number
     */
    private function getOrderFromIdentifier($identifier): ?Order
    {
        $query = Order::with(['customer', 'courier', 'items', 'voucher']);
        
        if (\Illuminate\Support\Str::isUuid($identifier)) {
            return $query->where('id', $identifier)->first();
        }
        return $query->where('order_number', $identifier)->first();
    }

    /**
     * Format order data from model for session storage
     */
    private function formatOrderDataFromModel(Order $order): array
    {
        $items = $order->items->map(function ($item) {
            return [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'variant_id' => $item->product_variant_id,
                'name' => $item->name,
                'price' => (float) $item->unit_price,
                'quantity' => (int) $item->quantity,
                'item_note' => $item->item_notes ?? '',
                'discount_nominal' => (float) $item->discount_nominal,
                'discount_percent' => (float) $item->discount_percent,
                'total' => (float) $item->total,
            ];
        })->toArray();

        return [
            'id' => $order->id,
            'order_number' => $order->order_number,
            'customer' => [
                'name' => $order->customer?->name ?? '',
                'email' => $order->customer?->email ?? '',
                'phone' => $order->customer?->phone ?? '',
                'user_id' => $order->customer?->user_id,
            ],
            'courier' => $order->courier?->code ?? '',
            'shipping_cost' => (float) $order->shipping_cost,
            'subtotal' => (float) $order->subtotal,
            'price_product_setting_discount' => 0.0,
            'voucher_discount' => (float) ($order->voucher_nominal ?? 0),
            'total_discount' => (float) $order->discount,
            'total' => (float) $order->total,
            'transaction_fee' => (float) ($order->transaction_fee ?? 0),
            'voucher_code' => $order->voucher?->code ?? '',
            'voucher_codes' => $order->voucher ? [$order->voucher->code] : [],
            'voucher_id' => $order->voucher_id,
            'voucher_ids' => $order->voucher_id ? [$order->voucher_id] : [],
            'items' => $items,
        ];
    }
}
