@php use Illuminate\Support\Facades\Storage; @endphp

<div wire:poll.10s x-data="{ sidebar: @entangle('sidebarVisible'), detalleSidebar: @entangle('detalleSidebarVisible') }" class="p-6 bg-white dark:bg-gray-900 text-gray-900 dark:text-white rounded-lg shadow space-y-4">
    <div class="flex flex-wrap gap-2 justify-between items-center">
        <h2 class="text-xl font-bold text-stone-600 dark:text-white">{{ $tituloModulo }}</h2>
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
                <option value="cancelada">Cancelada</option>
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
                    <th class="px-4 py-2 text-left text-xs font-semibold">Acciones</th>
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
                        <td class="px-4 py-2">{{ $solicitud->obligacion_etiqueta }}</td>
                        <td class="px-4 py-2 whitespace-nowrap">
                            {{ $solicitud->created_at?->format('d/m/Y H:i') ?? '-' }}
                        </td>
                        <td class="px-4 py-2 whitespace-nowrap">
                            <div class="flex items-center gap-2">
                                <x-action-icon icon="eye" label="Detalle" variant="primary"
                                    wire:click="abrirDetalle({{ $solicitud->id }})" />
                                <x-action-icon icon="edit" label="Editar" variant="primary"
                                    wire:click="editarSolicitud({{ $solicitud->id }})" />
                                @if (!in_array($solicitud->estado, ['cerrada', 'cancelada']))
                                    <x-action-icon icon="trash" label="Cancelar" variant="danger"
                                        wire:click="confirmarCancelacionSolicitud({{ $solicitud->id }})" />
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-4 py-4 text-center text-gray-500 dark:text-gray-400">
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
                    {{ $editandoSolicitud ? 'Editar solicitud' : 'Nueva solicitud' }}
                </h3>
                <button @click="$wire.cerrarSidebar()" class="text-gray-500 hover:text-black dark:hover:text-white">x</button>
            </div>

            <div class="flex-1 overflow-y-auto p-4 space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium mb-1">Cliente</label>
                        <div class="relative"
                            x-data="{
                                open: false,
                                query: '',
                                selectedId: @entangle('cliente_id_form'),
                                clientes: @js($clientesDisponibles),
                                get filtrados() {
                                    if (!this.query.trim()) return this.clientes;
                                    return this.clientes.filter(cliente => cliente.nombre.toLowerCase().includes(this.query.toLowerCase()));
                                },
                                get seleccionado() {
                                    return this.clientes.find(cliente => Number(cliente.id) === Number(this.selectedId));
                                },
                                seleccionar(cliente) {
                                    this.selectedId = Number(cliente.id);
                                    this.query = cliente.nombre;
                                    this.open = false;
                                },
                                limpiar() {
                                    this.selectedId = null;
                                    this.query = '';
                                }
                            }"
                            x-init="query = seleccionado ? seleccionado.nombre : ''"
                            @click.outside="open = false">
                            <input type="hidden" wire:model.live="cliente_id_form">
                            <input type="text"
                                x-model="query"
                                @focus="open = true"
                                @input="selectedId = null; open = true"
                                placeholder="Buscar cliente..."
                                class="w-full px-3 py-2 border rounded-md text-gray-700 placeholder:text-gray-700 dark:bg-gray-700 dark:text-white dark:placeholder:text-white border-gray-300 dark:border-gray-600 focus:border-amber-600 focus:ring focus:ring-amber-500/40 focus:outline-none">

                            <button type="button"
                                x-show="query"
                                @click="limpiar(); open = true"
                                class="absolute inset-y-0 right-8 flex items-center text-xs text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                                x
                            </button>

                            <button type="button"
                                @click="open = !open"
                                class="absolute inset-y-0 right-3 flex items-center text-gray-700 hover:text-gray-900 dark:text-white dark:hover:text-white">
                                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.168l3.71-3.938a.75.75 0 1 1 1.08 1.04l-4.25 4.512a.75.75 0 0 1-1.08 0L5.21 8.27a.75.75 0 0 1 .02-1.06Z" clip-rule="evenodd" />
                                </svg>
                            </button>

                            <div x-show="open" x-cloak
                                class="rigg-client-picker-panel absolute z-50 mt-1 w-full max-h-56 overflow-y-auto rounded-md border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800 shadow-lg">
                                <button type="button"
                                    @click="limpiar(); open = false"
                                    :class="selectedId === null
                                        ? 'text-gray-500 dark:text-gray-400 hover:bg-blue-600 hover:text-white dark:hover:bg-blue-700'
                                        : 'text-gray-900 dark:text-white hover:bg-blue-600 hover:text-white dark:hover:bg-blue-700'"
                                    class="rigg-client-picker-option block w-full px-3 py-2 text-left text-sm transition cursor-pointer">
                                    Seleccione...
                                </button>

                                <template x-for="cliente in filtrados" :key="cliente.id">
                                    <button type="button"
                                        @click="seleccionar(cliente)"
                                        :class="Number(selectedId) === Number(cliente.id)
                                            ? 'bg-blue-600 text-white'
                                            : 'text-gray-900 dark:text-white hover:bg-blue-600 hover:text-white dark:hover:bg-blue-700'"
                                        class="rigg-client-picker-option block w-full px-3 py-2 text-left text-sm transition cursor-pointer"
                                        x-text="cliente.nombre">
                                    </button>
                                </template>

                                <div x-show="filtrados.length === 0"
                                    class="rigg-client-picker-empty px-3 py-2 text-sm text-gray-500 dark:text-gray-400">
                                    Sin resultados
                                </div>
                            </div>
                        </div>
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
                        <label class="block text-sm font-medium mb-1">Relacion con obligacion</label>
                        <select wire:model.live="relacion_obligacion_form"
                            class="w-full px-3 py-2 border rounded-md dark:bg-gray-700 dark:text-white border-gray-300 dark:border-gray-600 focus:border-amber-600 focus:ring focus:ring-amber-500/40 focus:outline-none">
                            <option value="sin_relacion">Sin relacion</option>
                            <option value="con_relacion">Ligada a obligacion</option>
                        </select>
                        @error('relacion_obligacion_form') <div class="text-red-500 text-xs mt-1">{{ $message }}</div> @enderror
                    </div>
                </div>

                @if ($relacion_obligacion_form === 'con_relacion')
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium mb-1">Año</label>
                            <input type="number" min="2020" max="2100" wire:model.live="periodo_anio_form"
                                class="w-full px-3 py-2 border rounded-md dark:bg-gray-700 dark:text-white border-gray-300 dark:border-gray-600 focus:border-amber-600 focus:ring focus:ring-amber-500/40 focus:outline-none">
                            @error('periodo_anio_form') <div class="text-red-500 text-xs mt-1">{{ $message }}</div> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-1">Mes</label>
                            <select wire:model.live="periodo_mes_form"
                                class="w-full px-3 py-2 border rounded-md dark:bg-gray-700 dark:text-white border-gray-300 dark:border-gray-600 focus:border-amber-600 focus:ring focus:ring-amber-500/40 focus:outline-none">
                                <option value="">Seleccione...</option>
                                @foreach ([1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril', 5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto', 9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'] as $mesNumero => $mesNombre)
                                    <option value="{{ $mesNumero }}">{{ $mesNombre }}</option>
                                @endforeach
                            </select>
                            @error('periodo_mes_form') <div class="text-red-500 text-xs mt-1">{{ $message }}</div> @enderror
                        </div>

                        <div class="md:col-span-1">
                            <label class="block text-sm font-medium mb-1">Obligacion relacionada</label>
                            <div class="relative"
                                x-data="{
                                    open: false,
                                    query: '',
                                    disabled: @js(!$cliente_id_form || !$periodo_anio_form || !$periodo_mes_form),
                                    selectedId: @entangle('obligacion_cliente_contador_id_form'),
                                    obligaciones: @js($obligacionesDisponibles),
                                    get filtradas() {
                                        if (!this.query.trim()) return this.obligaciones;
                                        return this.obligaciones.filter(obligacion => obligacion.nombre.toLowerCase().includes(this.query.toLowerCase()));
                                    },
                                    get seleccionada() {
                                        return this.obligaciones.find(obligacion => Number(obligacion.id) === Number(this.selectedId));
                                    },
                                    seleccionar(obligacion) {
                                        this.selectedId = Number(obligacion.id);
                                        this.query = obligacion.nombre;
                                        this.open = false;
                                    },
                                    limpiar() {
                                        this.selectedId = null;
                                        this.query = '';
                                    }
                                }"
                                x-init="query = seleccionada ? seleccionada.nombre : ''"
                                @click.outside="open = false">
                                <input type="hidden" wire:model.live="obligacion_cliente_contador_id_form">
                                <input type="text"
                                    x-model="query"
                                    @focus="if (!disabled) open = true"
                                    @input="selectedId = null; if (!disabled) open = true"
                                    placeholder="Buscar obligacion..."
                                    :disabled="disabled"
                                    class="w-full px-3 py-2 border rounded-md text-gray-700 placeholder:text-gray-700 dark:bg-gray-700 dark:text-white dark:placeholder:text-white border-gray-300 dark:border-gray-600 focus:border-amber-600 focus:ring focus:ring-amber-500/40 focus:outline-none disabled:opacity-60">

                                <button type="button"
                                    x-show="query && !disabled"
                                    @click="limpiar(); open = true"
                                    class="absolute inset-y-0 right-8 flex items-center text-xs text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                                    x
                                </button>

                                <button type="button"
                                    @click="if (!disabled) open = !open"
                                    class="absolute inset-y-0 right-3 flex items-center text-gray-700 hover:text-gray-900 dark:text-white dark:hover:text-white">
                                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                        <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.168l3.71-3.938a.75.75 0 1 1 1.08 1.04l-4.25 4.512a.75.75 0 0 1-1.08 0L5.21 8.27a.75.75 0 0 1 .02-1.06Z" clip-rule="evenodd" />
                                    </svg>
                                </button>

                                <div x-show="open" x-cloak
                                    class="absolute z-50 mt-1 w-full max-h-56 overflow-y-auto rounded-md border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800 shadow-lg">
                                    <button type="button"
                                        @click="limpiar(); open = false"
                                        :class="selectedId === null
                                            ? 'text-gray-500 dark:text-gray-400 hover:bg-blue-600 hover:text-white dark:hover:bg-blue-700'
                                            : 'text-gray-900 dark:text-white hover:bg-blue-600 hover:text-white dark:hover:bg-blue-700'"
                                        class="block w-full px-3 py-2 text-left text-sm transition cursor-pointer">
                                        {{ count($obligacionesDisponibles) ? 'Seleccione...' : 'Sin obligaciones en ese periodo' }}
                                    </button>

                                    <template x-for="obligacion in filtradas" :key="obligacion.id">
                                        <button type="button"
                                            @click="seleccionar(obligacion)"
                                            :class="Number(selectedId) === Number(obligacion.id)
                                                ? 'bg-blue-600 text-white'
                                                : 'text-gray-900 dark:text-white hover:bg-blue-600 hover:text-white dark:hover:bg-blue-700'"
                                            class="block w-full px-3 py-2 text-left text-sm transition cursor-pointer"
                                            x-text="obligacion.nombre">
                                        </button>
                                    </template>

                                    <div x-show="filtradas.length === 0"
                                        class="px-3 py-2 text-sm text-gray-500 dark:text-gray-400">
                                        Sin resultados
                                    </div>
                                </div>
                            </div>
                            @error('obligacion_cliente_contador_id_form') <div class="text-red-500 text-xs mt-1">{{ $message }}</div> @enderror
                        </div>
                    </div>
                @endif

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
                    {{ $editandoSolicitud ? 'Actualizar solicitud' : 'Guardar solicitud' }}
                </button>
            </div>
        </div>
    </div>

    <div x-cloak x-show="detalleSidebar" x-transition.opacity class="fixed inset-0 bg-black/40 z-40 flex justify-end">
        <div class="flex-1" @click="$wire.cerrarDetalle()"></div>

        <div class="w-full max-w-xl bg-white dark:bg-gray-900 shadow-xl h-full border-l flex flex-col">
            <div class="p-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
                <div>
                    <h3 class="text-lg font-semibold text-stone-700 dark:text-white">Detalle de solicitud</h3>
                    @if ($solicitudDetalle)
                        <p class="text-xs text-gray-500 dark:text-gray-400">Solicitud #{{ $solicitudDetalle->id }}</p>
                    @endif
                </div>
                <button @click="$wire.cerrarDetalle()" class="text-gray-500 hover:text-black dark:hover:text-white">x</button>
            </div>

            <div class="flex-1 overflow-y-auto p-4 space-y-6">
                @if ($solicitudDetalle)
                    <div class="space-y-3">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <h4 class="text-lg font-semibold text-stone-700 dark:text-white">{{ $solicitudDetalle->titulo }}</h4>
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    Creada el {{ $solicitudDetalle->created_at?->format('d/m/Y H:i') ?? '-' }}
                                </p>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <span class="px-2.5 py-1 rounded-full text-xs bg-stone-100 text-stone-700 dark:bg-stone-800 dark:text-stone-200">
                                    {{ str_replace('_', ' ', $solicitudDetalle->estado) }}
                                </span>
                                <span class="px-2.5 py-1 rounded-full text-xs bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300">
                                    {{ $solicitudDetalle->prioridad ?: 'Sin prioridad' }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4 space-y-3">
                            <h5 class="font-semibold text-stone-700 dark:text-white">Contexto</h5>
                            <div class="text-sm">
                                <div><span class="font-medium">Cliente:</span> {{ $solicitudDetalle->cliente->nombre ?? ($solicitudDetalle->cliente->razon_social ?? '-') }}</div>
                                <div><span class="font-medium">Origen:</span> {{ ucfirst($solicitudDetalle->origen) }}</div>
                                <div><span class="font-medium">Responsable:</span> {{ $solicitudDetalle->responsable?->name ?? '-' }}</div>
                                <div><span class="font-medium">Tipo:</span> {{ $solicitudDetalle->tipoSolicitud?->nombre ?? 'General' }}</div>
                                <div><span class="font-medium">Obligación:</span> {{ $solicitudDetalle->obligacion_etiqueta }}</div>
                            </div>
                        </div>

                        <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4 space-y-3">
                            <h5 class="font-semibold text-stone-700 dark:text-white">Trazabilidad</h5>
                            <div class="text-sm">
                                <div><span class="font-medium">Creada por:</span> {{ $solicitudDetalle->creadoPor?->name ?? '-' }}</div>
                                <div><span class="font-medium">Modo:</span> {{ ucfirst($solicitudDetalle->modo_solicitud) }}</div>
                                <div><span class="font-medium">Tipo ID:</span> {{ $solicitudDetalle->tipo_solicitud_id ?: '-' }}</div>
                                <div><span class="font-medium">Relación obligación:</span> {{ $solicitudDetalle->obligacion_cliente_contador_id ?: 'Sin relación' }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4 space-y-3">
                        <h5 class="font-semibold text-stone-700 dark:text-white">Descripción</h5>
                        <p class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-line">{{ $solicitudDetalle->descripcion ?: 'Sin descripción.' }}</p>
                    </div>

                    @if ($solicitudDetalle->modo_solicitud === 'definida')
                        <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4 space-y-3">
                            <h5 class="font-semibold text-stone-700 dark:text-white">Formulario base</h5>
                            <p class="text-sm text-gray-600 dark:text-gray-300">
                                Tipo: {{ $solicitudDetalle->tipoSolicitud?->nombre ?? ($solicitudDetalle->plantilla_snapshot['nombre'] ?? '-') }}
                            </p>
                            <p class="text-sm text-gray-600 dark:text-gray-300">
                                Datos capturados y requerimientos se integrarán en este bloque.
                            </p>
                        </div>
                    @endif

                    <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4 space-y-3">
                        <h5 class="font-semibold text-stone-700 dark:text-white">Historial</h5>
                        <div class="space-y-2 text-sm">
                            <div class="flex items-start gap-3">
                                <div class="mt-1 h-2.5 w-2.5 rounded-full bg-amber-500"></div>
                                <div>
                                    <div class="font-medium text-gray-900 dark:text-white">Solicitud creada</div>
                                    <div class="text-gray-500 dark:text-gray-400">
                                        {{ $solicitudDetalle->created_at?->format('d/m/Y H:i') ?? '-' }}
                                        por {{ $solicitudDetalle->creadoPor?->name ?? 'usuario no disponible' }}
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-start gap-3">
                                <div class="mt-1 h-2.5 w-2.5 rounded-full bg-stone-400"></div>
                                <div>
                                    <div class="font-medium text-gray-900 dark:text-white">Responsable asignado</div>
                                    <div class="text-gray-500 dark:text-gray-400">
                                        {{ $solicitudDetalle->responsable?->name ?? '-' }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-lg border border-dashed border-gray-300 dark:border-gray-700 p-4 space-y-4">
                        <div class="flex items-center justify-between gap-3">
                            <h5 class="font-semibold text-stone-700 dark:text-white">Requerimientos</h5>
                            <button wire:click="abrirFormularioRequerimiento"
                                class="px-3 py-2 text-xs rounded bg-amber-600 text-white hover:bg-amber-700">
                                + Nuevo requerimiento
                            </button>
                        </div>

                        @if ($requerimientoFormVisible)
                            <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4 space-y-4">
                                <div class="flex items-center justify-between gap-3">
                                    <h6 class="font-semibold text-stone-700 dark:text-white">
                                        {{ $editandoRequerimiento ? 'Editar requerimiento' : 'Nuevo requerimiento' }}
                                    </h6>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium mb-1">Dirigido a</label>
                                        <select wire:model.live="requerimiento_destinatario_tipo"
                                            class="w-full px-3 py-2 border rounded-md dark:bg-gray-700 dark:text-white border-gray-300 dark:border-gray-600 focus:border-amber-600 focus:ring focus:ring-amber-500/40 focus:outline-none">
                                            <option value="cliente">Cliente</option>
                                            <option value="interno">Usuario interno</option>
                                        </select>
                                        @error('requerimiento_destinatario_tipo') <div class="text-red-500 text-xs mt-1">{{ $message }}</div> @enderror
                                    </div>

                                    @if ($requerimiento_destinatario_tipo === 'interno')
                                        <div>
                                            <label class="block text-sm font-medium mb-1">Usuario interno</label>
                                            <select wire:model="requerimiento_destinatario_user_id"
                                                class="w-full px-3 py-2 border rounded-md dark:bg-gray-700 dark:text-white border-gray-300 dark:border-gray-600 focus:border-amber-600 focus:ring focus:ring-amber-500/40 focus:outline-none">
                                                <option value="">Seleccione...</option>
                                                @foreach ($usuariosInternosRequerimiento as $usuarioInterno)
                                                    <option value="{{ $usuarioInterno->id }}">{{ $usuarioInterno->name }}</option>
                                                @endforeach
                                            </select>
                                            @error('requerimiento_destinatario_user_id') <div class="text-red-500 text-xs mt-1">{{ $message }}</div> @enderror
                                        </div>
                                    @endif
                                </div>

                                <div>
                                    <label class="block text-sm font-medium mb-1">Titulo</label>
                                    <input type="text" wire:model="requerimiento_titulo"
                                        class="w-full px-3 py-2 border rounded-md dark:bg-gray-700 dark:text-white border-gray-300 dark:border-gray-600 focus:border-amber-600 focus:ring focus:ring-amber-500/40 focus:outline-none">
                                    @error('requerimiento_titulo') <div class="text-red-500 text-xs mt-1">{{ $message }}</div> @enderror
                                </div>

                                <div>
                                    <label class="block text-sm font-medium mb-1">Descripcion</label>
                                    <textarea wire:model="requerimiento_descripcion" rows="3"
                                        class="w-full px-3 py-2 border rounded-md dark:bg-gray-700 dark:text-white border-gray-300 dark:border-gray-600 focus:border-amber-600 focus:ring focus:ring-amber-500/40 focus:outline-none"></textarea>
                                    @error('requerimiento_descripcion') <div class="text-red-500 text-xs mt-1">{{ $message }}</div> @enderror
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium mb-1">Fecha limite</label>
                                        <input type="date" wire:model="requerimiento_fecha_limite"
                                            class="w-full px-3 py-2 border rounded-md dark:bg-gray-700 dark:text-white border-gray-300 dark:border-gray-600 focus:border-amber-600 focus:ring focus:ring-amber-500/40 focus:outline-none">
                                        @error('requerimiento_fecha_limite') <div class="text-red-500 text-xs mt-1">{{ $message }}</div> @enderror
                                    </div>
                                </div>

                                <div class="flex justify-end gap-2">
                                    <button wire:click="cerrarFormularioRequerimiento"
                                        class="px-4 py-2 border rounded text-sm dark:border-gray-600 dark:text-white">
                                        Cancelar
                                    </button>
                                    <button wire:click="guardarRequerimiento"
                                        class="bg-amber-600 text-white px-4 py-2 rounded hover:bg-amber-700 text-sm">
                                        {{ $editandoRequerimiento ? 'Actualizar requerimiento' : 'Guardar requerimiento' }}
                                    </button>
                                </div>
                            </div>
                        @endif

                        <div class="space-y-3">
                            @forelse ($solicitudDetalle->requerimientos as $requerimiento)
                                <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4 space-y-2">
                                    <div class="flex flex-wrap items-start justify-between gap-3">
                                        <div>
                                            <div class="font-medium text-gray-900 dark:text-white">{{ $requerimiento->titulo }}</div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                                {{ ucfirst($requerimiento->destinatario_tipo) }}
                                                @if ($requerimiento->destinatario_tipo === 'interno')
                                                    · {{ $requerimiento->destinatario?->name ?? 'Sin usuario' }}
                                                @endif
                                            </div>
                                        </div>
                                        <div class="flex flex-wrap gap-2">
                                            <span class="px-2.5 py-1 rounded-full text-xs bg-stone-100 text-stone-700 dark:bg-stone-800 dark:text-stone-200">
                                                {{ str_replace('_', ' ', $requerimiento->estado) }}
                                            </span>
                                            @if ($requerimiento->fecha_limite)
                                                <span class="px-2.5 py-1 rounded-full text-xs bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300">
                                                    Limite {{ $requerimiento->fecha_limite?->format('d/m/Y') }}
                                                </span>
                                            @endif
                                            <x-action-icon icon="edit" label="Editar" variant="primary"
                                                wire:click="editarRequerimiento({{ $requerimiento->id }})" />
                                            <x-action-icon icon="trash" label="Eliminar" variant="danger"
                                                wire:click="confirmarEliminarRequerimiento({{ $requerimiento->id }})" />
                                            @if ($solicitudDetalle->responsable_user_id === auth()->id() && $requerimiento->estado === 'respondido')
                                                <x-action-icon icon="check" label="Validar respuesta" variant="primary"
                                                    wire:click="validarRespuestaRequerimiento({{ $requerimiento->id }})" />
                                                <x-action-icon icon="eye" label="Rechazar respuesta" variant="danger"
                                                    wire:click="mostrarRechazoRespuesta({{ $requerimiento->id }})" />
                                            @endif
                                        </div>
                                    </div>

                                    @if ($requerimiento->descripcion)
                                        <p class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-line">{{ $requerimiento->descripcion }}</p>
                                    @endif

                                    @if ($requerimiento->respuesta_texto)
                                        <div class="rounded-lg border border-emerald-200 dark:border-emerald-800 p-3 space-y-2">
                                            <div class="text-sm font-medium text-emerald-700 dark:text-emerald-300">Respuesta</div>
                                            <p class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-line">{{ $requerimiento->respuesta_texto }}</p>
                                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                                Respondido por {{ $requerimiento->respondidoPor?->name ?? '-' }} el {{ $requerimiento->respondido_at?->format('d/m/Y H:i') ?? '-' }}
                                            </div>
                                        </div>
                                    @endif

                                    @if ($requerimiento->estado === 'rechazado' && $requerimiento->comentario_validacion)
                                        <div class="rounded-lg border border-red-200 dark:border-red-800 p-3 space-y-2">
                                            <div class="text-sm font-medium text-red-700 dark:text-red-300">Respuesta rechazada</div>
                                            <p class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-line">{{ $requerimiento->comentario_validacion }}</p>
                                        </div>
                                    @endif

                                    @if ($requerimiento->archivos->isNotEmpty())
                                        <div class="rounded-lg border border-dashed border-gray-300 dark:border-gray-700 p-3">
                                            <div class="text-sm font-medium mb-2 text-stone-700 dark:text-white">Documentos de respuesta</div>
                                            <ul class="space-y-2 text-sm">
                                                @foreach ($requerimiento->archivos as $archivo)
                                                    <li>
                                                        <a href="{{ $archivo->archivo ? Storage::disk('public')->url($archivo->archivo) : $archivo->archivo_drive_url }}"
                                                            target="_blank"
                                                            class="text-amber-700 hover:underline dark:text-amber-300">
                                                            {{ $archivo->nombre }}
                                                        </a>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    @endif

                                    @if (($mostrarRechazoRequerimiento[$requerimiento->id] ?? false) === true)
                                        <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4 space-y-3">
                                            <div>
                                                <label class="block text-sm font-medium mb-1">Motivo del rechazo</label>
                                                <textarea wire:model="comentarioRechazoRequerimiento.{{ $requerimiento->id }}" rows="3"
                                                    class="w-full px-3 py-2 border rounded-md dark:bg-gray-700 dark:text-white border-gray-300 dark:border-gray-600 focus:border-amber-600 focus:ring focus:ring-amber-500/40 focus:outline-none"></textarea>
                                                @error('comentarioRechazoRequerimiento.' . $requerimiento->id) <div class="text-red-500 text-xs mt-1">{{ $message }}</div> @enderror
                                            </div>
                                            <div class="flex justify-end gap-2">
                                                <button wire:click="cancelarRechazoRespuesta({{ $requerimiento->id }})"
                                                    class="px-4 py-2 border rounded text-sm dark:border-gray-600 dark:text-white">
                                                    Cancelar
                                                </button>
                                                <button wire:click="rechazarRespuestaRequerimiento({{ $requerimiento->id }})"
                                                    class="bg-amber-600 text-white px-4 py-2 rounded hover:bg-amber-700 text-sm">
                                                    Rechazar respuesta
                                                </button>
                                            </div>
                                        </div>
                                    @endif

                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                        Creado por {{ $requerimiento->creadoPor?->name ?? '-' }} el {{ $requerimiento->created_at?->format('d/m/Y H:i') ?? '-' }}
                                    </div>
                                </div>
                            @empty
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    Aun no hay requerimientos registrados en esta solicitud.
                                </p>
                            @endforelse
                        </div>
                    </div>
                @else
                    <div class="text-sm text-gray-500 dark:text-gray-400">
                        No se pudo cargar el detalle de la solicitud.
                    </div>
                @endif
            </div>
        </div>
    </div>

    <x-confirmacion-eliminacion
        :visible="$confirmarCancelacion"
        closeFlag="confirmarCancelacion"
        action="cancelarSolicitudConfirmada"
        titulo="Cancelar solicitud"
        mensaje="Deseas cancelar esta solicitud? Se cerrara y dejara de estar activa para seguimiento normal."
        confirmLabel="Si, cancelar"
        cancelLabel="Volver" />

    <x-confirmacion-eliminacion
        :visible="$confirmarEliminarRequerimiento"
        closeFlag="confirmarEliminarRequerimiento"
        action="eliminarRequerimientoConfirmado"
        titulo="Eliminar requerimiento"
        mensaje="Deseas eliminar este requerimiento? Tambien se eliminaran sus archivos adjuntos en el sistema."
        confirmLabel="Si, eliminar"
        cancelLabel="Volver" />
</div>
