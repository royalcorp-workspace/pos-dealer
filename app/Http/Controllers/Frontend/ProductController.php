<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Services\ProductService;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    private ProductService $productService;

    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }

    public function index(Request $request)
    {
        $filterType = $request->query('type');
        $filterValue = $request->query('value');

        $products = $this->productService->filter($filterType, $filterValue);

        return view('frontend.product.index', [
            'products' => $products,
            'filterType' => $filterType,
            'filterValue' => $filterValue,
        ]);
    }

    public function show(string $id)
    {
        $product = $this->productService->find($id);

        if (!$product) {
            abort(404);
        }

        return view('frontend.product.show', compact('product'));
    }
}
