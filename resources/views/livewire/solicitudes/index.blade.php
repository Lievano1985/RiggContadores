<div class="space-y-4 rounded-lg bg-white p-6 text-gray-900 shadow dark:bg-gray-900 dark:text-white">
    <div class="flex flex-wrap items-center justify-between gap-2">
        <h2 class="text-xl font-bold text-stone-600 dark:text-white">{{ $tituloModulo }}</h2>
        @if ($puedeCrearSolicitud)
            <button wire:click="abrirSidebarCrear" class="rounded bg-amber-600 px-4 py-2 text-white hover:bg-amber-700">
                + Nueva solicitud
            </button>
        @endif
    </div>

    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
        <div class="flex flex-wrap items-center gap-2">
            <input type="text" placeholder="Buscar por cliente o titulo" wire:model.live="buscar"
                class="rounded border px-3 py-2 focus:border-amber-600 focus:outline-none focus:ring focus:ring-amber-500/40 dark:bg-gray-700 dark:text-white">

            <select wire:model.live="estado"
                class="rounded border px-3 py-2 focus:border-amber-600 focus:outline-none focus:ring focus:ring-amber-500/40 dark:bg-gray-700 dark:text-white">
                <option value="activos">Estado (activos)</option>
                <option value="">Estado (todos)</option>
                <option value="abierta">Abierta</option>
                <option value="en_proceso">En proceso</option>
                <option value="pendiente_cliente">En revision</option>
                <option value="resuelto">Resuelto</option>
                <option value="cerrada">Cerrada</option>
                <option value="cancelada">Cancelada</option>
            </select>

            <select wire:model.live="responsable"
                class="rounded border px-3 py-2 focus:border-amber-600 focus:outline-none focus:ring focus:ring-amber-500/40 dark:bg-gray-700 dark:text-white">
                <option value="">Responsable (todos)</option>
                @foreach ($responsables as $item)
                    <option value="{{ $item->id }}">{{ $item->name }}</option>
                @endforeach
            </select>

            <select wire:model.live="perPage"
                class="rounded border px-3 py-2 focus:border-amber-600 focus:outline-none focus:ring focus:ring-amber-500/40 dark:bg-gray-700 dark:text-white">
                @foreach ($perPageOptions as $option)
                    <option value="{{ $option }}">{{ $option === 'all' ? 'Todos' : $option }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="overflow-x-auto rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">
        <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
            <thead class="bg-stone-100 dark:bg-stone-900">
                <tr>
                    <x-sortable-th field="cliente" label="Cliente" :sort-field="$sortField" :sort-direction="$sortDirection" />
                    <x-sortable-th field="titulo" label="Titulo" :sort-field="$sortField" :sort-direction="$sortDirection" />
                    <x-sortable-th field="estado" label="Estado" :sort-field="$sortField" :sort-direction="$sortDirection" />
                    <th class="px-4 py-2 text-left text-xs font-semibold">Creada por</th>
                    <x-sortable-th field="responsable" label="Atiende" :sort-field="$sortField" :sort-direction="$sortDirection" />
                    <th class="px-4 py-2 text-left text-xs font-semibold">Vencimiento</th>
                    <th class="px-4 py-2 text-left text-xs font-semibold">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                @forelse ($solicitudes as $solicitud)
                    @php
                        $solicitudEstadoClass = match ($solicitud->estado) {
                            'abierta' => 'bg-sky-100 text-sky-700 dark:bg-sky-900/40 dark:text-sky-300',
                            'en_proceso' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300',
                            'pendiente_cliente' => 'bg-violet-100 text-violet-700 dark:bg-violet-900/40 dark:text-violet-300',
                            'resuelto' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300',
                            'cerrada' => 'bg-stone-200 text-stone-700 dark:bg-stone-700 dark:text-stone-100',
                            'cancelada' => 'bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-300',
                            default => 'bg-stone-100 text-stone-700 dark:bg-stone-800 dark:text-stone-200',
                        };
                    @endphp
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/60">
                        <td class="px-4 py-2">{{ $solicitud->cliente->nombre ?? ($solicitud->cliente->razon_social ?? '-') }}</td>
                        <td class="px-4 py-2">
                            <div class="font-medium">{{ $solicitud->titulo }}</div>
                        </td>
                        <td class="px-4 py-2">
                            <span class="inline-flex whitespace-nowrap rounded-full px-2.5 py-1 text-xs font-medium {{ $solicitudEstadoClass }}">
                                {{ $solicitud->estado === 'pendiente_cliente' ? 'En revision' : str_replace('_', ' ', $solicitud->estado) }}
                            </span>
                        </td>
                        <td class="px-4 py-2">{{ $solicitud->creadoPor->name ?? '-' }}</td>
                        <td class="px-4 py-2">
                            {{ $solicitud->origen === 'cliente' ? 'Cliente' : ($solicitud->responsable->name ?? '-') }}
                        </td>
                        <td class="px-4 py-2 whitespace-nowrap">{{ $solicitud->fecha_vencimiento?->format('d/m/Y') ?? '-' }}</td>
                        <td class="px-4 py-2 whitespace-nowrap">
                            <div class="flex items-center gap-2">
                                <x-action-icon icon="eye" label="Detalle" variant="primary"
                                    wire:click="abrirDetalle({{ $solicitud->id }})" />
                                @if ($solicitud->creado_por_user_id === auth()->id())
                                    <x-action-icon icon="edit" label="Editar" variant="primary"
                                        wire:click="editarSolicitud({{ $solicitud->id }})" />
                                @endif
                                @if (!in_array($solicitud->estado, ['cerrada', 'cancelada']) && ($usuarioEsAdminOSupervisor || $solicitud->creado_por_user_id === auth()->id()))
                                    <x-action-icon icon="trash" label="Cancelar" variant="danger"
                                        wire:click="confirmarCancelacionSolicitud({{ $solicitud->id }})" />
                                @endif
                            </div>
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

    @if ($sidebarVisible)
    <div class="fixed inset-0 z-40 flex justify-end bg-black/40">
        <button type="button" wire:click="cerrarSidebar" class="flex-1 cursor-default"></button>
        <div class="flex h-full w-full max-w-xl flex-col border-l bg-white shadow-xl dark:bg-gray-900">
            <div class="flex items-center justify-between border-b border-gray-200 p-4 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-stone-700 dark:text-white">
                    {{ $editandoSolicitud ? 'Editar solicitud' : 'Nueva solicitud' }}
                </h3>
                <button type="button" wire:click="cerrarSidebar" class="text-gray-500 hover:text-black dark:hover:text-white">x</button>
            </div>

            <div x-on:enfocar-formulario-requerimiento.window="$nextTick(() => $refs.requerimientoForm?.scrollIntoView({ behavior: 'smooth', block: 'start' }))"
                class="flex-1 space-y-6 overflow-y-auto p-4">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div class="md:col-span-2">
                        <label class="mb-1 block text-sm font-medium">Cliente</label>
                        <div
                            x-data="{
                                open: false,
                                search: '',
                                selectedId: @entangle('cliente_id_form').live,
                                options: @js($clientesDisponibles),
                                filteredOptions() {
                                    const term = this.search.toLowerCase().trim();
                                    if (!term) return this.options;
                                    return this.options.filter(option => (option.nombre || '').toLowerCase().includes(term));
                                },
                                selectedOption() {
                                    return this.options.find(option => Number(option.id) === Number(this.selectedId)) || null;
                                },
                                syncSearch() {
                                    const selected = this.selectedOption();
                                    this.search = selected ? selected.nombre : '';
                                },
                                selectOption(option) {
                                    this.selectedId = Number(option.id);
                                    this.search = option.nombre;
                                    this.open = false;
                                },
                                clearSelection() {
                                    this.selectedId = null;
                                    this.search = '';
                                    this.open = false;
                                }
                            }"
                            x-init="syncSearch()"
                            x-effect="syncSearch()"
                            class="relative">
                            <input type="text"
                                x-model="search"
                                @focus="open = true"
                                @click="open = true"
                                @input="open = true"
                                @keydown.escape.window="open = false"
                                placeholder="Buscar cliente..."
                                class="w-full rounded-md border border-gray-300 px-3 py-2 pr-20 focus:border-amber-600 focus:outline-none focus:ring focus:ring-amber-500/40 dark:border-gray-600 dark:bg-gray-700 dark:text-white">

                            <div x-show="!search && !selectedId"
                                x-cloak
                                class="pointer-events-none absolute inset-y-0 right-10 flex items-center text-xs text-gray-400">
                                Buscar
                            </div>

                            <button type="button"
                                x-show="selectedId"
                                x-cloak
                                @click="clearSelection()"
                                class="absolute inset-y-0 right-2 my-auto h-7 rounded px-2 text-xs text-gray-500 hover:bg-gray-100 hover:text-gray-700 dark:hover:bg-gray-600 dark:hover:text-white">
                                Limpiar
                            </button>

                            <div x-cloak
                                x-show="open"
                                @click.outside="open = false"
                                class="absolute z-20 mt-1 max-h-64 w-full overflow-y-auto rounded-md border border-gray-200 bg-white shadow-lg dark:border-gray-700 dark:bg-gray-800">
                                <template x-if="filteredOptions().length === 0">
                                    <div class="px-3 py-2 text-sm text-gray-500 dark:text-gray-400">
                                        Sin coincidencias.
                                    </div>
                                </template>

                                <template x-for="option in filteredOptions()" :key="option.id">
                                    <button type="button"
                                        @click="selectOption(option)"
                                        class="flex w-full items-center justify-between px-3 py-2 text-left text-sm hover:bg-amber-50 dark:hover:bg-gray-700">
                                        <span x-text="option.nombre" class="truncate"></span>
                                        <span x-show="Number(selectedId) === Number(option.id)" class="text-xs text-amber-600 dark:text-amber-300">Seleccionado</span>
                                    </button>
                                </template>
                            </div>
                        </div>
                        @error('cliente_id_form') <div class="mt-1 text-xs text-red-500">{{ $message }}</div> @enderror

                        @php
                            $puedeCambiarResponsableAsignado = auth()->check() && auth()->user()->hasAnyRole(['admin_despacho', 'supervisor', 'super_admin']);
                        @endphp
                        <div class="mt-3 rounded-md border border-slate-200 bg-slate-50 px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-800/60">
                            <div class="flex items-center justify-between gap-3">
                                <div class="font-medium text-stone-700 dark:text-white">Usuario asignado</div>
                                @if ($puedeCambiarResponsableAsignado && $cliente_id_form)
                                    <button type="button"
                                        wire:click="{{ $mostrarCambioResponsableForm ? 'cancelarCambioResponsableAsignadoForm' : 'abrirCambioResponsableAsignadoForm' }}"
                                        class="rounded border border-amber-300 px-2.5 py-1 text-xs font-medium text-amber-700 hover:bg-amber-50 dark:border-amber-700 dark:text-amber-300 dark:hover:bg-amber-900/20">
                                        {{ $mostrarCambioResponsableForm ? 'Cancelar' : 'Cambiar' }}
                                    </button>
                                @endif
                            </div>
                            @if ($responsableAsignadoSeleccionado)
                                <div class="mt-1 text-gray-700 dark:text-gray-300">{{ $responsableAsignadoSeleccionado->name }}</div>
                            @elseif ($responsableClienteSeleccionado)
                                <div class="mt-1 text-gray-700 dark:text-gray-300">{{ $responsableClienteSeleccionado->name }}</div>
                            @elseif ($cliente_id_form)
                                <div class="mt-1 text-rose-600 dark:text-rose-300">Este cliente no tiene usuario encargado asignado.</div>
                            @else
                                <div class="mt-1 text-gray-500 dark:text-gray-400">Selecciona un cliente para ver su usuario encargado.</div>
                            @endif

                            @if ($puedeCambiarResponsableAsignado && $mostrarCambioResponsableForm && $cliente_id_form)
                                <div class="mt-3 rounded-md border border-amber-200 bg-white p-3 dark:border-amber-800 dark:bg-gray-900">
                                    <label class="mb-1 block text-sm font-medium">Selecciona el usuario</label>
                                    <select wire:model="responsable_user_id_selector_form"
                                        class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-amber-600 focus:outline-none focus:ring focus:ring-amber-500/40 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                        <option value="">Seleccione...</option>
                                        @foreach ($responsables as $item)
                                            <option value="{{ $item->id }}">{{ $item->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('responsable_user_id_selector_form') <div class="mt-1 text-xs text-red-500">{{ $message }}</div> @enderror
                                    @error('responsable_user_id_form') <div class="mt-1 text-xs text-red-500">{{ $message }}</div> @enderror
                                    <div class="mt-3 flex justify-end gap-2">
                                        <button type="button"
                                            wire:click="cancelarCambioResponsableAsignadoForm"
                                            class="rounded border px-3 py-1.5 text-xs dark:border-gray-600 dark:text-white">
                                            Cancelar
                                        </button>
                                        <button type="button"
                                            wire:click="guardarCambioResponsableAsignadoForm"
                                            class="rounded bg-amber-600 px-3 py-1.5 text-xs text-white hover:bg-amber-700">
                                            Guardar
                                        </button>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium">Dirigida a</label>
                        <select wire:model.live="origen_form"
                            class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-amber-600 focus:outline-none focus:ring focus:ring-amber-500/40 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                            <option value="despacho">Despacho</option>
                            @if ($puedeCrearSolicitudParaCliente)
                                <option value="cliente">Cliente</option>
                            @endif
                        </select>
                        @error('origen_form') <div class="mt-1 text-xs text-red-500">{{ $message }}</div> @enderror
                        @unless ($puedeCrearSolicitudParaCliente)
                            <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                Las solicitudes dirigidas al cliente solo las puede crear el usuario encargado de ese cliente o el administrador.
                            </div>
                        @endunless
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium">Modo de solicitud</label>
                        <select wire:model.live="modo_solicitud_form"
                            class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-amber-600 focus:outline-none focus:ring focus:ring-amber-500/40 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                            <option value="general">General</option>
                            <option value="definida">Definida</option>
                        </select>
                        @error('modo_solicitud_form') <div class="mt-1 text-xs text-red-500">{{ $message }}</div> @enderror
                    </div>

                    @if ($modo_solicitud_form === 'definida')
                        <div>
                            <label class="mb-1 block text-sm font-medium">Tipo de solicitud</label>
                            <select wire:model.live="tipo_solicitud_id_form"
                                class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-amber-600 focus:outline-none focus:ring focus:ring-amber-500/40 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                <option value="">Seleccione...</option>
                                @foreach ($tiposDisponibles as $tipo)
                                    <option value="{{ $tipo['id'] }}">{{ $tipo['nombre'] }}</option>
                                @endforeach
                            </select>
                            @error('tipo_solicitud_id_form') <div class="mt-1 text-xs text-red-500">{{ $message }}</div> @enderror
                        </div>
                    @endif

                    <div>
                        <label class="mb-1 block text-sm font-medium">Relacion con obligacion</label>
                        <select wire:model.live="relacion_obligacion_form"
                            class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-amber-600 focus:outline-none focus:ring focus:ring-amber-500/40 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                            <option value="sin_relacion">Sin relacion</option>
                            <option value="con_relacion">Ligada a obligacion</option>
                        </select>
                    </div>

                    @if ($relacion_obligacion_form === 'con_relacion')
                        <div class="grid grid-cols-1 gap-4 md:col-span-2 md:grid-cols-3">
                            <div>
                                <label class="mb-1 block text-sm font-medium">Anio</label>
                                <input type="number" min="2020" max="2100" wire:model.live="periodo_anio_form"
                                    class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-amber-600 focus:outline-none focus:ring focus:ring-amber-500/40 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                            </div>

                            <div>
                                <label class="mb-1 block text-sm font-medium">Mes</label>
                                <select wire:model.live="periodo_mes_form"
                                    class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-amber-600 focus:outline-none focus:ring focus:ring-amber-500/40 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                    <option value="">Seleccione...</option>
                                    @foreach ([1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril', 5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto', 9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'] as $mesNumero => $mesNombre)
                                        <option value="{{ $mesNumero }}">{{ $mesNombre }}</option>
                                    @endforeach
                                </select>
                                @error('periodo_mes_form') <div class="mt-1 text-xs text-red-500">{{ $message }}</div> @enderror
                            </div>

                            <div>
                                <label class="mb-1 block text-sm font-medium">Obligacion relacionada</label>
                                <select wire:model.live="obligacion_cliente_contador_id_form"
                                    class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-amber-600 focus:outline-none focus:ring focus:ring-amber-500/40 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                    <option value="">{{ count($obligacionesDisponibles) ? 'Seleccione...' : 'Sin obligaciones en ese periodo' }}</option>
                                    @foreach ($obligacionesDisponibles as $obligacion)
                                        <option value="{{ $obligacion['id'] }}">{{ $obligacion['nombre'] }}</option>
                                    @endforeach
                                </select>
                                @error('obligacion_cliente_contador_id_form') <div class="mt-1 text-xs text-red-500">{{ $message }}</div> @enderror
                            </div>
                        </div>
                    @endif

                    <div class="md:col-span-2">
                        <label class="mb-1 block text-sm font-medium">Titulo</label>
                        <input type="text" wire:model="titulo_form"
                            class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-amber-600 focus:outline-none focus:ring focus:ring-amber-500/40 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        @error('titulo_form') <div class="mt-1 text-xs text-red-500">{{ $message }}</div> @enderror
                    </div>

                    <div class="md:col-span-2">
                        <label class="mb-1 block text-sm font-medium">Descripcion</label>
                        <textarea wire:model="descripcion_form" rows="4"
                            class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-amber-600 focus:outline-none focus:ring focus:ring-amber-500/40 dark:border-gray-600 dark:bg-gray-700 dark:text-white"></textarea>
                        @error('descripcion_form') <div class="mt-1 text-xs text-red-500">{{ $message }}</div> @enderror
                    </div>

                    <div class="md:col-span-2">
                        <label class="mb-1 block text-sm font-medium">Archivos de apoyo</label>
                        <input type="file" wire:model="solicitud_archivos_form" multiple
                            class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-amber-600 focus:outline-none focus:ring focus:ring-amber-500/40 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            Opcional. Puedes adjuntar archivos guÃ­a o soporte para quien atenderÃ¡ el requerimiento.
                        </div>
                        @error('solicitud_archivos_form.*') <div class="mt-1 text-xs text-red-500">{{ $message }}</div> @enderror

                        @if ($editandoSolicitud && $solicitudEditandoActual && $solicitudEditandoActual->archivos->isNotEmpty())
                            <div class="mt-3 space-y-2">
                                <div class="text-xs font-medium text-stone-700 dark:text-white">Archivos actuales</div>
                                @foreach ($solicitudEditandoActual->archivos as $archivo)
                                    <a href="{{ $archivo->archivo ? \Illuminate\Support\Facades\Storage::disk('public')->url($archivo->archivo) : $archivo->archivo_drive_url }}"
                                        target="_blank"
                                        class="block text-sm text-amber-600 hover:underline dark:text-amber-300">
                                        {{ $archivo->nombre }}
                                    </a>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium">Prioridad</label>
                        <select wire:model="prioridad_form"
                            class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-amber-600 focus:outline-none focus:ring focus:ring-amber-500/40 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                            <option value="">Seleccione...</option>
                            <option value="baja">Baja</option>
                            <option value="media">Media</option>
                            <option value="alta">Alta</option>
                            <option value="urgente">Urgente</option>
                        </select>
                        @error('prioridad_form') <div class="mt-1 text-xs text-red-500">{{ $message }}</div> @enderror
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium">Fecha de resultado</label>
                        <input type="date" wire:model="fecha_resultado_form"
                            class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-amber-600 focus:outline-none focus:ring focus:ring-amber-500/40 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        @error('fecha_resultado_form') <div class="mt-1 text-xs text-red-500">{{ $message }}</div> @enderror
                    </div>

                </div>

                @if ($modo_solicitud_form === 'definida' && $tipoSeleccionado)
                    <div class="space-y-3 rounded border p-4 shadow-sm dark:border-gray-700">
                        <h4 class="font-semibold text-stone-700 dark:text-white">Plantilla del tipo seleccionado</h4>
                        <p class="text-sm">
                            <strong>Secciones del formulario:</strong>
                            {{ !empty($tipoSeleccionado->configuracion_formulario['secciones']) ? count($tipoSeleccionado->configuracion_formulario['secciones']) : 0 }}
                        </p>
                    </div>
                @endif
            </div>

            <div class="flex justify-end gap-2 border-t border-gray-200 p-4 dark:border-gray-700">
                <button wire:click="cerrarSidebar"
                    class="rounded border px-4 py-2 text-sm dark:border-gray-600 dark:text-white">
                    Cancelar
                </button>
                <button wire:click="guardarSolicitud"
                    @click="window.dispatchEvent(new CustomEvent('spinner-on'))"
                    wire:loading.attr="disabled"
                    wire:target="guardarSolicitud"
                    class="rounded bg-amber-600 px-4 py-2 text-sm text-white hover:bg-amber-700">
                    {{ $editandoSolicitud ? 'Actualizar solicitud' : 'Guardar solicitud' }}
                </button>
            </div>
        </div>
    </div>
    @endif

    @if ($detalleSidebarVisible)
    <div class="fixed inset-0 z-40 flex justify-end bg-black/40">
        <button type="button" wire:click="cerrarDetalle" class="flex-1 cursor-default"></button>
        @php
            $solicitudDetalleEstadoClass = null;

            if ($solicitudDetalle) {
                $solicitudDetalleEstadoClass = match ($solicitudDetalle->estado) {
                    'abierta' => 'bg-sky-100 text-sky-700 dark:bg-sky-900/40 dark:text-sky-300',
                    'en_proceso' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300',
                    'pendiente_cliente' => 'bg-violet-100 text-violet-700 dark:bg-violet-900/40 dark:text-violet-300',
                    'resuelto' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300',
                    'cerrada' => 'bg-stone-200 text-stone-700 dark:bg-stone-700 dark:text-stone-100',
                    'cancelada' => 'bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-300',
                    default => 'bg-stone-100 text-stone-700 dark:bg-stone-800 dark:text-stone-200',
                };
            }
        @endphp
        <div class="flex h-full w-full max-w-xl flex-col border-l bg-white shadow-xl dark:bg-gray-900">
            <div class="flex items-center justify-between border-b border-gray-200 p-4 dark:border-gray-700">
                <div class="min-w-0">
                    <h3 class="truncate text-lg font-semibold text-stone-700 dark:text-white">
                        {{ $solicitudDetalle ? 'Detalles de la solicitud: ' . $solicitudDetalle->titulo : 'Detalles de la solicitud' }}
                    </h3>
                    @if ($solicitudDetalle)
                        <div class="mt-1 flex flex-wrap items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
                            <span>Creada el {{ $solicitudDetalle->created_at?->format('d/m/Y H:i') ?? '-' }}</span>
                            <span class="rounded-full px-2.5 py-1 text-xs font-medium {{ $solicitudDetalleEstadoClass }}">
                                {{ $solicitudDetalle->estado === 'pendiente_cliente' ? 'En revision' : str_replace('_', ' ', $solicitudDetalle->estado) }}
                            </span>
                            <span class="rounded-full bg-amber-100 px-2.5 py-1 text-xs text-amber-700 dark:bg-amber-900/40 dark:text-amber-300">
                                {{ $solicitudDetalle->prioridad ?: 'Sin prioridad' }}
                            </span>
                        </div>
                    @endif
                </div>
                <button type="button" wire:click="cerrarDetalle" class="text-gray-500 hover:text-black dark:hover:text-white">x</button>
            </div>

            <div class="flex-1 space-y-4 overflow-y-auto p-4">
                @if ($solicitudDetalle)
                    <div x-data="{ open: true }"
                        class="overflow-visible rounded-xl border border-stone-200 bg-stone-50/80 shadow-sm dark:border-stone-700 dark:bg-stone-800/60">
                        <div @click="open = !open"
                            class="flex items-center justify-between gap-3 px-4 py-4 text-left hover:bg-stone-100/80 dark:hover:bg-stone-700/40">
                            <div class="min-w-0">
                                <h5 class="font-semibold text-stone-700 dark:text-white">Detalles de la solicitud</h5>
                                <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                    Cliente, origen, responsable y descripcion general
                                </div>
                            </div>
                            <button type="button" @click.stop="open = !open" class="inline-flex items-center gap-1 text-xs font-medium text-stone-600 dark:text-stone-300">
                                <span x-show="!open">Ver mas</span>
                                <span x-show="open">Ocultar</span>
                                <svg class="h-4 w-4 transition-transform duration-200" :class="{ 'rotate-180': open }" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.168l3.71-3.938a.75.75 0 1 1 1.08 1.04l-4.25 4.512a.75.75 0 0 1-1.08 0L5.21 8.27a.75.75 0 0 1 .02-1.06Z" clip-rule="evenodd" />
                                </svg>
                            </button>
                        </div>

                        <div x-show="open" x-transition class="space-y-3 border-t border-stone-200 px-4 py-4 text-sm dark:border-stone-700">
                            <div><span class="font-medium">Cliente:</span> {{ $solicitudDetalle->cliente->nombre ?? ($solicitudDetalle->cliente->razon_social ?? '-') }}</div>
                            <div><span class="font-medium">Origen:</span> {{ ucfirst($solicitudDetalle->origen) }}</div>
                            <div><span class="font-medium">Creada por:</span> {{ $solicitudDetalle->creadoPor?->name ?? '-' }}</div>
                            @if ($solicitudDetalle->responsable?->name)
                                <div><span class="font-medium">Responsable:</span> {{ $solicitudDetalle->responsable->name }}</div>
                            @endif
                            @if (filled($solicitudDetalle->fecha_vencimiento))
                                <div><span class="font-medium">Vencimiento:</span> {{ $solicitudDetalle->fecha_vencimiento?->format('d/m/Y') }}</div>
                            @endif
                            @if (filled($solicitudDetalle->descripcion))
                                <div class="whitespace-pre-line text-gray-700 dark:text-gray-300">{{ $solicitudDetalle->descripcion }}</div>
                            @endif

                            <div class="mt-4 flex flex-wrap justify-end gap-2">
                                @if (!in_array($solicitudDetalle->estado, ['cerrada', 'cancelada']) && ($usuarioEsAdminOSupervisor || $solicitudDetalle->creado_por_user_id === auth()->id()))
                                    <x-action-icon icon="check" label="Cerrar solicitud" variant="success"
                                        wire:click="confirmarCierreSolicitud({{ $solicitudDetalle->id }})" />
                                @endif
                            </div>
                        </div>
                    </div>

                    <div x-data="{ open: true }"
                        class="overflow-visible rounded-xl border border-violet-200 bg-violet-50/60 shadow-sm dark:border-violet-900/60 dark:bg-violet-950/20">
                        <div @click="open = !open"
                            class="flex items-center justify-between gap-3 px-4 py-4 text-left hover:bg-violet-100/70 dark:hover:bg-violet-900/20">
                            <div class="min-w-0">
                                <h5 class="font-semibold text-stone-700 dark:text-white">Requerimientos</h5>
                                <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                    {{ $solicitudDetalle->requerimientos->reject(fn ($requerimiento) => $solicitudDetalle->usaFormularioComoCierre() && $requerimiento->tipo === 'resultado')->count() }} elemento(s)
                                </div>
                            </div>
                            <div class="flex items-center gap-2" @click.stop>
                                @if (($usuarioEsAdminOSupervisor || $solicitudDetalle->creado_por_user_id === auth()->id()) && $solicitudDetalle->modo_solicitud !== 'definida')
                                    <x-action-icon icon="plus" label="Nuevo requerimiento" variant="warning"
                                        wire:click="abrirFormularioRequerimiento" />
                                @endif
                                <button type="button" @click="open = !open" class="inline-flex items-center gap-1 text-xs font-medium text-stone-600 dark:text-stone-300">
                                    <span x-show="!open">Ver mas</span>
                                    <span x-show="open">Ocultar</span>
                                    <svg class="h-4 w-4 transition-transform duration-200" :class="{ 'rotate-180': open }" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                        <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.168l3.71-3.938a.75.75 0 1 1 1.08 1.04l-4.25 4.512a.75.75 0 0 1-1.08 0L5.21 8.27a.75.75 0 0 1 .02-1.06Z" clip-rule="evenodd" />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <div x-show="open" x-transition class="space-y-3 border-t border-violet-200 px-4 py-4 dark:border-violet-900/60">
                            @if ($requerimientoFormVisible)
                                <div class="mb-4 rounded-lg border border-white/70 bg-white/80 p-4 shadow-sm dark:border-stone-700 dark:bg-stone-900/50">
                                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                        <div>
                                            <label class="mb-1 block text-sm font-medium">Dirigido a</label>
                                            <select wire:model.live="requerimiento_destinatario_tipo"
                                                class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-amber-600 focus:outline-none focus:ring focus:ring-amber-500/40 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                                <option value="cliente">Cliente</option>
                                                <option value="interno">Usuario interno</option>
                                            </select>
                                        </div>
                                        @if ($requerimiento_destinatario_tipo === 'interno')
                                            <div>
                                                <label class="mb-1 block text-sm font-medium">Usuario interno</label>
                                                <select wire:model="requerimiento_destinatario_user_id"
                                                    class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-amber-600 focus:outline-none focus:ring focus:ring-amber-500/40 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                                    <option value="">Seleccione...</option>
                                                    @foreach ($usuariosInternosRequerimiento as $usuarioInterno)
                                                        <option value="{{ $usuarioInterno->id }}">{{ $usuarioInterno->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        @endif
                                        <div class="md:col-span-2">
                                            <label class="mb-1 block text-sm font-medium">Titulo</label>
                                            <input type="text" wire:model="requerimiento_titulo"
                                                class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-amber-600 focus:outline-none focus:ring focus:ring-amber-500/40 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                        </div>
                                        <div class="md:col-span-2">
                                            <label class="mb-1 block text-sm font-medium">Descripcion</label>
                                            <textarea wire:model="requerimiento_descripcion" rows="3"
                                                class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-amber-600 focus:outline-none focus:ring focus:ring-amber-500/40 dark:border-gray-600 dark:bg-gray-700 dark:text-white"></textarea>
                                        </div>
                                        <div>
                                            <label class="mb-1 block text-sm font-medium">Fecha limite</label>
                                            <input type="date" wire:model="requerimiento_fecha_limite"
                                                class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-amber-600 focus:outline-none focus:ring focus:ring-amber-500/40 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                        </div>
                                    </div>
                                    <div class="mt-4 flex justify-end gap-2">
                                        <button wire:click="cerrarFormularioRequerimiento"
                                            class="rounded border px-4 py-2 text-sm dark:border-gray-600 dark:text-white">
                                            Cancelar
                                        </button>
                                        <button wire:click="guardarRequerimiento"
                                            @click="window.dispatchEvent(new CustomEvent('spinner-on'))"
                                            wire:loading.attr="disabled"
                                            wire:target="guardarRequerimiento"
                                            class="rounded bg-amber-600 px-4 py-2 text-sm text-white hover:bg-amber-700">
                                            {{ $editandoRequerimiento ? 'Actualizar requerimiento' : 'Guardar requerimiento' }}
                                        </button>
                                    </div>
                                </div>
                            @endif

                            <div class="space-y-3">
                                @forelse ($solicitudDetalle->requerimientos as $requerimiento)
                                    @continue($solicitudDetalle->usaFormularioComoCierre() && $requerimiento->tipo === 'resultado')
                                    <div class="rounded-lg border border-stone-200 bg-white/80 p-4 dark:border-stone-700 dark:bg-stone-900/50">
                                        <div class="flex items-start justify-between gap-3">
                                            <div class="min-w-0">
                                                <div class="font-semibold text-stone-700 dark:text-white">{{ $requerimiento->titulo }}</div>
                                                <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                                    {{ ucfirst($requerimiento->destinatario_tipo) }}
                                                    @if ($requerimiento->destinatario_tipo === 'interno')
                                                        - {{ $requerimiento->destinatario?->name ?? 'Sin usuario' }}
                                                    @endif
                                                </div>
                                            </div>
                                            <div class="text-right text-xs text-gray-500 dark:text-gray-400">
                                                <div>{{ str_replace('_', ' ', $requerimiento->estado) }}</div>
                                                @if ($requerimiento->fecha_limite)
                                                    <div>{{ $requerimiento->fecha_limite->format('d/m/Y') }}</div>
                                                @endif
                                            </div>
                                        </div>

                                        @if ($requerimiento->descripcion)
                                            <div class="mt-3 whitespace-pre-line text-sm text-gray-700 dark:text-gray-300">
                                                {{ $requerimiento->descripcion }}
                                            </div>
                                        @endif

                                        @if ($requerimiento->respuesta_texto)
                                            <div class="mt-3 rounded border border-emerald-200 bg-emerald-50/70 p-3 text-sm dark:border-emerald-900/40 dark:bg-emerald-950/20">
                                                <div class="font-medium text-emerald-700 dark:text-emerald-300">Respuesta</div>
                                                <div class="mt-1 whitespace-pre-line text-gray-700 dark:text-gray-300">{{ $requerimiento->respuesta_texto }}</div>
                                                @if ($requerimiento->respondido_at)
                                                    <div class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                                                        Respondido por {{ $requerimiento->respondidoPor?->name ?? '-' }} el {{ $requerimiento->respondido_at?->format('d/m/Y H:i') ?? '-' }}
                                                    </div>
                                                @endif
                                            </div>
                                        @endif

                                        @if ($requerimiento->estado === 'rechazado' && $requerimiento->comentario_validacion)
                                            <div class="mt-3 rounded border border-rose-200 bg-rose-50/70 p-3 text-sm dark:border-rose-900/40 dark:bg-rose-950/20">
                                                <div class="font-medium text-rose-700 dark:text-rose-300">Respuesta rechazada</div>
                                                <div class="mt-1 whitespace-pre-line text-gray-700 dark:text-gray-300">{{ $requerimiento->comentario_validacion }}</div>
                                            </div>
                                        @endif

                                        @if (($mostrarRechazoRequerimiento[$requerimiento->id] ?? false) === true)
                                            <div class="mt-3 space-y-3 rounded-lg border border-gray-200 p-4 dark:border-gray-700">
                                                <div>
                                                    <label class="mb-1 block text-sm font-medium">Motivo del rechazo</label>
                                                    <textarea wire:model="comentarioRechazoRequerimiento.{{ $requerimiento->id }}" rows="3"
                                                        class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-amber-600 focus:outline-none focus:ring focus:ring-amber-500/40 dark:border-gray-600 dark:bg-gray-700 dark:text-white"></textarea>
                                                    @error('comentarioRechazoRequerimiento.' . $requerimiento->id) <div class="mt-1 text-xs text-red-500">{{ $message }}</div> @enderror
                                                </div>
                                                <div class="flex justify-end gap-2">
                                                    <button type="button"
                                                        wire:click="cancelarRechazoRespuesta({{ $requerimiento->id }})"
                                                        class="rounded border px-4 py-2 text-sm dark:border-gray-600 dark:text-white">
                                                        Cancelar
                                                    </button>
                                                    <button type="button"
                                                        wire:click="rechazarRespuestaRequerimiento({{ $requerimiento->id }})"
                                                        class="rounded bg-amber-600 px-4 py-2 text-sm text-white hover:bg-amber-700">
                                                        Rechazar respuesta
                                                    </button>
                                                </div>
                                            </div>
                                        @endif

                                        <div class="mt-3 flex justify-end gap-2">
                                            @if (($usuarioEsAdminOSupervisor || $solicitudDetalle->creado_por_user_id === auth()->id()) && $requerimiento->tipo !== 'resultado')
                                                <x-action-icon icon="edit" label="Editar" variant="primary"
                                                    wire:click.stop="editarRequerimiento({{ $requerimiento->id }})" />
                                                <x-action-icon icon="trash" label="Eliminar" variant="danger"
                                                    wire:click.stop="confirmarEliminarRequerimiento({{ $requerimiento->id }})" />
                                            @endif

                                            @if (
                                                $requerimiento->estado === 'respondido' &&
                                                (
                                                    ($requerimiento->tipo !== 'resultado' && $requerimiento->creado_por_user_id === auth()->id()) ||
                                                    ($requerimiento->tipo === 'resultado' && $solicitudDetalle->creado_por_user_id === auth()->id())
                                                )
                                            )
                                                <x-action-icon icon="check" label="Validar respuesta" variant="success"
                                                    wire:click="validarRespuestaRequerimiento({{ $requerimiento->id }})" />
                                                <x-action-icon icon="arrow-uturn-left" label="Rechazar respuesta" variant="warning"
                                                    wire:click="mostrarRechazoRespuesta({{ $requerimiento->id }})" />
                                            @endif
                                        </div>

                                        <div class="mt-3 text-xs text-gray-500 dark:text-gray-400">
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
                    </div>

                    <div x-data="{ open: false }"
                        class="overflow-visible rounded-xl border border-emerald-200 bg-emerald-50/70 shadow-sm dark:border-emerald-900/60 dark:bg-emerald-950/20">
                        <div @click="open = !open"
                            class="flex items-center justify-between gap-3 px-4 py-4 text-left hover:bg-emerald-100/70 dark:hover:bg-emerald-900/20">
                            <div class="min-w-0">
                                <h5 class="font-semibold text-stone-700 dark:text-white">Historial</h5>
                                <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                    {{ $solicitudDetalle->historial->count() }} movimiento(s) registrados
                                </div>
                            </div>
                            <button type="button" @click.stop="open = !open" class="inline-flex items-center gap-1 text-xs font-medium text-stone-600 dark:text-stone-300">
                                <span x-show="!open">Ver mas</span>
                                <span x-show="open">Ocultar</span>
                                <svg class="h-4 w-4 transition-transform duration-200" :class="{ 'rotate-180': open }" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.168l3.71-3.938a.75.75 0 1 1 1.08 1.04l-4.25 4.512a.75.75 0 0 1-1.08 0L5.21 8.27a.75.75 0 0 1 .02-1.06Z" clip-rule="evenodd" />
                                </svg>
                            </button>
                        </div>

                        <div x-show="open" x-transition class="border-t border-emerald-200 px-4 py-4 dark:border-emerald-900/60">
                            <div class="space-y-3 text-sm">
                                @forelse ($solicitudDetalle->historial as $evento)
                                    @php
                                        $eventoColor = match ($evento->tipo) {
                                            'solicitud_creada', 'requerimiento_creado', 'resultado_generado' => 'bg-sky-500',
                                            'solicitud_actualizada', 'requerimiento_actualizado' => 'bg-amber-500',
                                            'requerimiento_respondido', 'resultado_entregado' => 'bg-violet-500',
                                            'requerimiento_validado', 'resultado_validado', 'solicitud_cerrada' => 'bg-emerald-500',
                                            'requerimiento_rechazado', 'resultado_rechazado', 'solicitud_cancelada', 'requerimiento_eliminado' => 'bg-rose-500',
                                            default => 'bg-stone-400',
                                        };
                                        $detalleEvento = match ($evento->tipo) {
                                            'solicitud_creada',
                                            'solicitud_actualizada',
                                            'solicitud_cerrada',
                                            'solicitud_cancelada' => $solicitudDetalle->titulo,
                                            'resultado_entregado',
                                            'resultado_validado',
                                            'resultado_rechazado' => 'Resultado esperado',
                                            'resultado_generado' => 'Asignado a ' . (
                                                $evento->requerimiento?->destinatario_tipo === 'cliente'
                                                    ? 'Cliente'
                                                    : ($evento->requerimiento?->destinatario?->name ?? 'Sin asignar')
                                            ),
                                            'requerimiento_creado',
                                            'requerimiento_actualizado',
                                            'requerimiento_respondido',
                                            'requerimiento_validado',
                                            'requerimiento_rechazado',
                                            'requerimiento_eliminado' => $evento->requerimiento?->titulo,
                                            default => $evento->descripcion,
                                        };
                                    @endphp
                                    <div class="flex items-start gap-3">
                                        <div class="mt-1 h-2.5 w-2.5 shrink-0 rounded-full {{ $eventoColor }}"></div>
                                        <div class="min-w-0">
                                            <div class="font-medium text-gray-900 dark:text-white">{{ $evento->titulo }}</div>
                                            @if (filled($detalleEvento))
                                                <div class="mt-0.5 whitespace-pre-line text-gray-600 dark:text-gray-300">{{ $detalleEvento }}</div>
                                            @elseif ($evento->descripcion)
                                                <div class="mt-0.5 whitespace-pre-line text-gray-600 dark:text-gray-300">{{ $evento->descripcion }}</div>
                                            @endif
                                            <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                                {{ $evento->created_at?->format('d/m/Y H:i') ?? '-' }}
                                                · {{ $evento->user?->name ?? 'Sistema' }}
                                                @if ($evento->requerimiento)
                                                    · {{ $evento->requerimiento->tipo === 'resultado' ? 'Resultado esperado' : 'Req. #' . $evento->requerimiento->id }}
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                @empty
                                    <p class="text-sm text-gray-500 dark:text-gray-400">
                                        Aun no hay movimientos registrados para esta solicitud.
                                    </p>
                                @endforelse
                            </div>
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
    @endif

    <x-confirmacion-eliminacion
        :visible="$confirmarCierre"
        closeFlag="confirmarCierre"
        action="cerrarSolicitudConfirmada"
        titulo="Cerrar solicitud"
        mensaje="Deseas cerrar esta solicitud? Solo se cerrara si no tiene requerimientos pendientes."
        confirmLabel="Si, cerrar"
        cancelLabel="Volver" />

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

