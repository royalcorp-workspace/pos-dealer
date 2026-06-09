@props([
    'label' => 'Submit',
    'type' => 'submit',
    'variant' => 'primary',
    'class' => '',
    'loading' => false,
])

@php
    $base = 'inline-flex items-center justify-center gap-2 font-bold rounded-xl transition-transform active:scale-[0.98] focus:outline-none disabled:opacity-60 disabled:cursor-not-allowed';

    $variants = [
        'primary' => 'bg-brand-dark text-brand-gold hover:bg-brand-darker shadow-lg shadow-brand-dark/20',
        'secondary' => 'bg-white text-brand-dark border-2 border-brand-dark hover:bg-brand-light',
        'danger' => 'bg-red-600 text-white hover:bg-red-700',
        'ghost' => 'bg-transparent text-brand-gold hover:bg-brand-light',
    ];

    $classes = trim($base . ' py-3.5 px-6 ' . ($variants[$variant] ?? $variants['primary']) . ' ' . $class);
@endphp

<button
    type="{{ $type }}"
    {{ $attributes->merge(['class' => $classes]) }}
    @if($loading) disabled @endif
>
    @if($loading)
        <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
        </svg>
    @endif

    {{ $label }}
</button>
