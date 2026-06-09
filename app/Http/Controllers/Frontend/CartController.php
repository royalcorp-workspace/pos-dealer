<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Services\ProductService;
use Illuminate\Http\Request;

class CartController extends Controller
{
    private ProductService $productService;

    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }

    public function add(Request $request)
    {
        $request->validate([
            'product_id' => 'required|string',
            'quantity' => 'required|integer|min:1',
            'size' => 'nullable|string',
        ]);

        $productId = $request->input('product_id');
        $quantity = (int) $request->input('quantity');
        $size = $request->input('size');

        $product = $this->productService->find($productId);
        if (!$product) {
            return response()->json(['error' => 'Product not found'], 404);
        }

        // Calculate price based on size
        $price = $product['price'];
        if ($product['isVariable'] && $size) {
            $sizes = [
                '200 x 090 cm',
                '200 x 100 cm',
                '200 x 120 cm',
                '200 x 160 cm',
                '200 x 180 cm',
                '200 x 200 cm'
            ];
            $basePrice = $product['minPrice'] ?? $product['price'];
            $sizeIndex = array_search($size, $sizes);
            if ($sizeIndex !== false) {
                $price = $basePrice + ($sizeIndex * 500000);
            }
        }

        $cart = session()->get('cart', []);
        $cartItemId = $productId . '-' . ($size ?: 'default');

        if (isset($cart[$cartItemId])) {
            $cart[$cartItemId]['quantity'] += $quantity;
        } else {
            $cart[$cartItemId] = [
                'id' => $cartItemId,
                'product_id' => $productId,
                'name' => $product['name'],
                'brand' => $product['brand'],
                'image' => $product['image'],
                'size' => $size,
                'price' => $price,
                'quantity' => $quantity,
            ];
        }

        session()->put('cart', $cart);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'cart' => $cart,
                'cart_count' => $this->getCartCount($cart),
                'cart_total' => $this->getCartTotal($cart),
            ]);
        }

        return redirect()->back()->with('success', 'Produk berhasil ditambahkan ke keranjang!');
    }

    public function update(Request $request, string $id)
    {
        $request->validate([
            'quantity' => 'required|integer',
        ]);

        $quantity = (int) $request->input('quantity');
        $cart = session()->get('cart', []);

        if (isset($cart[$id])) {
            if ($quantity < 1) {
                unset($cart[$id]);
            } else {
                $cart[$id]['quantity'] = $quantity;
            }
            session()->put('cart', $cart);
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'cart' => $cart,
                'cart_count' => $this->getCartCount($cart),
                'cart_total' => $this->getCartTotal($cart),
            ]);
        }

        return redirect()->back();
    }

    public function remove(Request $request, string $id)
    {
        $cart = session()->get('cart', []);

        if (isset($cart[$id])) {
            unset($cart[$id]);
            session()->put('cart', $cart);
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'cart' => $cart,
                'cart_count' => $this->getCartCount($cart),
                'cart_total' => $this->getCartTotal($cart),
            ]);
        }

        return redirect()->back();
    }

    private function getCartCount(array $cart): int
    {
        $count = 0;
        foreach ($cart as $item) {
            $count += $item['quantity'];
        }
        return $count;
    }

    private function getCartTotal(array $cart): float
    {
        $total = 0;
        foreach ($cart as $item) {
            $total += ($item['price'] * $item['quantity']);
        }
        return $total;
    }
}

