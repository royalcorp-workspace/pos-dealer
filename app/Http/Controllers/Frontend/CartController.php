<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Frontend\ProductsCatalog\Product;
use App\Models\Frontend\ProductsCatalog\ProductVariant;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function toggleWishlist(Request $request)
    {
        $request->validate([
            'product_id' => 'required|string|uuid',
        ]);

        $productId = $request->input('product_id');
        $wishlist = session()->get('wishlist', []);
        $isInWishlist = in_array($productId, $wishlist);

        if ($isInWishlist) {
            $wishlist = array_values(array_filter($wishlist, fn($id) => $id !== $productId));
            session()->put('wishlist', $wishlist);
            $message = 'Produk dihapus dari favorit';
        } else {
            $wishlist[] = $productId;
            session()->put('wishlist', $wishlist);
            $message = 'Produk ditambahkan ke favorit';
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'in_wishlist' => !$isInWishlist,
                'wishlist_count' => count($wishlist),
                'message' => $message,
            ]);
        }

        return redirect()->back()->with('success', $message);
    }

    public function add(Request $request)
    {
        $request->validate([
            'product_id' => 'required|string|uuid',
            'quantity' => 'required|integer|min:1',
            'variant_id' => 'nullable|string|uuid',
        ]);

        $productId = $request->input('product_id');
        $variantId = $request->input('variant_id');
        $quantity = (int) $request->input('quantity');

        $product = Product::where('id', $productId)->first();
        if (!$product) {
            return response()->json(['error' => 'Product not found'], 404);
        }

        $price = $product->base_price;
        if ($variantId) {
            $variant = ProductVariant::where('id', $variantId)->first();
            if ($variant) {
                $price = $variant->price;
            }
        }

        $cart = session()->get('cart', []);
        $cartItemId = $variantId ? $variantId : $productId;

        if (isset($cart[$cartItemId])) {
            $cart[$cartItemId]['quantity'] += $quantity;
        } else {
            $cart[$cartItemId] = [
                'id' => $cartItemId,
                'product_id' => $productId,
                'name' => $product->name,
                'brand' => $product->brand->name ?? '',
                'image' => $product->thumbnail_url ?? '',
                'price' => (float) $price,
                'quantity' => $quantity,
            ];
        }

        session()->put('cart', $cart);

        if ($request->expectsJson()) {
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

        if ($request->expectsJson()) {
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

        if ($request->expectsJson()) {
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