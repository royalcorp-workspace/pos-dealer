<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EmailVerification;
use App\Models\LoginAttempt;
use App\Models\PasswordReset;
use App\Models\RefreshToken;
use App\Models\User;
use App\Services\AuthAuditService;
use App\Services\AuthTokenService;
use App\Services\DeviceSessionService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function __construct(
        private readonly AuthTokenService $tokens,
        private readonly AuthAuditService $audit,
        private readonly DeviceSessionService $deviceSessions
    ) {
    }

    private function lockoutSeconds(): int
    {
        return 15 * 60;
    }

    private function maxAttempts(): int
    {
        return 5;
    }

    private function recordAttempt(
        ?User $user,
        string $email,
        string $ip,
        bool $success,
        ?\DateTimeInterface $lockedUntil = null
    ): void {
        LoginAttempt::create([
            'user_id' => $user?->getKey() ? (string) $user->getKey() : null,
            'ip_address' => $ip,
            'email' => $email,
            'success' => $success,
            'attempted_at' => now(),
            'locked_until' => $lockedUntil,
        ]);
    }

    private function currentLockout(string $email, string $ip): ?\DateTimeInterface
    {
        $latest = LoginAttempt::query()
            ->where('email', $email)
            ->orderByDesc('attempted_at')
            ->first();

        if (!$latest) {
            return null;
        }

        return $latest->locked_until;
    }

    public function register(Request $request)
    {
        $isGoogleSignup = $request->filled('google_id');

        $data = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'password' => [$isGoogleSignup ? 'nullable' : 'required', 'string', 'min:8',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).+$/'],
            'password_confirmation' => [$isGoogleSignup ? 'nullable' : 'required', 'same:password'],
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20'],
            'sub_district_id' => ['required', 'uuid', 'exists:sub_districts,id'],
            'address' => ['required', 'string', 'max:500'],
            'google_id' => ['nullable', 'string'],
            'firebase_token' => ['nullable', 'string'],
        ]);

        if (empty($data['password']) && empty($data['google_id'])) {
            return response()->json(['message' => 'Password is required if not using Google Sign In'], 422);
        }

        $exists = User::query()->where('email', $data['email'])->exists();
        if ($exists) {
            return response()->json(['message' => 'Email already exists'], 422);
        }

        $user = User::create([
            'id' => Str::uuid()->toString(),
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => !empty($data['password']) ? Hash::make($data['password']) : null,
            'google_id' => $data['google_id'] ?? null,
            'firebase_token' => $data['firebase_token'] ?? null,
            'email_verified' => !empty($data['google_id']),
            'email_verified_at' => !empty($data['google_id']) ? now() : null,
        ]);

        \App\Models\Frontend\Customer\Customer::updateOrCreate(
            ['email' => $data['email']],
            [
                'user_id' => $user->id,
                'name' => $data['name'],
                'phone' => $data['phone'],
            ]
        );

        $subDistrict = \App\Models\Frontend\Location\SubDistrict::find($data['sub_district_id']);
        if ($subDistrict) {
            \App\Models\Frontend\Customer\Address::create([
                'id' => Str::uuid()->toString(),
                'user_id' => $user->id,
                'sub_district_id' => $data['sub_district_id'],
                'city_id' => $subDistrict->city_id,
                'label' => 'Rumah',
                'recipient_name' => $data['name'],
                'phone' => $data['phone'],
                'address' => $data['address'],
                'postal_code' => $subDistrict->postal_code,
                'is_primary' => true,
            ]);
        }

        if (empty($data['google_id'])) {
            $token = Str::random(64);
            EmailVerification::create([
                'user_id' => $user->getAttribute('id'),
                'token' => $token,
                'expires_at' => now()->addHours(24),
                'used' => false,
            ]);
            try {
                \Illuminate\Support\Facades\Mail::to($user->email)
                    ->send(new \App\Mail\VerifyEmailMail($user->email, $token));
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error('Failed to send verification email: ' . $e->getMessage());
            }
            return response()->json([
                'message' => 'User created. Verification email sent to your inbox.'
            ], 201);
        }

        $deviceId = $this->deviceSessions->deviceId($request);
        $access = $this->tokens->issueAccessToken($user, $deviceId);
        $refreshModel = $this->tokens->issueRefreshToken($user, $request, $deviceId);
        $this->deviceSessions->register($request, $user, null, null, $refreshModel->getKey());
        $this->deviceSessions->enforceLimit($user, null, $deviceId);

        $this->audit->log($user, 'login_success', $request, [
            'provider' => 'google',
            'google_id' => $data['google_id'],
        ]);

        return response()->json([
            'message' => 'User created successfully.',
            'access_token' => $access,
            'refresh_token' => (string) $refreshModel->getAttribute('raw_token'),
            'token_type' => 'Bearer',
            'expires_in' => $this->tokens->accessTokenTtlSeconds(),
            'redirect' => '/dashboard'
        ], 201);
    }

    public function verifyEmail(Request $request)
    {
        $data = $request->validate([
            'token' => ['required', 'string', 'max:255'],
        ]);

        $token = $data['token'];

        $verification = EmailVerification::query()->where('token', $token)->first();
        if (!$verification) {
            return response()->json(['message' => 'Invalid token'], 422);
        }

        if ($verification->used) {
            return response()->json(['message' => 'Token already used'], 422);
        }

        if ($verification->expires_at->getTimestamp() < time()) {
            return response()->json(['message' => 'Token expired'], 422);
        }

        $verification->update(['used' => true]);

        $user = User::query()->findOrFail($verification->user_id);
        $user->update([
            'email_verified' => true,
            'email_verified_at' => now(),
        ]);

        return response()->json(['message' => 'Email verified successfully']);
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $ip = (string) $request->ip();
        $email = $data['email'];

        $lockout = LoginAttempt::query()
            ->where('email', $email)
            ->where('locked_until', '!=', null)
            ->orderByDesc('attempted_at')
            ->first();

        if ($lockout?->locked_until && $lockout->locked_until->getTimestamp() > time()) {
            return response()->json(['message' => 'Account locked. Try later'], 423);
        }


        $user = User::query()->where('email', $email)->first();
        if (!$user || !Hash::check($data['password'], $user->password ?? '')) {

            $this->recordAttempt(null, $email, $ip, false);

            $failedCount = LoginAttempt::query()
                ->where('email', $email)
                ->where('success', false)
                ->where('attempted_at', '>=', now()->subSeconds($this->lockoutSeconds()))
                ->count();

            if ($failedCount >= $this->maxAttempts()) {
                $lockedUntil = now()->addSeconds($this->lockoutSeconds());
                LoginAttempt::query()->where('email', $email)->latest('attempted_at')->first()?->update([
                    'locked_until' => $lockedUntil,
                ]);
            }


            $this->audit->log(null, 'login_failed', $request, ['email' => $email]);
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        if (!($user->email_verified ?? false)) {
            $this->recordAttempt($user, $email, $ip, false);
            $this->audit->log($user, 'login_failed', $request, ['email_verified' => false]);
            return response()->json(['message' => 'Email not verified'], 403);
        }

        $this->recordAttempt($user, $email, $ip, true);

        $deviceId = $this->deviceSessions->deviceId($request);
        $access = $this->tokens->issueAccessToken($user, $deviceId);
        $refreshModel = $this->tokens->issueRefreshToken($user, $request, $deviceId);
        $this->deviceSessions->register($request, $user, null, null, $refreshModel->getKey());
        $this->deviceSessions->enforceLimit($user, null, $deviceId);

        $this->audit->log($user, 'login_success', $request, []);

        return response()->json([
            'access_token' => $access,
            'refresh_token' => (string) $refreshModel->getAttribute('raw_token'),
            'token_type' => 'Bearer',
            'expires_in' => $this->tokens->accessTokenTtlSeconds(),
        ]);
    }

    public function refresh(Request $request)
    {
        $data = $request->validate([
            'refresh_token' => ['required', 'string'],
        ]);

        $token = (string) $data['refresh_token'];
        $tokenHash = $this->tokens->hashRefreshToken($token);

        $refresh = RefreshToken::query()->where('token_hash', $tokenHash)->where('revoked', false)->first();
        if (!$refresh) {
            return response()->json(['message' => 'Invalid refresh token'], 401);
        }

        if ($refresh->expires_at->getTimestamp() < time()) {
            return response()->json(['message' => 'Refresh token expired'], 401);
        }

        $user = User::query()->findOrFail($refresh->user_id);
        $deviceId = $refresh->device_id ?: $this->deviceSessions->deviceId($request);

        $out = $this->tokens->refresh($token, $user, $request, $deviceId);
        $this->
            deviceSessions->
            register($request, $user, null, null, RefreshToken::query()->
            where('token_hash', $this->
            tokens->
            hashRefreshToken($out['refresh_token']))->
            value('id'));
        $this->deviceSessions->enforceLimit($user, null, $deviceId);
        $this->audit->log($user, 'token_refresh', $request, []);

        return response()->json($out);
    }

    public function logout(Request $request)
    {
        $data = $request->validate([
            'refresh_token' => ['required', 'string'],
        ]);

        $tokenHash = $this->tokens->hashRefreshToken((string) $data['refresh_token']);
        $refresh = RefreshToken::query()->where('token_hash', $tokenHash)->where('revoked', false)->first();

        $user = null;
        if ($refresh) {
            $user = User::query()->find($refresh->user_id);
            $deviceId = $refresh->device_id;
            $this->tokens->revokeRefreshToken($refresh);
            if ($deviceId !== null) {
                $this->deviceSessions->remove($deviceId, null, true);
            }
        }

        $this->audit->log($user, 'logout', $request, []);

        return response()->json(['message' => 'Logged out']);
    }

    public function devices(Request $request)
    {
        $refresh = $this->resolveRefreshToken($request);
        if (!$refresh) {
            return response()->json(['message' => 'Invalid refresh token'], 401);
        }

        $user = User::query()->find($refresh->user_id);
        $currentDeviceId = $refresh->device_id ?: $this->deviceSessions->deviceId($request);

        return response()->json([
            'devices' => $this->deviceSessions->list($user, null, $currentDeviceId),
        ]);
    }

    public function logoutDevice(Request $request, string $device)
    {
        $refresh = $this->resolveRefreshToken($request);
        if (!$refresh) {
            return response()->json(['message' => 'Invalid refresh token'], 401);
        }

        $currentDeviceId = $refresh->device_id ?: $this->deviceSessions->deviceId($request);
        $removed = $this->deviceSessions->remove($device, $currentDeviceId, $device === $currentDeviceId);

        if (!$removed) {
            return response()->json(['message' => 'Device session not found'], 404);
        }

        return response()->json(['message' => 'Device logged out']);
    }

    private function resolveRefreshToken(Request $request): ?RefreshToken
    {
        $data = $request->validate([
            'refresh_token' => ['required', 'string'],
        ]);

        $token = (string) $data['refresh_token'];
        $tokenHash = $this->tokens->hashRefreshToken($token);

        $refresh = RefreshToken::query()
            ->where('token_hash', $tokenHash)
            ->where('revoked', false)
            ->first();

        if (!$refresh || $refresh->expires_at->getTimestamp() < time()) {
            return null;
        }

        return $refresh;
    }
}
