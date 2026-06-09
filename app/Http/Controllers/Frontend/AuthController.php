<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
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

        session()->put('is_logged_in', true);
        session()->put('user', [
            'name' => 'Budi Santoso',
            'email' => $request->input('email'),
            'type' => 'Member Premium'
        ]);

        if ($request->expectsJson() || $request->is('login')) {
            return response()->json([
                'success' => true,
                'user' => session()->get('user')
            ]);
        }

        return redirect()->route('dashboard')->with('success', 'Selamat datang kembali!');
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

        session()->put('is_logged_in', true);
        session()->put('user', [
            'name' => 'Budi Santoso',
            'email' => $request->input('email'),
            'type' => 'Member Premium'
        ]);

        if ($request->expectsJson() || $request->is('register')) {
            return response()->json([
                'success' => true,
                'user' => session()->get('user')
            ]);
        }

        return redirect()->route('dashboard')->with('success', 'Akun berhasil dibuat!');
    }

    public function logout(Request $request)
    {
        session()->forget(['is_logged_in', 'user', 'access_token', 'refresh_token']);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true
            ]);
        }

        return redirect()->route('home')->with('success', 'Anda telah keluar.');
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

        session()->put('is_logged_in', true);
        session()->put('access_token', $data['access_token']);
        session()->put('refresh_token', $data['refresh_token']);
        session()->put('user', [
            'name' => $name,
            'email' => $email,
            'type' => 'Google Member',
        ]);

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
}
