<div class="p-6 bg-white dark:bg-gray-900 text-gray-900 dark:text-white space-y-4">

    {{-- Encabezado --}}
    <h2 class="text-xl font-bold text-stone-600 dark:text-white">
        Obligaciones asignadas
    </h2>

    <!-- 🔍 Filtros -->
    <div class="flex flex-wrap gap-4 items-center mb-4">

        <div>
            <label class="block text-sm font-semibold">Ejercicio</label>
            <select wire:model.live="filtroEjercicio"
                class="px-3 py-2 border rounded dark:bg-gray-700 dark:text-white
    border-gray-300 dark:border-gray-600 focus:border-amber-600
    focus:ring focus:ring-amber-500/40 focus:outline-none">
                <option value="">Selecciona...</option> {{-- 👈 OPCIÓN INICIAL --}}
                @foreach ($aniosDisponibles as $year)
                    <option value="{{ $year }}">{{ $year }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-sm font-medium">Mes</label>
            <select wire:model.live="filtroMes"
                class="px-3 py-2 border rounded dark:bg-gray-700 dark:text-white
    border-gray-300 dark:border-gray-600 focus:border-amber-600
    focus:ring focus:ring-amber-500/40 focus:outline-none">
                <option value="">Selecciona...</option> {{-- 👈 OPCIÓN INICIAL --}}

                @foreach ([
        1 => 'Enero',
        2 => 'Febrero',
        3 => 'Marzo',
        4 => 'Abril',
        5 => 'Mayo',
        6 => 'Junio',
        7 => 'Julio',
        8 => 'Agosto',
        9 => 'Septiembre',
        10 => 'Octubre',
        11 => 'Noviembre',
        12 => 'Diciembre',
    ] as $num => $nombre)
                    <option value="{{ $num }}">{{ $nombre }}</option>
                @endforeach
            </select>
        </div>

    </div>

    {{-- Tabla --}}
    <div class="overflow-x-auto rounded shadow">
        <table class="min-w-full text-sm divide-y divide-gray-300 dark:divide-gray-700">

            <thead class="bg-stone-100 dark:bg-stone-900">
                <tr>
                    <th class="px-4 py-2 text-left">Obligación</th>
                    <th class="px-4 py-2 text-left">Periodo</th>
                    <th class="px-4 py-2 text-left">Contador</th>
                    <th class="px-4 py-2 text-left">Carpeta</th>
                    <th class="px-4 py-2 text-left">Fecha límite</th>
                    <th class="px-4 py-2 text-left">Estatus</th>

                    <th class="px-4 py-2 text-center">Acciones</th>
                </tr>
            </thead>

            <tbody>
                @forelse($asignaciones as $a)
                    <tr class="@if ($a->fecha_vencimiento && \Carbon\Carbon::parse($a->fecha_vencimiento)->isPast() && $a->estatus != 'finalizado') bg-red-50 dark:bg-red-900/30 @endif">

                        <td class="px-4 py-2">
                            {{ $a->obligacion->nombre ?? '—' }}
                            @if (!$a->is_activa)
                                <span class="text-xs bg-stone-600 text-white px-2 rounded">Baja</span>
                            @endif
                        </td>

                        <td class="px-4 py-2">
                            {{ $a->ejercicio }}-{{ str_pad($a->mes, 2, '0', STR_PAD_LEFT) }}
                        </td>

                        <td class="px-4 py-2">
                            {{ $a->contador->name ?? '—' }}
                        </td>

                        <td class="px-4 py-2">
                            {{ $a->carpeta->nombre ?? '—' }}
                        </td>

                        <td class="px-4 py-2">
                            {{ $a->fecha_vencimiento ?? '—' }}
                        </td>

                        <td class="px-4 py-2">
                            <span
                                class="
                                    px-2 py-1 rounded text-xs font-semibold
                        
                                    @if($a->estatus === 'asignada')
                                        bg-gray-200 text-gray-800
                        
                                    @elseif($a->estatus === 'en_progreso')
                                        bg-blue-200 text-blue-800
                        
                                    @elseif($a->estatus === 'realizada')
                                        bg-indigo-200 text-indigo-800
                        
                                    @elseif($a->estatus === 'enviada_cliente')
                                        bg-yellow-200 text-yellow-800
                        
                                    @elseif($a->estatus === 'respuesta_cliente')
                                        bg-orange-200 text-orange-800
                        
                                    @elseif($a->estatus === 'respuesta_revisada')
                                        bg-purple-200 text-purple-800
                        
                                    @elseif($a->estatus === 'finalizado')
                                        bg-green-200 text-green-800
                        
                                    @elseif($a->estatus === 'rechazada')
                                        bg-red-200 text-red-800
                        
                                    @elseif($a->estatus === 'reabierta')
                                        bg-pink-200 text-pink-800
                        
                                    @else
                                        bg-gray-100 text-gray-600
                                    @endif
                                "
                            >
                                {{ str_replace('_',' ', $a->estatus) }}
                            </span>
                        </td>
                        
                        
                        <td class="px-4 py-2 text-center">
                            @if ($a->is_activa)
                                <button wire:click="editarAsignacion({{ $a->id }})"
                                    class="text-amber-600 hover:underline">Editar</button>

                                <button wire:click="confirmarBajaAsignacion({{ $a->id }})"
                                    class="text-red-600 hover:underline ml-2">Dar de baja</button>
                            @endif
                        </td>

                    </tr>

                @empty
                    <tr>
                        <td colspan="6" class="text-center py-4">
                            No hay obligaciones
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $asignaciones->links() }}
    </div>

    {{-- MODAL EDITAR --}}
    @if ($modalVisible)
        <div class="fixed inset-0 flex items-center justify-center bg-stone-600/50 z-50">
            <div class="bg-white dark:bg-gray-900 p-6 rounded-lg shadow-lg w-full max-w-2xl">
                <h3 class="text-lg font-semibold text-stone-600 dark:text-white mb-4">
                    Editar obligación
                </h3>

                <form wire:submit.prevent="guardar" wire:key="modal-obligacion-{{ $formKey }}">

                    {{-- Obligación (solo lectura en edición) --}}
                    <div class="mb-3">
                        <label class="block text-sm font-medium mb-1">Obligación</label>
                        <div
                            class="px-3 py-2 border rounded bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300">
                            {{ $obligacionSeleccionada->nombre ?? '—' }}
                        </div>
                    </div>

                    {{-- Contador --}}
                    <div class="mb-3">
                        <label class="block text-sm font-medium mb-1">Contador Responsable</label>
                        <select wire:model="contador_id"
                            class="w-full px-3 py-2 border rounded-md
                               dark:bg-gray-700 dark:text-white
                               border-gray-300 dark:border-gray-600
                               focus:border-amber-600 focus:ring focus:ring-amber-500/40
                               focus:outline-none">
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
                    {{--   <div class="mb-3">
                    <label class="block text-sm font-medium mb-1">Fecha límite</label>
                    <input type="date" wire:model="fecha_vencimiento"
                        class="w-full px-3 py-2 border rounded-md
                               dark:bg-gray-700 dark:text-white
                               border-gray-300 dark:border-gray-600
                               focus:border-amber-600 focus:ring focus:ring-amber-500/40
                               focus:outline-none">
                    @error('fecha_vencimiento') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div> --}}

                    {{-- Carpeta Drive --}}
                    <div class="mt-6">


                        <label class="block text-sm font-semibold text-stone-600 mb-2">Carpeta en Drive</label>
                        <div x-data="{ abiertos: {}, seleccion: @entangle('carpeta_drive_id') }"
                            class="p-4 bg-white dark:bg-gray-900 text-gray-900 dark:text-white rounded-lg shadow space-y-3">


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
                    <div class="flex justify-end space-x-2 mt-6">
                        <button type="button" wire:click="$set('modalVisible', false)"
                            class="bg-gray-300 dark:bg-gray-600 px-4 py-2 rounded">
                            Cancelar
                        </button>

                        <button type="submit" class="bg-amber-600 text-white px-4 py-2 rounded hover:bg-amber-700">
                            Guardar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif




    {{-- CONFIRMAR BAJA --}}
    @if ($confirmarBaja)
        <div class="fixed inset-0 bg-black/50 flex items-center justify-center">
            <div class="bg-white dark:bg-gray-900 p-6 rounded w-96">

                <label class="block text-sm mb-1">Motivo baja</label>
                <textarea wire:model.defer="motivoBaja" class="w-full border rounded p-2"></textarea>

                <div class="mt-4 flex justify-end gap-2">
                    <button wire:click="$set('confirmarBaja',false)"
                        class="bg-gray-300 px-3 py-2 rounded">Cancelar</button>

                    <button wire:click="darDeBajaAsignacionConfirmada"
                        class="bg-amber-600 text-white px-3 py-2 rounded">
                        Confirmar
                    </button>
                </div>

            </div>
        </div>
    @endif
    <x-notification />

</div>
