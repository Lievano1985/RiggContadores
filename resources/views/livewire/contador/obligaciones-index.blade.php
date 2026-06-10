<div class="p-6 bg-white dark:bg-gray-900 text-gray-900 dark:text-white rounded-lg shadow space-y-4">

    <h2 class="text-xl font-bold text-stone-600 dark:text-white">Mis obligaciones asignadas</h2>

    {{-- =========================
        FILTROS
    ========================== --}}
    <div class="space-y-3">
        <div class="grid grid-cols-1 gap-2 md:grid-cols-2 xl:grid-cols-5">

            <select wire:model.live="ejercicioSeleccionado"
                class="w-full min-w-0 px-3 py-2 border rounded dark:bg-gray-700 dark:text-white focus:border-amber-600 focus:ring focus:ring-amber-500/40 focus:outline-none">
                <option value="">Selecciona...</option> {{-- OPCION INICIAL --}}
                <option value="">Ejercicio (todos)</option>
                @foreach ($ejerciciosDisponibles as $ej)
                    <option value="{{ $ej }}">{{ $ej }}</option>
                @endforeach
            </select>

            <select wire:model.live="mesSeleccionado"
                class="w-full min-w-0 px-3 py-2 border rounded dark:bg-gray-700 dark:text-white focus:border-amber-600 focus:ring focus:ring-amber-500/40 focus:outline-none">
                <option value="">Selecciona...</option> {{-- OPCION INICIAL --}}
                <option value="">Mes (todos)</option>
                @foreach ($mesesDisponibles as $num => $texto)
                    <option value="{{ $num }}">{{ $texto }}</option>
                @endforeach
            </select>


            {{-- Estatus --}}
            <select wire:model.live="estatus"
                class="w-full min-w-0 px-3 py-2 border rounded dark:bg-gray-700 dark:text-white focus:border-amber-600 focus:ring focus:ring-amber-500/40 focus:outline-none">
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
                class="w-full min-w-0 px-3 py-2 border rounded dark:bg-gray-700 dark:text-white focus:border-amber-600 focus:ring focus:ring-amber-500/40 focus:outline-none">

                <option value="">Cliente (todos)</option>

                @foreach ($clientesDisponibles as $c)
                    <option value="{{ $c['id'] }}">
                        {{ $c['nombre'] }}
                    </option>
                    @endforeach
                </select>

            <select wire:model.live="filtroObligacion"
                class="w-full min-w-0 px-3 py-2 border rounded dark:bg-gray-700 dark:text-white focus:border-amber-600 focus:ring focus:ring-amber-500/40 focus:outline-none">
                <option value="">Obligacion (todas)</option>
                @foreach ($this->obligacionesDisponiblesFiltro as $obligacion)
                    <option value="{{ $obligacion['id'] }}">{{ $obligacion['nombre'] }}</option>
                @endforeach
            </select>
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

                            {{-- EN PROGRESO / REABIERTA --}}
                            @if (in_array($item->estatus, ['en_progreso', 'reabierta'], true))
                                <x-action-icon icon="upload"
                                    :label="$item->estatus === 'reabierta' ? 'Corregir resultado' : 'Subir resultados'"
                                    variant="success" wire:click="openResultModal({{ $item->id }})" />
                            @endif

                            {{-- RECHAZADA --}}
                            @if ($item->estatus === 'rechazada')
                                <x-action-icon icon="eye" label="Ver rechazo" variant="danger"
                                    wire:click="verRechazoObligacion({{ $item->id }})" />
                            @endif

                            {{-- RESULTADO SOLO LECTURA --}}
                            @if (in_array($item->estatus, ['realizada', 'declaracion_realizada', 'enviada_cliente', 'respuesta_cliente', 'respuesta_revisada', 'finalizado'], true))
                                <x-action-icon icon="eye" label="Ver resultado" variant="info"
                                    wire:click="openResultModal({{ $item->id }})" />
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
        @php
            $obligacionModal = $selectedId
                ? \App\Models\ObligacionClienteContador::with('archivos')->find($selectedId)
                : null;
            $esRechazo = $obligacionModal?->estatus === 'rechazada';
        @endphp
        <div class="fixed inset-0 flex items-center justify-center bg-stone-800/70 z-50 p-4">
            <div class="flex max-h-[90vh] w-full max-w-2xl flex-col overflow-hidden rounded-lg bg-white shadow-lg dark:bg-gray-900">
                <div class="border-b border-gray-200 p-6 dark:border-gray-700">
                    <h3 class="text-lg font-bold text-stone-700 dark:text-white">
                        {{ $modalCliente }}
                    </h3>

                    <p class="text-sm text-gray-600 dark:text-gray-300">
                        Resultados de obligacion - {{ $modalObligacion }}
                    </p>
                </div>


                <div class="flex-1 space-y-4 overflow-y-auto p-6">


                    {{-- ============================= --}}
                    {{-- SOLO SI NO ES RECHAZO --}}
                    {{-- ============================= --}}
                    @if ($soloLectura && !$esRechazo)
                        <div>
                            <label class="block text-sm mb-1">Archivos</label>
                            <div class="rounded border border-gray-200 dark:border-gray-700 p-3 space-y-2">
                                @forelse ($obligacionModal?->archivos ?? [] as $archivo)
                                    <div class="text-sm">
                                        <span class="font-medium text-stone-700 dark:text-white">{{ $archivo->nombre }}</span>
                                        @if ($archivo->archivo)
                                            <a href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($archivo->archivo) }}"
                                                target="_blank" class="ml-2 text-blue-600 dark:text-blue-400 hover:underline">
                                                Ver archivo
                                            </a>
                                        @endif
                                    </div>
                                @empty
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Sin archivos adjuntos.</p>
                                @endforelse
                            </div>
                        </div>
                    @endif

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
                                   focus:border-amber-600 focus:ring focus:ring-amber-500/40 focus:outline-none" />

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
                                   focus:border-amber-600 focus:ring focus:ring-amber-500/40 focus:outline-none" />

                            @error('fecha_finalizado')
                                <span class="text-red-600 text-sm">{{ $message }}</span>
                            @enderror
                        </div>

                    @endif

                    @if ($soloLectura && !$esRechazo)
                        <div>
                            <label class="block text-sm mb-1">Numero de operacion</label>
                            <div class="w-full px-3 py-2 border rounded bg-gray-100 dark:bg-gray-800 dark:text-white">
                                {{ $numero_operacion ?: '-' }}
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm mb-1">Fecha oficial del documento / linea de captura</label>
                            <div class="w-full px-3 py-2 border rounded bg-gray-100 dark:bg-gray-800 dark:text-white">
                                {{ $fecha_finalizado ?: '-' }}
                            </div>
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
                               focus:border-amber-600 focus:ring focus:ring-amber-500/40 focus:outline-none">
                    </textarea>
                    </div>
                </div>

                {{-- ============================= --}}
                {{-- BOTONES --}}
                {{-- ============================= --}}
                <div class="flex justify-end space-x-2 border-t border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-900">

                    {{-- Cerrar --}}
                    <button wire:click="$set('openModal', false)"
                        class="px-4 py-2 bg-amber-600 text-white rounded hover:bg-amber-700">
                        Cerrar
                    </button>

                    {{-- Rechazo --}}
                    @if ($soloLectura && $esRechazo)
                        <button wire:click="corregirObligacion({{ $selectedId }})"
                            class="px-4 py-2 bg-amber-600 text-white rounded hover:bg-amber-700">
                            Corregir
                        </button>
                    @elseif (!$soloLectura)
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
