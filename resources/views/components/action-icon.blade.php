@props([
    'icon' => 'eye',
    'label' => 'Acción',
    'variant' => 'neutral',
    'href' => null,
])

@php
    $palette = 'bg-amber-600 hover:bg-amber-700 focus:ring-amber-500 text-white';

    $base = "rigg-action-button group relative inline-flex h-8 w-8 items-center justify-center rounded-md transition
             focus:outline-none focus:ring-2 focus:ring-offset-1 dark:focus:ring-offset-gray-900 {$palette}";
@endphp

@if ($href)
    <a href="{{ $href }}" title="{{ $label }}" {{ $attributes->class($base . ' no-prefix-icon') }}>
        @include('components.partials.action-icon-svg', ['icon' => $icon])
        <span class="pointer-events-none absolute -top-8 left-1/2 -translate-x-1/2 whitespace-nowrap rounded bg-stone-800 px-2 py-1 text-[11px] text-white opacity-0 shadow transition group-hover:opacity-100">
            {{ $label }}
        </span>
    </a>
@else
    <button type="button" title="{{ $label }}" {{ $attributes->class($base . ' no-prefix-icon') }}>
        @include('components.partials.action-icon-svg', ['icon' => $icon])
        <span class="pointer-events-none absolute -top-8 left-1/2 -translate-x-1/2 whitespace-nowrap rounded bg-stone-800 px-2 py-1 text-[11px] text-white opacity-0 shadow transition group-hover:opacity-100">
            {{ $label }}
        </span>
    </button>
@endif
