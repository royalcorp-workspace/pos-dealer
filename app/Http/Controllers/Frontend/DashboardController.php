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

        if (session()->get('must_set_password')) {
            return redirect()->route('auth.set-password');
        }

        $mockProduct = $this->productService->all()[0];
        $user = session()->get('user', []);
        
        $activeDeviceSessions = $this->deviceSessions->list(
            null,
            (string) ($user['email'] ?? ''),
            $this->deviceSessions->deviceId($request)
        );
        $userId = $user['id'] ?? $user['sub'] ?? null;
      
        $orders = $this->getOrdersForCurrentUser((string) ($user['email'] ?? ''), $userId);
        $addresses = Address::where('user_id', $userId)
            ->with(['subDistrict', 'city'])
            ->latest()
            ->get();

        $orderStatusLabels = \App\Models\Frontend\Order::statusLabels();

        $customer = null;
        if ($userId) {
            $customer = Customer::where('user_id', $userId)->first();
        }
        if (!$customer && !empty($user['email'])) {
            $customer = Customer::whereRaw('LOWER(email) = ?', [strtolower(trim($user['email']))])->first();
        }

        return view('frontend.dashboard', compact('mockProduct', 'activeDeviceSessions', 'orders', 'addresses', 'orderStatusLabels', 'customer'));
    }

    public function updateProfile(Request $request)
    {
        if (!session()->get('is_logged_in')) {
            return redirect()->route('home')->with('show_login', true);
        }

        $sessionUser = session()->get('user', []);
        $userId = $sessionUser['id'] ?? $sessionUser['sub'] ?? null;
        $email = $sessionUser['email'] ?? null;

        $userModel = $userId ? \App\Models\User::find($userId) : null;
        if (!$userModel && $email) {
            $userModel = \App\Models\User::whereRaw('LOWER(email) = ?', [strtolower(trim($email))])->first();
        }

        $rules = [
            'name' => 'required|string|max:100',
            'phone' => 'nullable|string|max:25',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ];

        if ($request->filled('new_password')) {
            $rules['current_password'] = 'required_with:new_password';
            $rules['new_password'] = [
                'required',
                'string',
                'min:8',
                'regex:/[a-z]/',
                'regex:/[A-Z]/',
                'regex:/[0-9]/',
                'regex:/[!@#$%^&*(),.?":{}|<>\-+=_\[\]\\\/~`]/',
            ];
            $rules['new_password_confirmation'] = 'required_with:new_password|same:new_password';
        }

        $messages = [
            'new_password.min' => 'Password baru minimal harus 8 karakter.',
            'new_password.regex' => 'Password baru harus mengandung setidaknya 1 huruf besar, 1 huruf kecil, 1 angka, dan 1 simbol khusus.',
            'new_password_confirmation.same' => 'Konfirmasi password baru tidak cocok.',
            'current_password.required_with' => 'Password saat ini diperlukan untuk mengubah password.',
            'avatar.max' => 'Ukuran foto profil maksimal 2MB.',
            'avatar.image' => 'File foto profil harus berupa gambar (JPG, PNG, WEBP).',
        ];

        $request->validate($rules, $messages);

        if ($request->filled('new_password')) {
            if ($userModel && !empty($userModel->password)) {
                if (!\Illuminate\Support\Facades\Hash::check($request->current_password, $userModel->password)) {
                    return back()->withInput()->withErrors(['current_password' => 'Password saat ini tidak sesuai.']);
                }
            }
        }

        $avatarUrl = null;
        if ($request->hasFile('avatar')) {
            $avatarFile = $request->file('avatar');
            $filename = 'avatar_' . ($userId ?: uniqid()) . '_' . time() . '.' . $avatarFile->getClientOriginalExtension();
            $path = $avatarFile->storeAs('avatars', $filename, 'public');
            $avatarUrl = asset('storage/' . $path);
        }

        $userUpdates = [
            'name' => $request->name,
            'phone' => $request->phone,
        ];
        if ($avatarUrl) {
            $userUpdates['avatar'] = $avatarUrl;
            $userUpdates['photo_url'] = $avatarUrl;
        }
        if ($request->filled('new_password')) {
            $userUpdates['password'] = \Illuminate\Support\Facades\Hash::make($request->new_password);
        }

        if ($userModel) {
            $userModel->update($userUpdates);
        } elseif ($userId) {
            \App\Models\User::where('id', $userId)->update($userUpdates);
        }

        $customerUpdates = [
            'name' => $request->name,
            'phone' => $request->phone,
        ];
        if ($userId) {
            Customer::where('user_id', $userId)->update($customerUpdates);
        } elseif ($email) {
            Customer::whereRaw('LOWER(email) = ?', [strtolower(trim($email))])->update($customerUpdates);
        }

        $sessionUser['name'] = $request->name;
        $sessionUser['phone'] = $request->phone;
        if ($avatarUrl) {
            $sessionUser['avatar'] = $avatarUrl;
            $sessionUser['photo_url'] = $avatarUrl;
        }
        session()->put('user', $sessionUser);

        return redirect()->route('dashboard', ['tab' => 'profile'])->with('success', 'Profil berhasil diperbarui.');
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

    private function getOrdersForCurrentUser(string $email, $userId = null)
    {
        if (!Schema::hasTable('orders') || !Schema::hasTable('order_items')) {
            return collect();
        }

        $customer = null;
        if ($userId) {
            $customer = Customer::where('user_id', $userId)->first();
        }
        $cleanEmail = strtolower(trim($email));
        if (!$customer && $cleanEmail) {
            $customer = Customer::whereRaw('LOWER(email) = ?', [$cleanEmail])->first();
        }

        if (!$customer) {
            return collect();
        }

        return Order::with(['items.product', 'customer', 'courier'])
            ->where('customer_id', $customer->id)
            ->latest()
            ->get();
    }
}