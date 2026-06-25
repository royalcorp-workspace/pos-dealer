@component('mail::message')
# Reset Password

Kami menerima permintaan reset password untuk akun Anda: **{{ $email }}**.

Berikut kode OTP Anda (berlaku selama **{{ $expiresMinutes }} menit**):

@component('mail::panel')
**{{ $otpCode }}**
@endcomponent

Jika Anda tidak meminta reset password, abaikan email ini.

Terima kasih.
@endcomponent

