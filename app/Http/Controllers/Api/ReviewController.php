<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Frontend\Review;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function index(Request $request, int $productId): JsonResponse
    {
        $reviews = Review::query()
            ->where('product_id', $productId)
            ->published()
            ->orderByDesc('created_at')
            ->get(['user_name', 'rating', 'text', 'image_url', 'created_at']);

        return response()->json([
            'reviews' => $reviews->map(fn ($r) => [
                'id' => $r->user_name . $r->created_at->timestamp,
                'user_name' => $r->user_name,
                'rating' => $r->rating,
                'text' => $r->text,
                'image' => $r->image_url ? asset('storage/' . ltrim($r->image_url, '/')) : null,
                'created_at' => $r->created_at->diffForHumans(),
            ])->all(),
        ]);
    }
}
