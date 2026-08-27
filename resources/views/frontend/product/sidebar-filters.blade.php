<form action="{{ route('products.index', [], false) }}" method="GET" class="space-y-4 font-sans">

    <!-- Kategori Section -->
    <div x-data="{ open: true }" class="border-b border-[#EFEBE4] pb-4">
        <button type="button" @click="open = !open" class="w-full flex items-center justify-between text-left py-1 text-xs font-bold text-brand-dark uppercase tracking-wider group cursor-pointer focus:outline-none">
            <span class="flex items-center gap-1.5">
                <span class="w-1.5 h-1.5 rounded-full bg-brand-gold"></span>
                <span>{{ __('Kategori') }}</span>
            </span>
            <i class="fa-solid fa-chevron-down text-[10px] text-stone-400 group-hover:text-brand-gold transition-transform duration-200" :class="open ? 'rotate-180' : ''"></i>
        </button>
        <div x-show="open" class="mt-2.5 space-y-1.5 max-h-48 overflow-y-auto pr-1">
            @foreach($categories->take(15) as $category)
                <label class="flex items-center gap-2.5 py-1 text-xs sm:text-sm cursor-pointer select-none group">
                    <input type="checkbox" name="categories[]" value="{{ $category->slug }}"
                        {{ in_array($category->slug, $filters['categories'] ?? []) ? 'checked' : '' }}
                        class="rounded border-gray-300 text-brand-gold focus:ring-brand-gold cursor-pointer">
                    <span class="text-stone-700 group-hover:text-brand-dark transition-colors">{{ $category->name }}</span>
                </label>
            @endforeach
        </div>
    </div>

    <!-- Brand Section -->
    <div x-data="{ open: true }" class="border-b border-[#EFEBE4] pb-4">
        <button type="button" @click="open = !open" class="w-full flex items-center justify-between text-left py-1 text-xs font-bold text-brand-dark uppercase tracking-wider group cursor-pointer focus:outline-none">
            <span class="flex items-center gap-1.5">
                <span class="w-1.5 h-1.5 rounded-full bg-brand-gold"></span>
                <span>{{ __('Brand') }}</span>
            </span>
            <i class="fa-solid fa-chevron-down text-[10px] text-stone-400 group-hover:text-brand-gold transition-transform duration-200" :class="open ? 'rotate-180' : ''"></i>
        </button>
        <div x-show="open" class="mt-2.5 space-y-1.5 max-h-48 overflow-y-auto pr-1">
            @foreach($brands->take(15) as $brand)
                <label class="flex items-center gap-2.5 py-1 text-xs sm:text-sm cursor-pointer select-none group">
                    <input type="checkbox" name="brands[]" value="{{ $brand->slug }}"
                        {{ in_array($brand->slug, $filters['brands'] ?? []) ? 'checked' : '' }}
                        class="rounded border-gray-300 text-brand-gold focus:ring-brand-gold cursor-pointer">
                    <span class="text-stone-700 group-hover:text-brand-dark transition-colors">{{ $brand->name }}</span>
                </label>
            @endforeach
        </div>
    </div>

    <!-- Harga Section (Stacked Atas-Bawah) -->
    <div x-data="{ open: true }" class="border-b border-[#EFEBE4] pb-4">
        <button type="button" @click="open = !open" class="w-full flex items-center justify-between text-left py-1 text-xs font-bold text-brand-dark uppercase tracking-wider group cursor-pointer focus:outline-none">
            <span class="flex items-center gap-1.5">
                <span class="w-1.5 h-1.5 rounded-full bg-brand-gold"></span>
                <span>{{ __('Harga') }}</span>
            </span>
            <i class="fa-solid fa-chevron-down text-[10px] text-stone-400 group-hover:text-brand-gold transition-transform duration-200" :class="open ? 'rotate-180' : ''"></i>
        </button>
        <div x-show="open" class="mt-2.5 space-y-2.5">
            <div>
                <label class="block text-[11px] font-semibold text-stone-500 mb-1">{{ __('Harga Minimum') }}</label>
                <div class="relative">
                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-xs font-bold text-stone-400">Rp</span>
                    <input type="number" name="min_price" placeholder="0" value="{{ $filters['min_price'] ?? '' }}"
                        class="w-full pl-9 pr-3 py-2 border border-gray-200 rounded-xl text-xs sm:text-sm focus:ring-2 focus:ring-brand-gold/30 focus:border-brand-gold bg-white outline-none [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none">
                </div>
            </div>
            <div>
                <label class="block text-[11px] font-semibold text-stone-500 mb-1">{{ __('Harga Maksimum') }}</label>
                <div class="relative">
                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-xs font-bold text-stone-400">Rp</span>
                    <input type="number" name="max_price" placeholder="Tak Terbatas" value="{{ $filters['max_price'] ?? '' }}"
                        class="w-full pl-9 pr-3 py-2 border border-gray-200 rounded-xl text-xs sm:text-sm focus:ring-2 focus:ring-brand-gold/30 focus:border-brand-gold bg-white outline-none [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none">
                </div>
            </div>
        </div>
    </div>

    <!-- Stok Section -->
    <div x-data="{ open: true }" class="border-b border-[#EFEBE4] pb-4">
        <button type="button" @click="open = !open" class="w-full flex items-center justify-between text-left py-1 text-xs font-bold text-brand-dark uppercase tracking-wider group cursor-pointer focus:outline-none">
            <span class="flex items-center gap-1.5">
                <span class="w-1.5 h-1.5 rounded-full bg-brand-gold"></span>
                <span>{{ __('Ketersediaan') }}</span>
            </span>
            <i class="fa-solid fa-chevron-down text-[10px] text-stone-400 group-hover:text-brand-gold transition-transform duration-200" :class="open ? 'rotate-180' : ''"></i>
        </button>
        <div x-show="open" class="mt-2.5">
            <label class="flex items-center gap-2.5 py-1 text-xs sm:text-sm cursor-pointer select-none group">
                <input type="checkbox" name="in_stock" value="1"
                    {{ ($filters['in_stock'] ?? '') === '1' ? 'checked' : '' }}
                    class="rounded border-gray-300 text-brand-gold focus:ring-brand-gold cursor-pointer">
                <span class="text-stone-700 group-hover:text-brand-dark transition-colors">{{ __('Hanya Produk Tersedia') }}</span>
            </label>
        </div>
    </div>

    <!-- Atribut Section -->
    @if(isset($tags) && $tags->isNotEmpty())
        <div x-data="{ open: true }" class="border-b border-[#EFEBE4] pb-4">
            <button type="button" @click="open = !open" class="w-full flex items-center justify-between text-left py-1 text-xs font-bold text-brand-dark uppercase tracking-wider group cursor-pointer focus:outline-none">
                <span class="flex items-center gap-1.5">
                    <span class="w-1.5 h-1.5 rounded-full bg-brand-gold"></span>
                    <span>{{ __('Atribut & Fitur') }}</span>
                </span>
                <i class="fa-solid fa-chevron-down text-[10px] text-stone-400 group-hover:text-brand-gold transition-transform duration-200" :class="open ? 'rotate-180' : ''"></i>
            </button>
            <div x-show="open" class="mt-2.5 space-y-1.5 max-h-48 overflow-y-auto pr-1">
                @foreach($tags->take(15) as $tag)
                    <label class="flex items-center gap-2.5 py-1 text-xs sm:text-sm cursor-pointer select-none group">
                        <input type="checkbox" name="tags[]" value="{{ $tag->slug }}"
                            {{ in_array($tag->slug, $filters['tags'] ?? []) ? 'checked' : '' }}
                            class="rounded border-gray-300 text-brand-gold focus:ring-brand-gold cursor-pointer">
                        <span class="text-stone-700 group-hover:text-brand-dark transition-colors">{{ $tag->name }}</span>
                    </label>
                @endforeach
            </div>
        </div>
    @endif

    <div class="pt-1">
        <button type="submit" class="w-full py-2.5 bg-brand-gold hover:bg-brand-gold-dark text-brand-dark rounded-xl font-bold text-xs sm:text-sm transition-all shadow-xs cursor-pointer active:scale-98">
            {{ __('Terapkan Filter') }}
        </button>
    </div>
</form>
