@extends('frontend.layouts.app')

@section('title', 'Reset Password - IMG')
@section('robots', 'noindex,nofollow')

@section('content')
    <div class="min-h-screen bg-brand-light/40 flex items-center justify-center px-4 py-12">
        <div class="w-full max-w-md bg-white rounded-3xl shadow-xl p-8 sm:p-10 border border-brand-muted/40 relative">
            <div class="text-center mb-6">
                <div class="w-14 h-14 bg-brand-light rounded-2xl flex items-center justify-center mx-auto mb-3 text-brand-dark shadow-sm border border-brand-muted/60">
                    <svg class="w-7 h-7 text-brand-gold" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                </div>
                <h1 class="text-2xl font-extrabold text-brand-dark tracking-tight">Atur Ulang Password</h1>
                <p class="text-gray-500 text-xs sm:text-sm mt-2">
                    Masukkan 6 digit kode OTP yang telah dikirim ke email <strong class="text-brand-dark">{{ $email }}</strong> dan buat password baru Anda.
                </p>
            </div>

            <!-- Server Flash Messages -->
            @if(session('error'))
                <div id="serverErrorAlert" class="mb-5 p-4 bg-amber-50 border border-amber-200/80 rounded-2xl flex items-start gap-3 text-amber-900 shadow-sm animate-fade-in">
                    <svg class="w-5 h-5 text-amber-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    <div class="flex-1 text-xs sm:text-sm">
                        <p class="font-bold text-amber-950">Peringatan Kode OTP</p>
                        <p class="mt-0.5 text-amber-800 leading-relaxed">{{ session('error') }}</p>
                    </div>
                </div>
            @endif

            @if(session('success'))
                <div class="mb-5 p-4 bg-emerald-50 border border-emerald-200 rounded-2xl flex items-start gap-3 text-emerald-900 shadow-sm">
                    <svg class="w-5 h-5 text-emerald-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <div class="flex-1 text-xs sm:text-sm">
                        <p class="font-bold text-emerald-950">Berhasil</p>
                        <p class="mt-0.5 text-emerald-800">{{ session('success') }}</p>
                    </div>
                </div>
            @endif

            <!-- Dynamic Interactive Warning / Error Notification Card -->
            <div id="dynamicWarningAlert" class="hidden mb-5 p-4 bg-amber-50 border border-amber-300 rounded-2xl flex items-start gap-3 text-amber-900 shadow-sm transition-all">
                <svg class="w-5 h-5 text-amber-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                <div class="flex-1 text-xs sm:text-sm">
                    <p class="font-bold text-amber-950 flex items-center justify-between">
                        <span>Peringatan Kode OTP</span>
                        <button type="button" onclick="closeDynamicWarning()" class="text-amber-500 hover:text-amber-800 text-xs font-semibold">&times;</button>
                    </p>
                    <p id="dynamicWarningMessage" class="mt-1 text-amber-800 leading-relaxed"></p>
                    <div class="mt-2.5 pt-2 border-t border-amber-200/60 flex items-center justify-between">
                        <span class="text-[11px] text-amber-700">Kode belum masuk atau salah?</span>
                        <button type="button" onclick="resendOtpInline(this)" class="text-xs font-bold text-brand-dark underline hover:text-brand-gold transition-colors">
                            Kirim Ulang OTP
                        </button>
                    </div>
                </div>
            </div>

            <!-- Dynamic Success Card -->
            <div id="dynamicSuccessAlert" class="hidden mb-5 p-4 bg-emerald-50 border border-emerald-200 rounded-2xl flex items-start gap-3 text-emerald-900 shadow-sm transition-all">
                <svg class="w-5 h-5 text-emerald-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <div class="flex-1 text-xs sm:text-sm">
                    <p class="font-bold text-emerald-950">Berhasil</p>
                    <p id="dynamicSuccessMessage" class="mt-0.5 text-emerald-800 leading-relaxed"></p>
                </div>
            </div>

            <form id="resetPasswordForm" class="space-y-4" action="{{ route('reset-password.process') }}" method="POST">
                @csrf
                <input type="hidden" name="email" value="{{ $email }}">
                <input type="hidden" name="channel" value="email">
                
                <!-- OTP Code -->
                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label class="block text-xs font-bold text-brand-darker uppercase tracking-wider">Kode OTP (6 Digit)</label>
                        <button type="button" id="resendOtpBtn" onclick="resendOtpInline(this)" class="text-[11px] font-bold text-brand-gold hover:text-brand-dark transition-colors">
                            Kirim Ulang OTP
                        </button>
                    </div>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                            </svg>
                        </div>
                        <input
                            type="text"
                            name="otp_code"
                            id="otpCodeInput"
                            required
                            autofocus
                            placeholder="Contoh: 123456"
                            maxlength="6"
                            value="{{ old('otp_code') }}"
                            autocomplete="one-time-code"
                            inputmode="numeric"
                            class="w-full pl-11 pr-4 py-3 bg-brand-light border border-brand-muted rounded-xl text-brand-dark font-mono text-center text-lg font-bold tracking-widest focus:outline-none focus:ring-2 focus:ring-brand-gold/50 focus:border-brand-gold transition-all"
                        />
                    </div>
                    <p class="text-[11px] text-gray-400 mt-1">Periksa email Anda (termasuk folder Spam/Promosi) untuk mendapatkan kode.</p>
                </div>

                <!-- New Password -->
                <div>
                    <label class="block text-xs font-bold text-brand-darker uppercase tracking-wider mb-1.5">Password Baru</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
                                <rect x="5" y="11" width="14" height="9" rx="2"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/>
                            </svg>
                        </div>
                        <input
                            type="password"
                            name="new_password"
                            id="newPasswordInput"
                            required
                            placeholder="Minimal 6 karakter"
                            minlength="6"
                            class="w-full pl-11 pr-11 py-3 bg-brand-light border border-brand-muted rounded-xl text-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-brand-gold/50 focus:border-brand-gold transition-colors"
                        />
                        <button type="button" onclick="togglePasswordVisibility('newPasswordInput', this)" class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-gray-400 hover:text-gray-600 focus:outline-none">
                            <svg class="h-4 w-4 eye-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Confirm Password -->
                <div>
                    <label class="block text-xs font-bold text-brand-darker uppercase tracking-wider mb-1.5">Konfirmasi Password Baru</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
                                <rect x="5" y="11" width="14" height="9" rx="2"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/>
                            </svg>
                        </div>
                        <input
                            type="password"
                            name="new_password_confirmation"
                            id="confirmPasswordInput"
                            required
                            placeholder="Ulangi password baru"
                            class="w-full pl-11 pr-11 py-3 bg-brand-light border border-brand-muted rounded-xl text-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-brand-gold/50 focus:border-brand-gold transition-colors"
                        />
                        <button type="button" onclick="togglePasswordVisibility('confirmPasswordInput', this)" class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-gray-400 hover:text-gray-600 focus:outline-none">
                            <svg class="h-4 w-4 eye-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="pt-2">
                    <button
                        type="submit"
                        id="resetPasswordSubmitBtn"
                        class="w-full py-3.5 bg-brand-dark hover:bg-brand-darker text-brand-gold font-bold rounded-xl shadow-lg shadow-brand-dark/20 transition-all active:scale-[0.98] focus:outline-none disabled:opacity-60 disabled:cursor-not-allowed flex items-center justify-center gap-2"
                    >
                        <span id="submitBtnText">Reset Password Sekarang</span>
                        <div id="submitBtnSpinner" class="hidden w-4 h-4 border-2 border-brand-gold border-t-transparent rounded-full animate-spin"></div>
                    </button>
                </div>
            </form>

            <div class="mt-6 pt-5 border-t border-brand-muted/40 text-center flex flex-col gap-2">
                <a href="{{ route('forgot-password.show') }}" class="text-xs font-semibold text-brand-dark hover:text-brand-gold transition-colors">
                    &larr; Ganti alamat email
                </a>
                <a href="{{ route('home') }}" class="text-xs text-gray-500 hover:text-brand-dark transition-colors">
                    Kembali ke Beranda
                </a>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
function togglePasswordVisibility(inputId, btn) {
    const input = document.getElementById(inputId);
    if (!input) return;
    if (input.type === 'password') {
        input.type = 'text';
        btn.classList.add('text-brand-gold');
        btn.classList.remove('text-gray-400');
    } else {
        input.type = 'password';
        btn.classList.remove('text-brand-gold');
        btn.classList.add('text-gray-400');
    }
}

function showDynamicWarning(message) {
    const box = document.getElementById('dynamicWarningAlert');
    const msgEl = document.getElementById('dynamicWarningMessage');
    const successBox = document.getElementById('dynamicSuccessAlert');
    const otpInput = document.getElementById('otpCodeInput');
    
    if (successBox) successBox.classList.add('hidden');

    if (box && msgEl) {
        msgEl.textContent = message || 'Kode OTP salah atau sudah kedaluwarsa. Silakan periksa kembali atau minta kode OTP baru.';
        box.classList.remove('hidden');
    }

    if (otpInput) {
        otpInput.classList.add('border-red-400', 'bg-red-50/40', 'ring-2', 'ring-red-200');
        otpInput.focus();
        otpInput.select();
    }

    // Trigger styled warning popup using SweetAlert2
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            icon: 'warning',
            title: 'Kode OTP Tidak Valid',
            text: message || 'Kode OTP salah atau sudah kedaluwarsa. Silakan periksa kembali atau minta kode OTP baru.',
            confirmButtonColor: '#1e3a8a',
            confirmButtonText: 'Mengerti',
            customClass: {
                popup: 'rounded-2xl shadow-xl'
            }
        });
    } else {
        window.dispatchEvent(new CustomEvent('show-toast', {
            detail: { type: 'warning', message: message }
        }));
    }
}

function closeDynamicWarning() {
    const box = document.getElementById('dynamicWarningAlert');
    if (box) box.classList.add('hidden');
    const otpInput = document.getElementById('otpCodeInput');
    if (otpInput) {
        otpInput.classList.remove('border-red-400', 'bg-red-50/40', 'ring-2', 'ring-red-200');
    }
}

let resendCooldown = 0;
function resendOtpInline(btn) {
    if (resendCooldown > 0) return;

    const email = document.querySelector('input[name="email"]')?.value || '{{ $email }}';
    if (!email) {
        window.dispatchEvent(new CustomEvent('show-toast', { detail: { type: 'error', message: 'Alamat email tidak ditemukan.' } }));
        return;
    }

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || document.querySelector('input[name="_token"]')?.value || '';
    const originalText = btn ? btn.textContent : '';

    if (btn) {
        btn.disabled = true;
        btn.textContent = 'Mengirim...';
    }

    fetch('{{ url("/api/auth/forgot-password") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrfToken
        },
        body: JSON.stringify({ email: email, channel: 'email' })
    })
    .then(r => r.json().then(data => ({ ok: r.ok, data })))
    .then(({ ok, data }) => {
        if (ok && data.success !== false) {
            closeDynamicWarning();
            const successBox = document.getElementById('dynamicSuccessAlert');
            const successMsg = document.getElementById('dynamicSuccessMessage');
            if (successBox && successMsg) {
                successMsg.textContent = 'Kode OTP baru telah berhasil dikirim ke email Anda. Silakan cek kotak masuk.';
                successBox.classList.remove('hidden');
            }

            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'success',
                    title: 'OTP Terkirim!',
                    text: 'Kode OTP baru telah berhasil dikirim ke ' + email,
                    confirmButtonColor: '#1e3a8a',
                    confirmButtonText: 'OK',
                    customClass: { popup: 'rounded-2xl shadow-xl' }
                });
            } else {
                window.dispatchEvent(new CustomEvent('show-toast', { detail: { type: 'success', message: 'Kode OTP baru telah dikirim ke email Anda.' } }));
            }

            // Start cooldown 60s
            resendCooldown = 60;
            const timer = setInterval(() => {
                resendCooldown--;
                if (btn) btn.textContent = 'Kirim Ulang (' + resendCooldown + 's)';
                const resendTopBtn = document.getElementById('resendOtpBtn');
                if (resendTopBtn && resendTopBtn !== btn) resendTopBtn.textContent = 'Kirim Ulang (' + resendCooldown + 's)';
                if (resendCooldown <= 0) {
                    clearInterval(timer);
                    if (btn) {
                        btn.disabled = false;
                        btn.textContent = 'Kirim Ulang OTP';
                    }
                    if (resendTopBtn) {
                        resendTopBtn.disabled = false;
                        resendTopBtn.textContent = 'Kirim Ulang OTP';
                    }
                }
            }, 1000);
        } else {
            if (btn) {
                btn.disabled = false;
                btn.textContent = originalText;
            }
            showDynamicWarning(data.message || 'Gagal mengirim ulang kode OTP.');
        }
    })
    .catch(err => {
        if (btn) {
            btn.disabled = false;
            btn.textContent = originalText;
        }
        showDynamicWarning('Terjadi gangguan jaringan saat mengirim ulang kode OTP.');
    });
}

document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('resetPasswordForm');
    const submitBtn = document.getElementById('resetPasswordSubmitBtn');
    const btnText = document.getElementById('submitBtnText');
    const btnSpinner = document.getElementById('submitBtnSpinner');
    const otpInput = document.getElementById('otpCodeInput');

    if (otpInput) {
        otpInput.addEventListener('input', function() {
            this.classList.remove('border-red-400', 'bg-red-50/40', 'ring-2', 'ring-red-200');
        });
    }

    if (form) {
        form.addEventListener('submit', async function(e) {
            e.preventDefault();

            const otpVal = (form.otp_code?.value || '').trim();
            const newPass = form.new_password?.value || '';
            const confirmPass = form.new_password_confirmation?.value || '';

            if (!otpVal) {
                showDynamicWarning('Silakan masukkan 6 digit kode OTP terlebih dahulu.');
                return;
            }
            if (newPass.length < 6) {
                showDynamicWarning('Password baru minimal terdiri dari 6 karakter.');
                return;
            }
            if (newPass !== confirmPass) {
                showDynamicWarning('Konfirmasi password tidak cocok dengan password baru.');
                return;
            }

            submitBtn.disabled = true;
            btnText.textContent = 'Memproses...';
            btnSpinner.classList.remove('hidden');

            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || form.querySelector('input[name="_token"]')?.value || '';

            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({
                        email: form.email.value,
                        otp_code: otpVal,
                        new_password: newPass,
                        new_password_confirmation: confirmPass,
                        channel: 'email'
                    })
                });

                const data = await response.json().catch(() => ({}));

                if (response.ok && data.success !== false) {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil!',
                            text: data.message || 'Password berhasil direset! Mengalihkan ke halaman masuk...',
                            showConfirmButton: false,
                            timer: 2000,
                            customClass: { popup: 'rounded-2xl shadow-xl' }
                        }).then(() => {
                            window.location.href = data.redirect || '{{ route("login.show") }}';
                        });
                    } else {
                        window.dispatchEvent(new CustomEvent('show-toast', {
                            detail: { type: 'success', message: data.message || 'Password berhasil direset!' }
                        }));
                        setTimeout(() => {
                            window.location.href = data.redirect || '{{ route("login.show") }}';
                        }, 1500);
                    }
                } else {
                    submitBtn.disabled = false;
                    btnText.textContent = 'Reset Password Sekarang';
                    btnSpinner.classList.add('hidden');

                    const errorMsg = data.message || 'Kode OTP salah atau sudah kedaluwarsa. Silakan periksa kembali.';
                    showDynamicWarning(errorMsg);
                }
            } catch (err) {
                submitBtn.disabled = false;
                btnText.textContent = 'Reset Password Sekarang';
                btnSpinner.classList.add('hidden');
                showDynamicWarning('Terjadi gangguan koneksi internet. Silakan coba lagi.');
            }
        });
    }
});
</script>
@endpush