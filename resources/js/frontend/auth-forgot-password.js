
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('forgotPasswordPageForm');
    if (!form) return;

    form.addEventListener('submit', function (e) {
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
        .then(async (r) => {
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
            if (ok && (data.success === true || data.message)) {
                window.location.href = window.location.origin + '/password-otp-sent?email=' + encodeURIComponent(email);
            } else {
                alert(data.message || 'Gagal mengirim kode OTP (status: ' + status + ')');
                resetLoading();
            }
        })
        .catch((err) => {
            alert('Terjadi kesalahan: ' + err.message);
            resetLoading();
        });
    });
});
