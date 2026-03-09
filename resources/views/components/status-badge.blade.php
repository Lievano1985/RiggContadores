@props([
    'status' => null,
    'label' => null,
])

@php
    $status = (string) ($status ?? '');

    $classes = match ($status) {
        'asignada' => 'bg-slate-500',
        'en_progreso' => 'bg-amber-500',
        'realizada' => 'bg-blue-600',
        'revisada' => 'bg-violet-600',
        'enviada_cliente' => 'bg-cyan-600',
        'respuesta_cliente' => 'bg-orange-500',
        'respuesta_revisada' => 'bg-fuchsia-600',
        'finalizado', 'cerrada' => 'bg-emerald-600',
        'rechazada', 'cancelada' => 'bg-red-600',
        'reabierta' => 'bg-rose-600',
        default => 'bg-gray-500',
    };

    $text = $label ?: str_replace('_', ' ', $status);
@endphp

<span {{ $attributes->class("inline-flex items-center justify-center text-center px-2 py-1 rounded text-xs font-semibold text-white {$classes}") }}>
    {{ ucfirst($text) }}
</span>
