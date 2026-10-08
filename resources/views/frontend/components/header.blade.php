@php
    $cartSummary = \App\Models\Frontend\Buffer\Buffer::getActiveCartSummary();
    $cart = $cartSummary['cart'];
    $cartItemCount = $cartSummary['cartItemCount'];
    $cartTotal = $cartSummary['cartTotal'];
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
        $userId = $isLoggedIn ? ($user['id'] ?? null) : null;
        $unreadNotificationCount = \App\Models\Frontend\Notification::where('is_read', false)
            ->when($userId, function ($q) use ($userId) {
                $q->where(function ($sub) use ($userId) {
                    $sub->where('user_id', $userId)->orWhere('is_broadcast', true);
                });
            }, function ($q) {
                $q->where('is_broadcast', true);
            })
            ->count();
    } catch (\Throwable $e) {
        $unreadNotificationCount = 0;
    }

    try {
        $brandOrder = ['serenity' => 1, 'lady' => 2, 'elite' => 3, 'royal' => 4, 'moro' => 5, 'tote' => 6];
        $brands = \App\Models\Frontend\ProductsCatalog\Brand::where('deleted', false)
            ->where('status', true)
            ->withCount(['products' => function ($q) {
                $q->where('deleted', false)->where('status', true);
            }])
            ->get()
            ->sortBy(function ($brand) use ($brandOrder) {
                $slug = strtolower($brand->slug);
                return [$brandOrder[$slug] ?? 50, $brand->name];
            })
            ->values();
        $categories = \App\Models\Frontend\ProductsCatalog\ProductCategory::where('deleted', false)
            ->whereNull('parent_id')
            ->with('children.children')
            ->orderBy('sort_order')
            ->get();
    } catch (\Throwable $e) {
        $brands = collect();
        $categories = collect();
    }
    // --- UI/UX Dynamic Theming ---
    $isHp3 = request()->routeIs('homepages3');
    $isHp4 = request()->routeIs('homepages4');
    
    // Theme colors
    $headerBg = 'bg-white';
    $textColor = 'text-brand-dark';
    $iconColor = 'text-gray-700 hover:text-brand-gold';
    $logoColor = 'text-brand-dark';
    $searchBg = 'bg-gray-50/80 hover:bg-white focus:bg-white border-gray-200';
    $borderBottom = 'border-b border-gray-100 shadow-sm';
    
    if ($isHp3) {
        $headerBg = 'bg-[#FCF9F3]';
        $borderBottom = 'shadow-md border-b-2 border-brand-gold/20';
        // playful look
    } elseif ($isHp4) {
        $headerBg = 'bg-brand-dark';
        $textColor = 'text-white';
        $iconColor = 'text-white hover:text-brand-gold';
        $logoColor = 'text-brand-gold';
        $searchBg = 'bg-white/10 hover:bg-white/20 focus:bg-white text-brand-dark border-white/20 focus:border-brand-gold placeholder-gray-300 focus:placeholder-gray-400';
        $borderBottom = 'border-b border-brand-gold/30 shadow-lg';
    }
@endphp

<header class="w-full {{ $headerBg }} {{ $borderBottom }} sticky top-0 z-40 font-sans {{ $textColor }}" x-data="{ activeMegaMenu: null, searchOpen: false }">
    <!-- Top Bar -->
    <div class="container mx-auto px-4 md:px-6 h-auto py-3 md:h-20 md:py-0 flex flex-nowrap items-center justify-between gap-3 md:gap-6">
        <!-- Logo -->
        <div class="flex items-center gap-3 flex-shrink-0">
            <button 
                type="button"
                id="mobile-menu-hamburger-btn"
                class="md:hidden {{ $iconColor }} transition-colors focus:outline-none relative w-8 h-8 flex items-center justify-center flex-shrink-0 cursor-pointer p-1"
                @click="isMobileMenuOpen = !isMobileMenuOpen; $dispatch('toggle-mobile-menu')"
                onclick="window.toggleMobileMenu && window.toggleMobileMenu(event)"
                aria-label="Buka menu"
            >
                <!-- menu icon -->
                <svg :class="isMobileMenuOpen ? 'opacity-0 scale-50' : 'opacity-100 scale-100'" class="w-6 h-6 transition-all duration-300 transform" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M4 6h16M4 12h16M4 18h16" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                <!-- close icon -->
                <svg :class="isMobileMenuOpen ? 'opacity-100 scale-100' : 'opacity-0 scale-50'" class="w-6 h-6 absolute transition-all duration-300 transform" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M18 6L6 18M6 6l12 12" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </button>
            <a href="{{ route('home') }}" class="flex items-center gap-2.5 group text-left">
                <span class="text-3xl lg:text-4xl font-extrabold tracking-tight font-serif {{ $logoColor }} group-hover:text-brand-gold-dark transition-colors">
                    IMG
                </span>
                <span class="hidden xl:block text-[10px] lg:text-[11px] font-sans tracking-[0.18em] text-gray-500 uppercase leading-tight border-l border-gray-200 pl-2.5">
                    International<br/><strong class="text-brand-dark font-bold">Mattress Gallery</strong>
                </span>
            </a>
        </div>

        <!-- Search Bar with Popular Searches & Keyboard Accessibility -->
        <div class="hidden md:flex flex-1 max-w-xl mx-2 lg:mx-4 relative" x-data="{
            query: '{{ request('value', '') }}',
            suggestions: [],
            showSuggestions: false,
            loading: false,
            debounce: null,
            selectedIndex: -1,
            popularSearches: [
                'Elite Orthopedic',
                'Serenity Supreme',
                'Kasur 160x200',
                'Kasur 180x200',
                'Royal Foam',
                'Bantal Guling',
                'Bed Set'
            ],
            selectPopular(term) {
                this.query = term;
                this.fetchSuggestions();
                this.$nextTick(() => {
                    if (this.$refs.desktopSearchInput) {
                        this.$refs.desktopSearchInput.focus();
                    }
                });
            },
            onKeydown(e) {
                if (!this.showSuggestions) {
                    if (e.key === 'ArrowDown' || e.key === 'Enter') {
                        this.showSuggestions = true;
                    }
                    return;
                }

                if (e.key === 'Escape') {
                    this.showSuggestions = false;
                    this.selectedIndex = -1;
                    return;
                }

                const isSearching = this.query.trim().length >= 2;
                const totalItems = isSearching ? this.suggestions.length : this.popularSearches.length;

                if (e.key === 'ArrowDown') {
                    e.preventDefault();
                    if (totalItems > 0) {
                        this.selectedIndex = (this.selectedIndex + 1) % totalItems;
                    }
                } else if (e.key === 'ArrowUp') {
                    e.preventDefault();
                    if (totalItems > 0) {
                        this.selectedIndex = (this.selectedIndex - 1 + totalItems) % totalItems;
                    }
                } else if (e.key === 'Enter') {
                    if (this.selectedIndex >= 0) {
                        e.preventDefault();
                        if (isSearching && this.suggestions[this.selectedIndex]) {
                            const item = this.suggestions[this.selectedIndex];
                            window.location.href = '/products/' + item.slug;
                        } else if (!isSearching && this.popularSearches[this.selectedIndex]) {
                            const term = this.popularSearches[this.selectedIndex];
                            window.location.href = '/products?type=search&value=' + encodeURIComponent(term);
                        }
                    }
                }
            },
            fetchSuggestions() {
                this.selectedIndex = -1;
                if (this.query.trim().length < 2) {
                    this.suggestions = [];
                    return;
                }
                this.loading = true;
                this.showSuggestions = true;
                clearTimeout(this.debounce);
                this.debounce = setTimeout(async () => {
                    try {
                        const res = await fetch('/products/search-suggestions?q=' + encodeURIComponent(this.query));
                        const data = await res.json();
                        this.suggestions = Array.isArray(data) ? data : [];
                    } catch (e) {
                        console.error(e);
                    } finally {
                        this.loading = false;
                    }
                }, 250);
            }
        }" @click.outside="showSuggestions = false; selectedIndex = -1">
            <form action="{{ route('products.index') }}" method="GET" class="relative w-full z-50" @keydown="onKeydown($event)">
                <input type="hidden" name="type" value="search">
                <input 
                    type="text" 
                    name="value"
                    x-ref="desktopSearchInput"
                    x-model="query"
                    @input="fetchSuggestions()"
                    @focus="showSuggestions = true; if(query.trim().length >= 2 && suggestions.length === 0) fetchSuggestions()"
                    placeholder="{{ __('Cari kasur, spring bed, aksesoris tidur...') }}" 
                    class="w-full {{ $searchBg }} border focus:border-brand-gold text-gray-800 text-sm rounded-full pl-5 pr-20 py-2.5 focus:outline-none focus:ring-3 focus:ring-brand-gold/15 transition-all placeholder:text-gray-400 shadow-2xs"
                    autocomplete="off"
                />
                <!-- Clear Button Desktop -->
                <button 
                    type="button" 
                    x-show="query.length > 0" 
                    @click="query = ''; suggestions = []; selectedIndex = -1; $el.closest('form').querySelector('input[name=value]').focus()" 
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

            <!-- Search Suggestions & Popular Searches Dropdown -->
            <div 
                x-show="showSuggestions" 
                x-cloak
                x-transition:enter="transition ease-out duration-150 transform"
                x-transition:enter-start="opacity-0 translate-y-1 scale-98"
                x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                x-transition:leave="transition ease-in duration-100 transform"
                x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                x-transition:leave-end="opacity-0 translate-y-1 scale-98"
                style="display: none;"
                class="absolute top-full left-0 right-0 mt-2 bg-white rounded-2xl shadow-2xl border border-gray-100 overflow-hidden z-[100]"
            >
                <!-- CASE 1: Query < 2 chars -> SHOW PENCARIAN POPULER -->
                <div x-show="query.trim().length < 2" class="p-4 sm:p-5">
                    <div class="flex items-center justify-between mb-3 pb-2 border-b border-gray-100">
                        <span class="text-xs font-bold text-gray-700 uppercase tracking-wider flex items-center gap-1.5">
                            <i class="fa-solid fa-fire text-amber-500 text-xs"></i>
                            {{ __('Pencarian Populer') }}
                        </span>
                        <span class="text-[10px] text-gray-400 font-medium">{{ __('Klik untuk mencari') }}</span>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <template x-for="(term, idx) in popularSearches" :key="term">
                            <button
                                type="button"
                                @click="selectPopular(term)"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold transition-all duration-150 cursor-pointer border"
                                :class="selectedIndex === idx ? 'bg-brand-dark text-white border-brand-dark shadow-xs' : 'bg-gray-50 text-gray-700 border-gray-200 hover:border-brand-gold hover:bg-brand-light/50 hover:text-brand-dark'"
                            >
                                <i class="fa-solid fa-magnifying-glass text-[10px] opacity-50"></i>
                                <span x-text="term"></span>
                            </button>
                        </template>
                    </div>
                    <div class="mt-3.5 pt-2.5 border-t border-gray-100/70 flex items-center justify-between text-[11px] text-gray-400">
                        <span>{{ __('Gunakan tombol panah ↑ ↓ dan Enter untuk memilih') }}</span>
                        <span class="font-mono text-[10px] bg-gray-100 px-1.5 py-0.5 rounded text-gray-500">ESC tutup</span>
                    </div>
                </div>

                <!-- CASE 2: Query >= 2 chars & Results exist -> SHOW RESULTS -->
                <div x-show="query.trim().length >= 2 && suggestions.length > 0" class="flex flex-col">
                    <div class="px-4 py-2.5 bg-gray-50/80 border-b border-gray-100 flex items-center justify-between">
                        <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">{{ __('Produk Terkait') }}</span>
                        <span class="text-[10px] text-brand-gold-dark font-medium" x-text="suggestions.length + ' Ditemukan'"></span>
                    </div>
                    <div class="max-h-80 overflow-y-auto">
                        <template x-for="(item, idx) in suggestions" :key="item.id">
                            <a 
                                :href="'/products/' + item.slug" 
                                class="flex items-center gap-3 p-3 transition-colors border-b border-gray-50 last:border-0 group cursor-pointer"
                                :class="selectedIndex === idx ? 'bg-brand-light/70 ring-1 ring-inset ring-brand-gold/30' : 'hover:bg-brand-light/40'"
                            >
                                <div class="w-11 h-11 rounded-lg bg-[#FAF8F5] border border-gray-100 overflow-hidden flex-shrink-0 flex items-center justify-center p-0.5">
                                    <img :src="item.thumbnail_url || '{{ asset('images/dummy/header.jpg') }}'" :alt="item.name" class="max-w-full max-h-full object-contain">
                                </div>
                                <div class="flex-1 min-w-0">
                                    <h4 class="text-xs sm:text-sm font-bold text-brand-dark truncate group-hover:text-brand-gold-dark transition-colors" x-text="item.name"></h4>
                                    <div class="flex items-center gap-2 mt-0.5 text-xs">
                                        <span class="text-gray-400 font-medium truncate max-w-[120px]" x-text="item.category"></span>
                                        <span class="text-gray-300">•</span>
                                        <span class="font-extrabold text-brand-dark truncate" x-text="'Rp ' + Number(item.sell_price ?? item.price ?? 0).toLocaleString('id-ID')"></span>
                                    </div>
                                </div>
                                <i class="fa-solid fa-arrow-right text-xs text-gray-300 group-hover:text-brand-gold-dark group-hover:translate-x-0.5 transition-all"></i>
                            </a>
                        </template>
                    </div>
                    <a :href="'/products?type=search&value=' + encodeURIComponent(query)" class="block text-center py-2.5 text-xs font-bold text-brand-dark hover:text-brand-gold-dark hover:bg-brand-light/50 transition-colors border-t border-gray-100">
                        {{ __('Lihat Semua Hasil Pencarian') }} <i class="fa-solid fa-arrow-right ml-1"></i>
                    </a>
                </div>

                <!-- CASE 3: Query >= 2 chars & Results Empty -->
                <div x-show="query.trim().length >= 2 && suggestions.length === 0 && !loading" class="p-6 text-center text-gray-500">
                    <i class="fa-solid fa-box-open mb-2 text-2xl text-gray-200"></i>
                    <p class="text-xs font-medium">{{ __('Tidak menemukan produk untuk pencarian ini.') }}</p>
                    <div class="mt-3">
                        <span class="text-[11px] text-gray-400 block mb-2">{{ __('Coba kata kunci populer berikut:') }}</span>
                        <div class="flex flex-wrap justify-center gap-1.5">
                            <template x-for="term in popularSearches.slice(0, 4)" :key="term">
                                <button
                                    type="button"
                                    @click="selectPopular(term)"
                                    class="text-[11px] font-semibold text-brand-gold-dark bg-brand-gold/10 hover:bg-brand-gold/20 px-2.5 py-1 rounded-full transition-colors cursor-pointer"
                                    x-text="term"
                                ></button>
                            </template>
                        </div>
                    </div>
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
                    <span id="wishlist-count-badge" class="{{ $wishlistCount > 0 ? '' : 'hidden' }} absolute -top-1.5 -right-2 bg-red-500 text-white text-[9px] font-extrabold min-w-[15px] h-[15px] px-1 rounded-full flex items-center justify-center shadow-xs ring-2 ring-white transition-transform">
                        {{ $wishlistCount }}
                    </span>
                </div>
            </a>

            <!-- Notification Bell -->
            <div x-data="{ open: false }" class="relative">
                <button 
                    @click="open = !open; if(open) { if (typeof fetchNotifications === 'function') { fetchNotifications(); } else if (window.fetchNotifications) { window.fetchNotifications(); } }"
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

            <script>
            if (typeof window.fetchNotifications !== 'function') {
                window.fetchNotifications = function () {
                    var listEl = document.getElementById('notification-list');
                    if (!listEl) return;

                    fetch('/notifications', {
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                    })
                    .then(function (r) { return r.json(); })
                    .then(function (data) {
                        var notifications = data.notifications || [];
                        if (notifications.length === 0) {
                            listEl.innerHTML = '<div class="p-4 text-center text-xs text-gray-500">{{ __("Belum ada notifikasi.") }}</div>';
                            return;
                        }

                        var html = '';
                        notifications.forEach(function (n) {
                            var dateStr = n.published_at || (n.created_at ? new Date(n.created_at).toLocaleDateString('id-ID') : '');
                            html += '<div class="p-3 border-b border-gray-50 last:border-0">' +
                                '<div class="flex gap-3">' +
                                '<div class="flex-1">' +
                                '<p class="text-xs font-semibold text-gray-800">' + (n.title || '') + '</p>' +
                                '<p class="text-xs text-gray-500 mt-0.5">' + (n.message || '') + '</p>' +
                                '<span class="text-[10px] text-gray-400 mt-1 block">' + dateStr + '</span>' +
                                '</div>' +
                                (n.is_read ? '' : '<span class="w-2 h-2 bg-brand-gold rounded-full flex-shrink-0 mt-0.5"></span>') +
                                '</div></div>';
                        });
                        listEl.innerHTML = html;
                    })
                    .catch(function () {
                        listEl.innerHTML = '<div class="p-4 text-center text-xs text-gray-500">{{ __("Gagal memuat notifikasi.") }}</div>';
                    });
                };
            }
            if (typeof window.markAllRead !== 'function') {
                window.markAllRead = function () {
                    fetch('/notifications/read-all', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') || {}).content,
                            'Accept': 'application/json',
                        },
                    })
                    .then(function (r) { return r.json(); })
                    .then(function () {
                        if (typeof window.fetchNotifications === 'function') {
                            window.fetchNotifications();
                        }
                        var countBadge = document.querySelector('header .bg-brand-gold.text-white');
                        if (countBadge && countBadge.closest('button[aria-label="Notifikasi"]')) {
                            countBadge.style.display = 'none';
                        }
                    });
                };
            }
            </script>

            <div class="h-5 w-px bg-gray-200 hidden sm:block mx-1"></div>

            <!-- User Auth / Dashboard Module -->
            @if($isLoggedIn)
                <a 
                    href="{{ route('dashboard') }}"
                    class="flex items-center gap-2 px-3 py-1.5 rounded-full bg-brand-light/60 hover:bg-brand-light border border-brand-muted/80 text-brand-dark text-xs font-bold transition-all group"
                    title="{{ $user['name'] ?? __('Akun') }}"
                >
                    <div class="w-6 h-6 rounded-full bg-brand-dark flex items-center justify-center text-brand-gold font-bold text-[10px] shrink-0">
                        {{ strtoupper(substr($user['name'] ?? 'B', 0, 1)) }}
                    </div>
                    <span class="hidden sm:inline-block max-w-[130px] lg:max-w-[170px] truncate">{{ $user['name'] ?? __('Akun') }}</span>
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

            <!-- Cart Drawer Trigger (Dynamic State: Clean Icon when Empty, Expanding Pill with Item Count when Loaded) -->
            <button 
                x-data="{ 
                    count: {{ $cartItemCount }}, 
                    total: {{ $cartTotal }},
                    isBumping: false,
                    triggerBump() {
                        this.isBumping = true;
                        setTimeout(() => { this.isBumping = false; }, 400);
                    },
                    init() {
                        localStorage.setItem('cart_count', {{ $cartItemCount }});
                        localStorage.setItem('cart_total', {{ $cartTotal }});
                        this.$watch('count', (val, oldVal) => {
                            localStorage.setItem('cart_count', val);
                            if (val !== oldVal) this.triggerBump();
                        });
                        this.$watch('total', val => localStorage.setItem('cart_total', val));
                    }
                }"
                @cart-added.window="if($event.detail.cart_count !== undefined) { count = $event.detail.cart_count; total = $event.detail.cart_total || 0; triggerBump(); }"
                @cart-updated.window="if($event.detail.count !== undefined) { count = $event.detail.count; total = $event.detail.total || 0; triggerBump(); }"
                @cart-drawer-updated.window="
                    setTimeout(() => {
                        let badge = document.getElementById('cart-count-badge');
                        if (badge) count = parseInt(badge.textContent) || 0;
                        let totalEl = document.getElementById('header-cart-total');
                        if (totalEl) total = parseFloat(totalEl.textContent.replace(/[^0-9]/g, '')) || 0;
                        triggerBump();
                    }, 100);
                "
                @click="isCartOpen = true"
                class="flex items-center transition-all duration-300 focus:outline-none cursor-pointer group relative"
                :class="[
                    count > 0 ? 'bg-amber-50/90 hover:bg-brand-gold/20 px-3 sm:px-3.5 py-1.5 sm:py-2 rounded-full border-2 border-brand-gold/70 hover:border-brand-gold shadow-xs gap-2 sm:gap-2.5' : 'justify-center w-10 h-10 rounded-full bg-gray-50 hover:bg-brand-gold/15 border border-gray-200/80',
                    isBumping ? 'scale-105' : 'scale-100'
                ]"
                title="Buka Keranjang"
                aria-label="Keranjang Belanja"
            >
                <div class="relative">
                    <i class="fa-solid fa-bag-shopping text-base text-gray-700 group-hover:text-brand-dark transition-transform" :class="isBumping ? 'text-brand-gold-dark scale-110' : ''"></i>
                    <span 
                        id="cart-count-badge" 
                        x-text="count"
                        class="absolute -top-2.5 -right-2.5 text-[10px] font-black min-w-[18px] h-[18px] px-1 rounded-full flex items-center justify-center shadow-xs ring-2 ring-white tabular-nums transition-all duration-300"
                        :class="[
                            count > 0 ? 'bg-red-600 text-white' : 'bg-stone-300 text-stone-600',
                            isBumping ? 'scale-125 animate-bounce ring-4 ring-brand-gold/50 shadow-md' : 'scale-100'
                        ]"
                    >
                        {{ $cartItemCount }}
                    </span>
                </div>
                <div x-show="count > 0" x-cloak class="flex flex-col items-start leading-tight">
                    <span class="text-[10px] sm:text-[11px] font-black text-brand-dark uppercase tracking-wider flex items-center gap-1 font-sans">
                        <span id="header-cart-items-text" x-text="count + ' Barang'">{{ $cartItemCount }}</span>
                    </span>
                    <span id="header-cart-total" class="text-[11px] sm:text-xs font-extrabold text-brand-gold-dark group-hover:text-brand-dark transition-colors font-sans">
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

                <!-- Kasur & Kategori Dropdown Trigger with Auto-Positioning -->
                <li 
                    class="h-full flex items-center cursor-pointer relative"
                    x-data="{
                        dropdownAlign: 'left',
                        checkBoundary() {
                            this.$nextTick(() => {
                                const el = this.$refs.categoriesDropdown;
                                if (!el) return;
                                const rect = el.getBoundingClientRect();
                                if (rect.right > (window.innerWidth - 16)) {
                                    this.dropdownAlign = 'right';
                                } else if (rect.left < 16) {
                                    this.dropdownAlign = 'left';
                                }
                            });
                        }
                    }"
                    @mouseenter="activeMegaMenu = 'categories'; checkBoundary()"
                    @mouseleave="activeMegaMenu = null"
                >
                    <a href="{{ route('categories') }}" class="nav-link text-sm font-semibold text-brand-dark hover:text-brand-gold-dark transition-colors flex items-center gap-1.5 focus:outline-hidden py-2 {{ request()->routeIs('categories*') || request()->routeIs('category.*') ? 'text-brand-gold-dark font-bold' : '' }}">
                        {{ __('Produk Kategori') }} 
                        <svg class="w-3.5 h-3.5 text-gray-400 group-hover:text-brand-gold-dark transition-transform duration-200" :class="activeMegaMenu === 'categories' ? 'rotate-180 text-brand-gold' : ''" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M6 9l6 6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </a>

                    <!-- Clean Dropdown Content with Auto-Positioning -->
                    <div 
                        x-ref="categoriesDropdown"
                        x-show="activeMegaMenu === 'categories'"
                        x-cloak
                        x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0 translate-y-2 scale-98"
                        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                        x-transition:leave="transition ease-in duration-150"
                        x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                        x-transition:leave-end="opacity-0 translate-y-2 scale-98"
                        class="absolute top-full w-[840px] lg:w-[960px] max-w-[calc(100vw-2.5rem)] bg-white shadow-2xl border border-stone-200/90 rounded-2xl p-6 z-50 overflow-hidden font-sans"
                        :class="dropdownAlign === 'right' ? 'right-0 left-auto' : 'left-0 right-auto'"
                    >
                        <div class="mb-4 pb-3 border-b border-gray-100 flex items-center justify-between">
                            <span class="font-bold text-sm tracking-tight text-brand-dark inline-block border-b-2 border-brand-dark pb-0.5">{{ __('Product Categories') }}</span>
                            <a href="{{ route('categories') }}" class="text-xs font-bold text-brand-gold-dark hover:text-brand-dark transition-colors flex items-center gap-1">
                                <span>{{ __('Semua Kategori') }}</span>
                                <svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M9 6l6 6-6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            </a>
                        </div>

                        <div class="space-y-4">
                            @foreach($categories as $category)
                                <div class="flex flex-col sm:flex-row sm:items-baseline gap-2 sm:gap-8 py-2.5 border-b border-gray-100/70 last:border-b-0 hover:bg-[#FAF8F5]/60 px-3 rounded-xl transition-colors">
                                    <!-- Parent (Bold) -->
                                    <a 
                                        href="{{ route('category.show', $category->slug) }}" 
                                        class="w-44 shrink-0 font-bold text-brand-dark text-base hover:text-brand-gold-dark transition-colors tracking-tight font-sans"
                                    >
                                        {{ html_entity_decode($category->name) }}
                                    </a>

                                    <!-- Children kesamping (Horizontal) -->
                                    <div class="flex flex-wrap items-baseline gap-x-8 gap-y-2 flex-1">
                                        @if($category->children && $category->children->count() > 0)
                                            @foreach($category->children as $child)
                                                <a 
                                                    href="{{ route('category.show', $child->slug) }}" 
                                                    class="text-xs sm:text-sm text-stone-700 hover:text-brand-dark hover:font-semibold hover:underline transition-all whitespace-nowrap"
                                                >
                                                    {{ html_entity_decode($child->name) }}
                                                </a>
                                            @endforeach
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </li>

                <!-- Brand Dropdown Trigger with Auto-Positioning -->
                <li 
                    class="h-full flex items-center cursor-pointer relative"
                    x-data="{
                        dropdownAlign: 'left',
                        checkBoundary() {
                            this.$nextTick(() => {
                                const el = this.$refs.brandDropdown;
                                if (!el) return;
                                const rect = el.getBoundingClientRect();
                                if (rect.right > (window.innerWidth - 16)) {
                                    this.dropdownAlign = 'right';
                                } else if (rect.left < 16) {
                                    this.dropdownAlign = 'left';
                                }
                            });
                        }
                    }"
                    @mouseenter="activeMegaMenu = 'brands'; checkBoundary()"
                    @mouseleave="activeMegaMenu = null"
                >
                    <a href="{{ route('brands') }}" class="nav-link text-sm font-semibold text-brand-dark hover:text-brand-gold-dark transition-colors flex items-center gap-1.5 focus:outline-hidden py-2 {{ request()->routeIs('brands*') ? 'text-brand-gold-dark font-bold' : '' }}">
                        {{ __('Brand') }} 
                        <svg class="w-3.5 h-3.5 text-gray-400 group-hover:text-brand-gold-dark transition-transform duration-200" :class="activeMegaMenu === 'brands' ? 'rotate-180 text-brand-gold' : ''" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M6 9l6 6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </a>
                    
                    <!-- Clean Brands Dropdown Content with Auto-Positioning (Text-only 2-column Card Layout) -->
                    <div 
                        x-ref="brandDropdown"
                        x-show="activeMegaMenu === 'brands'"
                        x-cloak
                        x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0 translate-y-2 scale-98"
                        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                        x-transition:leave="transition ease-in duration-150"
                        x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                        x-transition:leave-end="opacity-0 translate-y-2 scale-98"
                        class="absolute top-full mt-1 w-[300px] sm:w-[330px] max-w-[calc(100vw-2rem)] bg-white shadow-2xl border border-gray-100 rounded-2xl p-4 sm:p-5 z-50 overflow-hidden"
                        :class="dropdownAlign === 'right' ? 'right-0 left-auto' : 'left-0 right-auto'"
                    >
                        <div class="grid grid-cols-2 gap-2.5 sm:gap-3">
                            @foreach($brands as $brand)
                                @php
                                    $displayName = Str::title(strtolower(html_entity_decode($brand->name)));
                                @endphp
                                <a 
                                    href="{{ route('brands.show', $brand->slug) }}" 
                                    class="flex items-center justify-center p-3 sm:py-3.5 sm:px-4 rounded-xl border border-gray-100 bg-[#FAF8F5]/50 hover:bg-white hover:border-brand-gold/60 hover:shadow-2xs transition-all duration-200 group text-center"
                                >
                                    <h4 class="font-bold text-brand-dark text-sm sm:text-base tracking-tight group-hover:text-brand-gold-dark transition-colors leading-tight">
                                        {{ $displayName }}
                                    </h4>
                                </a>
                            @endforeach
                        </div>
                        <div class="pt-3 border-t border-gray-100 mt-3.5">
                            <a href="{{ route('brands') }}" class="flex items-center justify-center gap-1.5 py-1 text-xs font-bold text-brand-gold-dark hover:text-brand-dark transition-colors">
                                <span>{{ __('Lihat Semua Brand') }}</span>
                                <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M9 6l6 6-6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            </a>
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
                {{-- <li class="h-full flex items-center" @mouseenter="activeMegaMenu = null">
                    <a href="{{ route('bundling.index') }}" class="nav-link text-sm font-semibold text-brand-dark hover:text-brand-gold-dark transition-colors py-2 {{ request()->routeIs('bundling.*') ? 'text-brand-gold-dark font-bold' : '' }}">
                        {{ __('Bundling Hemat') }}
                    </a>
                </li> --}}
                
                <!-- Bantuan -->
                <li class="h-full flex items-center" @mouseenter="activeMegaMenu = null">
                    <a href="{{ route('about') }}" class="nav-link text-sm font-semibold text-brand-dark hover:text-brand-gold-dark transition-colors py-2 {{ request()->routeIs('help') ? 'text-brand-gold-dark font-bold' : '' }}">
                        {{ __('Tentang Kami') }}
                    </a>
                </li>
            </ul>
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
                            <div class="w-12 h-12 rounded-lg bg-[#FAF8F5] border border-gray-100 overflow-hidden flex-shrink-0 flex items-center justify-center p-0.5">
                                <img :src="item.thumbnail_url || '{{ asset('images/dummy/header.jpg') }}'" :alt="item.name" class="max-w-full max-h-full object-contain">
                            </div>
                            <div class="flex-1 min-w-0">
                                <h4 class="text-sm font-extrabold text-brand-dark truncate group-hover:text-brand-gold transition-colors" x-text="item.name"></h4>
                                <div class="flex items-center gap-2 mt-1 text-xs">
                                    <span class="text-gray-500 font-medium truncate max-w-[120px]" x-text="item.category"></span>
                                    <span class="text-gray-300">•</span>
                                    <span class="font-bold text-brand-gold-dark truncate" x-text="'Rp ' + Number(item.sell_price ?? item.price ?? 0).toLocaleString('id-ID')"></span>
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
