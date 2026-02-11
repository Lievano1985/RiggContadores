<div class="p-6 bg-white dark:bg-gray-900 rounded-lg shadow">
    <div class="flex justify-between items-center mb-4">
        <h2 class="text-xl font-bold text-stone-600">Tareas Asignadas</h2>
       
    </div>
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
            <label class="block text-sm font-semibold">Mes</label>
            <select wire:model.live="filtroMes"
                class="px-3 py-2 border rounded dark:bg-gray-700 dark:text-white
            border-gray-300 dark:border-gray-600 focus:border-amber-600
            focus:ring focus:ring-amber-500/40 focus:outline-none">
                <option value="">Selecciona...</option> {{-- 👈 OPCIÓN INICIAL --}}
                @foreach (range(1, 12) as $m)
                    <option value="{{ $m }}">
                        {{ ucfirst(\Carbon\Carbon::create()->month($m)->locale('es')->monthName) }}
                    </option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-sm font-semibold">Buscar tarea/Obligacion</label>
            <input type="text" wire:model.live="buscarTarea" placeholder="Nombre de la tarea"
                class="px-3 py-1.5 border rounded-sm
                               dark:bg-gray-700 dark:text-white 
                               border-gray-300 dark:border-gray-600 
                               focus:border-amber-600 focus:ring focus:ring-amber-500/40 
                               focus:outline-none">
        </div>

    </div>
    <div class="overflow-x-auto rounded shadow">
    <table class="min-w-full divide-y divide-gray-300 dark:divide-gray-700 text-sm">
        
        <thead class="bg-stone-100 dark:bg-stone-900">
            <tr>
                <th class="px-4 py-2 text-left">Tarea</th>
                <th class="px-4 py-2 text-left">Periodo</th>
                <th class="px-4 py-2 text-left">Carpeta Drive</th>
                <th class="px-4 py-2 text-left">Contador</th>
                <th class="px-4 py-2 text-left">Obligación</th>
                <th class="px-4 py-2 text-left">Vencimiento</th>
                <th class="px-4 py-2 text-left">Estatus</th>
                <th class="px-4 py-2 text-center">Acciones</th>
            </tr>
        </thead>

        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
            @foreach ($tareasAsignadas as $tarea)
                <tr
                    class="
                        {{-- VENCIDA --}}
                        @if (
                            $tarea->fecha_limite &&
                                \Carbon\Carbon::parse($tarea->fecha_limite)->isPast() &&
                                !in_array($tarea->estatus, ['terminada', 'cancelada'])) bg-red-50 dark:bg-red-900/30 @endif
    
                        {{-- CANCELADA --}}
                        @if ($tarea->estatus === 'cancelada') opacity-70 dark:opacity-60 @endif
                    ">

                    {{-- TAREA --}}
                    <td class="px-4 py-2">
                        {{ $tarea->tareaCatalogo->nombre }}
                    </td>

                    {{-- PERIODO --}}
                    <td class="px-4 py-2 whitespace-nowrap">
                        @if ($tarea->ejercicio && $tarea->mes)
                            {{ $tarea->ejercicio }}-{{ str_pad($tarea->mes, 2, '0', STR_PAD_LEFT) }}
                        @else
                            —
                        @endif
                    </td>

                    {{-- CARPETA --}}
                    <td class="px-4 py-2">
                        @php
                            $carpeta = $tarea->carpeta_drive_id
                                ? \App\Models\CarpetaDrive::find($tarea->carpeta_drive_id)
                                : null;
                        @endphp

                        {{ $carpeta?->nombre ?? 'Sin carpeta' }}
                    </td>

                    {{-- CONTADOR --}}
                    <td class="px-4 py-2">
                        {{ $tarea->contador->name ?? '—' }}
                    </td>

                    {{-- OBLIGACIÓN --}}
                    <td class="px-4 py-2">
                        {{ $tarea->obligacionClientecontador?->obligacion?->nombre ?? 'Sin obligación' }}
                    </td>

                    {{-- FECHA LIMITE --}}
                    <td class="px-4 py-2 whitespace-nowrap">
                        {{ $tarea->fecha_limite ? \Carbon\Carbon::parse($tarea->fecha_limite)->format('Y-m-d') : '—' }}
                    </td>
                    {{-- ESTATUS --}}
                    <td class="px-4 py-2">
                        <span
                            class="px-2 py-1 rounded text-xs font-semibold
                            @if ($tarea->estatus === 'asignada') bg-gray-200 text-gray-800
                             @elseif($tarea->estatus === 'en_progreso') bg-blue-200 text-blue-800
                             @elseif($tarea->estatus === 'realizada') bg-indigo-200 text-indigo-800
                             @elseif($tarea->estatus === 'revisada') bg-purple-200 text-purple-800 
                             @elseif($tarea->estatus === 'rechazada') bg-red-200 text-red-800
                             @elseif($tarea->estatus === 'reabierta') bg-pink-200 text-pink-800 
                             @else bg-gray-100 text-gray-600 @endif ">
                            {{ str_replace('_', ' ', $tarea->estatus) }}
                        </span>
                    </td>


                    {{-- ACCIONES --}}
                    <td class="px-4 py-2 text-center space-x-2">
                        @if ($tarea->estatus !== 'cancelada')
                            <button wire:click="editar({{ $tarea->id }})" class="text-amber-600 hover:underline">
                                Editar
                            </button>
                        @else
                            <span class="text-gray-500 text-sm italic">
                                Sin acciones
                            </span>
                        @endif
                    </td>

                </tr>
            @endforeach
        </tbody>
    </table>
    </div>


    <div class="mt-4">
        {{ $tareasAsignadas->links() }}
    </div>

    {{-- Modal --}}
    @if ($modalFormVisible)
        <div class="fixed inset-0 flex items-center justify-center bg-stone-600/50 z-50 p-4">
            <div
                class="bg-white dark:bg-gray-900 p-6 rounded-lg shadow-lg w-full max-w-2xl max-h-[85vh] overflow-y-auto">
                <h3 class="text-lg font-semibold mb-4 text-stone-700 dark:text-white">
                    {{ $modoEdicion ? 'Editar tarea asignada' : 'Asignar nueva tarea' }}
                </h3>

                <form wire:submit.prevent="guardar" class="space-y-4">

                    {{-- Obligación --}}
                    <div class="space-y-1">
                        <label class="block text-sm font-semibold text-stone-600 dark:text-gray-300">Obligación</label>
                        @if ($modoEdicion)
                            <p
                                class="text-sm text-gray-800 dark:text-white px-3 py-2 bg-gray-100 dark:bg-gray-800 border border-gray-300 rounded">
                                {{ $obligacion_id
                                    ? optional($obligacionesAsignadas->firstWhere('id', $obligacion_id)?->obligacion)->nombre
                                    : 'Sin obligación' }}
                            </p>
                            <input type="hidden" wire:model="obligacion_id">
                        @else
                            <select wire:model.live="obligacion_id"
                                class="w-full px-3 py-2 border rounded-md 
                                       dark:bg-gray-700 dark:text-white 
                                       border-gray-300 dark:border-gray-600 
                                       focus:border-amber-600 focus:ring focus:ring-amber-500/40 
                                       focus:outline-none">
                                <option value="">-- Selecciona una opción --</option>
                                <option value="sin">Sin obligación</option>
                                @foreach ($obligacionesAsignadas as $pivot)
                                    <option value="{{ $pivot->id }}">{{ $pivot->obligacion->nombre }}</option>
                                @endforeach
                            </select>
                            @error('obligacion_id')
                                <span class="text-red-600 text-sm">{{ $message }}</span>
                            @enderror
                        @endif
                    </div>

                    {{-- Tarea --}}
                    <div class="space-y-1">
                        <label class="block text-sm font-semibold text-stone-600 dark:text-gray-300">Tarea</label>
                        @if ($modoEdicion)
                            <p
                                class="text-sm text-gray-800 dark:text-white px-3 py-2 bg-gray-100 dark:bg-gray-800 border border-gray-300 rounded">
                                {{ optional($tareasDisponibles->firstWhere('id', $tarea_catalogo_id))->nombre ?? 'Sin nombre' }}
                            </p>
                            <input type="hidden" wire:model="tarea_catalogo_id">
                        @else
                            @if ($tareasDisponibles && $tareasDisponibles->count() > 0)
                                <select wire:model="tarea_catalogo_id"
                                    class="w-full px-3 py-2 border rounded-md 
                                           dark:bg-gray-700 dark:text-white 
                                           border-gray-300 dark:border-gray-600 
                                           focus:border-amber-600 focus:ring focus:ring-amber-500/40 
                                           focus:outline-none">
                                    <option value="">-- Selecciona una tarea --</option>
                                    @foreach ($tareasDisponibles as $tarea)
                                        <option value="{{ $tarea->id }}">{{ $tarea->nombre }}</option>
                                    @endforeach
                                </select>
                            @else
                                <p class="text-sm text-gray-500 italic">Selecciona una obligación para ver sus tareas.
                                </p>
                            @endif
                            @error('tarea_catalogo_id')
                                <span class="text-red-600 text-sm">{{ $message }}</span>
                            @enderror
                        @endif
                    </div>


                    {{-- Tiempo y fecha --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                        <div>
                            <label class="block text-sm mb-1">Contador Responsable</label>
                            <select wire:model.defer="contador_id"
                                class="w-full px-3 py-2 border rounded-md 
                                       dark:bg-gray-700 dark:text-white 
                                       border-gray-300 dark:border-gray-600 
                                       focus:border-amber-600 focus:ring focus:ring-amber-500/40 
                                       focus:outline-none">
                                <option value="">Selecciona un Auxiliar</option>
                                @foreach ($contadores as $u)
                                    <option value="{{ $u->id }}">{{ $u->name }}</option>
                                @endforeach
                            </select>
                            @error('contador_id')
                                <span class="text-red-500 text-sm">{{ $message }}</span>
                            @enderror

                        </div>

                        <div>
                            <label class="block text-sm mb-1">Tiempo estimado (minutos)</label>
                            <input type="number" wire:model.defer="tiempo_estimado" min="1" max="1440"
                                placeholder="Ej. 60"
                                class="w-full px-3 py-2 border rounded-md 
                                       dark:bg-gray-700 dark:text-white 
                                       border-gray-300 dark:border-gray-600 
                                       focus:border-amber-600 focus:ring focus:ring-amber-500/40 
                                       focus:outline-none">
                            @error('tiempo_estimado')
                                <span class="text-red-500 text-sm">{{ $message }}</span>
                            @enderror
                        </div>


                    </div>

                    {{-- Árbol de carpetas --}}
                    <div x-data="{ abiertos: {}, seleccion: @entangle('carpeta_drive_id') }"
                        class="p-4 bg-white dark:bg-gray-900 text-gray-900 dark:text-white rounded-lg shadow space-y-3">
                        <label class="block text-sm mb-1 text-stone-600 dark:text-gray-300">Carpeta en Drive</label>
                        <div class="overflow-y-auto max-h-80 rounded border border-gray-200 dark:border-gray-700 p-3">
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
                    <div class="mt-6 flex justify-end space-x-2">
                        <button type="button" wire:click="cerrarModal"
                            class="px-4 py-2 bg-gray-300 dark:bg-gray-700 text-black dark:text-white rounded hover:bg-gray-400">Cancelar</button>
                        <button type="submit"
                            class="px-4 py-2 bg-amber-600 text-white rounded hover:bg-amber-700">Guardar</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

</div>
