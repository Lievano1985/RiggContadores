<div class="p-6 bg-white dark:bg-gray-900 rounded-lg shadow">
    <div class="flex justify-between items-center mb-4">
        <h2 class="text-xl font-bold text-stone-600">Tareas Asignadas</h2>
        <div class="flex flex-col items-end space-y-2 mb-4">
            @if ($tareasCompletadas)
                <span class="px-2 py-1 text-xs bg-green-100 text-green-800 rounded-full">
                    ✔ Todas las tareas por obligación asignadas
                </span>
            @endif
            <button wire:click="crear" class="bg-amber-600 text-white px-4 py-2 rounded hover:bg-amber-700">
                + Asignar nueva tarea
            </button>
        </div>
    </div>
    <div class="flex space-x-3 mb-4">
        <div>
            <label class="text-sm font-semibold">Ejercicio</label>
            <select wire:model.live="filtroEjercicio"
                class="px-3 py-2 border rounded dark:bg-gray-700 dark:text-white
                   border-gray-300 dark:border-gray-600 focus:border-amber-600
                   focus:ring focus:ring-amber-500/40 focus:outline-none">
                @foreach ($aniosDisponibles as $year)
                    <option value="{{ $year }}">{{ $year }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="text-sm font-semibold">Mes</label>
            <select wire:model.live="filtroMes"
                class="px-3 py-2 border rounded dark:bg-gray-700 dark:text-white
            border-gray-300 dark:border-gray-600 focus:border-amber-600
            focus:ring focus:ring-amber-500/40 focus:outline-none">
                @foreach (range(1, 12) as $m)
                    <option value="{{ $m }}">
                        {{ ucfirst(\Carbon\Carbon::create()->month($m)->locale('es')->monthName) }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="mb-4 flex items-center gap-3">
            <div>
                <label class="text-sm font-semibold">Buscar tarea</label>
                <input type="text" wire:model.live="buscarTarea" placeholder="Nombre de la tarea"
                    class="px-3 py-2 border rounded-md 
                               dark:bg-gray-700 dark:text-white 
                               border-gray-300 dark:border-gray-600 
                               focus:border-amber-600 focus:ring focus:ring-amber-500/40 
                               focus:outline-none">
            </div>
        </div>
    </div>



    <table class="min-w-full divide-y divide-gray-300 dark:divide-gray-700 text-sm">
        <thead class="bg-stone-100 dark:bg-stone-900">
            <tr>
                <th class="px-4 py-2 text-left">Tarea</th>
                <th class="px-4 py-2 text-left">Carpeta Drive</th> {{-- 👈 NUEVO --}}
                <th class="px-4 py-2 text-left">Contador Responsable</th>
                <th class="px-4 py-2 text-left">Obligación</th>
                <th class="px-4 py-2 text-left">Vencimiento</th>
                <th class="px-4 py-2 text-left">Acciones</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
            @foreach ($tareasAsignadas as $tarea)
                <tr class="@if ($tarea->estatus === 'cancelada') opacity-70 dark:opacity-60 @endif">
                    <td class="px-4 py-2">
                        <div class="flex items-center gap-2">
                            <span>{{ $tarea->tareaCatalogo->nombre }}</span>

                            {{-- Badge según estatus --}}
                            @switch($tarea->estatus)
                                @case('cancelada')
                                    <span
                                        class="text-xs font-semibold px-2 py-0.5 rounded-full bg-stone-600 text-white dark:bg-gray-700 cursor-help"
                                        title="Tarea cancelada por baja de obligación o cliente">
                                        Cancelada
                                    </span>
                                @break

                                @case('terminada')
                                @case('revisada')
                                    <span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-green-600 text-white">
                                        {{ ucfirst($tarea->estatus) }}
                                    </span>
                                @break

                                @case('en_progreso')
                                    <span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-amber-500 text-white">
                                        En progreso
                                    </span>
                                @break

                                @default
                                    <span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-gray-400 text-white">
                                        {{ ucfirst($tarea->estatus) }}
                                    </span>
                            @endswitch
                        </div>
                    </td>
                    <td class="px-4 py-2 text-sm">
                        @php
                            $carpeta = $tarea->carpeta_drive_id 
                                ? \App\Models\CarpetaDrive::find($tarea->carpeta_drive_id) 
                                : null;
                        @endphp
                    
                        @if ($carpeta)
                           
                                {{ $carpeta->nombre }}
                         
                        @else
                            Sin carpeta
                        @endif
                    </td>
                    <td class="px-4 py-2">{{ $tarea->contador->name ?? '-' }}</td>

                    <td class="px-4 py-2">
                        {{ $tarea->obligacionClientecontador?->obligacion?->nombre ?? 'Sin obligación' }}
                    </td>

                    <td class="px-4 py-2">
                        {{ $tarea->fecha_limite ? \Carbon\Carbon::parse($tarea->fecha_limite)->format('Y-m-d') : '—' }}
                    </td>

                    <td class="px-4 py-2 space-x-2">
                        @if ($tarea->estatus !== 'cancelada')
                            <button wire:click="editar({{ $tarea->id }})"
                                class="text-blue-600 hover:underline">Editar</button>
                            <button wire:click="eliminar({{ $tarea->id }})"
                                class="text-red-600 hover:underline">Eliminar</button>
                        @else
                            <span class="text-gray-500 text-sm italic">Sin acciones</span>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>

    </table>

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

                    {{-- Auxiliar --}}
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

                    {{-- Tiempo y fecha --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
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

                        <div>
                            <label class="block text-sm mb-1">Fecha de Vencimiento</label>
                            <input type="date" wire:model.defer="fecha_limite"
                                class="w-full px-3 py-2 border rounded-md 
                                       dark:bg-gray-700 dark:text-white 
                                       border-gray-300 dark:border-gray-600 
                                       focus:border-amber-600 focus:ring focus:ring-amber-500/40 
                                       focus:outline-none">
                            @error('fecha_limite')
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
