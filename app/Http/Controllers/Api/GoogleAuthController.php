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
        try {
            $verifiedIdToken = app('firebase.auth')->verifyIdToken($idToken);
            $googleId = (string) $verifiedIdToken->claims()->get('sub');
            $email = (string) $verifiedIdToken->claims()->get('email');
            $name = (string) $verifiedIdToken->claims()->get('name');
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Token Firebase tidak valid: ' . $e->getMessage(),
            ], 401);
        }

        if ($googleId === '' || $email === '') {
            return response()->json([
                'message' => 'Token Firebase tidak valid.',
            ], 401);
        }

        $user = User::query()
            ->where('google_id', $googleId)
            ->first();

        if (!$user) {
            $user = User::query()
                ->where('email', $email)
                ->first();
        }

        if ($user) {
            if (empty($user->google_id)) {
                $user->update([
                    'google_id' => $googleId,
                    'firebase_token' => $firebaseToken !== '' ? $firebaseToken : $user->firebase_token,
                    'email_verified' => true,
                    'email_verified_at' => now(),
                ]);
            } elseif ($user->google_id !== $googleId) {
                return response()->json([
                    'action' => 'conflict',
                    'message' => 'Email ini sudah terdaftar dengan akun Google lain. Silakan login dengan akun Google yang sesuai.',
                ], 409);
            } elseif ($firebaseToken !== '') {
                $user->update([
                    'firebase_token' => $firebaseToken,
                    'email_verified' => true,
                    'email_verified_at' => now(),
                ]);
            }
        } else {
            return response()->json([
                'action' => 'register',
                'message' => 'Akun belum terdaftar. Silakan lengkapi pendaftaran.',
                'user' => [
                    'email' => $email,
                    'name' => $name,
                    'google_id' => $googleId,
                    'firebase_token' => $firebaseToken,
                ]
            ], 404);
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
