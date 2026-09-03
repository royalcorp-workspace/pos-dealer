<?php

declare(strict_types=1);

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Frontend\Review;
use App\Models\Frontend\Order;
use App\Models\Frontend\Order\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ReviewController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'product_id' => 'required|uuid|exists:products,id',
            'order_id' => 'required|uuid|exists:orders,id',
            'rating' => 'required|integer|min:1|max:5',
            'text' => 'required|string|min:10',
            'image' => 'nullable|string',
        ]);

        $user = session()->get('user', []);
        $userId = $user['id'] ?? $user['sub'] ?? null;
        $email = $user['email'] ?? '';
        $name = $user['name'] ?? 'Anonymous';

        // Check if order is delivered and belongs to user
        $order = Order::find($request->order_id);
        if (!$order || $order->status !== Order::STATUS_DELIVERED) {
            return response()->json(['error' => 'Order belum dapat direview.'], 422);
        }

        // Check if already reviewed
        $existing = Review::where('product_id', $request->product_id)
            ->where('order_id', $request->order_id)
            ->exists();

        if ($existing) {
            return response()->json(['error' => 'Produk sudah pernah direview.'], 422);
        }

        $imageUrl = null;
        if ($request->hasFile('image')) {
            $imageUrl = $request->file('image')->store('reviews', 's3');
        } elseif ($request->filled('image')) {
            $imageUrl = $request->input('image');
        }

        $review = Review::create([
            'id' => Str::uuid(),
            'product_id' => $request->product_id,
            'order_id' => $request->order_id,
            'user_name' => $name,
            'user_email' => $email,
            'rating' => $request->rating,
            'text' => $request->text,
            'image_url' => $imageUrl,
            'is_approved' => false,
            'is_published' => true,
        ]);

        return response()->json(['success' => true, 'review' => $review]);
    }

    public function filter(Request $request, string $productId)
    {
        $rating = $request->query('rating');
        $query = Review::where('product_id', $productId)->published();

        if ($rating) {
            $query->where('rating', $rating);
        }

        $reviews = $query->latest()->paginate(10);
        return response()->json($reviews);
    }

    public function report(string $reviewId)
    {
        $review = Review::findOrFail($reviewId);
        $review->increment('report_count');
        return response()->json(['success' => true]);
    }
}
