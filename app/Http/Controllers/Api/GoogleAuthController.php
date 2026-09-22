<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuthAuditService;
use App\Services\AuthTokenService;
use App\Services\DeviceSessionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class GoogleAuthController extends Controller
{
    public function __construct(
        private readonly AuthTokenService $tokens,
        private readonly AuthAuditService $audit,
        private readonly DeviceSessionService $deviceSessions
    ) {
    }

    public function redirectToGoogle()
    {
        $clientId = (string) config('services.google.client_id');
        $clientSecret = (string) config('services.google.client_secret');
        $redirectUri = (string) config('services.google.redirect_uri');
        $scopes = 'openid email profile';

        if (!$clientId || !$clientSecret || !$redirectUri) {
            return response()->json([
                'message' => 'Google OAuth belum dikonfigurasi.',
            ], 500);
        }

        $state = Str::random(40);
        session()->put('google_oauth_state', $state);

        $query = http_build_query([
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'scope' => $scopes,
            'access_type' => 'offline',
            'prompt' => 'select_account',
            'state' => $state,
        ]);

        return redirect('https://accounts.google.com/o/oauth2/v2/auth?' . $query);
    }

    public function handleGoogleCallback(Request $request)
    {
        $state = (string) $request->query('state', '');
        $sessionState = (string) session()->pull('google_oauth_state', '');

        if (!hash_equals($sessionState, $state)) {
            return response()->json([
                'message' => 'OAuth state tidak valid.',
            ], 422);
        }

        $code = (string) $request->query('code', '');

        if ($code === '') {
            return response()->json([
                'message' => 'Authorization code tidak ditemukan.',
            ], 422);
        }

        $clientId = (string) config('services.google.client_id');
        $clientSecret = (string) config('services.google.client_secret');
        $redirectUri = (string) config('services.google.redirect_uri');

        if (!$clientId || !$clientSecret || !$redirectUri) {
            return response()->json([
                'message' => 'Google OAuth belum dikonfigurasi.',
            ], 500);
        }

        $tokenResponse = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'code' => $code,
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'redirect_uri' => $redirectUri,
            'grant_type' => 'authorization_code',
        ]);

        if (!$tokenResponse->successful()) {
            Log::warning('Google OAuth token exchange failed', [
                'status' => $tokenResponse->status(),
                'body' => $tokenResponse->body(),
            ]);

            return response()->json([
                'message' => 'Gagal memverifikasi akun Google.',
            ], 401);
        }

        $tokenData = $tokenResponse->json();
        $idToken = (string) ($tokenData['id_token'] ?? '');

        if ($idToken === '') {
            return response()->json([
                'message' => 'ID token tidak ditemukan dari Google.',
            ], 401);
        }

        return $this->verifyGoogleIdTokenAndLogin($idToken, $request, true, '');
    }

    public function googleSignIn(Request $request)
    {
        $data = $request->validate([
            'id_token' => ['required', 'string'],
            'firebase_token' => ['nullable', 'string'],
        ]);

        $idToken = (string) $data['id_token'];
        $firebaseToken = (string) ($data['firebase_token'] ?? '');

        try {
            return $this->verifyGoogleIdTokenAndLogin($idToken, $request, false, $firebaseToken);
        } catch (ValidationException $exception) {
            return response()->json($exception->validator->errors(), 422);
        }
    }

    private function verifyGoogleIdTokenAndLogin(string $idToken, Request $request, bool $redirect, string $firebaseToken): mixed
    {
        $googleId = '';
        $email = '';
        $name = '';

        try {
            $verifiedIdToken = app('firebase.auth')->verifyIdToken($idToken);
            $googleId = (string) $verifiedIdToken->claims()->get('sub');
            $email = (string) $verifiedIdToken->claims()->get('email');
            $name = (string) $verifiedIdToken->claims()->get('name');
        } catch (\Throwable $e) {
            // Fallback: direct Google OAuth tokeninfo check
            try {
                $googleUser = Http::get('https://oauth2.googleapis.com/tokeninfo', [
                    'id_token' => $idToken,
                ]);
                if ($googleUser->successful()) {
                    $payload = $googleUser->json();
                    $googleId = (string) ($payload['sub'] ?? '');
                    $email = (string) ($payload['email'] ?? '');
                    $name = (string) ($payload['name'] ?? '');
                } else {
                    return response()->json([
                        'message' => 'Token tidak valid: ' . $e->getMessage(),
                    ], 401);
                }
            } catch (\Throwable $ex) {
                return response()->json([
                    'message' => 'Token tidak valid: ' . $e->getMessage(),
                ], 401);
            }
        }

        $email = trim($email);
        $googleId = trim($googleId);

        if ($googleId === '' || $email === '') {
            return response()->json([
                'message' => 'Token tidak mengandung informasi user yang valid.',
            ], 401);
        }

        $normalizedEmail = strtolower($email);

        // Cari user berdasarkan google_id, firebase_uid, atau email (case-insensitive)
        $user = User::query()
            ->where('google_id', $googleId)
            ->orWhere('firebase_uid', $googleId)
            ->orWhereRaw('LOWER(email) = ?', [$normalizedEmail])
            ->first();

        if ($user) {
            // User sudah terdaftar (baik via register biasa/non-google maupun google sebelumnya)
            $updateData = [
                'google_id' => $googleId,
                'firebase_uid' => $user->firebase_uid ?: $googleId,
                'email_verified' => true,
                'email_verified_at' => $user->email_verified_at ?? now(),
            ];

            if ($firebaseToken !== '') {
                $updateData['firebase_token'] = $firebaseToken;
            }

            $user->update($updateData);

            // Pastikan data customer juga sinkron
            try {
                \App\Models\Frontend\Customer\Customer::updateOrCreate(
                    ['email' => $user->email],
                    [
                        'user_id' => $user->id,
                        'name' => $user->name ?: ($name ?: $user->email),
                    ]
                );
            } catch (\Throwable $e) {
                Log::warning('Failed to sync customer on Google login: ' . $e->getMessage());
            }
        } else {
            // User baru -> langsung daftarkan akun dan customer secara otomatis tanpa 404
            $userName = $name !== '' ? $name : explode('@', $email)[0];

            $user = User::create([
                'id' => (string) \Illuminate\Support\Str::uuid(),
                'name' => $userName,
                'email' => $email,
                'google_id' => $googleId,
                'firebase_uid' => $googleId,
                'firebase_token' => $firebaseToken !== '' ? $firebaseToken : null,
                'email_verified' => true,
                'email_verified_at' => now(),
            ]);

            try {
                \App\Models\Frontend\Customer\Customer::create([
                    'user_id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ]);
            } catch (\Throwable $e) {
                Log::warning('Failed to create customer on Google auto-register: ' . $e->getMessage());
            }
        }

        $deviceId = $this->deviceSessions->deviceId($request);
        $access = $this->tokens->issueAccessToken($user, $deviceId);
        $refreshModel = $this->tokens->issueRefreshToken($user, $request, $deviceId);
        $this->deviceSessions->register($request, $user, null, null, $refreshModel->getKey());
        $this->deviceSessions->enforceLimit($user, null, $deviceId);

        $this->audit->log($user, 'login_success', $request, [
            'provider' => 'google',
            'google_id' => $googleId,
        ]);

        if ($redirect) {
            return redirect(config('app.url') . '/auth/callback#access_token=' . $access . '&refresh_token=' . ($refreshModel->getAttribute('raw_token')) . '&token_type=Bearer&expires_in=' . $this->tokens->accessTokenTtlSeconds());
        }

        return response()->json([
            'access_token' => $access,
            'refresh_token' => (string) $refreshModel->getAttribute('raw_token'),
            'token_type' => 'Bearer',
            'expires_in' => $this->tokens->accessTokenTtlSeconds(),
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
        ]);
    }
}
