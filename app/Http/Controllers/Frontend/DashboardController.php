<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Frontend\Customer\Address;
use App\Models\Frontend\Customer\Customer;
use App\Models\Frontend\Location\SubDistrict;
use App\Models\Frontend\Order;
use App\Services\DeviceSessionService;
use App\Services\ProductService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

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

        $mockProduct = $this->productService->all()[0];
        $user = session()->get('user', []);
        $activeDeviceSessions = $this->deviceSessions->list(
            null,
            (string) ($user['email'] ?? ''),
            $this->deviceSessions->deviceId($request)
        );
        $orders = $this->getOrdersForCurrentUser((string) ($user['email'] ?? ''));
        $addresses = Address::where('user_id', $user['id'] ?? $user['sub'] ?? null)
            ->with(['subDistrict', 'city'])
            ->latest()
            ->get();

        $orderStatusLabels = \App\Models\Frontend\Order::statusLabels();

        return view('frontend.dashboard', compact('mockProduct', 'activeDeviceSessions', 'orders', 'addresses', 'orderStatusLabels'));
    }

    public function addresses()
    {
        if (!session()->get('is_logged_in')) {
            return redirect()->route('home')->with('show_login', true);
        }

        $user = session()->get('user', []);
        $userId = $user['id'] ?? $user['sub'] ?? null;
        $addresses = Address::where('user_id', $userId)->latest()->get();

        return view('frontend.dashboard-addresses', compact('addresses'));
    }

    public function storeAddress(Request $request)
    {
        if (!session()->get('is_logged_in')) {
            return redirect()->route('home')->with('show_login', true);
        }

        $request->validate([
            'label' => 'required|string|max:50',
            'recipient_name' => 'required|string|max:100',
            'phone' => 'required|string|max:20',
            'sub_district_id' => 'required|uuid|exists:sub_districts,id',
            'address' => 'required|string|max:500',
        ]);

        $user = session()->get('user', []);
        $userId = $user['id'] ?? $user['sub'] ?? null;

        if (!$userId) {
            return redirect()->route('dashboard.addresses')->with('error', 'Data pengguna tidak valid. Silakan login kembali.');
        }

        $subDistrict = SubDistrict::findOrFail($request->sub_district_id);

        if ($request->boolean('is_primary')) {
            Address::where('user_id', $userId)->update(['is_primary' => false]);
        }

        Address::create([
            'id' => Str::uuid(),
            'user_id' => $userId,
            'sub_district_id' => $request->sub_district_id,
            'city_id' => $subDistrict->city_id,
            'label' => $request->label,
            'recipient_name' => $request->recipient_name,
            'phone' => $request->phone,
            'address' => $request->address,
            'postal_code' => $subDistrict->postal_code,
            'is_primary' => $request->boolean('is_primary'),
        ]);

        return redirect()->route('dashboard.addresses')->with('success', 'Alamat berhasil ditambahkan.');
    }

    public function updateAddress(Request $request, string $id)
    {
        if (!session()->get('is_logged_in')) {
            return redirect()->route('home')->with('show_login', true);
        }

        $address = Address::findOrFail($id);
        $user = session()->get('user', []);
        $userId = $user['id'] ?? $user['sub'] ?? null;

        if ($address->user_id !== $userId) {
            abort(403);
        }

        $request->validate([
            'label' => 'required|string|max:50',
            'recipient_name' => 'required|string|max:100',
            'phone' => 'required|string|max:20',
            'address' => 'required|string|max:500',
        ]);

        if ($request->boolean('is_primary')) {
            Address::where('user_id', $userId)->where('id', '!=', $id)->update(['is_primary' => false]);
        }

        $address->update([
            'label' => $request->label,
            'recipient_name' => $request->recipient_name,
            'phone' => $request->phone,
            'address' => $request->address,
            'postal_code' => $request->postal_code,
            'is_primary' => $request->boolean('is_primary'),
        ]);

        return redirect()->route('dashboard.addresses')->with('success', 'Alamat berhasil diperbarui.');
    }

    public function deleteAddress(string $id)
    {
        if (!session()->get('is_logged_in')) {
            return redirect()->route('home')->with('show_login', true);
        }

        $address = Address::findOrFail($id);
        $user = session()->get('user', []);
        $userId = $user['id'] ?? $user['sub'] ?? null;

        if ($address->user_id !== $userId) {
            abort(403);
        }

        $address->delete();
        return redirect()->route('dashboard.addresses')->with('success', 'Alamat berhasil dihapus.');
    }

    public function setPrimaryAddress(string $id)
    {
        if (!session()->get('is_logged_in')) {
            return redirect()->route('home')->with('show_login', true);
        }

        $address = Address::findOrFail($id);
        $user = session()->get('user', []);
        $userId = $user['id'] ?? $user['sub'] ?? null;

        Address::where('user_id', $userId)->update(['is_primary' => false]);
        $address->update(['is_primary' => true]);

        return redirect()->route('dashboard.addresses')->with('success', 'Alamat utama berhasil diubah.');
    }

    private function getOrdersForCurrentUser(string $email)
    {
        if (!Schema::hasTable('orders') || !Schema::hasTable('order_items')) {
            return collect();
        }

        $customer = Customer::where('email', $email)->first();

        if (!$customer) {
            return collect();
        }

        return Order::with(['items.product', 'customer', 'courier'])
            ->where('customer_id', $customer->id)
            ->latest()
            ->get();
    }
}