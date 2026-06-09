@extends('frontend.layouts.app')

@section('title', 'Memproses Login Google - IMG')

@section('content')
<div class="container mx-auto px-4 md:px-6 py-12 min-h-[70vh] font-sans">
    <div class="max-w-xl mx-auto text-center bg-white border border-brand-muted rounded-3xl p-8 shadow-sm">
        <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-brand-light text-brand-gold mb-6">
            <i class="fa-solid fa-circle-notch w-7 h-7 animate-spin"></i>
        </div>

        <h1 class="text-2xl md:text-3xl font-extrabold text-brand-dark font-serif mb-3">
            Memproses Login Google
        </h1>

        <p class="text-gray-500">
            Mohon tunggu, kami sedang menyimpan sesi login Anda.
        </p>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const params = new URLSearchParams(window.location.hash.replace(/^#/, ''));
    const accessToken = params.get('access_token');
    const refreshToken = params.get('refresh_token');

    if (!accessToken || !refreshToken) {
        window.location.href = '{{ route('home') }}';
        return;
    }

    const csrf = document.querySelector('meta[name="csrf-token"]');

    fetch('{{ route('auth.google.session') }}', {
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

        window.location.href = '{{ route('home') }}';
    })
    .catch(function () {
        window.location.href = '{{ route('home') }}';
    });
});
</script>
@endsection
