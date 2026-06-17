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

    <script>
        // Shim/queue for `tailwind` to prevent "tailwind is not defined" if some inline scripts run before CDN loads.
        (function(){
            if (typeof window.tailwind === 'undefined') {
                window._tailwindQueue = window._tailwindQueue || [];
                window.tailwind = function(){ window._tailwindQueue.push(arguments); };
                var runQueued = function(){
                    try {
                        if (typeof window.tailwind !== 'function') return;
                        if (!window._tailwindQueue || !window._tailwindQueue.length) return;
                        var real = window.tailwind;
                        // if real tailwind replaced our stub, drain queued calls
                        if (real && real !== window.tailwind) return; // replaced too early
                    } catch(e){}
                };
                window.addEventListener('load', function(){
                    if (window._tailwindQueue && window.tailwind && typeof window.tailwind === 'function' && window._tailwindQueue.length) {
                        try {
                            // if real tailwind becomes available as a different object, replay
                            var realTailwind = window.tailwind !== arguments.callee ? window.tailwind : null;
                        } catch(e){ realTailwind = null }
                    }
                });
            }
        })();

        tailwind.config = {
            theme: {
                fontFamily: {
                    sans: ['Inter', 'ui-sans-serif', 'system-ui', '-apple-system', 'sans-serif'],
                    serif: ['"Playfair Display"', 'ui-serif', 'Georgia', 'Cambria', 'serif'],
                    mono: ['ui-monospace', 'SFMono-Regular', 'Menlo', 'Monaco', 'monospace'],
                },
                extend: {
                    colors: {
                        brand: {
                            dark: '#2b1d12',
                            darker: '#1a1009',
                            gold: '#c09d6b',
                            'gold-dark': '#ad8a58',
                            light: '#fdfbf7',
                            muted: '#f2ebd9',
                        },
                    },
                },
            },
            safelist: [
                'hover:shadow-xl',
                'hover:scale-[1.02]',
                'hover:-translate-y-1',
                'group-hover:scale-105',
                'group-hover:scale-110',
                'group-hover:opacity-100',
                'group-hover:translate-x-0',
                'group-hover:text-white',
                'group-hover:bg-brand-dark',
                'group-hover:text-white',
                'group-hover:text-brand-gold-dark',
                'group-hover:bg-brand-gold',
                'hover:text-brand-gold',
                'hover:text-brand-gold-dark',
                'hover:text-brand-dark',
                'hover:border-brand-gold',
                'hover:bg-brand-dark',
                'hover:bg-brand-light',
                'hover:shadow-md',
                'hover:shadow-lg',
            ],
        };
    </script>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        // If we queued tailwind calls before the CDN loaded, replay them now.
        (function(){
            try {
                if (window._tailwindQueue && window._tailwindQueue.length && typeof window.tailwind === 'function') {
                    window._tailwindQueue.forEach(function(args){
                        try { window.tailwind.apply(null, args); } catch(e) { console.warn('replaying tailwind call failed', e); }
                    });
                    window._tailwindQueue = [];
                }
            } catch(e) { /* noop */ }
        })();
    </script>

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
            background: rgba(255,255,255,0.8);
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
    
    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js"></script>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    
    <!-- Motion One (used by React motion/react) - attempt to reproduce exact animations -->
    <script defer src="https://cdn.jsdelivr.net/npm/motion@10.15.3/dist/motion.global.min.js"></script>
</head>
<body 
    class="min-h-screen bg-brand-light/30 flex flex-col font-sans text-brand-dark selection:bg-brand-gold/30"
    x-data="{ 
        isCartOpen: false, 
        isAuthOpen: {{ session()->has('show_login') ? 'true' : 'false' }}, 
        selectedProductForReview: null,
        isMobileMenuOpen: false
    }"
    @open-cart.window="isCartOpen = true"
    @open-auth.window="isAuthOpen = true"
    @open-review.window="selectedProductForReview = $event.detail; console.log($event.detail)"
    @open-review="selectedProductForReview = $event.detail; console.log($event.detail)"
>
     <!-- Header Component -->
    @include('frontend.components.header')

    <!-- Main Content -->
    <main id="main-content" class="flex-1 w-full" tabindex="-1">
        @yield('content')
    </main>

    <!-- Footer Component -->
    @include('frontend.components.footer')

    <!-- Modals & Overlays -->
    @include('frontend.components.cart-drawer')
    @include('frontend.components.auth-modal')
    @include('frontend.components.review-modal')

    <div id="loading-overlay" class="loading-overlay">
        <div class="loading-spinner"></div>
    </div>

    <script>
        window.addEventListener('beforeunload', function() {
            document.getElementById('loading-overlay').style.display = 'flex';
        });

        function showLoading() {
            document.getElementById('loading-overlay').style.display = 'flex';
        }

        function hideLoading() {
            document.getElementById('loading-overlay').style.display = 'none';
        }

        document.addEventListener('submit', function(e) {
            if (!e.target.matches('form[action*="cart/add"], form[action*="checkout"], form[action*="login"], form[action*="logout"]')) return;
            setTimeout(hideLoading, 3000);
        });

        function openProductReview(event, productId) {
            const holder = event && event.currentTarget ? event.currentTarget.closest('[data-product-review]') : null;
            const productEl = holder || (productId ? document.querySelector(`[data-product-id="${productId}"]`) : null);
            const product = productEl ? JSON.parse(productEl.getAttribute('data-product-review')) : null;

            if (!product) {
                console.warn('Produk ulasan tidak ditemukan:', productId);
                return;
            }

            const body = document.querySelector('body');
            if (body && body.__x) {
                body.__x.$data.selectedProductForReview = product;
            }

            const modal = document.querySelector('[data-review-modal]');
            if (modal && modal.__x) {
                modal.__x.$data.selectedProductForReview = product;
            }

            window.dispatchEvent(new CustomEvent('open-review', { detail: product, bubbles: true }));
        }

        function toggleWishlist(el) {
            const productId = el.dataset.productId;
            showLoading();
            fetch("{{ route('cart.toggle-wishlist') }}", {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ product_id: productId })
            })
            .then(r => r.json())
            .then(data => {
                hideLoading();
                if (data.success) {
                    const icon = el.querySelector('i');
                    if (data.in_wishlist) {
                        icon.classList.remove('fa-regular');
                        icon.classList.add('fa-solid', 'text-brand-gold');
                    } else {
                        icon.classList.remove('fa-solid', 'text-brand-gold');
                        icon.classList.add('fa-regular');
                    }
                }
            })
            .catch(err => { hideLoading(); console.error('Wishlist error:', err); });
        }

        function initHeroMotion() {
            // If Motion One (motion) is available, run precise animations to match React
            try {
                if (window.motion && typeof window.motion.animate === 'function') {
                    const m = window.motion;
                    // badge
                    const badge = document.querySelector('.hero-badge');
                    if (badge) m.animate(badge, { opacity: [0,1], transform: ['translateY(20px)','translateY(0)'] }, { duration: 500, easing: 'cubic-bezier(.4,0,.2,1)', delay: 0 });
                    // title
                    const title = document.querySelector('.hero-title');
                    if (title) m.animate(title, { opacity: [0,1], transform: ['translateY(20px)','translateY(0)'] }, { duration: 500, easing: 'cubic-bezier(.4,0,.2,1)', delay: 100 });
                    // copy
                    const copy = document.querySelector('.hero-copy');
                    if (copy) m.animate(copy, { opacity: [0,1], transform: ['translateY(20px)','translateY(0)'] }, { duration: 500, easing: 'cubic-bezier(.4,0,.2,1)', delay: 200 });
                    // cta
                    const cta = document.querySelector('.hero-cta');
                    if (cta) m.animate(cta, { opacity: [0,1], transform: ['translateY(20px)','translateY(0)'] }, { duration: 500, easing: 'cubic-bezier(.4,0,.2,1)', delay: 300 });
                    // image container scale
                    const image = document.querySelector('.hero-image');
                    if (image) m.animate(image, { opacity: [0,1], transform: ['scale(0.95)','scale(1)'] }, { duration: 700, easing: 'cubic-bezier(.4,0,.2,1)', delay: 200 });
                    return;
                }
            } catch (e) {
                console.warn('Motion init failed', e);
            }
            // fallback: ensure CSS animations are allowed (they run by default)
            // remove any class that might prevent animations
        }

        window.addEventListener('load', initHeroMotion);
        window.addEventListener('DOMContentLoaded', initHeroMotion);
    </script>

    @stack('scripts')
</body>

</html>
