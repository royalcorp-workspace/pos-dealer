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
        $email = strtolower(trim($data['email']));
        $user = User::query()->whereRaw('LOWER(email) = ?', [$email])->first();

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
                try {
                    Mail::to($user->email)->send(new OtpPasswordResetMail(
                        $user->email,
                        $otp,
                        (int) ($this->otpTtlSeconds() / 60)
                    ));
                    \Illuminate\Support\Facades\Log::channel('email')->info("OTP Password Reset email sent successfully to {$user->email}");
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::channel('email')->error("Failed to send OTP email to {$user->email}: " . $e->getMessage(), [
                        'exception' => $e->getMessage(),
                    ]);
                    \Illuminate\Support\Facades\Log::error("Gagal mengirim email OTP ke {$user->email}: " . $e->getMessage());
                    return response()->json([
                        'message' => 'Gagal mengirim email kode OTP. Silakan periksa koneksi atau coba lagi nanti.',
                        'success' => false
                    ], 500);
                }
            }

            return response()->json(['message' => 'Kode OTP berhasil dikirim ke email Anda', 'success' => true]);
        }

        return response()->json(['message' => 'Jika akun terdaftar, kode OTP telah dikirimkan', 'success' => true]);
    }

    public function reset(Request $request)
    {
        $isJson = $request->expectsJson() || $request->isJson() || $request->ajax() || $request->wantsJson();

        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'email' => ['required', 'email'],
            'otp_code' => ['required', 'string'],
            'new_password' => ['required', 'string', 'min:6', 'confirmed'],
            'channel' => ['nullable', 'in:email,sms'],
        ], [
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'otp_code.required' => 'Kode OTP wajib diisi.',
            'new_password.required' => 'Password baru wajib diisi.',
            'new_password.min' => 'Password minimal terdiri dari 6 karakter.',
            'new_password.confirmed' => 'Konfirmasi password tidak cocok dengan password baru.',
        ]);

        if ($validator->fails()) {
            $errorMessage = $validator->errors()->first();
            if ($isJson) {
                return response()->json([
                    'success' => false,
                    'message' => $errorMessage,
                    'errors' => $validator->errors()
                ], 422);
            }
            return redirect()->back()
                ->withErrors($validator)
                ->withInput($request->except('new_password', 'new_password_confirmation'))
                ->with('error', $errorMessage);
        }

        $data = $validator->validated();
        $channel = $data['channel'] ?? 'email';
        $email = strtolower(trim($data['email']));
        $otpCode = trim($data['otp_code']);

        $user = User::query()->whereRaw('LOWER(email) = ?', [$email])->first();
        if (!$user) {
            $msg = 'Akun dengan email tersebut tidak ditemukan atau kode OTP tidak valid.';
            if ($isJson) {
                return response()->json([
                    'success' => false,
                    'message' => $msg
                ], 422);
            }
            return redirect()->back()
                ->withInput($request->except('new_password', 'new_password_confirmation'))
                ->with('error', $msg);
        }

        $reset = PasswordReset::query()
            ->where('user_id', $user->id)
            ->where('otp_code', $otpCode)
            ->where('used', false)
            ->latest('created_at')
            ->first();

        if (!$reset || $reset->expires_at->getTimestamp() < time()) {
            \Illuminate\Support\Facades\Log::channel('email')->warning("OTP verification failed for {$email}: Invalid or expired OTP code");
            $msg = 'Kode OTP salah atau sudah kedaluwarsa. Silakan periksa kembali kode OTP di email Anda atau minta kode OTP baru.';
            if ($isJson) {
                return response()->json([
                    'success' => false,
                    'message' => $msg
                ], 422);
            }
            return redirect()->back()
                ->withInput($request->except('new_password', 'new_password_confirmation'))
                ->with('error', $msg);
        }

        $reset->update(['used' => true]);

        $user->update([
            'password' => \Illuminate\Support\Facades\Hash::make($data['new_password']),
        ]);

        // Revoke refresh tokens on password reset
        \App\Models\RefreshToken::query()
            ->where('user_id', $user->id)
            ->update(['revoked' => true]);

        $this->audit->log($user, 'password_reset_success', $request, []);

        \Illuminate\Support\Facades\Log::channel('email')->info("Password reset completed successfully for {$email} via OTP");

        $successMsg = 'Password berhasil direset! Silakan masuk dengan password baru Anda.';

        if ($isJson) {
            return response()->json([
                'success' => true,
                'message' => $successMsg,
                'redirect' => route('login.show')
            ]);
        }

        return redirect()->route('login.show')->with('success', $successMsg);
    }
}

