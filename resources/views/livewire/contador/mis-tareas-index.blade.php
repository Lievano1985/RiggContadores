<div>

    <div class="p-6 bg-white dark:bg-gray-900 text-gray-900 dark:text-white rounded-lg shadow space-y-4">
        <h2 class="text-xl font-bold text-stone-600">Mis tareas asignadas</h2>

        {{-- =========================
            FILTROS
        ========================== --}}
        <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">

            <div class="flex flex-wrap items-center gap-2">

                {{-- Filtro ejercicio (solo anios con datos) --}}
                <select wire:model.live="ejercicio"
                    class="px-3 py-2 border rounded dark:bg-gray-700 dark:text-white focus:outline-amber-600 focus:outline">
                    <option value="">Selecciona...</option> {{-- OPCION INICIAL --}}
                    <option value="">Ejercicio (todos)</option>
                    @foreach ($ejerciciosDisponibles as $anio)
                        <option value="{{ $anio }}">{{ $anio }}</option>
                    @endforeach
                </select>

                {{-- Filtro mes (solo meses con datos para el anio seleccionado) --}}
                <select wire:model.live="mes"
                    class="px-3 py-2 border rounded dark:bg-gray-700 dark:text-white focus:outline-amber-600 focus:outline">
                    <option value="">Selecciona...</option> {{-- OPCION INICIAL --}}
                    <option value="">Mes (todos)</option>
                    @foreach ($mesesManual as $num => $txt)
                        <option value="{{ $num }}">{{ $txt }}</option>
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
                <input type="text" placeholder="Buscar (tarea / obligacion)" wire:model.live="buscar"
                    class=" px-3 py-2 border rounded dark:bg-gray-700 dark:text-white focus:outline-amber-600 focus:outline">
            </div>
        </div>

        {{-- =========================
            TABLA
        ========================== --}}
        <div class="bg-white dark:bg-gray-900 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
        
                {{-- =========================
                    HEADER
                ========================== --}}
                                                <thead class="bg-stone-100 dark:bg-stone-900">
                    <tr>
                        <x-sortable-th field="cliente" label="Cliente" :sort-field="$sortField" :sort-direction="$sortDirection" />
                        <x-sortable-th field="ejercicio" label="Ejercicio" :sort-field="$sortField" :sort-direction="$sortDirection" />
                        <x-sortable-th field="tarea" label="Tarea" :sort-field="$sortField" :sort-direction="$sortDirection" />
                        <x-sortable-th field="obligacion" label="Obligacion" :sort-field="$sortField" :sort-direction="$sortDirection" />
                        <x-sortable-th field="fecha_limite" label="Vence" :sort-field="$sortField" :sort-direction="$sortDirection" />
                        <x-sortable-th field="estatus" label="Estatus" :sort-field="$sortField" :sort-direction="$sortDirection" />
                        <th class="px-4 py-2 text-left text-xs font-semibold">Tiempo estimado</th>
                        <th class="px-4 py-2 text-left text-xs font-semibold">Duracion real</th>
                        <th class="px-4 py-2 text-left text-xs font-semibold">Acciones</th>
                    </tr>
                </thead>
        
                {{-- =========================
                    BODY
                ========================== --}}
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
        
                    @forelse ($tareas as $t)
                        @php
                            $vence = $t->fecha_limite ? \Carbon\Carbon::parse($t->fecha_limite) : null;
        
                            $vencida = $vence && $vence->isPast() && $t->estatus !== 'cerrada';
                        @endphp
        
                        <tr
                        @class([
                            'transition-all duration-400',
                        
                            // vencida SOLO si no esta resaltada
                            $vencida && $highlightId !== $t->id
                                ? 'bg-red-50 dark:bg-red-900 dark:text-red-100'
                                : '',
                        
                            // highlight tiene prioridad
                            'bg-amber-100 dark:bg-amber-900' => $highlightId === $t->id,
                        ])
                           
                        >
        
                            <td class="px-4 py-2">
                                {{ $t->cliente->nombre ?? ($t->cliente->razon_social ?? '-') }}
                            </td>
        
                            <td class="px-4 py-2 whitespace-nowrap">
                                {{ $t->ejercicio }}-{{ str_pad($t->mes, 2, '0', STR_PAD_LEFT) }}
                            </td>
        
                            <td class="px-4 py-2">
                                {{ $t->tareaCatalogo?->nombre ?? '-' }}
                            </td>
        
                            <td class="px-4 py-2">
                                {{ $t->obligacionClienteContador?->obligacion?->nombre ?? 'Sin obligacion' }}
                            </td>
        
                            <td class="px-4 py-2 whitespace-nowrap">
                                {{ $vence ? $vence->format('Y-m-d') : '-' }}
                            </td>
        
                            <td class="px-4 py-2">
                                <x-status-badge :status="$t->estatus" />
                            </td>
        
                            <td class="px-4 py-2">
                                {{ $t->tiempo_estimado ? $t->tiempo_estimado . ' min' : '-' }}
                            </td>
        
                            <td class="px-4 py-2">
                                {{ $t->duracion_minutos ? $t->duracion_minutos . ' min' : '-' }}
                            </td>
        
                            <td class="px-4 py-2">
                                <div class="flex items-center gap-1">

                                {{-- ASIGNADA --}}
                                @if ($t->estatus === 'asignada')
                                    <x-action-icon icon="play" label="Iniciar" variant="neutral"
                                        wire:click="iniciar({{ $t->id }})" />
                                @endif

                                {{-- EN PROGRESO --}}
                                @if ($t->estatus === 'en_progreso')
                                    <x-action-icon icon="check" label="Terminar" variant="primary"
                                        wire:click="terminar({{ $t->id }})" />
                                @endif

                                {{-- RECHAZADA --}}
                                @if ($t->estatus === 'rechazada')
                                    <x-action-icon icon="eye" label="Ver rechazo" variant="danger"
                                        wire:click="verRechazo({{ $t->id }})" />
                                @endif

                                </div>
                            </td>
        
                        </tr>
        
                    @empty
                        <tr>
                            <td colspan="9"
                                class="px-4 py-6 text-center text-sm text-gray-600 dark:text-gray-300">
                                No hay tareas para mostrar.
                            </td>
                        </tr>
                    @endforelse
        
                </tbody>
            </table>
        </div>
        

        @include('livewire.shared.pagination-controls', ['paginator' => $tareas])

        {{-- =========================
            MODAL SEGUIMIENTO
        ========================== --}}
        @if ($openModal && $tareaSeleccionada)
            <div class="fixed inset-0 flex items-center justify-center bg-stone-600 bg-opacity-50 z-50">
                <div class="bg-white dark:bg-gray-900 p-6 rounded-lg shadow-lg w-full max-w-2xl">

                    <div class="mb-4">
                        <h4 class="text-lg font-bold text-stone-600">
                            {{ $modalCliente }}
                        </h4>

                        <p class="text-sm text-gray-600 dark:text-gray-300">
                            Resultados de tarea - {{ $modalTarea }}

                            @if ($modalObligacion)
                                - {{ $modalObligacion }}
                            @endif
                        </p>
                    </div>

                    {{-- Comentario si esta en estatus rechazado --}}
                    @if ($tareaSeleccionada->estatus === 'rechazada')
                        <div class="mb-4">
                            <label class="block text-sm mb-1 text-stone-600">Comentario del rechazo</label>
                            <div class="bg-gray-100 dark:bg-gray-800 px-3 py-2 rounded text-gray-800 dark:text-white">
                                {{ $tareaSeleccionada->comentario ?? '-' }}
                            </div>
                        </div>
                    @endif

                    {{-- Archivos y comentario solo si esta en progreso --}}
                    @if ($tareaSeleccionada->estatus === 'en_progreso')
                        {{-- COMPONENTE DE ARCHIVOS --}}
                        @livewire('shared.archivos-adjuntos-crud', ['modelo' => $tareaSeleccionada], key('archivos-tarea-' . $tareaSeleccionada->id))

                        {{-- COMENTARIO --}}
                        <div class="mt-4">
                            <label class="block text-sm mb-1 text-stone-600">Comentario</label>
                            <textarea wire:model.defer="comentario" rows="3" placeholder="Describe el resultado o notas adicionales..."
                                class="w-full px-3 py-2 border rounded dark:bg-gray-700 dark:text-white focus:outline-amber-600 focus:outline">
                        </textarea>
                        </div>
                    @endif

                    {{-- ACCIONES --}}
                    <div class="flex justify-end space-x-2 mt-6">
                        {{-- Cerrar modal --}}
                        <button wire:click="$set('openModal', false)"
                            class="bg-amber-600 text-white px-4 py-2 rounded hover:bg-amber-700">
                            Cerrar
                        </button>

                        {{-- Boton "Realizar" si esta en rechazado --}}
                        @if ($tareaSeleccionada->estatus === 'rechazada')
                            <button wire:click="corregir({{ $tareaSeleccionada->id }})"
                                class="bg-amber-600 hover:bg-amber-700 px-4 py-2 rounded text-white">
                                Corregir
                            </button>
                        @endif

                        {{-- Boton "Finalizar" si ya esta en progreso --}}
                        @if ($tareaSeleccionada->estatus === 'en_progreso')
                            <button wire:click="saveResultTarea"
                                @click="window.dispatchEvent(new CustomEvent('spinner-on'))"
                                class="bg-amber-600 hover:bg-amber-700 px-4 py-2 rounded text-white">
                                Marcar como realizada
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
</div>
