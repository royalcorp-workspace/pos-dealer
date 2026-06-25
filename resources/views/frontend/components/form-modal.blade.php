@props([
    'name' => 'default-modal',
    'title' => '',
    'subtitle' => '',
    'show' => false,
    'maxWidth' => 'max-w-md',
    'closeButton' => true,
])

<div
    x-data="{ open: @js($show) }"
    x-show="open"
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    class="fixed inset-0 z-50 flex items-center justify-center p-4"
    style="display: none;"
    @click.self="open = false"
>
    <!-- Backdrop -->
    <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm"></div>

    <!-- Modal -->
    <div
        x-transition:enter="transition ease-out duration-300 transform"
        x-transition:enter-start="opacity-0 translate-y-4 scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
        x-transition:leave="transition ease-in duration-200 transform"
        x-transition:leave-start="opacity-100 translate-y-0 scale-100"
        x-transition:leave-end="opacity-0 translate-y-4 scale-95"
        class="relative w-full {{ $maxWidth }} bg-white rounded-3xl shadow-2xl overflow-hidden font-sans flex flex-col max-h-[90vh]"
        @click.stop
    >
        @if($closeButton || $title || $subtitle)
            <div class="flex items-start justify-between p-6 border-b border-gray-100">
                <div>
                    @if($title)
                        <h3 class="text-xl font-extrabold text-brand-dark tracking-tight">{{ $title }}</h3>
                    @endif
                    @if($subtitle)
                        <p class="text-sm text-gray-500 mt-1">{{ $subtitle }}</p>
                    @endif
                </div>

                @if($closeButton)
                    <button
                        @click="open = false"
                        type="button"
                        class="p-2 text-gray-400 hover:text-gray-600 bg-gray-50 hover:bg-gray-100 rounded-full transition-colors focus:outline-none"
                    >
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M18 6L6 18M6 6l12 12" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </button>
                @endif
            </div>
        @endif

        <div class="p-6 overflow-y-auto">
            {{ $slot }}
        </div>
    </div>
</div>
