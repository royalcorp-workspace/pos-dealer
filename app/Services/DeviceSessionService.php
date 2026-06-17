<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\DeviceSession;
use App\Models\RefreshToken;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class DeviceSessionService
{
    public const MAX_DEVICES = 5;

    private const SESSION_KEY = 'device_session_id';

    public function deviceId(Request $request): string
    {
        $id = (string) $request->session()->get(self::SESSION_KEY, '');

        if ($id === '') {
            $id = (string) Str::uuid();
            $request->session()->put(self::SESSION_KEY, $id);
        }

        return $id;
    }

    public function register(
        Request $request,
        ?User $user = null,
        ?string $userEmail = null,
        ?string $sessionId = null,
        ?string $refreshTokenId = null
    ): DeviceSession {
        $deviceId = $this->deviceId($request);
        $email = $userEmail !== null && trim($userEmail) !== '' ? $userEmail : null;

        return DeviceSession::query()->updateOrCreate(
            ['id' => $deviceId],
            [
                'user_id' => $user?->getKey(),
                'user_email' => $email,
                'session_id' => $sessionId ?: $request->session()->getId(),
                'refresh_token_id' => $refreshTokenId,
                'device_name' => $this->deviceName($request),
                'device_type' => $this->deviceType($request),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'last_active_at' => now(),
            ]
        );
    }

    public function markActive(Request $request): void
    {
        DeviceSession::query()
            ->where('id', $this->deviceId($request))
            ->update([
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'last_active_at' => now(),
            ]);
    }

    public function list(
        ?User $user = null,
        ?string $userEmail = null,
        ?string $currentDeviceId = null
    ): Collection {
        return $this->queryFor($user, $userEmail)
            ->orderByDesc('last_active_at')
            ->orderByDesc('created_at')
            ->get()
            ->map(function (DeviceSession $device) use ($currentDeviceId) {
                return [
                    'id' => $device->id,
                    'user_id' => $device->user_id,
                    'user_email' => $device->user_email,
                    'device_name' => $device->device_name,
                    'device_type' => $device->device_type,
                    'ip_address' => $device->ip_address,
                    'user_agent' => $device->user_agent,
                    'last_active_at' => $device->last_active_at,
                    'created_at' => $device->created_at,
                    'is_current' => $device->id === $currentDeviceId,
                ];
            });
    }

    public function enforceLimit(
        ?User $user = null,
        ?string $userEmail = null,
        ?string $currentDeviceId = null
    ): int {
        $query = $this->queryFor($user, $userEmail);
        $count = $query->count();

        if ($count <= self::MAX_DEVICES) {
            return 0;
        }

        $revoked = $query
            ->where('id', '!=', $currentDeviceId)
            ->orderBy('created_at')
            ->orderBy('id')
            ->limit($count - self::MAX_DEVICES)
            ->get();

        foreach ($revoked as $device) {
            $this->deleteSession($device->session_id);
            RefreshToken::query()
                ->where('device_id', $device->id)
                ->update(['revoked' => true]);
            $device->delete();
        }

        return $revoked->count();
    }

    public function remove(string $deviceId, ?string $currentDeviceId = null, bool $forceCurrent = false): bool
    {
        if ($deviceId === $currentDeviceId && !$forceCurrent) {
            return false;
        }

        $device = DeviceSession::query()->find($deviceId);
        if (!$device) {
            return false;
        }

        $this->deleteSession($device->session_id);
        RefreshToken::query()
            ->where('device_id', $device->id)
            ->update(['revoked' => true]);
        $device->delete();

        return true;
    }

    public function removeCurrent(Request $request): void
    {
        $deviceId = $this->deviceId($request);
        $device = DeviceSession::query()->find($deviceId);

        if ($device) {
            $this->deleteSession($device->session_id);
            RefreshToken::query()
                ->where('device_id', $device->id)
                ->update(['revoked' => true]);
            $device->delete();
        }

        $request->session()->forget(self::SESSION_KEY);
    }

    private function queryFor(?User $user = null, ?string $userEmail = null): Builder
    {
        return DeviceSession::query()->where(function ($query) use ($user, $userEmail): void {
            if ($user) {
                $query->where('user_id', $user->getKey());
            } elseif ($userEmail !== null && trim($userEmail) !== '') {
                $query->where('user_email', $userEmail);
            } else {
                $query->whereNull('user_id')->whereNull('user_email');
            }
        });
    }

    private function deleteSession(?string $sessionId): void
    {
        if ($sessionId === null || $sessionId === '') {
            return;
        }

        try {
            DB::table((string) config('session.table', 'sessions'))
                ->where('id', $sessionId)
                ->delete();
        } catch (Throwable) {
        }
    }

    private function deviceName(Request $request): string
    {
        $agent = (string) $request->userAgent();

        if ($agent === '') {
            return 'Unknown Browser';
        }

        $os = 'Browser';
        if (str_contains($agent, 'Android')) {
            $os = 'Android';
        } elseif (str_contains($agent, 'iPhone')) {
            $os = 'iPhone';
        } elseif (str_contains($agent, 'iPad')) {
            $os = 'iPad';
        } elseif (str_contains($agent, 'Macintosh')) {
            $os = 'macOS';
        } elseif (str_contains($agent, 'Windows NT')) {
            $os = 'Windows';
        } elseif (str_contains($agent, 'Linux')) {
            $os = 'Linux';
        }

        return $os . ' Browser';
    }

    private function deviceType(Request $request): string
    {
        $agent = (string) $request->userAgent();

        if (str_contains($agent, 'Mobile') || str_contains($agent, 'Android') || str_contains($agent, 'iPhone')) {
            return 'mobile';
        }

        if (str_contains($agent, 'iPad') || str_contains($agent, 'Tablet')) {
            return 'tablet';
        }

        if (str_contains($agent, 'Windows') || str_contains($agent, 'Macintosh') || str_contains($agent, 'Linux')) {
            return 'desktop';
        }

        return 'unknown';
    }
}
