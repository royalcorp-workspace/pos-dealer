<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Services\ProductService;

class HomeController extends Controller
{
    private ProductService $productService;

    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }

    public function index()
    {
        $bestsellers = $this->productService->getBestsellers();
        $recommended = array_merge($this->productService->all(), array_slice($this->productService->all(), 0, 2));
        $featured = $this->productService->getFeatured();

        return view('frontend.home', compact('bestsellers', 'recommended', 'featured'));
    }
}

