{{-- Vista: ValidacionesIndex
     Autor: Luis Lievano - JL3 Digital
     Bandeja de revision interna (sin envio a cliente)
--}}
@php use Illuminate\Support\Facades\Storage; @endphp

<div x-data="{ sidebar: @entangle('sidebarVisible') }">

    <div class="flex flex-wrap gap-2 justify-between items-center mb-4">

        <h2 class="text-xl font-bold text-stone-600 dark:text-white">
            Validar obligaciones
        </h2>
    </div>
    {{-- Header + filtros --}}
    <div class="flex flex-wrap gap-2 justify-between items-center mb-4">

        <div class="flex flex-wrap gap-2 items-center">
            {{-- Ejercicio --}}
            <select wire:model.live="filtroEjercicio"
                class="px-3 py-2 border rounded dark:bg-gray-700 dark:text-white border-gray-300 dark:border-gray-600 focus:border-amber-600 focus:ring focus:ring-amber-500/40 focus:outline-none">
                <option value="">Ejercicio</option>
                @foreach ($ejerciciosDisponibles ?? [] as $anio)
                    <option value="{{ $anio }}">{{ $anio }}</option>
                @endforeach
            </select>
            {{-- Mes --}}
            <select wire:model.live="filtroMes"
                class="px-3 py-2 border rounded dark:bg-gray-700 dark:text-white border-gray-300 dark:border-gray-600 focus:border-amber-600 focus:ring focus:ring-amber-500/40 focus:outline-none">
                <option value="">Mes</option>
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
    ] as $num => $mes)
                    <option value="{{ $num }}">{{ $mes }}</option>
                @endforeach
            </select>

        </div>
        <div class="flex flex-wrap gap-2 items-center">




            {{-- Estatus --}}
            <select wire:model.live="filtroEstatus" class="px-3 py-2 border rounded dark:bg-gray-700 dark:text-white border-gray-300 dark:border-gray-600 focus:border-amber-600 focus:ring focus:ring-amber-500/40 focus:outline-none">

                <option value="auto">Mes actual + vencidas</option>
                <option value="todos">Todos</option>

                <option value="asignada">Asignada</option>
                <option value="en_progreso">En progreso</option>
                <option value="realizada">Realizada</option>
                <option value="finalizado">Finalizado</option>
                <option value="rechazada">Rechazada</option>
                <option value="reabierta">Reabierta</option>

            </select>

            {{-- Buscar --}}
            {{-- Cliente --}}
            {{-- Cliente con autocompletar --}}
            <div 
            x-data="{
                open: false,
                search: '',
                clientes: @js($clientesDisponibles),
        
                get filtered() {
                    if (this.search === '') return this.clientes;
                    return this.clientes.filter(c => 
                        c.nombre.toLowerCase().includes(this.search.toLowerCase())
                    );
                },
        
                init() {
                    this.$watch('search', value => {
                        if (value === '') {
                            $wire.set('clienteSeleccionado', null);
                        }
                    });
                }
            }"
            class="relative w-64"
        >
        
            <input type="text"
                x-model="search"
                @focus="open = true"
                @click.away="open = false"
                placeholder="Seleccionar cliente..."
                class="w-full px-3 py-2 border rounded dark:bg-gray-700 dark:text-white border-gray-300 dark:border-gray-600 focus:border-amber-600 focus:ring focus:ring-amber-500/40 focus:outline-none"
            >
        
            <div 
                x-show="open"
                x-transition
                class="absolute z-50 w-full bg-white dark:bg-gray-800 border rounded shadow max-h-60 overflow-y-auto"
            >
                <template x-for="cliente in filtered" :key="cliente.id">
                    <div 
                        @click="
                            $wire.set('clienteSeleccionado', cliente.id);
                            search = cliente.nombre;
                            open = false;
                        "
                        class="px-3 py-2 cursor-pointer hover:bg-amber-100 dark:hover:bg-gray-700"
                    >
                        <span x-text="cliente.nombre"></span>
                    </div>
                </template>
        
                <div x-show="filtered.length === 0"
                    class="px-3 py-2 text-sm text-gray-400">
                    Sin resultados
                </div>
            </div>
        </div>
            <input type="text" wire:model.live="buscarObligacion" placeholder="Filtrar obligacion..."
                class="px-3 py-2 border rounded dark:bg-gray-700 dark:text-white border-gray-300 dark:border-gray-600 focus:border-amber-600 focus:ring focus:ring-amber-500/40 focus:outline-none">
        </div>
    </div>

    {{-- Tabla --}}
    <div class="bg-white dark:bg-gray-900 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                    <thead class="bg-stone-100 dark:bg-stone-900 text-xs">
                <tr>
                    <th class="px-2 py-2"></th>
                    <x-sortable-th field="cliente" label="Cliente" :sort-field="$sortField" :sort-direction="$sortDirection" class="px-3 py-2" />
                    <x-sortable-th field="obligacion" label="Obligacion" :sort-field="$sortField" :sort-direction="$sortDirection" class="px-3 py-2" />
                    <x-sortable-th field="contador" label="Contador" :sort-field="$sortField" :sort-direction="$sortDirection" class="px-3 py-2" />
                    <x-sortable-th field="estatus" label="Estatus" :sort-field="$sortField" :sort-direction="$sortDirection" align="center" class="px-3 py-2" />
                    <x-sortable-th field="periodo" label="Periodo" :sort-field="$sortField" :sort-direction="$sortDirection" align="center" class="px-3 py-2" />
                    <x-sortable-th field="fecha_vencimiento" label="Vencimiento" :sort-field="$sortField" :sort-direction="$sortDirection" align="center" class="px-3 py-2" />
                    <th class="px-3 py-2 text-center text-xs font-semibold">Tareas</th>
                    <th class="px-3 py-2 text-right text-xs font-semibold">Acciones</th>
                </tr>
            </thead>

            <tbody class="divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                @forelse($obligaciones as $oc)

                    @php
                        $open = $expandida[$oc->id] ?? false;
                        $totalTareas = $oc->tareasAsignadas->count();
                        $revisadas = $oc->tareasAsignadas->where('estatus', 'revisada')->count();
                    @endphp

                    <tr wire:key="obligacion-{{ $oc->id }}" class="hover:bg-gray-50 dark:hover:bg-gray-800/60">
                        <td class="px-2">
                            <button wire:click="toggleExpandida({{ $oc->id }})" class="hover:underline">
                                {{ $open ? '-' : '+' }}
                            </button>
                        </td>

                        <td class="px-3 py-2">{{ $oc->cliente->nombre }}</td>
                        <td class="px-3 py-2">{{ $oc->obligacion->nombre }}</td>
                        <td class="px-3 py-2">{{ $oc->contador->name ?? '-' }}</td>

                        {{-- Badge estatus obligacion --}}
                        <td class="px-3 py-2 text-center">
                            <x-status-badge :status="$oc->estatus" />
                        </td>
                        <td class="px-3 py-2 text-center whitespace-nowrap">
                            {{ $oc->ejercicio }}-{{ str_pad($oc->mes, 2, '0', STR_PAD_LEFT) }}
                        </td>
                        <td class="px-3 py-2 text-center whitespace-nowrap">
                            {{ \Carbon\Carbon::parse($oc->fecha_vencimiento)->format('Y-m-d') }}
                        </td>

                        <td class="px-3 py-2 text-center">
                            @if ($totalTareas > 0)
                                <span class="text-xs">
                                    {{ $revisadas }}/{{ $totalTareas }}
                                </span>
                            @else
                                <span class="text-xs">0</span>
                            @endif
                        </td>

                        <td class="px-3 py-2 text-right">
                            <x-action-icon icon="eye" label="Revisar" variant="primary"
                                wire:click="abrirSidebar({{ $oc->id }})" />
                        </td>
                    </tr>

                    {{-- Subfila con tareas --}}
                    @if ($open)
                        <tr wire:key="tareas-{{ $oc->id }}" class="bg-gray-50 dark:bg-gray-800/40">
                            <td colspan="9" class="px-6 pb-4 pt-2">
                                @if ($oc->tareasAsignadas->isEmpty())
                                    <p class="text-sm italic text-gray-500 dark:text-gray-400">
                                        Esta obligacion no tiene tareas ligadas.
                                    </p>
                                @else
                                    <ul class="list-disc ml-4 text-gray-700 dark:text-gray-300 text-sm space-y-1">
                                        @foreach ($oc->tareasAsignadas->sortBy(fn($t) => $t->tareaCatalogo->nombre ?? '') as $tarea)
                                            <li>
                                                {{ $tarea->tareaCatalogo->nombre ?? 'Sin nombre' }}
                                                -
                                                <span class="text-xs">{{ $tarea->contador->name ?? 'Sin contador' }}</span>
                                                <x-status-badge :status="$tarea->estatus" class="ml-2" />
                                            </li>
                                        @endforeach
                                    </ul>
                                @endif
                            </td>
                        </tr>
                    @endif

                @empty
                    <tr>
                        <td colspan="9" class="py-6 text-center text-gray-500">
                            No hay obligaciones para validar.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @include('livewire.shared.pagination-controls', ['paginator' => $obligaciones])

    {{-- SIDEBAR --}}
    <div x-cloak x-show="sidebar" x-transition.opacity class="fixed inset-0 bg-black/40 z-40 flex justify-end">
        <div class="flex-1" @click="$wire.cerrarSidebar()"></div>

        <div class="w-full max-w-xl bg-white dark:bg-gray-900 shadow-xl h-full border-l flex flex-col">

            {{-- Header --}}
            <div class="p-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
                <h3 class="text-lg font-semibold text-stone-700 dark:text-white">
                    Revision y validacion
                </h3>
                <button @click="$wire.cerrarSidebar()" class="text-gray-500 hover:text-black dark:hover:text-white">x</button>
            </div>

            {{-- Contenido --}}
            <div class="flex-1 overflow-y-auto p-4 space-y-6">

                @if ($obligacionSeleccionada)

                    {{-- OBLIGACION --}}
                    <div class="border p-4 rounded shadow-sm dark:border-gray-700">
                        <h4 class="font-semibold mb-2">Obligacion</h4>

                        <p><strong>Cliente:</strong> {{ $obligacionSeleccionada->cliente->nombre }}</p>
                        <p><strong>Obligacion:</strong> {{ $obligacionSeleccionada->obligacion->nombre }}</p>
                        <p><strong>Contador:</strong> {{ $obligacionSeleccionada->contador->name ?? '-' }}</p>


                        <p class="mt-1">
                            <strong>Estatus:</strong>
                            <span
                                class="text-sm">{{ ucfirst(str_replace('_', ' ', $obligacionSeleccionada->estatus)) }}</span>
                        </p>

                        <p class="mt-2"><strong>Comentario:</strong> {{ $obligacionSeleccionada->comentario ?? '-' }}
                        </p>
                        {{-- Archivos de la obligacion --}}
                        @if ($obligacionSeleccionada->archivos->count())
                            <div class="mt-3 space-y-1">
                                <strong>Archivos:</strong>

                                @foreach ($obligacionSeleccionada->archivos as $archivo)
                                    @if ($archivo->archivo)
                                        <a href="{{ Storage::disk('public')->url($archivo->archivo) }}"
                                            target="_blank" class="block text-blue-600 dark:text-blue-400 text-sm hover:underline">
                                            Archivo: {{ $archivo->nombre }}
                                        </a>
                                    @endif
                                @endforeach
                            </div>
                        @endif
                        <p>
                            <strong>Fecha vencimiento:</strong>
                            {{ $obligacionSeleccionada->fecha_finalizado
                                ? \Carbon\Carbon::parse($obligacionSeleccionada->fecha_finalizado)->format('Y-m-d')
                                : '-' }}
                        </p>
                        {{-- Rechazo obligacion --}}
                        @if (!$mostrarRechazoObligacion)
                            <button wire:click="$set('mostrarRechazoObligacion', true)"
                                class="mt-3 text-sm text-white bg-red-600 px-3 py-1 rounded hover:bg-red-700">
                                Rechazar obligacion
                            </button>
                        @else
                            <div class="mt-3">
                                <textarea wire:model.defer="comentarioRechazoObligacion"
                                    class="w-full px-3 py-2 border rounded dark:bg-gray-700 dark:text-white border-gray-300 dark:border-gray-600 focus:border-amber-600 focus:ring focus:ring-amber-500/40 focus:outline-none" placeholder="Motivo del rechazo"></textarea>

                                <div class="mt-2 text-right space-x-2">
                                    <button wire:click="rechazarObligacion"
                                        class="bg-red-600 text-white px-4 py-1 rounded text-sm hover:bg-red-700">
                                        Confirmar
                                    </button>
                                    <button wire:click="$set('mostrarRechazoObligacion', false)"
                                        class="px-3 py-1 border rounded text-sm">
                                        Cancelar
                                    </button>
                                </div>
                            </div>
                        @endif

                        {{-- FINALIZAR OBLIGACION --}}
                        @php
                            $tareasTotal = $obligacionSeleccionada->tareasAsignadas->count();
                            $tareasNoRevisadas = $obligacionSeleccionada->tareasAsignadas
                                ->where('estatus', '!=', 'revisada')
                                ->count();
                            $puedeFinalizar =
                                $obligacionSeleccionada->estatus === 'realizada' &&
                                ($tareasTotal === 0 || $tareasNoRevisadas === 0);
                        @endphp

                        <div class="mt-4">
                            @if ($puedeFinalizar)
                                <button wire:click="finalizarObligacion"
                                    class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700 text-sm">
                                    Finalizar obligacion
                                </button>
                            @else
                                <button disabled
                                    class="bg-green-300 text-white/80 px-4 py-2 rounded text-sm cursor-not-allowed">
                                    Finalizar obligacion
                                </button>
                                <p class="text-xs text-gray-500 mt-2">
                                    * Para finalizar: obligacion en <strong>realizada</strong> y todas las tareas en
                                    <strong>revisada</strong> (o sin tareas).
                                </p>
                            @endif
                        </div>
                    </div>

                    {{-- TAREAS --}}
                    <div class="space-y-4">
                        <h4 class="font-semibold">Tareas asignadas</h4>

                        @if ($obligacionSeleccionada->tareasAsignadas->isEmpty())
                            <p class="text-sm italic text-gray-500 dark:text-gray-400">
                                Esta obligacion no tiene tareas ligadas.
                            </p>
                        @else
                            @foreach ($obligacionSeleccionada->tareasAsignadas as $tarea)
                                <div wire:key="tarea-card-{{ $tarea->id }}"
                                    class="border p-4 rounded dark:border-gray-700">

                                    <p><strong>Tarea:</strong> {{ $tarea->tareaCatalogo->nombre ?? 'Sin nombre' }}</p>

                                    <p>
                                        <strong>Contador:</strong>
                                        {{ $tarea->contador->name ?? 'Sin contador' }}
                                    </p>

                                    <p><strong>Estatus:</strong> {{ ucfirst($tarea->estatus) }}</p>
                                    <p><strong>Comentario:</strong> {{ $tarea->comentario ?? '-' }}</p>
                                    {{-- Archivos de la tarea --}}
                                    @if ($tarea->archivos->count())
                                        <div class="mt-2 space-y-1">
                                            <strong>Archivos:</strong>

                                            @foreach ($tarea->archivos as $archivo)
                                                @if ($archivo->archivo)
                                                    <a href="{{ Storage::disk('public')->url($archivo->archivo) }}"
                                                        target="_blank"
                                                        class="block text-blue-600 dark:text-blue-400 text-sm hover:underline">
                                                        Archivo: {{ $archivo->nombre }}
                                                    </a>
                                                @endif
                                            @endforeach
                                        </div>
                                    @endif


                                    {{-- Boton revisar tarea --}}
                                    @if ($tarea->estatus === 'realizada')
                                        <button wire:click="marcarTareaRevisada({{ $tarea->id }})"
                                            class="mt-2 bg-green-600 text-white px-3 py-1 rounded text-sm hover:bg-green-700">
                                            Marcar como revisada
                                        </button>
                                    @elseif($tarea->estatus === 'revisada')
                                        <span
                                            class="mt-2 inline-block text-xs px-2 py-1 rounded bg-green-600 text-white">
                                            Revisada
                                        </span>
                                    @endif

                                    {{-- Rechazo tarea --}}
                                    @if (!($mostrarRechazoTarea[$tarea->id] ?? false))
                                        <button wire:click="$set('mostrarRechazoTarea.{{ $tarea->id }}', true)"
                                            class="mt-2 text-sm text-white bg-red-600 px-3 py-1 rounded hover:bg-red-700">
                                            Rechazar tarea
                                        </button>
                                    @else
                                        <div class="mt-2">
                                            <textarea wire:model.defer="comentarioRechazoTarea.{{ $tarea->id }}"
                                                class="w-full px-3 py-2 border rounded dark:bg-gray-700 dark:text-white border-gray-300 dark:border-gray-600 focus:border-amber-600 focus:ring focus:ring-amber-500/40 focus:outline-none" placeholder="Motivo del rechazo"></textarea>

                                            <div class="mt-2 text-right space-x-2">
                                                <button wire:click="rechazarTarea({{ $tarea->id }})"
                                                    class="bg-red-600 text-white px-4 py-1 rounded text-sm hover:bg-red-700">
                                                    Confirmar
                                                </button>
                                                <button
                                                    wire:click="$set('mostrarRechazoTarea.{{ $tarea->id }}', false)"
                                                    class="px-3 py-1 border rounded text-sm">
                                                    Cancelar
                                                </button>
                                            </div>
                                        </div>
                                    @endif

                                </div>
                            @endforeach
                        @endif
                    </div>

                @endif
            </div>
        </div>
    </div>

</div>

