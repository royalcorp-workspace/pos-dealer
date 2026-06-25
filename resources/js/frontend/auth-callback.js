document.addEventListener('DOMContentLoaded', function () {
    if (!window.location.hash || window.location.hash.length <= 1) return;

    const params = new URLSearchParams(window.location.hash.replace(/^#/, ''));
    const accessToken = params.get('access_token');
    const refreshToken = params.get('refresh_token');

    if (!accessToken || !refreshToken) return;

    const csrf = document.querySelector('meta[name="csrf-token"]');

    fetch(document.body.dataset.routeAuthGoogleSession, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrf ? csrf.getAttribute('content') : '',
            'Accept': 'application/json'
        },
        body: JSON.stringify({
            access_token: accessToken,
            refresh_token: refreshToken
        })
    })
    .then(function (response) {
        return response.json().then(function (data) {
            return { ok: response.ok, data: data };
        });
    })
    .then(function (result) {
        if (result.ok && result.data.success && result.data.redirect) {
            window.location.href = result.data.redirect;
            return;
        }

        window.location.href = document.body.dataset.routeHome || '/';
    })
    .catch(function () {
        window.location.href = document.body.dataset.routeHome || '/';
    });
});
