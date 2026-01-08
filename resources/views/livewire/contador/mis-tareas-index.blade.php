<div>

    <div class="p-6 bg-white dark:bg-gray-900 text-gray-900 dark:text-white rounded-lg shadow space-y-4">
        <h2 class="text-xl font-bold text-stone-600">Mis tareas asignadas</h2>

        {{-- =========================
            FILTROS
        ========================== --}}
        <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">

            <div class="flex flex-wrap items-center gap-2">

                {{-- Filtro ejercicio (solo años con datos) --}}
                <select wire:model.live="ejercicio"
                    class="px-3 py-2 border rounded dark:bg-gray-700 dark:text-white focus:outline-amber-600 focus:outline">
                    <option value="">Ejercicio (todos)</option>
                    @foreach ($ejerciciosDisponibles as $anio)
                        <option value="{{ $anio }}">{{ $anio }}</option>
                    @endforeach
                </select>

                {{-- Filtro mes (solo meses con datos para el año seleccionado) --}}
                <select wire:model.live="mes"
                    class="px-3 py-2 border rounded dark:bg-gray-700 dark:text-white focus:outline-amber-600 focus:outline"
                    @disabled(empty($mesesDisponibles))>
                    <option value="">Mes (todos)</option>
                    @foreach ($mesesDisponibles as $m)
                        @php
                            $nombresMes = [
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
                            ];
                        @endphp
                        <option value="{{ $m }}">{{ $nombresMes[(int) $m] ?? $m }}</option>
                    @endforeach
                </select>


                {{-- Estatus --}}
                <select wire:model.live="estatus"
                    class="px-3 py-2 border rounded dark:bg-gray-700 dark:text-white focus:outline-amber-600 focus:outline">
                    <option value="">Estatus (todos)</option>
                    <option value="asignada">Asignada</option>
                    <option value="en_progreso">En progreso</option>
                    <option value="realizada">Realizada</option>
                    <option value="revisada">Revisada</option>
                    <option value="rechazada">Rechazada</option>
                    <option value="cancelada">Cancelada</option>
                    <option value="cerrada">Cerrada</option>
                    <option value="reabierta">Reabierta</option>
                </select>

                {{-- Buscar --}}
                <input type="text" placeholder="Buscar (cliente / tarea / obligación)" wire:model.live="buscar"
                    class="w-72 px-3 py-2 border rounded dark:bg-gray-700 dark:text-white focus:outline-amber-600 focus:outline">
            </div>
        </div>

        {{-- =========================
            TABLA
        ========================== --}}
        <div class="overflow-x-auto rounded shadow">
            <table class="min-w-full divide-y divide-gray-300 dark:divide-gray-700 text-sm">
                <thead class="bg-stone-100 dark:bg-stone-900">
                    <tr>
                        <th class="px-4 py-2 text-left">Cliente</th>
                        <th class="px-4 py-2 text-left">Tarea</th>
                        <th class="px-4 py-2 text-left">Obligación</th>
                        <th class="px-4 py-2 text-left">Vence</th>
                        <th class="px-4 py-2 text-left">Estatus</th>
                        <th class="px-4 py-2 text-left">Tiempo estimado</th>
                        <th class="px-4 py-2 text-left">Duración real</th>
                        <th class="px-4 py-2 text-left">Acciones</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                    @forelse ($tareas as $t)
                        @php
                            $vence = $t->fecha_limite ? \Carbon\Carbon::parse($t->fecha_limite) : null;

                            // Chip color por estatus
                            $chip = match ($t->estatus) {
                                'en_progreso' => 'bg-amber-600',
                                'realizada' => 'bg-green-600',
                                'revisada' => 'bg-blue-600',
                                'rechazada' => 'bg-red-600',
                                'cancelada' => 'bg-gray-600',
                                'cerrada' => 'bg-stone-800',
                                'reabierta' => 'bg-purple-600',
                                default => 'bg-stone-600',
                            };

                            // Color de vencimiento
                            $venceClass = $vence
                                ? ($vence->isPast()
                                    ? 'text-red-600'
                                    : ($vence->isToday()
                                        ? 'text-amber-600'
                                        : 'text-gray-700 dark:text-gray-300'))
                                : 'text-gray-500';
                        @endphp

                        <tr>
                            <td class="px-4 py-2">
                                {{ $t->cliente->nombre ?? ($t->cliente->razon_social ?? '—') }}
                            </td>

                            <td class="px-4 py-2">
                                {{ $t->tareaCatalogo?->nombre ?? '—' }}
                            </td>

                            <td class="px-4 py-2">
                                {{ $t->obligacionClienteContador?->obligacion?->nombre ?? 'Sin obligación' }}
                            </td>

                            <td class="px-4 py-2 {{ $venceClass }}">
                                {{ $vence ? $vence->format('d/m/Y') : '—' }}
                            </td>

                            <td class="px-4 py-2">
                                <span class="text-xs px-2 py-1 rounded text-white {{ $chip }}">
                                    {{ str_replace('_', ' ', ucfirst($t->estatus)) }}
                                </span>
                            </td>

                            <td class="px-4 py-2">
                                {{ $t->tiempo_estimado ? $t->tiempo_estimado . ' min' : '—' }}
                            </td>

                            <td class="px-4 py-2">
                                {{ $t->duracion_minutos ? $t->duracion_minutos . ' min' : '—' }}
                            </td>

                            <td class="px-4 py-2 space-x-2">

                                {{-- ASIGNADA --}}
                                @if ($t->estatus === 'asignada')
                                    <button wire:click="iniciar({{ $t->id }})"
                                        class="px-3 py-1 bg-stone-600 text-white rounded hover:bg-stone-700">
                                        Iniciar
                                    </button>
                                @endif

                                {{-- EN PROGRESO --}}
                                @if ($t->estatus === 'en_progreso')
                                    <button wire:click="terminar({{ $t->id }})"
                                        class="px-3 py-1 bg-amber-600 text-white rounded hover:bg-amber-700">
                                        Terminar
                                    </button>
                                @endif


                                {{-- RECHAZADA --}}
                                @if ($t->estatus === 'rechazada')
                                    <button wire:click="verRechazo({{ $t->id }})"
                                        class="px-3 py-1 bg-red-600 text-white rounded hover:bg-red-700">
                                        Ver rechazo
                                    </button>

                                
                                @endif

                            </td>

                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-6 text-center text-sm text-gray-600 dark:text-gray-300">
                                No hay tareas para mostrar.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div>{{ $tareas->links() }}</div>

        {{-- =========================
            MODAL SEGUIMIENTO
        ========================== --}}
        @if ($openModal && $tareaSeleccionada)
        <div class="fixed inset-0 flex items-center justify-center bg-stone-600 bg-opacity-50 z-50">
            <div class="bg-white dark:bg-gray-900 p-6 rounded-lg shadow-lg w-full max-w-2xl">
    
                <h4 class="text-lg font-bold mb-4 text-stone-600">
                    Finalizar tarea
                </h4>
    
                {{-- Comentario si está en estatus rechazado --}}
                @if ($tareaSeleccionada->estatus === 'rechazada')
                    <div class="mb-4">
                        <label class="block text-sm mb-1 text-stone-600">Comentario del rechazo</label>
                        <div class="bg-gray-100 dark:bg-gray-800 px-3 py-2 rounded text-gray-800 dark:text-white">
                            {{ $tareaSeleccionada->comentario ?? '—' }}
                        </div>
                    </div>
                @endif
    
                {{-- Archivos y comentario solo si está en progreso --}}
                @if ($tareaSeleccionada->estatus === 'en_progreso')
                    {{-- COMPONENTE DE ARCHIVOS --}}
                    @livewire('shared.archivos-adjuntos-crud', ['modelo' => $tareaSeleccionada], key('archivos-tarea-' . $tareaSeleccionada->id))
    
                    {{-- COMENTARIO --}}
                    <div class="mt-4">
                        <label class="block text-sm mb-1 text-stone-600">Comentario</label>
                        <textarea wire:model.defer="comentario" rows="3"
                            placeholder="Describe el resultado o notas adicionales..."
                            class="w-full px-3 py-2 border rounded dark:bg-gray-700 dark:text-white focus:outline-amber-600 focus:outline">
                        </textarea>
                    </div>
                @endif
    
                {{-- ACCIONES --}}
                <div class="flex justify-end space-x-2 mt-6">
                    {{-- Cerrar modal --}}
                    <button wire:click="$set('openModal', false)"
                        class="bg-gray-300 dark:bg-gray-600 px-4 py-2 rounded text-black dark:text-white hover:bg-gray-400">
                        Cerrar
                    </button>
    
                    {{-- Botón "Realizar" si está en rechazado --}}
                    @if ($tareaSeleccionada->estatus === 'rechazada')
                        <button wire:click="corregir({{ $tareaSeleccionada->id }})"
                            class="bg-amber-600 hover:bg-amber-700 px-4 py-2 rounded text-white">
                            Corregir
                        </button>
                    @endif
    
                    {{-- Botón "Finalizar" si ya está en progreso --}}
                    @if ($tareaSeleccionada->estatus === 'en_progreso')
                        <button wire:click="cerrarTarea"
                            class="bg-green-600 hover:bg-green-700 px-4 py-2 rounded text-white">
                            Marcar como realizada
                        </button>
                    @endif
                </div>
            </div>
        </div>
    @endif
    

        <x-spinner target="guardarSeguimiento" />

    </div>
</div>
