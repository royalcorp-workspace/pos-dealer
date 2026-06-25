
document.addEventListener('DOMContentLoaded', function () {
    const loginForm = document.getElementById('loginModalForm');
    if (loginForm) {
        loginForm.addEventListener('submit', function (e) {
            e.preventDefault();
            const formData = new FormData(e.target);
            const submitBtn = e.target.querySelector('button[type="submit"]');
            const csrfToken = document.querySelector('meta[name="csrf-token"]');

            submitBtn.disabled = true;
            submitBtn.querySelector('span').textContent = 'Memproses...';

            fetch('/login', {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken ? csrfToken.getAttribute('content') : '',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            })
            .then(function (response) { return response.json().then(function (data) { return { ok: response.ok, data: data }; }); })
            .then(function (result) {
                if (result.ok && result.data.success) {
                    var isCheckout = window.location.pathname.includes('checkout');
                    document.querySelector('body').__x.$data.isAuthOpen = false;
                    setTimeout(function () {
                        if (isCheckout) {
                            var checkoutForm = document.getElementById('checkout-form');
                            if (checkoutForm) checkoutForm.submit();
                        } else {
                            window.location.href = '/dashboard';
                        }
                    }, 1000);
                    return;
                }
                window.dispatchEvent(new CustomEvent('show-auth-toast', {
                    detail: { message: result.data.message || 'Login gagal. Periksa email dan password Anda.', type: 'error' }
                }));
            })
            .catch(function () {
                window.dispatchEvent(new CustomEvent('show-auth-toast', {
                    detail: { message: 'Terjadi kesalahan jaringan. Silakan coba lagi.', type: 'error' }
                }));
            })
            .finally(function () {
                submitBtn.disabled = false;
                submitBtn.querySelector('span').textContent = 'Sign In';
            });
        });
    }

    const forgotForm = document.getElementById('forgotPasswordModalForm');
    if (forgotForm) {
        forgotForm.addEventListener('submit', function (e) {
            e.preventDefault();
            const formData = new FormData(e.target);
            const email = formData.get('email');
            const submitBtn = e.target.querySelector('button[type="submit"]');
            const csrfToken = document.querySelector('meta[name="csrf-token"]');

            submitBtn.disabled = true;
            submitBtn.querySelector('span').textContent = 'Memproses...';

            if (!csrfToken) {
                alert('CSRF token tidak ditemukan');
                submitBtn.disabled = false;
                submitBtn.querySelector('span').textContent = 'Lanjutkan';
                return;
            }

            fetch(window.location.origin + '/api/auth/forgot-password', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken.getAttribute('content'),
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ email: email, channel: 'email' })
            })
            .then(function (r) { return r.json().then(function (d) { return { ok: r.ok, data: d }; }); })
            .then(function (res) {
                submitBtn.disabled = false;
                submitBtn.querySelector('span').textContent = 'Lanjutkan';
                if (res.ok && (res.data.success || res.data.message)) {
                    window.location.href = window.location.origin + '/password-otp-sent?email=' + encodeURIComponent(email);
                } else {
                    window.dispatchEvent(new CustomEvent('show-auth-toast', { detail: { message: res.data.message || 'Gagal mengirim kode OTP', type: 'error' }}));
                }
            })
            .catch(function () {
                submitBtn.disabled = false;
                submitBtn.querySelector('span').textContent = 'Lanjutkan';
                window.dispatchEvent(new CustomEvent('show-auth-toast', { detail: { message: 'Terjadi kesalahan jaringan', type: 'error' }}));
            });
        });
    }
});

document.addEventListener('DOMContentLoaded', function () {
    const registerForm = document.getElementById('registerModalForm');
    if (registerForm) {
        registerForm.addEventListener('submit', function (e) {
            e.preventDefault();
            const formData = new FormData(e.target);
            const submitBtn = e.target.querySelector('button[type="submit"]');
            const csrfToken = document.querySelector('meta[name="csrf-token"]');

            submitBtn.disabled = true;
            submitBtn.querySelector('span').textContent = 'Memproses...';

            fetch(window.location.origin + '/api/auth/register', {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken ? csrfToken.getAttribute('content') : '',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            })
            .then(function (r) { return r.json().then(function (d) { return { ok: r.ok, data: d }; }); })
            .then(function (res) {
                if (res.ok) {
                    window.location.href = window.location.origin + '/register-success';
                } else {
                    window.dispatchEvent(new CustomEvent('show-auth-toast', { detail: { message: res.data.message || 'Register gagal', type: 'error' }}));
                }
            })
            .catch(function () {
                window.dispatchEvent(new CustomEvent('show-auth-toast', { detail: { message: 'Terjadi kesalahan jaringan', type: 'error' }}));
            })
            .finally(function () {
                submitBtn.disabled = false;
                submitBtn.querySelector('span').textContent = 'Create Account';
            });
        });
    }
});
