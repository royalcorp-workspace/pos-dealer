<form action="{{ route('products.index') }}" method="GET" class="space-y-6">

    <div>
        <h3 class="font-bold text-brand-dark mb-3 text-sm uppercase tracking-wider">{{ __('Kategori') }}</h3>
        @foreach($categories->take(10) as $category)
            <label class="flex items-center gap-2 py-1 text-sm cursor-pointer select-none">
                <input type="checkbox" name="categories[]" value="{{ $category->slug }}"
                    {{ in_array($category->slug, $filters['categories'] ?? []) ? 'checked' : '' }}
                    class="rounded border-gray-300 text-brand-gold focus:ring-brand-gold cursor-pointer">
                <span class="text-gray-700">{{ $category->name }}</span>
            </label>
        @endforeach
    </div>

    <div>
        <h3 class="font-bold text-brand-dark mb-3 text-sm uppercase tracking-wider">{{ __('Brand') }}</h3>
        @foreach($brands->take(10) as $brand)
            <label class="flex items-center gap-2 py-1 text-sm cursor-pointer select-none">
                <input type="checkbox" name="brands[]" value="{{ $brand->slug }}"
                    {{ in_array($brand->slug, $filters['brands'] ?? []) ? 'checked' : '' }}
                    class="rounded border-gray-300 text-brand-gold focus:ring-brand-gold cursor-pointer">
                <span class="text-gray-700">{{ $brand->name }}</span>
            </label>
        @endforeach
    </div>

    <div>
        <h3 class="font-bold text-brand-dark mb-3 text-sm uppercase tracking-wider">{{ __('Harga') }}</h3>
        <div class="grid grid-cols-2 gap-3">
            <input type="number" name="min_price" placeholder="Min" value="{{ $filters['min_price'] ?? '' }}"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-brand-gold focus:border-brand-gold">
            <input type="number" name="max_price" placeholder="Max" value="{{ $filters['max_price'] ?? '' }}"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-brand-gold focus:border-brand-gold">
        </div>
    </div>

    <div>
        <h3 class="font-bold text-brand-dark mb-3 text-sm uppercase tracking-wider">{{ __('Stok') }}</h3>
        <label class="flex items-center gap-2 py-1 text-sm cursor-pointer select-none">
            <input type="checkbox" name="in_stock" value="1"
                {{ ($filters['in_stock'] ?? '') === '1' ? 'checked' : '' }}
                class="rounded border-gray-300 text-brand-gold focus:ring-brand-gold cursor-pointer">
            <span class="text-gray-700">{{ __('Tersedia') }}</span>
        </label>
    </div>

    <div>
        <h3 class="font-bold text-brand-dark mb-3 text-sm uppercase tracking-wider">{{ __('Atribut') }}</h3>
        @foreach($tags->take(10) as $tag)
            <label class="flex items-center gap-2 py-1 text-sm cursor-pointer select-none">
                <input type="checkbox" name="tags[]" value="{{ $tag->slug }}"
                    {{ in_array($tag->slug, $filters['tags'] ?? []) ? 'checked' : '' }}
                    class="rounded border-gray-300 text-brand-gold focus:ring-brand-gold cursor-pointer">
                <span class="text-gray-700">{{ $tag->name }}</span>
            </label>
        @endforeach
    </div>

    <button type="submit" class="w-full py-2 bg-brand-gold text-brand-dark rounded-lg font-bold text-sm hover:bg-brand-gold/80 transition">
        {{ __('Terapkan Filter') }}
    </button>
</form>
