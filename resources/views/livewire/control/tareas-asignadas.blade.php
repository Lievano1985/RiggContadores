<div class="p-6 bg-white dark:bg-gray-900 rounded-lg shadow">
    <div class="flex justify-between items-center mb-4">
        <h2 class="text-xl font-bold text-stone-600 dark:text-white">Tareas Asignadas</h2>
        <button type="button" wire:click="abrirConfiguracionTareas"
            class="px-4 py-2 text-sm font-semibold text-white bg-amber-600 rounded hover:bg-amber-700">
            Configurar tareas
        </button>
    </div>
    <div class="flex flex-wrap gap-4 items-center mb-4">
        <div>

            <label class="block text-sm font-semibold text-stone-600 dark:text-white">Ejercicio</label>
            <select wire:model.live="filtroEjercicio"
                class="px-3 py-2 border rounded dark:bg-gray-700 dark:text-white
                   border-gray-300 dark:border-gray-600 focus:border-amber-600
                   focus:ring focus:ring-amber-500/40 focus:outline-none">
                <option value="">Selecciona...</option> {{-- OPCION INICIAL --}}

                @foreach ($aniosDisponibles as $year)
                    <option value="{{ $year }}">{{ $year }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-sm font-semibold text-stone-600 dark:text-white">Mes</label>
            <select wire:model.live="filtroMes"
                class="px-3 py-2 border rounded dark:bg-gray-700 dark:text-white
            border-gray-300 dark:border-gray-600 focus:border-amber-600
            focus:ring focus:ring-amber-500/40 focus:outline-none">
                <option value="">Selecciona...</option>
                <option value="__all__">Todos</option>
                @foreach (range(1, 12) as $m)
                    <option value="{{ $m }}">
                        {{ ucfirst(\Carbon\Carbon::create()->month($m)->locale('es')->monthName) }}
                    </option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-sm font-semibold text-stone-600 dark:text-white">Periodicidad</label>
            <select wire:model.live="filtroPeriodicidad"
                class="px-3 py-2 border rounded dark:bg-gray-700 dark:text-white
            border-gray-300 dark:border-gray-600 focus:border-amber-600
            focus:ring focus:ring-amber-500/40 focus:outline-none">
                <option value="">Todas</option>
                @foreach ($periodicidadesDisponibles as $periodicidad)
                    <option value="{{ $periodicidad }}">{{ ucfirst($periodicidad) }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-sm font-semibold text-stone-600 dark:text-white">Buscar tarea/Obligacion</label>
            <input type="text" wire:model.live="buscarTarea" placeholder="Nombre de la tarea"
                class="px-3 py-1.5 border rounded-sm
                               dark:bg-gray-700 dark:text-white 
                               border-gray-300 dark:border-gray-600 
                               focus:border-amber-600 focus:ring focus:ring-amber-500/40 
                               focus:outline-none">
        </div>

    </div>
    <div class="bg-white dark:bg-gray-900 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-x-auto">
    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
        
                <thead class="bg-stone-100 dark:bg-stone-900">
            <tr>
                <x-sortable-th field="tarea" label="Tarea" :sort-field="$sortField" :sort-direction="$sortDirection" />
                <x-sortable-th field="ejercicio" label="Periodo" :sort-field="$sortField" :sort-direction="$sortDirection" />
                <th class="px-4 py-2 text-left text-xs font-semibold">Carpeta Drive</th>
                <x-sortable-th field="contador" label="Contador" :sort-field="$sortField" :sort-direction="$sortDirection" />
                <x-sortable-th field="obligacion" label="Obligación" :sort-field="$sortField" :sort-direction="$sortDirection" />
                <x-sortable-th field="fecha_limite" label="Vencimiento" :sort-field="$sortField" :sort-direction="$sortDirection" />
                <x-sortable-th field="estatus" label="Estatus" :sort-field="$sortField" :sort-direction="$sortDirection" />
                <th class="px-4 py-2 text-center text-xs font-semibold">Acciones</th>
            </tr>
        </thead>

        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
            @foreach ($tareasAsignadas as $tarea)
                <tr
                    class="
                        hover:bg-gray-50 dark:hover:bg-gray-800/60
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
                            -
                        @endif
                    </td>

                    {{-- CARPETA --}}
                    <td class="px-4 py-2">
                        @php
                            $carpeta = $tarea->carpeta_drive_id
                                ? \App\Models\CarpetaDrive::find($tarea->carpeta_drive_id)
                                : null;
                        @endphp

                        {{ $tarea->sin_carpeta ? 'Sin carpeta' : ($carpeta?->nombre ?? '-') }}
                    </td>

                    {{-- CONTADOR --}}
                    <td class="px-4 py-2">
                        {{ $tarea->contador->name ?? '-' }}
                    </td>

                    {{-- OBLIGACION --}}
                    <td class="px-4 py-2">
                        {{ $tarea->obligacionClientecontador?->obligacion?->nombre ?? 'Sin obligacion' }}
                    </td>

                    {{-- FECHA LIMITE --}}
                    <td class="px-4 py-2 whitespace-nowrap">
                        {{ $tarea->fecha_limite ? \Carbon\Carbon::parse($tarea->fecha_limite)->format('Y-m-d') : '-' }}
                    </td>
                    {{-- ESTATUS --}}
                    <td class="px-4 py-2">
                        <x-status-badge :status="$tarea->estatus" />
                    </td>


                    {{-- ACCIONES --}}
                    <td class="px-4 py-2 text-center">
                        @if ($tarea->estatus !== 'cancelada')
                            <x-action-icon icon="edit" label="Editar" variant="primary"
                                wire:click="editar({{ $tarea->id }})" />
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
        @include('livewire.shared.pagination-controls', ['paginator' => $tareasAsignadas])
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

                    {{-- Obligacion --}}
                    <div class="space-y-1">
                        <label class="block text-sm font-semibold text-stone-600 dark:text-gray-300">Obligacion</label>
                        @if ($modoEdicion)
                            <p
                                class="text-sm text-gray-800 dark:text-white px-3 py-2 bg-gray-100 dark:bg-gray-800 border border-gray-300 rounded">
                                {{ $obligacion_id
                                    ? optional($obligacionesAsignadas->firstWhere('id', $obligacion_id)?->obligacion)->nombre
                                    : 'Sin obligacion' }}
                            </p>
                            <input type="hidden" wire:model="obligacion_id">
                        @else
                            <select wire:model.live="obligacion_id"
                                class="w-full px-3 py-2 border rounded-md 
                                       dark:bg-gray-700 dark:text-white 
                                       border-gray-300 dark:border-gray-600 
                                       focus:border-amber-600 focus:ring focus:ring-amber-500/40 
                                       focus:outline-none">
                                <option value="">-- Selecciona una opcion --</option>
                                <option value="sin">Sin obligacion</option>
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
                                <p class="text-sm text-gray-500 dark:text-gray-400 italic">Selecciona una obligacion para ver sus tareas.
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
                            <label class="block text-sm mb-1 text-stone-600 dark:text-white">Contador Responsable</label>
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
                            <label class="block text-sm mb-1 text-stone-600 dark:text-white">Tiempo estimado (minutos)</label>
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

                    {{-- Arbol de carpetas --}}
                    <label class="mb-3 flex items-center gap-2 text-sm font-medium text-stone-600 dark:text-gray-300">
                        <input type="checkbox" wire:model.live="sin_carpeta" class="rounded border-gray-300 text-amber-600 focus:ring-amber-500">
                        No requiere carpeta
                    </label>
                    <div x-data="{ abiertos: {}, seleccion: @entangle('carpeta_drive_id') }"
                        @class([
                            'p-4 bg-white dark:bg-gray-900 text-gray-900 dark:text-white rounded-lg shadow space-y-3',
                            'opacity-50 pointer-events-none' => $sin_carpeta,
                        ])>
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
                            class="px-4 py-2 bg-amber-600 text-white rounded hover:bg-amber-700">Cancelar</button>
                        <button type="submit"
                            class="px-4 py-2 bg-amber-600 text-white rounded hover:bg-amber-700">Guardar</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- Panel de configuración por cliente para futuras generaciones --}}
    @if ($panelConfiguracionTareasVisible)
        <div class="fixed inset-0 z-50 bg-stone-900/40" wire:click="cerrarConfiguracionTareas"></div>
        <aside class="fixed inset-y-0 right-0 z-50 flex w-full max-w-xl flex-col bg-white shadow-2xl dark:bg-gray-900"
            role="dialog" aria-modal="true" aria-label="Configurar tareas del cliente">
            <div class="flex items-center justify-between border-b border-gray-200 p-5 dark:border-gray-700">
                <div>
                    <h3 class="text-lg font-semibold text-stone-700 dark:text-white">Configurar tareas</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Define las tareas que se crearán para {{ $cliente->nombre ?? $cliente->razon_social }} en próximos periodos.
                    </p>
                </div>
                <button type="button" wire:click="cerrarConfiguracionTareas" class="text-2xl leading-none text-gray-500 hover:text-gray-800 dark:hover:text-white" aria-label="Cerrar">&times;</button>
            </div>

            <div class="border-b border-gray-200 p-5 dark:border-gray-700">
                <label class="block text-sm font-semibold text-stone-600 dark:text-white">Mostrar</label>
                <select wire:model.live="filtroConfiguracionTareas"
                    class="mt-1 w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white">
                    <option value="activas">Tareas activas</option>
                    <option value="inactivas">Tareas inactivas</option>
                    <option value="todas">Todas las tareas</option>
                </select>
            </div>

            <div class="flex-1 overflow-y-auto p-5">
                <p class="mb-4 text-sm text-gray-600 dark:text-gray-300">
                    Desactivar una tarea no borra ni modifica tareas existentes; solo evita su generación futura para este cliente.
                </p>

                <div class="space-y-3">
                    @forelse ($this->obligacionesConfigurables as $obligacion)
                        <details class="rounded border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800">
                            <summary class="cursor-pointer px-4 py-3 font-semibold text-stone-700 dark:text-white">
                                {{ $obligacion['nombre'] }}
                                <span class="ml-1 text-sm font-normal text-gray-500">({{ $obligacion['tareas']->count() }})</span>
                            </summary>
                            <div class="divide-y divide-gray-100 border-t border-gray-200 dark:divide-gray-700 dark:border-gray-700">
                                @foreach ($obligacion['tareas'] as $tarea)
                                    <label class="flex cursor-pointer items-start gap-3 px-4 py-3 hover:bg-stone-50 dark:hover:bg-gray-700">
                                        <input type="checkbox" class="mt-1 rounded border-gray-300 text-amber-600 focus:ring-amber-500"
                                            wire:model.defer="configuracionTareasSeleccionadas.{{ $tarea['id'] }}">
                                        <span>
                                            <span class="block text-sm font-medium text-stone-700 dark:text-white">{{ $tarea['nombre'] }}</span>
                                            @if ($tarea['descripcion'])
                                                <span class="block text-xs text-gray-500 dark:text-gray-400">{{ $tarea['descripcion'] }}</span>
                                            @endif
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                        </details>
                    @empty
                        <p class="rounded border border-dashed border-gray-300 p-4 text-sm text-gray-500 dark:border-gray-600 dark:text-gray-400">
                            No hay tareas {{ $filtroConfiguracionTareas === 'inactivas' ? 'inactivas' : 'activas' }} para las obligaciones vigentes de este cliente.
                        </p>
                    @endforelse
                </div>
            </div>
            <div class="flex justify-end gap-3 border-t border-gray-200 p-5 dark:border-gray-700">
                <button type="button" wire:click="cerrarConfiguracionTareas"
                    class="rounded border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-100 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-800">
                    Cancelar
                </button>
                <button type="button" wire:click="guardarConfiguracionTareas"
                    class="rounded bg-amber-600 px-4 py-2 text-sm font-semibold text-white hover:bg-amber-700">
                    Guardar configuración
                </button>
            </div>
        </aside>
    @endif

</div>
