@component('mail::message')
# Verifikasi Email

Halo,

Silakan verifikasi email Anda dengan menekan tombol di bawah ini (token berlaku 24 jam):

@component('mail::button', ['url' => $verifyUrl])
Verifikasi Email
@endcomponent

Jika tombol tidak berfungsi, Anda bisa membuka link berikut:

{{ $verifyUrl }}

Terima kasih.
@endcomponent

