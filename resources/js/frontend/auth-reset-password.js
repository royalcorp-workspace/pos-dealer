document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('resetPasswordForm');
    if (!form) return;

    form.addEventListener('submit', function (e) {
        e.preventDefault();

        const submitBtn = e.target.querySelector('button[type="submit"]');
        const formData = {
            email: form.email.value,
            otp_code: form.otp_code.value,
            new_password: form.new_password.value,
            new_password_confirmation: form.new_password_confirmation.value,
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
        .then((r) => {
            clearTimeout(timeoutId);
            const contentType = r.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                throw new Error('Response bukan JSON');
            }
            return r.json().then((d) => ({ ok: r.ok, status: r.status, data: d }));
        })
        .then(({ ok, data }) => {
            if (ok) {
                window.dispatchEvent(new CustomEvent('reset-success'));
            } else {
                window.dispatchEvent(new CustomEvent('reset-error', { detail: data.message || 'Terjadi kesalahan' }));
            }
        })
        .catch((err) => {
            clearTimeout(timeoutId);
            if (err.name === 'AbortError') {
                window.dispatchEvent(new CustomEvent('reset-error', { detail: 'Request timeout' }));
            } else {
                window.dispatchEvent(new CustomEvent('reset-error', { detail: err.message }));
            }
        });
    });
});
