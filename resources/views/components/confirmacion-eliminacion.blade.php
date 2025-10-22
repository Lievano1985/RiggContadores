{{-- @props([
    'confirmingDelete' => false, 
    'action' => ''
])

@if ($confirmingDelete)
<div class="fixed inset-0 flex items-center justify-center z-50 bg-black/70">
    <div class="bg-white dark:bg-gray-900 p-6 rounded-lg shadow-lg max-w-md w-full">
        <h2 class="text-lg font-semibold text-stone-600 dark:text-white mb-4">Confirmar eliminación</h2>
        <p class="text-gray-700 dark:text-gray-300 mb-6">¿Estás seguro de que lo quieres eliminar? Esta acción no se puede deshacer.</p>
        
        <div class="flex justify-end space-x-2">
            <button wire:click="$set('confirmingDelete', false)"
                    class="px-4 py-2 bg-gray-300 dark:bg-gray-600 text-black dark:text-white rounded hover:bg-gray-400">
                Cancelar
            </button>
            <button wire:click="{{ $action }}"
                    class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700">
                Eliminar
            </button>
        </div>
    </div>
</div>
@endif --}}

@props([
    'confirmingDelete' => false, 
    'action' => '',
    'titulo' => 'Confirmar eliminación',
    'mensaje' => '¿Estás seguro de que lo quieres eliminar? Esta acción no se puede deshacer.',
    'tareas' => [],
])

@if ($confirmingDelete)
<div class="fixed inset-0 flex items-center justify-center z-50 bg-black/70">
    <div class="bg-white dark:bg-gray-900 p-6 rounded-lg shadow-lg max-w-md w-full space-y-4">

        <h2 class="text-lg font-semibold text-stone-600 dark:text-white">{{ $titulo }}</h2>
        <p class="text-gray-700 dark:text-gray-300">{{ $mensaje }}</p>

        @if (!empty($tareas))
            <div class="border border-red-300 dark:border-red-600 p-3 rounded bg-red-50 dark:bg-red-900/30">
                <p class="text-sm text-red-700 dark:text-red-300 font-semibold mb-2">Tareas relacionadas:</p>
                <ul class="list-disc list-inside text-sm text-gray-700 dark:text-gray-300 max-h-40 overflow-auto">
                    @foreach($tareas as $tarea)
                        <li>{{ $tarea->tareaCatalogo->nombre ?? 'Tarea sin nombre' }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="flex justify-end space-x-2">
            <button wire:click="$set('confirmarEliminacion', false)"
                    class="px-4 py-2 bg-gray-300 dark:bg-gray-600 text-black dark:text-white rounded hover:bg-gray-400">
                Cancelar
            </button>
            <button wire:click="{{ $action }}"
                    class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700">
                Eliminar
            </button>
        </div>
    </div>
</div>
@endif
