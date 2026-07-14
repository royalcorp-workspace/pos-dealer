<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\OtpPasswordResetMail;
use App\Models\PasswordReset;
use App\Models\User;
use App\Services\AuthAuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class PasswordResetController extends Controller
{
    public function __construct(private readonly AuthAuditService $audit)
    {
    }

    private function otpLength(): int
    {
        return 6;
    }

    private function otpTtlSeconds(): int
    {
        return 600; // 10 minutes
    }

    public function forgot(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'channel' => ['nullable', 'in:email,sms'],
        ]);

        $channel = $data['channel'] ?? 'email';
        $user = User::query()->where('email', $data['email'])->first();

        if ($user) {
            $otp = str_pad((string) random_int(0, (int) (10 ** $this->otpLength() - 1)), $this->otpLength(), '0', STR_PAD_LEFT);

            PasswordReset::create([
                'user_id' => (string) $user->getKey(),
                'otp_code' => $otp,
                'channel' => $channel,
                'used' => false,
                'expires_at' => now()->addSeconds($this->otpTtlSeconds()),
            ]);

            $this->audit->log($user, 'password_reset_request', $request, ['channel' => $channel]);

            if ($channel === 'email') {
                Mail::to($user->email)->send(new OtpPasswordResetMail(
                    $user->email,
                    $otp,
                    (int) ($this->otpTtlSeconds() / 60)
                ));
            }

            return response()->json(['  ' => 'If account exists, OTP has been sent', 'success' => true]);
        }

        return response()->json(['message' => 'If account exists, OTP has been sent', 'success' => true]);
    }

    public function reset(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'otp_code' => ['required', 'string'],
            'new_password' => ['required', 'string', 'min:6', 'confirmed'],
            'channel' => ['nullable', 'in:email,sms'],
        ]);

        $channel = $data['channel'] ?? 'email';
        $user = User::query()->where('email', $data['email'])->first();
        if (!$user) {
            return response()->json(['message' => 'Invalid OTP'], 422);
        }

        $reset = PasswordReset::query()
            ->where('user_id', $user->id)
            ->where('otp_code', $data['otp_code'])
            ->where('channel', $channel)
            ->where('used', false)
            ->first();

        if (!$reset || $reset->expires_at->getTimestamp() < time()) {
            return response()->json(['message' => 'OTP expired or invalid'], 422);
        }

        $reset->update(['used' => true]);

        $user->update([
            'password' => \Illuminate\Support\Facades\Hash::make($data['new_password']),
        ]);

        // Revoke refresh tokens on password reset
        // (Table exists in DB; we avoid dependency on model existence here by direct query.)
        \App\Models\RefreshToken::query()
            ->where('user_id', $user->id)
            ->update(['revoked' => true]);

        $this->audit->log($user, 'password_reset_success', $request, []);

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Password reset successful', 'success' => true]);
        }

        return redirect()->route('login')->with('success', 'Password berhasil direset! Silakan masuk dengan password baru.');
    }
}

