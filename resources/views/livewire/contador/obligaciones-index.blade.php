<div class="p-6 bg-white dark:bg-gray-900 text-gray-900 dark:text-white rounded-lg shadow space-y-4">

    <h2 class="text-xl font-bold text-stone-600">Mis obligaciones asignadas</h2>

    {{-- =========================
        FILTROS
    ========================== --}}
    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
        <div class="flex flex-wrap items-center gap-2">

            <select wire:model.live="ejercicioSeleccionado"
                class="px-3 py-2 border rounded dark:bg-gray-700 dark:text-white focus:outline-amber-600 focus:outline">
                <option value="">Selecciona...</option> {{-- OPCION INICIAL --}}
                <option value="">Ejercicio (todos)</option>
                @foreach ($ejerciciosDisponibles as $ej)
                    <option value="{{ $ej }}">{{ $ej }}</option>
                @endforeach
            </select>

            <select wire:model.live="mesSeleccionado"
                class="px-3 py-2 border rounded dark:bg-gray-700 dark:text-white focus:outline-amber-600 focus:outline">
                <option value="">Selecciona...</option> {{-- OPCION INICIAL --}}
                <option value="">Mes (todos)</option>
                @foreach ($mesesDisponibles as $num => $texto)
                    <option value="{{ $num }}">{{ $texto }}</option>
                @endforeach
            </select>


            {{-- Estatus --}}
            <select wire:model.live="estatus"
                class="px-3 py-2 border rounded dark:bg-gray-700 dark:text-white focus:outline-amber-600 focus:outline">
                <option value="">Estatus (todos)</option>
                <option value="asignada">Asignada</option>
                <option value="en_progreso">En progreso</option>
                <option value="realizada">Realizada</option>
                <option value="enviada_cliente">Enviada a cliente</option>
                <option value="respuesta_cliente">Respuesta cliente</option>
                <option value="respuesta_revisada">Respuesta revisada</option>
                <option value="finalizado">Finalizado</option>
                <option value="reabierta">Reabierta</option>
            </select>
            {{-- Filtro Cliente --}}
            <select wire:model.live="cliente_id"
                class="px-3 py-2 border rounded dark:bg-gray-700 dark:text-white focus:outline-amber-600 focus:outline">

                <option value="">Cliente (todos)</option>

                @foreach ($clientesDisponibles as $c)
                    <option value="{{ $c['id'] }}">
                        {{ $c['nombre'] }}
                    </option>
                @endforeach
            </select>

            {{-- Buscar --}}
            <input type="text" placeholder="Buscar (obligacion)" wire:model.live="buscar"
                class=" px-3 py-2 border rounded dark:bg-gray-700 dark:text-white focus:outline-amber-600 focus:outline">
        </div>
    </div>

    {{-- =========================
        TABLA
    ========================== --}}
    <div class="bg-white dark:bg-gray-900 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-x-auto mt-2">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                                    <thead class="bg-stone-100 dark:bg-stone-900">
                <tr>
                    <x-sortable-th field="cliente" label="Cliente" :sort-field="$sortField" :sort-direction="$sortDirection" />
                    <x-sortable-th field="ejercicio" label="Ejercicio" :sort-field="$sortField" :sort-direction="$sortDirection" />
                    <x-sortable-th field="obligacion" label="Obligacion" :sort-field="$sortField" :sort-direction="$sortDirection" />
                    <th class="px-4 py-2 text-left text-xs font-semibold">Periodicidad</th>
                    <x-sortable-th field="estatus" label="Estatus" :sort-field="$sortField" :sort-direction="$sortDirection" />
                    <x-sortable-th field="fecha_vencimiento" label="Vence" :sort-field="$sortField" :sort-direction="$sortDirection" />
                    <th class="px-4 py-2 text-left text-xs font-semibold">Acciones</th>
                </tr>
            </thead>

            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                @forelse ($obligaciones as $item)
                    @php
                        $vencida =
                            $item->fecha_vencimiento &&
                            \Carbon\Carbon::parse($item->fecha_vencimiento)->isPast() &&
                            $item->estatus !== 'finalizado';
                    @endphp


                    <tr @class([
                        'transition-all duration-400',
                    
                        // vencida SOLO si no esta resaltada
                        $item->fecha_vencimiento < now() && $highlightId !== $item->id
                            ? 'bg-red-50 dark:bg-red-900 dark:text-red-100'
                            : '',
                    
                        // highlight flash (PRIORIDAD)
                        'bg-amber-100 dark:bg-amber-900' => $highlightId === $item->id,
                    ])>

                        <td class="px-4 py-2">{{ $item->cliente->nombre ?? ($item->cliente->razon_social ?? '-') }}</td>
                        <td class="px-4 py-2 whitespace-nowrap">
                            {{ $item->ejercicio }} - {{ str_pad($item->mes, 2, '0', STR_PAD_LEFT) }}
                        </td>
                        <td class="px-4 py-2">{{ $item->obligacion->nombre ?? '-' }}</td>
                        <td class="px-4 py-2">{{ ucfirst($item->obligacion->periodicidad ?? '-') }}</td>

                        <td class="px-4 py-2">
                            <x-status-badge :status="$item->estatus" />
                        </td>

                        <td class="px-4 py-2 whitespace-nowrap">
                            {{ $item->fecha_vencimiento ? \Carbon\Carbon::parse($item->fecha_vencimiento)->format('Y-m-d') : '-' }}
                        </td>

                        <td class="px-4 py-2">
                            <div class="flex items-center gap-1">

                            {{-- ASIGNADA --}}
                            @if ($item->estatus === 'asignada')
                                <x-action-icon icon="play" label="Iniciar" variant="primary"
                                    wire:click="iniciarObligacion({{ $item->id }})" />
                            @endif

                            {{-- EN PROGRESO / REALIZADA --}}
                            @if (in_array($item->estatus, ['en_progreso', 'realizada'], true))
                                <x-action-icon icon="upload"
                                    :label="$item->estatus === 'realizada' ? 'Editar resultados' : 'Subir resultados'"
                                    variant="success" wire:click="openResultModal({{ $item->id }})" />
                            @endif

                            {{-- RECHAZADA --}}
                            @if ($item->estatus === 'rechazada')
                                <x-action-icon icon="eye" label="Ver rechazo" variant="danger"
                                    wire:click="verRechazoObligacion({{ $item->id }})" />
                            @endif

                            </div>
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

    @include('livewire.shared.pagination-controls', ['paginator' => $obligaciones])

    @if ($openModal)
        <div class="fixed inset-0 flex items-center justify-center bg-stone-800/70 z-50 p-4">
            <div class="bg-white dark:bg-gray-900 p-6 rounded-lg shadow-lg w-full max-w-lg">
                <div class="mb-4">
                    <h3 class="text-lg font-bold text-stone-700 dark:text-white">
                        {{ $modalCliente }}
                    </h3>

                    <p class="text-sm text-gray-600 dark:text-gray-300">
                        Resultados de obligacion - {{ $modalObligacion }}
                    </p>
                </div>


                <div class="space-y-4">


                    {{-- ============================= --}}
                    {{-- SOLO SI NO ES RECHAZO --}}
                    {{-- ============================= --}}
                    @if (!$soloLectura)

                        {{-- Archivos multiples --}}
                        @if ($selectedId)
                            <div class="mt-4 border-t pt-4">
                                <livewire:shared.archivos-adjuntos-crud :modelo="\App\Models\ObligacionClienteContador::find($selectedId)"
                                    wire:key="archivos-obligacion-{{ $selectedId }}" />
                            </div>
                        @endif

                        {{-- Numero de operacion --}}
                        <div>
                            <label class="block text-sm mb-1">
                                Numero de operacion
                            </label>

                            <input type="text" wire:model.defer="numero_operacion"
                                class="w-full px-3 py-2 border rounded
                                   dark:bg-gray-700 dark:text-white
                                   focus:outline-amber-600 focus:outline" />

                            @error('numero_operacion')
                                <span class="text-red-600 text-sm">{{ $message }}</span>
                            @enderror
                        </div>

                        {{-- Fecha oficial --}}
                        <div>
                            <label class="block text-sm mb-1">
                                Fecha oficial del documento / linea de captura
                            </label>

                            <input type="date" wire:model.defer="fecha_finalizado"
                                class="w-full px-3 py-2 border rounded
                                   dark:bg-gray-700 dark:text-white
                                   focus:outline-amber-600 focus:outline" />

                            @error('fecha_finalizado')
                                <span class="text-red-600 text-sm">{{ $message }}</span>
                            @enderror
                        </div>

                    @endif

                    {{-- ============================= --}}
                    {{-- COMENTARIO (UNICO) --}}
                    {{-- ============================= --}}
                    <div>
                        <label class="block text-sm mb-1">
                            Comentario
                        </label>

                        <textarea wire:model.defer="comentario" @if ($soloLectura) readonly @endif
                            class="w-full px-3 py-2 border rounded
                               dark:bg-gray-700 dark:text-white
                               focus:outline-amber-600 focus:outline">
                    </textarea>
                    </div>
                </div>

                {{-- ============================= --}}
                {{-- BOTONES --}}
                {{-- ============================= --}}
                <div class="flex justify-end space-x-2 mt-6">

                    {{-- Cerrar --}}
                    <button wire:click="$set('openModal', false)"
                        class="px-4 py-2 bg-amber-600 text-white rounded hover:bg-amber-700">
                        Cerrar
                    </button>

                    {{-- Rechazo --}}
                    @if ($soloLectura)
                        <button wire:click="corregirObligacion({{ $selectedId }})"
                            class="px-4 py-2 bg-amber-600 text-white rounded hover:bg-amber-700">
                            Corregir
                        </button>
                    @else
                        <button wire:click="saveResult" @click="window.dispatchEvent(new CustomEvent('spinner-on'))"
                            class="px-4 py-2 bg-amber-600 text-white rounded hover:bg-amber-700">
                            Guardar
                        </button>
                    @endif

                </div>

            </div>
        </div>
    @endif



    <script>
        window.addEventListener('limpiar-highlight', () => {
            setTimeout(() => {
                Livewire.find(@this.__instance.id).call('limpiarHighlight')
            }, 5000)
        })
    </script>

</div>
