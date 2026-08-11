<?php

declare(strict_types=1);

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Frontend\ProductsCatalog\Product;
use App\Models\Frontend\ProductsCatalog\Wishlist;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class WishlistController extends Controller
{
    public function index(Request $request)
    {
        $userId = session()->get('is_logged_in')
            ? (session()->get('user')['id'] ?? session()->get('user')['sub'] ?? null)
            : null;

        if (!$userId) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['wishlists' => [], 'count' => 0]);
            }
            return redirect()->route('home')->with('error', 'Silakan login untuk melihat wishlist.');
        }

        $wishlists = Wishlist::where('user_id', $userId)
            ->with(['product.brand', 'product.category', 'product.images', 'product.variants'])
            ->orderBy('created_at', 'desc')
            ->get();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'wishlists' => $wishlists,
                'count' => $wishlists->count(),
            ]);
        }

        return view('frontend.wishlist.index', compact('wishlists'));
    }

    public function toggle(Request $request)
    {
        $request->validate([
            'product_id' => 'required|string|uuid',
        ]);

        $productId = $request->input('product_id');
        $userId = session()->get('is_logged_in')
            ? (session()->get('user')['id'] ?? session()->get('user')['sub'] ?? null)
            : null;

        if (!$userId) {
            return response()->json([
                'success' => false,
                'message' => 'Silakan login untuk menambahkan ke wishlist.',
                'require_login' => true
            ], 401);
        }

        $wishlist = Wishlist::where('user_id', $userId)
            ->where('product_id', $productId)
            ->first();

        if ($wishlist) {
            $wishlist->delete();
            $inWishlist = false;
        } else {
            Wishlist::create([
                'id' => Str::uuid()->toString(),
                'user_id' => $userId,
                'product_id' => $productId,
            ]);
            $inWishlist = true;
        }

        $count = Wishlist::where('user_id', $userId)->count();

        return response()->json([
            'success' => true,
            'in_wishlist' => $inWishlist,
            'count' => $count,
        ]);
    }

    public function remove(Request $request, Product $product)
    {
        $userId = session()->get('is_logged_in')
            ? (session()->get('user')['id'] ?? session()->get('user')['sub'] ?? null)
            : null;

        if ($userId) {
            Wishlist::where('user_id', $userId)
                ->where('product_id', $product->id)
                ->delete();
        }

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Produk dihapus dari wishlist.']);
        }

        return back()->with('success', 'Produk dihapus dari wishlist.');
    }

    public function count(Request $request)
    {
        $userId = session()->get('is_logged_in')
            ? (session()->get('user')['id'] ?? session()->get('user')['sub'] ?? null)
            : null;

        if (!$userId) {
            return response()->json(['count' => 0]);
        }

        $count = Wishlist::where('user_id', $userId)->count();

        return response()->json(['count' => $count]);
    }

    private function getSessionId(): string
    {
        if (!session()->has('guest_session_id')) {
            session()->put('guest_session_id', session()->getId() ?: Str::random(40));
        }
        return (string) session()->get('guest_session_id');
    }
}
