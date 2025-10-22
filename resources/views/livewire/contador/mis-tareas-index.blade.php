<div>
    <h2 class="text-xl font-bold text-stone-600">Mis tareas asignadas </h2>

    <div class="p-6 bg-white dark:bg-gray-900 text-gray-900 dark:text-white rounded-lg shadow space-y-4">

        <div class="flex items-center justify-between">
            <select wire:model.live="periodoSeleccionado"
                class="px-3 py-2 border rounded dark:bg-gray-700 dark:text-white focus:outline-amber-600 focus:outline">
                @foreach ($periodosDisponibles as $p)
                    <option value="{{ $p }}">{{ $p }}</option>
                @endforeach
            </select>
            <div class="flex items-center gap-2">

                <input type="text" placeholder="Buscar (cliente/obligación/tarea)" wire:model.live="buscar"
                    class="w-64 px-3 py-2 border rounded dark:bg-gray-700 dark:text-white focus:outline-amber-600 focus:outline">

                <select wire:model.live="estatus"
                    class="px-3 py-2 border rounded dark:bg-gray-700 dark:text-white focus:outline-amber-600 focus:outline">
                    <option value="">Estatus (todos)</option>
                    <option value="asignada">Asignada</option>
                    <option value="iniciando">Iniciando</option>
                    <option value="en_progreso">En progreso</option>
                    <option value="realizada">realizada</option>
                    <option value="revisada">Revisada</option>
                    <option value="rechazada">Rechazada</option>
                </select>

                {{--  <input type="date" wire:model.live="vence_desde"
                class="px-3 py-2 border rounded dark:bg-gray-700 dark:text-white focus:outline-amber-600 focus:outline"
                title="Vence desde">

            <input type="date" wire:model.live="vence_hasta"
                class="px-3 py-2 border rounded dark:bg-gray-700 dark:text-white focus:outline-amber-600 focus:outline"
                title="Vence hasta"> --}}
            </div>
        </div>

        <div class="overflow-x-auto rounded shadow">
            <table class="min-w-full divide-y divide-gray-300 dark:divide-gray-700">
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
                            $chip = match ($t->estatus) {
                                'en_progreso' => 'bg-amber-600',
                                'realizada' => 'bg-green-600',
                                'rechazada' => 'bg-red-600',
                                default => 'bg-stone-600',
                            };
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
                                {{-- OCC->obligacion (catálogo) --}}
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
                                {{-- accessor getDuracionMinutosAttribute() --}}
                                {{ $t->duracion_minutos ? $t->duracion_minutos . ' min' : '—' }}
                            </td>
                            <td class="px-4 py-2 space-x-2">
                                @if ($t->estatus === 'asignada')
                                    <button wire:click="iniciar({{ $t->id }})"
                                        class="px-3 py-1 bg-stone-600 text-white rounded hover:bg-stone-700">Iniciar</button>
                                @elseif ($t->estatus === 'en_progreso')
                                    <button wire:click="abrirModalTerminar({{ $t->id }})"
                                        class="px-3 py-1 bg-green-600 text-white rounded hover:bg-green-700">Resultados</button>
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

        {{-- Modal terminar --}}
        @if ($openModal)
            <div class="fixed inset-0 flex items-center justify-center bg-stone-600 bg-opacity-50 z-50">
                <div class="bg-white dark:bg-gray-900 p-6 rounded-lg shadow-lg w-full max-w-lg">
                    <h4 class="text-lg font-bold mb-4 text-stone-600">Finalizar tarea</h4>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm mb-1">Comentario (opcional)</label>
                            <textarea wire:model.defer="comentario" rows="3"
                                class="w-full px-3 py-2 border rounded dark:bg-gray-700 dark:text-white focus:outline-amber-600 focus:outline"></textarea>
                            @error('comentario')
                                <span class="text-red-600 text-sm">{{ $message }}</span>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm mb-1">Archivo (opcional)</label>
                            <input type="file" wire:model="archivo"
                                class="w-full px-3 py-2 border rounded dark:bg-gray-700 dark:text-white focus:outline-amber-600 focus:outline">
                            @error('archivo')
                                <span class="text-red-600 text-sm">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="flex justify-end space-x-2 mt-6">
                        <button wire:click="$set('openModal', false)"
                            class="bg-gray-300 dark:bg-gray-600 px-4 py-2 rounded text-black dark:text-white hover:bg-gray-400">
                            Cancelar
                        </button>
                        <button wire:click="terminar"
                            class="bg-amber-600 hover:bg-amber-700 px-4 py-2 rounded text-white">
                            Terminar
                        </button>
                    </div>
                </div>
            </div>
        @endif
        <x-spinner target="terminar" />

    </div>
</div>
