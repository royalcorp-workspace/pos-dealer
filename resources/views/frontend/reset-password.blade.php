@extends('frontend.layouts.app')

@section('content')
    <div class="min-h-screen bg-brand-light/40 flex items-center justify-center px-4 py-12">
        <div class="w-full max-w-md bg-white rounded-3xl shadow-xl p-8 sm:p-10">
            <div class="text-center mb-8">
                <h1 class="text-3xl font-extrabold text-brand-dark tracking-tight">Reset Password</h1>
                <p class="text-gray-500 text-sm mt-3">Masukkan kode OTP dan password baru Anda.</p>
            </div>

            <form id="resetPasswordForm" class="space-y-5" action="/api/auth/reset-password" method="POST">
                @csrf
                
                <input type="hidden" name="email" value="{{ $email }}">
                
                <!-- OTP Code -->
                <div>
                    <label class="block text-xs font-bold text-brand-darker uppercase tracking-wider mb-2">Kode OTP</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M5 11h14M5 11l2-7h10l2 7" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/><path d="M7 11V7a5 5 0 0 1 10 0v4" stroke="currentColor" stroke-width="1.6"/></svg>
                        </div>
                        <input
                            type="text"
                            name="otp_code"
                            required
                            placeholder="Masukkan 6 digit kode OTP"
                            maxlength="6"
                            class="w-full pl-11 pr-4 py-3 bg-brand-light border border-brand-muted rounded-xl text-gray-800 focus:outline-none focus:ring-2 focus:ring-brand-gold/50 focus:border-brand-gold transition-colors"
                        />
                    </div>
                </div>

                <!-- New Password -->
                <div>
                    <label class="block text-xs font-bold text-brand-darker uppercase tracking-wider mb-2">Password Baru</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><rect x="5" y="11" width="14" height="9" rx="2" stroke="currentColor" stroke-width="1.6"/><path d="M8 11V8a4 4 0 0 1 8 0v3" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
                        </div>
                        <input
                            type="password"
                            name="new_password"
                            required
                            placeholder="Minimal 6 karakter"
                            minlength="6"
                            class="w-full pl-11 pr-4 py-3 bg-brand-light border border-brand-muted rounded-xl text-gray-800 focus:outline-none focus:ring-2 focus:ring-brand-gold/50 focus:border-brand-gold transition-colors"
                        />
                    </div>
                </div>

                <!-- Confirm Password -->
                <div>
                    <label class="block text-xs font-bold text-brand-darker uppercase tracking-wider mb-2">Konfirmasi Password</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><rect x="5" y="11" width="14" height="9" rx="2" stroke="currentColor" stroke-width="1.6"/><path d="M8 11V8a4 4 0 0 1 8 0v3" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
                        </div>
                        <input
                            type="password"
                            name="new_password_confirmation"
                            required
                            placeholder="Ulangi password baru"
                            class="w-full pl-11 pr-4 py-3 bg-brand-light border border-brand-muted rounded-xl text-gray-800 focus:outline-none focus:ring-2 focus:ring-brand-gold/50 focus:border-brand-gold transition-colors"
                        />
                    </div>
                </div>

                <button
                    type="submit"
                    class="w-full py-3.5 bg-brand-dark hover:bg-brand-darker text-brand-gold font-bold rounded-xl shadow-lg shadow-brand-dark/20 transition-transform active:scale-[0.98] focus:outline-none"
                >
                    Reset Password
                </button>
            </form>

            <div x-data="{ showSuccess: false, errorMessage: '' }" 
                 x-on:reset-success.window="showSuccess = true; setTimeout(() => { window.location.href = '/login'; }, 2000)"
                 x-on:reset-error.window="errorMessage = $event.detail"
                 class="mt-6 text-center">
                <template x-if="showSuccess">
                    <div class="p-4 bg-green-50 border border-green-200 rounded-xl">
                        <p class="text-green-700 font-semibold">Password berhasil direset! Mengalihkan ke halaman login...</p>
                    </div>
                </template>
                <template x-if="errorMessage">
                    <p class="text-red-500 text-sm" x-text="errorMessage"></p>
                </template>
                <a href="{{ route('forgot-password.show') }}" class="text-sm font-semibold text-brand-gold hover:text-brand-dark transition-colors">&larr; Kirim ulang kode OTP</a>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('resetPasswordForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const submitBtn = e.target.querySelector('button[type="submit"]');
            const formData = {
                email: this.email.value,
                otp_code: this.otp_code.value,
                new_password: this.new_password.value,
                new_password_confirmation: this.new_password_confirmation.value,
            };
            
            const csrfToken = document.querySelector('meta[name=csrf-token]');
            const controller = new AbortController();
            const timeoutId = setTimeout(() => controller.abort(), 15000);
            
            fetch(window.location.origin + '/api/auth/reset-password', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken ? csrfToken.getAttribute('content') : '',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(formData),
                signal: controller.signal
            })
            .then(r => {
                clearTimeout(timeoutId);
                const contentType = r.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    throw new Error('Response bukan JSON');
                }
                return r.json().then(d => ({ ok: r.ok, status: r.status, data: d }));
            })
            .then(({ ok, data }) => {
                if (ok) {
                    window.dispatchEvent(new CustomEvent('reset-success'));
                } else {
                    window.dispatchEvent(new CustomEvent('reset-error', { detail: data.message || 'Terjadi kesalahan' }));
                }
            })
            .catch(err => {
                clearTimeout(timeoutId);
                if (err.name === 'AbortError') {
                    window.dispatchEvent(new CustomEvent('reset-error', { detail: 'Request timeout' }));
                } else {
                    window.dispatchEvent(new CustomEvent('reset-error', { detail: err.message }));
                }
            });
        });
    </script>
@endsection