@props([
    'title' => '',
    'subtitle' => '',
    'class' => '',
    'padding' => true,
])

<div @class([
    'bg-white rounded-2xl border border-gray-100 shadow-sm flex flex-col',
    'p-6' => $padding,
    $class,
])>
    @if ($title || $subtitle)
        <div class="mb-5">
            @if ($title)
                <h3 class="text-lg font-extrabold text-brand-dark tracking-tight">{{ $title }}</h3>
            @endif
            @if ($subtitle)
                <p class="text-sm text-gray-500 mt-1">{{ $subtitle }}</p>
            @endif
        </div>
    @endif

    {{ $slot }}
</div>
