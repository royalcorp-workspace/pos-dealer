<?php

declare(strict_types=1);

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Frontend\Promo\PriceProductSetting;
use Illuminate\Http\Request;

class PriceProductSettingController extends Controller
{
    public function index()
    {
        $featured = PriceProductSetting::featured()->with('products')->get();
        $active = PriceProductSetting::active()->latest()->paginate(12);
        return view('frontend.price-product-settings', compact('featured', 'active'));
    }

    public function show(string $code)
    {
        $priceProductSetting = PriceProductSetting::active()->where('code', strtoupper($code))->firstOrFail();
        $products = $priceProductSetting->products()->active()->paginate(20);
        return view('frontend.price-product-setting-detail', compact('priceProductSetting', 'products'));
    }
}
