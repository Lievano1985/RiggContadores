<div class="p-6 bg-white dark:bg-gray-900 text-gray-900 dark:text-white rounded-lg shadow space-y-4">

    <h2 class="text-xl font-bold text-stone-600">Mis obligaciones asignadas</h2>

    {{-- =========================
        FILTROS
    ========================== --}}
    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
        <div class="flex flex-wrap items-center gap-2">

            <select wire:model.live="ejercicioSeleccionado"
                class="px-3 py-2 border rounded dark:bg-gray-700 dark:text-white focus:outline-amber-600 focus:outline">
                <option value="">Selecciona...</option> {{-- 👈 OPCIÓN INICIAL --}}
                <option value="">Ejercicio (todos)</option>
                @foreach ($ejerciciosDisponibles as $ej)
                    <option value="{{ $ej }}">{{ $ej }}</option>
                @endforeach
            </select>

            <select wire:model.live="mesSeleccionado"
                class="px-3 py-2 border rounded dark:bg-gray-700 dark:text-white focus:outline-amber-600 focus:outline">
                <option value="">Selecciona...</option> {{-- 👈 OPCIÓN INICIAL --}}
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
            <input type="text" placeholder="Buscar (obligación)" wire:model.live="buscar"
                class=" px-3 py-2 border rounded dark:bg-gray-700 dark:text-white focus:outline-amber-600 focus:outline">
        </div>
    </div>

    {{-- =========================
        TABLA
    ========================== --}}
    <div class="overflow-x-auto rounded shadow mt-2">
        <table class="min-w-full divide-y divide-gray-300 dark:divide-gray-700 text-sm">
            <thead class="bg-stone-100 dark:bg-stone-900">
                <tr>
                    <th class="px-4 py-2 text-left">Cliente</th>
                    <th class="px-4 py-2 text-left">Ejercicio</th>
                    <th class="px-4 py-2 text-left">Obligación</th>
                    <th class="px-4 py-2 text-left">Periodicidad</th>
                    <th class="px-4 py-2 text-left">Estatus</th>
                    <th class="px-4 py-2 text-left">Vence</th>
                    <th class="px-4 py-2 text-left">Acciones</th>
                </tr>
            </thead>

            <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                @forelse ($obligaciones as $item)
                    @php
                        $chip = match ($item->estatus) {
                            'asignada' => 'bg-stone-600',
                            'en_progreso' => 'bg-amber-600',
                            'realizada' => 'bg-green-600',
                            'enviada_cliente' => 'bg-blue-600',
                            'respuesta_cliente' => 'bg-indigo-600',
                            'respuesta_revisada' => 'bg-purple-600',
                            'finalizado' => 'bg-emerald-700',
                            'rechazada' => 'bg-red-600',

                            'reabierta' => 'bg-red-600',
                            default => 'bg-gray-500',
                        };
                    @endphp

                    @php
                        $vencida =
                            $item->fecha_vencimiento &&
                            \Carbon\Carbon::parse($item->fecha_vencimiento)->isPast() &&
                            $item->estatus !== 'finalizado';
                    @endphp


                    <tr @class([
                        'transition-all duration-400',
                    
                        // 🔴 vencida SOLO si no está resaltada
                        $item->fecha_vencimiento < now() && $highlightId !== $item->id
                            ? 'bg-red-50 dark:bg-red-900 dark:text-red-100'
                            : '',
                    
                        // 🟡 highlight flash (PRIORIDAD)
                        'bg-amber-100 dark:bg-amber-900' => $highlightId === $item->id,
                    ])>

                        <td class="px-4 py-2">{{ $item->cliente->nombre ?? ($item->cliente->razon_social ?? '—') }}</td>
                        <td class="px-4 py-2 whitespace-nowrap">
                            {{ $item->ejercicio }} - {{ str_pad($item->mes, 2, '0', STR_PAD_LEFT) }}
                        </td>
                        <td class="px-4 py-2">{{ $item->obligacion->nombre ?? '—' }}</td>
                        <td class="px-4 py-2">{{ ucfirst($item->obligacion->periodicidad ?? '—') }}</td>

                        <td class="px-4 py-2">
                            <span class="text-xs px-2 py-1 rounded text-white {{ $chip }}">
                                {{ ucfirst(str_replace('_', ' ', $item->estatus)) }}
                            </span>
                        </td>

                        <td class="px-4 py-2 whitespace-nowrap">
                            {{ $item->fecha_vencimiento ? \Carbon\Carbon::parse($item->fecha_vencimiento)->format('Y-m-d') : '—' }}
                        </td>

                        <td class="px-4 py-2 space-x-2">

                            {{-- ASIGNADA --}}
                            @if ($item->estatus === 'asignada')
                                <button wire:click="iniciarObligacion({{ $item->id }})"
                                    class="bg-amber-600 text-white px-3 py-1 rounded hover:bg-amber-700">
                                    Iniciar
                                </button>
                            @endif

                            {{-- EN PROGRESO / REALIZADA --}}
                            @if (in_array($item->estatus, ['en_progreso', 'realizada'], true))
                                <button wire:click="openResultModal({{ $item->id }})"
                                    class="bg-green-600 text-white px-3 py-1 rounded hover:bg-green-700">
                                    {{ $item->estatus === 'realizada' ? 'Editar resultados' : 'Subir resultados' }}
                                </button>
                            @endif

                            {{-- RECHAZADA --}}
                            @if ($item->estatus === 'rechazada')
                                <button wire:click="verRechazoObligacion({{ $item->id }})"
                                    class="bg-red-600 text-white px-3 py-1 rounded hover:bg-red-700">
                                    Ver rechazo
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

    <div>{{ $obligaciones->links() }}</div>

    @if ($openModal)
        <div class="fixed inset-0 flex items-center justify-center bg-stone-800/70 z-50 p-4">
            <div class="bg-white dark:bg-gray-900 p-6 rounded-lg shadow-lg w-full max-w-lg">
                <div class="mb-4">
                    <h3 class="text-lg font-bold text-stone-700 dark:text-white">
                        {{ $modalCliente }}
                    </h3>

                    <p class="text-sm text-gray-600 dark:text-gray-300">
                        Resultados de obligación – {{ $modalObligacion }}
                    </p>
                </div>


                <div class="space-y-4">


                    {{-- ============================= --}}
                    {{-- SOLO SI NO ES RECHAZO --}}
                    {{-- ============================= --}}
                    @if (!$soloLectura)

                        {{-- Archivos múltiples --}}
                        @if ($selectedId)
                            <div class="mt-4 border-t pt-4">
                                <livewire:shared.archivos-adjuntos-crud :modelo="\App\Models\ObligacionClienteContador::find($selectedId)"
                                    wire:key="archivos-obligacion-{{ $selectedId }}" />
                            </div>
                        @endif

                        {{-- Número de operación --}}
                        <div>
                            <label class="block text-sm mb-1">
                                Número de operación
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
                                Fecha oficial del documento / línea de captura
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
                    {{-- COMENTARIO (ÚNICO) --}}
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
                        class="px-4 py-2 bg-gray-300 dark:bg-gray-700
                           text-black dark:text-white rounded hover:bg-gray-400">
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



    <x-notification />
    <script>
        window.addEventListener('limpiar-highlight', () => {
            setTimeout(() => {
                Livewire.find(@this.__instance.id).call('limpiarHighlight')
            }, 5000)
        })
    </script>

</div>
