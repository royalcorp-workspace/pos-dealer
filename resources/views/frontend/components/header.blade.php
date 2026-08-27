@php
    $customerId = null;
    if (session()->get('is_logged_in')) {
        $user = session()->get('user', []);
        $userId = $user['id'] ?? $user['sub'] ?? null;
        $email = $user['email'] ?? null;
        if ($userId) {
            $customer = \App\Models\Frontend\Customer\Customer::where('user_id', $userId)->first();
            if (!$customer && $email) {
                $customer = \App\Models\Frontend\Customer\Customer::where('email', $email)->first();
            }
            $customerId = $customer?->id;
        }
    }
    $sessionId = session()->get('guest_session_id', session()->getId());
    
    $buffer = \App\Models\Frontend\Buffer\Buffer::where(function ($q) use ($customerId, $sessionId) {
        if ($customerId) {
            $q->where('customer_id', $customerId);
            if ($sessionId) {
                $q->orWhere('session_id', $sessionId);
            }
        } else if ($sessionId) {
            $q->where('session_id', $sessionId);
        }
    })->first();

    $cart = [];
    if ($buffer) {
        $cart = $buffer->items()
            ->with(['product.brand', 'variant'])
            ->get()
            ->map(function ($item) {
                $isBundle = str_starts_with($item->name ?? '', 'BUNDLE_');
                $bundleNotes = [];
                if ($isBundle && $item->item_notes) {
                    $bundleNotes = json_decode($item->item_notes, true) ?? [];
                }
                return [
                    'id' => $item->id,
                    'product_id' => $item->product_id,
                    'variant_id' => $item->product_variant_id,
                    'name' => $item->name,
                    'brand' => $item->product->brand->name ?? '',
                    'image' => $item->product->thumbnail_url ?? '',
                    'sell_price' => (float) $item->unit_price,
                    'quantity' => (int) $item->quantity,
                    'item_note' => $item->item_notes ?? '',
                    'type' => $isBundle ? 'bundle' : 'product',
                    'bundle_data' => $bundleNotes,
                ];
            })
            ->toArray();
    }
    $cartItemCount = collect($cart)->sum('quantity');
    $cartTotal = collect($cart)->sum(function($item) {
        return ($item['sell_price'] ?? 0) * ($item['quantity'] ?? 0);
    });
    $isLoggedIn = session()->get('is_logged_in', false);
    $user = session()->get('user');
             $wishlist = session()->get('wishlist', []);
            $wishlistCount = count($wishlist);

            if (session()->get('is_logged_in')) {
                $userId = session()->get('user')['id'] ?? session()->get('user')['sub'] ?? null;
                $wishlistCount = \App\Models\Frontend\ProductsCatalog\Wishlist::where('user_id', $userId)->count();
            }

    $currentLocale = session()->get('locale', 'id');
    $unreadNotificationCount = 0;
    try {
        $unreadNotificationCount = \App\Models\Frontend\Notification::where('is_active', true)
            ->where('deleted', false)
            ->where('is_read', false)
            ->where(function ($q) {
                $q->whereNull('published_at')->orWhere('published_at', '<=', now());
            })
            ->count();
    } catch (\Throwable $e) {
        $unreadNotificationCount = 0;
    }

    try {
        $brands = \App\Models\Frontend\ProductsCatalog\Brand::where('deleted', false)
            ->orderBy('sort_order')
            ->get();
        $categories = \App\Models\Frontend\ProductsCatalog\ProductCategory::where('deleted', false)
            ->whereNull('parent_id')
            ->with('children.children')
            ->orderBy('sort_order')
            ->get();
    } catch (\Throwable $e) {
        $brands = collect();
        $categories = collect();
    }
@endphp

<header class="w-full bg-white border-b border-gray-100 sticky top-0 z-40 shadow-sm font-sans" x-data="{ activeMegaMenu: null, searchOpen: false, isMobileMenuOpen: false }">
    <!-- Top Bar -->
    <div class="container mx-auto px-4 md:px-6 h-auto py-3 md:h-20 md:py-0 flex flex-nowrap items-center justify-between gap-3 md:gap-6">
        <!-- Logo -->
        <div class="flex items-center gap-3 flex-shrink-0">
            <button 
                class="md:hidden text-gray-700 hover:text-brand-gold transition-colors focus:outline-none relative w-6 h-6 flex-shrink-0"
                @click="isMobileMenuOpen = !isMobileMenuOpen"
                aria-label="Buka menu"
            >
                <!-- menu icon -->
                <svg :class="isMobileMenuOpen ? 'opacity-0 scale-50' : 'opacity-100 scale-100'" class="w-6 h-6 absolute inset-0 transition-all duration-300 transform" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M4 6h16M4 12h16M4 18h16" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                <!-- close icon -->
                <svg :class="isMobileMenuOpen ? 'opacity-100 scale-100' : 'opacity-0 scale-50'" class="w-6 h-6 absolute inset-0 transition-all duration-300 transform" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M18 6L6 18M6 6l12 12" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </button>
            <a href="{{ route('home') }}" class="flex items-center gap-2.5 group text-left">
                <span class="text-3xl lg:text-4xl font-extrabold tracking-tight text-brand-dark font-serif group-hover:text-brand-gold-dark transition-colors">
                    IMG
                </span>
                <span class="hidden xl:block text-[10px] lg:text-[11px] font-sans tracking-[0.18em] text-gray-500 uppercase leading-tight border-l border-gray-200 pl-2.5">
                    International<br/><strong class="text-brand-dark font-bold">Mattress Gallery</strong>
                </span>
            </a>
        </div>

        <!-- Search Bar -->
        <div class="hidden md:flex flex-1 max-w-xl mx-2 lg:mx-4 relative" x-data="{
            query: '{{ request('value', '') }}',
            suggestions: [],
            showSuggestions: false,
            loading: false,
            debounce: null,
            fetchSuggestions() {
                if (this.query.length < 2) {
                    this.suggestions = [];
                    this.showSuggestions = false;
                    return;
                }
                this.loading = true;
                this.showSuggestions = true;
                clearTimeout(this.debounce);
                this.debounce = setTimeout(async () => {
                    try {
                        const res = await fetch('/products/search-suggestions?q=' + encodeURIComponent(this.query));
                        const data = await res.json();
                        this.suggestions = data;
                    } catch (e) {
                        console.error(e);
                    } finally {
                        this.loading = false;
                    }
                }, 300);
            }
        }" @click.outside="showSuggestions = false">
            <form action="{{ route('products.index') }}" method="GET" class="relative w-full z-50">
                <input type="hidden" name="type" value="search">
                <input 
                    type="text" 
                    name="value"
                    x-model="query"
                    @input="fetchSuggestions()"
                    @focus="if(query.length >= 2) showSuggestions = true"
                    placeholder="{{ __('Cari kasur, spring bed, aksesoris tidur...') }}" 
                    class="w-full bg-gray-50/80 hover:bg-white focus:bg-white border border-gray-200 focus:border-brand-gold text-gray-800 text-sm rounded-full pl-5 pr-20 py-2.5 focus:outline-none focus:ring-3 focus:ring-brand-gold/15 transition-all placeholder:text-gray-400 shadow-2xs"
                    autocomplete="off"
                />
                <!-- Clear Button Desktop -->
                <button 
                    type="button" 
                    x-show="query.length > 0" 
                    @click="query = ''; suggestions = []; showSuggestions = false; $el.closest('form').querySelector('input[name=value]').focus()" 
                    class="absolute right-10 top-1/2 -translate-y-1/2 p-1 text-stone-400 hover:text-brand-dark transition-colors cursor-pointer"
                    aria-label="Hapus pencarian"
                    style="display: none;"
                >
                    <i class="fa-solid fa-circle-xmark text-sm"></i>
                </button>
                <button type="submit" class="absolute right-1.5 top-1.5 p-2 bg-brand-dark hover:bg-brand-darker text-white rounded-full transition-colors flex items-center justify-center min-w-[28px] shadow-2xs cursor-pointer" aria-label="Cari">
                    <i class="fa-solid fa-magnifying-glass text-xs" x-show="!loading"></i>
                    <i class="fa-solid fa-spinner fa-spin text-xs" x-show="loading" style="display: none;"></i>
                </button>
            </form>

            <!-- Search Suggestions Dropdown -->
            <div 
                x-show="showSuggestions" 
                x-transition
                style="display: none;"
                class="absolute top-full left-0 right-0 mt-2 bg-white rounded-2xl shadow-2xl border border-gray-100 overflow-hidden z-[100]"
            >
                <div x-show="suggestions.length > 0" class="flex flex-col">
                    <div class="px-4 py-2.5 bg-gray-50/80 border-b border-gray-100 flex items-center justify-between">
                        <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">{{ __('Produk Terkait') }}</span>
                        <span class="text-[10px] text-brand-gold-dark font-medium" x-text="suggestions.length + ' Ditemukan'"></span>
                    </div>
                    <template x-for="item in suggestions" :key="item.id">
                        <a :href="'/products/' + item.slug" class="flex items-center gap-3 p-3 hover:bg-brand-light/40 transition-colors border-b border-gray-50 last:border-0 group">
                            <div class="w-11 h-11 rounded-lg bg-gray-50 border border-gray-100 overflow-hidden flex-shrink-0">
                                <img :src="item.thumbnail_url || '{{ asset('images/dummy/header.jpg') }}'" :alt="item.name" class="w-full h-full object-cover">
                            </div>
                            <div class="flex-1 min-w-0">
                                <h4 class="text-xs sm:text-sm font-bold text-brand-dark truncate group-hover:text-brand-gold-dark transition-colors" x-text="item.name"></h4>
                                <div class="flex items-center gap-2 mt-0.5 text-xs">
                                    <span class="text-gray-400 font-medium truncate max-w-[120px]" x-text="item.category"></span>
                                    <span class="text-gray-300">•</span>
                                    <span class="font-extrabold text-brand-dark truncate" x-text="'Rp ' + Number(item.price).toLocaleString('id-ID')"></span>
                                </div>
                            </div>
                        </a>
                    </template>
                    <a :href="'/products?type=search&value=' + encodeURIComponent(query)" class="block text-center py-2.5 text-xs font-bold text-brand-dark hover:text-brand-gold-dark hover:bg-brand-light/50 transition-colors border-t border-gray-100">
                        {{ __('Lihat Semua Hasil Pencarian') }} <i class="fa-solid fa-arrow-right ml-1"></i>
                    </a>
                </div>
                <div x-show="suggestions.length === 0 && !loading" class="p-6 text-center text-gray-500">
                    <i class="fa-solid fa-box-open mb-2 text-2xl text-gray-200"></i>
                    <p class="text-xs font-medium">{{ __('Tidak menemukan produk untuk pencarian ini.') }}</p>
                </div>
            </div>
        </div>

        <!-- Right Actions -->
        <div class="flex items-center gap-2 sm:gap-3">
                        <!-- Language Switcher -->
            <div x-data="{ openLang: false }" class="relative hidden sm:block z-50">
                <button 
                    @click="openLang = !openLang"
                    @click.outside="openLang = false"
                    class="flex items-center gap-1.5 px-3 h-10 rounded-full bg-gray-50 hover:bg-brand-gold/15 border border-gray-200/80 transition-all focus:outline-none text-sm font-bold text-gray-700 hover:text-brand-dark"
                >
                    <i class="fa-solid fa-globe text-brand-gold"></i>
                    <span class="uppercase">{{ app()->getLocale() }}</span>
                    <i class="fa-solid fa-chevron-down text-[10px] ml-0.5 text-gray-400"></i>
                </button>
                <div 
                    x-show="openLang" 
                    x-cloak
                    x-transition:enter="transition ease-out duration-200 transform"
                    x-transition:enter-start="opacity-0 scale-95"
                    x-transition:enter-end="opacity-100 scale-100"
                    class="absolute right-0 mt-2 w-36 bg-white border border-gray-100 rounded-xl shadow-xl py-2 z-50 overflow-hidden"
                >
                    <a href="{{ route('lang.switch', 'id') }}" class="flex items-center gap-2 px-4 py-2 hover:bg-brand-light text-sm font-bold {{ app()->getLocale() === 'id' ? 'text-brand-gold-dark bg-brand-light/50' : 'text-brand-dark' }}">
                        <span class="w-4 h-4 flex items-center justify-center rounded-full border border-gray-200 overflow-hidden text-[10px]">🇮🇩</span> 
                        Indonesia
                    </a>
                    <a href="{{ route('lang.switch', 'en') }}" class="flex items-center gap-2 px-4 py-2 hover:bg-brand-light text-sm font-bold {{ app()->getLocale() === 'en' ? 'text-brand-gold-dark bg-brand-light/50' : 'text-brand-dark' }}">
                        <span class="w-4 h-4 flex items-center justify-center rounded-full border border-gray-200 overflow-hidden text-[10px]">🇬🇧</span> 
                        English
                    </a>
                </div>
            </div>

            <!-- Wishlist Button -->
            <a 
                id="wishlist-link"
                href="{{ route('wishlist.index') }}" 
                class="flex items-center justify-center w-10 h-10 rounded-full bg-gray-50 hover:bg-brand-gold/15 border border-gray-200/80 transition-all focus:outline-none relative group" 
                aria-label="Wishlist ({{ $wishlistCount }} Produk)"
                title="Favorit Saya"
            >
                <div class="relative">
                    <i id="wishlist-icon" class="fa-{{ $wishlistCount > 0 ? 'solid' : 'regular' }} fa-heart text-base {{ $wishlistCount > 0 ? 'text-red-500' : 'text-gray-700 group-hover:text-brand-dark' }}"></i>
                    @if($wishlistCount > 0)
                        <span id="wishlist-count-badge" class="absolute -top-1.5 -right-2 bg-red-500 text-white text-[9px] font-extrabold min-w-[14px] h-[14px] px-1 rounded-full flex items-center justify-center shadow-xs">
                            {{ $wishlistCount }}
                        </span>
                    @endif
                </div>
            </a>

            <!-- Notification Bell -->
            <div x-data="{ open: false }" class="relative">
                <button 
                    @click="open = !open; if(open) { fetchNotifications(); }"
                    @click.outside="open = false"
                    class="flex items-center justify-center w-10 h-10 rounded-full bg-gray-50 hover:bg-brand-gold/15 border border-gray-200/80 transition-all focus:outline-none relative group cursor-pointer"
                    aria-label="Notifikasi"
                    title="Pemberitahuan"
                >
                    <i class="fa-regular fa-bell text-base text-gray-700 group-hover:text-brand-dark"></i>
                    @if($unreadNotificationCount > 0)
                        <span class="absolute -top-1 -right-1 bg-brand-gold text-white text-[9px] font-extrabold min-w-[14px] h-[14px] px-1 rounded-full flex items-center justify-center shadow-xs">
                            {{ $unreadNotificationCount > 9 ? '9+' : $unreadNotificationCount }}
                        </span>
                    @endif
                </button>

                <div 
                    x-show="open" 
                    x-cloak
                    x-transition:enter="transition ease-out duration-200 transform"
                    x-transition:enter-start="opacity-0 scale-95"
                    x-transition:enter-end="opacity-100 scale-100"
                    class="absolute right-0 mt-2 w-80 bg-white border border-gray-100 rounded-2xl shadow-xl py-2 z-50"
                >
                    <div class="px-4 py-2 border-b border-gray-100 flex justify-between items-center">
                        <h3 class="font-bold text-xs text-gray-800 uppercase tracking-wider">{{ __('Notifikasi') }}</h3>
                        <button onclick="markAllRead()" class="text-xs text-brand-gold-dark hover:text-brand-dark font-semibold cursor-pointer">
                            {{ __('Tandai Dibaca') }}
                        </button>
                    </div>
                    <div id="notification-list" class="max-h-80 overflow-y-auto">
                        <div class="p-4 text-center text-xs text-gray-500">{{ __('Memuat...') }}</div>
                    </div>
                    <div class="border-t border-gray-100 px-4 py-2">
                        <a href="{{ route('notifications.index') }}" class="text-center text-xs font-bold text-brand-dark hover:text-brand-gold-dark block">
                            {{ __('Lihat Semua Notifikasi →') }}
                        </a>
                    </div>
                </div>
            </div>

            <div class="h-5 w-px bg-gray-200 hidden sm:block mx-1"></div>

            <!-- User Auth / Dashboard Module -->
            @if($isLoggedIn)
                <a 
                    href="{{ route('dashboard') }}"
                    class="flex items-center gap-2 px-3 py-1.5 rounded-full bg-brand-light/60 hover:bg-brand-light border border-brand-muted/80 text-brand-dark text-xs font-bold transition-all group"
                >
                    <div class="w-6 h-6 rounded-full bg-brand-dark flex items-center justify-center text-brand-gold font-bold text-[10px]">
                        {{ substr($user['name'] ?? 'B', 0, 1) }}
                    </div>
                    <span class="hidden lg:block">{{ __('Akun') }}</span>
                </a>
            @else
                <button 
                    @click="isAuthOpen = true"
                    class="flex items-center gap-2 px-3.5 py-2 rounded-full bg-brand-dark hover:bg-brand-darker text-white text-xs font-bold shadow-2xs hover:shadow-sm transition-all duration-200 cursor-pointer focus:outline-none group active:scale-95"
                >
                    <i class="fa-solid fa-user text-[11px] text-brand-gold group-hover:scale-110 transition-transform"></i>
                    <span class="hidden sm:inline-block">{{ __('Masuk') }}</span>
                </button>
            @endif

            <!-- Cart Drawer Trigger (Dynamic State: Clean Icon when Empty, Expanding Pill when Loaded) -->
            <button 
                x-data="{ count: {{ $cartItemCount }}, total: {{ $cartTotal }} }"
                @cart-added.window="if($event.detail.cart_count !== undefined) { count = $event.detail.cart_count; total = $event.detail.cart_total || 0; }"
                @cart-drawer-updated.window="
                    // Fetch if needed, or rely on the JS DOM updates.
                    // Actually, let's just let the DOM update the text, but Alpine handles visibility
                    setTimeout(() => {
                        let badge = document.getElementById('cart-count-badge');
                        if (badge) count = parseInt(badge.textContent) || 0;
                        let totalEl = document.getElementById('header-cart-total');
                        if (totalEl) total = parseFloat(totalEl.textContent.replace(/[^0-9]/g, '')) || 0;
                    }, 100);
                "
                @click="isCartOpen = true"
                class="flex items-center transition-all duration-300 focus:outline-none cursor-pointer group relative"
                :class="count > 0 ? 'bg-brand-light/80 hover:bg-brand-gold/15 px-3 sm:px-4 py-2 rounded-full border border-brand-muted/80 hover:border-brand-gold/50 gap-2 sm:gap-2.5' : 'justify-center w-10 h-10 rounded-full bg-gray-50 hover:bg-brand-gold/15 border border-gray-200/80'"
                title="Buka Keranjang"
                aria-label="Keranjang Belanja"
            >
                <div class="relative">
                    <i class="fa-solid fa-bag-shopping text-base text-gray-700 group-hover:text-brand-dark transition-colors"></i>
                    <span 
                        id="cart-count-badge" 
                        x-show="count > 0"
                        x-transition:enter="transition ease-out duration-300 transform"
                        x-transition:enter-start="opacity-0 scale-50"
                        x-transition:enter-end="opacity-100 scale-100"
                        x-cloak
                        class="absolute -top-2 -right-2 bg-brand-dark text-brand-gold text-[9px] font-black min-w-[16px] h-[16px] px-1 rounded-full flex items-center justify-center shadow-xs border border-brand-gold/40"
                    >
                        {{ $cartItemCount }}
                    </span>
                </div>
                <div x-show="count > 0" x-cloak class="hidden sm:flex flex-col items-start leading-none">
                    <span class="text-[9px] text-gray-400 font-bold uppercase tracking-wider">Keranjang</span>
                    <span id="header-cart-total" class="text-xs font-extrabold text-brand-dark group-hover:text-brand-gold-dark transition-colors mt-0.5 font-sans">
                        Rp {{ number_format($cartTotal, 0, ',', '.') }}
                    </span>
                </div>
            </button>
        </div>
    </div>

    <!-- Mobile Search Bar -->
    <div class="md:hidden px-4 pb-3" x-data="{ mobileQuery: '{{ request('type') === 'search' ? request('value', '') : '' }}' }">
        <form action="{{ route('products.index') }}" method="GET" class="relative w-full">
            <input type="hidden" name="type" value="search">
            <input 
                type="text" 
                name="value"
                x-model="mobileQuery"
                placeholder="Cari produk..." 
                class="w-full bg-brand-light border border-brand-muted text-gray-800 text-sm rounded-full pl-4 pr-18 py-2 focus:outline-none focus:ring-2 focus:ring-brand-gold/50"
            />
            <!-- Clear Button Mobile -->
            <button 
                type="button" 
                x-show="mobileQuery.length > 0" 
                @click="mobileQuery = ''; $el.closest('form').querySelector('input[name=value]').focus()" 
                class="absolute right-9 top-1/2 -translate-y-1/2 p-1 text-stone-400 hover:text-brand-dark transition-colors cursor-pointer"
                aria-label="Hapus pencarian"
                style="display: none;"
            >
                <i class="fa-solid fa-circle-xmark text-sm"></i>
            </button>
            <button type="submit" class="absolute right-1 top-1 p-1 bg-brand-dark text-white rounded-full" aria-label="Cari">
                <i class="fa-solid fa-magnifying-glass w-4 h-4"></i>
            </button>
        </form>
    </div>

    <!-- Clean Minimalist Navigation (Desktop) -->
    <nav class="hidden md:block w-full border-t border-brand-muted/50 bg-white" aria-label="Navigasi utama">
        <div class="container mx-auto px-6">
            <ul class="flex items-center gap-8 h-14 relative">
                <!-- Home -->
                <li class="h-full flex items-center" @mouseenter="activeMegaMenu = null">
                    <a href="{{ route('home') }}" class="py-2 text-sm font-semibold text-brand-dark hover:text-brand-gold-dark transition-colors relative {{ request()->routeIs('home') ? 'text-brand-gold-dark font-bold' : '' }}">
                        {{ __('Home') }}
                        @if(request()->routeIs('home'))
                            <span class="absolute bottom-0 left-0 right-0 h-0.5 bg-brand-gold"></span>
                        @endif
                    </a>
                </li>

                <!-- Kasur & Kategori Dropdown Trigger -->
                <li 
                    class="h-full flex items-center cursor-pointer relative"
                    @mouseenter="activeMegaMenu = 'categories'"
                    @mouseleave="activeMegaMenu = null"
                >
                    <a href="{{ route('categories') }}" class="nav-link text-sm font-semibold text-brand-dark hover:text-brand-gold-dark transition-colors flex items-center gap-1.5 focus:outline-hidden py-2 {{ request()->routeIs('categories*') || request()->routeIs('category.*') ? 'text-brand-gold-dark font-bold' : '' }}">
                        {{ __('Kasur & Kategori') }} 
                        <svg class="w-3.5 h-3.5 text-gray-400 group-hover:text-brand-gold-dark transition-transform duration-200" :class="activeMegaMenu === 'categories' ? 'rotate-180 text-brand-gold' : ''" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M6 9l6 6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </a>

                    <!-- Clean Dropdown Content -->
                    <div 
                        x-show="activeMegaMenu === 'categories'"
                        x-cloak
                        x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0 translate-y-2 scale-98"
                        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                        x-transition:leave="transition ease-in duration-150"
                        x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                        x-transition:leave-end="opacity-0 translate-y-2 scale-98"
                        class="absolute top-full left-0 w-[500px] lg:w-[600px] bg-white shadow-xl border border-brand-muted/80 rounded-2xl p-4 z-50 overflow-hidden"
                    >
                        <div class="grid grid-cols-2 gap-x-4 gap-y-2">
                            @foreach($categories as $category)
                                <a 
                                    href="{{ route('category.show', $category->slug) }}" 
                                    class="flex items-center justify-between p-2.5 rounded-xl hover:bg-brand-light transition-colors group text-left"
                                >
                                    <div>
                                        <span class="font-bold text-brand-dark text-sm group-hover:text-brand-gold-dark transition-colors block">
                                            {{ html_entity_decode($category->name) }}
                                        </span>
                                        @if($category->description)
                                            <span class="text-[11px] text-gray-500 line-clamp-1 mt-0.5">
                                                {{ $category->description }}
                                            </span>
                                        @endif
                                    </div>
                                    <svg class="w-4 h-4 text-gray-300 group-hover:text-brand-gold group-hover:translate-x-0.5 transition-all" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M9 6l6 6-6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                </a>
                            @endforeach
                            <div class="pt-2 border-t border-brand-muted/50 mt-2">
                                <a href="{{ route('categories') }}" class="flex items-center justify-center gap-1.5 py-2 text-xs font-bold text-brand-gold-dark hover:text-brand-dark transition-colors">
                                    <span>{{ __('Semua Kategori Produk') }}</span>
                                    <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M9 6l6 6-6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                </a>
                            </div>
                        </div>
                    </div>
                </li>

                <!-- Brand Dropdown Trigger -->
                <li 
                    class="h-full flex items-center cursor-pointer relative"
                    @mouseenter="activeMegaMenu = 'brands'"
                    @mouseleave="activeMegaMenu = null"
                >
                    <a href="{{ route('brands') }}" class="nav-link text-sm font-semibold text-brand-dark hover:text-brand-gold-dark transition-colors flex items-center gap-1.5 focus:outline-hidden py-2 {{ request()->routeIs('brands*') ? 'text-brand-gold-dark font-bold' : '' }}">
                        {{ __('Brand') }} 
                        <svg class="w-3.5 h-3.5 text-gray-400 group-hover:text-brand-gold-dark transition-transform duration-200" :class="activeMegaMenu === 'brands' ? 'rotate-180 text-brand-gold' : ''" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M6 9l6 6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </a>
                    
                    <!-- Clean Brands Dropdown Content -->
                    <div 
                        x-show="activeMegaMenu === 'brands'"
                        x-cloak
                        x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0 translate-y-2 scale-98"
                        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                        x-transition:leave="transition ease-in duration-150"
                        x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                        x-transition:leave-end="opacity-0 translate-y-2 scale-98"
                        class="absolute top-full left-0 w-max min-w-48 pr-6 bg-white shadow-xl border border-brand-muted/80 rounded-2xl p-4 z-50 overflow-hidden"
                    >
                        <div class="flex flex-col gap-y-1">
                            @foreach($brands as $brand)
                                <a 
                                    href="{{ route('brands.show', $brand->slug) }}" 
                                    class="flex items-center p-2.5 rounded-xl hover:bg-brand-light transition-colors group text-left"
                                >
                                    <span class="font-bold text-brand-dark text-sm group-hover:text-brand-gold-dark transition-colors">
                                        {{ html_entity_decode($brand->name) }}
                                    </span>
                                </a>
                            @endforeach
                            <div class="pt-2 border-t border-brand-muted/50 mt-2">
                                <a href="{{ route('brands') }}" class="flex items-center justify-center gap-1.5 py-2 text-xs font-bold text-brand-gold-dark hover:text-brand-dark transition-colors">
                                    <span>{{ __('Lihat Semua Brand') }}</span>
                                    <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M9 6l6 6-6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                </a>
                            </div>
                        </div>
                    </div>
                </li>

                <!-- Promo Spesial -->
                <li class="h-full flex items-center" @mouseenter="activeMegaMenu = null">
                    <a href="{{ route('promos') }}" class="nav-link text-sm font-semibold text-brand-dark hover:text-brand-gold-dark transition-colors flex items-center gap-2 py-2 {{ request()->routeIs('promos') ? 'text-brand-gold-dark font-bold' : '' }}">
                        {{ __('Promo Spesial') }}
                        <span class="px-2 py-0.5 rounded-full bg-red-500/10 text-red-600 text-[10px] font-bold uppercase tracking-wider">Hot</span>
                    </a>
                </li>
                
                <!-- Bundling Hemat -->
                <li class="h-full flex items-center" @mouseenter="activeMegaMenu = null">
                    <a href="{{ route('bundling.index') }}" class="nav-link text-sm font-semibold text-brand-dark hover:text-brand-gold-dark transition-colors py-2 {{ request()->routeIs('bundling.*') ? 'text-brand-gold-dark font-bold' : '' }}">
                        {{ __('Bundling Hemat') }}
                    </a>
                </li>
                
                <!-- Bantuan -->
                <li class="h-full flex items-center" @mouseenter="activeMegaMenu = null">
                    <a href="{{ route('help') }}" class="nav-link text-sm font-semibold text-brand-dark hover:text-brand-gold-dark transition-colors py-2 {{ request()->routeIs('help') ? 'text-brand-gold-dark font-bold' : '' }}">
                        {{ __('Bantuan') }}
                    </a>
                </li>
            </ul>
        </div>
    </div>

    <!-- Mobile Search Bar -->
    <div class="md:hidden px-4 pb-3">
        <form action="{{ route('products.index') }}" method="GET" class="relative w-full">
            <input type="hidden" name="type" value="search">
            <input 
                type="text" 
                name="value"
                placeholder="Cari produk..." 
                class="w-full bg-brand-light border border-brand-muted text-gray-800 text-sm rounded-full pl-4 pr-12 py-2 focus:outline-none focus:ring-2 focus:ring-brand-gold/50"
            />
            <button type="submit" class="absolute right-1 top-1 p-1 bg-brand-dark text-white rounded-full" aria-label="Cari">
                <i class="fa-solid fa-magnifying-glass w-4 h-4"></i>
            </button>
        </form>
    </div>

    

    <!-- Mobile Accordion Menu Overlay -->
    <div 
        x-show="isMobileMenuOpen"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 -translate-y-2"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 -translate-y-2"
        x-cloak
        class="md:hidden border-t border-brand-muted/60 bg-white overflow-hidden shadow-2xl max-h-[85vh] z-50 overflow-y-auto"
        x-data="{ openSection: null }"
    >
        <div class="p-4 space-y-4 font-sans">
            <!-- Home Link -->
            <a href="{{ route('home') }}" class="flex items-center justify-between p-3 rounded-xl bg-brand-light font-bold text-brand-dark text-sm" @click="isMobileMenuOpen = false">
                <span>{{ __('Home') }}</span>
                <svg class="w-4 h-4 text-brand-gold" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M9 6l6 6-6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </a>

                        <!-- Mobile Language Switcher -->
            <div class="flex items-center justify-between p-3 rounded-xl bg-gray-50 border border-brand-muted/70">
                <span class="text-sm font-bold text-brand-dark">{{ __('Bahasa') }}</span>
                <div class="flex items-center bg-white rounded-lg border border-gray-200 overflow-hidden shadow-xs">
                    <a href="{{ route('lang.switch', 'id') }}" class="px-3 py-1.5 text-xs font-bold transition-colors {{ app()->getLocale() === 'id' ? 'bg-brand-gold text-white' : 'text-gray-500 hover:bg-gray-100' }}">ID</a>
                    <a href="{{ route('lang.switch', 'en') }}" class="px-3 py-1.5 text-xs font-bold transition-colors {{ app()->getLocale() === 'en' ? 'bg-brand-gold text-white' : 'text-gray-500 hover:bg-gray-100' }}">EN</a>
                </div>
            </div>

            <!-- Kasur & Kategori Accordion -->
            <div class="border border-brand-muted/70 rounded-2xl overflow-hidden">
                <button 
                    @click="openSection = (openSection === 'categories' ? null : 'categories')"
                    class="w-full flex items-center justify-between p-3.5 bg-white text-left font-bold text-brand-dark text-sm focus:outline-hidden"
                >
                    <span class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-brand-gold-dark" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="7" width="18" height="13" rx="2"/><path d="M16 3v4M8 3v4"/></svg>
                        {{ __('Kasur & Kategori') }}
                    </span>
                    <svg class="w-4 h-4 text-gray-400 transition-transform duration-200" :class="openSection === 'categories' ? 'rotate-180 text-brand-gold' : ''" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M6 9l6 6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </button>
                <div x-show="openSection === 'categories'"  class="bg-brand-light/50 border-t border-brand-muted/40 p-2 space-y-1">
                    @foreach($categories as $category)
                        <a 
                            href="{{ route('category.show', $category->slug) }}" 
                            class="block p-2.5 rounded-lg text-sm text-gray-700 font-medium hover:bg-white hover:text-brand-gold-dark transition-colors text-left"
                            @click="isMobileMenuOpen = false"
                        >
                            {{ html_entity_decode($category->name) }}
                        </a>
                    @endforeach
                    <a href="{{ route('categories') }}" class="block p-2.5 text-xs font-bold text-brand-gold-dark text-left" @click="isMobileMenuOpen = false">
                        {{ __('Lihat Semua Kategori &rarr;') }}
                    </a>
                </div>
            </div>

            <!-- Brand Accordion -->
            <div class="border border-brand-muted/70 rounded-2xl overflow-hidden">
                <button 
                    @click="openSection = (openSection === 'brands' ? null : 'brands')"
                    class="w-full flex items-center justify-between p-3.5 bg-white text-left font-bold text-brand-dark text-sm focus:outline-hidden"
                >
                    <span class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-brand-gold-dark" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>
                        {{ __('Brand') }}
                    </span>
                    <svg class="w-4 h-4 text-gray-400 transition-transform duration-200" :class="openSection === 'brands' ? 'rotate-180 text-brand-gold' : ''" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M6 9l6 6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </button>
                <div x-show="openSection === 'brands'"  class="bg-brand-light/50 border-t border-brand-muted/40 p-2 space-y-1">
                    @foreach($brands as $brand)
                        <a 
                            href="{{ route('brands.show', $brand->slug) }}" 
                            class="block p-2.5 rounded-lg text-sm text-gray-700 font-medium hover:bg-white hover:text-brand-gold-dark transition-colors text-left"
                            @click="isMobileMenuOpen = false"
                        >
                            {{ html_entity_decode($brand->name) }}
                        </a>
                    @endforeach
                    <a href="{{ route('brands') }}" class="block p-2.5 text-xs font-bold text-brand-gold-dark text-left" @click="isMobileMenuOpen = false">
                        {{ __('Lihat Semua Brand &rarr;') }}
                    </a>
                </div>
            </div>

            <!-- Direct Links -->
            <div class="space-y-1 pt-2">
                <a href="{{ route('promos') }}" class="flex items-center justify-between p-3 rounded-xl hover:bg-brand-light text-sm font-bold text-brand-dark" @click="isMobileMenuOpen = false">
                    <span class="flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-red-500"></span>
                        {{ __('Promo Spesial') }}
                    </span>
                    <span class="px-2 py-0.5 rounded-full bg-red-500/10 text-red-600 text-[10px] font-bold uppercase">Hot</span>
                </a>
                <a href="{{ route('bundling.index') }}" class="flex items-center justify-between p-3 rounded-xl hover:bg-brand-light text-sm font-bold text-brand-dark" @click="isMobileMenuOpen = false">
                    <span>{{ __('Bundling Hemat') }}</span>
                    <svg class="w-4 h-4 text-gray-300" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M9 6l6 6-6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </a>
                <a href="{{ route('blog') }}" class="flex items-center justify-between p-3 rounded-xl hover:bg-brand-light text-sm font-semibold text-gray-700" @click="isMobileMenuOpen = false">
                    <span>{{ __('Blog & Artikel Tidur') }}</span>
                    <svg class="w-4 h-4 text-gray-300" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M9 6l6 6-6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </a>
                <a href="{{ route('help') }}" class="flex items-center justify-between p-3 rounded-xl hover:bg-brand-light text-sm font-semibold text-gray-700" @click="isMobileMenuOpen = false">
                    <span>{{ __('Pusat Bantuan & FAQ') }}</span>
                    <svg class="w-4 h-4 text-gray-300" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M9 6l6 6-6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </a>
            </div>
        </div>
    </div>

    <!-- Slide-down Search Panel -->
    <div x-show="searchOpen" 
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 -translate-y-4"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 -translate-y-4"
         @click.outside="searchOpen = false"
         class="absolute top-full left-0 w-full bg-white border-t border-b border-gray-100 shadow-md z-50 py-4 hidden md:block">
        <div class="container mx-auto px-6">
<!-- Search Bar -->
        <div class="flex w-full max-w-3xl mx-auto relative" x-data="{
            query: '{{ request('value', '') }}',
            suggestions: [],
            showSuggestions: false,
            loading: false,
            debounce: null,
            fetchSuggestions() {
                if (this.query.length < 2) {
                    this.suggestions = [];
                    this.showSuggestions = false;
                    return;
                }
                this.loading = true;
                this.showSuggestions = true;
                clearTimeout(this.debounce);
                this.debounce = setTimeout(async () => {
                    try {
                        const res = await fetch('/products/search-suggestions?q=' + encodeURIComponent(this.query));
                        const data = await res.json();
                        this.suggestions = data;
                    } catch (e) {
                        console.error(e);
                    } finally {
                        this.loading = false;
                    }
                }, 300);
            }
        }" @click.outside="showSuggestions = false">
            <form action="{{ route('products.index') }}" method="GET" class="relative w-full z-50">
                <input x-ref="searchInput" type="hidden" name="type" value="search">
                <input x-ref="searchInput" 
                    type="text" 
                    name="value"
                    x-model="query"
                    @input="fetchSuggestions()"
                    @focus="if(query.length >= 2) showSuggestions = true"
                    placeholder="{{ __('Cari kasur, bantal, atau brand impianmu...') }}" 
                    class="w-full bg-brand-light border border-brand-muted text-gray-800 text-sm rounded-full pl-5 pr-12 py-2.5 focus:outline-none focus:ring-2 focus:ring-brand-gold/50 focus:border-brand-gold transition-all placeholder:text-gray-400"
                    autocomplete="off"
                />
                <button type="submit" class="absolute right-1 top-1 p-1.5 bg-brand-dark hover:bg-brand-darker text-white rounded-full transition-colors flex items-center justify-center min-w-[28px]" aria-label="Cari">
                    <i class="fa-solid fa-magnifying-glass text-xs" x-show="!loading"></i>
                    <i class="fa-solid fa-spinner fa-spin text-xs" x-show="loading" style="display: none;"></i>
                </button>
            </form>

            <!-- Search Suggestions Dropdown -->
            <div 
                x-show="showSuggestions" 
                x-transition
                style="display: none;"
                class="absolute top-full left-0 right-0 mt-2 bg-white rounded-xl shadow-2xl border border-gray-100 overflow-hidden z-[100]"
            >
                <div x-show="suggestions.length > 0" class="flex flex-col">
                    <div class="px-4 py-2 bg-gray-50 border-b border-gray-100">
                        <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">{{ __('Produk Terkait') }}</span>
                    </div>
                    <template x-for="item in suggestions" :key="item.id">
                        <a :href="'/products/' + item.slug" class="flex items-center gap-3 p-3 hover:bg-brand-light/50 transition-colors border-b border-gray-50 last:border-0 group">
                            <div class="w-12 h-12 rounded-lg bg-gray-50 border border-gray-100 overflow-hidden flex-shrink-0">
                                <img :src="item.thumbnail_url || '{{ asset('images/dummy/header.jpg') }}'" :alt="item.name" class="w-full h-full object-cover">
                            </div>
                            <div class="flex-1 min-w-0">
                                <h4 class="text-sm font-extrabold text-brand-dark truncate group-hover:text-brand-gold transition-colors" x-text="item.name"></h4>
                                <div class="flex items-center gap-2 mt-1 text-xs">
                                    <span class="text-gray-500 font-medium truncate max-w-[120px]" x-text="item.category"></span>
                                    <span class="text-gray-300">•</span>
                                    <span class="font-bold text-brand-gold-dark truncate" x-text="'Rp ' + Number(item.sell_price).toLocaleString('id-ID')"></span>
                                </div>
                            </div>
                        </a>
                    </template>
                    <a :href="'/products?type=search&value=' + encodeURIComponent(query)" class="block text-center py-3 text-sm font-bold text-brand-gold hover:text-brand-gold-dark hover:bg-brand-light transition-colors border-t border-gray-100">
                        {{ __('Lihat Semua Hasil') }} <i class="fa-solid fa-arrow-right ml-1"></i>
                    </a>
                </div>
                <div x-show="suggestions.length === 0 && !loading" class="p-8 text-center text-gray-500">
                    <i class="fa-solid fa-box-open mb-3 text-3xl text-gray-200"></i>
                    <p class="text-sm font-medium">{{ __('Tidak menemukan produk untuk pencarian ini.') }}</p>
                </div>
            </div>
        </div>

        
        </div>
    </div>
</header>
