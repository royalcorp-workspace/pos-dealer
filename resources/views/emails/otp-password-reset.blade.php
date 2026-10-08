@component('mail::message')
# 🔐 Permintaan Reset Password

Halo **{{ $email }}**,

Kami menerima permintaan untuk mengatur ulang kata sandi akun Anda di **IMG (International Mattress Gallery)**.

Gunakan kode verifikasi (OTP) berikut untuk melanjutkan proses reset password:

@component('mail::panel')
<div style="text-align: center; padding: 10px 0;">
    <span style="font-family: 'Courier New', Courier, monospace; font-size: 32px; font-weight: 800; letter-spacing: 8px; color: #2b1d12; background: #ffffff; padding: 8px 18px; border-radius: 8px; border: 2px dashed #c09d6b; display: inline-block;">
        {{ $otpCode }}
    </span>
    <p style="font-size: 12px; color: #ad8a58; margin: 10px 0 0; font-weight: 600;">
        ⏱️ Berlaku selama {{ $expiresMinutes }} menit
    </p>
</div>
@endcomponent

<div style="background-color: #fdfbf7; border: 1px solid #f2ebd9; border-radius: 8px; padding: 12px 16px; margin: 20px 0; font-size: 13px; color: #71717a;">
    <strong style="color: #2b1d12;">Demi Keamanan:</strong> Jangan berikan kode OTP ini kepada siapa pun, termasuk staf atau pihak yang mengatasnamakan IMG. Jika Anda tidak merasa meminta reset kata sandi, abaikan email ini dan akun Anda tetap aman.
</div>

Salam hangat,  
**Tim Keamanan Akun IMG**
@endcomponent
