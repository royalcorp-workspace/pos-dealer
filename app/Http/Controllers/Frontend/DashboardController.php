<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Services\DeviceSessionService;
use App\Services\ProductService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    private ProductService $productService;
    private DeviceSessionService $deviceSessions;

    public function __construct(ProductService $productService, DeviceSessionService $deviceSessions)
    {
        $this->productService = $productService;
        $this->deviceSessions = $deviceSessions;
    }

    public function index(Request $request)
    {
        if (!session()->get('is_logged_in')) {
            return redirect()->route('home')->with('show_login', true);
        }

        $mockProduct = $this->productService->all()[0]; // Elite Royal Sovereign
        $user = session()->get('user', []);
        $activeDeviceSessions = $this->deviceSessions->list(
            null,
            (string) ($user['email'] ?? ''),
            $this->deviceSessions->deviceId($request)
        );

        return view('frontend.dashboard', compact('mockProduct', 'activeDeviceSessions'));
    }
}

