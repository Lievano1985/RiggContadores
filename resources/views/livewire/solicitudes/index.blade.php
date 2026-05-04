@php use Illuminate\Support\Facades\Storage; @endphp

<div x-data="{ sidebar: @entangle('sidebarVisible'), detalleSidebar: @entangle('detalleSidebarVisible') }" class="space-y-4 rounded-lg bg-white p-6 text-gray-900 shadow dark:bg-gray-900 dark:text-white">
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
                    <x-sortable-th field="responsable" label="Responsable" :sort-field="$sortField" :sort-direction="$sortDirection" />
                    <th class="px-4 py-2 text-left text-xs font-semibold">Obligacion</th>
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
                        <td class="px-4 py-2">{{ $solicitud->responsable->name ?? '-' }}</td>
                        <td class="px-4 py-2">{{ $solicitud->obligacion_etiqueta }}</td>
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
                                    <x-action-icon icon="check" label="Cerrar" variant="primary"
                                        wire:click="confirmarCierreSolicitud({{ $solicitud->id }})" />
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

    <div x-cloak x-show="sidebar" x-transition.opacity class="fixed inset-0 z-40 flex justify-end bg-black/40">
        <div class="flex-1" @click="$wire.cerrarSidebar()"></div>
        <div class="flex h-full w-full max-w-xl flex-col border-l bg-white shadow-xl dark:bg-gray-900">
            <div class="flex items-center justify-between border-b border-gray-200 p-4 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-stone-700 dark:text-white">
                    {{ $editandoSolicitud ? 'Editar solicitud' : 'Nueva solicitud' }}
                </h3>
                <button @click="$wire.cerrarSidebar()" class="text-gray-500 hover:text-black dark:hover:text-white">x</button>
            </div>

            <div class="flex-1 space-y-6 overflow-y-auto p-4">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div class="md:col-span-2">
                        <label class="mb-1 block text-sm font-medium">Cliente</label>
                        <select wire:model.live="cliente_id_form"
                            class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-amber-600 focus:outline-none focus:ring focus:ring-amber-500/40 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                            <option value="">Seleccione...</option>
                            @foreach ($clientesDisponibles as $cliente)
                                <option value="{{ $cliente['id'] }}">{{ $cliente['nombre'] }}</option>
                            @endforeach
                        </select>
                        @error('cliente_id_form') <div class="mt-1 text-xs text-red-500">{{ $message }}</div> @enderror
                    </div>

                    <input type="hidden" wire:model="origen_form">

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

                    <div class="md:col-span-2">
                        <label class="mb-1 block text-sm font-medium">Responsable asignado</label>
                        <div class="w-full rounded-md border border-gray-300 bg-gray-50 px-3 py-2 text-gray-800 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100">
                            {{ $responsableClienteSeleccionado?->name ?? 'Seleccione un cliente con responsable asignado' }}
                        </div>
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
                    class="rounded bg-amber-600 px-4 py-2 text-sm text-white hover:bg-amber-700">
                    {{ $editandoSolicitud ? 'Actualizar solicitud' : 'Guardar solicitud' }}
                </button>
            </div>
        </div>
    </div>

    <div x-cloak x-show="detalleSidebar" x-transition.opacity class="fixed inset-0 z-40 flex justify-end bg-black/40">
        <div class="flex-1" @click="$wire.cerrarDetalle()"></div>
        <div class="flex h-full w-full max-w-xl flex-col border-l bg-white shadow-xl dark:bg-gray-900" wire:poll.10s="detalleSidebarVisible">
            <div class="flex items-center justify-between border-b border-gray-200 p-4 dark:border-gray-700">
                <div>
                    <h3 class="text-lg font-semibold text-stone-700 dark:text-white">Detalle de solicitud</h3>
                    @if ($solicitudDetalle)
                        <p class="text-xs text-gray-500 dark:text-gray-400">Solicitud #{{ $solicitudDetalle->id }}</p>
                    @endif
                </div>
                <button @click="$wire.cerrarDetalle()" class="text-gray-500 hover:text-black dark:hover:text-white">x</button>
            </div>

            <div class="flex-1 space-y-6 overflow-y-auto p-4">
                @if ($solicitudDetalle)
                    @php
                        $solicitudDetalleEstadoClass = match ($solicitudDetalle->estado) {
                            'abierta' => 'bg-sky-100 text-sky-700 dark:bg-sky-900/40 dark:text-sky-300',
                            'en_proceso' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300',
                            'pendiente_cliente' => 'bg-violet-100 text-violet-700 dark:bg-violet-900/40 dark:text-violet-300',
                            'resuelto' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300',
                            'cerrada' => 'bg-stone-200 text-stone-700 dark:bg-stone-700 dark:text-stone-100',
                            'cancelada' => 'bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-300',
                            default => 'bg-stone-100 text-stone-700 dark:bg-stone-800 dark:text-stone-200',
                        };
                    @endphp

                    <div class="space-y-3">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <h4 class="text-lg font-semibold text-stone-700 dark:text-white">{{ $solicitudDetalle->titulo }}</h4>
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    Creada el {{ $solicitudDetalle->created_at?->format('d/m/Y H:i') ?? '-' }}
                                </p>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <span class="rounded-full px-2.5 py-1 text-xs font-medium {{ $solicitudDetalleEstadoClass }}">
                                    {{ $solicitudDetalle->estado === 'pendiente_cliente' ? 'En revision' : str_replace('_', ' ', $solicitudDetalle->estado) }}
                                </span>
                                <span class="rounded-full bg-amber-100 px-2.5 py-1 text-xs text-amber-700 dark:bg-amber-900/40 dark:text-amber-300">
                                    {{ $solicitudDetalle->prioridad ?: 'Sin prioridad' }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div class="space-y-3 rounded-lg border border-stone-200 bg-stone-50/80 p-4 dark:border-stone-700 dark:bg-stone-800/60">
                            <h5 class="font-semibold text-stone-700 dark:text-white">Contexto</h5>
                            <div class="space-y-1 text-sm">
                                <div><span class="font-medium">Cliente:</span> {{ $solicitudDetalle->cliente->nombre ?? ($solicitudDetalle->cliente->razon_social ?? '-') }}</div>
                                <div><span class="font-medium">Origen:</span> {{ ucfirst($solicitudDetalle->origen) }}</div>
                                <div><span class="font-medium">Responsable:</span> {{ $solicitudDetalle->responsable?->name ?? '-' }}</div>
                                <div><span class="font-medium">Tipo:</span> {{ $solicitudDetalle->tipoSolicitud?->nombre ?? 'General' }}</div>
                                <div><span class="font-medium">Vencimiento:</span> {{ $solicitudDetalle->fecha_vencimiento?->format('d/m/Y') ?? '-' }}</div>
                                <div><span class="font-medium">Obligacion:</span> {{ $solicitudDetalle->obligacion_etiqueta }}</div>
                            </div>
                        </div>

                        <div class="space-y-3 rounded-lg border border-slate-200 bg-slate-50/80 p-4 dark:border-slate-700 dark:bg-slate-800/40">
                            <h5 class="font-semibold text-stone-700 dark:text-white">Trazabilidad</h5>
                            <div class="space-y-1 text-sm">
                                <div><span class="font-medium">Creada por:</span> {{ $solicitudDetalle->creadoPor?->name ?? '-' }}</div>
                                <div><span class="font-medium">Modo:</span> {{ ucfirst($solicitudDetalle->modo_solicitud) }}</div>
                                <div><span class="font-medium">Tipo ID:</span> {{ $solicitudDetalle->tipo_solicitud_id ?: '-' }}</div>
                                <div><span class="font-medium">Relacion obligacion:</span> {{ $solicitudDetalle->obligacion_cliente_contador_id ?: 'Sin relacion' }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-3 rounded-lg border border-amber-200 bg-amber-50/70 p-4 dark:border-amber-900/60 dark:bg-amber-950/20">
                        <h5 class="font-semibold text-stone-700 dark:text-white">Descripcion</h5>
                        <p class="whitespace-pre-line text-sm text-gray-700 dark:text-gray-300">{{ $solicitudDetalle->descripcion ?: 'Sin descripcion.' }}</p>
                    </div>

                    @if ($solicitudDetalle->modo_solicitud === 'definida')
                        <div class="space-y-3 rounded-lg border border-sky-200 bg-sky-50/70 p-4 dark:border-sky-900/60 dark:bg-sky-950/20">
                            <h5 class="font-semibold text-stone-700 dark:text-white">Formulario base</h5>
                            <p class="text-sm text-gray-600 dark:text-gray-300">
                                Tipo: {{ $solicitudDetalle->tipoSolicitud?->nombre ?? ($solicitudDetalle->plantilla_snapshot['nombre'] ?? '-') }}
                            </p>
                            <p class="text-sm text-gray-600 dark:text-gray-300">
                                Datos capturados y requerimientos se integraran en este bloque.
                            </p>
                        </div>
                    @endif

                    <div class="space-y-4 rounded-lg border border-violet-200 bg-violet-50/60 p-4 dark:border-violet-900/60 dark:bg-violet-950/20">
                        <div class="flex items-center justify-between gap-3">
                            <h5 class="font-semibold text-stone-700 dark:text-white">Requerimientos</h5>
                            @if ($solicitudDetalle->responsable_user_id === auth()->id())
                                <button wire:click="abrirFormularioRequerimiento"
                                    class="rounded bg-amber-600 px-3 py-2 text-xs text-white hover:bg-amber-700">
                                    + Nuevo requerimiento
                                </button>
                            @endif
                        </div>

                        @if ($requerimientoFormVisible)
                            <div class="space-y-4 rounded-lg border border-white/70 bg-white/80 p-4 shadow-sm dark:border-stone-700 dark:bg-stone-900/50">
                                <div class="flex items-center justify-between gap-3">
                                    <h6 class="font-semibold text-stone-700 dark:text-white">
                                        {{ $editandoRequerimiento ? 'Editar requerimiento' : 'Nuevo requerimiento' }}
                                    </h6>
                                </div>

                                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                    <div>
                                        <label class="mb-1 block text-sm font-medium">Dirigido a</label>
                                        <select wire:model.live="requerimiento_destinatario_tipo"
                                            class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-amber-600 focus:outline-none focus:ring focus:ring-amber-500/40 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                            <option value="cliente">Cliente</option>
                                            <option value="interno">Usuario interno</option>
                                        </select>
                                        @error('requerimiento_destinatario_tipo') <div class="mt-1 text-xs text-red-500">{{ $message }}</div> @enderror
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
                                            @error('requerimiento_destinatario_user_id') <div class="mt-1 text-xs text-red-500">{{ $message }}</div> @enderror
                                        </div>
                                    @endif

                                    <div class="md:col-span-2">
                                        <label class="mb-1 block text-sm font-medium">Titulo</label>
                                        <input type="text" wire:model="requerimiento_titulo"
                                            class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-amber-600 focus:outline-none focus:ring focus:ring-amber-500/40 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                        @error('requerimiento_titulo') <div class="mt-1 text-xs text-red-500">{{ $message }}</div> @enderror
                                    </div>

                                    <div class="md:col-span-2">
                                        <label class="mb-1 block text-sm font-medium">Descripcion</label>
                                        <textarea wire:model="requerimiento_descripcion" rows="3"
                                            class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-amber-600 focus:outline-none focus:ring focus:ring-amber-500/40 dark:border-gray-600 dark:bg-gray-700 dark:text-white"></textarea>
                                        @error('requerimiento_descripcion') <div class="mt-1 text-xs text-red-500">{{ $message }}</div> @enderror
                                    </div>

                                    <div>
                                        <label class="mb-1 block text-sm font-medium">Fecha limite</label>
                                        <input type="date" wire:model="requerimiento_fecha_limite"
                                            class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-amber-600 focus:outline-none focus:ring focus:ring-amber-500/40 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                        @error('requerimiento_fecha_limite') <div class="mt-1 text-xs text-red-500">{{ $message }}</div> @enderror
                                    </div>
                                </div>

                                <div class="flex justify-end gap-2">
                                    <button wire:click="cerrarFormularioRequerimiento"
                                        class="rounded border px-4 py-2 text-sm dark:border-gray-600 dark:text-white">
                                        Cancelar
                                    </button>
                                    <button wire:click="guardarRequerimiento"
                                        class="rounded bg-amber-600 px-4 py-2 text-sm text-white hover:bg-amber-700">
                                        {{ $editandoRequerimiento ? 'Actualizar requerimiento' : 'Guardar requerimiento' }}
                                    </button>
                                </div>
                            </div>
                        @endif

                        <div class="space-y-3">
                            @forelse ($solicitudDetalle->requerimientos as $requerimiento)
                                @php
                                    $requerimientoEstadoClass = match ($requerimiento->estado) {
                                        'abierto' => 'bg-sky-100 text-sky-700 dark:bg-sky-900/40 dark:text-sky-300',
                                        'respondido' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300',
                                        'validado' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300',
                                        'rechazado' => 'bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-300',
                                        'cancelado' => 'bg-stone-200 text-stone-700 dark:bg-stone-700 dark:text-stone-100',
                                        default => 'bg-stone-100 text-stone-700 dark:bg-stone-800 dark:text-stone-200',
                                    };
                                @endphp
                                <div x-data="{ open: {{ $requerimiento->tipo === 'resultado' ? 'true' : 'false' }} }"
                                    class="overflow-visible rounded-xl border-2 border-stone-300 bg-stone-100 shadow-md dark:border-stone-600 dark:bg-stone-800">
                                    <div @click="open = !open"
                                        class="flex items-start justify-between gap-3 px-4 py-4 text-left hover:bg-stone-200/70 dark:hover:bg-stone-700/50">
                                        <div class="min-w-0">
                                            <div class="flex flex-nowrap items-center gap-2">
                                                <div class="truncate font-semibold text-gray-900 dark:text-white">{{ $requerimiento->titulo }}</div>
                                                @if ($requerimiento->tipo === 'resultado')
                                                    <span class="rounded-full bg-blue-100 px-2.5 py-1 text-xs text-blue-700 dark:bg-blue-900/40 dark:text-blue-300">
                                                        Resultado
                                                    </span>
                                                @endif
                                            </div>
                                            <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                                {{ ucfirst($requerimiento->destinatario_tipo) }}
                                                @if ($requerimiento->destinatario_tipo === 'interno')
                                                    · {{ $requerimiento->destinatario?->name ?? 'Sin usuario' }}
                                                @endif
                                            </div>
                                        </div>

                                        <div class="flex flex-col items-end gap-2" @click.stop>
                                            <div class="flex flex-nowrap items-center justify-end gap-2">
                                                <span class="rounded-full px-2.5 py-1 text-xs font-medium {{ $requerimientoEstadoClass }}">
                                                    {{ str_replace('_', ' ', $requerimiento->estado) }}
                                                </span>
                                                @if ($requerimiento->fecha_limite)
                                                    <span class="rounded-full bg-amber-100 px-2.5 py-1 text-xs text-amber-700 dark:bg-amber-900/40 dark:text-amber-300">
                                                        Limite {{ $requerimiento->fecha_limite?->format('d/m/Y') }}
                                                    </span>
                                                @endif
                                            </div>
                                            <button type="button" @click="open = !open" class="inline-flex items-center gap-1 text-xs font-medium text-stone-600 dark:text-stone-300">
                                                <span x-show="!open">Ver mas</span>
                                                <span x-show="open">Ocultar</span>
                                                <svg class="h-4 w-4 transition-transform duration-200" :class="{ 'rotate-180': open }" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                    <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.168l3.71-3.938a.75.75 0 1 1 1.08 1.04l-4.25 4.512a.75.75 0 0 1-1.08 0L5.21 8.27a.75.75 0 0 1 .02-1.06Z" clip-rule="evenodd" />
                                                </svg>
                                            </button>
                                        </div>
                                    </div>

                                    <div x-show="open" x-transition class="space-y-3 border-t border-stone-300 px-4 pb-4 pt-4 dark:border-stone-600">
                                        @if ($requerimiento->descripcion)
                                            <p class="whitespace-pre-line text-sm text-gray-700 dark:text-gray-300">{{ $requerimiento->descripcion }}</p>
                                        @endif

                                        @if ($requerimiento->respuesta_texto)
                                            <div class="space-y-2 rounded-lg border border-emerald-200 p-3 dark:border-emerald-800">
                                                <div class="text-sm font-medium text-emerald-700 dark:text-emerald-300">Respuesta</div>
                                                <p class="whitespace-pre-line text-sm text-gray-700 dark:text-gray-300">{{ $requerimiento->respuesta_texto }}</p>
                                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                                    Respondido por {{ $requerimiento->respondidoPor?->name ?? '-' }} el {{ $requerimiento->respondido_at?->format('d/m/Y H:i') ?? '-' }}
                                                </div>
                                            </div>
                                        @endif

                                        @if ($requerimiento->tipo === 'resultado' && $requerimiento->destinatario_user_id === auth()->id())
                                            <div class="space-y-3 rounded-lg border border-blue-200 p-3 dark:border-blue-800">
                                                <div class="text-sm font-medium text-blue-700 dark:text-blue-300">Entregar resultado</div>
                                                <textarea wire:model="respuestaResultado.{{ $requerimiento->id }}" rows="4"
                                                    @disabled(in_array($requerimiento->estado, ['validado', 'cancelado']))
                                                    class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-amber-600 focus:outline-none focus:ring focus:ring-amber-500/40 disabled:opacity-60 dark:border-gray-600 dark:bg-gray-700 dark:text-white"></textarea>
                                                @error('respuestaResultado.' . $requerimiento->id) <div class="mt-1 text-xs text-red-500">{{ $message }}</div> @enderror
                                                <div class="rounded-lg border border-dashed border-gray-300 p-3 dark:border-gray-700">
                                                    <div class="mb-2 text-sm font-medium text-stone-700 dark:text-white">Documentos de resultado</div>
                                                    <p class="mb-2 text-xs text-gray-500 dark:text-gray-400">
                                                        Adjunta aqui los archivos del resultado final si aplica.
                                                    </p>
                                                    @livewire('shared.archivos-adjuntos-crud', ['modelo' => $requerimiento], key('resultado-archivos-' . $requerimiento->id))
                                                </div>
                                                <div class="flex justify-end">
                                                    <button wire:click="guardarRespuestaResultado({{ $requerimiento->id }})"
                                                        @disabled(in_array($requerimiento->estado, ['validado', 'cancelado']))
                                                        class="rounded bg-amber-600 px-4 py-2 text-sm text-white hover:bg-amber-700 disabled:opacity-60">
                                                        Guardar resultado
                                                    </button>
                                                </div>
                                            </div>
                                        @endif

                                        @if ($requerimiento->estado === 'rechazado' && $requerimiento->comentario_validacion)
                                            <div class="space-y-2 rounded-lg border border-red-200 p-3 dark:border-red-800">
                                                <div class="text-sm font-medium text-red-700 dark:text-red-300">Respuesta rechazada</div>
                                                <p class="whitespace-pre-line text-sm text-gray-700 dark:text-gray-300">{{ $requerimiento->comentario_validacion }}</p>
                                            </div>
                                        @endif

                                        @if (($mostrarRechazoRequerimiento[$requerimiento->id] ?? false) === true)
                                            <div class="space-y-3 rounded-lg border border-gray-200 p-4 dark:border-gray-700">
                                                <div>
                                                    <label class="mb-1 block text-sm font-medium">Motivo del rechazo</label>
                                                    <textarea wire:model="comentarioRechazoRequerimiento.{{ $requerimiento->id }}" rows="3"
                                                        class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-amber-600 focus:outline-none focus:ring focus:ring-amber-500/40 dark:border-gray-600 dark:bg-gray-700 dark:text-white"></textarea>
                                                    @error('comentarioRechazoRequerimiento.' . $requerimiento->id) <div class="mt-1 text-xs text-red-500">{{ $message }}</div> @enderror
                                                </div>
                                                <div class="flex justify-end gap-2">
                                                    <button wire:click="cancelarRechazoRespuesta({{ $requerimiento->id }})"
                                                        class="rounded border px-4 py-2 text-sm dark:border-gray-600 dark:text-white">
                                                        Cancelar
                                                    </button>
                                                    <button wire:click="rechazarRespuestaRequerimiento({{ $requerimiento->id }})"
                                                        class="rounded bg-amber-600 px-4 py-2 text-sm text-white hover:bg-amber-700">
                                                        Rechazar respuesta
                                                    </button>
                                                </div>
                                            </div>
                                        @endif

                                        <div class="flex justify-end gap-2">
                                            @if ($solicitudDetalle->responsable_user_id === auth()->id() && $requerimiento->tipo !== 'resultado')
                                                <x-action-icon icon="edit" label="Editar" variant="primary"
                                                    wire:click="editarRequerimiento({{ $requerimiento->id }})" />
                                                <x-action-icon icon="trash" label="Eliminar" variant="danger"
                                                    wire:click="confirmarEliminarRequerimiento({{ $requerimiento->id }})" />
                                            @endif

                                            @if (
                                                $requerimiento->estado === 'respondido' &&
                                                (
                                                    ($requerimiento->tipo !== 'resultado' && $requerimiento->creado_por_user_id === auth()->id()) ||
                                                    ($requerimiento->tipo === 'resultado' && $solicitudDetalle->creado_por_user_id === auth()->id())
                                                )
                                            )
                                                <x-action-icon icon="check" label="Validar respuesta" variant="primary"
                                                    wire:click="validarRespuestaRequerimiento({{ $requerimiento->id }})" />
                                                <x-action-icon icon="arrow-uturn-left" label="Rechazar respuesta" variant="danger"
                                                    wire:click="mostrarRechazoRespuesta({{ $requerimiento->id }})" />
                                            @endif
                                        </div>

                                        <div class="text-xs text-gray-500 dark:text-gray-400">
                                            Creado por {{ $requerimiento->creadoPor?->name ?? '-' }} el {{ $requerimiento->created_at?->format('d/m/Y H:i') ?? '-' }}
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    Aun no hay requerimientos registrados en esta solicitud.
                                </p>
                            @endforelse
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
                                    @endphp
                                    <div class="flex items-start gap-3">
                                        <div class="mt-1 h-2.5 w-2.5 shrink-0 rounded-full {{ $eventoColor }}"></div>
                                        <div class="min-w-0">
                                            <div class="font-medium text-gray-900 dark:text-white">{{ $evento->titulo }}</div>
                                            @if ($evento->descripcion)
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
