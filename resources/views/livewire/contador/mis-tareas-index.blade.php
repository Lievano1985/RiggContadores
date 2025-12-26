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
                                {{-- Acción rápida: iniciar --}}
                                @if ($t->estatus === 'asignada')
                                    <button wire:click="iniciar({{ $t->id }})"
                                        class="px-3 py-1 bg-stone-600 text-white rounded hover:bg-stone-700">
                                        Iniciar
                                    </button>
                                @endif

                                {{-- Seguimiento (siempre disponible) --}}
                                <button wire:click="abrirSeguimiento({{ $t->id }})"
                                    class="px-3 py-1 bg-amber-600 text-white rounded hover:bg-amber-700">
                                    Seguimiento
                                </button>
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
        @if ($openModal)
            <div class="fixed inset-0 flex items-center justify-center bg-stone-600 bg-opacity-50 z-50">
                <div class="bg-white dark:bg-gray-900 p-6 rounded-lg shadow-lg w-full max-w-lg">
                    <h4 class="text-lg font-bold mb-4 text-stone-600">Seguimiento de tarea</h4>

                    <div class="space-y-4">

                        {{-- Cambiar estatus --}}
                        <div>
                            <label class="block text-sm mb-1">Cambiar estatus</label>
                            <select wire:model.defer="nuevoEstatus"
                                class="w-full px-3 py-2 border rounded dark:bg-gray-700 dark:text-white focus:outline-amber-600 focus:outline">
                                <option value="">(Sin cambio)</option>
                                <option value="asignada">Asignada</option>
                                <option value="en_progreso">En progreso</option>
                                <option value="realizada">Realizada</option>
                                <option value="revisada">Revisada</option>
                                <option value="rechazada">Rechazada</option>
                                <option value="cancelada">Cancelada</option>
                                <option value="cerrada">Cerrada</option>
                                <option value="reabierta">Reabierta</option>
                            </select>
                            @error('nuevoEstatus')
                                <span class="text-red-600 text-sm">{{ $message }}</span>
                            @enderror
                        </div>

                        {{-- Comentario --}}
                        <div>
                            <label class="block text-sm mb-1">Comentario (opcional)</label>
                            <textarea wire:model.defer="comentario" rows="3"
                                class="w-full px-3 py-2 border rounded dark:bg-gray-700 dark:text-white focus:outline-amber-600 focus:outline"></textarea>
                            @error('comentario')
                                <span class="text-red-600 text-sm">{{ $message }}</span>
                            @enderror
                        </div>

                        {{-- Archivo --}}
                        {{-- Archivo --}}
                        <div>
                            <label class="block text-sm mb-1">Archivo (opcional)</label>
                            <input type="file" wire:model="archivo"
                                class="w-full px-3 py-2 border rounded dark:bg-gray-700 dark:text-white focus:outline-amber-600 focus:outline">
                            @error('archivo')
                                <span class="text-red-600 text-sm">{{ $message }}</span>
                            @enderror

                            @if ($tareaId && ($tarea = \App\Models\TareaAsignada::find($tareaId)) && $tarea->archivo)
                                <div class="mt-2 space-y-1 text-sm">
                                    @if ($tarea->archivo)
                                        <a href="{{ Storage::disk('public')->url($tarea->archivo) }}"
                                            class="text-blue-600 hover:underline" target="_blank">📄 Ver archivo
                                            (Storage)</a>
                                    @endif

                                    @if ($tarea->archivo_drive_url)
                                        <a href="{{ $tarea->archivo_drive_url }}"
                                            class="text-green-600 hover:underline" target="_blank">📁 Ver en Google
                                            Drive</a>
                                    @endif
                                </div>
                            @endif
                        </div>


                        <div class="flex justify-end space-x-2 mt-6">
                            <button wire:click="$set('openModal', false)"
                                class="bg-gray-300 dark:bg-gray-600 px-4 py-2 rounded text-black dark:text-white hover:bg-gray-400">
                                Cancelar
                            </button>

                            <button wire:click="guardarSeguimiento"
                                class="bg-amber-600 hover:bg-amber-700 px-4 py-2 rounded text-white">
                                Guardar
                            </button>
                        </div>
                    </div>
                </div>
        @endif

        <x-spinner target="guardarSeguimiento" />
    </div>
</div>
