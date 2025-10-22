<div class="p-6 bg-white dark:bg-gray-900 text-gray-900 dark:text-white rounded-lg shadow space-y-4">

    <h2 class="text-xl font-bold text-stone-600">Mis obligaciones asignadas</h2>

    {{-- Filtros --}}
    <div class="flex flex-wrap items-center gap-2">
        <select wire:model.live="ejercicioSeleccionado"
            class="px-3 py-2 border rounded dark:bg-gray-700 dark:text-white focus:outline-amber-600">
            @foreach ($ejercicios as $ej)
                <option value="{{ $ej }}">{{ $ej }}</option>
            @endforeach
        </select>

        <select wire:model.live="mesSeleccionado"
            class="px-3 py-2 border rounded dark:bg-gray-700 dark:text-white focus:outline-amber-600">
            @foreach ($meses as $num => $nombre)
                <option value="{{ $num }}">{{ $nombre }}</option>
            @endforeach
        </select>

        <input type="text" placeholder="Buscar cliente u obligación"
            wire:model.live="buscar"
            class="w-64 px-3 py-2 border rounded dark:bg-gray-700 dark:text-white focus:outline-amber-600">

        <select wire:model.live="estatus"
            class="px-3 py-2 border rounded dark:bg-gray-700 dark:text-white focus:outline-amber-600">
            <option value="">Estatus (todos)</option>
            <option value="asignada">Asignada</option>
            <option value="en_progreso">En progreso</option>
            <option value="declaracion_realizada">Declaración realizada</option>
            <option value="enviada_cliente">Enviada a cliente</option>
            <option value="finalizado">Finalizado</option>
        </select>
    </div>

    {{-- Tabla --}}
    <div class="overflow-x-auto rounded shadow mt-4">
        <table class="min-w-full divide-y divide-gray-300 dark:divide-gray-700 text-sm">
            <thead class="bg-stone-100 dark:bg-stone-900">
                <tr>
                    <th class="px-4 py-2 text-left">Cliente</th>
                    <th class="px-4 py-2 text-left">Obligación</th>
                    <th class="px-4 py-2 text-left">Periodicidad</th>
                    <th class="px-4 py-2 text-left">Estatus</th>
                    <th class="px-4 py-2 text-left">Vence</th>
                    <th class="px-4 py-2 text-left">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                @forelse ($obligaciones as $item)
                    <tr>
                        <td class="px-4 py-2">{{ $item->cliente->nombre ?? '—' }}</td>
                        <td class="px-4 py-2">{{ $item->obligacion->nombre ?? '—' }}</td>
                        <td class="px-4 py-2">{{ ucfirst($item->obligacion->periodicidad ?? '—') }}</td>
                        <td class="px-4 py-2">
                            <span class="text-xs px-2 py-1 rounded text-white 
                                {{ match($item->estatus) {
                                    'asignada' => 'bg-stone-600',
                                    'en_progreso' => 'bg-amber-600',
                                    'declaracion_realizada' => 'bg-green-600',
                                    'finalizado' => 'bg-blue-600',
                                    default => 'bg-gray-500',
                                } }}">
                                {{ ucfirst(str_replace('_', ' ', $item->estatus)) }}
                            </span>
                        </td>
                        <td class="px-4 py-2">
                            {{ $item->fecha_vencimiento ? \Carbon\Carbon::parse($item->fecha_vencimiento)->format('d/m/Y') : '—' }}
                        </td>
                        <td class="px-4 py-2 space-x-2">
                            @if ($item->estatus === 'asignada')
                                <button wire:click="iniciarObligacion({{ $item->id }})"
                                    class="bg-amber-600 text-white px-3 py-1 rounded hover:bg-amber-700">
                                    Iniciar
                                </button>
                            @elseif ($item->estatus === 'en_progreso')
                                <button wire:click="openResultModal({{ $item->id }})"
                                    class="bg-green-600 text-white px-3 py-1 rounded hover:bg-green-700">
                                    Subir resultados
                                </button>
                            @elseif ($item->estatus === 'declaracion_realizada')
                                <button wire:click="openResultModal({{ $item->id }})"
                                    class="bg-blue-600 text-white px-3 py-1 rounded hover:bg-blue-700">
                                    Editar resultados
                                </button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-4 text-center text-gray-600 dark:text-gray-300">
                            No hay obligaciones para mostrar.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Modal de resultados --}}
    @if($openModal)
        <div class="fixed inset-0 flex items-center justify-center bg-stone-800/70 z-50 p-4">
            <div class="bg-white dark:bg-gray-900 p-6 rounded-lg shadow-lg w-full max-w-lg">
                <h3 class="text-lg font-bold mb-4 text-stone-700">Subir resultados</h3>

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm mb-1">Archivo (PDF, ZIP, JPG, PNG)</label>
                        <input type="file" wire:model="archivo"
                            class="w-full border rounded dark:bg-gray-700 dark:text-white focus:outline-amber-600 focus:outline" />
                        @error('archivo') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm mb-1">Número de operación</label>
                        <input type="text" wire:model.defer="numero_operacion"
                            class="w-full px-3 py-2 border rounded dark:bg-gray-700 dark:text-white focus:outline-amber-600 focus:outline" />
                        @error('numero_operacion') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm mb-1">Fecha de vencimiento</label>
                        <input type="date" wire:model.defer="fecha_vencimiento"
                            class="w-full px-3 py-2 border rounded dark:bg-gray-700 dark:text-white focus:outline-amber-600 focus:outline" />
                        @error('fecha_vencimiento') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="flex justify-end space-x-2 mt-6">
                    <button wire:click="$set('openModal', false)"
                        class="px-4 py-2 bg-gray-300 dark:bg-gray-700 text-black dark:text-white rounded hover:bg-gray-400">
                        Cancelar
                    </button>
                    <button wire:click="saveResult"
                        class="px-4 py-2 bg-amber-600 text-white rounded hover:bg-amber-700">
                        Guardar
                    </button>
                </div>
            </div>
        </div>
    @endif

    <x-spinner target="saveResult" />
</div>
