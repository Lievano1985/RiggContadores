@props([
    'icon' => 'eye',
    'label' => 'Acción',
    'variant' => 'neutral',
    'href' => null,
])

@php
    $palette = match ($variant) {
        'primary' => 'bg-slate-600 hover:bg-slate-700 focus:ring-slate-500 text-white',
        'danger' => 'bg-rose-600 hover:bg-rose-700 focus:ring-rose-500 text-white',
        'success' => 'bg-emerald-600 hover:bg-emerald-700 focus:ring-emerald-500 text-white',
        'info' => 'bg-sky-600 hover:bg-sky-700 focus:ring-sky-500 text-white',
        'warning' => 'bg-amber-600 hover:bg-amber-700 focus:ring-amber-500 text-white',
        'neutral' => 'bg-stone-500 hover:bg-stone-600 focus:ring-stone-400 text-white',
        default => 'bg-amber-600 hover:bg-amber-700 focus:ring-amber-500 text-white',
    };

    $base = "rigg-action-button group relative inline-flex h-8 w-8 items-center justify-center rounded-md transition
             focus:outline-none focus:ring-2 focus:ring-offset-1 dark:focus:ring-offset-gray-900 {$palette}";
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->class($base . ' no-prefix-icon') }}>
        @include('components.partials.action-icon-svg', ['icon' => $icon])
        <span class="pointer-events-none absolute -top-8 left-1/2 -translate-x-1/2 whitespace-nowrap rounded bg-stone-800 px-2 py-1 text-[11px] text-white opacity-0 shadow transition group-hover:opacity-100">
            {{ $label }}
        </span>
    </a>
@else
    <button type="button" {{ $attributes->class($base . ' no-prefix-icon') }}>
        @include('components.partials.action-icon-svg', ['icon' => $icon])
        <span class="pointer-events-none absolute -top-8 left-1/2 -translate-x-1/2 whitespace-nowrap rounded bg-stone-800 px-2 py-1 text-[11px] text-white opacity-0 shadow transition group-hover:opacity-100">
            {{ $label }}
        </span>
    </button>
@endif
