<?php

declare(strict_types=1);

namespace App\Http\Controllers\Frontend;

use App\Concerns\BufferCartTrait;
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
use App\Models\Frontend\Buffer\Buffer;
use App\Models\Frontend\Buffer\BufferItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use App\Services\BiteshipService;

class CheckoutController extends Controller
{
    use BufferCartTrait;
    public function index()
    {
        $buffer = $this->getCurrentBuffer();
        $cart = $buffer ? $this->getBufferCartArray($buffer) : [];

        if (empty($cart)) {
            $sessionCart = session()->get('cart', []);
            if (!empty($sessionCart) && is_array($sessionCart)) {
                $buffer = $this->findOrCreateBuffer();
                foreach ($sessionCart as $sItem) {
                    if (empty($sItem['product_id']) && empty($sItem['bundle_data']['bundle_id'])) continue;
                    $pId = $sItem['product_id'] ?? null;
                    $vId = $sItem['variant_id'] ?? null;
                    $qty = max(1, (int) ($sItem['quantity'] ?? 1));
                    $price = (float) ($sItem['sell_price'] ?? ($sItem['unit_price'] ?? 0));
                    $meta = [];
                    if (!empty($sItem['color_id'])) {
                        $meta['color_id'] = $sItem['color_id'];
                        $meta['color_name'] = $sItem['color_name'] ?? null;
                        $meta['color_code'] = $sItem['color_code'] ?? null;
                    }
                    $meta['base_price'] = (float) ($sItem['base_price'] ?? $price);
                    $meta['sell_price'] = $price;
                    $notes = !empty($sItem['bundle_data']) ? json_encode($sItem['bundle_data']) : ($sItem['item_note'] ?? '');

                    \App\Models\Frontend\Buffer\BufferItem::create([
                        'id' => \Illuminate\Support\Str::uuid()->toString(),
                        'buffer_id' => $buffer->id,
                        'product_id' => $pId,
                        'product_variant_id' => $vId,
                        'name' => $sItem['name'] ?? 'Produk',
                        'quantity' => $qty,
                        'unit_price' => $price,
                        'total' => $price * $qty,
                        'discount_nominal' => (float) ($sItem['discount_nominal'] ?? 0),
                        'discount_percent' => (float) ($sItem['discount_percent'] ?? 0),
                        'item_notes' => $notes,
                        'meta' => $meta,
                    ]);
                }
                $this->recalculateBuffer($buffer);
                $cart = $this->getBufferCartArray($buffer);
            }
        }

        if (empty($cart)) {
            $lastProductUrl = $this->getLastProductUrl();
            return redirect($lastProductUrl)->with('warning', 'Keranjang belanja Anda kosong.');
        }

        $lastItem = end($cart);
        if ($lastItem) {
            if (($lastItem['type'] ?? '') === 'bundle' && !empty($lastItem['bundle_data']['bundle_id'])) {
                $bundle = \App\Models\Frontend\ProductsCatalog\ProductBundling::find($lastItem['bundle_data']['bundle_id']);
                if ($bundle && !empty($bundle->slug)) {
                    $this->rememberLastProductUrl(route('bundling.show', $bundle->slug));
                }
            } elseif (!empty($lastItem['product_id'])) {
                $product = \App\Models\Frontend\ProductsCatalog\Product::find($lastItem['product_id']);
                if ($product && !empty($product->slug)) {
                    $this->rememberLastProductUrl(route('products.show', $product->slug));
                }
            }
        }

        $vouchers = $this->getAvailableVouchers($cart);

        // Kurir per produk & kategori: filter kurir toko vs expedisi
        $cartProductIds = collect($cart)->pluck('product_id')->filter()->unique()->values()->all();
        $cartProducts = \App\Models\Frontend\ProductsCatalog\Product::with('category')->whereIn('id', $cartProductIds)->get();

        $resolvedCourierTypes = [];
        foreach ($cartProducts as $cp) {
            $cat = $cp->category;
            // Jika kategori diset global, ikuti kurir kategori. Jika detail / kosong, ikuti kurir produk.
            if ($cat && $cat->courier_setting_type === 'global' && !empty($cat->courier_type)) {
                $resolvedCourierTypes[] = $cat->courier_type;
            } else {
                $resolvedCourierTypes[] = $cp->courier_type ?: 'keduanya';
            }
        }

        $hasTokoOnly = in_array('toko', $resolvedCourierTypes, true);
        $hasExpedisiOnly = in_array('expedisi', $resolvedCourierTypes, true);

        $courierQuery = Courier::with('shippingAddresses');
        $enforcedCourierType = null;
        if ($hasTokoOnly && !$hasExpedisiOnly) {
            $courierQuery->where('courier_type', 'toko');
            $enforcedCourierType = 'toko';
        } elseif ($hasExpedisiOnly && !$hasTokoOnly) {
            $courierQuery->where('courier_type', 'expedisi');
            $enforcedCourierType = 'expedisi';
        }
        $couriers = $courierQuery->get();

        $selectedVoucher = Session::get('selected_voucher');
        $selectedVoucherCodes = $this->getSelectedVoucherCodesFromSession();
        if (empty($selectedVoucherCodes) && $buffer) {
            $bufferMeta = $buffer->meta ?? [];
            if (!empty($bufferMeta['voucher_codes'])) {
                $selectedVoucherCodes = array_values(array_unique(array_filter(array_map('strtoupper', (array) $bufferMeta['voucher_codes']))));
                Session::put('selected_voucher_codes', $selectedVoucherCodes);
            } elseif (!empty($bufferMeta['voucher_code'])) {
                $selectedVoucherCodes = array_values(array_unique(array_filter(array_map('strtoupper', explode(',', (string) $bufferMeta['voucher_code'])))));
                Session::put('selected_voucher_codes', $selectedVoucherCodes);
            }
        }
        $selectedVouchers = \App\Models\Frontend\Promo\Voucher::active()
            ->whereIn(\Illuminate\Support\Facades\DB::raw('UPPER(code)'), array_map('strtoupper', $selectedVoucherCodes))
            ->with(['categories', 'products', 'brands', 'customerGroups'])
            ->get();
        $savedAddresses = collect();
        $savedAddressesSafe = collect();
        $checkoutFormData = Session::get('checkout_form_data', []);
        $provinces = \App\Models\Frontend\Location\Province::orderBy('name')->get(['id', 'name']);

        $defaultCustomerAddr = null;
        if (session()->get('is_logged_in')) {
            $user = session()->get('user', []);
            $userId = $user['id'] ?? $user['sub'] ?? null;
            $userEmail = strtolower(trim($user['email'] ?? ''));

            $customer = null;
            if ($userId) {
                $customer = Customer::where('user_id', $userId)->first();
            }
            if (!$customer && !empty($userEmail)) {
                $customer = Customer::whereRaw('LOWER(email) = ?', [$userEmail])->first();
                if ($customer && $userId && empty($customer->user_id)) {
                    $customer->update(['user_id' => $userId]);
                }
            }

            $savedAddresses = Address::where(function ($q) use ($userId, $customer) {
                if ($userId) {
                    $q->where('user_id', $userId);
                }
                if ($customer) {
                    $q->orWhere('customer_id', $customer->id);
                }
            })
            ->where('deleted', false)
            ->with('subDistrict.city')
            ->orderByDesc('is_primary')
            ->get();

            if ($savedAddresses->isEmpty() && $customer) {
                $defaultCustomerAddr = $this->resolveCustomerAddressData($customer);
            }

            $savedAddressesSafe = $savedAddresses->map(function ($a) {
                return [
                    'id' => $a->id,
                    'recipient_name' => $a->recipient_name,
                    'phone' => $a->phone,
                    'city' => $a->subDistrict->city->name ?? '',
                    'address' => $a->address,
                    'postal_code' => $a->postal_code,
                    'sub_district_id' => $a->sub_district_id,
                    'province_id' => $a->subDistrict->city->province_id ?? ($a->subDistrict->province_id ?? null),
                    'city_id' => $a->subDistrict->city_id ?? null,
                ];
            });
        }

        $originalCartTotal = 0.0;
        $totalPercentDiscount = 0.0;
        $totalNominalDiscount = 0.0;
        $priceProductSettingDiscount = 0.0;

        foreach ($cart as $key => $item) {
            $isBundle = ($item['type'] ?? null) === 'bundle';
            $bundleData = $item['bundle_data'] ?? null;
            $quantity = (int) $item['quantity'];

            if ($isBundle && $bundleData) {
                $bundlePrice = (float) ($bundleData['bundle_price'] ?? 0);
                $bundleModel = \App\Models\Frontend\ProductsCatalog\ProductBundling::find($bundleData['bundle_id'] ?? '');
                $basePrice = (float) ($bundleModel->base_price ?? ($bundleData['original_price'] ?? $bundlePrice));
                if ($basePrice <= 0) {
                    $basePrice = $bundlePrice;
                }
                
                $promotionalPrice = $bundlePrice;
                if ($bundleModel) {
                    $ppsPromo = \App\Services\StaticPromoService::forBundling($bundleModel, $bundlePrice);
                    if ($ppsPromo) {
                        $promotionalPrice = \App\Services\StaticPromoService::discountedPrice($bundlePrice, $ppsPromo);
                    }
                }

                $itemSubtotal = $basePrice * $quantity;
                $originalCartTotal += $itemSubtotal;

                $itemDiscount = max(0.0, $itemSubtotal - ($promotionalPrice * $quantity));
                $totalPercentDiscount += $itemDiscount;

                $cart[$key]['base_price'] = $basePrice;
                $cart[$key]['original_price'] = $basePrice;
                $cart[$key]['sell_price'] = $promotionalPrice;
                continue;
            }

            $variantId = $item['variant_id'] ?? ($item['id'] !== $item['product_id'] ? $item['id'] : null);
            $basePrice = 0.0;
            $sellPrice = 0.0;
            if ($variantId) {
                $variantModel = \App\Models\Frontend\ProductsCatalog\ProductVariant::find($variantId);
                if ($variantModel) {
                    $basePrice = (float) ($variantModel->base_price > 0 ? $variantModel->base_price : $variantModel->sell_price);
                    $sellPrice = (float) $variantModel->sell_price;
                }
            }
            if ($basePrice <= 0.0) {
                $productModel = \App\Models\Frontend\ProductsCatalog\Product::find($item['product_id']);
                if ($productModel) {
                    $minBase = (float) ($productModel->variants->where('status', true)->min('base_price') ?? 0);
                    $minSell = (float) ($productModel->variants->where('status', true)->min('sell_price') ?? 0);
                    $basePrice = $minBase > 0 ? $minBase : $minSell;
                    $sellPrice = $minSell;
                }
            }
            if ($basePrice <= 0.0) {
                $basePrice = (float) ($item['base_price'] ?? ($item['unit_price'] ?? $item['sell_price'] ?? 0));
                $sellPrice = (float) ($item['sell_price'] ?? $basePrice);
            }
            if ($sellPrice <= 0.0) {
                $sellPrice = $basePrice;
            }
            if ($basePrice < $sellPrice) {
                $basePrice = $sellPrice;
            }

            $itemSubtotal = $basePrice * $quantity;
            $originalCartTotal += $itemSubtotal;

            $res = \App\Services\StaticPromoService::calculateItemDiscounts($item, $quantity, $sellPrice);
            $promotionalPrice = (float) ($res['promotional_price'] ?? $sellPrice);
            $itemVolumeDiscount = (float) ($res['volume_discount'] ?? 0);

            $itemDiscount = max(0.0, $itemSubtotal - ($promotionalPrice * $quantity));
            $itemStaticDiscount = max(0.0, $itemDiscount - $itemVolumeDiscount);
            $totalPercentDiscount += $itemStaticDiscount;
            $priceProductSettingDiscount += $itemVolumeDiscount;

            $cart[$key]['base_price'] = $basePrice;
            $cart[$key]['original_price'] = $basePrice;
            $cart[$key]['sell_price'] = $promotionalPrice;
        }

        $cartTotal = collect($cart)->sum(fn($item) => $item['sell_price'] * $item['quantity']);

        $cartWeightDetails = $this->calculateCartWeightAndDimensions($cart);
        $initialSubDistrictId = $savedAddresses->first()->sub_district_id 
            ?? ($defaultCustomerAddr['sub_district_id'] ?? ($checkoutFormData['sub_district_id'] ?? old('sub_district_id', '')));

        $selectedProvinceId = null;
        $selectedCityId = null;
        $selectedSubDistrictId = null;
        $cities = collect();
        $subDistricts = collect();

        if (!empty($initialSubDistrictId)) {
            $initSd = \App\Models\Frontend\Location\SubDistrict::withoutGlobalScopes()->with('city')->find($initialSubDistrictId)
                ?? \App\Models\Frontend\Location\SubDistrict::with('city')->find($initialSubDistrictId);
            if ($initSd) {
                $selectedSubDistrictId = $initSd->id;
                $selectedCityId = $initSd->city_id;
                $selectedProvinceId = $initSd->city->province_id ?? ($initSd->province_id ?? null);

                if ($selectedProvinceId) {
                    $cities = \App\Models\Frontend\Location\City::where('province_id', $selectedProvinceId)
                        ->orderBy('name')
                        ->get(['id', 'name']);
                }
                if ($selectedCityId) {
                    $subDistricts = \App\Models\Frontend\Location\SubDistrict::where('city_id', $selectedCityId)
                        ->orderBy('sub_district')
                        ->get(['id', 'district', 'sub_district', 'postal_code'])
                        ->map(fn($sd) => [
                            'id' => $sd->id,
                            'label' => $sd->sub_district . ($sd->district ? ' (Kec. ' . $sd->district . ')' : '') . ($sd->postal_code ? ' - ' . $sd->postal_code : ''),
                            'postal_code' => $sd->postal_code,
                            'district' => $sd->district,
                            'sub_district' => $sd->sub_district,
                            'city' => $initSd->city->name ?? '',
                        ]);
                }
            }
        }

        $courierPrices = [];
        foreach ($couriers as $courier) {
            $courierPrices[$courier->code] = $this->calculateShippingDetails($courier->code, $initialSubDistrictId ?? '', $cart);
        }

        $selectedVoucherDiscount = 0;
        $productVoucherDiscount = 0;
        $shippingVoucherDiscount = 0;
        if (!empty($selectedVoucherCodes)) {
            $defaultShippingCost = 0;
            if (!empty($couriers->first()) && isset($courierPrices[$couriers->first()->code]['price'])) {
                $defaultShippingCost = (float) ($courierPrices[$couriers->first()->code]['price'] ?? 0);
            }
            $voucherCalc = $this->calculateVoucherDiscount($selectedVoucherCodes, $cart, $cartTotal, $defaultShippingCost);
            $selectedVoucherDiscount = (float) ($voucherCalc['discount'] ?? 0);
            $productVoucherDiscount = (float) ($voucherCalc['product_voucher_discount'] ?? 0);
            $shippingVoucherDiscount = (float) ($voucherCalc['shipping_voucher_discount'] ?? 0);
            $selectedVoucher = [
                'code' => $voucherCalc['primary']?->code ?? ($selectedVoucherCodes[0] ?? ''),
                'discount' => $selectedVoucherDiscount,
                'product_discount' => $productVoucherDiscount,
                'shipping_discount' => $shippingVoucherDiscount,
            ];
            Session::put('selected_voucher', $selectedVoucher);
        }

        return view('frontend.checkout', compact('cart', 'vouchers', 'couriers', 'selectedVoucher', 'selectedVoucherCodes', 'savedAddresses', 'savedAddressesSafe', 'checkoutFormData', 'provinces', 'cities', 'subDistricts', 'selectedProvinceId', 'selectedCityId', 'selectedSubDistrictId', 'priceProductSettingDiscount', 'originalCartTotal', 'totalPercentDiscount', 'totalNominalDiscount', 'cartTotal', 'selectedVouchers', 'enforcedCourierType', 'cartWeightDetails', 'courierPrices', 'defaultCustomerAddr', 'selectedVoucherDiscount', 'productVoucherDiscount', 'shippingVoucherDiscount'));
    }

    public function store(Request $request)
    {
        $formFields = $request->only(['name', 'email', 'phone', 'address', 'postal_code', 'courier', 'voucher_code', 'selected_address_id', 'sub_district_id']);

        $rawPhone = trim((string) $request->phone);
        $cleanPhone = ltrim(preg_replace('/[\s\-]/', '', $rawPhone), '+');
        if (!preg_match('/[^\d\+\s\-]/', $rawPhone) && !empty($cleanPhone)) {
            $request->merge(['phone' => $cleanPhone]);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email',
            'phone' => [
                'required',
                'string',
                'min:9',
                'max:20',
                'regex:/^(62|0)[0-9]{8,14}$/',
            ],
            'sub_district_id' => 'required|uuid',
            'address' => [
                'required',
                'string',
                'min:5',
                'max:500',
                'regex:/[a-zA-Z0-9]{4,}/',
            ],
            'courier' => 'required|string',
            'voucher_code' => 'nullable|string|max:200',
            'voucher_codes' => 'nullable|string|max:500',
            'selected_address_id' => 'nullable|uuid|exists:addresses,id',
            'item_notes' => 'nullable|array',
            'item_notes.*' => 'nullable|string|max:500',
        ], [
            'phone.required' => 'Nomor handphone wajib diisi.',
            'phone.min' => 'Nomor handphone minimal 9 digit angka.',
            'phone.regex' => 'Nomor handphone tidak valid. Gunakan format 08... atau 62... tanpa simbol/huruf.',
            'address.required' => 'Alamat lengkap pengiriman wajib diisi.',
            'address.min' => 'Alamat pengiriman terlalu pendek. Mohon isi alamat dengan detail.',
            'address.regex' => 'Alamat pengiriman tidak valid. Mohon masukkan alamat lengkap (bukan tanda strip/simbol).',
            'sub_district_id.required' => 'Kecamatan / wilayah tujuan pengiriman wajib dipilih.',
            'courier.required' => 'Pilihan ekspedisi / kurir pengiriman wajib dipilih.',
        ]);

        session()->forget(['thankyou_order_id', 'last_created_order_id']);
        $buffer = $this->getCurrentBuffer();
        $cart = $buffer ? $this->getBufferCartArray($buffer) : [];

        if (empty($cart) || !$buffer) {
            $lastProductUrl = $this->getLastProductUrl();
            return redirect($lastProductUrl)->with('warning', 'Keranjang belanja Anda kosong.');
        }

        $lastItem = end($cart);
        if ($lastItem) {
            if (($lastItem['type'] ?? '') === 'bundle' && !empty($lastItem['bundle_data']['bundle_id'])) {
                $bundle = \App\Models\Frontend\ProductsCatalog\ProductBundling::find($lastItem['bundle_data']['bundle_id']);
                if ($bundle && !empty($bundle->slug)) {
                    $this->rememberLastProductUrl(route('bundling.show', $bundle->slug));
                }
            } elseif (!empty($lastItem['product_id'])) {
                $product = \App\Models\Frontend\ProductsCatalog\Product::find($lastItem['product_id']);
                if ($product && !empty($product->slug)) {
                    $this->rememberLastProductUrl(route('products.show', $product->slug));
                }
            }
        }

        $itemNotes = (array) $request->input('item_notes', []);
        $cartTotal = collect($cart)->sum(fn($item) => ($item['sell_price'] ?? 0) * ($item['quantity'] ?? 0));

        $resolvedItems = [];
        $originalCartTotal = 0.0;
        $totalStaticDiscount = 0.0;
        $priceProductSettingDiscount = 0.0;

        foreach ($cart as $key => $item) {
            $isBundle = ($item['type'] ?? null) === 'bundle';
            $bundleData = $item['bundle_data'] ?? null;
            $quantity = (int) $item['quantity'];

            if ($isBundle && $bundleData) {
                $bundlePrice = (float) ($bundleData['bundle_price'] ?? 0);
                $bundleModel = \App\Models\Frontend\ProductsCatalog\ProductBundling::find($bundleData['bundle_id'] ?? '');
                $basePrice = (float) ($bundleModel->base_price ?? ($bundleData['original_price'] ?? $bundlePrice));
                if ($basePrice <= 0) {
                    $basePrice = $bundlePrice;
                }
                $variantId = null;
                $cart[$key]['bundle_data'] = $bundleData;
                
                $originalSubtotal = $basePrice * $quantity;
                $originalCartTotal += $originalSubtotal;
                
                $promotionalPrice = $bundlePrice;
                if ($bundleModel) {
                    $ppsPromo = \App\Services\StaticPromoService::forBundling($bundleModel, $bundlePrice);
                    if ($ppsPromo) {
                        $promotionalPrice = \App\Services\StaticPromoService::discountedPrice($bundlePrice, $ppsPromo);
                    }
                }
                
                $itemTotal = $promotionalPrice * $quantity;
                $itemDiscount = max(0.0, $originalSubtotal - $itemTotal);
                $discountPercent = $originalSubtotal > 0 ? round(($itemDiscount / $originalSubtotal) * 100, 2) : 0.0;
                
                $totalStaticDiscount += $itemDiscount;
                $cart[$key]['base_price'] = $basePrice;
                $cart[$key]['original_price'] = $basePrice;
                $cart[$key]['sell_price'] = $promotionalPrice;

                $resolvedItems[] = [
                    'item' => $item,
                    'variant_id' => $variantId,
                    'base_price' => $basePrice,
                    'original_price' => $basePrice,
                    'sell_price' => $promotionalPrice,
                    'after_disc_price' => $promotionalPrice,
                    'original_subtotal' => $originalSubtotal,
                    'item_total' => $itemTotal,
                    'discount_nominal' => $itemDiscount,
                    'discount_percent' => $discountPercent,
                    'static_promo_discount' => $itemDiscount,
                    'volume_promo_discount' => 0.0,
                ];
                continue;
            }

            $variantId = $item['variant_id'] ?? ($item['id'] !== $item['product_id'] ? $item['id'] : null);

            $basePrice = 0.0;
            $sellPrice = 0.0;
            if ($variantId) {
                $variantModel = \App\Models\Frontend\ProductsCatalog\ProductVariant::find($variantId);
                if ($variantModel) {
                    $basePrice = (float) ($variantModel->base_price > 0 ? $variantModel->base_price : $variantModel->sell_price);
                    $sellPrice = (float) $variantModel->sell_price;
                }
            }
            if ($basePrice <= 0.0) {
                $productModel = \App\Models\Frontend\ProductsCatalog\Product::find($item['product_id']);
                if ($productModel) {
                    $minBase = (float) ($productModel->variants->where('status', true)->min('base_price') ?? 0);
                    $minSell = (float) ($productModel->variants->where('status', true)->min('sell_price') ?? 0);
                    $basePrice = $minBase > 0 ? $minBase : $minSell;
                    $sellPrice = $minSell;
                }
            }
            if ($basePrice <= 0.0) {
                $basePrice = (float) ($item['base_price'] ?? ($item['unit_price'] ?? $item['sell_price'] ?? 0));
                $sellPrice = (float) ($item['sell_price'] ?? $basePrice);
            }
            if ($sellPrice <= 0.0) {
                $sellPrice = $basePrice;
            }
            if ($basePrice < $sellPrice) {
                $basePrice = $sellPrice;
            }

            $originalSubtotal = $basePrice * $quantity;
            $originalCartTotal += $originalSubtotal;

            $res = \App\Services\StaticPromoService::calculateItemDiscounts($item, $quantity, $sellPrice);
            $promotionalPrice = (float) ($res['promotional_price'] ?? $sellPrice);
            $itemVolumeDiscount = (float) ($res['volume_discount'] ?? 0);

            $itemTotal = $promotionalPrice * $quantity;
            $itemDiscount = max(0.0, $originalSubtotal - $itemTotal);
            $discountPercent = $originalSubtotal > 0 ? round(($itemDiscount / $originalSubtotal) * 100, 2) : 0.0;

            $itemStaticDiscount = max(0.0, $itemDiscount - $itemVolumeDiscount);
            $totalStaticDiscount += $itemStaticDiscount;
            $priceProductSettingDiscount += $itemVolumeDiscount;

            // Recalculate cart item price for voucher calculations
            $cart[$key]['base_price'] = $basePrice;
            $cart[$key]['original_price'] = $basePrice;
            $cart[$key]['sell_price'] = $promotionalPrice;

            $resolvedItems[] = [
                'item' => $item,
                'variant_id' => $variantId,
                'base_price' => $basePrice,
                'original_price' => $basePrice,
                'sell_price' => $promotionalPrice,
                'after_disc_price' => $promotionalPrice,
                'original_subtotal' => $originalSubtotal,
                'item_total' => $itemTotal,
                'discount_nominal' => $itemDiscount,
                'discount_percent' => $discountPercent,
                'static_promo_discount' => $itemStaticDiscount,
                'volume_promo_discount' => $itemVolumeDiscount,
            ];
        }

        $cartTotal = collect($cart)->sum(fn($item) => ($item['sell_price'] ?? 0) * ($item['quantity'] ?? 0));

        $subDistrictId = $request->sub_district_id;
        $addressId = $request->selected_address_id;
        if (session()->get('is_logged_in') && $addressId) {
            $savedAddress = Address::find($addressId);
            if ($savedAddress) {
                $subDistrictId = $savedAddress->sub_district_id;
            }
        }

        $submittedShippingCost = $request->filled('shipping_cost') ? (float) $request->input('shipping_cost') : null;
        if ($submittedShippingCost !== null && $submittedShippingCost >= 0) {
            $shippingCost = $submittedShippingCost;
        } else {
            $shippingCost = $this->getShippingCost($request->courier, $subDistrictId ?? '', $cart);
        }

        $voucherDiscount = 0;
        $productVoucherDiscount = 0;
        $shippingVoucherDiscount = 0;
        $voucher = null;
        $appliedVouchers = [];
        $voucherCodes = $this->parseVoucherCodes($request);
        if ($voucherCodes) {
            $voucherResult = $this->calculateVoucherDiscount($voucherCodes, $cart, $cartTotal, $shippingCost);
            $voucherDiscount = $voucherResult['discount'];
            $productVoucherDiscount = $voucherResult['product_voucher_discount'] ?? 0;
            $shippingVoucherDiscount = $voucherResult['shipping_voucher_discount'] ?? 0;
            $voucher = $voucherResult['primary'];
            $appliedVouchers = $voucherResult['vouchers'];
        }

        $shippingVoucherDiscount = min($shippingVoucherDiscount, $shippingCost);
        $voucherDiscount = $productVoucherDiscount + $shippingVoucherDiscount;

        $subtotal = $originalCartTotal;
        $totalPromoDiscount = $totalStaticDiscount + $priceProductSettingDiscount;
        $discountedProductSubtotal = max(0, $originalCartTotal - $totalStaticDiscount - $priceProductSettingDiscount - $productVoucherDiscount);
        $actualShippingCost = max(0, $shippingCost - $shippingVoucherDiscount);
        $total = $discountedProductSubtotal + $actualShippingCost;

        $userId = null;
        if (session()->get('is_logged_in')) {
            $user = session()->get('user', []);
            $tempUserId = $user['id'] ?? $user['sub'] ?? null;
            if ($tempUserId && !\App\Models\User::where('id', $tempUserId)->exists()) {
                session()->forget(['is_logged_in', 'user', 'access_token', 'refresh_token']);
            } else {
                $userId = $tempUserId;
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
        if ($courierModel) {
            $cartProductIds = collect($cart)->pluck('product_id')->filter()->unique()->values()->all();
            $cartProducts = \App\Models\Frontend\ProductsCatalog\Product::with('category')->whereIn('id', $cartProductIds)->get();

            $resolvedCourierTypes = [];
            foreach ($cartProducts as $cp) {
                $cat = $cp->category;
                if ($cat && $cat->courier_setting_type === 'global' && !empty($cat->courier_type)) {
                    $resolvedCourierTypes[] = $cat->courier_type;
                } else {
                    $resolvedCourierTypes[] = $cp->courier_type ?: 'keduanya';
                }
            }

            $hasTokoOnly = in_array('toko', $resolvedCourierTypes, true);
            $hasExpedisiOnly = in_array('expedisi', $resolvedCourierTypes, true);

            if ($hasTokoOnly && !$hasExpedisiOnly && $courierModel->courier_type !== 'toko') {
                return redirect()->route('checkout')->withErrors(['courier' => 'Produk di keranjang hanya dapat dikirim menggunakan Kurir Toko.'])->withInput();
            }
            if ($hasExpedisiOnly && !$hasTokoOnly && $courierModel->courier_type !== 'expedisi') {
                return redirect()->route('checkout')->withErrors(['courier' => 'Produk di keranjang hanya dapat dikirim menggunakan Kurir Ekspedisi.'])->withInput();
            }
        }
        $finalSubDistrictId = $subDistrictId ?? $request->sub_district_id;

        $shippingAddressRecord = null;
        if ($courierModel && $finalSubDistrictId) {
            $destSd = \App\Models\Frontend\Location\SubDistrict::withoutGlobalScopes()->find($finalSubDistrictId);
            $destCityId = $destSd?->city_id;
            $shippingAddressRecord = \App\Models\Frontend\Shipping\ShippingAddress::where('courier_id', $courierModel->id)
                ->where(function ($q) use ($finalSubDistrictId, $destCityId) {
                    $q->where('sub_district_id', $finalSubDistrictId);
                    if ($destCityId) {
                        $q->orWhere('city_id', $destCityId);
                    }
                })
                ->orderByRaw('sub_district_id IS NOT NULL DESC')
                ->first();
        }

        if ($courierModel && $courierModel->courier_type === 'toko' && !$shippingAddressRecord) {
            return redirect()->route('checkout')->withErrors(['courier' => 'Kurir Toko belum melayani pengiriman ke wilayah / kota tujuan yang dipilih. Silakan pilih alamat lain atau hubungi admin.'])->withInput();
        }
        $shippingAddressesId = $shippingAddressRecord ? $shippingAddressRecord->id : null;

        $shippingAddressData = null;
        if ($finalSubDistrictId) {
            $subDistrictModel = \App\Models\Frontend\Location\SubDistrict::with('city.province')->find($finalSubDistrictId);
            if ($subDistrictModel) {
                $cleanPhone = ltrim(preg_replace('/[\s\-]/', '', trim((string) $request->phone)), '+');
                $phone = !empty($cleanPhone) ? $cleanPhone : trim((string) $request->phone);
                $recipientName = trim((string) $request->name);
                $addressText = trim((string) $request->address);
                $postalCode = trim((string) $request->postal_code) ?: ($subDistrictModel->postal_code ?? '');

                if (session()->get('is_logged_in')) {
                    $userId = session()->get('user')['id'] ?? session()->get('user')['sub'] ?? null;
                    $customer = null;
                    if ($userId) {
                        $customer = Customer::where('user_id', $userId)->first();
                    }
                    if (!$customer && !empty($request->email)) {
                        $customer = Customer::whereRaw('LOWER(email) = ?', [strtolower(trim($request->email))])->first();
                    }
                    if ($customer && !empty($phone)) {
                        $customer->update([
                            'phone' => $phone,
                            'name' => $recipientName ?: $customer->name,
                        ]);
                    }
                    if ($addressId) {
                        $savedAddress = Address::find($addressId);
                        if ($savedAddress && !empty($phone)) {
                            $savedAddress->update([
                                'phone' => $phone,
                                'recipient_name' => $recipientName ?: $savedAddress->recipient_name,
                            ]);
                        }
                    }
                    if (session()->has('user')) {
                        $sessionUser = session()->get('user');
                        $sessionUser['phone'] = $phone;
                        if (!empty($recipientName)) {
                            $sessionUser['name'] = $recipientName;
                        }
                        session()->put('user', $sessionUser);
                    }
                }

                $shippingAddressData = [
                    'recipient_name' => $recipientName,
                    'phone' => $phone,
                    'address' => $addressText,
                    'sub_district' => $subDistrictModel->sub_district,
                    'city' => $subDistrictModel->city->name ?? '',
                    'province' => $subDistrictModel->city->province->name ?? '',
                    'postal_code' => $postalCode,
                    'sub_district_id' => $finalSubDistrictId,
                    'city_id' => $subDistrictModel->city_id,
                    'province_id' => $subDistrictModel->province_id ?? ($subDistrictModel->city?->province_id ?? null),
                ];
            }
        }

        $dbSubtotal = max(0, $originalCartTotal - $totalStaticDiscount - $priceProductSettingDiscount);
        $dbDiscount = $productVoucherDiscount;
        $actualShippingCost = max(0, $shippingCost - $shippingVoucherDiscount);
        $dbTotal = max(0, $dbSubtotal - $productVoucherDiscount) + $actualShippingCost;

        $shippingCalc = $this->calculateShippingDetails($request->courier, (string) ($finalSubDistrictId ?? $subDistrictId ?? ''), $cart);
        $resolvedEtaLabel = $request->input('shipping_eta_label') ?: ($shippingCalc['eta_label'] ?? null);
        $resolvedEtaSource = $request->input('shipping_eta_source') ?: ($shippingCalc['eta_source'] ?? null);
        $resolvedServiceName = $request->input('shipping_service_name') ?: ($shippingCalc['service_name'] ?? null);
        $resolvedServiceCode = $request->input('shipping_service_code') ?: ($shippingCalc['service_code'] ?? null);

        // Update Buffer without creating Order in orders table!
        $bufferMeta = array_merge($buffer->meta ?? [], [
            'shipping_address' => $shippingAddressData,
            'customer' => [
                'name' => $recipientName,
                'email' => $request->email,
                'phone' => $phone,
                'user_id' => $userId,
                'selected_address_id' => $addressId,
                'address' => $addressText,
                'sub_district_id' => $finalSubDistrictId,
                'postal_code' => $shippingAddressData['postal_code'] ?? ($request->postal_code ?? ''),
            ],
            'courier' => $request->courier,
            'courier_id' => $courierModel?->id,
            'shipping_eta_label' => $resolvedEtaLabel,
            'shipping_duration' => $shippingCalc['duration'] ?? null,
            'shipping_eta_source' => $resolvedEtaSource,
            'shipping_eta_dates' => $shippingCalc['eta_dates'] ?? null,
            'shipping_service_name' => $resolvedServiceName,
            'shipping_service_code' => $resolvedServiceCode,
            'courier_service_type' => $resolvedServiceCode,
            'applied_vouchers' => $appliedVouchers,
            'voucher_codes' => $voucherCodes,
            'voucher_code' => implode(',', $voucherCodes),
            'voucher_id' => $voucher?->id,
            'voucher_ids' => collect($appliedVouchers)->pluck('voucher.id')->filter()->values()->all(),
            'voucher_discount' => $voucherDiscount,
            'product_voucher_discount' => $productVoucherDiscount,
            'shipping_voucher_discount' => $shippingVoucherDiscount,
            'promo_discount' => $totalPromoDiscount,
            'total_static_discount' => $totalStaticDiscount,
            'price_product_setting_discount' => $priceProductSettingDiscount,
            'original_cart_total' => $originalCartTotal,
            'resolved_items' => $resolvedItems,
            'item_notes' => $itemNotes,
            'platform' => 'website',
        ]);

        $buffer->update([
            'customer_name' => $recipientName,
            'customer_email' => $request->email,
            'customer_phone' => $phone,
            'subtotal' => $dbSubtotal,
            'discount' => $dbDiscount,
            'total' => $dbTotal,
            'courier_id' => $courierModel?->id,
            'voucher_id' => $voucher?->id,
            'voucher_nominal' => $voucherDiscount,
            'shipping_cost' => $shippingCost,
            'shipping_cost_subsidy' => $shippingVoucherDiscount,
            'shipping_addresses_id' => $shippingAddressesId,
            'meta' => $bufferMeta,
        ]);

        $itemsForOrderData = array_map(function ($item) use ($itemNotes) {
            $originalPrice = (float) ($item['original_price'] ?? $item['sell_price'] ?? $item['sell_price']);
            $price = (float) $item['sell_price'];
            $discountNominal = $originalPrice - $price;
            $discountPercent = $originalPrice > 0 ? round(($discountNominal / $originalPrice) * 100, 2) : 0.0;

            return [
                'id' => $item['id'],
                'product_id' => $item['product_id'],
                'variant_id' => $item['variant_id'] ?? ($item['id'] !== $item['product_id'] ? $item['id'] : null),
                'name' => $item['name'],
                'image' => $item['image'] ?? '',
                'sell_price' => $originalPrice,
                'quantity' => (int) $item['quantity'],
                'item_note' => $item['item_note'] ?? ($itemNotes[$item['id']] ?? ''),
                'discount_nominal' => $discountNominal,
                'discount_percent' => $discountPercent,
                'total' => $price * (int) $item['quantity'],
            ];
        }, array_values($cart));

        $orderData = [
            'id' => $buffer->id,
            'order_number' => null,
            'customer' => [
                'name' => $recipientName,
                'email' => $request->email,
                'phone' => $phone,
                'user_id' => $userId,
                'selected_address_id' => $addressId,
                'address' => $addressText,
                'sub_district_id' => $finalSubDistrictId,
                'postal_code' => $shippingAddressData['postal_code'] ?? ($request->postal_code ?? ''),
            ],
            'shipping_address' => $shippingAddressData,
            'courier' => $request->courier,
            'courier_id' => $courierModel?->id,
            'shipping_cost' => $shippingCost,
            'eta_label' => $resolvedEtaLabel,
            'original_cart_total' => $originalCartTotal,
            'promo_discount' => $totalPromoDiscount,
            'total_static_discount' => $totalStaticDiscount,
            'price_product_setting_discount' => $priceProductSettingDiscount,
            'product_voucher_discount' => $productVoucherDiscount,
            'shipping_voucher_discount' => $shippingVoucherDiscount,
            'applied_vouchers' => $appliedVouchers,
            'subtotal' => $dbSubtotal,
            'voucher_discount' => $voucherDiscount,
            'total_discount' => $totalPromoDiscount + $voucherDiscount,
            'total' => $dbTotal,
            'transaction_fee' => 0.0,
            'voucher_code' => implode(',', $voucherCodes),
            'voucher_codes' => $voucherCodes,
            'voucher_id' => $voucher?->id,
            'voucher_ids' => collect($appliedVouchers)->pluck('voucher.id')->filter()->values()->all(),
            'items' => $itemsForOrderData,
            'resolved_items' => $resolvedItems,
        ];

        Session::put('checkout_form_data', [
            'name' => $recipientName,
            'email' => $request->email,
            'phone' => $phone,
            'address' => $addressText,
            'postal_code' => $postalCode,
            'sub_district_id' => $finalSubDistrictId,
            'courier' => $request->courier,
            'shipping_cost' => $shippingCost,
            'selected_address_id' => $addressId,
        ]);
        Session::put('selected_voucher_codes', $voucherCodes);
        Session::put('order_data', $orderData);
        Session::put('checkout_data', $orderData);

        return redirect()->route('payment');
    }

    public function payment(Request $request)
    {
        $buffer = $this->getCurrentBuffer();
        $cart = $buffer ? $this->getBufferCartArray($buffer) : [];

        // Check if user is navigating back to payment or continuing an existing unpaid order
        $existingOrderId = $request->query('order_id') 
            ?? session()->get('last_created_order_id') 
            ?? session()->get('thankyou_order_id');

        $existingOrder = null;
        if ($existingOrderId) {
            $existingOrder = $this->getOrderFromIdentifier($existingOrderId);
        }

        // If buffer is empty/missing, check if there is an existing UNPAID order to resume payment
        if ((empty($cart) || !$buffer) && $existingOrder && (int)$existingOrder->payment_status === 1 && (int)$existingOrder->status !== Order::STATUS_CANCELLED) {
            // If order already has an initiated payment / settlement, navigating back to payment should redirect to thankyou page!
            if (!empty($existingOrder->settlement_id) || \App\Models\Settlement::where('reference_id', $existingOrder->order_number)->exists() || session()->get('thankyou_order_id') === $existingOrder->id) {
                return redirect()->route('thankyou', ['order_id' => $existingOrder->id]);
            }

            $orderData = $this->formatOrderDataFromModel($existingOrder);
            session()->put('order_data', $orderData);
            session()->put('last_created_order_id', $existingOrder->id);
        } elseif (empty($cart) || !$buffer) {
            $lastProductUrl = $this->getLastProductUrl();
            return redirect($lastProductUrl)->with('warning', 'Keranjang belanja Anda kosong.');
        }

        $orderData = session()->get('order_data');

        // Check if existing order matches orderData in session
        if ($existingOrder && (int)$existingOrder->payment_status === 1 && (int)$existingOrder->status !== Order::STATUS_CANCELLED) {
            if (empty($orderData) || ($orderData['id'] ?? '') !== $existingOrder->id) {
                $orderData = $this->formatOrderDataFromModel($existingOrder);
                session()->put('order_data', $orderData);
            }
        } elseif ($buffer && (empty($orderData) || ($orderData['id'] ?? '') !== $buffer->id)) {
            $meta = $buffer->meta ?? [];
            if (empty($meta['customer'])) {
                return redirect()->route('checkout')->with('warning', 'Silakan lengkapi formulir pengiriman terlebih dahulu.');
            }

            $orderData = [
                'id' => $buffer->id,
                'order_number' => null,
                'customer' => $meta['customer'] ?? [],
                'shipping_address' => $meta['shipping_address'] ?? [],
                'courier' => $meta['courier'] ?? 'kurir',
                'courier_id' => $buffer->courier_id,
                'shipping_cost' => (float) $buffer->shipping_cost,
                'eta_label' => $meta['shipping_eta_label'] ?? ($meta['eta_label'] ?? null),
                'shipping_duration' => $meta['shipping_duration'] ?? null,
                'shipping_eta_source' => $meta['shipping_eta_source'] ?? null,
                'subtotal' => (float) $buffer->subtotal,
                'original_cart_total' => (float) ($meta['original_cart_total'] ?? $buffer->subtotal),
                'promo_discount' => (float) ($meta['promo_discount'] ?? (($meta['total_static_discount'] ?? 0) + ($meta['price_product_setting_discount'] ?? 0))),
                'total_static_discount' => (float) ($meta['total_static_discount'] ?? 0),
                'product_voucher_discount' => (float) ($meta['product_voucher_discount'] ?? 0),
                'shipping_voucher_discount' => (float) ($meta['shipping_voucher_discount'] ?? 0),
                'applied_vouchers' => $meta['applied_vouchers'] ?? [],
                'price_product_setting_discount' => (float) ($meta['price_product_setting_discount'] ?? 0),
                'voucher_discount' => (float) ($buffer->voucher_nominal ?? 0),
                'total_discount' => (float) $buffer->discount,
                'total' => (float) $buffer->total,
                'transaction_fee' => 0.0,
                'voucher_code' => $meta['voucher_code'] ?? '',
                'voucher_codes' => $meta['voucher_codes'] ?? [],
                'voucher_id' => $buffer->voucher_id,
                'voucher_ids' => $meta['voucher_ids'] ?? [],
                'items' => !empty($meta['resolved_items']) 
                    ? array_map(function ($res) {
                        $it = $res['item'] ?? [];
                        return [
                            'id' => $it['id'] ?? '',
                            'product_id' => $it['product_id'] ?? '',
                            'variant_id' => $res['variant_id'] ?? null,
                            'name' => $it['name'] ?? '',
                            'image' => $it['image'] ?? '',
                            'sell_price' => (float) ($res['base_price'] ?? $res['original_price'] ?? ($it['sell_price'] ?? 0)),
                            'quantity' => (int) ($it['quantity'] ?? 1),
                            'item_note' => $it['item_note'] ?? '',
                            'discount_nominal' => (float) ($res['discount_nominal'] ?? 0),
                            'discount_percent' => (float) ($res['discount_percent'] ?? 0),
                            'total' => (float) ($res['item_total'] ?? (($it['sell_price'] ?? 0) * ($it['quantity'] ?? 1))),
                        ];
                    }, $meta['resolved_items'])
                    : array_map(function ($item) {
                        return [
                            'id' => $item['id'],
                            'product_id' => $item['product_id'],
                            'variant_id' => $item['variant_id'] ?? null,
                            'name' => $item['name'],
                            'image' => $item['image'] ?? '',
                            'sell_price' => (float) ($item['original_price'] ?? $item['base_price'] ?? $item['sell_price']),
                            'quantity' => (int) $item['quantity'],
                            'item_note' => $item['item_note'] ?? '',
                            'discount_nominal' => (float) ($item['discount_nominal'] ?? 0),
                            'discount_percent' => (float) ($item['discount_percent'] ?? 0),
                            'total' => (float) $item['sell_price'] * (int) $item['quantity'],
                        ];
                    }, $cart),
            ];
            session()->put('order_data', $orderData);
        }

        $dbMethods = \App\Models\PaymentMethod::active()->orderBy('sort_order')->get();
        $uniqueMethods = [];

        foreach ($dbMethods as $method) {
            $rawCode = strtoupper(trim((string)$method->code));
            $rawName = strtoupper(trim((string)$method->name));
            $type = (int)$method->type;

            $channelKey = null;

            // 1. Bank Transfer Manual
            if ($type === 1 || in_array($rawCode, ['TRANSFER_MANUAL', 'TRF'], true) || str_contains($rawName, 'TRANSFER')) {
                $channelKey = 'manual_transfer';
            }
            // 2. QRIS (Type 4)
            elseif ($type === 4 || str_contains($rawCode, 'QRIS') || str_contains($rawName, 'QRIS')) {
                $channelKey = 'qris';
            }
            // 3. Kartu Kredit (Type 5)
            elseif ($type === 5 || str_contains($rawCode, 'CREDIT') || str_contains($rawName, 'CREDIT')) {
                $channelKey = 'credit_card';
            }
            // 4. Kartu Debit (Type 6)
            elseif ($type === 6 || str_contains($rawCode, 'DEBIT') || str_contains($rawName, 'DEBIT')) {
                $channelKey = 'debit_card';
            }
            // 5. E-Wallet GoPay (Type 3 atau nama/kode GOPAY)
            elseif (str_contains($rawCode, 'GOPAY') || str_contains($rawName, 'GOPAY') || ($type === 3 && $rawCode === '014')) {
                $channelKey = 'ewallet_gopay';
            }
            // 6. E-Wallet OVO (Type 3 atau nama/kode OVO)
            elseif (str_contains($rawCode, 'OVO') || str_contains($rawName, 'OVO') || ($type === 3 && $rawCode === '503')) {
                $channelKey = 'ewallet_ovo';
            }
            // 7. Virtual Account BCA (Type 2 / code 014 / BCA)
            elseif (($type === 2 && ($rawCode === '014' || str_contains($rawCode, 'BCA') || str_contains($rawName, 'BCA'))) || str_contains($rawCode, 'BCAATM')) {
                $channelKey = 'va_bca';
            }
            // 8. Virtual Account Mandiri
            elseif (str_contains($rawCode, 'MANDIRI') || str_contains($rawName, 'MANDIRI') || $rawCode === '008') {
                $channelKey = 'va_mandiri';
            }
            // 9. Virtual Account BRI
            elseif (str_contains($rawCode, 'BRI') || str_contains($rawName, 'BRI') || $rawCode === '002') {
                $channelKey = 'va_bri';
            }
            // 10. Virtual Account CIMB Niaga
            elseif (str_contains($rawCode, 'CIMB') || str_contains($rawName, 'CIMB') || $rawCode === '022') {
                $channelKey = 'va_cimb';
            }
            // 11. Virtual Account Danamon
            elseif (str_contains($rawCode, 'DANAMON') || str_contains($rawName, 'DANAMON') || $rawCode === '011') {
                $channelKey = 'va_danamon';
            }
            // 12. Virtual Account Bank Saqu
            elseif (str_contains($rawCode, 'SAQU') || str_contains($rawName, 'SAQU') || $rawCode === '472') {
                $channelKey = 'va_saqu';
            }
            // 13. Virtual Account Maybank / BII
            elseif (str_contains($rawCode, 'BII') || str_contains($rawName, 'BII') || str_contains($rawName, 'MAYBANK') || $rawCode === '016') {
                $channelKey = 'va_maybank';
            }
            else {
                $channelKey = 'other_' . ($method->code ?: $method->id);
            }

            if (isset($uniqueMethods[$channelKey])) {
                continue;
            }

            $isManual = (int)$method->type === 1 
                || $method->isTypeBankTransfer() 
                || strtolower((string)$method->provider) !== 'espay' 
                || in_array($method->code, ['transfer_manual', 'trf'], true);

            $resolvedType = $method->type;
            $displayName = $method->name;
            $bankCode = '';
            $productCode = '';

            switch ($channelKey) {
                case 'ewallet_gopay':
                    $displayName = 'GoPay';
                    $finalCode = 'GOPAYJUMPAPP';
                    $productCode = 'GOPAYJUMPAPP';
                    $bankCode = '014';
                    $resolvedType = 3;
                    break;
                case 'ewallet_ovo':
                    $displayName = 'OVO';
                    $finalCode = 'OVO';
                    $productCode = 'OVO';
                    $bankCode = '503';
                    $resolvedType = 3;
                    break;
                case 'qris':
                    $displayName = 'QRIS';
                    $finalCode = 'QRISPLUS';
                    $productCode = 'QRISPLUS';
                    $bankCode = '008';
                    $resolvedType = 4;
                    break;
                case 'credit_card':
                    $displayName = 'Kartu Kredit';
                    $finalCode = 'CREDITCARD';
                    $productCode = 'CREDITCARD';
                    $bankCode = '008';
                    $resolvedType = 5;
                    break;
                case 'debit_card':
                    $displayName = 'Kartu Debit';
                    $finalCode = 'DEBITCARD';
                    $productCode = 'DEBITCARD';
                    $bankCode = '008';
                    $resolvedType = 6;
                    break;
                case 'va_bca':
                    $displayName = 'BCA Virtual Account';
                    $finalCode = 'BCAATM';
                    $productCode = 'BCAATM';
                    $bankCode = '014';
                    $resolvedType = 2;
                    break;
                case 'va_mandiri':
                    $displayName = 'Mandiri Virtual Account';
                    $finalCode = 'MANDIRIATM';
                    $productCode = 'MANDIRIATM';
                    $bankCode = '008';
                    $resolvedType = 2;
                    break;
                case 'va_bri':
                    $displayName = 'BRI Virtual Account';
                    $finalCode = 'BRIATM';
                    $productCode = 'BRIATM';
                    $bankCode = '002';
                    $resolvedType = 2;
                    break;
                case 'va_cimb':
                    $displayName = 'CIMB Niaga Virtual Account';
                    $finalCode = 'CIMBATM';
                    $productCode = 'CIMBATM';
                    $bankCode = '022';
                    $resolvedType = 2;
                    break;
                case 'va_danamon':
                    $displayName = 'Danamon Virtual Account';
                    $finalCode = 'DANAMONATM';
                    $productCode = 'DANAMONATM';
                    $bankCode = '011';
                    $resolvedType = 2;
                    break;
                case 'va_saqu':
                    $displayName = 'Bank Saqu Virtual Account';
                    $finalCode = 'BANKSAQUATM';
                    $productCode = 'BANKSAQUATM';
                    $bankCode = '472';
                    $resolvedType = 2;
                    break;
                case 'va_maybank':
                    $displayName = 'Maybank Virtual Account';
                    $finalCode = 'BIIATM';
                    $productCode = 'BIIATM';
                    $bankCode = '016';
                    $resolvedType = 2;
                    break;
                case 'manual_transfer':
                    $displayName = 'Transfer Bank Manual';
                    $finalCode = 'transfer_manual';
                    $resolvedType = 1;
                    break;
                default:
                    $finalCode = $method->code;
                    $productCode = \App\Models\PaymentMethod::resolveEspayProductCode($method->code, $method->type, $method);
                    $bankCode = \App\Models\PaymentMethod::resolveEspayBankCode($method->code, $method->type, $method);
                    break;
            }

            $typeLabel = match ((int)$resolvedType) {
                1 => 'Bank Transfer Manual',
                2 => 'Virtual Account',
                3 => 'E-Wallet',
                4 => 'QRIS',
                5 => 'Kartu Kredit',
                6 => 'Kartu Debit',
                default => $method->typeLabel(),
            };

            $uniqueMethods[$channelKey] = [
                'code' => $finalCode,
                'db_code' => $method->code,
                'product_code' => $productCode,
                'bank_code' => $bankCode,
                'name' => $displayName,
                'image' => $method->image,
                'type' => $typeLabel,
                'type_id' => $resolvedType,
                'provider' => $method->provider,
                'is_manual' => $isManual,
                'has_charge' => $method->has_charge,
                'charge_value' => $method->charge_value,
                'charge_type' => $method->charge_type, // 1: Percentage, 2: Fixed
                'bank_info' => $method->bank_info,
            ];
        }

        $paymentMethods = array_values($uniqueMethods);

        $user = session()->get('user', []);
        $userId = $user['id'] ?? $user['sub'] ?? null;
        $address = null;
        if ($userId) {
            $address = \App\Models\Frontend\Customer\Address::where('user_id', $userId)->where('is_primary', true)->first();
        }
        $shippingAddress = $orderData['shipping_address'] ?? null;

        return view('frontend.payment', compact('orderData', 'paymentMethods', 'address', 'shippingAddress'));
    }

    public function processPayment(Request $request)
    {
        $orderId = $request->input('order_id');
        $paymentMethod = $request->input('payment_method');

        if (!$paymentMethod) {
            return response()->json([
                'success' => false,
                'message' => 'Silakan pilih saluran metode pembayaran terlebih dahulu.'
            ], 422);
        }

        $sessionOrderData = session()->get('order_data', []);

        $buffer = $this->getCurrentBuffer();
        if (!$buffer && $orderId) {
            $buffer = Buffer::find($orderId);
        }
        if (!$buffer && !empty($sessionOrderData['id'])) {
            $buffer = Buffer::find($sessionOrderData['id']);
        }

        $cart = $buffer ? $this->getBufferCartArray($buffer) : [];

        // Check if an order was already created previously for this exact ID or buffer
        $existingOrder = $this->getOrderFromIdentifier($orderId);
        if (!$existingOrder && !empty($sessionOrderData['id'])) {
            $existingOrder = $this->getOrderFromIdentifier($sessionOrderData['id']);
        }

        // If order already exists and is already paid, redirect to thankyou immediately
        if ($existingOrder && (int)$existingOrder->payment_status === 2) {
            return response()->json([
                'success' => true,
                'redirect_url' => route('thankyou', ['order_id' => $existingOrder->id]),
                'message' => 'Pesanan ini sudah dibayar.'
            ]);
        }

        // If order already has an initiated payment / settlement, redirect to thankyou immediately
        if ($existingOrder && (!empty($existingOrder->settlement_id) || \App\Models\Settlement::where('reference_id', $existingOrder->order_number)->exists() || session()->get('thankyou_order_id') === $existingOrder->id)) {
            return response()->json([
                'success' => true,
                'redirect_url' => route('thankyou', ['order_id' => $existingOrder->id]),
                'message' => 'Pesanan ini sudah berhasil dibuat. Mengalihkan ke rincian pembayaran...'
            ]);
        }

        // Recover cart from buffer meta or session order_data if buffer_items table was cleared or detached
        if (empty($cart) && !$existingOrder) {
            $metaResolved = $buffer?->meta['resolved_items'] ?? ($sessionOrderData['resolved_items'] ?? []);
            if (!empty($metaResolved)) {
                $cart = array_map(function ($r) {
                    $item = $r['item'] ?? [];
                    return [
                        'id' => $item['id'] ?? Str::uuid()->toString(),
                        'product_id' => $item['product_id'] ?? null,
                        'variant_id' => $r['variant_id'] ?? ($item['variant_id'] ?? null),
                        'name' => $item['name'] ?? 'Produk',
                        'brand' => $item['brand'] ?? '',
                        'image' => $item['image'] ?? '',
                        'base_price' => (float) ($r['base_price'] ?? ($item['base_price'] ?? 0)),
                        'sell_price' => (float) ($r['sell_price'] ?? ($item['sell_price'] ?? 0)),
                        'original_price' => (float) ($r['original_price'] ?? ($item['original_price'] ?? 0)),
                        'quantity' => (int) ($item['quantity'] ?? 1),
                        'item_note' => $item['item_note'] ?? '',
                        'color_id' => $item['color_id'] ?? null,
                        'color_name' => $item['color_name'] ?? null,
                        'color_code' => $item['color_code'] ?? null,
                        'type' => $item['type'] ?? 'product',
                        'bundle_data' => $item['bundle_data'] ?? [],
                    ];
                }, $metaResolved);
            } elseif (!empty($sessionOrderData['items'])) {
                $cart = $sessionOrderData['items'];
            }
        }

        if (!$buffer && !$existingOrder && empty($cart)) {
            $lastProductUrl = $this->getLastProductUrl();
            return response()->json([
                'success' => false,
                'redirect_url' => $lastProductUrl,
                'message' => 'Keranjang belanja Anda telah kosong atau sesi telah kedaluwarsa.'
            ], 400);
        }

        if (empty($cart) && !$existingOrder) {
            $lastProductUrl = $this->getLastProductUrl();
            return response()->json([
                'success' => false,
                'redirect_url' => $lastProductUrl,
                'message' => 'Keranjang belanja Anda telah kosong.'
            ], 400);
        }
        $categoryType = $request->input('category_type') ? (int)$request->input('category_type') : null;
        $paymentMethodModel = \App\Models\PaymentMethod::findByCodeOrBank($paymentMethod, $categoryType);

        // Tentukan apakah metode pembayaran adalah Bank Transfer / Manual
        $isBankTransfer = ($paymentMethodModel && (
            $paymentMethodModel->isTypeBankTransfer() 
            || (int)$paymentMethodModel->type === 1 
            || strtolower((string)$paymentMethodModel->provider) !== 'espay'
        )) || in_array($paymentMethod, ['transfer_manual', 'trf'], true);

        if ($isBankTransfer && $request->hasFile('payment_proof')) {
            $request->validate([
                'payment_proof' => 'nullable|file|image|max:10240',
            ]);
        }

        $bufferMeta = $buffer ? ($buffer->meta ?? []) : [];
        $customerData = !empty($bufferMeta['customer']) ? $bufferMeta['customer'] : ($sessionOrderData['customer'] ?? []);
        $shippingAddressData = !empty($bufferMeta['shipping_address']) ? $bufferMeta['shipping_address'] : ($sessionOrderData['shipping_address'] ?? null);
        $resolvedItems = !empty($bufferMeta['resolved_items']) ? $bufferMeta['resolved_items'] : ($sessionOrderData['resolved_items'] ?? []);
        $appliedVouchers = !empty($bufferMeta['applied_vouchers']) ? $bufferMeta['applied_vouchers'] : ($sessionOrderData['applied_vouchers'] ?? []);
        $itemNotes = !empty($bufferMeta['item_notes']) ? $bufferMeta['item_notes'] : ($sessionOrderData['item_notes'] ?? []);

        $charge = 0;
        $baseTotal = $existingOrder ? (float)$existingOrder->total : (float)($buffer?->total ?? ($sessionOrderData['total'] ?? 0));
        if ($paymentMethodModel && $paymentMethodModel->has_charge) {
            $charge = (int) $paymentMethodModel->charge_type === 1 
                ? ($baseTotal * $paymentMethodModel->charge_value / 100) 
                : $paymentMethodModel->charge_value;
        }

        \Illuminate\Support\Facades\DB::beginTransaction();

        try {
            $userId = session()->get('is_logged_in') 
                ? (session()->get('user')['id'] ?? session()->get('user')['sub'] ?? null) 
                : ($customerData['user_id'] ?? null);

            $order = $existingOrder;

            if (!$order) {
                // 1. Create or update Customer and Address

                $inputEmail = strtolower(trim($customerData['email'] ?? ($buffer?->customer_email ?? '')));
                $inputPhone = ltrim(preg_replace('/[\s\-]/', '', trim((string) ($customerData['phone'] ?? ($buffer?->customer_phone ?? '')))), '+');
                $inputName = trim($customerData['name'] ?? ($buffer?->customer_name ?? 'Pelanggan'));

                // Customer unique by email (case-insensitive)
                $customer = !empty($inputEmail)
                    ? Customer::whereRaw('LOWER(email) = ?', [$inputEmail])->first()
                    : null;

                if ($userId && !$customer) {
                    $customer = Customer::where('user_id', $userId)->first();
                }

                if ($customer) {
                    $updateFields = [
                        'name' => $inputName ?: $customer->name,
                    ];
                    if ($userId && empty($customer->user_id)) {
                        $updateFields['user_id'] = $userId;
                    }
                    if (!empty($inputPhone)) {
                        $updateFields['phone'] = $inputPhone;
                    }
                    $customer->update($updateFields);
                } else {
                    $fallbackEmail = !empty($inputEmail) ? $inputEmail : ('guest_' . Str::random(8) . '@imgstore.local');
                    $customer = Customer::create([
                        'id' => Str::uuid()->toString(),
                        'user_id' => $userId,
                        'email' => $fallbackEmail,
                        'name' => $inputName ?: 'Pelanggan',
                        'phone' => $inputPhone,
                    ]);
                }

                if (!$customer) {
                    throw new \Exception('Gagal menyiapkan data pelanggan untuk transaksi pesanan.');
                }

                $savedCustomerAddressId = null;

                if (!empty($customerData['sub_district_id'])) {
                    if (!empty($customerData['selected_address_id'])) {
                        Address::where('id', $customerData['selected_address_id'])->update(['is_primary' => true]);
                        $savedCustomerAddressId = $customerData['selected_address_id'];
                    } else {
                        $subDistrict = SubDistrict::withoutGlobalScopes()->find($customerData['sub_district_id']) ?? SubDistrict::find($customerData['sub_district_id']);
                        if ($subDistrict) {
                            $existingAddr = Address::where(function ($q) use ($customer, $userId) {
                                if ($userId) {
                                    $q->where('user_id', $userId);
                                }
                                $q->orWhere('customer_id', $customer->id);
                            })->where('deleted', false)->first();

                            if ($existingAddr) {
                                $existingAddr->update([
                                    'customer_id' => $customer->id,
                                    'user_id' => $userId ?: $existingAddr->user_id,
                                    'sub_district_id' => $subDistrict->id,
                                    'city_id' => $subDistrict->city_id,
                                    'recipient_name' => $customerData['name'] ?? $existingAddr->recipient_name,
                                    'phone' => $customerData['phone'] ?? $existingAddr->phone,
                                    'address' => $customerData['address'] ?? $existingAddr->address,
                                    'postal_code' => $customerData['postal_code'] ?? ($subDistrict->postal_code ?? $existingAddr->postal_code),
                                    'is_primary' => true,
                                ]);
                                $savedCustomerAddressId = $existingAddr->id;
                            } else {
                                $newAddr = Address::create([
                                    'id' => Str::uuid()->toString(),
                                    'customer_id' => $customer->id,
                                    'user_id' => $userId,
                                    'sub_district_id' => $subDistrict->id,
                                    'city_id' => $subDistrict->city_id,
                                    'label' => 'Rumah',
                                    'recipient_name' => $customerData['name'] ?? '',
                                    'phone' => $customerData['phone'] ?? '',
                                    'address' => $customerData['address'] ?? '',
                                    'postal_code' => $customerData['postal_code'] ?? $subDistrict->postal_code,
                                    'is_primary' => true,
                                ]);
                                $savedCustomerAddressId = $newAddr->id;
                            }
                        }
                    }
                }

                if ($userId) {
                    $user = session()->get('user', []);
                    $user['name'] = $inputName;
                    if (!empty($inputPhone)) {
                        $user['phone'] = $inputPhone;
                    }
                    session()->put('user', $user);
                }

                // 2. Prepare Order Metadata
                $selectedCourierCode = $bufferMeta['courier'] ?? ($sessionOrderData['courier'] ?? ($buffer?->courier?->code ?? ''));
                $shippingCalc = $this->calculateShippingDetails($selectedCourierCode, (string) ($shippingAddressData['sub_district_id'] ?? ''), $cart);
                $etaData = $shippingCalc['eta_dates'] ?? null;
                $etaDuration = $shippingCalc['duration'] ?? '1-2 hari';
                $etaSource = $shippingCalc['eta_source'] ?? ($selectedCourierCode === 'kurir_toko' ? 'store' : 'biteship');

                $orderMeta = array_merge(
                    $shippingAddressData ? ['shipping_address' => $shippingAddressData] : [],
                    [
                        'buffer_id' => $buffer?->id ?? ($sessionOrderData['id'] ?? null),
                        'customer' => $customerData,
                        'customer_address_id' => $savedCustomerAddressId,
                        'platform' => 'website',
                        'payment_started_at' => now()->toIso8601String(),
                        'shipping_eta_label' => $shippingCalc['eta_label'] ?? null,
                        'shipping_duration' => $etaDuration,
                        'shipping_eta_source' => $etaSource,
                        'shipping_service_name' => $shippingCalc['service_name'] ?? null,
                        'shipping_service_code' => $shippingCalc['service_code'] ?? null,
                        'courier_service_type' => $shippingCalc['service_code'] ?? null,
                        'payment_method_name' => $paymentMethodModel?->name,
                        'payment_method_code' => $paymentMethod,
                        'items' => !empty($itemsForOrderData) ? $itemsForOrderData : array_values($cart),
                    ]
                );

                if ($paymentMethodModel && is_array($paymentMethodModel->instructions)) {
                    $orderMeta['payment_instructions'] = $paymentMethodModel->instructions;
                }

                // 3. Create Order
                // shipping_addresses_id on orders table references shipping_addresses (courier rate zone), NOT addresses table.
                $actualShippingAddressesId = null;
                $candidateShippingAddressId = $buffer?->shipping_addresses_id ?? ($sessionOrderData['shipping_addresses_id'] ?? null);
                if (!empty($candidateShippingAddressId)) {
                    if (\App\Models\Frontend\Shipping\ShippingAddress::where('id', $candidateShippingAddressId)->exists()) {
                        $actualShippingAddressesId = $candidateShippingAddressId;
                    }
                }
                if (!$actualShippingAddressesId && !empty($buffer?->courier_id) && !empty($customerData['sub_district_id'])) {
                    $sdId = $customerData['sub_district_id'];
                    $subDistrictModel = SubDistrict::withoutGlobalScopes()->find($sdId) ?? SubDistrict::find($sdId);
                    $destCityId = $subDistrictModel?->city_id;
                    $actualShippingAddressesId = \App\Models\Frontend\Shipping\ShippingAddress::where('courier_id', $buffer->courier_id)
                        ->where(function ($q) use ($sdId, $destCityId) {
                            $q->where('sub_district_id', $sdId);
                            if ($destCityId) {
                                $q->orWhere('city_id', $destCityId);
                            }
                        })
                        ->orderByRaw('sub_district_id IS NOT NULL DESC')
                        ->value('id');
                }

                $orderNumber = Order::generateOrderNumber();
                $orderSubtotal = $buffer?->subtotal ?? ($sessionOrderData['subtotal'] ?? 0);
                $orderDiscount = $buffer?->discount ?? ($sessionOrderData['total_discount'] ?? 0);
                $orderShippingCost = $buffer?->shipping_cost ?? ($sessionOrderData['shipping_cost'] ?? 0);
                $orderShippingSubsidy = $buffer?->shipping_cost_subsidy ?? ($sessionOrderData['shipping_cost_subsidy'] ?? 0);
                $orderTotal = ($orderSubtotal - $orderDiscount + $orderShippingCost - $orderShippingSubsidy) + $charge;

                $order = Order::create([
                    'id' => Str::uuid()->toString(),
                    'order_number' => $orderNumber,
                    'order_date' => now()->format('Y-m-d'),
                    'jde_push_status' => 0,
                    'jde_push_date' => null,
                    'customer_id' => $customer->id,
                    'courier_id' => $buffer?->courier_id ?? ($sessionOrderData['courier_id'] ?? null),
                    'status' => Order::STATUS_PENDING_APPROVAL,
                    'payment_method' => $paymentMethod,
                    'payment_status' => 1,
                    'subtotal' => $orderSubtotal,
                    'tax' => 0,
                    'discount' => $orderDiscount,
                    'total' => $orderTotal,
                    'notes' => null,
                    'voucher_id' => $buffer?->voucher_id ?? ($sessionOrderData['voucher_id'] ?? null),
                    'voucher_nominal' => $buffer?->voucher_nominal ?? ($sessionOrderData['voucher_discount'] ?? 0),
                    'shipping_cost' => $orderShippingCost,
                    'shipping_cost_subsidy' => $orderShippingSubsidy,
                    'shipping_addresses_id' => $actualShippingAddressesId,
                    'transaction_fee' => $charge,
                    'meta' => $orderMeta,
                    'creator' => session()->get('user.username') ?? session()->get('user.name') ?? ($customer ? $customer->name : 'Customer Web'),
                    'editor' => session()->get('user.username') ?? session()->get('user.name') ?? ($customer ? $customer->name : 'Customer Web'),
                ]);

                // 3b. Create initial Delivery record in deliveries table
                try {
                    \App\Models\Frontend\Shipping\Delivery::create([
                        'id' => Str::uuid()->toString(),
                        'order_id' => $order->id,
                        'courier_id' => $order->courier_id,
                        'status' => 1, // pending
                        'estimated_delivery_at' => $etaData['estimated_at'] ?? null,
                        'estimated_delivery_min' => $etaData['min_date'] ?? null,
                        'estimated_delivery_max' => $etaData['max_date'] ?? null,
                        'estimated_delivery_duration' => $etaDuration,
                        'eta_source' => $etaSource,
                        'eta_notes' => $shippingCalc['eta_label'] ?? null,
                    ]);
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::warning("Gagal membuat initial delivery record #{$order->order_number}: " . $e->getMessage());
                }

                // 4. Create OrderItems & Allocate Inventory
                if (!empty($resolvedItems)) {
                    foreach ($resolvedItems as $resolved) {
                        $item = $resolved['item'];
                        $variantId = $resolved['variant_id'];
                        $basePrice = (float) ($resolved['base_price'] ?? $resolved['original_price'] ?? $item['sell_price']);
                        $originalSubtotal = (float) ($resolved['original_subtotal'] ?? ($basePrice * (int) $item['quantity']));
                        $productDiscountNominal = (float) ($resolved['discount_nominal'] ?? 0);
                        $discountPercent = (float) ($resolved['discount_percent'] ?? 0);
                        $itemTotal = (float) ($resolved['item_total'] ?? max(0, $originalSubtotal - $productDiscountNominal));

                        $itemNotesValue = $item['item_note'] ?? ($itemNotes[$item['id']] ?? '');
                        if (($item['type'] ?? null) === 'bundle' && ($item['bundle_data'] ?? null)) {
                            $itemNotesValue = $item['bundle_data'];
                            $itemName = ($item['bundle_data']['bundle_name'] ?? 'Paket Bundling');
                        } else {
                            $itemName = $item['name'];
                        }

                        $itemMeta = [];
                        if (!empty($item['color_id'])) {
                            $itemMeta['color_id'] = $item['color_id'];
                            $itemMeta['color_name'] = $item['color_name'] ?? null;
                            $itemMeta['color_code'] = $item['color_code'] ?? null;
                        }
                        $itemMeta['base_price'] = $basePrice;
                        $itemMeta['original_price'] = $basePrice;
                        $itemMeta['after_disc_price'] = (int)$item['quantity'] > 0 ? round($itemTotal / (int)$item['quantity'], 2) : $basePrice;
                        $itemMeta['original_subtotal'] = $originalSubtotal;
                        $itemMeta['discount_nominal'] = $productDiscountNominal;
                        $itemMeta['discount_percent'] = $discountPercent;
                        $itemMeta['unit_discount_nominal'] = (int)$item['quantity'] > 0 ? round($productDiscountNominal / (int)$item['quantity'], 2) : 0;

                        OrderItem::create([
                            'id' => Str::uuid(),
                            'order_id' => $order->id,
                            'product_id' => $item['product_id'],
                            'product_variant_id' => $variantId,
                            'name' => $itemName,
                            'quantity' => $item['quantity'],
                            'unit_price' => $basePrice,
                            'discount_nominal' => $productDiscountNominal,
                            'discount_percent' => $discountPercent,
                            'total' => $itemTotal,
                            'item_notes' => is_array($itemNotesValue) ? json_encode($itemNotesValue) : $itemNotesValue,
                            'meta' => $itemMeta,
                        ]);

                        if ($variantId && ($item['type'] ?? null) !== 'bundle') {
                            \App\Services\InventoryService::recordWebOrder($variantId, (int) $item['quantity']);
                        }

                        if (($item['type'] ?? null) === 'bundle' && isset($item['bundle_data']['items'])) {
                            foreach ($item['bundle_data']['items'] as $bItem) {
                                $bVariantId = $bItem['variant_id'] ?? null;
                                $bQty = ((int)$bItem['quantity']) * ((int)$item['quantity']);

                                OrderItem::create([
                                    'id' => Str::uuid(),
                                    'order_id' => $order->id,
                                    'product_id' => $bItem['product_id'],
                                    'product_variant_id' => $bVariantId,
                                    'name' => ' - ' . ($bItem['product_name'] ?? 'Produk'),
                                    'quantity' => $bQty,
                                    'unit_price' => 0,
                                    'discount_nominal' => 0,
                                    'discount_percent' => 0,
                                    'total' => 0,
                                    'item_notes' => 'Bagian dari paket: ' . ($item['bundle_data']['bundle_name'] ?? 'Bundling'),
                                    'meta' => json_encode(['is_bundle_item' => true, 'bundle_id' => $item['bundle_data']['bundle_id'] ?? null])
                                ]);

                                if ($bVariantId) {
                                    \App\Services\InventoryService::recordWebOrder($bVariantId, $bQty);
                                }
                            }
                        }
                    }
                } else {
                    foreach ($cart as $item) {
                        $variantId = $item['variant_id'] ?? null;
                        $basePrice = 0.0;
                        if ($variantId) {
                            $vModel = \App\Models\Frontend\ProductsCatalog\ProductVariant::find($variantId);
                            if ($vModel) {
                                $basePrice = (float) ($vModel->base_price > 0 ? $vModel->base_price : $vModel->sell_price);
                            }
                        }
                        if ($basePrice <= 0.0) {
                            $basePrice = (float) ($item['base_price'] ?? ($item['sell_price'] ?? 0));
                        }
                        $qty = (int) $item['quantity'];
                        $sellPrice = (float) ($item['sell_price'] ?? $basePrice);
                        $origSub = $basePrice * $qty;
                        $itemTot = $sellPrice * $qty;
                        $discNom = max(0.0, $origSub - $itemTot);
                        $discPct = $origSub > 0 ? round(($discNom / $origSub) * 100, 2) : 0.0;

                        OrderItem::create([
                            'id' => Str::uuid(),
                            'order_id' => $order->id,
                            'product_id' => $item['product_id'],
                            'product_variant_id' => $variantId,
                            'name' => $item['name'],
                            'quantity' => $qty,
                            'unit_price' => $basePrice,
                            'discount_nominal' => $discNom,
                            'discount_percent' => $discPct,
                            'total' => $itemTot,
                            'item_notes' => $item['item_note'] ?? '',
                            'meta' => [
                                'base_price' => $basePrice,
                                'after_disc_price' => $sellPrice,
                                'original_price' => $basePrice,
                                'original_subtotal' => $origSub,
                                'discount_nominal' => $discNom,
                                'discount_percent' => $discPct,
                            ],
                        ]);
                        if (!empty($item['variant_id'])) {
                            \App\Services\InventoryService::recordWebOrder($item['variant_id'], (int) $item['quantity']);
                        }
                    }
                }

                // 5. Voucher Usage
                foreach ($appliedVouchers as $appliedVoucher) {
                    $voucherIdToUse = $appliedVoucher['voucher']['id'] ?? ($appliedVoucher['voucher']->id ?? null);
                    $appliedVoucherModel = $voucherIdToUse ? \App\Models\Frontend\Promo\Voucher::find($voucherIdToUse) : null;
                    if ($appliedVoucherModel) {
                        $resolvedUsageUserId = $userId ?: ($customer?->user_id ?: ($customer?->id ?: null));
                        VoucherUsage::create([
                            'id' => Str::uuid(),
                            'voucher_id' => $appliedVoucherModel->id,
                            'user_id' => $resolvedUsageUserId,
                            'order_id' => $order->id,
                            'discount_amount' => $appliedVoucher['discount'],
                        ]);
                        $appliedVoucherModel->increment('used_count');

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
                }
            } else {
                $meta = $order->meta ?? [];
                $meta['payment_started_at'] = now()->toIso8601String();
                if ($paymentMethodModel && is_array($paymentMethodModel->instructions)) {
                    $meta['payment_instructions'] = $paymentMethodModel->instructions;
                }
                $order->meta = $meta;
            }

            // Post-creation safety guarantee: ensure order has items in order_items table
            $dbItemCount = \Illuminate\Support\Facades\DB::table('order_items')->where('order_id', $order->id)->count();
            if ($dbItemCount === 0) {
                \Illuminate\Support\Facades\Log::warning("Order #{$order->order_number} has 0 items, running emergency order item insertion");
                $fallbackCart = !empty($cart) ? $cart : ($sessionOrderData['items'] ?? ($order->meta['items'] ?? []));
                foreach ($fallbackCart as $item) {
                    $variantId = $item['variant_id'] ?? ($item['id'] !== ($item['product_id'] ?? null) ? $item['id'] : null);
                    $qty = max(1, (int) ($item['quantity'] ?? 1));
                    $sellPrice = (float) ($item['sell_price'] ?? $item['price'] ?? 0);
                    $basePrice = (float) ($item['base_price'] ?? $item['original_price'] ?? $sellPrice);
                    $itemTotal = (float) ($item['total'] ?? ($sellPrice * $qty));
                    $discNom = max(0.0, ($basePrice * $qty) - $itemTotal);
                    $discPct = ($basePrice * $qty) > 0 ? round(($discNom / ($basePrice * $qty)) * 100, 2) : 0.0;

                    $itemMeta = [
                        'base_price' => $basePrice,
                        'original_price' => $basePrice,
                        'after_disc_price' => $sellPrice,
                        'discount_nominal' => $discNom,
                        'discount_percent' => $discPct,
                    ];
                    if (!empty($item['image'])) {
                        $itemMeta['image'] = $item['image'];
                    }

                    OrderItem::create([
                        'id' => Str::uuid(),
                        'order_id' => $order->id,
                        'product_id' => $item['product_id'] ?? null,
                        'product_variant_id' => $variantId,
                        'name' => $item['name'] ?? 'Produk',
                        'quantity' => $qty,
                        'unit_price' => $basePrice,
                        'discount_nominal' => $discNom,
                        'discount_percent' => $discPct,
                        'total' => $itemTotal,
                        'item_notes' => $item['item_note'] ?? '',
                        'meta' => $itemMeta,
                    ]);
                }
            }

            $meta = $order->meta ?? [];

            // 6. Handle Bank Transfer
            if ($isBankTransfer) {
                if ($request->hasFile('payment_proof')) {
                    try {
                        if (config('filesystems.disks.s3.key') || config('filesystems.disks.s3.bucket')) {
                            $proofPath = $request->file('payment_proof')->store('payment_proofs', 's3');
                        } else {
                            $proofPath = $request->file('payment_proof')->store('payment_proofs', 'public');
                        }
                    } catch (\Throwable $e) {
                        $proofPath = $request->file('payment_proof')->store('payment_proofs', 'public');
                    }
                    $meta['payment_proof'] = $proofPath;
                    $order->payment_status = 2;
                } elseif ($request->filled('payment_proof')) {
                    $meta['payment_proof'] = $request->input('payment_proof');
                    $order->payment_status = 2;
                }
            }

            $order->payment_method = $paymentMethod;
            $order->transaction_fee = $charge;
            $order->total = ((float)$order->subtotal - (float)$order->discount + (float)$order->shipping_cost - (float)($order->shipping_cost_subsidy ?? 0)) + $charge;
            $order->meta = $meta;
            $order->save();

            \Illuminate\Support\Facades\DB::commit();
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            \Illuminate\Support\Facades\Log::error('Checkout processPayment error: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat memproses pesanan: ' . $e->getMessage()
            ], 500);
        }

        $isEspay = $paymentMethodModel && strtolower((string)$paymentMethodModel->provider) === 'espay';
        if ($isEspay) {
            $amount = number_format((float)($order->total), 2, '.', '');
            $baseUrl = rtrim(config('espay.base_url', 'https://sandbox-api.espay.id/rest/merchant'), '/');
            $espayUrl = str_replace('/rest/merchant', '/rest/merchantpg', $baseUrl) . '/sendinvoice';

            $signatureKey = config('espay.signature_key');
            $commCode = config('espay.merchant_key');
            $rqUuid = Str::uuid()->toString();
            $rqDatetime = date('Y-m-d H:i:s');
            $espayOrderId = str_replace('-', '', $order->order_number);

            $dataToHash = "##{$signatureKey}##{$rqUuid}##{$rqDatetime}##{$espayOrderId}##{$amount}##IDR##{$commCode}##SENDINVOICE##";
            $signature = hash('sha256', strtoupper($dataToHash));

            $reqProductCode = $request->input('product_code');
            $reqBankCode = $request->input('bank_code');

            $resolvedProductCode = !empty($reqProductCode) 
                ? $reqProductCode 
                : \App\Models\PaymentMethod::resolveEspayProductCode(
                    codeOrBank: $paymentMethod,
                    type: $categoryType ?? $paymentMethodModel?->type,
                    paymentMethod: $paymentMethodModel
                );

            $resolvedBankCode = !empty($reqBankCode) 
                ? $reqBankCode 
                : \App\Models\PaymentMethod::resolveEspayBankCode(
                    codeOrBank: $paymentMethod,
                    type: $categoryType ?? $paymentMethodModel?->type,
                    paymentMethod: $paymentMethodModel
                );

            // Special explicit overrides for clearing codes (misal 014: BCA VA vs GoPay Jump App)
            $upperMethod = strtoupper($paymentMethod);
            if (in_array($upperMethod, ['GOPAY', 'GOPAYJUMPAPP', 'GOPAYINAPP']) || (($categoryType === 3 || $paymentMethodModel?->type === 3) && in_array($paymentMethod, ['014', 'GOPAY', 'GOPAYJUMPAPP', 'GOPAYINAPP']))) {
                $resolvedProductCode = 'GOPAYJUMPAPP';
                $resolvedBankCode = '014';
            } elseif (in_array($upperMethod, ['BCAATM', 'BCA']) || (($categoryType === 2 || $paymentMethodModel?->type === 2) && in_array($paymentMethod, ['014', 'BCAATM', 'BCA']))) {
                $resolvedProductCode = 'BCAATM';
                $resolvedBankCode = '014';
            } elseif (in_array($upperMethod, ['QRIS', 'QRISPLUS']) || ($categoryType === 4 || $paymentMethodModel?->type === 4)) {
                $resolvedProductCode = 'QRISPLUS';
                $resolvedBankCode = '008';
            } elseif (in_array($upperMethod, ['OVO']) || (($categoryType === 3 || $paymentMethodModel?->type === 3) && in_array($paymentMethod, ['503', 'OVO']))) {
                $resolvedProductCode = 'OVO';
                $resolvedBankCode = '503';
            } elseif (in_array($upperMethod, ['CREDITCARD', 'CREDIT']) || ($categoryType === 5 || $paymentMethodModel?->type === 5)) {
                $resolvedProductCode = 'CREDITCARD';
                $resolvedBankCode = '008';
            }

            // Espay SendInvoice expects bank clearing code (e.g. 014, 008, 002) in 'bank_code' parameter
            $espayBankCode = $resolvedBankCode ?: \App\Models\PaymentMethod::resolveEspayBankCode(
                codeOrBank: $paymentMethod,
                type: $categoryType ?? $paymentMethodModel?->type,
                paymentMethod: $paymentMethodModel
            );

            $payload = [
                'rq_uuid' => $rqUuid,
                'rq_datetime' => $rqDatetime,
                'order_id' => $espayOrderId,
                'amount' => $amount,
                'ccy' => 'IDR',
                'comm_code' => $commCode,
                'remark1' => $order->customer->phone ?? ($customerData['phone'] ?? '00000000000'),
                'remark2' => $order->customer->name ?? ($customerData['name'] ?? 'Customer'),
                'remark3' => $order->customer->email ?? ($customerData['email'] ?? ''),
                'update' => 'N',
                'bank_code' => $espayBankCode,
                'va_expired' => 1440,
                'signature' => $signature,
            ];

            try {
                \Illuminate\Support\Facades\Log::channel('espay')->info("Espay Send Invoice Request\nURL: {$espayUrl}\nOrder: {$order->order_number}\nPayload: " . json_encode($payload, JSON_PRETTY_PRINT));

                $response = \Illuminate\Support\Facades\Http::timeout(30)->asForm()->post($espayUrl, $payload);
                $paymentData = $response->json();

                $logData = [
                    'http_status' => $response->status(),
                    'raw_body' => $response->body(),
                    'parsed_json' => $paymentData,
                ];

                if ($response->successful() && isset($paymentData['error_code']) && $paymentData['error_code'] === '0000') {
                    $settlement = \App\Models\Settlement::updateOrCreate(
                        ['reference_id' => $order->order_number],
                        [
                            'gross_amount' => $amount,
                            'fee_amount' => $charge,
                            'net_amount' => $order->total,
                            'status' => 'pending',
                            'notes' => "Payment via {$paymentMethod}"
                        ]
                    );

                    $updatedMeta = array_merge($order->meta ?? [], [
                        'espay_reference' => $paymentData['reference'] ?? ($paymentData['trx_id'] ?? ''),
                        'va_number' => $paymentData['va_number'] ?? '',
                    ]);

                    if (!empty($paymentData['qr_code'])) {
                        $updatedMeta['qr_code'] = $paymentData['qr_code'];
                    }
                    if (!empty($paymentData['qr_string'])) {
                        $updatedMeta['qr_string'] = $paymentData['qr_string'];
                    }
                    if (!empty($paymentData['payment_url'])) {
                        $updatedMeta['payment_url'] = $paymentData['payment_url'];
                    }

                    $order->update([
                        'settlement_id' => $settlement->id,
                        'meta' => $updatedMeta,
                    ]);

                    \Illuminate\Support\Facades\Log::channel('espay')->info("Espay Send Invoice Success\nOrder ID: {$order->order_number}\nData: " . json_encode($logData, JSON_PRETTY_PRINT));
                } else {
                    \Illuminate\Support\Facades\Log::channel('espay')->error("Espay Send Invoice Failed\nOrder ID: {$order->order_number}\nData: " . json_encode($logData, JSON_PRETTY_PRINT));

                    if (!$existingOrder) {
                        $this->rollbackFailedOrder($order);
                    }

                    return response()->json([
                        'success' => false,
                        'message' => 'Gagal mendapatkan data pembayaran dari Espay: ' . ($paymentData['error_message'] ?? 'Unknown error')
                    ]);
                }
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::channel('espay')->error("Espay Exception for Order {$order->order_number}: " . $e->getMessage() . "\nTrace: " . $e->getTraceAsString());

                if (!$existingOrder) {
                    $this->rollbackFailedOrder($order);
                }

                return response()->json([
                    'success' => false,
                    'message' => 'Terjadi kesalahan sistem saat menghubungi payment gateway.'
                ]);
            }
        }

        // Send notification email
        try {
            $customerEmail = $order->customer->email ?? ($customerData['email'] ?? null);
            if ($customerEmail) {
                \Illuminate\Support\Facades\Mail::to($customerEmail)->send(new \App\Mail\OrderCreated($order));
                \Illuminate\Support\Facades\Log::channel('email')->info("OrderCreated email sent successfully to {$customerEmail} for Order #{$order->order_number}");
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::channel('email')->error("Failed to send OrderCreated email to {$customerEmail}: " . $e->getMessage(), [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'exception' => $e->getMessage(),
            ]);
            \Illuminate\Support\Facades\Log::error('Gagal mengirim email OrderCreated: ' . $e->getMessage());
        }

        // Clear Buffer & Session
        if ($buffer) {
            $buffer->items()->delete();
            $buffer->delete();
        }

        session()->forget(['order_data', 'checkout_data', 'selected_voucher_codes', 'selected_voucher', 'cart', 'checkout_form_data', 'order_preview']);
        session()->put('thankyou_order_id', $order->id);
        session()->put('last_created_order_id', $order->id);

        $thankYouUrl = route('thankyou', ['order_id' => $order->id]);

        // If Espay payment method (Embed Kit integration)
        if ($isEspay) {
            $apiKey = config('espay.api_key');
            $commCode = config('espay.merchant_key');
            $paymentId = $order->order_number;
            $sig = sha1($paymentId . '590' . $apiKey);

            // Return espay_kit payload for Snap iframe integration
            return response()->json([
                'success' => true,
                'open_iframe' => true,
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'redirect_url' => $order->meta['payment_url'] ?? $thankYouUrl,
                'espay_kit' => [
                    'key' => $apiKey,
                    'paymentId' => $paymentId,
                    'backUrl' => $thankYouUrl,
                    'commCode' => $commCode,
                    'bankCode' => $resolvedBankCode,
                    'productCode' => $resolvedProductCode,
                    'signature' => $sig,
                    'amount' => (string) round((float) $order->total),
                ]
            ]);
        }

        return response()->json([
            'success' => true,
            'redirect_url' => $thankYouUrl
        ]);
    }

    public function uploadPaymentProof(Request $request, string $orderId)
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

        $request->validate([
            'payment_proof' => $request->hasFile('payment_proof')
                ? 'required|image|mimes:jpeg,png,jpg,webp|max:5120'
                : 'required|string',
        ]);

        $proofPath = null;
        if ($request->hasFile('payment_proof')) {
            $proofPath = $request->file('payment_proof')->store('payment_proofs', 's3');
        } elseif ($request->filled('payment_proof')) {
            $proofPath = $request->input('payment_proof');
        }

        if ($proofPath) {
            $meta = $order->meta ?? [];
            $meta['payment_started_at'] = now()->toIso8601String();
            $meta['payment_proof'] = $proofPath;

            $order->update([
                'meta' => $meta,
                'payment_status' => 2, // Terbayar / Menunggu verifikasi
            ]);

            return redirect()->back()->with('success', 'Bukti transfer berhasil diunggah.');
        }

        return redirect()->back()->with('error', 'Gagal mengunggah bukti transfer.');
    }

    public function cancelOrder(Request $request, string $orderId)
    {
        try {
            $order = null;
            if (\Illuminate\Support\Str::isUuid($orderId)) {
                $order = \App\Models\Frontend\Order::where('id', $orderId)->first();
            }
            if (!$order) {
                $order = \App\Models\Frontend\Order::where('order_number', $orderId)->first();
            }

            if (!$order) {
                return redirect()->back()->with('error', 'Pesanan tidak ditemukan.');
            }

            // Authorization check
            $user = session()->get('user', []);
            $userId = $user['id'] ?? $user['sub'] ?? null;
            $userEmail = $user['email'] ?? null;
            if ($userId || $userEmail) {
                $customerUserId = $order->customer?->user_id;
                $customerEmail = $order->customer?->email ?? ($order->meta['customer']['email'] ?? null);
                if (($customerUserId && $userId && $customerUserId !== $userId) && ($customerEmail && $userEmail && strtolower($customerEmail) !== strtolower($userEmail))) {
                    return redirect()->back()->with('error', 'Anda tidak memiliki akses untuk membatalkan pesanan ini.');
                }
            }

            if ($order->status === \App\Models\Frontend\Order::STATUS_CANCELLED) {
                return redirect()->back()->with('info', 'Pesanan ini sudah dibatalkan sebelumnya.');
            }

            // Only allow cancel if unpaid (payment_status == 1) and not shipped
            if ((int)$order->payment_status !== 1 && (int)$order->status >= \App\Models\Frontend\Order::STATUS_SHIPPED) {
                return redirect()->back()->with('error', 'Pesanan yang sudah dibayar atau sedang dikirim tidak dapat dibatalkan secara otomatis.');
            }

            $order->status = \App\Models\Frontend\Order::STATUS_CANCELLED;
            $orderMeta = is_array($order->meta) ? $order->meta : [];
            $orderMeta['cancelled_at'] = now()->toIso8601String();
            $orderMeta['cancelled_by'] = 'customer';
            $order->meta = $orderMeta;
            $order->save();

            // Release voucher usages and decrement used_count
            try {
                $usages = \App\Models\Frontend\Promo\VoucherUsage::where('order_id', $order->id)->get();
                foreach ($usages as $usage) {
                    if ($usage->voucher_id) {
                        $v = \App\Models\Frontend\Promo\Voucher::withoutGlobalScopes()->find($usage->voucher_id);
                        if ($v && $v->used_count > 0) {
                            $v->decrement('used_count');
                        }
                    }
                    $usage->delete();
                }
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning("Gagal melepaskan voucher usage saat pembatalan order #{$order->order_number}: " . $e->getMessage());
            }

            return redirect()->back()->with('success', "Pesanan #{$order->order_number} berhasil dibatalkan. Anda dapat memesan kembali produk kapan saja.");
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error("Gagal membatalkan pesanan {$orderId}: " . $e->getMessage());
            return redirect()->back()->with('error', 'Terjadi kendala saat membatalkan pesanan. Silakan coba lagi.');
        }
    }

    public function reorder(string $orderId)
    {
        try {
            $order = null;
            $query = \App\Models\Frontend\Order::with(['items.product.variants', 'items.variant', 'customer']);
            if (\Illuminate\Support\Str::isUuid($orderId)) {
                $order = $query->where('id', $orderId)->first();
            }
            if (!$order) {
                $order = $query->where('order_number', $orderId)->first();
            }

            if (!$order) {
                return redirect()->back()->with('error', 'Pesanan tidak ditemukan.');
            }

            // Authorization check
            $user = session()->get('user', []);
            $userId = $user['id'] ?? $user['sub'] ?? null;
            $userEmail = $user['email'] ?? null;
            if ($userId || $userEmail) {
                $customerUserId = $order->customer?->user_id;
                $customerEmail = $order->customer?->email ?? ($order->meta['customer']['email'] ?? null);
                if (($customerUserId && $userId && $customerUserId !== $userId) && ($customerEmail && $userEmail && strtolower($customerEmail) !== strtolower($userEmail))) {
                    return redirect()->back()->with('error', 'Anda tidak memiliki akses ke pesanan ini.');
                }
            }

            if ($order->items->isEmpty()) {
                return redirect()->back()->with('warning', 'Pesanan ini tidak memiliki produk untuk di-order ulang.');
            }

            $buffer = $this->findOrCreateBuffer();
            $addedQuantity = 0;
            $skippedItems = [];

            foreach ($order->items as $item) {
                $product = $item->product ?? \App\Models\Frontend\ProductsCatalog\Product::with('variants')->find($item->product_id);
                if (!$product || ($product->deleted ?? false) || !(int)$product->status) {
                    $skippedItems[] = $item->name . ' (Produk tidak aktif/dihapus)';
                    continue;
                }

                $variant = null;
                if ($item->product_variant_id) {
                    $variant = $item->variant ?? ($product->variants ? $product->variants->firstWhere('id', $item->product_variant_id) : null);
                    if (!$variant || !(int)$variant->status) {
                        $skippedItems[] = $item->name . ' (Varian sudah tidak tersedia)';
                        continue;
                    }
                }

                $availableStock = $variant ? $variant->stock : $product->stock;
                if ($availableStock !== null && (int)$availableStock <= 0) {
                    $skippedItems[] = $item->name . ' (Stok habis)';
                    continue;
                }

                $sellPrice = $variant ? (float)$variant->sell_price : (float)($product->sell_price ?? $product->price ?? $item->unit_price);
                $basePrice = $variant ? (float)($variant->base_price ?? $variant->sell_price) : (float)($product->base_price ?? $product->price ?? $item->unit_price);
                $qty = max(1, (int)$item->quantity);
                if ($availableStock !== null && $qty > (int)$availableStock) {
                    $qty = (int)$availableStock;
                }

                $itemName = $product->name;
                if ($variant && !empty($variant->variant_name)) {
                    $itemName .= ' - ' . $variant->variant_name;
                }

                $existingItemQuery = BufferItem::where('buffer_id', $buffer->id)
                    ->where('product_id', $product->id);

                if ($variant) {
                    $existingItemQuery->where('product_variant_id', $variant->id);
                } else {
                    $existingItemQuery->whereNull('product_variant_id');
                }

                $existingItem = $existingItemQuery->first();

                $meta = [
                    'base_price' => $basePrice,
                    'after_disc_price' => $sellPrice,
                    'original_price' => $basePrice,
                    'image' => $product->thumbnail_url ?? media_url($product->image),
                    'reorder_from_order_number' => $order->order_number,
                ];

                if ($existingItem) {
                    $newQty = $existingItem->quantity + $qty;
                    $discNom = max(0.0, ($basePrice - $sellPrice) * $newQty);
                    $existingItem->update([
                        'quantity' => $newQty,
                        'unit_price' => $basePrice,
                        'total' => $sellPrice * $newQty,
                        'discount_nominal' => $discNom,
                        'meta' => array_merge(is_array($existingItem->meta) ? $existingItem->meta : [], $meta),
                    ]);
                } else {
                    $discNom = max(0.0, ($basePrice - $sellPrice) * $qty);
                    $discPct = $basePrice > 0 ? round((($basePrice - $sellPrice) / $basePrice) * 100, 2) : 0;
                    BufferItem::create([
                        'id' => Str::uuid()->toString(),
                        'buffer_id' => $buffer->id,
                        'product_id' => $product->id,
                        'product_variant_id' => $variant?->id,
                        'name' => $itemName,
                        'quantity' => $qty,
                        'unit_price' => $basePrice,
                        'total' => $sellPrice * $qty,
                        'discount_nominal' => $discNom,
                        'discount_percent' => $discPct,
                        'item_notes' => $item->item_notes ?? '',
                        'meta' => $meta,
                    ]);
                }

                $addedQuantity += $qty;
            }

            $this->recalculateBuffer($buffer);
            session()->put('reorder_for', $order->order_number);

            if ($addedQuantity === 0) {
                $msg = !empty($skippedItems)
                    ? 'Produk dari pesanan ini tidak dapat di-order ulang: ' . implode(', ', $skippedItems)
                    : 'Produk dari pesanan ini sudah tidak tersedia atau stok habis.';
                return redirect()->back()->with('warning', $msg);
            }

            if (!empty($skippedItems)) {
                $msg = "Berhasil menambahkan {$addedQuantity} produk ke keranjang belanja. Beberapa produk tidak tersedia: " . implode(', ', $skippedItems);
                return redirect()->route('checkout')->with('warning', $msg);
            }

            return redirect()->route('checkout')->with('success', "Berhasil menambahkan {$addedQuantity} produk dari pesanan #{$order->order_number} ke keranjang belanja.");
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error("Gagal melakukan order ulang untuk pesanan {$orderId}: " . $e->getMessage() . "\n" . $e->getTraceAsString());
            return redirect()->back()->with('error', 'Terjadi kendala saat memproses order ulang. Silakan coba kembali.');
        }
    }

    public function thankYou(Request $request)
    {
        $orderIdFromUrl = $request->query('order_id');
        $orderId = $orderIdFromUrl ?: session('thankyou_order_id');
        $order = null;
        
        if ($orderId) {
            $order = $this->getOrderFromIdentifier($orderId);
            if (!$order) {
                $order = Order::with(['customer', 'courier', 'items.product', 'voucher'])->find($orderId);
            }
        } else {
            // As last resort, get most recent order for logged-in user
            if (session()->get('is_logged_in')) {
                $user = session()->get('user', []);
                $userId = $user['id'] ?? $user['sub'] ?? null;
                
                if ($userId) {
                    $order = Order::with(['customer', 'courier', 'items.product', 'voucher'])
                        ->whereHas('customer', function ($q) use ($userId) {
                            $q->where('user_id', $userId);
                        })
                        ->latest()
                        ->first();
                }
            }
        }
        if (!$order) {
            return redirect()->route('home')->with('error', 'Pesanan tidak ditemukan.');
        }

        // Pastikan seluruh session checkout dan form data dibersihkan setelah transaksi selesai
        session()->forget([
            'checkout_form_data',
            'order_data',
            'checkout_data',
            'selected_voucher_codes',
            'selected_voucher',
            'cart',
            'order_preview',
        ]);

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

        $userId = session()->get('is_logged_in') ? (session()->get('user')['id'] ?? session()->get('user')['sub'] ?? null) : null;

        return Voucher::active()
            ->where('show_on_web', true)
            ->with(['categories', 'products', 'brands', 'customerGroups'])
            ->get()
            ->filter(function ($voucher) use ($cart, $userId) {
                return $voucher->appliesToCart($cart, $userId);
            })
            ->map(function ($voucher) use ($cart, $userId) {
                $voucher->is_usable = $voucher->canBeUsedBy($userId);
                $voucher->eligible_subtotal = $voucher->getEligibleSubtotal($cart);
                return $voucher;
            })
            ->values();
    }

    private function voucherAppliesToCart(Voucher $voucher, array $cartProductIds, array $cartCategoryIds, ?string $userId = null): bool
    {
        // Scope 2: Customer Tertentu
        if ((int) $voucher->scope === 2) {
            return $voucher->canBeUsedBy($userId);
        }

        // Scope 3: Kategori Tertentu
        if ((int) $voucher->scope === 3) {
            return $voucher->categories()->where('product_category.deleted', false)->whereIn('product_category.id', $cartCategoryIds)->exists();
        }

        // Scope 4: Produk Tertentu (per Artikel)
        if ((int) $voucher->scope === 4) {
            return $voucher->products()->where('products.deleted', false)->whereIn('products.id', $cartProductIds)->exists();
        }

        // Scope 5: Brand Tertentu
        if ((int) $voucher->scope === 5) {
            $cartBrandIds = Product::whereIn('id', $cartProductIds)->where('deleted', false)->pluck('brand_id')->unique()->toArray();
            return $voucher->brands()->where('brands.deleted', false)->whereIn('brands.id', $cartBrandIds)->exists();
        }

        // Scope 6: Brand & Artikel Tertentu
        if ((int) $voucher->scope === 6) {
            $cartBrandIds = Product::whereIn('id', $cartProductIds)->where('deleted', false)->pluck('brand_id')->unique()->toArray();
            $hasProduct = $voucher->products()->where('products.deleted', false)->whereIn('products.id', $cartProductIds)->exists();
            $hasBrand = $voucher->brands()->where('brands.deleted', false)->whereIn('brands.id', $cartBrandIds)->exists();
            return $hasProduct || $hasBrand;
        }

        // Scope 7: Group Customer (Karyawan / Reseller)
        if ((int) $voucher->scope === 7) {
            return $voucher->canBeUsedBy($userId);
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
            ->with(['categories', 'products', 'brands', 'customerGroups'])
            ->where(function ($query) use ($codes) {
                foreach ($codes as $code) {
                    $query->orWhereRaw('LOWER(code) = ?', [strtolower($code)]);
                }
            })
            ->get()
            ->keyBy(fn($v) => strtoupper($v->code));

        $orderedVouchers = collect($codes)->map(fn($code) => $vouchers->get(strtoupper($code)))->filter()->values();
        if ($orderedVouchers->count() > 1 && !Voucher::validateVoucherCombination($orderedVouchers)) {
            $orderedVouchers = $orderedVouchers->take(1);
        }

        $appliedVouchers = [];
        $productVoucherDiscount = 0.0;
        $shippingVoucherDiscount = 0.0;
        foreach ($orderedVouchers as $voucher) {
            if (!$voucher->canBeUsedBy($userId)) {
                continue;
            }

            if (!$voucher->appliesToCart($cart, $userId)) {
                continue;
            }

            $eligibleSubtotal = $voucher->getEligibleSubtotal($cart);
            if (in_array((int)$voucher->scope, [3, 4, 5, 6], true) && $eligibleSubtotal <= 0) {
                continue;
            }

            if ($eligibleSubtotal < $voucher->min_purchase) {
                continue;
            }

            $voucherDiscount = $voucher->calculateDiscountValue($eligibleSubtotal, $shippingCost ?? 0);

            if ((int) $voucher->type === 3) {
                // Voucher Gratis Ongkir: motong biaya kirim only, jangan sampai potong harga barang!
                $actualDiscount = min((float) $voucherDiscount, (float) ($shippingCost ?? 0));
                $shippingVoucherDiscount += $actualDiscount;
                $appliedVouchers[] = [
                    'voucher' => $voucher,
                    'discount' => $actualDiscount,
                    'is_shipping' => true,
                ];
            } else {
                $actualDiscount = min((float) $voucherDiscount, (float) $eligibleSubtotal);
                $productVoucherDiscount += $actualDiscount;
                $appliedVouchers[] = [
                    'voucher' => $voucher,
                    'discount' => $actualDiscount,
                    'is_shipping' => false,
                ];
            }
        }

        $totalDiscount = $productVoucherDiscount + $shippingVoucherDiscount;

        return [
            'discount' => $totalDiscount,
            'product_voucher_discount' => $productVoucherDiscount,
            'shipping_voucher_discount' => $shippingVoucherDiscount,
            'primary' => $orderedVouchers->first(),
            'vouchers' => $appliedVouchers,
        ];
    }

    private function getVoucherEligibleSubtotal(Voucher $voucher, array $cart): float
    {
        // Scope 2 (Customer) & Scope 7 (Group Customer): All products in cart are eligible
        if (in_array((int) $voucher->scope, [1, 2, 7], true)) {
            return (float) collect($cart)->sum(fn($item) => ($item['sell_price'] ?? 0) * ($item['quantity'] ?? 0));
        }

        // Scope 3: Kategori tertentu
        if ((int) $voucher->scope === 3) {
            $productIds = $voucher->categories()
                ->where('product_category.deleted', false)
                ->with('products')
                ->get()
                ->flatMap(fn($category) => $category->products->where('deleted', false)->pluck('id'))
                ->unique()
                ->toArray();

            return (float) collect($cart)
                ->filter(fn($item) => in_array($item['product_id'] ?? null, $productIds, true))
                ->sum(fn($item) => ($item['sell_price'] ?? 0) * ($item['quantity'] ?? 0));
        }

        // Scope 4: Produk Tertentu (per Artikel)
        if ((int) $voucher->scope === 4) {
            $eligibleProductIds = $voucher->products()
                ->where('products.deleted', false)
                ->pluck('products.id')
                ->toArray();

            return (float) collect($cart)
                ->filter(fn($item) => in_array($item['product_id'] ?? null, $eligibleProductIds, true))
                ->sum(fn($item) => ($item['sell_price'] ?? 0) * ($item['quantity'] ?? 0));
        }

        // Scope 5: Brand Tertentu
        if ((int) $voucher->scope === 5) {
            $brandIds = $voucher->brands()
                ->where('brands.deleted', false)
                ->pluck('brands.id')
                ->toArray();

            $eligibleProductIds = Product::whereIn('brand_id', $brandIds)
                ->where('deleted', false)
                ->pluck('id')
                ->toArray();

            return (float) collect($cart)
                ->filter(fn($item) => in_array($item['product_id'] ?? null, $eligibleProductIds, true))
                ->sum(fn($item) => ($item['sell_price'] ?? 0) * ($item['quantity'] ?? 0));
        }

        // Scope 6: Brand & Artikel Tertentu
        if ((int) $voucher->scope === 6) {
            $directProductIds = $voucher->products()
                ->where('products.deleted', false)
                ->pluck('products.id')
                ->toArray();

            $brandIds = $voucher->brands()
                ->where('brands.deleted', false)
                ->pluck('brands.id')
                ->toArray();

            $brandProductIds = Product::whereIn('brand_id', $brandIds)
                ->where('deleted', false)
                ->pluck('id')
                ->toArray();

            $allEligible = array_values(array_unique(array_merge($directProductIds, $brandProductIds)));

            return (float) collect($cart)
                ->filter(fn($item) => in_array($item['product_id'] ?? null, $allEligible, true))
                ->sum(fn($item) => ($item['sell_price'] ?? 0) * ($item['quantity'] ?? 0));
        }

        return (float) collect($cart)->sum(fn($item) => ($item['sell_price'] ?? 0) * ($item['quantity'] ?? 0));
    }

    private function calculateVoucherDiscountValue(Voucher $voucher, float $eligibleSubtotal, float $shippingCost): float
    {
        $voucherValue = (float) $voucher->value;

        if ($voucher->type == 1) {
            $maxDiscount = ($voucher->max_discount !== null && (float) $voucher->max_discount > 0) ? (float) $voucher->max_discount : PHP_FLOAT_MAX;
            return (float) min(($eligibleSubtotal * $voucherValue / 100), $maxDiscount);
        }

        if ($voucher->type == 2) {
            return (float) min($voucherValue, $eligibleSubtotal);
        }

        if ($voucher->type == 3) {
            return (float) min($voucherValue, $shippingCost);
        }

        if ($voucher->type == 4) {
            return 0.0; // Bonus produk ditangani terpisah
        }

        return 0.0;
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
        $itemTotal = ($item['sell_price'] ?? 0) * ($item['quantity'] ?? 0);
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

    public function calculateCartWeightAndDimensions(array $cart = []): array
    {
        if (empty($cart)) {
            $buffer = $this->getCurrentBuffer();
            $cart = $buffer ? $this->getBufferCartArray($buffer) : [];
        }

        $totalActualWeight = 0.0;
        $totalVolumetricWeight = 0.0;
        $totalFixedShippingCost = 0.0;
        $hasAnyDimensionOrWeight = false;
        $hasFixedShippingItems = false;
        $hasDimensionItems = false;

        foreach ($cart as $item) {
            $quantity = max(1, (int) ($item['quantity'] ?? 1));
            $isBundle = ($item['type'] ?? null) === 'bundle';

            if ($isBundle && !empty($item['bundle_data']['bundle_id'])) {
                $bundleModel = \App\Models\Frontend\ProductsCatalog\ProductBundling::with(['items.variant', 'items.product'])->find($item['bundle_data']['bundle_id']);
                if ($bundleModel && $bundleModel->items->isNotEmpty()) {
                    foreach ($bundleModel->items as $bItem) {
                        $bQty = max(1, (int) ($bItem->quantity ?? 1)) * $quantity;
                        $v = $bItem->variant;
                        $p = $bItem->product;

                        $hasDimensionItems = true;
                        $bLen = (float) ($v?->package_length ?: ($v?->length ?? $p?->length ?? ($v?->attributes['length'] ?? 0)));
                        $bWid = (float) ($v?->package_width ?: ($v?->width ?? $p?->width ?? ($v?->attributes['width'] ?? 0)));
                        $bHei = (float) ($v?->package_height ?: ($v?->height ?? $p?->height ?? ($v?->attributes['height'] ?? 0)));
                        $bWei = (float) ($v?->package_weight ?: ($v?->weight ?? $p?->weight ?? ($v?->attributes['weight'] ?? 0)));

                        if ($bWei > 0 || ($bLen > 0 && $bWid > 0 && $bHei > 0)) {
                            $hasAnyDimensionOrWeight = true;
                        }

                        $totalActualWeight += ($bWei * $bQty);
                        if ($bLen > 0 && $bWid > 0 && $bHei > 0) {
                            $totalVolumetricWeight += (($bLen * $bWid * $bHei) / 6000) * $bQty;
                        }
                    }
                    continue;
                }
            }

            $variantId = $item['variant_id'] ?? (($item['id'] ?? null) !== ($item['product_id'] ?? null) ? ($item['id'] ?? null) : null);
            $variantModel = $variantId ? \App\Models\Frontend\ProductsCatalog\ProductVariant::find($variantId) : null;
            $productModel = !empty($item['product_id']) ? \App\Models\Frontend\ProductsCatalog\Product::with('category')->find($item['product_id']) : null;
            if (!$productModel && $variantModel) {
                $productModel = $variantModel->product;
                if ($productModel) {
                    $productModel->loadMissing('category');
                }
            }

            $hasDimensionItems = true;
            $length = 0.0;
                $width = 0.0;
                $height = 0.0;
                $weight = 0.0;

                if ($variantModel) {
                    $length = (float) ($variantModel->package_length ?: ($variantModel->length ?? $productModel->length ?? ($variantModel->attributes['length'] ?? 0)));
                    $width = (float) ($variantModel->package_width ?: ($variantModel->width ?? $productModel->width ?? ($variantModel->attributes['width'] ?? 0)));
                    $height = (float) ($variantModel->package_height ?: ($variantModel->height ?? $productModel->height ?? ($variantModel->attributes['height'] ?? 0)));
                    $weight = (float) ($variantModel->package_weight ?: ($variantModel->weight ?? $productModel->weight ?? ($variantModel->attributes['weight'] ?? 0)));
                } elseif ($productModel) {
                    $length = (float) ($productModel->length ?? 0);
                    $width = (float) ($productModel->width ?? 0);
                    $height = (float) ($productModel->height ?? 0);
                    $weight = (float) ($productModel->weight ?? 0);
                }

                if (isset($item['dimensions']) && is_array($item['dimensions'])) {
                    $length = (float) ($item['dimensions']['length'] ?? $length);
                    $width = (float) ($item['dimensions']['width'] ?? $width);
                    $height = (float) ($item['dimensions']['height'] ?? $height);
                    $weight = (float) ($item['dimensions']['weight'] ?? $weight);
                } else {
                    if (isset($item['length']) && $item['length'] !== null && $item['length'] !== '') $length = (float) $item['length'];
                    if (isset($item['width']) && $item['width'] !== null && $item['width'] !== '') $width = (float) $item['width'];
                    if (isset($item['height']) && $item['height'] !== null && $item['height'] !== '') $height = (float) $item['height'];
                    if (isset($item['weight']) && $item['weight'] !== null && $item['weight'] !== '') $weight = (float) $item['weight'];
                }

                if ($weight > 0 || ($length > 0 && $width > 0 && $height > 0)) {
                    $hasAnyDimensionOrWeight = true;
                }

                $totalActualWeight += ($weight * $quantity);
                if ($length > 0 && $width > 0 && $height > 0) {
                    $totalVolumetricWeight += (($length * $width * $height) / 6000) * $quantity;
                }
        }

        $chargeableWeight = max($totalActualWeight, $totalVolumetricWeight);

        return [
            'is_calculable' => $hasAnyDimensionOrWeight && $chargeableWeight > 0,
            'has_fixed_items' => $hasFixedShippingItems,
            'has_dimension_items' => $hasDimensionItems,
            'fixed_shipping_cost' => round($totalFixedShippingCost, 2),
            'actual_weight' => round($totalActualWeight, 2),
            'volumetric_weight' => round($totalVolumetricWeight, 2),
            'chargeable_weight' => round($chargeableWeight, 2),
        ];
    }

    public function calculateShippingDetails(string $courier, string $subDistrictId = '', array $cart = []): array
    {
        if (empty($cart)) {
            $buffer = $this->getCurrentBuffer();
            $cart = $buffer ? $this->getBufferCartArray($buffer) : [];
        }

        $courierModel = Courier::whereRaw('LOWER(code) = ?', [strtolower($courier)])->first();
        if (!$courierModel) {
            return [
                'shipping_cost' => 0,
                'base_price' => 0,
                'is_available' => false,
                'is_calculated' => false,
                'billable_weight' => 0,
                'chargeable_weight' => 0,
                'actual_weight' => 0,
                'volumetric_weight' => 0,
                'has_fixed_items' => false,
                'has_dimension_items' => false,
                'message' => 'Kurir tidak ditemukan',
            ];
        }

        $weightDetails = $this->calculateCartWeightAndDimensions($cart);
        $totalChargeableWeight = $weightDetails['chargeable_weight'];
        $isCalculable = $weightDetails['is_calculable'];
        $fixedShippingCost = (int) ($weightDetails['fixed_shipping_cost'] ?? 0);
        $hasDimensionItems = $weightDetails['has_dimension_items'] ?? false;
        $hasFixedItems = $weightDetails['has_fixed_items'] ?? false;

        $destCityId = null;
        $destPostalCode = null;
        $destProvinceName = null;
        $destCityName = null;
        if (!empty($subDistrictId)) {
            $destSubDistrict = SubDistrict::withoutGlobalScopes()->with('city.province')->find($subDistrictId)
                ?? SubDistrict::with('city.province')->find($subDistrictId);
            if ($destSubDistrict) {
                $destCityId = $destSubDistrict->city_id;
                $destPostalCode = $destSubDistrict->postal_code;
                $destProvinceName = $destSubDistrict->city->province->name ?? ($destSubDistrict->city->province ?? ($destSubDistrict->province ?? null));
                $destCityName = $destSubDistrict->city->name ?? null;
            }
        }

        $estimatedDuration = \App\Services\EtaService::estimateDuration(
            $courierModel->courier_type,
            $courierModel->code,
            $destProvinceName,
            $destCityName
        );

        // Logic for Kurir Toko (Scope Wilayah Kota & Hybrid Ongkir Model A+B)
        if ($courierModel->courier_type === 'toko') {
            $shipping = null;
            if (!empty($subDistrictId)) {
                $shipping = ShippingAddress::where('courier_id', $courierModel->id)
                    ->where(function ($q) use ($subDistrictId, $destCityId) {
                        $q->where('sub_district_id', $subDistrictId);
                        if ($destCityId) {
                            $q->orWhere('city_id', $destCityId);
                        }
                    })
                    ->orderByRaw('sub_district_id IS NOT NULL DESC')
                    ->first();

                // If destination is not in Kurir Toko's configured coverage, it is OUT OF RANGE
                if (!$shipping) {
                    return [
                        'shipping_cost' => 0,
                        'base_price' => 0,
                        'additional_price_per_kg' => 0,
                        'fixed_shipping_cost' => $fixedShippingCost,
                        'expedition_cost' => 0,
                        'is_available' => false,
                        'is_calculated' => false,
                        'billable_weight' => 0,
                        'chargeable_weight' => $totalChargeableWeight,
                        'actual_weight' => $weightDetails['actual_weight'],
                        'volumetric_weight' => $weightDetails['volumetric_weight'],
                        'has_fixed_items' => $hasFixedItems,
                        'has_dimension_items' => $hasDimensionItems,
                        'service_name' => 'Kurir Toko',
                        'duration' => null,
                        'source' => 'internal',
                        'courier_type' => 'toko',
                        'message' => 'Kurir Toko belum melayani pengiriman ke kota / wilayah tujuan ini.',
                    ];
                }
            } else {
                // If subDistrictId is empty (initial view before entering address)
                $shipping = ShippingAddress::where('courier_id', $courierModel->id)->first();
                if (!$shipping) {
                    return [
                        'shipping_cost' => 0,
                        'base_price' => 0,
                        'additional_price_per_kg' => 0,
                        'fixed_shipping_cost' => $fixedShippingCost,
                        'expedition_cost' => 0,
                        'is_available' => false,
                        'is_calculated' => false,
                        'billable_weight' => 0,
                        'chargeable_weight' => $totalChargeableWeight,
                        'actual_weight' => $weightDetails['actual_weight'],
                        'volumetric_weight' => $weightDetails['volumetric_weight'],
                        'has_fixed_items' => $hasFixedItems,
                        'has_dimension_items' => $hasDimensionItems,
                        'service_name' => 'Kurir Toko',
                        'duration' => null,
                        'source' => 'internal',
                        'courier_type' => 'toko',
                        'message' => 'Kurir Toko belum diatur jangkauan wilayahnya.',
                    ];
                }
            }

            $basePrice = (int) $shipping->price;
            $additionalPricePerKg = (int) ($shipping->additional_price_per_kg ?? 0);

            $dimensionCost = 0;
            $billableWeight = 0;

            if ($hasDimensionItems) {
                $billableWeight = max(1, (int) ceil($totalChargeableWeight));
                if ($totalChargeableWeight > 1 && $additionalPricePerKg > 0) {
                    $extraKg = (int) ceil($totalChargeableWeight - 1);
                    $dimensionCost = $basePrice + ($extraKg * $additionalPricePerKg);
                } else {
                    $dimensionCost = $basePrice;
                }
            } elseif (!$hasFixedItems) {
                $dimensionCost = $basePrice;
                $billableWeight = 1;
            }

            $totalShippingCost = $fixedShippingCost + $dimensionCost;
            $etaToko = \App\Services\EtaService::calculateEta($estimatedDuration);

            return [
                'shipping_cost' => $totalShippingCost,
                'base_price' => $basePrice,
                'additional_price_per_kg' => $additionalPricePerKg,
                'fixed_shipping_cost' => $fixedShippingCost,
                'expedition_cost' => $dimensionCost,
                'is_available' => true,
                'is_calculated' => true,
                'billable_weight' => $billableWeight,
                'chargeable_weight' => $totalChargeableWeight,
                'actual_weight' => $weightDetails['actual_weight'],
                'volumetric_weight' => $weightDetails['volumetric_weight'],
                'has_fixed_items' => $hasFixedItems,
                'has_dimension_items' => $hasDimensionItems,
                'service_name' => 'Kurir Toko',
                'duration' => '1-2 Hari',
                'eta_label' => $etaToko['formatted_label'],
                'eta_dates' => $etaToko,
                'source' => 'internal',
                'eta_source' => 'store',
                'courier_type' => 'toko',
            ];
        }

        // Logic for Kurir Ekspedisi
        $shipping = null;
        if (!empty($subDistrictId)) {
            $shipping = ShippingAddress::where('courier_id', $courierModel->id)
                ->where(function ($q) use ($subDistrictId, $destCityId) {
                    $q->where('sub_district_id', $subDistrictId);
                    if ($destCityId) {
                        $q->orWhere('city_id', $destCityId);
                    }
                })
                ->orderByRaw('sub_district_id IS NOT NULL DESC')
                ->first();

            // Jika alamat kelurahan/kecamatan tidak ditemukan di shipping_addresses
            if (!$shipping) {
                return [
                    'shipping_cost' => 0,
                    'base_price' => 0,
                    'fixed_shipping_cost' => $fixedShippingCost,
                    'expedition_cost' => 0,
                    'is_available' => false,
                    'is_calculated' => false,
                    'billable_weight' => 0,
                    'chargeable_weight' => $totalChargeableWeight,
                    'actual_weight' => $weightDetails['actual_weight'],
                    'volumetric_weight' => $weightDetails['volumetric_weight'],
                    'has_fixed_items' => $hasFixedItems,
                    'has_dimension_items' => $hasDimensionItems,
                    'service_name' => $courierModel->name,
                    'service_code' => 'reg',
                    'duration' => null,
                    'source' => 'internal',
                    'courier_type' => 'expedisi',
                    'message' => 'Ekspedisi ' . $courierModel->name . ' belum melayani pengiriman ke kelurahan / wilayah tujuan ini.',
                ];
            }
        } else {
            // Ketika belum memilih kelurahan/kecamatan (tampilan awal checkout)
            $shipping = ShippingAddress::where('courier_id', $courierModel->id)->first();
            if (!$shipping) {
                return [
                    'shipping_cost' => 0,
                    'base_price' => 0,
                    'fixed_shipping_cost' => $fixedShippingCost,
                    'expedition_cost' => 0,
                    'is_available' => false,
                    'is_calculated' => false,
                    'billable_weight' => 0,
                    'chargeable_weight' => $totalChargeableWeight,
                    'actual_weight' => $weightDetails['actual_weight'],
                    'volumetric_weight' => $weightDetails['volumetric_weight'],
                    'has_fixed_items' => $hasFixedItems,
                    'has_dimension_items' => $hasDimensionItems,
                    'service_name' => $courierModel->name,
                    'service_code' => 'reg',
                    'duration' => null,
                    'source' => 'internal',
                    'courier_type' => 'expedisi',
                    'message' => 'Tarif ekspedisi ' . $courierModel->name . ' belum dikonfigurasi.',
                ];
            }
        }

        $basePrice = $shipping ? (int) $shipping->price : 25000;

        $expeditionCost = 0;
        $billableWeight = 0;
        $shippingServiceName = null;
        $shippingEtd = null;
        $shippingSource = 'internal';

        $biteshipService = app(BiteshipService::class);
        $biteshipRate = null;
        if ($biteshipService->isConfigured() && !empty($destPostalCode)) {
            $biteshipRate = $biteshipService->getBestRateForCourier($courierModel->code, $cart, (string) $destPostalCode);
        }

        $shippingServiceCode = null;
        if ($biteshipRate && isset($biteshipRate['price'])) {
            $expeditionCost = (int) $biteshipRate['price'];
            $shippingServiceName = $biteshipRate['service_name'] ?? null;
            $shippingServiceCode = $biteshipRate['service_code'] ?? ($biteshipRate['type'] ?? 'reg');
            $shippingEtd = $biteshipRate['duration'] ?? null;
            $shippingSource = 'biteship';
        } elseif ($hasDimensionItems) {
            if ($isCalculable && $totalChargeableWeight > 0) {
                $billableWeight = max(1, (int) ceil($totalChargeableWeight));
                $expeditionCost = (int) ($basePrice * $billableWeight);
            } else {
                $expeditionCost = $basePrice;
            }
        } elseif (!$hasFixedItems) {
            $expeditionCost = $basePrice;
        }

        $totalShippingCost = $fixedShippingCost + $expeditionCost;
        $etaExpedisi = \App\Services\EtaService::calculateEta($shippingEtd ?: $estimatedDuration);

        return [
            'shipping_cost' => $totalShippingCost,
            'base_price' => $basePrice,
            'fixed_shipping_cost' => $fixedShippingCost,
            'expedition_cost' => $expeditionCost,
            'is_available' => true,
            'is_calculated' => ($isCalculable && $totalChargeableWeight > 0) || $hasFixedItems || $biteshipRate !== null,
            'billable_weight' => $billableWeight,
            'chargeable_weight' => $totalChargeableWeight,
            'actual_weight' => $weightDetails['actual_weight'],
            'volumetric_weight' => $weightDetails['volumetric_weight'],
            'has_dimension_items' => $hasDimensionItems,
            'service_name' => $shippingServiceName ?: 'Reguler',
            'service_code' => $shippingServiceCode ?: 'reg',
            'duration' => $shippingEtd ?: $etaExpedisi['duration'],
            'eta_label' => $etaExpedisi['formatted_label'],
            'eta_dates' => $etaExpedisi,
            'source' => $shippingSource,
            'eta_source' => $biteshipRate ? 'biteship' : 'manual',
            'courier_type' => 'expedisi',
        ];
    }

    private function getShippingCost(string $courier, string $subDistrictId, array $cart = []): int
    {
        $details = $this->calculateShippingDetails($courier, $subDistrictId, $cart);
        return (int) ($details['shipping_cost'] ?? 0);
    }

    public function getCitiesAjax(Request $request): \Illuminate\Http\JsonResponse
    {
        $provinceId = (string) $request->query('province_id', '');
        if (!$provinceId) {
            return response()->json([]);
        }
        $cities = \App\Models\Frontend\Location\City::where('province_id', $provinceId)
            ->orderBy('name')
            ->get(['id', 'name']);
        return response()->json($cities);
    }

    public function getSubDistrictsAjax(Request $request): \Illuminate\Http\JsonResponse
    {
        $cityId = (string) $request->query('city_id', '');
        if (!$cityId) {
            return response()->json([]);
        }
        $subDistricts = \App\Models\Frontend\Location\SubDistrict::where('city_id', $cityId)
            ->orderBy('sub_district')
            ->get(['id', 'district', 'sub_district', 'postal_code'])
            ->map(fn($sd) => [
                'id' => $sd->id,
                'label' => $sd->sub_district . ($sd->district ? ' (Kec. ' . $sd->district . ')' : '') . ($sd->postal_code ? ' - ' . $sd->postal_code : ''),
                'district' => $sd->district,
                'sub_district' => $sd->sub_district,
                'postal_code' => $sd->postal_code,
            ]);
        return response()->json($subDistricts);
    }

    public function calculateShippingCostAjax(Request $request): \Illuminate\Http\JsonResponse
    {
        $courier = (string) $request->query('courier', '');
        $subDistrictId = (string) $request->query('sub_district_id', '');

        $buffer = $this->getCurrentBuffer();
        $cart = $buffer ? $this->getBufferCartArray($buffer) : [];

        if ($courier === 'all' || empty($courier)) {
            $couriers = Courier::all();
            $results = [];
            foreach ($couriers as $c) {
                $results[$c->code] = $this->calculateShippingDetails($c->code, $subDistrictId, $cart);
            }
            return response()->json([
                'success' => true,
                'data' => $results,
                'is_bulk' => true,
            ]);
        }

        $details = $this->calculateShippingDetails($courier, $subDistrictId, $cart);

        return response()->json([
            'success' => true,
            'data' => $details,
            'is_bulk' => false,
        ]);
    }

    public function syncVouchers(Request $request): \Illuminate\Http\JsonResponse
    {
        $codes = (array) $request->input('voucher_codes', []);
        $codes = array_values(array_unique(array_filter(array_map('strtoupper', $codes))));
        Session::put('selected_voucher_codes', $codes);

        $buffer = $this->getCurrentBuffer();
        $cart = $buffer ? $this->getBufferCartArray($buffer) : [];
        $cartTotal = (float) collect($cart)->sum(fn($item) => ($item['sell_price'] ?? 0) * ($item['quantity'] ?? 0));

        $shippingCost = (float) $request->input('shipping_cost', 0);
        $voucherCalc = $this->calculateVoucherDiscount($codes, $cart, $cartTotal, $shippingCost);
        $selectedVoucher = [
            'code' => $voucherCalc['primary']?->code ?? ($codes[0] ?? ''),
            'discount' => (float) ($voucherCalc['discount'] ?? 0),
            'product_discount' => (float) ($voucherCalc['product_voucher_discount'] ?? 0),
            'shipping_discount' => (float) ($voucherCalc['shipping_voucher_discount'] ?? 0),
        ];
        Session::put('selected_voucher', $selectedVoucher);

        if ($buffer) {
            $meta = $buffer->meta ?? [];
            $meta['voucher_codes'] = $codes;
            $meta['voucher_code'] = implode(',', $codes);
            $meta['voucher_discount'] = $selectedVoucher['discount'];
            $meta['product_voucher_discount'] = $selectedVoucher['product_discount'];
            $meta['shipping_voucher_discount'] = $selectedVoucher['shipping_discount'];
            $buffer->update(['meta' => $meta]);
        }

        return response()->json([
            'success' => true,
            'codes' => $codes,
            'selected_voucher' => $selectedVoucher,
        ]);
    }

    /**
     * Rollback order items, inventory and voucher usage if payment gateway call fails
     */
    private function rollbackFailedOrder(Order $order): void
    {
        try {
            $order->loadMissing('items');
            foreach ($order->items as $item) {
                if ($item->product_variant_id) {
                    \App\Services\InventoryService::rollbackWebOrder($item->product_variant_id, (int)$item->quantity);
                }
            }
            $order->items()->delete();
            \App\Models\Frontend\Promo\VoucherUsage::where('order_id', $order->id)->delete();
            $order->delete();
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Error rolling back failed order: ' . $e->getMessage());
        }
    }

    /**
     * Get order from ID or order number
     */
    private function getOrderFromIdentifier($identifier): ?Order
    {
        if (empty($identifier)) {
            return null;
        }

        $query = Order::with(['customer', 'courier', 'items.product', 'voucher']);
        
        if (\Illuminate\Support\Str::isUuid($identifier)) {
            $order = (clone $query)->where('id', $identifier)->first();
            if ($order) {
                return $order;
            }

            // Also check if this identifier matches buffer_id stored in meta
            $orderByBuffer = (clone $query)->where('meta->buffer_id', $identifier)->latest('created_at')->first();
            if ($orderByBuffer) {
                return $orderByBuffer;
            }
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
                'image' => $item->product?->thumbnail_url ?? '',
                'sell_price' => (float) $item->unit_price,
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
                'name' => $order->meta['shipping_address']['recipient_name'] ?? $order->customer?->name ?? '',
                'email' => $order->customer?->email ?? '',
                'phone' => $order->meta['shipping_address']['phone'] ?? $order->customer?->phone ?? '',
                'user_id' => $order->customer?->user_id,
            ],
            'shipping_address' => $order->meta['shipping_address'] ?? null,
            'courier' => $order->courier?->code ?? '',
            'courier_id' => $order->courier_id,
            'shipping_cost' => (float) $order->shipping_cost,
            'eta_label' => $order->meta['shipping_eta_label'] ?? ($order->meta['eta_label'] ?? null),
            'shipping_duration' => $order->meta['shipping_duration'] ?? null,
            'shipping_eta_source' => $order->meta['shipping_eta_source'] ?? null,
            'subtotal' => (float) $order->subtotal,
            'original_cart_total' => (float) ($order->meta['original_cart_total'] ?? $order->subtotal),
            'promo_discount' => (float) ($order->meta['promo_discount'] ?? 0),
            'product_voucher_discount' => (float) ($order->meta['product_voucher_discount'] ?? 0),
            'shipping_voucher_discount' => (float) ($order->meta['shipping_voucher_discount'] ?? 0),
            'applied_vouchers' => $order->meta['applied_vouchers'] ?? [],
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
            'created_at' => $order->created_at ? $order->created_at->toIso8601String() : now()->toIso8601String(),
        ];
    }

    private function resolveCustomerAddressData(Customer $customer): ?array
    {
        $address = null;
        if (!empty($customer->user_id)) {
            $address = Address::where('user_id', $customer->user_id)
                ->where('deleted', false)
                ->orderBy('is_primary', 'desc')
                ->first();
        }
        if (!$address) {
            $address = Address::where('customer_id', $customer->id)
                ->where('deleted', false)
                ->orderBy('is_primary', 'desc')
                ->first();
        }

        $addressText = '';
        $subDistrictId = null;
        $cityId = null;
        $provinceId = null;
        $postalCode = '';
        $cityName = '';
        $provinceName = '';
        $subDistrictName = '';

        if ($address) {
            $addressText = $address->address ?? '';
            $subDistrictId = $address->sub_district_id ?? null;
            $cityId = $address->city_id ?? null;
            $postalCode = $address->postal_code ?? '';
        } else {
            $order = Order::where('customer_id', $customer->id)->whereNotNull('meta')->latest()->first();
            if ($order) {
                $shippingData = $order->meta['shipping_address'] ?? null;
                $custData = $order->meta['customer'] ?? null;
                $addressText = $shippingData['address'] ?? ($custData['address'] ?? '');
                $subDistrictId = $shippingData['sub_district_id'] ?? ($custData['sub_district_id'] ?? null);
                $cityId = $shippingData['city_id'] ?? ($custData['city_id'] ?? null);
                $provinceId = $shippingData['province_id'] ?? ($custData['province_id'] ?? null);
                $postalCode = $shippingData['postal_code'] ?? ($custData['postal_code'] ?? '');
                $cityName = $shippingData['city'] ?? '';
                $provinceName = $shippingData['province'] ?? '';
                $subDistrictName = $shippingData['sub_district'] ?? '';
            }
        }

        $sd = null;
        if ($subDistrictId) {
            $sd = SubDistrict::withoutGlobalScopes()->find($subDistrictId);
        }

        if ($sd) {
            $subDistrictId = $sd->id;
            $subDistrictName = $sd->sub_district;
            $postalCode = $postalCode ?: ($sd->postal_code ?? '');
            if (!$cityId) {
                $cityId = $sd->city_id;
            }
            if (!$provinceId) {
                $provinceId = $sd->province_id ?? ($sd->city?->province_id ?? null);
            }
            if (empty($provinceName) && is_string($sd->province)) {
                $provinceName = $sd->province;
            }
        }

        if ($cityId) {
            $city = \App\Models\Frontend\Location\City::find($cityId);
            if ($city) {
                $cityId = $city->id;
                if (empty($cityName)) {
                    $cityName = $city->name;
                }
                if (!$provinceId) {
                    $provinceId = $city->province_id;
                }
                if (empty($provinceName) && is_string($city->province)) {
                    $provinceName = $city->province;
                }
            }

            if (!$sd) {
                $fallbackSd = null;
                if (!empty($postalCode)) {
                    $fallbackSd = SubDistrict::where('city_id', $cityId)->where('postal_code', $postalCode)->first();
                }
                if (!$fallbackSd) {
                    $fallbackSd = SubDistrict::where('city_id', $cityId)->first();
                }
                if ($fallbackSd) {
                    $subDistrictId = $fallbackSd->id;
                    $subDistrictName = $fallbackSd->sub_district;
                    $postalCode = $postalCode ?: ($fallbackSd->postal_code ?? '');
                    if (!$provinceId) {
                        $provinceId = $fallbackSd->province_id;
                    }
                    if (empty($provinceName) && is_string($fallbackSd->province)) {
                        $provinceName = $fallbackSd->province;
                    }
                }
            }
        }

        if ($provinceId && empty($provinceName)) {
            $prov = \App\Models\Frontend\Location\Province::find($provinceId);
            if ($prov) {
                $provinceName = $prov->name;
            }
        }

        if (!$addressText && !$subDistrictId && !$cityId && !$provinceId) {
            return null;
        }

        return [
            'address' => $addressText,
            'province_id' => $provinceId,
            'city_id' => $cityId,
            'sub_district_id' => $subDistrictId,
            'province_name' => $provinceName,
            'city_name' => $cityName,
            'sub_district_name' => $subDistrictName,
            'postal_code' => $postalCode,
        ];
    }

    public function searchUser(Request $request)
    {
        $term = trim((string)$request->input('term'));
        if (strlen($term) < 4) {
            return response()->json([]);
        }

        if (str_contains($term, '@')) {
            $customers = Customer::whereRaw('LOWER(email) = ?', [strtolower($term)])
                ->orWhere('email', 'ilike', $term . '%')
                ->take(5)
                ->get();
        } else {
            $customers = Customer::where('phone', 'ilike', '%' . $term . '%')
                ->orWhere('name', 'ilike', '%' . $term . '%')
                ->take(5)
                ->get();
        }

        $results = [];
        foreach ($customers as $c) {
            $addr = $this->resolveCustomerAddressData($c);
                
            $results[] = [
                'name' => $c->name,
                'email' => $c->email,
                'phone' => $c->phone,
                'address' => $addr['address'] ?? '',
                'province_id' => $addr['province_id'] ?? null,
                'city_id' => $addr['city_id'] ?? null,
                'sub_district_id' => $addr['sub_district_id'] ?? null,
                'province_name' => $addr['province_name'] ?? '',
                'city_name' => $addr['city_name'] ?? '',
                'sub_district_name' => $addr['sub_district_name'] ?? '',
                'postal_code' => $addr['postal_code'] ?? '',
            ];
        }

        return response()->json($results);
    }

    public function checkUser(Request $request)
    {
        $email = $request->input('email');
        $phone = $request->input('phone');

        $customer = null;
        if ($email) {
            $customer = Customer::whereRaw('LOWER(email) = ?', [strtolower(trim($email))])->first();
        } elseif ($phone) {
            $cleanPhone = preg_replace('/[^0-9]/', '', $phone);
            if (str_starts_with($cleanPhone, '0')) {
                $cleanPhone = '62' . substr($cleanPhone, 1);
            }
            $customer = Customer::where(function ($q) use ($phone, $cleanPhone) {
                $q->where('phone', $phone)
                  ->orWhere('phone', 'like', '%' . $phone)
                  ->orWhereRaw("REPLACE(REPLACE(REPLACE(phone, '-', ''), ' ', ''), '+', '') = ?", [$cleanPhone]);
            })->first();
        }

        if ($customer) {
            $addr = $this->resolveCustomerAddressData($customer);

            return response()->json([
                'registered' => true,
                'customer' => [
                    'name' => $customer->name,
                    'phone' => $customer->phone,
                    'email' => $customer->email,
                ],
                'address' => $addr,
            ]);
        }

        return response()->json(['registered' => false]);
    }

    public function trackOrder()
    {
        return view('frontend.track-order');
    }

    public function processTrackOrder(Request $request)
    {
        $request->validate([
            'order_number' => 'required|string',
            'contact' => 'required|string', // Bisa email atau nomor HP untuk security
        ]);

        $orderNumber = trim((string) $request->input('order_number'));
        $contact = trim((string) $request->input('contact'));

        // Cari order (case-insensitive untuk order_number maupun id UUID)
        $order = \App\Models\Frontend\Order::with(['customer', 'items'])
            ->where(function ($q) use ($orderNumber) {
                $q->whereRaw('LOWER(order_number) = ?', [strtolower($orderNumber)])
                  ->orWhereRaw('LOWER(id::text) = ?', [strtolower($orderNumber)]);
            })
            ->first();

        if (!$order) {
            return back()->withInput()->with('error', 'Pesanan tidak ditemukan. Pastikan Nomor Pesanan benar.');
        }

        // Verifikasi contact (email atau phone) secara case-insensitive
        $customer = $order->customer;
        $customerData = $order->meta['customer'] ?? null;
        $shippingAddressData = $order->meta['shipping_address'] ?? null;

        // 1. Email matching: Case-insensitive & trimmed
        $inputContactLower = strtolower($contact);
        $orderEmails = array_filter([
            $customer?->email,
            $customerData['email'] ?? null,
            $shippingAddressData['email'] ?? null,
        ]);

        $validEmail = false;
        foreach ($orderEmails as $orderEmail) {
            if (strtolower(trim((string) $orderEmail)) === $inputContactLower) {
                $validEmail = true;
                break;
            }
        }

        // 2. Phone matching: Normalized digits comparison
        $inputPhoneDigits = preg_replace('/[^0-9]/', '', $contact);
        $orderPhones = array_filter([
            $customer?->phone,
            $customerData['phone'] ?? null,
            $shippingAddressData['phone'] ?? null,
        ]);

        $validPhone = false;
        if (!empty($inputPhoneDigits)) {
            foreach ($orderPhones as $orderPhone) {
                $orderPhoneDigits = preg_replace('/[^0-9]/', '', (string) $orderPhone);
                if (!empty($orderPhoneDigits)) {
                    if (
                        $orderPhoneDigits === $inputPhoneDigits ||
                        str_ends_with($orderPhoneDigits, $inputPhoneDigits) ||
                        str_ends_with($inputPhoneDigits, $orderPhoneDigits)
                    ) {
                        $validPhone = true;
                        break;
                    }
                }
            }
        }

        // Exact string fallback matching for contact
        if (!$validPhone) {
            foreach ($orderPhones as $orderPhone) {
                if (trim((string) $orderPhone) === $contact) {
                    $validPhone = true;
                    break;
                }
            }
        }

        if (!$validEmail && !$validPhone) {
            return back()->withInput()->with('error', 'Email atau Nomor HP tidak cocok dengan data pesanan.');
        }

        // Jika cocok, redirect ke tracking detail page
        return redirect()->route('track-order.detail', ['order_id' => $order->id]);
    }

    public function trackOrderDetail(string $orderId)
    {
        $order = \App\Models\Frontend\Order::with(['customer', 'courier', 'items.product', 'voucher'])->findOrFail($orderId);

        $deliveryLogs = \Illuminate\Support\Facades\DB::table('delivery_logs')
            ->where(function ($q) use ($order) {
                $q->where('order_id', $order->id);
                if (!empty($order->order_number)) {
                    $q->orWhereRaw("payload->'metadata'->>'order_number' = ?", [$order->order_number]);
                }
            })
            ->orderBy('created_at', 'asc')
            ->get();

        $delivery = \Illuminate\Support\Facades\DB::table('deliveries')
            ->where('order_id', $order->id)
            ->first();

        $waybillId = $deliveryLogs->firstWhere('waybill_id', '!=', null)->waybill_id 
            ?? $delivery->tracking_number 
            ?? data_get($order->meta, 'waybill_id') 
            ?? data_get($order->meta, 'resi') 
            ?? null;

        $courierName = $order->courier->name 
            ?? $deliveryLogs->firstWhere('courier_code', '!=', null)->courier_code 
            ?? 'Kurir Pengiriman';

        $latestLog = $deliveryLogs->last();
        $latestPayload = $latestLog ? (is_string($latestLog->payload) ? json_decode($latestLog->payload, true) : (array) $latestLog->payload) : null;

        return view('frontend.track-order-detail', compact('order', 'deliveryLogs', 'delivery', 'waybillId', 'courierName', 'latestPayload'));
    }
}
