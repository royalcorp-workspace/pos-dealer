@php
    $isLoggedIn = session()->get('is_logged_in', false);
    $user = session()->get('user');
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

    $contactPhone = isset($about) && isset($about->social_media['whatsapp'])
        ? preg_replace('/[^0-9]/', '', $about->social_media['whatsapp'])
        : '6281112345678';
@endphp

<!-- Mobile Off-Canvas Drawer (Slide from Left) -->
<div 
    id="mobile-menu-drawer"
    x-show="isMobileMenuOpen" 
    class="fixed inset-0 z-[100] md:hidden font-sans"
    aria-modal="true"
    role="dialog"
    x-cloak
>
    <!-- Backdrop -->
    <div 
        id="mobile-menu-backdrop"
        x-show="isMobileMenuOpen"
        x-transition:enter="transition-opacity ease-linear duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition-opacity ease-linear duration-300"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        @click="isMobileMenuOpen = false"
        onclick="window.closeMobileMenu && window.closeMobileMenu()"
        class="fixed inset-0 bg-black/60 backdrop-blur-xs cursor-pointer"
    ></div>

    <!-- Drawer Panel -->
    <div class="fixed inset-y-0 left-0 max-w-full flex">
        <div 
            id="mobile-menu-panel"
            x-show="isMobileMenuOpen"
            x-transition:enter="transform transition ease-out duration-300 sm:duration-400"
            x-transition:enter-start="-translate-x-full"
            x-transition:enter-end="translate-x-0"
            x-transition:leave="transform transition ease-in duration-250 sm:duration-300"
            x-transition:leave-start="translate-x-0"
            x-transition:leave-end="-translate-x-full"
            class="w-screen max-w-xs sm:max-w-sm bg-white shadow-2xl flex flex-col h-full text-brand-dark"
            x-data="{ openSection: null }"
        >
            <!-- Drawer Header -->
            <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100 bg-[#FAF8F5]/60 shrink-0">
                <a href="{{ route('home') }}" class="flex items-center gap-2" @click="isMobileMenuOpen = false">
                    <span class="text-2xl font-black tracking-tight font-serif text-brand-gold">
                        IMG
                    </span>
                    <span class="text-[10px] font-sans tracking-[0.16em] text-gray-500 uppercase leading-tight border-l border-gray-200 pl-2">
                        Mattress Gallery
                    </span>
                </a>
                <button 
                    type="button"
                    @click="isMobileMenuOpen = false"
                    onclick="window.closeMobileMenu && window.closeMobileMenu()"
                    class="w-9 h-9 rounded-full bg-white border border-gray-200 flex items-center justify-center text-gray-500 hover:text-brand-dark hover:border-brand-gold/60 transition-colors cursor-pointer focus:outline-none"
                    aria-label="Tutup menu"
                >
                    <i class="fa-solid fa-xmark text-base"></i>
                </button>
            </div>

            <!-- Drawer Scrollable Content -->
            <div class="flex-1 overflow-y-auto overscroll-contain p-4 space-y-4">
                @if($isLoggedIn)
                    <a href="{{ route('dashboard') }}" class="flex items-center gap-3 p-3 rounded-2xl bg-brand-light/70 border border-brand-muted/80 font-bold text-brand-dark text-sm hover:border-brand-gold/60 transition-all" @click="isMobileMenuOpen = false">
                        <div class="w-10 h-10 rounded-full bg-brand-dark flex items-center justify-center text-brand-gold font-bold text-sm shrink-0 shadow-xs">
                            {{ strtoupper(substr($user['name'] ?? 'B', 0, 1)) }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <span class="block truncate font-bold text-brand-dark text-sm">{{ $user['name'] ?? __('Akun Saya') }}</span>
                            <span class="text-[11px] text-gray-500 font-normal truncate block">{{ $user['email'] ?? '' }}</span>
                        </div>
                        <i class="fa-solid fa-chevron-right text-xs text-brand-gold shrink-0"></i>
                    </a>
                @else
                    <div class="flex items-center gap-2 p-3 rounded-2xl bg-brand-light/50 border border-brand-muted/60">
                        <button 
                            type="button"
                            @click="isMobileMenuOpen = false; isAuthOpen = true" 
                            class="flex-1 py-2.5 px-3 rounded-xl bg-brand-dark text-white text-xs font-bold text-center hover:bg-brand-darker transition-colors cursor-pointer"
                        >
                            <i class="fa-solid fa-right-to-bracket mr-1.5 text-brand-gold"></i>{{ __('Masuk') }}
                        </button>
                        <a 
                            href="{{ route('dashboard') }}" 
                            class="flex-1 py-2.5 px-3 rounded-xl bg-white border border-gray-200 text-brand-dark text-xs font-bold text-center hover:border-brand-gold transition-colors"
                            @click="isMobileMenuOpen = false"
                        >
                            {{ __('Daftar') }}
                        </a>
                    </div>
                @endif

                <!-- Home Link -->
                <a href="{{ route('home') }}" class="flex items-center justify-between p-3 rounded-xl bg-brand-light/80 hover:bg-brand-light font-bold text-brand-dark text-sm transition-colors" @click="isMobileMenuOpen = false">
                    <span class="flex items-center gap-2.5">
                        <i class="fa-solid fa-house text-brand-gold text-sm"></i>
                        {{ __('Home') }}
                    </span>
                    <i class="fa-solid fa-chevron-right text-xs text-gray-300"></i>
                </a>

                <!-- Mobile Language Switcher -->
                <div class="flex items-center justify-between p-3 rounded-xl bg-gray-50 border border-gray-100">
                    <span class="text-xs font-bold text-gray-700 flex items-center gap-2">
                        <i class="fa-solid fa-globe text-brand-gold text-xs"></i>
                        {{ __('Bahasa') }}
                    </span>
                    <div class="flex items-center bg-white rounded-lg border border-gray-200 overflow-hidden shadow-xs">
                        <a href="{{ route('lang.switch', 'id') }}" class="px-2.5 py-1 text-xs font-bold transition-colors {{ app()->getLocale() === 'id' ? 'bg-brand-gold text-white' : 'text-gray-500 hover:bg-gray-100' }}">ID</a>
                        <a href="{{ route('lang.switch', 'en') }}" class="px-2.5 py-1 text-xs font-bold transition-colors {{ app()->getLocale() === 'en' ? 'bg-brand-gold text-white' : 'text-gray-500 hover:bg-gray-100' }}">EN</a>
                    </div>
                </div>

                <!-- Kasur & Kategori Accordion -->
                <div class="border border-brand-muted/70 rounded-2xl overflow-hidden">
                    <button 
                        type="button"
                        @click="openSection = (openSection === 'categories' ? null : 'categories')"
                        class="w-full flex items-center justify-between p-3.5 bg-white text-left font-bold text-brand-dark text-sm focus:outline-hidden cursor-pointer"
                    >
                        <span class="flex items-center gap-2.5">
                            <i class="fa-solid fa-layer-group text-brand-gold text-sm"></i>
                            {{ __('Produk Kategori') }}
                        </span>
                        <i class="fa-solid fa-chevron-down text-xs text-gray-400 transition-transform duration-200" :class="openSection === 'categories' ? 'rotate-180 text-brand-gold' : ''"></i>
                    </button>
                    <div x-show="openSection === 'categories'" class="bg-brand-light/50 border-t border-brand-muted/40 p-2 space-y-1">
                        @foreach($categories as $category)
                            <div class="space-y-0.5">
                                <a 
                                    href="{{ route('category.show', $category->slug) }}" 
                                    class="flex items-center justify-between p-2.5 rounded-lg text-sm text-gray-800 font-bold hover:bg-white hover:text-brand-gold transition-colors text-left"
                                    @click="isMobileMenuOpen = false"
                                >
                                    <span>{{ html_entity_decode($category->name) }}</span>
                                    <i class="fa-solid fa-chevron-right text-[10px] text-gray-300"></i>
                                </a>
                                @if($category->children && $category->children->count() > 0)
                                    <div class="pl-4 pr-1 py-1.5 flex flex-wrap gap-1.5 border-l-2 border-brand-gold/30 ml-3">
                                        @foreach($category->children as $child)
                                            <a 
                                                href="{{ route('category.show', $child->slug) }}" 
                                                class="inline-block py-1 px-2.5 rounded-lg text-xs text-stone-600 bg-white/80 border border-stone-200/60 hover:text-brand-dark hover:border-brand-gold transition-colors text-left"
                                                @click="isMobileMenuOpen = false"
                                            >
                                                {{ html_entity_decode($child->name) }}
                                            </a>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        @endforeach
                        <a href="{{ route('categories') }}" class="block p-2.5 text-xs font-bold text-brand-gold-dark hover:text-brand-dark text-left transition-colors" @click="isMobileMenuOpen = false">
                            {{ __('Lihat Semua Kategori &rarr;') }}
                        </a>
                    </div>
                </div>

                <!-- Brand Accordion -->
                <div class="border border-brand-muted/70 rounded-2xl overflow-hidden">
                    <button 
                        type="button"
                        @click="openSection = (openSection === 'brands' ? null : 'brands')"
                        class="w-full flex items-center justify-between p-3.5 bg-white text-left font-bold text-brand-dark text-sm focus:outline-hidden cursor-pointer"
                    >
                        <span class="flex items-center gap-2.5">
                            <i class="fa-solid fa-award text-brand-gold text-sm"></i>
                            {{ __('Brand') }}
                        </span>
                        <i class="fa-solid fa-chevron-down text-xs text-gray-400 transition-transform duration-200" :class="openSection === 'brands' ? 'rotate-180 text-brand-gold' : ''"></i>
                    </button>
                    <div x-show="openSection === 'brands'" class="bg-white border-t border-brand-muted/40 p-3">
                        <div class="grid grid-cols-2 gap-2">
                            @foreach($brands as $brand)
                                @php
                                    $displayName = Str::title(strtolower(html_entity_decode($brand->name)));
                                @endphp
                                <a 
                                    href="{{ route('brands.show', $brand->slug) }}" 
                                    class="flex items-center justify-center p-2.5 rounded-xl border border-gray-100 bg-[#FAF8F5]/50 hover:bg-white hover:border-brand-gold/60 hover:shadow-2xs transition-all text-center"
                                    @click="isMobileMenuOpen = false"
                                >
                                    <h4 class="font-bold text-brand-dark text-xs tracking-tight leading-tight">
                                        {{ $displayName }}
                                    </h4>
                                </a>
                            @endforeach
                        </div>
                        <div class="pt-3 border-t border-gray-100 mt-3 text-center">
                            <a href="{{ route('brands') }}" class="inline-flex items-center gap-1.5 text-xs font-bold text-brand-gold-dark hover:text-brand-dark" @click="isMobileMenuOpen = false">
                                <span>{{ __('Lihat Semua Brand') }}</span>
                                <i class="fa-solid fa-arrow-right text-[10px]"></i>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Direct Links -->
                <div class="space-y-1 pt-1 border-t border-gray-100">
                    <a href="{{ route('promos') }}" class="flex items-center justify-between p-3 rounded-xl hover:bg-brand-light text-sm font-bold text-brand-dark transition-colors" @click="isMobileMenuOpen = false">
                        <span class="flex items-center gap-2.5">
                            <i class="fa-solid fa-tag text-red-500 text-sm"></i>
                            {{ __('Promo Spesial') }}
                        </span>
                        <span class="px-2 py-0.5 rounded-full bg-red-500/10 text-red-600 text-[10px] font-bold uppercase tracking-wider">Hot</span>
                    </a>
                    <a href="{{ route('bundling.index') }}" class="flex items-center justify-between p-3 rounded-xl hover:bg-brand-light text-sm font-bold text-brand-dark transition-colors" @click="isMobileMenuOpen = false">
                        <span class="flex items-center gap-2.5">
                            <i class="fa-solid fa-boxes-stacked text-brand-gold text-sm"></i>
                            {{ __('Bundling Hemat') }}
                        </span>
                        <i class="fa-solid fa-chevron-right text-xs text-gray-300"></i>
                    </a>
                    <a href="{{ route('blog') }}" class="flex items-center justify-between p-3 rounded-xl hover:bg-brand-light text-sm font-semibold text-gray-700 transition-colors" @click="isMobileMenuOpen = false">
                        <span class="flex items-center gap-2.5">
                            <i class="fa-solid fa-newspaper text-gray-400 text-sm"></i>
                            {{ __('Blog & Artikel Tidur') }}
                        </span>
                        <i class="fa-solid fa-chevron-right text-xs text-gray-300"></i>
                    </a>
                    <a href="{{ route('help') }}" class="flex items-center justify-between p-3 rounded-xl hover:bg-brand-light text-sm font-semibold text-gray-700 transition-colors" @click="isMobileMenuOpen = false">
                        <span class="flex items-center gap-2.5">
                            <i class="fa-solid fa-circle-question text-gray-400 text-sm"></i>
                            {{ __('Pusat Bantuan & FAQ') }}
                        </span>
                        <i class="fa-solid fa-chevron-right text-xs text-gray-300"></i>
                    </a>
                </div>
            </div>

            <!-- Drawer Footer (Quick Contacts) -->
            
        </div>
    </div>
</div>
