@if ($paginator->hasPages())
    <nav class="flex items-center justify-center gap-2 font-sans" role="navigation">
        {{-- Previous Page Link --}}
        @if ($paginator->onFirstPage())
            <span class="px-4 py-2 text-gray-400 rounded-lg cursor-not-allowed">
                <i class="fa-solid fa-chevron-left w-4 h-4"></i>
            </span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="px-4 py-2 text-brand-dark border border-brand-muted rounded-lg hover:bg-brand-light hover:border-brand-gold transition-colors">
                <i class="fa-solid fa-chevron-left w-4 h-4"></i>
            </a>
        @endif

        {{-- Pagination Elements --}}
        @foreach ($paginator->getUrlRange(1, $paginator->lastPage()) as $page => $url)
            @if ($page == $paginator->currentPage())
                <span class="px-4 py-2 bg-brand-gold text-brand-dark font-bold rounded-lg">{{ $page }}</span>
            @else
                <a href="{{ $url }}" class="px-4 py-2 text-brand-dark border border-brand-muted rounded-lg hover:bg-brand-light hover:border-brand-gold transition-colors">{{ $page }}</a>
            @endif
        @endforeach

        {{-- Next Page Link --}}
        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="px-4 py-2 text-brand-dark border border-brand-muted rounded-lg hover:bg-brand-light hover:border-brand-gold transition-colors">
                <i class="fa-solid fa-chevron-right w-4 h-4"></i>
            </a>
        @else
            <span class="px-4 py-2 text-gray-400 rounded-lg cursor-not-allowed">
                <i class="fa-solid fa-chevron-right w-4 h-4"></i>
            </span>
        @endif
    </nav>
@endif