@props([
    'name',
    'label' => null,
    'value' => '',
    'placeholder' => '',
    'required' => false,
    'error' => null,
    'type' => 'text',
    'icon' => null,
    'hint' => null,
    'class' => '',
    'inputClass' => '',
])

@php
    $hasError = (bool) $error;
    $inputId = $attributes->get('id', $name);
@endphp

<div class="{{ $class }}">
    @if($label)
        <label for="{{ $inputId }}" class="block text-xs font-bold text-brand-darker uppercase tracking-wider mb-2">
            {{ $label }}
            @if($required)
                <span class="text-red-500 ml-0.5">*</span>
            @endif
        </label>
    @endif

    <div class="relative">
        @if($icon)
            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                <div class="h-5 w-5 text-gray-400">{!! $icon !!}</div>
            </div>
        @endif

        <input
            type="{{ $type }}"
            name="{{ $name }}"
            id="{{ $inputId }}"
            value="{{ old($name, $value) }}"
            placeholder="{{ $placeholder }}"
            @if($required) required @endif
            {{ $attributes->merge(['class' => '
                w-full
                pl-' . ($icon ? '11' : '4') . '
                pr-4
                py-3
                bg-brand-light
                border
                ' . ($hasError ? 'border-red-400 focus:ring-red-200 focus:border-red-500' : 'border-brand-muted focus:ring-brand-gold/50 focus:border-brand-gold') . '
                rounded-xl
                text-gray-800
                placeholder-gray-400
                focus:outline-none
                focus:ring-2
                transition-colors
                ' . $inputClass
            ]) }}
        />
    </div>

    @if($hasError)
        <p class="text-red-500 text-xs mt-1.5">{{ $error }}</p>
    @endif

    @if($hint && !$hasError)
        <p class="text-gray-500 text-xs mt-1.5">{{ $hint }}</p>
    @endif
</div>
