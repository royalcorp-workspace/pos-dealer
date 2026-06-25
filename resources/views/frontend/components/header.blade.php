@php
    $cart = session()->get('cart', []);
    $cartItemCount = collect($cart)->sum('quantity');
    $cartTotal = collect($cart)->sum(function($item) {
        return ($item['price'] ?? 0) * ($item['quantity'] ?? 0);
    });
    $isLoggedIn = session()->get('is_logged_in', false);
    $user = session()->get('user');
    $wishlist = session()->get('wishlist', []);
    $wishlistCount = count($wishlist);

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

<header class="w-full bg-white border-b border-gray-100 sticky top-0 z-40 shadow-sm font-sans" x-data="{ activeMegaMenu: null }">
    <!-- Top Bar -->
    <div class="container mx-auto px-4 md:px-6 h-auto py-3 md:h-20 md:py-0 flex flex-wrap md:flex-nowrap items-center justify-between gap-4">
        <!-- Logo -->
        <div class="flex items-center gap-4">
            <button 
                class="md:hidden text-gray-700 hover:text-brand-gold transition-colors focus:outline-none"
                @click="isMobileMenuOpen = !isMobileMenuOpen"
                aria-label="Buka menu"
            >
                <!-- menu icon -->
                <svg x-show="!isMobileMenuOpen" class="w-6 h-6" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M4 6h16M4 12h16M4 18h16" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                <!-- close icon -->
                <svg x-show="isMobileMenuOpen" x-cloak class="w-6 h-6" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M18 6L6 18M6 6l12 12" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </button>
            <a href="{{ route('home') }}" class="text-3xl lg:text-4xl font-extrabold tracking-tight text-brand-dark flex items-center gap-2 font-serif text-left">
                IMG
                <span class="text-xs lg:text-sm font-sans tracking-widest text-brand-gold-dark uppercase leading-tight ml-2 border-l-2 border-brand-gold pl-2">
                    International<br/>Mattress Gallery
                </span>
            </a>
        </div>

        <!-- Search Bar -->
        <div class="hidden md:flex flex-1 max-w-2xl mx-6 relative">
            <form action="{{ route('products.index') }}" method="GET" class="relative w-full">
                <input type="hidden" name="type" value="search">
                <input 
                    type="text" 
                    name="value"
                    placeholder="Cari kasur, bantal, atau brand impianmu..." 
                    class="w-full bg-brand-light border border-brand-muted text-gray-800 text-sm rounded-full pl-5 pr-12 py-2.5 focus:outline-none focus:ring-2 focus:ring-brand-gold/50 focus:border-brand-gold transition-all placeholder:text-gray-400"
                />
                <button type="submit" class="absolute right-1 top-1 p-1.5 bg-brand-dark hover:bg-brand-darker text-white rounded-full transition-colors" aria-label="Cari">
                    <i class="fa-solid fa-magnifying-glass w-4 h-4"></i>
                </button>
            </form>
        </div>

        <!-- Right Actions -->
        <div class="flex items-center gap-2 sm:gap-5">
            <!-- Wishlist Button -->
            <a 
                id="wishlist-link"
                href="{{ route('dashboard', ['tab' => 'wishlist']) }}" 
                class="hidden sm:flex items-center justify-center w-10 h-10 rounded-full bg-brand-light hover:bg-brand-gold/20 transition-colors focus:outline-none relative" 
                aria-label="Wishlist ({{ $wishlistCount }} Produk)"
            >
                <div class="relative">
                    <i id="wishlist-icon" class="fa-{{ $wishlistCount > 0 ? 'solid' : 'regular' }} fa-heart w-5 h-5 {{ $wishlistCount > 0 ? 'text-brand-gold' : 'text-brand-dark' }}"></i>
                    @if($wishlistCount > 0)
                        <span id="wishlist-count-badge" class="absolute -top-1 -right-1 bg-brand-gold text-white text-[10px] font-bold min-w-[1rem] h-4 px-1 rounded-full flex items-center justify-center shadow-sm">
                            {{ $wishlistCount }}
                        </span>
                    @endif
                </div>
            </a>

            <!-- Language Picker -->
            <button class="hidden sm:flex items-center gap-1.5 text-sm font-medium text-gray-600 hover:text-brand-gold transition-colors py-1">
                <i class="fa-solid fa-globe w-4 h-4"></i>
                <span>ID</span>
                <i class="fa-solid fa-chevron-down w-3 h-3 text-gray-400"></i>
            </button>

            <div class="h-5 w-px bg-gray-200 hidden sm:block"></div>

            <!-- User Auth / Dashboard Module -->
            @if($isLoggedIn)
                <a 
                    href="{{ route('dashboard') }}"
                    class="flex items-center gap-2 text-sm font-medium text-gray-700 hover:text-brand-gold transition-colors py-1"
                >
                    <div class="w-8 h-8 rounded-full bg-brand-dark flex items-center justify-center text-brand-gold font-bold">
                        {{ substr($user['name'] ?? 'B', 0, 1) }}
                    </div>
                    <span class="hidden lg:block font-bold">Akun Saya</span>
                </a>
            @else
                <button 
                    @click="isAuthOpen = true"
                    class="flex items-center gap-2 text-sm font-medium text-gray-700 hover:text-brand-gold transition-colors py-1 focus:outline-none"
                >
                    <div class="w-8 h-8 rounded-full bg-brand-light flex items-center justify-center text-brand-gold-dark">
                        <i class="fa-solid fa-user w-4 h-4"></i>
                    </div>
                    <span class="hidden lg:block">Masuk / Daftar</span>
                </button>
            @endif

            <!-- Cart Drawer Trigger -->
            <button 
                @click="isCartOpen = true"
                class="flex items-center gap-3 bg-brand-light hover:bg-brand-muted px-3 sm:px-4 py-2 rounded-full border border-brand-muted transition-colors group relative focus:outline-none"
            >
                    <div class="relative">
                    <i class="fa-solid fa-cart-shopping w-5 h-5 text-brand-dark group-hover:text-brand-gold transition-colors"></i>
                    @if($cartItemCount > 0)
                        <span id="cart-count-badge" class="absolute -top-2 -right-2 bg-brand-gold text-white text-[10px] font-bold w-4 h-4 rounded-full flex items-center justify-center shadow-sm">
                            {{ $cartItemCount }}
                        </span>
                    @endif
                </div>
                <div class="hidden sm:flex flex-col items-start leading-none">
                    <span class="text-[10px] text-gray-500 font-medium uppercase tracking-wider">Keranjang</span>
                    <span id="header-cart-total" class="text-sm font-bold text-brand-dark group-hover:text-brand-gold-dark transition-colors">
                        Rp {{ number_format($cartTotal, 0, ',', '.') }}
                    </span>
                </div>
            </button>
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

    <!-- Mega Menu / Categories Navigation (Desktop) -->
    <nav class="hidden md:block w-full border-t border-gray-100 bg-white" aria-label="Navigasi utama">
        <div class="container mx-auto px-6">
            <ul class="flex items-center gap-8 h-14 relative">
                <!-- Home -->
                <li class="h-full flex items-center" @mouseenter="activeMegaMenu = null">
                    <a href="{{ route('home') }}" class="nav-link text-sm font-semibold text-gray-800 hover:text-brand-gold transition-colors">
                        Home
                    </a>
                </li>

                <!-- Brands Mega Menu Trigger -->
                <li 
                    class="h-full flex items-center cursor-pointer"
                    @mouseenter="activeMegaMenu = 'brands'"
                    @mouseleave="activeMegaMenu = null"
                >
                <a href="{{ route('brands') }}" class="nav-link text-sm font-semibold text-gray-800 hover:text-brand-gold transition-colors flex items-center gap-1.5 focus:outline-none">Belanja Berdasarkan Brand <i class="fa-solid fa-chevron-down w-4 h-4"></i></a>
                    
                    <!-- Brands Mega Menu Content -->
                    <div 
                        x-show="activeMegaMenu === 'brands'"
                        x-cloak
                        x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0 translate-y-2"
                        x-transition:enter-end="opacity-100 translate-y-0"
                        x-transition:leave="transition ease-in duration-150"
                        x-transition:leave-start="opacity-100 translate-y-0"
                        x-transition:leave-end="opacity-0 translate-y-2"
                        class="absolute top-full left-0 w-full bg-white shadow-xl border border-gray-100 rounded-b-xl py-6 px-8 z-50 overflow-hidden"
                    >
                        <div class="grid grid-cols-6 gap-6">
                            @foreach($brands as $brand)
                                <a 
                                    href="{{ route('brands.show', $brand->slug) }}" 
                                    class="group flex justify-center items-center p-4 border border-brand-muted rounded-lg hover:border-brand-gold hover:bg-brand-light transition-all focus:outline-none w-full text-center"
                                >
                                    <span class="font-medium text-gray-700 text-sm group-hover:text-brand-dark">{{ $brand->name }}</span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                </li>

                <!-- Categories Mega Menu Trigger -->
                <li 
                    class="h-full flex items-center cursor-pointer"
                    @mouseenter="activeMegaMenu = 'categories'"
                    @mouseleave="activeMegaMenu = null"
                >
                <a href="{{ route('categories') }}" class="nav-link text-sm font-semibold text-gray-800 hover:text-brand-gold transition-colors flex items-center gap-1.5 focus:outline-none">Kategori Produk <i class="fa-solid fa-chevron-down w-4 h-4"></i></a>

                    <!-- Categories Mega Menu Content -->
                    <div 
                        x-show="activeMegaMenu === 'categories'"
                        x-cloak
                        x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0 translate-y-2"
                        x-transition:enter-end="opacity-100 translate-y-0"
                        x-transition:leave="transition ease-in duration-150"
                        x-transition:leave-start="opacity-100 translate-y-0"
                        x-transition:leave-end="opacity-0 translate-y-2"
                        class="absolute top-full left-0 w-full bg-white shadow-xl border border-gray-100 rounded-b-xl p-8 z-50 flex gap-12"
                    >
                        <div class="flex-1 grid grid-cols-2 lg:grid-cols-3 gap-y-6 gap-x-8">
                            @foreach($categories as $category)
                                <div class="space-y-3">
                                    <a 
                                        href="{{ route('category.show', $category->slug) }}" 
                                        class="font-bold text-gray-900 hover:text-brand-gold-dark flex items-center justify-between border-b border-gray-100 pb-2 w-full text-left"
                                    >
                                        {{ $category->name }}
                                    </a>
                                    @if($category->children->isNotEmpty())
                                        <ul class="space-y-2 mt-2">
                                            @foreach($category->children->take(4) as $child)
                                                <li>
                                                    <a 
                                                        href="{{ route('category.show', $child->slug) }}" 
                                                        class="text-sm text-gray-500 hover:text-brand-gold transition-colors text-left"
                                                    >
                                                        {{ $child->name }}
                                                    </a>
                                                </li>
                                            @endforeach
                                        </ul>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                        <a 
                            href="{{ route('categories') }}" 
                            class="w-72 bg-brand-dark rounded-xl p-6 flex flex-col justify-end relative overflow-hidden group cursor-pointer"
                        >
                            <div class="absolute top-0 right-0 p-4 w-32 opacity-10">
                                <i class="fa-solid fa-bag-shopping w-full h-full text-brand-gold"></i>
                            </div>
                            <div class="relative z-10">
                                <span class="inline-block bg-brand-gold text-white text-[10px] font-bold px-2 py-1 relative rounded uppercase tracking-wider mb-2">New Arrival</span>
                                <h4 class="font-bold text-lg text-white mb-1 leading-tight group-hover:text-brand-light transition-colors">Koleksi Springbed 2026</h4>
                                <p class="text-sm text-gray-300 mb-4">Temukan kenyamanan tidur tak tertandingi.</p>
                                <span class="text-sm font-semibold text-brand-gold flex items-center gap-1 group-hover:gap-2 transition-all">Lihat Koleksi &rarr;</span>
                            </div>
                        </a>
                    </div>
                </li>

                <li class="h-full flex items-center" @mouseenter="activeMegaMenu = null">
                    <a href="{{ route('promos') }}" class="nav-link text-sm font-semibold text-gray-800 hover:text-brand-gold transition-colors">
                        Promo Spesial
                    </a>
                </li>
                
                <li class="h-full flex items-center" @mouseenter="activeMegaMenu = null">
                    <a href="{{ route('help') }}" class="nav-link text-sm font-semibold text-gray-800 hover:text-brand-gold transition-colors">
                        Bantuan
                    </a>
                </li>
            </ul>
        </div>
    </nav>

    <!-- Mobile Menu Overlay -->
    <div 
        x-show="isMobileMenuOpen"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 height-0"
        x-transition:enter-end="opacity-100 height-auto"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 height-auto"
        x-transition:leave-end="opacity-0 height-0"
        x-cloak
        class="md:hidden border-t border-gray-100 bg-white overflow-hidden"
    >
        <div class="p-4 space-y-6">
            <a href="{{ route('home') }}" class="text-sm text-gray-600 font-semibold text-left flex justify-between items-center" @click="isMobileMenuOpen = false">
                Home
                <span class="text-gray-400 -rotate-90 inline-block transition-transform">&rarr;</span>
            </a>
            <div>
                <h4 class="font-bold text-gray-900 mb-3 text-sm tracking-wider uppercase">Brands</h4>
                <div class="grid grid-cols-2 gap-2">
                    @foreach($brands as $brand)
<a 
                             href="{{ route('brands.show', $brand->slug) }}" 
                             class="text-sm text-gray-600 py-1.5 text-left"
                             @click="isMobileMenuOpen = false"
                         >
                            {{ $brand->name }}
                        </a>
                    @endforeach
                </div>
            </div>
            <div class="w-full h-px bg-gray-100"></div>
            <div>
                <h4 class="font-bold text-gray-900 mb-3 text-sm tracking-wider uppercase">Kategori</h4>
                <div class="grid grid-cols-1 gap-2">
                    @foreach($categories as $category)
                        <a 
                            href="{{ route('category.show', $category->slug) }}" 
                            class="text-sm text-gray-600 py-2 border-b border-gray-50 flex justify-between items-center group text-left"
                            @click="isMobileMenuOpen = false"
                        >
                            {{ $category->name }}
                            <span class="text-gray-400 group-hover:text-gray-600 -rotate-90 inline-block transition-transform">&rarr;</span>
                        </a>
                    @endforeach
                </div>
            </div>
            <div class="w-full h-px bg-gray-100"></div>
            <div class="flex flex-col gap-3 py-2">
                <a href="{{ route('promos') }}" class="text-sm text-gray-600 font-semibold text-left" @click="isMobileMenuOpen = false">Promo Spesial</a>
                <a href="{{ route('blog') }}" class="text-sm text-gray-600 font-semibold text-left" @click="isMobileMenuOpen = false">Blog</a>
                <a href="{{ route('help') }}" class="text-sm text-gray-600 font-semibold text-left" @click="isMobileMenuOpen = false">Bantuan</a>
            </div>
        </div>
    </div>
</header>
