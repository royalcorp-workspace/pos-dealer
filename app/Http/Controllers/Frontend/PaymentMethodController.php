<?php

declare(strict_types=1);

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\PaymentMethod;
use Illuminate\Http\Request;

class PaymentMethodController extends Controller
{
    public function index()
    {
        $methods = PaymentMethod::active()
            ->orderBy('sort_order')
            ->get()
            ->groupBy('type');

        return view('frontend.payment-methods', compact('methods'));
    }
}
