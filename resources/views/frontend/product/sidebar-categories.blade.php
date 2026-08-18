<h3 class="font-bold text-brand-dark mb-4 text-lg">{{ __('Kategori') }}</h3>

@foreach($categories as $category)
    @php
        $hasChildren = $category->children->isNotEmpty();
        $isActive = $filterType === 'category' && $filterValue === $category->slug;
    @endphp
    <div class="mb-3">
        <a href="{{ route('category.show', $category->slug) }}"
            class="flex items-center justify-between py-2 px-3 rounded-lg text-sm font-medium transition-colors {{ $isActive ? 'bg-brand-gold/20 text-brand-dark' : 'text-gray-700 hover:bg-brand-light hover:text-brand-dark' }}"
        >
            <span>{{ $category->name }}</span>
        </a>
        
        @if($hasChildren)
            <div class="ml-4 mt-2 space-y-1">
                @foreach($category->children->take(10) as $child)
                    @php
                        $childActive = $filterType === 'category' && $filterValue === $child->slug;
                        $grandchildren = $child->children->take(5);
                    @endphp
                    <a href="{{ route('category.show', $child->slug) }}"
                        class="block py-1.5 px-3 rounded text-xs transition-colors {{ $childActive ? 'bg-brand-gold/10 text-brand-dark font-semibold' : 'text-gray-500 hover:text-brand-dark' }}"
                    >
                        {{ $child->name }}
                    </a>
                    
                    @if($grandchildren->isNotEmpty())
                        <div class="ml-3 mt-1 space-y-1">
                            @foreach($grandchildren as $grandchild)
                                <a href="{{ route('category.show', $grandchild->slug) }}"
                                    class="block py-1 px-3 rounded text-xs text-gray-400 hover:text-brand-dark"
                                >
                                    {{ $grandchild->name }}
                                </a>
                            @endforeach
                        </div>
                    @endif
                @endforeach
            </div>
        @endif
    </div>
@endforeach
