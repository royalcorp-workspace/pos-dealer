@props([
    'name',
    'label' => null,
    'value' => '',
    'placeholder' => '',
    'required' => false,
    'error' => null,
    'rows' => 4,
    'hint' => null,
    'class' => '',
    'textareaClass' => '',
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

    <textarea
        name="{{ $name }}"
        id="{{ $inputId }}"
        rows="{{ $rows }}"
        placeholder="{{ $placeholder }}"
        @if($required) required @endif
        {{ $attributes->merge(['class' => '
            w-full
            px-4
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
            ' . $textareaClass
        ]) }}
    >{{ old($name, $value) }}</textarea>

    @if($hasError)
        <p class="text-red-500 text-xs mt-1.5">{{ $error }}</p>
    @endif

    @if($hint && !$hasError)
        <p class="text-gray-500 text-xs mt-1.5">{{ $hint }}</p>
    @endif
</div>
