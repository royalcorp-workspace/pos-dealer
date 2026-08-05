
// Helper for Alpine @click in auth modal
window.handleGoogleSignInFromModal = function(e) {
    var submitBtn = e.currentTarget || e.target.closest('button') || e.target;
    if (!firebase || !firebase.apps.length) {
        document.dispatchEvent(new CustomEvent('show-auth-toast', {
            detail: { message: 'Firebase belum dikonfigurasi', type: 'error' }
        }));
        return;
    }

    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.style.opacity = '0.6';
    }

    var provider = new firebase.auth.GoogleAuthProvider();
    firebase.auth().signInWithPopup(provider)
        .then(function(result) {
            return result.user.getIdToken().then(function(idToken) {
                var isJwt = idToken && idToken.startsWith('eyJ');
                if (!isJwt) {
                    document.dispatchEvent(new CustomEvent('show-auth-toast', {
                        detail: { message: 'Firebase error: Token format invalid', type: 'error' }
                    }));
                    return;
                }
                // Send Firebase ID Token as firebase_token for your separate backend validation
                var firebaseToken = idToken; // Firebase JWT for authentication
                return fetch('/api/auth/google', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        id_token: idToken,
                        firebase_token: firebaseToken
                    })
                }).then(function(response) {
                    return response.json().then(function(data) {
                        return { ok: response.ok, data: data };
                    });
                });
            });
        })
        .then(function(result) {
            if (result.ok) {
                fetch('/auth/google/session', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        access_token: result.data.access_token,
                        refresh_token: result.data.refresh_token
                    })
                }).then(function() {
                    var isCheckout = window.location.pathname.includes('checkout');
                    if (isCheckout) {
                        var checkoutForm = document.getElementById('checkout-form');
                        if (checkoutForm) {
                            document.querySelector('body').__x.$data.isAuthOpen = false;
                            setTimeout(function() { checkoutForm.submit(); }, 1000);
                        } else {
                            window.location.href = '/dashboard';
                        }
                    } else {
                        window.location.href = '/dashboard';
                    }
                }).catch(function() {
                    window.location.href = '/dashboard';
                });
            } else if (result.data && result.data.action === 'register') {
                var params = {
                    email: result.data.user.email,
                    name: result.data.user.name,
                    google_id: result.data.user.google_id,
                };
                if (result.data.user.firebase_token) {
                    params.firebase_token = result.data.user.firebase_token;
                }
                var q = new URLSearchParams(params).toString();
                window.location.href = '/register?' + q;
            } else if (result.data && result.data.action === 'conflict') {
                document.dispatchEvent(new CustomEvent('show-auth-toast', {
                    detail: { message: result.data.message || 'Akun Google tidak cocok', type: 'error' }
                }));
            } else {
                document.dispatchEvent(new CustomEvent('show-auth-toast', {
                    detail: { message: result.data.message || 'Login Google gagal', type: 'error' }
                }));
            }
        })
        .catch(function(error) {
            var msg = error.message || 'Login Google gagal';
            document.dispatchEvent(new CustomEvent('show-auth-toast', {
                detail: { message: msg, type: 'error' }
            }));
        })
        .finally(function() {
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.style.opacity = '';
            }
        });
};

// Also support legacy data-google-signin attribute (for backward compatibility)
var googleSignInHandler = function(e) {
    window.handleGoogleSignInFromModal(e);
};

window.initFirebaseGoogleSignIn = function() {
    var googleButtons = document.querySelectorAll('button[data-google-signin]');
    googleButtons.forEach(function(btn) {
        btn.removeEventListener('click', googleSignInHandler);
        btn.addEventListener('click', googleSignInHandler);
    });
};

document.addEventListener('alpine:initialized', function() {
    // Delay to ensure function is registered
    setTimeout(function() {
        if (typeof window.initFirebaseGoogleSignIn === 'function') {
            window.initFirebaseGoogleSignIn();
        }
    }, 0);
});

document.addEventListener('DOMContentLoaded', function() {
    window.initFirebaseGoogleSignIn();
});

// Watch for Alpine isAuthOpen changes - rebind when modal opens
var lastAuthOpenState = false;
setInterval(function() {
    var modalEl = document.querySelector('[x-data*="isAuthOpen"]');
    if (modalEl && modalEl.__x && modalEl.__x.$data) {
        var currentAuthOpen = modalEl.__x.$data.isAuthOpen;
        if (currentAuthOpen !== null && currentAuthOpen !== lastAuthOpenState && currentAuthOpen === true) {
            lastAuthOpenState = currentAuthOpen;
            window.initFirebaseGoogleSignIn();
        }
    }
}, 200);

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
            window.dispatchEvent(new CustomEvent('auth-error-login-clear'));

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
                var msg = result.data.message || 'Login gagal. Periksa email dan password Anda.';
                if (result.data.errors) {
                    var fieldErrors = [];
                    for (var field in result.data.errors) {
                        if (result.data.errors[field] && result.data.errors[field][0]) {
                            fieldErrors.push(result.data.errors[field][0]);
                        }
                    }
                    if (fieldErrors.length > 0) msg = fieldErrors.join('<br>');
                }
                window.dispatchEvent(new CustomEvent('auth-error-login', {
                    detail: { message: msg }
                }));
            })
            .catch(function () {
                window.dispatchEvent(new CustomEvent('auth-error-login', {
                    detail: { message: 'Terjadi kesalahan jaringan. Silakan coba lagi.' }
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
                    window.dispatchEvent(new CustomEvent('auth-error-forgot', { detail: { message: res.data.message || 'Gagal mengirim kode OTP' }}));
                }
            })
            .catch(function () {
                submitBtn.disabled = false;
                submitBtn.querySelector('span').textContent = 'Lanjutkan';
                window.dispatchEvent(new CustomEvent('auth-error-forgot', { detail: { message: 'Terjadi kesalahan jaringan' }}));
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
                    var msg = res.data.message || 'Register gagal';
                    if (res.data.errors) {
                        var fieldErrors = [];
                        for (var field in res.data.errors) {
                            if (res.data.errors[field] && res.data.errors[field][0]) {
                                fieldErrors.push(res.data.errors[field][0]);
                            }
                        }
                        if (fieldErrors.length > 0) msg = fieldErrors.join('<br>');
                    }
                    window.dispatchEvent(new CustomEvent('auth-error-register', {
                        detail: { message: msg }
                    }));
                }
            })
            .catch(function () {
                window.dispatchEvent(new CustomEvent('auth-error-register', {
                    detail: { message: 'Terjadi kesalahan jaringan' }
                }));
            })
            .finally(function () {
                submitBtn.disabled = false;
                submitBtn.querySelector('span').textContent = 'Create Account';
            });
        });
    }
});
