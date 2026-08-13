<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    @php
        $seoTitle = trim($__env->yieldContent('title')) ?: config('seo.title');
        $seoDescription = trim($__env->yieldContent('meta_description')) ?: config('seo.description');
        $seoKeywords = trim($__env->yieldContent('meta_keywords')) ?: config('seo.keywords');
        $seoAuthor = trim($__env->yieldContent('author')) ?: config('seo.author');
        $seoUrl = trim($__env->yieldContent('canonical')) ?: request()->url();
        $seoImage = trim($__env->yieldContent('og_image')) ?: config('seo.og_image');
        $seoRobots = trim($__env->yieldContent('robots')) ?: config('seo.robots');
        $seoType = trim($__env->yieldContent('og_type')) ?: 'website';

        $localBusinessSchema = [
            '@context' => 'https://schema.org',
            '@type' => config('seo.business.type'),
            '@id' => url('/#localbusiness'),
            'name' => config('seo.business.name'),
            'url' => url('/'),
            'logo' => url(config('seo.business.logo')),
            'image' => config('seo.business.image'),
            'description' => config('seo.description'),
            'telephone' => config('seo.business.telephone'),
            'email' => config('seo.business.email'),
            'priceRange' => config('seo.business.price_range'),
            'address' => [
                '@type' => 'PostalAddress',
                'streetAddress' => config('seo.business.address.street'),
                'addressLocality' => config('seo.business.address.locality'),
                'addressRegion' => config('seo.business.address.region'),
                'postalCode' => config('seo.business.address.postal_code'),
                'addressCountry' => config('seo.business.address.country'),
            ],
            'openingHoursSpecification' => [
                [
                    '@type' => 'OpeningHoursSpecification',
                    'dayOfWeek' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'],
                    'opens' => explode('-', explode(' ', config('seo.business.opening_hours'))[1] ?? '10:00-22:00')[0] ?? '10:00',
                    'closes' => explode('-', explode(' ', config('seo.business.opening_hours'))[1] ?? '10:00-22:00')[1] ?? '22:00',
                ]
            ],
            'sameAs' => config('seo.business.social_links'),
        ];
    @endphp
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $seoTitle }}</title>
    <meta name="description" content="{{ $seoDescription }}">
    <meta name="keywords" content="{{ $seoKeywords }}">
    <meta name="author" content="{{ $seoAuthor }}">
    <meta name="robots" content="{{ $seoRobots }}">
    <link rel="canonical" href="{{ $seoUrl }}">
    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}">
    <link rel="shortcut icon" type="image/png" href="{{ asset('images/logo.png') }}">
    <meta property="og:locale" content="id_ID">
    <meta property="og:type" content="{{ $seoType }}">
    <meta property="og:title" content="{{ $seoTitle }}">
    <meta property="og:description" content="{{ $seoDescription }}">
    <meta property="og:url" content="{{ $seoUrl }}">
    <meta property="og:image" content="{{ $seoImage }}">
    <meta property="og:site_name" content="{{ config('seo.business.name') }}">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $seoTitle }}">
    <meta name="twitter:description" content="{{ $seoDescription }}">
    <meta name="twitter:image" content="{{ $seoImage }}">

    @stack('jsonld')
    <script type="application/ld+json">
    @json($localBusinessSchema)
    </script>

    <!-- Google Fonts: match React app imports (Inter weights + Playfair for headings) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

    <script src="{{ asset('js/frontend/tailwind-config.js') }}?v={{ filemtime(public_path('js/frontend/tailwind-config.js')) }}"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Firebase SDK -->
    <script defer src="https://www.gstatic.com/firebasejs/10.12.0/firebase-app-compat.js"></script>
    <script defer src="https://www.gstatic.com/firebasejs/10.12.0/firebase-auth-compat.js"></script>
    
    <script id="firebase-config-data" type="application/json">
        {
            "apiKey": "{{ config('services.firebase.api_key') }}",
            "authDomain": "{{ config('services.firebase.auth_domain') }}",
            "projectId": "{{ config('services.firebase.project_id') }}"
        }
    </script>
    <script defer src="{{ asset('js/frontend/firebase-init.js') }}?v={{ filemtime(public_path('js/frontend/firebase-init.js')) }}"></script>

    <style>
        [x-cloak] { display: none !important; }

        /* Mirror React app: define CSS font variables and apply to body
           Keep .font-serif to switch headings to Playfair Display */
        :root {
            --font-sans: 'Inter', ui-sans-serif, system-ui, -apple-system, sans-serif;
            --font-serif: 'Playfair Display', ui-serif, Georgia, Cambria, serif;
            --font-mono: ui-monospace, SFMono-Regular, Menlo, Monaco, monospace;
            --color-brand-dark: #2b1d12;
            --color-brand-darker: #1a1009;
            --color-brand-gold: #c09d6b;
            --color-brand-gold-dark: #ad8a58;
            --color-brand-light: #fdfbf7;
            --color-brand-muted: #f2ebd9;
            --brand-dark: #2b1d12;
            --brand-darker: #1a1009;
            --brand-gold: #c09d6b;
            --brand-gold-dark: #ad8a58;
            --brand-light: #fdfbf7;
            --brand-muted: #f2ebd9;
        }

        .font-serif, [class*="font-serif"] {
            font-family: var(--font-serif);
        }

        .text-brand-dark { color: var(--brand-dark); }
        .text-brand-darker { color: var(--brand-darker); }
        .text-brand-gold { color: var(--brand-gold); }
        .text-brand-gold-dark { color: var(--brand-gold-dark); }
        .text-brand-light { color: var(--brand-light); }
        .text-brand-muted { color: var(--brand-muted); }

        .bg-brand-dark { background-color: var(--brand-dark); }
        .bg-brand-darker { background-color: var(--brand-darker); }
        .bg-brand-gold { background-color: var(--brand-gold); }
        .bg-brand-gold-dark { background-color: var(--brand-gold-dark); }
        .bg-brand-light { background-color: var(--brand-light); }
        .bg-brand-muted { background-color: var(--brand-muted); }

        .border-brand-dark { border-color: var(--brand-dark); }
        .border-brand-darker { border-color: var(--brand-darker); }
        .border-brand-gold { border-color: var(--brand-gold); }
        .border-brand-light { border-color: var(--brand-light); }
        .border-brand-muted { border-color: var(--brand-muted); }

        .bg-brand-dark\/40 { background-color: rgba(43,29,18,.4); }
        .bg-brand-light\/30 { background-color: rgba(253,251,247,.3); }
        .bg-brand-muted\/30 { background-color: rgba(242,235,217,.3); }
        .bg-brand-gold\/10 { background-color: rgba(192,157,107,.1); }
        .bg-brand-gold\/20 { background-color: rgba(192,157,107,.2); }
        .bg-brand-gold\/50 { background-color: rgba(192,157,107,.5); }
        .bg-brand-light\/50 { background-color: rgba(253,251,247,.5); }

        .text-brand-dark\/80 { color: rgba(43,29,18,.8); }
        .text-brand-light\/70 { color: rgba(253,251,247,.7); }
        .text-brand-light\/90 { color: rgba(253,251,247,.9); }
        .text-brand-gold-dark\/50 { color: rgba(173,138,88,.5); }
        .text-brand-light\/50 { color: rgba(253,251,247,.5); }

        .border-brand-gold\/20 { border-color: rgba(192,157,107,.2); }
        .border-brand-gold\/30 { border-color: rgba(192,157,107,.3); }
        .shadow-brand-dark\/20 { box-shadow: 0 10px 15px -3px rgba(43,29,18,.2), 0 4px 6px -2px rgba(0,0,0,.05); }
        .focus\:ring-brand-gold\/50:focus { box-shadow: 0 0 0 3px rgba(192,157,107,.5); }

        body {
            font-family: var(--font-sans);
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }
        /* Force consistent weight/variation to more closely match React build */
        body {
            font-weight: 400;
            font-variation-settings: 'wght' 400;
        }

        h1, h2, h3, h4, h5 {
            font-family: var(--font-serif);
            font-weight: 800;
            font-variation-settings: 'wght' 800;
            letter-spacing: -0.02em;
            -webkit-font-smoothing: antialiased;
            text-rendering: optimizeLegibility;
            font-kerning: normal;
        }
        /* Visual tuning to match React build: radii, shadows, transitions */
        :root {
            --radius-2xl: 1rem; /* 16px */
            --radius-3xl: 1.5rem; /* 24px */
            --shadow-lg: 0 10px 15px -3px rgba(0,0,0,0.08), 0 4px 6px -2px rgba(0,0,0,0.04);
            --shadow-2xl: 0 25px 50px -12px rgba(0,0,0,0.25);
            --transition-fast: 180ms;
        }

        .rounded-2xl { border-radius: var(--radius-2xl) !important; }
        .rounded-3xl { border-radius: var(--radius-3xl) !important; }

        .shadow-lg { box-shadow: var(--shadow-lg) !important; }
        .shadow-2xl { box-shadow: var(--shadow-2xl) !important; }

        .transition-colors { transition: color var(--transition-fast) cubic-bezier(.4,0,.2,1), background-color var(--transition-fast) cubic-bezier(.4,0,.2,1) !important; }
        .group:hover .group-hover\:translate-x-1 { transform: translateX(.25rem); }
        .btn-solid-hover {
            background-color: #ffffff;
            color: var(--brand-darker);
            border-color: var(--brand-gold);
        }
        .btn-solid-hover:hover,
        .btn-solid-hover:focus {
            background-color: var(--brand-dark) !important;
            color: #ffffff !important;
            border-color: var(--brand-gold) !important;
            box-shadow: 0 18px 35px -22px rgba(0,0,0,.35) !important;
        }
        .btn-solid-hover svg {
            color: inherit;
            fill: currentColor !important;
        }
        .scrollbar-none {
            scrollbar-width: none;
        }
        .scrollbar-none::-webkit-scrollbar {
            display: none;
        }
        /* Ensure text gradients (bg-clip-text) render reliably
           Avoid forcing inline-block (causes wrapping differences). */
        .bg-clip-text {
            -webkit-background-clip: text !important;
            background-clip: text !important;
            -webkit-text-fill-color: transparent !important;
            color: transparent !important;
            display: inline !important;
            background-repeat: no-repeat !important;
        }
        .text-transparent { color: transparent !important; -webkit-text-fill-color: transparent !important; }

        /* Explicit gradient for hero title span to ensure visibility even if utility classes are missing */
        .hero-title .bg-clip-text {
            background-image: linear-gradient(90deg, var(--brand-gold), var(--brand-gold-dark));
            background-size: 100% 100% !important;
        }
        /* Ensure generated SVG icons follow currentColor and align with text */
        svg {
            display: inline-block !important;
            vertical-align: middle !important;
            stroke: currentColor !important;
        }
        svg[fill="none"] {
            fill: none !important;
        }
        /* Entrance animations to replicate React motion/react usage */
        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(20px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        @keyframes scaleIn {
            from { opacity: 0; transform: scale(0.95); }
            to   { opacity: 1; transform: scale(1); }
        }

        .motion-enter {
            opacity: 0;
            animation-name: fadeUp;
            animation-fill-mode: forwards;
            animation-duration: 500ms;
            animation-timing-function: cubic-bezier(.4,0,.2,1);
        }
        .motion-scale-enter {
            opacity: 0;
            animation-name: scaleIn;
            animation-fill-mode: forwards;
            animation-duration: 700ms;
            animation-timing-function: cubic-bezier(.4,0,.2,1);
        }

        .motion-enter-delay-0 { animation-delay: 0ms; }
        .motion-enter-delay-100 { animation-delay: 100ms; }
        .motion-enter-delay-200 { animation-delay: 200ms; }
        .motion-enter-delay-300 { animation-delay: 300ms; }
        .motion-enter-delay-400 { animation-delay: 400ms; }

        /* Nav link hover — match React Header */
        .nav-link {
            color: #1f2937;
            transition: color 200ms cubic-bezier(.4,0,.2,1);
        }
        .nav-link:hover,
        .nav-link:focus {
            color: var(--brand-gold) !important;
        }

        /* Product card hover — match React ProductCard */
        .product-card {
            transition: box-shadow 300ms cubic-bezier(.4,0,.2,1),
                        transform 300ms cubic-bezier(.4,0,.2,1) !important;
        }
        .product-card:hover {
            box-shadow: 0 20px 25px -5px rgba(0,0,0,.1), 0 8px 10px -6px rgba(0,0,0,.1) !important;
            transform: scale(1.02) translateY(-4px) !important;
        }
        .product-card__image {
            transition: transform 700ms cubic-bezier(.4,0,.2,1) !important;
        }
        .product-card:hover .product-card__image {
            transform: scale(1.05) !important;
        }
        .product-card__actions {
            opacity: 0;
            transform: translateX(1rem);
            transition: opacity 300ms cubic-bezier(.4,0,.2,1),
                        transform 300ms cubic-bezier(.4,0,.2,1);
        }
        .product-card:hover .product-card__actions {
            opacity: 1;
            transform: translateX(0);
        }
        .product-card__btn {
            transition: background-color 300ms cubic-bezier(.4,0,.2,1),
                        color 300ms cubic-bezier(.4,0,.2,1),
                        border-color 300ms cubic-bezier(.4,0,.2,1) !important;
        }
        .product-card:hover .product-card__btn:not(:disabled) {
            background-color: var(--brand-dark) !important;
            color: #ffffff !important;
            border-color: var(--brand-dark) !important;
        }
        .product-card__btn:hover:not(:disabled),
        .load-more-btn:hover {
            background-color: var(--brand-dark) !important;
            color: #ffffff !important;
            border-color: var(--brand-dark) !important;
        }
        .product-card__title:hover {
            color: var(--brand-gold) !important;
        }
        .product-card__rating:hover {
            background-color: var(--brand-light);
        }

        /* Help page card hover */
        .help-contact-card {
            transition: border-color 200ms cubic-bezier(.4,0,.2,1);
        }
        .help-contact-card:hover {
            border-color: var(--brand-gold) !important;
        }
        .help-contact-card:hover .help-contact-icon {
            background-color: var(--brand-gold) !important;
            color: #ffffff !important;
        }
        .help-faq-item:hover span:first-child {
            color: var(--brand-dark) !important;
        }
        .help-faq-item:hover .help-faq-toggle {
            background-color: var(--brand-gold) !important;
            color: #ffffff !important;
        }

        .footer-social-icon {
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            font-size: 1rem !important;
            line-height: 1 !important;
        }
        .footer-social-icon.fa-facebook-f {
            transform: translateX(-1px);
        }
        .footer-social-icon.fa-x-twitter {
            transform: translateX(1px);
        }

        /* Loading Animation */
        .loading-overlay {
            position: fixed;
            inset: 0;
            background: rgba(255,255,255,0.9);
            z-index: 9999;
            display: none;
            align-items: center;
            justify-content: center;
        }
        .loading-spinner {
            width: 40px;
            height: 40px;
            border: 3px solid var(--brand-gold);
            border-top-color: transparent;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .category-icon-box {
            background-color: var(--brand-light) !important;
            color: var(--brand-gold) !important;
        }
        .category-card:hover .category-icon-box {
            background-color: var(--brand-gold) !important;
        }
        .category-card:hover .category-icon-box svg {
            color: #ffffff !important;
        }
        .fa-thin.fa-grid-2 {
            position: relative;
            display: inline-block;
            width: 1em;
            height: 1em;
            font-family: inherit !important;
            font-weight: inherit !important;
        }
        .fa-thin.fa-grid-2::before,
        .fa-thin.fa-grid-2::after {
            content: "" !important;
            position: absolute;
            width: 0.42em;
            height: 0.42em;
            border: 0.12em solid currentColor;
            border-radius: 0.1em;
            background: transparent;
        }
        .fa-thin.fa-grid-2::before {
            left: 0;
            top: 0;
        }
        .fa-thin.fa-grid-2::after {
            right: 0;
            bottom: 0;
        }
        .fa-thin.fa-grid-2 .grid-tile {
            position: absolute;
            width: 0.42em;
            height: 0.42em;
            border: 0.12em solid currentColor;
            border-radius: 0.1em;
            background: transparent;
        }
        .fa-thin.fa-grid-2 .tile-1 { left: 0; top: 0; }
        .fa-thin.fa-grid-2 .tile-2 { right: 0; top: 0; }
        .fa-thin.fa-grid-2 .tile-3 { left: 0; bottom: 0; }
        .fa-thin.fa-grid-2 .tile-4 { right: 0; bottom: 0; }
        .category-card:hover .fa-thin.fa-grid-2::before,
        .category-card:hover .fa-thin.fa-grid-2::after,
        .category-card:hover .fa-thin.fa-grid-2 .grid-tile {
            background: currentColor;
            border-color: currentColor;
        }
    </style>
    
    <!-- App JS (deferred to avoid render-blocking; preserved order ensures Alpine loads after) -->
    <script defer src="{{ asset('js/frontend/app.js') }}?v={{ filemtime(public_path('js/frontend/app.js')) }}"></script>
    <script defer src="{{ asset('js/frontend/cart-drawer.js') }}?v={{ filemtime(public_path('js/frontend/cart-drawer.js')) }}"></script>
    @if(file_exists(public_path('js/frontend/header.js')))
        <script defer src="{{ asset('js/frontend/header.js') }}?v={{ filemtime(public_path('js/frontend/header.js')) }}"></script>
    @endif
    @if(file_exists(public_path('js/frontend/language-switcher.js')))
        <script defer src="{{ asset('js/frontend/language-switcher.js') }}?v={{ filemtime(public_path('js/frontend/language-switcher.js')) }}"></script>
    @endif
    @if(file_exists(public_path('js/frontend/popup-event.js')))
        <script defer src="{{ asset('js/frontend/popup-event.js') }}?v={{ filemtime(public_path('js/frontend/popup-event.js')) }}"></script>
    @endif
    <script defer src="{{ asset('js/frontend/auth-modal.js') }}?v={{ filemtime(public_path('js/frontend/auth-modal.js')) }}"></script>

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js"></script>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" media="print" onload="this.media='all'">
    <noscript><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css"></noscript>
    
    <!-- Motion One (used by React motion/react) - attempt to reproduce exact animations -->
    <script defer src="https://cdn.jsdelivr.net/npm/motion@10.15.3/dist/motion.global.min.js"></script>
</head>
@php
    $whatsappNumber = isset($about) && isset($about->social_media['whatsapp'])
        ? preg_replace('/[^0-9]/', '', $about->social_media['whatsapp'])
        : '6281112345678';
    $whatsappUrl = 'https://wa.me/' . $whatsappNumber;
@endphp
<body 
    class="min-h-screen bg-brand-light/30 flex flex-col font-sans text-brand-dark selection:bg-brand-gold/30 pb-20 md:pb-0"
    data-route-home="{{ route('home') }}"
    data-route-cart-toggle-wishlist="{{ route('cart.toggle-wishlist') }}"
    data-route-cart-add="{{ route('cart.add') }}"
    data-route-cart-update="{{ route('cart.update', '__ID__') }}"
    data-route-thankyou="{{ route('thankyou') }}"
    data-route-auth-google-session="{{ route('auth.google.session') }}"
    x-data="{ 
        isCartOpen: false, 
        isAuthOpen: {{ session()->has('show_login') ? 'true' : 'false' }}, 
        selectedProductForReview: null,
        isMobileMenuOpen: false,
        toasts: [],
        addToast(type, message, duration = 4000) {
            if (!message || String(message).trim() === '') {
                return;
            }
            const id = Date.now() + Math.random().toString(36).substr(2, 9);
            this.toasts.push({ id, type, message });
            setTimeout(() => {
                this.removeToast(id);
            }, duration);
        },
        removeToast(id) {
            this.toasts = this.toasts.filter(t => t.id !== id);
        }
    }"
    x-init="
        window.showToast = (type, message, duration) => addToast(type, message, duration);
        @if(session('success') && is_string(session('success')) && trim(session('success')) !== '')
            addToast('success', '{{ addslashes(session('success')) }}');
        @endif
        @if(session('status') && is_string(session('status')) && trim(session('status')) !== '')
            addToast('success', '{{ addslashes(session('status')) }}');
        @endif
        @if(session('error') && is_string(session('error')) && trim(session('error')) !== '')
            addToast('error', '{{ addslashes(session('error')) }}');
        @endif
        @if(session('failed') && is_string(session('failed')) && trim(session('failed')) !== '')
            addToast('error', '{{ addslashes(session('failed')) }}');
        @endif
        @if($errors->any())
            @foreach($errors->all() as $error)
                addToast('error', '{{ addslashes($error) }}');
            @endforeach
        @endif
    "
    @open-cart.window="isCartOpen = true"
    @open-auth.window="isAuthOpen = true; setTimeout(() => window.initFirebaseGoogleSignIn && window.initFirebaseGoogleSignIn(), 100)"
    @open-review.window="selectedProductForReview = $event.detail"
    @open-review="selectedProductForReview = $event.detail"
    @cart-added.window="addToast('success', $event.detail && $event.detail.message ? $event.detail.message : 'Produk berhasil masuk keranjang')"
    @cart-add-failed.window="addToast('error', $event.detail && $event.detail.message ? $event.detail.message : 'Gagal menambahkan produk ke keranjang')"
    @show-toast.window="addToast($event.detail.type, $event.detail.message, $event.detail.duration)"
>
     <!-- Header Component -->
    @include('frontend.components.header')

    <!-- Main Content -->
    <main id="main-content" class="flex-1 w-full" tabindex="-1">
        @yield('content')
    </main>

    <!-- Loading Overlay -->
    <div id="loading-overlay" class="loading-overlay">
        <div class="loading-spinner"></div>
    </div>

    <!-- Footer Component -->
    @include('frontend.components.footer')

    <!-- Modals & Overlays -->
    @include('frontend.components.cart-drawer')
    @include('frontend.components.auth-modal')
    @include('frontend.components.review-modal')

    <!-- Global Floating Toast Container -->
    <div class="fixed top-5 right-4 z-[9999] flex flex-col gap-3 w-[calc(100%-2rem)] max-w-sm pointer-events-none">
        <template x-for="toast in toasts" :key="toast.id">
            <div
                x-show="true"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-y-2 md:translate-x-4"
                x-transition:enter-end="opacity-100 translate-y-0 md:translate-x-0"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100 translate-y-0"
                x-transition:leave-end="opacity-0 translate-y-2 md:translate-x-4"
                class="rounded-2xl border bg-white p-4 shadow-2xl flex items-start gap-3 pointer-events-auto"
                :class="{
                    'border-green-100 bg-green-50/90': toast.type === 'success',
                    'border-red-100 bg-red-50/90': toast.type === 'error',
                    'border-yellow-100 bg-yellow-50/90': toast.type === 'warning',
                    'border-blue-100 bg-blue-50/90': toast.type === 'info' || !toast.type
                }"
            >
                <!-- Icon -->
                <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full"
                    :class="{
                        'bg-green-100 text-green-700': toast.type === 'success',
                        'bg-red-100 text-red-700': toast.type === 'error',
                        'bg-yellow-100 text-yellow-700': toast.type === 'warning',
                        'bg-blue-100 text-blue-700': toast.type === 'info' || !toast.type
                    }"
                >
                    <i class="fa-solid w-5 h-5 flex items-center justify-center text-sm"
                        :class="{
                            'fa-check': toast.type === 'success',
                            'fa-xmark': toast.type === 'error',
                            'fa-triangle-exclamation': toast.type === 'warning',
                            'fa-info': toast.type === 'info' || !toast.type
                        }"
                    ></i>
                </div>
                <!-- Content -->
                <div class="min-w-0 flex-1">
                    <h4 class="font-extrabold text-brand-dark text-sm" 
                        x-text="toast.type === 'success' ? 'Sukses' : (toast.type === 'error' ? 'Kesalahan' : (toast.type === 'warning' ? 'Peringatan' : 'Informasi'))"
                    ></h4>
                    <p class="mt-1 text-sm text-gray-600 font-medium" x-text="toast.message"></p>
                </div>
                <!-- Close Button -->
                <button @click="removeToast(toast.id)" class="text-gray-400 hover:text-gray-600 transition-colors flex-shrink-0 p-1">
                    <i class="fa-solid fa-xmark text-sm"></i>
                </button>
            </div>
        </template>
    </div>

    <!-- Mobile Bottom Navigation -->
    <nav class="md:hidden fixed bottom-0 inset-x-0 bg-white border-t border-gray-200 z-[90] flex items-center justify-around h-[70px] shadow-[0_-4px_10px_rgba(0,0,0,0.05)] pb-[env(safe-area-inset-bottom)]">
        <a href="{{ route('home') }}" class="flex flex-col items-center justify-center w-full h-full text-gray-400 hover:text-brand-gold transition-colors {{ request()->routeIs('home') ? 'text-brand-gold' : '' }}">
            <i class="fa-solid fa-house text-[22px] mb-1"></i>
            <span class="text-[10px] font-medium">Home</span>
        </a>
        <a href="{{ route('products.index') }}" class="flex flex-col items-center justify-center w-full h-full text-gray-400 hover:text-brand-gold transition-colors {{ request()->routeIs('products.*') ? 'text-brand-gold' : '' }}">
            <i class="fa-solid fa-store text-[22px] mb-1"></i>
            <span class="text-[10px] font-medium">Shop</span>
        </a>
        <button @click="isCartOpen = true" class="flex flex-col items-center justify-center w-full h-full text-gray-400 hover:text-brand-gold transition-colors relative focus:outline-none">
            <div class="relative">
                <i class="fa-solid fa-cart-shopping text-[22px] mb-1"></i>
                @php
                    $navCartCount = collect($cart ?? [])->sum('quantity');
                @endphp
                @if($navCartCount > 0)
                    <span class="absolute -top-1.5 -right-2 bg-brand-gold text-white text-[9px] font-bold w-4 h-4 rounded-full flex items-center justify-center shadow-sm">
                        {{ $navCartCount }}
                    </span>
                @endif
            </div>
            <span class="text-[10px] font-medium">Cart</span>
        </button>
        @if(session()->get('is_logged_in'))
            <a href="{{ route('dashboard') }}" class="flex flex-col items-center justify-center w-full h-full text-gray-400 hover:text-brand-gold transition-colors {{ request()->routeIs('dashboard') ? 'text-brand-gold' : '' }}">
                <i class="fa-solid fa-user text-[22px] mb-1"></i>
                <span class="text-[10px] font-medium">Account</span>
            </a>
        @else
            <button @click="isAuthOpen = true" class="flex flex-col items-center justify-center w-full h-full text-gray-400 hover:text-brand-gold transition-colors focus:outline-none">
                <i class="fa-solid fa-user text-[22px] mb-1"></i>
                <span class="text-[10px] font-medium">Account</span>
            </button>
        @endif
    </nav>

    <!-- Live Chat Widget -->
    <div x-data="liveChat('{{ isset($product) && request()->routeIs('products.show') ? addslashes($product->name) : '' }}')" class="fixed bottom-24 md:bottom-6 right-4 md:right-6 z-[80] font-sans">
        <!-- Toggle Button -->
        <button 
            @click="toggleChat()"
            class="flex h-14 w-14 items-center justify-center rounded-full bg-brand-gold text-white shadow-2xl transition-transform hover:scale-105 focus:outline-none focus:ring-4 focus:ring-brand-gold/30 relative"
            aria-label="Live Chat"
        >
            <i class="fa-solid fa-comments text-3xl" x-show="!isOpen"></i>
            <i class="fa-solid fa-xmark text-3xl" x-show="isOpen" style="display: none;"></i>
            
            <span x-show="unreadCount > 0" style="display: none;" class="absolute -top-1 -right-1 bg-red-500 text-white text-[10px] font-bold w-5 h-5 flex items-center justify-center rounded-full border-2 border-white shadow-sm" x-text="unreadCount"></span>
        </button>
        
        <!-- Chat Window -->
        <div x-show="isOpen" style="display: none;" class="absolute bottom-16 right-0 w-80 md:w-96 h-[450px] max-h-[80vh] bg-white rounded-xl shadow-2xl overflow-hidden flex flex-col border border-gray-100">
            <div class="bg-brand-gold text-white p-4 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-white/20 flex items-center justify-center">
                        <i class="fa-solid fa-headset text-xl"></i>
                    </div>
                    <div>
                        <h3 class="font-bold leading-tight">Live Chat</h3>
                        <p class="text-[10px] opacity-80">Online</p>
                    </div>
                </div>
                <button @click="isOpen = false" class="text-white hover:text-gray-200 focus:outline-none">
                    <i class="fa-solid fa-xmark text-xl"></i>
                </button>
            </div>
            
            <div class="flex-1 p-4 overflow-y-auto bg-gray-50 flex flex-col gap-3 h-80" id="chat-messages" x-ref="messagesContainer">
                <template x-for="(msg, index) in messages" :key="index">
                    <div :class="msg.sender_type === 'customer' ? 'self-end bg-brand-gold text-white' : 'self-start bg-white border border-gray-200 text-gray-800'" class="px-3 py-2 rounded-xl max-w-[85%] text-sm shadow-sm relative">
                        <p x-text="msg.text" class="break-words"></p>
                        <span :class="msg.sender_type === 'customer' ? 'text-brand-light' : 'text-gray-400'" class="text-[9px] mt-1 block text-right" x-text="formatTime(msg.created_at)"></span>
                    </div>
                </template>
                <div x-show="loading" class="text-center text-xs text-gray-400 my-2">Memuat pesan...</div>
                <div x-show="messages.length === 0 && !loading && !productName" class="flex flex-col items-center justify-center w-full my-auto gap-4">
                    <div class="text-center text-xs text-gray-400 mb-2">
                        Belum ada obrolan. Pilih topik atau ketik pesan Anda:
                    </div>
                    <div class="flex flex-col gap-2 w-full px-2">
                        <button type="button" @click="sendSuggestion('Apakah produk ini ready stock?')" class="text-xs bg-brand-gold/10 border border-brand-gold/30 text-brand-dark px-4 py-2.5 rounded-2xl hover:bg-brand-gold hover:text-brand-darker transition-all text-left flex justify-between items-center shadow-sm group">
                            <span>Apakah produk ini ready stock?</span>
                            <i class="fa-solid fa-paper-plane text-[10px] text-brand-gold group-hover:text-brand-darker transition-colors"></i>
                        </button>
                        <button type="button" @click="sendSuggestion('Bagaimana proses klaim garansinya?')" class="text-xs bg-brand-gold/10 border border-brand-gold/30 text-brand-dark px-4 py-2.5 rounded-2xl hover:bg-brand-gold hover:text-brand-darker transition-all text-left flex justify-between items-center shadow-sm group">
                            <span>Bagaimana proses klaim garansinya?</span>
                            <i class="fa-solid fa-paper-plane text-[10px] text-brand-gold group-hover:text-brand-darker transition-colors"></i>
                        </button>
                        <button type="button" @click="sendSuggestion('Bisa minta rekomendasi produk terbaik?')" class="text-xs bg-brand-gold/10 border border-brand-gold/30 text-brand-dark px-4 py-2.5 rounded-2xl hover:bg-brand-gold hover:text-brand-darker transition-all text-left flex justify-between items-center shadow-sm group">
                            <span>Bisa minta rekomendasi produk terbaik?</span>
                            <i class="fa-solid fa-paper-plane text-[10px] text-brand-gold group-hover:text-brand-darker transition-colors"></i>
                        </button>
                    </div>
                </div>

                <!-- Product Specific Context (Always shows on product page) -->
                <template x-if="productName && !loading">
                    <div class="flex flex-col w-full mt-auto gap-2 pt-4">
                        <div class="text-center text-[10px] text-gray-400 mb-1 border-t border-gray-200/60 pt-3">
                            Tanyakan tentang produk ini:
                        </div>
                        <div class="flex flex-col gap-2 w-full px-1">
                            <button type="button" @click="sendSuggestion('Apakah stok produk ' + productName + ' masih tersedia?')" class="text-xs bg-brand-gold/10 border border-brand-gold/30 text-brand-dark px-3 py-2 rounded-2xl hover:bg-brand-gold hover:text-brand-darker transition-all text-left flex justify-between items-center shadow-sm group">
                                <span>Apakah stok <strong x-text="productName.length > 20 ? productName.substring(0, 20) + '...' : productName"></strong> tersedia?</span>
                                <i class="fa-solid fa-paper-plane text-[10px] text-brand-gold group-hover:text-brand-darker transition-colors"></i>
                            </button>
                            <button type="button" @click="sendSuggestion('Bisa infokan detail garansi untuk ' + productName + '?')" class="text-xs bg-brand-gold/10 border border-brand-gold/30 text-brand-dark px-3 py-2 rounded-2xl hover:bg-brand-gold hover:text-brand-darker transition-all text-left flex justify-between items-center shadow-sm group">
                                <span>Bisa infokan detail garansinya?</span>
                                <i class="fa-solid fa-paper-plane text-[10px] text-brand-gold group-hover:text-brand-darker transition-colors"></i>
                            </button>
                        </div>
                    </div>
                </template>
            </div>
            
            <div class="p-3 bg-white border-t border-gray-100">
                <form @submit.prevent="sendMessage" class="flex gap-2">
                    <input type="text" x-model="newMessage" placeholder="Tulis pesan..." class="flex-1 text-sm border-gray-300 rounded-lg focus:ring-brand-gold focus:border-brand-gold px-3 py-2 border outline-none" :disabled="sending">
                    <button type="submit" class="bg-brand-gold text-white rounded-lg transition-colors hover:bg-brand-gold-dark disabled:opacity-50 relative min-w-[46px] flex items-center justify-center" :disabled="sending || !newMessage.trim()">
                        <i class="fa-solid fa-paper-plane text-sm" x-show="!sending"></i>
                        <i class="fa-solid fa-spinner fa-spin text-sm" x-show="sending" style="display: none;"></i>
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
    <script>
        function liveChat(initialProductName = '') {
            return {
                productName: initialProductName,
                isOpen: false,
                isLoggedIn: {{ session()->get('is_logged_in') ? 'true' : 'false' }},
                messages: [],
                newMessage: '',
                loading: false,
                sending: false,
                conversationId: null,
                pusherInstance: null,
                unreadCount: 0,
                
                init() {
                    if (this.isLoggedIn) {
                        this.fetchMessages(true); // Silent fetch, do NOT connect to Pusher yet
                    }
                },
                
                toggleChat() {
                    if (!this.isLoggedIn) {
                        this.isOpen = false;
                        // Dispatch custom event to open auth modal
                        window.dispatchEvent(new CustomEvent('open-auth'));
                    } else {
                        this.isOpen = !this.isOpen;
                        if (this.isOpen) {
                            this.unreadCount = 0;
                            if (this.messages.length === 0 && !this.loading && !this.conversationId) {
                                this.fetchMessages();
                            } else if (this.conversationId) {
                                this.initPusher();
                            }
                            setTimeout(() => this.scrollToBottom(), 100);
                        } else {
                            if (this.pusherInstance) {
                                this.pusherInstance.disconnect();
                                this.pusherInstance = null;
                            }
                        }
                    }
                },
                
                formatTime(isoString) {
                    if (!isoString) return '';
                    const d = new Date(isoString);
                    return d.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
                },
                
                scrollToBottom() {
                    if (this.$refs.messagesContainer) {
                        this.$refs.messagesContainer.scrollTop = this.$refs.messagesContainer.scrollHeight;
                    }
                },
                
                sendSuggestion(text) {
                    this.newMessage = text;
                    this.sendMessage();
                },
                
                async fetchMessages(silent = false) {
                    if (!silent) this.loading = true;
                    try {
                        const response = await fetch('/chat/messages', {
                            headers: { 'Accept': 'application/json' }
                        });
                        const data = await response.json();
                        if (data.success) {
                            this.messages = data.messages;
                            this.conversationId = data.conversation_id;
                            if (!silent && this.isOpen) {
                                this.initPusher();
                            }
                            if (!silent) setTimeout(() => this.scrollToBottom(), 100);
                        }
                    } catch (e) {
                        console.error('Failed to load messages', e);
                    } finally {
                        if (!silent) this.loading = false;
                    }
                },
                
                async sendMessage() {
                    if (!this.newMessage.trim() || this.sending) return;
                    
                    const text = this.newMessage;
                    this.newMessage = '';
                    this.sending = true;
                    
                    // Optimistic UI
                    const tempId = Date.now();
                    this.messages.push({
                        id: tempId,
                        text: text,
                        sender_type: 'customer',
                        created_at: new Date().toISOString()
                    });
                    setTimeout(() => this.scrollToBottom(), 50);
                    
                    try {
                        const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                        const response = await fetch('/chat/messages', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': token
                            },
                            body: JSON.stringify({ text })
                        });
                        const data = await response.json();
                        if (data.success) {
                            // Replace temp message with actual
                            const idx = this.messages.findIndex(m => m.id === tempId);
                            if (idx !== -1) {
                                this.messages.splice(idx, 1, data.message);
                            }
                        }
                    } catch (e) {
                        console.error('Failed to send', e);
                    } finally {
                        this.sending = false;
                        setTimeout(() => this.scrollToBottom(), 50);
                    }
                },
                
                initPusher() {
                    if (this.pusherInstance || !this.conversationId) return;
                    
                    this.pusherInstance = new Pusher('{{ env('PUSHER_APP_KEY') }}', {
                        cluster: '{{ env('PUSHER_APP_CLUSTER') }}',
                        forceTLS: true
                    });
                    
                    const channel = this.pusherInstance.subscribe('chat.' + this.conversationId);
                    channel.bind('message.sent', (data) => {
                        // Avoid duplicates if we just sent it (optimistic UI)
                        const exists = this.messages.find(m => m.id === data.id);
                        if (!exists) {
                            this.messages.push(data);
                            if (this.isOpen) {
                                setTimeout(() => this.scrollToBottom(), 100);
                            } else {
                                this.unreadCount++;
                            }
                        }
                    });
                }
            }
        }
    </script>
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    @stack('scripts')
  </body>
</html>
