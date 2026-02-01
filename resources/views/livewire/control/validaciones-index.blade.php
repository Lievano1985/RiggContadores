{{-- Vista: ValidacionesIndex
     Autor: Luis Liévano - JL3 Digital
     Bandeja de revisión interna (sin envío a cliente)
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
                class="px-3 py-2 border rounded dark:bg-gray-700 dark:text-white focus:outline-amber-600">
                <option value="">Ejercicio</option>
                @foreach ($ejerciciosDisponibles ?? [] as $anio)
                    <option value="{{ $anio }}">{{ $anio }}</option>
                @endforeach
            </select>

            {{-- Mes --}}
            <select wire:model.live="filtroMes"
                class="px-3 py-2 border rounded dark:bg-gray-700 dark:text-white focus:outline-amber-600">
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

            {{-- Estatus --}}
            <select wire:model.live="filtroEstatus" class="px-3 py-2 border rounded dark:bg-gray-700 dark:text-white">

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
            <input type="text" wire:model.live="search" placeholder="Buscar cliente..."
                class="px-3 py-2 border rounded dark:bg-gray-700 dark:text-white focus:outline-amber-600">

        </div>
    </div>

    {{-- Tabla --}}
    <div class="bg-white dark:bg-gray-900 border rounded shadow-sm overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-300 dark:divide-gray-700">
            <thead class="bg-stone-100 dark:bg-stone-900 text-xs">
                <tr>
                    <th class="px-2 py-2"></th>
                    <th class="px-3 py-2 text-left">Cliente</th>
                    <th class="px-3 py-2 text-left">Obligación</th>
                    <th class="px-3 py-2 text-left">Contador</th>
                    <th class="px-3 py-2 text-center">Estatus</th>
                    <th class="px-3 py-2 text-center">Periodo</th>
                    <th class="px-3 py-2 text-center">Vencimiento</th>
                    <th class="px-3 py-2 text-center">Tareas</th>
                    <th class="px-3 py-2 text-right">Acciones</th>
                </tr>
            </thead>

            <tbody class="divide-y divide-gray-300 dark:divide-gray-700 text-sm">
                @forelse($obligaciones as $oc)
                
                    @php
                        $open = $expandida[$oc->id] ?? false;
                        $totalTareas = $oc->tareasAsignadas->count();
                        $revisadas = $oc->tareasAsignadas->where('estatus', 'revisada')->count();
                    @endphp

                    <tr wire:key="obligacion-{{ $oc->id }}" class="hover:bg-gray-50 dark:hover:bg-gray-800/60">
                        <td class="px-2">
                            <button wire:click="toggleExpandida({{ $oc->id }})" class="hover:underline">
                                {{ $open ? '−' : '+' }}
                            </button>
                        </td>

                        <td class="px-3 py-2">{{ $oc->cliente->nombre }}</td>
                        <td class="px-3 py-2">{{ $oc->obligacion->nombre }}</td>
                        <td class="px-3 py-2">{{ $oc->contador->name ?? '—' }}</td>

                        {{-- Badge estatus obligación --}}
                        <td class="px-3 py-2 text-center">
                            <span
                                class="px-2 py-1 rounded text-xs
                                @if ($oc->estatus == 'asignada') bg-gray-200 text-gray-800
                                @elseif($oc->estatus == 'en_progreso') bg-yellow-200 text-yellow-800
                                @elseif($oc->estatus == 'realizada') bg-green-200 text-green-800
                                @elseif($oc->estatus == 'finalizado') bg-emerald-200 text-emerald-800
                                @elseif($oc->estatus == 'rechazada') bg-red-200 text-red-800
                                @elseif($oc->estatus == 'reabierta') bg-purple-200 text-purple-800
                                @else bg-gray-300 text-gray-800 @endif">
                                {{ ucfirst(str_replace('_', ' ', $oc->estatus)) }}
                            </span>
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
                            <button wire:click="abrirSidebar({{ $oc->id }})"
                                class="bg-amber-600 text-white px-3 py-1 rounded hover:bg-amber-700 text-sm">
                                Revisar
                            </button>
                        </td>
                    </tr>

                    {{-- Subfila con tareas --}}
                    @if ($open)
                        <tr wire:key="tareas-{{ $oc->id }}" class="bg-gray-50 dark:bg-gray-800/40">
                            <td colspan="8" class="px-6 pb-4 pt-2">
                                @if ($oc->tareasAsignadas->isEmpty())
                                    <p class="text-sm italic text-gray-500 dark:text-gray-400">
                                        Esta obligación no tiene tareas ligadas.
                                    </p>
                                @else
                                    <ul class="list-disc ml-4 text-gray-700 dark:text-gray-300 text-sm space-y-1">
                                        @foreach ($oc->tareasAsignadas->sortBy(fn($t) => $t->tareaCatalogo->nombre ?? '') as $tarea)
                                            <li>
                                                {{ $tarea->tareaCatalogo->nombre ?? 'Sin nombre' }}
                                                —
                                                <span class="text-xs">
                                                    {{ ucfirst($tarea->estatus) }}
                                                    | {{ $tarea->contador->name ?? 'Sin contador' }}
                                                </span>
                                            </li>
                                        @endforeach
                                    </ul>
                                @endif
                            </td>
                        </tr>
                    @endif

                @empty
                    <tr>
                        <td colspan="8" class="py-6 text-center text-gray-500">
                            No hay obligaciones para validar.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $obligaciones->links() }}</div>

    {{-- SIDEBAR --}}
    <div x-cloak x-show="sidebar" x-transition.opacity class="fixed inset-0 bg-black/40 z-40 flex justify-end">
        <div class="flex-1" @click="$wire.cerrarSidebar()"></div>

        <div class="w-full max-w-xl bg-white dark:bg-gray-900 shadow-xl h-full border-l flex flex-col">

            {{-- Header --}}
            <div class="p-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
                <h3 class="text-lg font-semibold text-stone-700 dark:text-white">
                    Revisión y validación
                </h3>
                <button @click="$wire.cerrarSidebar()" class="text-gray-500 hover:text-black">✕</button>
            </div>

            {{-- Contenido --}}
            <div class="flex-1 overflow-y-auto p-4 space-y-6">

                @if ($obligacionSeleccionada)

                    {{-- OBLIGACIÓN --}}
                    <div class="border p-4 rounded shadow-sm dark:border-gray-700">
                        <h4 class="font-semibold mb-2">Obligación</h4>

                        <p><strong>Cliente:</strong> {{ $obligacionSeleccionada->cliente->nombre }}</p>
                        <p><strong>Obligación:</strong> {{ $obligacionSeleccionada->obligacion->nombre }}</p>
                        <p><strong>Contador:</strong> {{ $obligacionSeleccionada->contador->name ?? '—' }}</p>
                   
                        
                        <p class="mt-1">
                            <strong>Estatus:</strong>
                            <span
                                class="text-sm">{{ ucfirst(str_replace('_', ' ', $obligacionSeleccionada->estatus)) }}</span>
                        </p>

                        <p class="mt-2"><strong>Comentario:</strong> {{ $obligacionSeleccionada->comentario ?? '—' }}
                        </p>
                        {{-- Archivos de la obligación --}}
                        @if ($obligacionSeleccionada->archivos->count())
                            <div class="mt-3 space-y-1">
                                <strong>Archivos:</strong>

                                @foreach ($obligacionSeleccionada->archivos as $archivo)
                                    @if ($archivo->archivo)
                                        <a href="{{ Storage::disk('public')->url($archivo->archivo) }}" target="_blank"
                                            class="block text-blue-600 text-sm hover:underline">
                                            📄 {{ $archivo->nombre }}
                                        </a>
                                    @endif
                                @endforeach
                            </div>
                        @endif
                        <p>
                            <strong>Fecha vencimiento:</strong>
                            {{ 
                                $obligacionSeleccionada->fecha_finalizado
                                    ? \Carbon\Carbon::parse($obligacionSeleccionada->fecha_finalizado)->format('Y-m-d')
                                    : '—'
                            }}
                        </p>
                        {{-- Rechazo obligación --}}
                        @if (!$mostrarRechazoObligacion)
                            <button wire:click="$set('mostrarRechazoObligacion', true)"
                                class="mt-3 text-sm text-red-600 border border-red-400 px-3 py-1 rounded">
                                Rechazar obligación
                            </button>
                        @else
                            <div class="mt-3">
                                <textarea wire:model.defer="comentarioRechazoObligacion"
                                    class="w-full px-3 py-2 border rounded dark:bg-gray-700 dark:text-white" placeholder="Motivo del rechazo"></textarea>

                                <div class="mt-2 text-right space-x-2">
                                    <button wire:click="rechazarObligacion"
                                        class="bg-red-600 text-white px-4 py-1 rounded text-sm">
                                        Confirmar
                                    </button>
                                    <button wire:click="$set('mostrarRechazoObligacion', false)"
                                        class="px-3 py-1 border rounded text-sm">
                                        Cancelar
                                    </button>
                                </div>
                            </div>
                        @endif

                        {{-- FINALIZAR OBLIGACIÓN --}}
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
                                    class="bg-emerald-600 text-white px-4 py-2 rounded hover:bg-emerald-700 text-sm">
                                    Finalizar obligación
                                </button>
                            @else
                                <button disabled
                                    class="bg-gray-200 text-gray-500 px-4 py-2 rounded text-sm cursor-not-allowed">
                                    Finalizar obligación
                                </button>
                                <p class="text-xs text-gray-500 mt-2">
                                    * Para finalizar: obligación en <strong>realizada</strong> y todas las tareas en
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
                                Esta obligación no tiene tareas ligadas.
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
                                    <p><strong>Comentario:</strong> {{ $tarea->comentario ?? '—' }}</p>
                                    {{-- Archivos de la tarea --}}
                                    @if ($tarea->archivos->count())
                                        <div class="mt-2 space-y-1">
                                            <strong>Archivos:</strong>

                                            @foreach ($tarea->archivos as $archivo)
                                                @if ($archivo->archivo)
                                                    <a href="{{ Storage::disk('public')->url($archivo->archivo) }}"
                                                        target="_blank"
                                                        class="block text-blue-600 text-sm hover:underline">
                                                        📄 {{ $archivo->nombre }}
                                                    </a>
                                                @endif
                                            @endforeach
                                        </div>
                                    @endif


                                    {{-- Botón revisar tarea --}}
                                    @if ($tarea->estatus === 'realizada')
                                        <button wire:click="marcarTareaRevisada({{ $tarea->id }})"
                                            class="mt-2 bg-emerald-600 text-white px-3 py-1 rounded text-sm hover:bg-emerald-700">
                                            Marcar como revisada
                                        </button>
                                    @elseif($tarea->estatus === 'revisada')
                                        <span
                                            class="mt-2 inline-block text-xs px-2 py-1 rounded bg-emerald-100 text-emerald-800">
                                            Revisada
                                        </span>
                                    @endif

                                    {{-- Rechazo tarea --}}
                                    @if (!($mostrarRechazoTarea[$tarea->id] ?? false))
                                        <button wire:click="$set('mostrarRechazoTarea.{{ $tarea->id }}', true)"
                                            class="mt-2 text-sm text-red-600 border border-red-400 px-3 py-1 rounded">
                                            Rechazar tarea
                                        </button>
                                    @else
                                        <div class="mt-2">
                                            <textarea wire:model.defer="comentarioRechazoTarea.{{ $tarea->id }}"
                                                class="w-full px-3 py-2 border rounded dark:bg-gray-700 dark:text-white" placeholder="Motivo del rechazo"></textarea>

                                            <div class="mt-2 text-right space-x-2">
                                                <button wire:click="rechazarTarea({{ $tarea->id }})"
                                                    class="bg-red-600 text-white px-4 py-1 rounded text-sm">
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
    <x-notification />

</div>
