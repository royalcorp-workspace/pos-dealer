<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Frontend\ProductsCatalog\Product;
use App\Models\Frontend\ProductsCatalog\ProductCategory;

class HomeController extends Controller
{
    public function index()
    {
        $bestsellers = Product::where('deleted', false)
            ->where('best_seller', true)
            ->with(['brand', 'category', 'images', 'variants', 'tags'])
            ->take(4)
            ->get();

        $recommended = Product::where('deleted', false)
            ->with(['brand', 'category', 'images', 'variants', 'tags'])
            ->take(6)
            ->get();

        $featured = Product::where('deleted', false)
            ->where('is_new', true)
            ->with(['brand', 'category', 'images', 'variants', 'tags'])
            ->first();

        $categories = ProductCategory::where('deleted', false)
            ->whereNull('parent_id')
            ->withCount('children')
            ->take(6)
            ->get();

        return view('frontend.home', compact('bestsellers', 'recommended', 'featured', 'categories'));
    }
}

