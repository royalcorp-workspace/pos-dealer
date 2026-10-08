@component('mail::message')
# ✨ Verifikasi Akun Anda

Halo **{{ $email }}**,

Selamat datang di **IMG (International Mattress Gallery)**! Kami senang Anda bergabung dengan kami untuk pengalaman istirahat terbaik.

Untuk mengamankan akun dan mengaktifkan akses belanja Anda, silakan lakukan konfirmasi alamat email dengan menekan tombol elegan di bawah ini:

@component('mail::button', ['url' => $verifyUrl])
Konfirmasi Alamat Email
@endcomponent

<div style="background-color: #fdfbf7; border: 1px solid #f2ebd9; border-radius: 8px; padding: 12px 16px; margin: 20px 0; font-size: 13px; color: #71717a;">
    <strong style="color: #2b1d12;">Catatan Keamanan:</strong> Tautan verifikasi ini hanya berlaku selama <strong>24 jam</strong>. Jika Anda tidak pernah mendaftarkan akun di IMG, silakan abaikan email ini dengan aman.
</div>

Jika Anda mengalami kendala saat menekan tombol di atas, salin dan buka tautan berikut di browser Anda:  
[{{ $verifyUrl }}]({{ $verifyUrl }})

Salam hangat,  
**Tim IMG Mattress Gallery**
@endcomponent
