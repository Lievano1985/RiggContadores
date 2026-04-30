@props([
    'visible' => null,
    'confirmingDelete' => false,
    'action' => '',
    'titulo' => 'Confirmar eliminacion',
    'mensaje' => 'Estas seguro de que lo quieres eliminar? Esta accion no se puede deshacer.',
    'tareas' => [],
    'closeFlag' => 'confirmarEliminacion',
    'confirmLabel' => 'Eliminar',
    'cancelLabel' => 'Cancelar',
])

@php
    $isVisible = is_null($visible) ? $confirmingDelete : $visible;
@endphp

@if ($isVisible)
    <div class="fixed inset-0 flex items-center justify-center z-50 bg-black/70">
        <div class="bg-white dark:bg-gray-900 p-6 rounded-lg shadow-lg max-w-md w-full space-y-4">
            <h2 class="text-lg font-semibold text-stone-600 dark:text-white">{{ $titulo }}</h2>
            <p class="text-gray-700 dark:text-gray-300">{{ $mensaje }}</p>

            @if (!empty($tareas))
                <div class="border border-amber-300 dark:border-amber-700 p-3 rounded bg-amber-50 dark:bg-amber-900/30">
                    <p class="text-sm text-amber-800 dark:text-amber-300 font-semibold mb-2">Tareas relacionadas:</p>
                    <ul class="list-disc list-inside text-sm text-gray-700 dark:text-gray-300 max-h-40 overflow-auto">
                        @foreach ($tareas as $tarea)
                            <li>{{ $tarea->tareaCatalogo->nombre ?? 'Tarea sin nombre' }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="flex justify-end space-x-2">
                <button wire:click="$set('{{ $closeFlag }}', false)"
                    class="px-4 py-2 bg-amber-600 text-white rounded hover:bg-amber-700">
                    {{ $cancelLabel }}
                </button>
                <button wire:click="{{ $action }}"
                    class="px-4 py-2 bg-amber-600 text-white rounded hover:bg-amber-700">
                    {{ $confirmLabel }}
                </button>
            </div>
        </div>
    </div>
@endif
