@props([
    'name',
    'label' => null,
    'checked' => false,
    'value' => '1',
    'error' => null,
    'hint' => null,
    'class' => '',
])

@php
    $hasError = (bool) $error;
    $inputId = $attributes->get('id', $name);
@endphp

<div class="{{ $class }}">
    <label for="{{ $inputId }}" class="flex items-center gap-3 cursor-pointer select-none">
        <div class="relative">
            <input
                type="checkbox"
                name="{{ $name }}"
                id="{{ $inputId }}"
                value="{{ $value }}"
                @if($checked || old($name)) checked @endif
                {{ $attributes->except(['class']) }}
                class="sr-only peer"
            />
            <div class="w-5 h-5 rounded border border-brand-muted bg-white peer-checked:bg-brand-gold peer-checked:border-brand-gold transition-colors flex items-center justify-center">
                <svg x-show="true" class="w-3.5 h-3.5 text-white" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M6 12.5l3 3 9-9" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </div>
        </div>
        @if($label)
            <span class="text-sm font-medium text-gray-700 peer-checked:text-brand-dark transition-colors">{{ $label }}</span>
        @endif
    </label>

    @if($hasError)
        <p class="text-red-500 text-xs mt-1.5 ml-8">{{ $error }}</p>
    @endif

    @if($hint && !$hasError)
        <p class="text-gray-500 text-xs mt-1.5 ml-8">{{ $hint }}</p>
    @endif
</div>
