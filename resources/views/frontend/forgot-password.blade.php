@extends('frontend.layouts.app')

@section('title', 'Lupa Password - IMG')
@section('robots', 'noindex,nofollow')

@section('content')
    <div class="min-h-screen bg-brand-light/40 flex items-center justify-center px-4 py-12">
        <div class="w-full max-w-md bg-white rounded-3xl shadow-xl p-8 sm:p-10">
            <div class="text-center mb-8">
                <h1 class="text-3xl font-extrabold text-brand-dark tracking-tight">Lupa Password</h1>
                <p class="text-gray-500 text-sm mt-3">Masukkan email Anda, kami akan kirimkan kode OTP untuk mereset password.</p>
            </div>

            <form id="forgotPasswordPageForm" class="space-y-5" method="POST">
                @csrf
                @if($showRegister)
                    <p class="text-xs text-gray-500">Akun berhasil dibuat. Silakan cek email untuk verifikasi.</p>
                @endif
                <div>
                    <label class="block text-xs font-bold text-brand-darker uppercase tracking-wider mb-2">Email Address</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M3 7.5v9a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-9a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2Z" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/><path d="m3 7.5 9 6 9-6" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </div>
                        <input
                            type="email"
                            name="email"
                            required
                            placeholder="you@example.com"
                            class="w-full pl-11 pr-4 py-3 bg-brand-light border border-brand-muted rounded-xl text-gray-800 focus:outline-none focus:ring-2 focus:ring-brand-gold/50 focus:border-brand-gold transition-colors"
                        />
                    </div>
                </div>

                <button
                    type="submit"
                    id="forgotPasswordSubmitBtn"
                    class="w-full py-3.5 bg-brand-dark hover:bg-brand-darker text-brand-gold font-bold rounded-xl shadow-lg shadow-brand-dark/20 transition-transform active:scale-[0.98] focus:outline-none"
                >
                    <span class="submit-text">Kirim Kode OTP</span>
                    <span class="loading-text hidden">Memproses...</span>
                </button>
            </form>

            <div class="mt-6 text-center">
                <a href="{{ route('home') }}" class="text-sm font-semibold text-brand-gold hover:text-brand-dark transition-colors">&larr; Kembali ke Beranda</a>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('forgotPasswordPageForm');
            if (!form) return;
            
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const formData = new FormData(e.target);
                const submitBtn = e.target.querySelector('button[type="submit"]');
                const submitText = submitBtn.querySelector('.submit-text');
                const loadingText = submitBtn.querySelector('.loading-text');
                
                const resetLoading = () => {
                    submitBtn.disabled = false;
                    submitText.classList.remove('hidden');
                    loadingText.classList.add('hidden');
                };
                
                submitBtn.disabled = true;
                submitText.classList.add('hidden');
                loadingText.classList.remove('hidden');
                
                const email = formData.get('email');
                const csrfToken = document.querySelector('meta[name=csrf-token]');
                if (!csrfToken) {
                    alert('CSRF token tidak ditemukan');
                    resetLoading();
                    return;
                }
                
                fetch(window.location.origin + '/api/auth/forgot-password', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken.getAttribute('content'),
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        email: email,
                        channel: 'email'
                    })
                })
                .then(async r => {
                    const status = r.status;
                    const contentType = r.headers.get('content-type');
                    let data;
                    try {
                        data = await r.json();
                    } catch (err) {
                        throw new Error('Response bukan JSON (status: ' + status + ')');
                    }
                    return { ok: r.ok, status, data };
                })
                .then(({ ok, status, data }) => {
                    console.log('API Response:', { ok, status, data });
                    if (ok && (data.success === true || data.message)) {
                        window.location.href = window.location.origin + '/password-otp-sent?email=' + encodeURIComponent(email);
                    } else {
                        alert(data.message || 'Gagal mengirim kode OTP (status: ' + status + ')');
                        resetLoading();
                    }
                })
                .catch(err => {
                    console.error('Fetch error:', err);
                    alert('Terjadi kesalahan: ' + err.message);
                    resetLoading();
                });
            });
        });
    </script>
@endsection
