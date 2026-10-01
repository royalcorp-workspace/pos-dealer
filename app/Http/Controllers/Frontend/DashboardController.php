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
      
        $customer = null;
        if ($userId) {
            $customer = Customer::where('user_id', $userId)->first();
        }
        if (!$customer && !empty($user['email'])) {
            $customer = Customer::whereRaw('LOWER(email) = ?', [strtolower(trim($user['email']))])->first();
        }
        $customerId = $customer?->id;

        $orders = $this->getOrdersForCurrentUser((string) ($user['email'] ?? ''), $userId);
        $addresses = Address::where(function ($q) use ($userId, $customerId) {
                if ($userId) {
                    $q->where('user_id', $userId);
                }
                if ($customerId) {
                    $q->orWhere('customer_id', $customerId);
                }
            })
            ->where('deleted', false)
            ->with(['subDistrict.city.province', 'city'])
            ->orderByDesc('is_primary')
            ->latest()
            ->get();

        $provinces = \App\Models\Frontend\Location\Province::orderBy('name')->get();
        $orderStatusLabels = \App\Models\Frontend\Order::statusLabels();

        return view('frontend.dashboard', compact('mockProduct', 'activeDeviceSessions', 'orders', 'addresses', 'orderStatusLabels', 'customer', 'provinces'));
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

        if ($request->filled('phone')) {
            $rawPhone = trim((string) $request->phone);
            $cleanPhone = preg_replace('/[\s\-]/', '', $rawPhone);
            if (!preg_match('/[^\d\+\s\-]/', $rawPhone) && !empty($cleanPhone)) {
                $request->merge(['phone' => $cleanPhone]);
            }
        }

        $rules = [
            'name' => 'required|string|max:100',
            'phone' => ['nullable', 'string', 'min:9', 'max:20', 'regex:/^(\+62|62|0)[0-9]{8,14}$/'],
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
            'phone.min' => 'Nomor telepon minimal 9 digit angka.',
            'phone.regex' => 'Nomor telepon tidak valid. Gunakan format 08... atau +628... tanpa simbol/huruf.',
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
        return redirect()->route('dashboard', ['tab' => 'addresses']);
    }

    public function storeAddress(Request $request)
    {
        if (!session()->get('is_logged_in')) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Silakan login terlebih dahulu.'], 401);
            }
            return redirect()->route('home')->with('show_login', true);
        }

        $rawPhone = trim((string) $request->phone);
        $cleanPhone = preg_replace('/[\s\-]/', '', $rawPhone);
        if (!preg_match('/[^\d\+\s\-]/', $rawPhone) && !empty($cleanPhone)) {
            $request->merge(['phone' => $cleanPhone]);
        }

        $request->validate([
            'label' => 'required|string|max:50',
            'recipient_name' => 'required|string|max:100',
            'phone' => ['required', 'string', 'min:9', 'max:20', 'regex:/^(\+62|62|0)[0-9]{8,14}$/'],
            'sub_district_id' => 'required|string|exists:sub_districts,id',
            'address' => ['required', 'string', 'min:5', 'max:500', 'regex:/[a-zA-Z0-9]{4,}/'],
            'postal_code' => 'nullable|string|max:10',
        ], [
            'phone.required' => 'Nomor telepon penerima wajib diisi.',
            'phone.min' => 'Nomor telepon minimal 9 digit angka.',
            'phone.regex' => 'Nomor telepon tidak valid. Gunakan format 08... atau +628... tanpa simbol/huruf.',
            'address.required' => 'Alamat lengkap wajib diisi.',
            'address.min' => 'Alamat pengiriman terlalu pendek. Mohon isi alamat dengan detail.',
            'address.regex' => 'Alamat pengiriman tidak valid. Mohon masukkan alamat lengkap (bukan tanda strip/simbol).',
        ]);

        $user = session()->get('user', []);
        $userId = $user['id'] ?? $user['sub'] ?? null;
        $email = $user['email'] ?? null;

        $customer = null;
        if ($userId) {
            $customer = Customer::where('user_id', $userId)->first();
        }
        if (!$customer && !empty($email)) {
            $customer = Customer::whereRaw('LOWER(email) = ?', [strtolower(trim($email))])->first();
        }

        if (!$userId && !$customer) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Data pengguna tidak valid. Silakan login kembali.'], 401);
            }
            return redirect()->route('dashboard', ['tab' => 'addresses'])->with('error', 'Data pengguna tidak valid. Silakan login kembali.');
        }

        $subDistrict = SubDistrict::with('city')->findOrFail($request->sub_district_id);
        $cityId = $subDistrict->city_id;
        $cityName = $subDistrict->city?->name ?? null;
        $subDistrictName = $subDistrict->sub_district;
        $postalCode = $request->filled('postal_code') ? $request->postal_code : ($subDistrict->postal_code ?? null);

        $isPrimary = $request->boolean('is_primary');

        // If user has no existing active address, auto set as primary
        $existingCount = Address::where(function ($q) use ($userId, $customer) {
            if ($userId) $q->where('user_id', $userId);
            if ($customer) $q->orWhere('customer_id', $customer->id);
        })->where('deleted', false)->count();

        if ($existingCount === 0) {
            $isPrimary = true;
        }

        if ($isPrimary) {
            Address::where(function ($q) use ($userId, $customer) {
                if ($userId) $q->where('user_id', $userId);
                if ($customer) $q->orWhere('customer_id', $customer->id);
            })->update(['is_primary' => false]);
        }

        $address = Address::create([
            'id' => Str::uuid()->toString(),
            'user_id' => $userId,
            'customer_id' => $customer?->id,
            'sub_district_id' => $request->sub_district_id,
            'city_id' => $cityId,
            'city_name' => $cityName,
            'sub_district_name' => $subDistrictName,
            'label' => $request->label,
            'recipient_name' => $request->recipient_name,
            'phone' => $request->phone,
            'address' => $request->address,
            'postal_code' => $postalCode,
            'is_primary' => $isPrimary,
            'deleted' => false,
            'creator' => $userId,
            'editor' => $userId,
        ]);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Alamat berhasil ditambahkan.',
                'address' => $address,
            ]);
        }

        return redirect()->route('dashboard', ['tab' => 'addresses'])->with('success', 'Alamat berhasil ditambahkan.');
    }

    public function updateAddress(Request $request, string $id)
    {
        if (!session()->get('is_logged_in')) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Silakan login terlebih dahulu.'], 401);
            }
            return redirect()->route('home')->with('show_login', true);
        }

        $address = Address::findOrFail($id);
        $user = session()->get('user', []);
        $userId = $user['id'] ?? $user['sub'] ?? null;
        $email = $user['email'] ?? null;

        $customer = null;
        if ($userId) {
            $customer = Customer::where('user_id', $userId)->first();
        }
        if (!$customer && !empty($email)) {
            $customer = Customer::whereRaw('LOWER(email) = ?', [strtolower(trim($email))])->first();
        }

        $authorized = false;
        if ($userId && (string)$address->user_id === (string)$userId) {
            $authorized = true;
        }
        if ($customer && (string)$address->customer_id === (string)$customer->id) {
            $authorized = true;
        }

        if (!$authorized) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Anda tidak memiliki hak untuk mengubah alamat ini.'], 403);
            }
            abort(403);
        }

        $rawPhone = trim((string) $request->phone);
        $cleanPhone = preg_replace('/[\s\-]/', '', $rawPhone);
        if (!preg_match('/[^\d\+\s\-]/', $rawPhone) && !empty($cleanPhone)) {
            $request->merge(['phone' => $cleanPhone]);
        }

        $request->validate([
            'label' => 'required|string|max:50',
            'recipient_name' => 'required|string|max:100',
            'phone' => ['required', 'string', 'min:9', 'max:20', 'regex:/^(\+62|62|0)[0-9]{8,14}$/'],
            'sub_district_id' => 'nullable|string|exists:sub_districts,id',
            'address' => ['required', 'string', 'min:5', 'max:500', 'regex:/[a-zA-Z0-9]{4,}/'],
            'postal_code' => 'nullable|string|max:10',
        ], [
            'phone.required' => 'Nomor telepon penerima wajib diisi.',
            'phone.min' => 'Nomor telepon minimal 9 digit angka.',
            'phone.regex' => 'Nomor telepon tidak valid. Gunakan format 08... atau +628... tanpa simbol/huruf.',
            'address.required' => 'Alamat lengkap wajib diisi.',
            'address.min' => 'Alamat pengiriman terlalu pendek. Mohon isi alamat dengan detail.',
            'address.regex' => 'Alamat pengiriman tidak valid. Mohon masukkan alamat lengkap (bukan tanda strip/simbol).',
        ]);

        $updateData = [
            'label' => $request->label,
            'recipient_name' => $request->recipient_name,
            'phone' => $request->phone,
            'address' => $request->address,
            'editor' => $userId,
        ];

        if ($userId && empty($address->user_id)) {
            $updateData['user_id'] = $userId;
        }
        if ($customer && empty($address->customer_id)) {
            $updateData['customer_id'] = $customer->id;
        }

        if ($request->filled('sub_district_id') && $request->sub_district_id !== $address->sub_district_id) {
            $subDistrict = SubDistrict::with('city')->findOrFail($request->sub_district_id);
            $updateData['sub_district_id'] = $subDistrict->id;
            $updateData['city_id'] = $subDistrict->city_id;
            $updateData['city_name'] = $subDistrict->city?->name ?? null;
            $updateData['sub_district_name'] = $subDistrict->sub_district;
            if (!$request->filled('postal_code')) {
                $updateData['postal_code'] = $subDistrict->postal_code;
            }
        }

        if ($request->filled('postal_code')) {
            $updateData['postal_code'] = $request->postal_code;
        }

        if ($request->has('is_primary')) {
            $isPrimary = $request->boolean('is_primary');
            if ($isPrimary) {
                Address::where(function ($q) use ($userId, $customer) {
                    if ($userId) $q->where('user_id', $userId);
                    if ($customer) $q->orWhere('customer_id', $customer->id);
                })->where('id', '!=', $id)->update(['is_primary' => false]);
            }
            $updateData['is_primary'] = $isPrimary;
        }

        $address->update($updateData);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Alamat berhasil diperbarui.',
                'address' => $address,
            ]);
        }

        return redirect()->route('dashboard', ['tab' => 'addresses'])->with('success', 'Alamat berhasil diperbarui.');
    }

    public function deleteAddress(string $id)
    {
        if (!session()->get('is_logged_in')) {
            if (request()->expectsJson() || request()->ajax()) {
                return response()->json(['success' => false, 'message' => 'Silakan login terlebih dahulu.'], 401);
            }
            return redirect()->route('home')->with('show_login', true);
        }

        $address = Address::findOrFail($id);
        $user = session()->get('user', []);
        $userId = $user['id'] ?? $user['sub'] ?? null;
        $email = $user['email'] ?? null;

        $customer = null;
        if ($userId) {
            $customer = Customer::where('user_id', $userId)->first();
        }
        if (!$customer && !empty($email)) {
            $customer = Customer::whereRaw('LOWER(email) = ?', [strtolower(trim($email))])->first();
        }

        $authorized = false;
        if ($userId && (string)$address->user_id === (string)$userId) $authorized = true;
        if ($customer && (string)$address->customer_id === (string)$customer->id) $authorized = true;

        if (!$authorized) {
            if (request()->expectsJson() || request()->ajax()) {
                return response()->json(['success' => false, 'message' => 'Anda tidak memiliki hak untuk menghapus alamat ini.'], 403);
            }
            abort(403);
        }

        $wasPrimary = $address->is_primary;
        $address->update(['deleted' => true]);
        $address->delete();

        // If the deleted address was primary, make the next active one primary
        if ($wasPrimary) {
            $next = Address::where(function ($q) use ($userId, $customer) {
                if ($userId) $q->where('user_id', $userId);
                if ($customer) $q->orWhere('customer_id', $customer->id);
            })->where('deleted', false)->first();

            if ($next) {
                $next->update(['is_primary' => true]);
            }
        }

        if (request()->expectsJson() || request()->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Alamat berhasil dihapus.',
            ]);
        }

        return redirect()->route('dashboard', ['tab' => 'addresses'])->with('success', 'Alamat berhasil dihapus.');
    }

    public function setPrimaryAddress(string $id)
    {
        if (!session()->get('is_logged_in')) {
            if (request()->expectsJson() || request()->ajax()) {
                return response()->json(['success' => false, 'message' => 'Silakan login terlebih dahulu.'], 401);
            }
            return redirect()->route('home')->with('show_login', true);
        }

        $address = Address::findOrFail($id);
        $user = session()->get('user', []);
        $userId = $user['id'] ?? $user['sub'] ?? null;
        $email = $user['email'] ?? null;

        $customer = null;
        if ($userId) {
            $customer = Customer::where('user_id', $userId)->first();
        }
        if (!$customer && !empty($email)) {
            $customer = Customer::whereRaw('LOWER(email) = ?', [strtolower(trim($email))])->first();
        }

        $authorized = false;
        if ($userId && (string)$address->user_id === (string)$userId) $authorized = true;
        if ($customer && (string)$address->customer_id === (string)$customer->id) $authorized = true;

        if (!$authorized) {
            if (request()->expectsJson() || request()->ajax()) {
                return response()->json(['success' => false, 'message' => 'Anda tidak memiliki hak untuk mengubah alamat ini.'], 403);
            }
            abort(403);
        }

        Address::where(function ($q) use ($userId, $customer) {
            if ($userId) $q->where('user_id', $userId);
            if ($customer) $q->orWhere('customer_id', $customer->id);
        })->update(['is_primary' => false]);

        $address->update(['is_primary' => true]);

        if (request()->expectsJson() || request()->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Alamat utama berhasil diubah.',
            ]);
        }

        return redirect()->route('dashboard', ['tab' => 'addresses'])->with('success', 'Alamat utama berhasil diubah.');
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