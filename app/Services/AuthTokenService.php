<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\RefreshToken;
use App\Models\User;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AuthTokenService
{
    // Durations
    private int $accessTtlSeconds = 3600; // 1 hour
    private int $refreshTtlSeconds = 2592000; // 30 days

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

    public function issueAccessToken(User $user): string
    {
        $now = time();

        $payload = [
            'iss' => env('APP_URL', 'localhost'),
            'sub' => (string) $user->id,
            'email' => $user->email,
            'iat' => $now,
            'exp' => $now + $this->accessTtlSeconds,
            'typ' => 'access',
        ];

        return JWT::encode($payload, $this->jwtSecret(), 'HS256');
    }

    public function issueRefreshToken(User $user, Request $request): RefreshToken
    {
        $raw = Str::random(60);
        $tokenHash = hash('sha256', $raw);

        $now = now();
        $expiresAt = $now->copy()->addSeconds($this->refreshTtlSeconds);
        $model = RefreshToken::create([
            'user_id' => $user->getAttributes()['id'],
            'token_hash' => $tokenHash,
            'expires_at' => $expiresAt,
            'revoked' => false,
            'device_info' => $request->userAgent(),
        ]);

        $model->setAttribute('raw_token', $raw);
        return $model;
    }

    public function verifyAccessToken(string $token): object
    {
        $secret = $this->jwtSecret();
        return JWT::decode($token, new Key($secret, 'HS256'));
    }

    public function hashRefreshToken(string $rawToken): string
    {
        return hash('sha256', $rawToken);
    }

    public function revokeRefreshToken(RefreshToken $refreshToken): void
    {
        $refreshToken->update(['revoked' => true]);
    }

    public function refresh(string $rawRefreshToken, User $user, Request $request): array
    {
        $tokenHash = $this->hashRefreshToken($rawRefreshToken);

        /** @var RefreshToken|null $existing */
        $existing = RefreshToken::query()
            ->where('user_id', $user->id)
            ->where('token_hash', $tokenHash)
            ->first();

        if (!$existing) {
            throw new \RuntimeException('Invalid refresh token');
        }

        if ($existing->revoked) {
            throw new \RuntimeException('Refresh token revoked');
        }

        if ($existing->expires_at instanceof \DateTimeInterface) {
            if ($existing->expires_at->getTimestamp() < time()) {
                throw new \RuntimeException('Refresh token expired');
            }
        }

        // Rotate
        $this->revokeRefreshToken($existing);
        $new = $this->issueRefreshToken($user, $request);

        $access = $this->issueAccessToken($user);

        return [
            'access_token' => $access,
            'refresh_token' => (string) $new->getAttribute('raw_token'),
            'token_type' => 'Bearer',
            'expires_in' => $this->accessTtlSeconds,
        ];
    }
}

