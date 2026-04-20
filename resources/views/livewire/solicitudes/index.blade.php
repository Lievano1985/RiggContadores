<div x-data="{ sidebar: @entangle('sidebarVisible') }" class="p-6 bg-white dark:bg-gray-900 text-gray-900 dark:text-white rounded-lg shadow space-y-4">
    <div class="flex flex-wrap gap-2 justify-between items-center">
        <h2 class="text-xl font-bold text-stone-600 dark:text-white">Solicitudes</h2>
        <button wire:click="abrirSidebarCrear" class="px-4 py-2 bg-amber-600 text-white rounded hover:bg-amber-700">
            + Nueva solicitud
        </button>
    </div>

    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
        <div class="flex flex-wrap items-center gap-2">
            <input type="text" placeholder="Buscar por cliente o titulo" wire:model.live="buscar"
                class="px-3 py-2 border rounded dark:bg-gray-700 dark:text-white focus:border-amber-600 focus:ring focus:ring-amber-500/40 focus:outline-none">

            <select wire:model.live="estado"
                class="px-3 py-2 border rounded dark:bg-gray-700 dark:text-white focus:border-amber-600 focus:ring focus:ring-amber-500/40 focus:outline-none">
                <option value="">Estado (todos)</option>
                <option value="abierta">Abierta</option>
                <option value="en_proceso">En proceso</option>
                <option value="pendiente_cliente">Pendiente cliente</option>
                <option value="cerrada">Cerrada</option>
            </select>

            <select wire:model.live="origen"
                class="px-3 py-2 border rounded dark:bg-gray-700 dark:text-white focus:border-amber-600 focus:ring focus:ring-amber-500/40 focus:outline-none">
                <option value="">Origen (todos)</option>
                <option value="cliente">Cliente</option>
                <option value="despacho">Despacho</option>
            </select>

            <select wire:model.live="responsable"
                class="px-3 py-2 border rounded dark:bg-gray-700 dark:text-white focus:border-amber-600 focus:ring focus:ring-amber-500/40 focus:outline-none">
                <option value="">Responsable (todos)</option>
                @foreach ($responsables as $item)
                    <option value="{{ $item->id }}">{{ $item->name }}</option>
                @endforeach
            </select>

            <select wire:model.live="perPage"
                class="px-3 py-2 border rounded dark:bg-gray-700 dark:text-white focus:border-amber-600 focus:ring focus:ring-amber-500/40 focus:outline-none">
                @foreach ($perPageOptions as $option)
                    <option value="{{ $option }}">{{ $option === 'all' ? 'Todos' : $option }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="bg-white dark:bg-gray-900 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
            <thead class="bg-stone-100 dark:bg-stone-900">
                <tr>
                    <x-sortable-th field="cliente" label="Cliente" :sort-field="$sortField" :sort-direction="$sortDirection" />
                    <x-sortable-th field="titulo" label="Titulo" :sort-field="$sortField" :sort-direction="$sortDirection" />
                    <x-sortable-th field="origen" label="Origen" :sort-field="$sortField" :sort-direction="$sortDirection" />
                    <x-sortable-th field="estado" label="Estado" :sort-field="$sortField" :sort-direction="$sortDirection" />
                    <x-sortable-th field="responsable" label="Responsable" :sort-field="$sortField" :sort-direction="$sortDirection" />
                    <th class="px-4 py-2 text-left text-xs font-semibold">Obligacion</th>
                    <x-sortable-th field="created_at" label="Creada" :sort-field="$sortField" :sort-direction="$sortDirection" />
                </tr>
            </thead>

            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                @forelse ($solicitudes as $solicitud)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/60">
                        <td class="px-4 py-2">
                            {{ $solicitud->cliente->nombre ?? ($solicitud->cliente->razon_social ?? '-') }}
                        </td>
                        <td class="px-4 py-2">
                            <div class="font-medium">{{ $solicitud->titulo }}</div>
                            @if ($solicitud->descripcion)
                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ \Illuminate\Support\Str::limit($solicitud->descripcion, 80) }}
                                </div>
                            @endif
                        </td>
                        <td class="px-4 py-2 capitalize">{{ $solicitud->origen }}</td>
                        <td class="px-4 py-2">{{ str_replace('_', ' ', $solicitud->estado) }}</td>
                        <td class="px-4 py-2">{{ $solicitud->responsable->name ?? '-' }}</td>
                        <td class="px-4 py-2">{{ $solicitud->obligacion->nombre ?? '-' }}</td>
                        <td class="px-4 py-2 whitespace-nowrap">
                            {{ $solicitud->created_at?->format('d/m/Y H:i') ?? '-' }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-4 text-center text-gray-500 dark:text-gray-400">
                            No hay solicitudes registradas.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>
        {{ $solicitudes->links('vendor.pagination.tailwind-links-only') }}
    </div>

    <div x-cloak x-show="sidebar" x-transition.opacity class="fixed inset-0 bg-black/40 z-40 flex justify-end">
        <div class="flex-1" @click="$wire.cerrarSidebar()"></div>

        <div class="w-full max-w-xl bg-white dark:bg-gray-900 shadow-xl h-full border-l flex flex-col">
            <div class="p-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
                <h3 class="text-lg font-semibold text-stone-700 dark:text-white">
                    Nueva solicitud
                </h3>
                <button @click="$wire.cerrarSidebar()" class="text-gray-500 hover:text-black dark:hover:text-white">x</button>
            </div>

            <div class="flex-1 overflow-y-auto p-4 space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium mb-1">Cliente</label>
                        <select wire:model.live="cliente_id_form"
                            class="w-full px-3 py-2 border rounded-md dark:bg-gray-700 dark:text-white border-gray-300 dark:border-gray-600 focus:border-amber-600 focus:ring focus:ring-amber-500/40 focus:outline-none">
                            <option value="">Seleccione...</option>
                            @foreach ($clientesDisponibles as $cliente)
                                <option value="{{ $cliente['id'] }}">{{ $cliente['nombre'] }}</option>
                            @endforeach
                        </select>
                        @error('cliente_id_form') <div class="text-red-500 text-xs mt-1">{{ $message }}</div> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1">Origen</label>
                        <select wire:model="origen_form"
                            class="w-full px-3 py-2 border rounded-md dark:bg-gray-700 dark:text-white border-gray-300 dark:border-gray-600 focus:border-amber-600 focus:ring focus:ring-amber-500/40 focus:outline-none">
                            <option value="despacho">Despacho</option>
                            <option value="cliente">Cliente</option>
                        </select>
                        @error('origen_form') <div class="text-red-500 text-xs mt-1">{{ $message }}</div> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1">Modo de solicitud</label>
                        <select wire:model.live="modo_solicitud_form"
                            class="w-full px-3 py-2 border rounded-md dark:bg-gray-700 dark:text-white border-gray-300 dark:border-gray-600 focus:border-amber-600 focus:ring focus:ring-amber-500/40 focus:outline-none">
                            <option value="general">General</option>
                            <option value="definida">Definida</option>
                        </select>
                        @error('modo_solicitud_form') <div class="text-red-500 text-xs mt-1">{{ $message }}</div> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1">Obligacion relacionada</label>
                        <select wire:model="obligacion_id_form"
                            class="w-full px-3 py-2 border rounded-md dark:bg-gray-700 dark:text-white border-gray-300 dark:border-gray-600 focus:border-amber-600 focus:ring focus:ring-amber-500/40 focus:outline-none">
                            <option value="">Sin relacion</option>
                            @foreach ($obligacionesDisponibles as $obligacion)
                                <option value="{{ $obligacion['id'] }}">{{ $obligacion['nombre'] }}</option>
                            @endforeach
                        </select>
                        @error('obligacion_id_form') <div class="text-red-500 text-xs mt-1">{{ $message }}</div> @enderror
                    </div>
                </div>

                @if ($modo_solicitud_form === 'definida')
                    <div>
                        <label class="block text-sm font-medium mb-1">Tipo de solicitud</label>
                        <select wire:model.live="tipo_solicitud_id_form"
                            class="w-full px-3 py-2 border rounded-md dark:bg-gray-700 dark:text-white border-gray-300 dark:border-gray-600 focus:border-amber-600 focus:ring focus:ring-amber-500/40 focus:outline-none">
                            <option value="">Seleccione...</option>
                            @foreach ($tiposDisponibles as $tipo)
                                <option value="{{ $tipo['id'] }}">{{ $tipo['nombre'] }}</option>
                            @endforeach
                        </select>
                        @error('tipo_solicitud_id_form') <div class="text-red-500 text-xs mt-1">{{ $message }}</div> @enderror
                    </div>
                @endif

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium mb-1">Titulo</label>
                        <input type="text" wire:model="titulo_form"
                            class="w-full px-3 py-2 border rounded-md dark:bg-gray-700 dark:text-white border-gray-300 dark:border-gray-600 focus:border-amber-600 focus:ring focus:ring-amber-500/40 focus:outline-none">
                        @error('titulo_form') <div class="text-red-500 text-xs mt-1">{{ $message }}</div> @enderror
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium mb-1">Descripcion</label>
                        <textarea wire:model="descripcion_form" rows="4"
                            class="w-full px-3 py-2 border rounded-md dark:bg-gray-700 dark:text-white border-gray-300 dark:border-gray-600 focus:border-amber-600 focus:ring focus:ring-amber-500/40 focus:outline-none"></textarea>
                        @error('descripcion_form') <div class="text-red-500 text-xs mt-1">{{ $message }}</div> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1">Prioridad</label>
                        <select wire:model="prioridad_form"
                            class="w-full px-3 py-2 border rounded-md dark:bg-gray-700 dark:text-white border-gray-300 dark:border-gray-600 focus:border-amber-600 focus:ring focus:ring-amber-500/40 focus:outline-none">
                            <option value="">Seleccione...</option>
                            <option value="baja">Baja</option>
                            <option value="media">Media</option>
                            <option value="alta">Alta</option>
                            <option value="urgente">Urgente</option>
                        </select>
                        @error('prioridad_form') <div class="text-red-500 text-xs mt-1">{{ $message }}</div> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1">Responsable asignado</label>
                        <div class="w-full px-3 py-2 border rounded-md bg-gray-50 dark:bg-gray-800 border-gray-300 dark:border-gray-600 text-gray-800 dark:text-gray-100">
                            {{ $responsableClienteSeleccionado?->name ?? 'Seleccione un cliente con responsable asignado' }}
                        </div>
                    </div>
                </div>

                @if ($modo_solicitud_form === 'definida' && $tipoSeleccionado)
                    <div class="border p-4 rounded shadow-sm dark:border-gray-700 space-y-3">
                        <h4 class="font-semibold text-stone-700 dark:text-white">Plantilla del tipo seleccionado</h4>

                        <p class="text-sm">
                            <strong>Documentos sugeridos:</strong>
                            {{ !empty($tipoSeleccionado->documentos_sugeridos) ? count($tipoSeleccionado->documentos_sugeridos) : 0 }}
                        </p>

                        <p class="text-sm">
                            <strong>Secciones del formulario:</strong>
                            {{ !empty($tipoSeleccionado->configuracion_formulario['secciones']) ? count($tipoSeleccionado->configuracion_formulario['secciones']) : 0 }}
                        </p>

                        @if (!empty($tipoSeleccionado->documentos_sugeridos))
                            <div>
                                <p class="text-sm font-medium mb-1">Documentos</p>
                                <ul class="list-disc ml-5 text-sm text-gray-700 dark:text-gray-300 space-y-1">
                                    @foreach ($tipoSeleccionado->documentos_sugeridos as $documento)
                                        <li>{{ $documento }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    </div>
                @endif
            </div>

            <div class="p-4 border-t border-gray-200 dark:border-gray-700 flex justify-end gap-2">
                <button wire:click="cerrarSidebar"
                    class="px-4 py-2 border rounded text-sm dark:border-gray-600 dark:text-white">
                    Cancelar
                </button>
                <button wire:click="guardarSolicitud"
                    class="bg-amber-600 text-white px-4 py-2 rounded hover:bg-amber-700 text-sm">
                    Guardar solicitud
                </button>
            </div>
        </div>
    </div>
</div>
