<?php

declare(strict_types=1);

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Frontend\Customer\Customer;
use App\Models\Frontend\ProductsCatalog\Product;
use App\Models\Frontend\Promo\Voucher;
use App\Models\Frontend\Promo\VoucherClaim;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VoucherController extends Controller
{
    public function validate(Request $request)
    {
        $request->validate([
            'code' => 'required|string|max:50',
            'cart_total' => 'required|numeric|min:0',
            'product_ids' => 'nullable|array',
            'product_ids.*' => 'string',
            'category_ids' => 'nullable|array',
            'category_ids.*' => 'string',
        ]);

        $voucher = Voucher::active()->whereRaw('LOWER(code) = ?', [strtolower(trim($request->code))])->first();

        if (!$voucher) {
            return response()->json([
                'valid' => false,
                'message' => 'Kode voucher tidak ditemukan atau sudah tidak berlaku.',
            ]);
        }

        $user = session()->get('user');
        $userId = $user['id'] ?? $user['sub'] ?? null;

        // Check if customer can use this voucher (validity, user limits, scope 2, scope 7, claimable, follow)
        if (!$voucher->canBeUsedBy($userId)) {
            if ((int) $voucher->scope === 2) {
                return response()->json([
                    'valid' => false,
                    'message' => 'Voucher ini hanya berlaku untuk pelanggan tertentu.',
                ]);
            }
            if ((int) $voucher->scope === 7) {
                return response()->json([
                    'valid' => false,
                    'message' => 'Voucher ini khusus untuk group customer tertentu (misal: Karyawan atau Reseller).',
                ]);
            }
            if (($voucher->visibility ?? 'public') === 'claimable') {
                return response()->json([
                    'valid' => false,
                    'message' => 'Voucher ini harus diklaim terlebih dahulu sebelum dapat digunakan.',
                ]);
            }
            if ($voucher->require_follow) {
                return response()->json([
                    'valid' => false,
                    'message' => 'Voucher ini hanya dapat digunakan oleh pengikut toko (Follow Store).',
                ]);
            }
            return response()->json([
                'valid' => false,
                'message' => 'Voucher sudah mencapai batas pemakaian atau tidak tersedia untuk akun Anda.',
            ]);
        }

        $productIds = (array) $request->input('product_ids', []);
        $categoryIds = (array) $request->input('category_ids', []);

        $cartSummary = \App\Models\Frontend\Buffer\Buffer::getActiveCartSummary();
        $cart = $cartSummary['cart'] ?? session()->get('cart', []);

        // Verify cart applicability
        if (!empty($cart)) {
            if (!$voucher->appliesToCart($cart, $userId)) {
                return response()->json([
                    'valid' => false,
                    'message' => 'Voucher ini tidak berlaku untuk produk di keranjang belanja Anda.',
                ]);
            }
            $eligibleSubtotal = $voucher->getEligibleSubtotal($cart);
            $eligibleProductIds = $voucher->getEligibleProductIds($cart);
        } else {
            // Fallback for direct API validation without cart buffer
            $eligibleSubtotal = (float) $request->cart_total;
            $eligibleProductIds = $productIds;
        }

        if (in_array((int) $voucher->scope, [3, 4, 5, 6], true) && $eligibleSubtotal <= 0) {
            return response()->json([
                'valid' => false,
                'message' => 'Produk di keranjang belanja tidak memenuhi syarat untuk voucher ini.',
            ]);
        }

        // Minimum purchase check against eligible subtotal
        if ($eligibleSubtotal < $voucher->min_purchase) {
            $kurang = (float) $voucher->min_purchase - $eligibleSubtotal;
            return response()->json([
                'valid' => false,
                'is_min_purchase' => true,
                'min_purchase' => (float) $voucher->min_purchase,
                'eligible_subtotal' => $eligibleSubtotal,
                'cart_total' => (float) $request->cart_total,
                'shortfall' => $kurang,
                'message' => 'Minimum pembelian Rp ' . number_format((float) $voucher->min_purchase, 0, ',', '.') . ' untuk produk promo ini (Kurang Rp ' . number_format($kurang, 0, ',', '.') . ').',
            ]);
        }

        // Calculate discount strictly against eligible subtotal
        $discount = $voucher->calculateDiscountValue($eligibleSubtotal, 0.0);

        $typeLabel = match((int) $voucher->type) {
            1 => 'Persentase',
            2 => 'Nominal',
            3 => 'Gratis Ongkir',
            4 => 'Bonus Produk',
            default => 'Tidak diketahui',
        };

        return response()->json([
            'valid' => true,
            'voucher' => [
                'code' => $voucher->code,
                'title' => $voucher->title,
                'type' => $typeLabel,
                'value' => $voucher->value,
                'discount' => $discount,
                'max_discount' => $voucher->max_discount,
                'scope' => $voucher->scope,
                'scopeLabel' => $voucher->scopeLabel(),
                'allow_stacking' => $voucher->isStackable(),
                'allowStacking' => $voucher->isStackable(),
                'eligible_subtotal' => $eligibleSubtotal,
                'eligible_products' => $eligibleProductIds,
                'products' => $voucher->products ? $voucher->products->pluck('name')->toArray() : [],
            ],
        ]);
    }

    public function claim(Request $request)
    {
        $request->validate([
            'voucher_id' => 'required|string',
        ]);

        $user = session()->get('user');
        $userId = $user['id'] ?? $user['sub'] ?? null;

        if (!$userId) {
            return response()->json([
                'success' => false,
                'message' => 'Silakan login terlebih dahulu untuk mengklaim voucher.',
            ], 401);
        }

        $voucher = Voucher::active()->findOrFail($request->voucher_id);

        $customer = Customer::where('user_id', $userId)
            ->orWhere('id', $userId)
            ->first();

        $customerId = $customer ? $customer->id : $userId;

        $existingClaim = VoucherClaim::where('voucher_id', $voucher->id)
            ->where('customer_id', $customerId)
            ->exists();

        if ($existingClaim) {
            return response()->json([
                'success' => false,
                'message' => 'Anda sudah mengklaim voucher ini.',
            ]);
        }

        VoucherClaim::create([
            'voucher_id' => $voucher->id,
            'customer_id' => $customerId,
            'claimed_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Voucher berhasil diklaim!',
        ]);
    }

    public function followStore(Request $request)
    {
        $request->validate([
            'store_id' => 'required|integer',
        ]);

        $user = session()->get('user');
        $userId = $user['id'] ?? $user['sub'] ?? null;

        if (!$userId) {
            return response()->json([
                'success' => false,
                'message' => 'Silakan login terlebih dahulu untuk mengikuti toko.',
            ], 401);
        }

        $customer = Customer::where('user_id', $userId)
            ->orWhere('id', $userId)
            ->first();

        $customerId = $customer ? $customer->id : $userId;

        DB::table('store_followers')->updateOrInsert(
            ['store_id' => $request->store_id, 'customer_id' => $customerId],
            ['created_at' => now(), 'updated_at' => now()]
        );

        return response()->json([
            'success' => true,
            'message' => 'Berhasil mengikuti toko.',
        ]);
    }

    public function unfollowStore(Request $request)
    {
        $request->validate([
            'store_id' => 'required|integer',
        ]);

        $user = session()->get('user');
        $userId = $user['id'] ?? $user['sub'] ?? null;

        if (!$userId) {
            return response()->json([
                'success' => false,
                'message' => 'Silakan login terlebih dahulu.',
            ], 401);
        }

        $customer = Customer::where('user_id', $userId)
            ->orWhere('id', $userId)
            ->first();

        $customerId = $customer ? $customer->id : $userId;

        DB::table('store_followers')
            ->where('store_id', $request->store_id)
            ->where('customer_id', $customerId)
            ->delete();

        return response()->json([
            'success' => true,
            'message' => 'Berhenti mengikuti toko.',
        ]);
    }
}
