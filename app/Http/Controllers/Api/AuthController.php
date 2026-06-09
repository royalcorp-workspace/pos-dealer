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
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Kreait\Firebase\Contract\Auth as FirebaseAuthContract;

class AuthController extends Controller
{
    public function __construct(
        private readonly AuthTokenService $tokens,
        private readonly AuthAuditService $audit
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
        $data = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:6'],
            'name' => ['nullable', 'string', 'max:255'],
        ]);

        $exists = User::query()->where('email', $data['email'])->exists();
        if ($exists) {
            return response()->json(['message' => 'Email already exists'], 422);
        }

        $user = User::create([
            'id' => Str::uuid()->toString(),
            'name' => $data['name'] ?? 'Member',
            'email' => $data['email'],
            'password_hash' => Hash::make($data['password']),
            'email_verified' => false,
            'email_verified_at' => null,
        ]);


        // Create email verification token (24h, once-use)
        $token = Str::random(64);
        EmailVerification::create([
            'user_id' => $user->getAttribute('id'),
            'token' => $token,
            'expires_at' => now()->addHours(24),
            'used' => false,
        ]);

        // Send verification email
        \Illuminate\Support\Facades\Mail::to($user->email)->send(new \App\Mail\VerifyEmailMail($user->email, $token));

        return response()->json([
            'message' => 'User created. Verification email sent to your inbox.'
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
        if (!$user || !Hash::check($data['password'], $user->password_hash ?? '')) {

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

        $access = $this->tokens->issueAccessToken($user);
        $refreshModel = $this->tokens->issueRefreshToken($user, $request);

        $this->audit->log($user, 'login_success', $request, []);

        return response()->json([
            'access_token' => $access,
            'refresh_token' => (string) $refreshModel->getAttribute('raw_token'),
            'token_type' => 'Bearer',
            'expires_in' => 3600,
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

        $out = $this->tokens->refresh($token, $user, $request);
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
            $this->tokens->revokeRefreshToken($refresh);
        }

        $this->audit->log($user, 'logout', $request, []);

        return response()->json(['message' => 'Logged out']);
    }
}

