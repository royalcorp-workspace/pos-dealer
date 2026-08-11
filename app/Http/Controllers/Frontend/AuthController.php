<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\DeviceSessionService;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function __construct(
        private readonly DeviceSessionService $deviceSessions
    ) {
    }

    public function login(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|email',
                'password' => 'required|string',
            ]);
        } catch (ValidationException $e) {
            if ($request->expectsJson() || $request->is('login')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Email atau password tidak valid'
                ], 422);
            }
            throw $e;
        }

        $email = (string) $request->input('email');
        $user = User::query()->where('email', $email)->first();

        if (!$user || !Hash::check($request->password, $user->password ?? '')) {
            if ($request->expectsJson() || $request->is('login')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Email atau password tidak valid'
                ], 422);
            }
            return redirect()->route('home')->with('show_login', true);
        }

        session()->put('is_logged_in', true);
        session()->put('user', [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $email,
            'type' => 'Member'
        ]);

        // Merge guest cart with logged-in user cart
        $guestSessionId = session()->get('guest_session_id') ?: session()->getId();
        $customer = \App\Models\Frontend\Customer\Customer::where('user_id', $user->id)->first();
        if ($customer && $guestSessionId) {
            $guestBuffer = \App\Models\Frontend\Buffer\Buffer::where('session_id', $guestSessionId)
                ->whereNull('customer_id')
                ->first();
                
            if ($guestBuffer) {
                $userBuffer = \App\Models\Frontend\Buffer\Buffer::where('customer_id', $customer->id)->first();
                if ($userBuffer) {
                    foreach ($guestBuffer->items as $guestItem) {
                        $existingItem = \App\Models\Frontend\Buffer\BufferItem::where('buffer_id', $userBuffer->id)
                            ->where('product_id', $guestItem->product_id)
                            ->where('product_variant_id', $guestItem->product_variant_id)
                            ->first();
                            
                        if ($existingItem) {
                            $existingItem->update([
                                'quantity' => $existingItem->quantity + $guestItem->quantity,
                                'total' => $existingItem->unit_price * ($existingItem->quantity + $guestItem->quantity)
                            ]);
                            $guestItem->delete();
                        } else {
                            $guestItem->update([
                                'buffer_id' => $userBuffer->id
                            ]);
                        }
                    }
                    $userItems = $userBuffer->items()->get();
                    $subtotal = $userItems->sum(fn($item) => (float) $item->unit_price * (int) $item->quantity);
                    $discount = $userItems->sum(function ($item) {
                        $itemTotal = (float) $item->unit_price * (int) $item->quantity;
                        $discountNominal = (float) $item->discount_nominal;
                        $discountPercent = $itemTotal > 0 ? ($itemTotal * (float) $item->discount_percent / 100) : 0;
                        return $discountNominal + $discountPercent;
                    });
                    $userBuffer->update([
                        'subtotal' => $subtotal,
                        'discount' => $discount,
                        'total' => $subtotal - $discount,
                        'session_id' => $guestSessionId,
                    ]);
                    $guestBuffer->delete();
                } else {
                    $guestBuffer->update([
                        'customer_id' => $customer->id,
                        'customer_name' => $customer->name,
                        'customer_email' => $customer->email,
                        'creator' => $user->id,
                        'editor' => $user->id,
                    ]);
                }
            }
        }

        $this->deviceSessions->register($request, $user, $email);
        $revokedCount = $this->deviceSessions->enforceLimit($user, $email, $this->deviceSessions->deviceId($request));

        if ($request->expectsJson() || $request->is('login')) {
            return response()->json([
                'success' => true,
                'user' => session()->get('user'),
                'revoked_devices' => $revokedCount,
            ]);
        }

        return redirect()->route('dashboard')->with('success', $revokedCount > 0 ? 'Login berhasil. Perangkat lama telah dikeluarkan.' : 'Selamat datang kembali!');
    }

    public function register(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|email',
                'password' => 'required|string|min:6',
            ]);
        } catch (ValidationException $e) {
            if ($request->expectsJson() || $request->is('register')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data yang dimasukkan tidak valid'
                ], 422);
            }
            throw $e;
        }

        $email = (string) $request->input('email');
        $user = User::query()->where('email', $email)->first();

        if (!$user) {
            if ($request->expectsJson() || $request->is('register')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data yang dimasukkan tidak valid'
                ], 422);
            }
            return redirect()->route('home')->with('show_login', true);
        }

        session()->put('is_logged_in', true);
        session()->put('user', [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $email,
            'type' => 'Member'
        ]);

        $this->deviceSessions->register($request, $user, $email);
        $revokedCount = $this->deviceSessions->enforceLimit($user, $email, $this->deviceSessions->deviceId($request));

        if ($request->expectsJson() || $request->is('register')) {
            return response()->json([
                'success' => true,
                'user' => session()->get('user'),
                'revoked_devices' => $revokedCount,
            ]);
        }

        return redirect()->route('dashboard')->with('success', $revokedCount > 0 ? 'Akun berhasil dibuat. Perangkat lama telah dikeluarkan.' : 'Akun berhasil dibuat!');
    }

    public function logout(Request $request)
    {
        $this->deviceSessions->removeCurrent($request);
        session()->forget(['is_logged_in', 'user', 'access_token', 'refresh_token']);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true
            ]);
        }

        return redirect()->route('home')->with('success', 'Anda telah keluar.');
    }

    public function logoutDevice(Request $request, string $device)
    {
        if (!session()->get('is_logged_in')) {
            return redirect()->route('home')->with('show_login', true);
        }

        $user = session()->get('user');
        if (!is_array($user)) {
            return redirect()->route('home')->with('show_login', true);
        }

        $email = (string) ($user['email'] ?? '');
        $dbUser = User::query()->where('email', $email)->first();
        $removed = $this->deviceSessions->remove($device, $this->deviceSessions->deviceId($request), false);

        if ($removed) {
            $this->deviceSessions->enforceLimit($dbUser, $email, $this->deviceSessions->deviceId($request));
        }

        return redirect()->route('dashboard')->with('success', $removed ? 'Perangkat berhasil dikeluarkan.' : 'Perangkat tidak ditemukan.');
    }

    public function googleCallback()
    {
        return view('frontend.auth-callback');
    }

    public function storeGoogleSession(Request $request)
    {
        $data = $request->validate([
            'access_token' => ['required', 'string'],
            'refresh_token' => ['required', 'string'],
        ]);

        $payload = $this->decodeAccessToken($data['access_token']);

        $email = (string) ($payload->email ?? '');
        $name = (string) ($payload->name ?? ($email ? explode('@', $email)[0] : 'Member'));

        $user = User::query()->where('email', $email)->first();

        if (!$user) {
            $user = User::create([
                'id' => \Illuminate\Support\Str::uuid()->toString(),
                'name' => $name,
                'email' => $email,
                'email_verified' => true,
                'email_verified_at' => now(),
            ]);
        }

        // Ensure customer record exists and is linked to the user
        \App\Models\Frontend\Customer\Customer::updateOrCreate(
            ['email' => $email],
            [
                'user_id' => $user->id,
                'name' => $name,
            ]
        );

        session()->put('is_logged_in', true);
        session()->put('access_token', $data['access_token']);
        session()->put('refresh_token', $data['refresh_token']);
        session()->put('user', [
            'id' => $user->id,
            'name' => $name,
            'email' => $email,
            'type' => 'Google Member',
        ]);

        $this->deviceSessions->register($request, $user, $email);
        $this->deviceSessions->enforceLimit($user, $email, $this->deviceSessions->deviceId($request));

        return response()->json([
            'success' => true,
            'redirect' => route('dashboard'),
        ]);
    }

    private function jwtSecret(): string
    {
        $raw = (string) env('JWT_SECRET');
        if ($raw === '') {
            $raw = (string) env('APP_KEY', 'laravel-jwt-fallback');
        }

        if (strlen($raw) < 32) {
            return hash('sha256', $raw);
        }

        return $raw;
    }

    private function decodeAccessToken(string $token): object
    {
        return JWT::decode($token, new Key($this->jwtSecret(), 'HS256'));
    }

    public function showForgotPassword()
    {
        return view('frontend.forgot-password', [
            'showRegister' => (bool) request()->query('register', false),
        ]);
    }

    public function processForgotPassword(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $response = \Illuminate\Support\Facades\Http::post(url('/api/auth/forgot-password'), [
            'email' => $request->email,
            'channel' => 'email',
        ]);

        return redirect()->route('reset-password.show', ['email' => $request->email]);
    }

    public function showResetPassword()
    {
        return view('frontend.reset-password', [
            'email' => request()->query('email', ''),
        ]);
    }

    public function showRegister()
    {
        return view('frontend.register', [
            'email' => request()->query('email', ''),
            'name' => request()->query('name', ''),
            'google_id' => request()->query('google_id', ''),
            'firebase_token' => request()->query('firebase_token', ''),
        ]);
    }
}
