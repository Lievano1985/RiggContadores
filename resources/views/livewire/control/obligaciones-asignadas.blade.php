{{-- 
Componente Blade: Obligaciones Asignadas
Autor: Luis Liévano - JL3 Digital
Descripción: Muestra las obligaciones del cliente, permite asignar contador, carpeta y fecha límite.
--}}

<div class="p-6 bg-white dark:bg-gray-900 text-gray-900 dark:text-white space-y-4">

    {{-- Encabezado --}}
    <div class="flex justify-between items-center">
        <h2 class="text-xl font-bold text-stone-600 dark:text-white">Obligaciones asignadas</h2>
    
        @if (count($obligacionesDisponibles) > 0)
            <button wire:click="mostrarModalCrear"
                class="px-4 py-2 bg-amber-600 text-white rounded hover:bg-amber-700">
                + Asignar obligación
            </button>
        @endif
    </div>
    

    {{-- Tabla principal --}}
    <div class="overflow-x-auto rounded shadow">
        <table class="min-w-full divide-y divide-gray-300 dark:divide-gray-700">
            <thead class="bg-stone-100 dark:bg-stone-900">
                <tr>
                    <th class="px-4 py-2 text-left">Obligación</th>
                    <th class="px-4 py-2 text-left">Carpeta en Drive</th>
                    <th class="px-4 py-2 text-left">Contador asignado</th>
                    <th class="px-4 py-2 text-left">Fecha límite</th>
                    <th class="px-4 py-2 text-center">Acciones</th>
                </tr>
            </thead>

            <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                @forelse ($asignaciones as $asignacion)
                    <tr>
                        <td class="px-4 py-2">
                            {{ $asignacion->obligacion->nombre ?? '—' }}
                            @if (in_array(strtolower($asignacion->obligacion->periodicidad ?? ''), ['unica', 'única']))
                                <span class="ml-2 text-xs text-amber-600 font-semibold">(Única)</span>
                            @endif
                        </td>
                        <td class="px-4 py-2">
                            {{ $asignacion->carpeta->nombre ?? 'Sin carpeta' }}
                        </td>
                        <td class="px-4 py-2">
                            {{ $asignacion->contador->name ?? 'Sin asignar' }}
                        </td>
                        <td class="px-4 py-2">
                            {{ $asignacion->fecha_vencimiento ? \Carbon\Carbon::parse($asignacion->fecha_vencimiento)->format('Y-m-d') : '—' }}
                        </td>
                        <td class="px-4 py-2 text-center space-x-2">
                            <button wire:click="editarAsignacion({{ $asignacion->id }})"
                                class="text-amber-600 hover:underline">Editar</button>
                            <button wire:click="confirmarEliminacionAsignacion({{ $asignacion->id }})"
                                class="text-red-600 hover:underline">Eliminar</button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-4 text-center text-gray-500 dark:text-gray-400">
                            No hay obligaciones asignadas.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Modal de creación / edición --}}
    @if ($modalVisible)
        <div class="fixed inset-0 flex items-center justify-center bg-stone-600/50 z-50">
            <div class="bg-white dark:bg-gray-900 p-6 rounded-lg shadow-lg w-full max-w-2xl">
                <h3 class="text-lg font-semibold text-stone-600 dark:text-white mb-4">
                    {{ $modoEdicion ? 'Editar obligación' : 'Nueva asignación' }}
                </h3>
                <form wire:submit.prevent="guardar" x-data="{ esUnica: false }"
                x-effect="
                    $wire.obligacion_id &&
                    $wire.call('getPeriodicidad', $wire.obligacion_id)
                        .then(p => esUnica = (p === 'unica' || p === 'única'))">
            
                {{-- Obligación --}}
                <div class="mb-3">
                    <label class="block text-sm font-medium mb-1">Obligación</label>
            
                    @if ($modoEdicion)
                        <div
                            class="px-3 py-2 border rounded bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300">
                            {{ $obligacionSeleccionada->nombre ?? '—' }}
                        </div>
                        <input type="hidden" wire:model="obligacion_id">
                    @else
                        <select wire:model="obligacion_id"
                            class="w-full px-3 py-2 border rounded dark:bg-gray-700 focus:outline-amber-600">
                            <option value="">Seleccione...</option>
                            @foreach ($obligacionesDisponibles as $obligacion)
                                <option value="{{ $obligacion->id }}">{{ $obligacion->nombre }}</option>
                            @endforeach
                        </select>
                    @endif
            
                    @error('obligacion_id')
                        <span class="text-red-500 text-sm">{{ $message }}</span>
                    @enderror
                </div>
            
                {{-- Contador --}}
                <div class="mb-3">
                    <label class="block text-sm font-medium mb-1">Contador asignado</label>
                    <select wire:model="contador_id"
                        class="w-full px-3 py-2 border rounded dark:bg-gray-700 focus:outline-amber-600">
                        <option value="">Seleccione...</option>
                        @foreach ($contadores as $contador)
                            <option value="{{ $contador->id }}">{{ $contador->name }}</option>
                        @endforeach
                    </select>
                    @error('contador_id')
                        <span class="text-red-500 text-sm">{{ $message }}</span>
                    @enderror
                </div>
            
                {{-- Fecha límite --}}
                <div class="mb-3">
                    <label class="block text-sm font-medium mb-1">Fecha límite</label>
                    <input type="date" wire:model="fecha_vencimiento" :required="!esUnica"
                        class="w-full px-3 py-2 border rounded dark:bg-gray-700 focus:outline-amber-600">
                    <p x-show="esUnica" class="text-xs text-gray-500 mt-1 italic">
                        Obligación única: el contador puede definir la fecha límite manualmente.
                    </p>
                    @error('fecha_vencimiento')
                        <span class="text-red-500 text-sm">{{ $message }}</span>
                    @enderror
                </div>
            
                {{-- Carpeta Drive --}}
                <div class="mt-6">
                    <label class="block text-sm font-semibold text-stone-600 mb-2">Carpeta en Drive</label>
                    <div x-data="{ abiertos: {}, seleccion: @entangle('carpeta_drive_id') }"
                        class="overflow-y-auto max-h-80 rounded border border-gray-200 dark:border-gray-700 p-3">
                        <ul class="space-y-1">
                            @foreach ($arbolCarpetas as $nodo)
                                <x-arbol-carpetas-nodo :nodo="$nodo" :nivel="0"
                                    model="carpeta_drive_id" />
                            @endforeach
                        </ul>
                    </div>
                    @error('carpeta_drive_id')
                        <span class="text-red-500 text-sm">{{ $message }}</span>
                    @enderror
                </div>
            
                {{-- Botones --}}
                <div class="flex justify-end space-x-2">
                    <button type="button" wire:click="$set('modalVisible', false)"
                        class="bg-gray-300 dark:bg-gray-600 px-4 py-2 rounded">Cancelar</button>
                    <button type="submit"
                        class="bg-amber-600 text-white px-4 py-2 rounded hover:bg-amber-700">Guardar</button>
                </div>
            </form>
            
            </div>
        </div>
    @endif
    <x-confirmacion-eliminacion :confirming-delete="$confirmarEliminacion" action="eliminarAsignacionConfirmada"
        titulo="Eliminar obligación asignada"
        mensaje="Esta obligación tiene tareas asignadas. Si la eliminas, también se eliminarán las tareas relacionadas."
        :tareas="$tareasRelacionadas" />

    {{-- ✅ Alerta de éxito --}}
    @if (session()->has('success'))
        <div x-data="{ show: true }" x-init="setTimeout(() => show = false, 3000)" x-show="show"
            x-transition:leave="transition ease-in duration-500"
            x-transition:leave-start="opacity-100 transform translate-y-0"
            x-transition:leave-end="opacity-0 transform -translate-y-10"
            class="fixed bottom-6 left-1/2 transform -translate-x-1/2 z-50 w-full max-w-sm p-4 text-sm text-green-800 bg-green-200 rounded-lg shadow-lg dark:bg-green-200 dark:text-green-900">
            {{ session('success') }}
        </div>
    @endif
</div>
