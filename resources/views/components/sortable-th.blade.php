@props([
    'field',
    'label',
    'sortField' => '',
    'sortDirection' => 'asc',
    'align' => 'left',
])

@php
    $alignClass = $align === 'center'
        ? 'text-center'
        : ($align === 'right' ? 'text-right' : 'text-left');
@endphp

<th {{ $attributes->merge(['class' => "px-4 py-2 {$alignClass} text-xs font-semibold"]) }}>
    <button type="button" wire:click="sortBy('{{ $field }}')"
        class="group inline-flex items-center gap-1 hover:text-amber-600">
        {{ $label }}
        @if ($sortField === $field)
            <span class="text-amber-600">{!! $sortDirection === 'asc' ? '&uarr;' : '&darr;' !!}</span>
        @else
            <span class="text-gray-400 group-hover:text-amber-600">&harr;</span>
        @endif
    </button>
</th>
