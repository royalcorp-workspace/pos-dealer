<?php

declare(strict_types=1);

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Frontend\Promo\Voucher;
use Illuminate\Http\Request;

class VoucherController extends Controller
{
    public function validate(Request $request)
    {
        $request->validate([
            'code' => 'required|string|max:20',
            'cart_total' => 'required|numeric|min:0',
            'product_ids' => 'nullable|array',
            'product_ids.*' => 'string',
            'category_ids' => 'nullable|array',
            'category_ids.*' => 'string',
        ]);

        $voucher = Voucher::active()->where('code', strtoupper($request->code))->first();

        if (!$voucher) {
            return response()->json([
                'valid' => false,
                'message' => 'Kode voucher tidak ditemukan atau sudah tidak berlaku.',
            ]);
        }

        $productIds = (array) $request->input('product_ids', []);
        $categoryIds = (array) $request->input('category_ids', []);
        if ((int) $voucher->scope === 2 && $voucher->products()->where('deleted', false)->pluck('products.id')->intersect($productIds)->isEmpty()) {
            return response()->json([
                'valid' => false,
                'message' => 'Voucher ini hanya berlaku untuk produk tertentu.',
            ]);
        }

        if ((int) $voucher->scope === 3 && $voucher->categories()->where('deleted', false)->pluck('product_category.id')->intersect($categoryIds)->isEmpty()) {
            return response()->json([
                'valid' => false,
                'message' => 'Voucher ini hanya berlaku untuk kategori tertentu.',
            ]);
        }

        $user = session()->get('user');
        $userId = $user['id'] ?? $user['sub'] ?? null;

        if ($request->cart_total < $voucher->min_purchase) {
            return response()->json([
                'valid' => false,
                'message' => 'Minimum pembelian Rp ' . number_format($voucher->min_purchase, 0, ',', '.') . ' untuk voucher ini.',
            ]);
        }

        if (!$voucher->canBeUsedBy($userId)) {
            return response()->json([
                'valid' => false,
                'message' => 'Voucher sudah mencapai batas pemakaian atau tidak tersedia untuk akun Anda.',
            ]);
        }

        $discount = 0;
        if ($voucher->type == 1) {
            $discount = min(
                ($request->cart_total * $voucher->value / 100),
                $voucher->max_discount ?? PHP_FLOAT_MAX
            );
        } elseif ($voucher->type == 2) {
            $discount = min($voucher->value, $request->cart_total);
        } elseif ($voucher->type == 3) {
            $discount = $voucher->value;
        }

        $typeLabel = match($voucher->type) {
            1 => 'Persentase',
            2 => 'Nominal',
            3 => 'Gratis Ongkir',
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
                'scope' => $voucher->scope,
                'scopeLabel' => $voucher->scopeLabel(),
                'allowStacking' => $voucher->isStackable(),
            ],
        ]);
    }
}