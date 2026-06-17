<div x-data="{ sidebar: @entangle('sidebarVisible'), detalleSidebar: @entangle('detalleSidebarVisible') }" class="space-y-4 rounded-lg bg-white p-6 text-gray-900 shadow dark:bg-gray-900 dark:text-white">
    <div class="flex flex-wrap items-center justify-between gap-2">
        <h2 class="text-xl font-bold text-stone-600 dark:text-white">Mis solicitudes</h2>
        <button wire:click="abrirSidebarCrear" class="rounded bg-amber-600 px-4 py-2 text-white hover:bg-amber-700">
            + Nueva solicitud
        </button>
    </div>

    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
        <div class="flex flex-wrap items-center gap-2">
            <input type="text" placeholder="Buscar por titulo o tipo" wire:model.live="buscar"
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
                    <th class="px-4 py-2 text-left text-xs font-semibold">Solicitud</th>
                    <th class="px-4 py-2 text-left text-xs font-semibold">Tipo</th>
                    <th class="px-4 py-2 text-left text-xs font-semibold">Estado</th>
                    <th class="px-4 py-2 text-left text-xs font-semibold">Formulario</th>
                    <th class="px-4 py-2 text-left text-xs font-semibold">Responsable</th>
                    <th class="px-4 py-2 text-left text-xs font-semibold">Vencimiento</th>
                    <th class="px-4 py-2 text-left text-xs font-semibold">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                @forelse ($solicitudes as $solicitud)
                    @php
                        $estadoClase = match ($solicitud->estado) {
                            'abierta' => 'bg-sky-100 text-sky-700 dark:bg-sky-900/40 dark:text-sky-300',
                            'en_proceso' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300',
                            'pendiente_cliente' => 'bg-violet-100 text-violet-700 dark:bg-violet-900/40 dark:text-violet-300',
                            'resuelto' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300',
                            'cerrada' => 'bg-stone-200 text-stone-700 dark:bg-stone-700 dark:text-stone-100',
                            'cancelada' => 'bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-300',
                            default => 'bg-stone-100 text-stone-700 dark:bg-stone-800 dark:text-stone-200',
                        };
                        $estadoFormularioClase = match ($solicitud->estado_formulario) {
                            'no_aplica' => 'bg-stone-100 text-stone-700 dark:bg-stone-800 dark:text-stone-200',
                            'pendiente' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300',
                            'respondido' => 'bg-sky-100 text-sky-700 dark:bg-sky-900/40 dark:text-sky-300',
                            'validado' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300',
                            default => 'bg-stone-100 text-stone-700 dark:bg-stone-800 dark:text-stone-200',
                        };
                        $clientePuedeCerrar = !$solicitud->usaFormularioComoCierre()
                            && in_array($solicitud->estado, ['pendiente_cliente', 'resuelto'], true)
                            && in_array($solicitud->resultadoRequerimiento?->estado, ['respondido', 'validado'], true);
                    @endphp
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/60">
                        <td class="px-4 py-2">
                            <div class="font-medium">{{ $solicitud->titulo }}</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">{{ $solicitud->created_at?->format('d/m/Y H:i') ?? '-' }}</div>
                        </td>
                        <td class="px-4 py-2">{{ $solicitud->tipoSolicitud?->nombre ?? '-' }}</td>
                        <td class="px-4 py-2">
                            <span class="inline-flex whitespace-nowrap rounded-full px-2.5 py-1 text-xs font-medium {{ $estadoClase }}">
                                {{ $solicitud->estado === 'pendiente_cliente' ? 'En revision' : str_replace('_', ' ', $solicitud->estado) }}
                            </span>
                        </td>
                        <td class="px-4 py-2">
                            <span class="inline-flex whitespace-nowrap rounded-full px-2.5 py-1 text-xs font-medium {{ $estadoFormularioClase }}">
                                {{ $solicitud->estado_formulario_label }}
                            </span>
                        </td>
                        <td class="px-4 py-2">{{ $solicitud->responsable?->name ?? '-' }}</td>
                        <td class="px-4 py-2 whitespace-nowrap">{{ $solicitud->fecha_vencimiento?->format('d/m/Y') ?? '-' }}</td>
                        <td class="px-4 py-2 whitespace-nowrap">
                            <div class="flex items-center gap-2">
                                <x-action-icon icon="eye" label="Ver resultado" variant="primary"
                                    wire:click="abrirDetalle({{ $solicitud->id }})" />
                                @if ($clientePuedeCerrar)
                                    <x-action-icon icon="check" label="Cerrar solicitud" variant="success"
                                        wire:click="confirmarCerrarSolicitud({{ $solicitud->id }})" />
                                @endif
                                <x-action-icon icon="edit" label="Editar" variant="primary"
                                    wire:click="editarSolicitud({{ $solicitud->id }})" />
                                <x-action-icon icon="trash" label="Eliminar" variant="danger"
                                    wire:click="confirmarEliminarSolicitud({{ $solicitud->id }})" />
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-4 text-center text-gray-500 dark:text-gray-400">
                            Aun no tienes solicitudes registradas.
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

        <div class="flex h-full w-full max-w-2xl flex-col border-l bg-white shadow-xl dark:bg-gray-900">
            <div class="flex items-center justify-between border-b border-gray-200 p-4 dark:border-gray-700">
                <div>
                    <h3 class="text-lg font-semibold text-stone-700 dark:text-white">
                        {{ $editandoSolicitud ? 'Editar solicitud' : 'Nueva solicitud' }}
                    </h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        {{ $editandoSolicitud ? 'Actualiza la informacion y guarda los cambios.' : 'Selecciona un tipo y completa el formulario.' }}
                    </p>
                </div>
                <button @click="$wire.cerrarSidebar()" class="text-gray-500 hover:text-black dark:hover:text-white">x</button>
            </div>

            <div class="flex-1 space-y-6 overflow-y-auto p-4">
                <div>
                    <label class="mb-1 block text-sm font-medium">Tipo de solicitud</label>
                    <select wire:model.live="tipo_solicitud_id_form"
                        class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-amber-600 focus:outline-none focus:ring focus:ring-amber-500/40 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        <option value="">Seleccione...</option>
                        @foreach ($tiposDisponibles as $tipo)
                            <option value="{{ $tipo->id }}">{{ $tipo->nombre }}</option>
                        @endforeach
                    </select>
                    @error('tipo_solicitud_id_form') <div class="mt-1 text-xs text-red-500">{{ $message }}</div> @enderror
                </div>

                @if ($tipoSeleccionado)
                    <div class="space-y-3 rounded-lg border border-sky-200 bg-sky-50/70 p-4 dark:border-sky-900/60 dark:bg-sky-950/20">
                        <div class="font-semibold text-stone-700 dark:text-white">{{ $tipoSeleccionado->nombre }}</div>
                        @if ($tipoSeleccionado->descripcion_sugerida)
                            <p class="text-sm text-gray-600 dark:text-gray-300">{{ $tipoSeleccionado->descripcion_sugerida }}</p>
                        @endif
                        <div class="text-xs text-gray-500 dark:text-gray-400">
                            Prioridad sugerida: {{ $tipoSeleccionado->prioridad_default ?: 'Sin prioridad definida' }}
                        </div>
                    </div>

                    <div class="space-y-4 rounded-lg border border-gray-200 p-4 dark:border-gray-700">
                        @forelse ($camposFormulario as $campo)
                            @php
                                $campoKey = $campo['key'] ?? null;
                                $campoType = $campo['type'] ?? 'text';
                                $campoRequired = !empty($campo['required']);
                            @endphp

                            @if ($campoKey)
                                <div class="space-y-1">
                                    <label class="block text-sm font-medium text-stone-700 dark:text-white">
                                        {{ $campo['label'] ?? $campoKey }}
                                        @if ($campoRequired)
                                            <span class="text-red-500">*</span>
                                        @endif
                                    </label>

                                    @if ($campoType === 'textarea')
                                        <textarea wire:model.defer="formulario_respuesta.{{ $campoKey }}" rows="3"
                                            class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-amber-600 focus:outline-none focus:ring focus:ring-amber-500/40 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                            placeholder="{{ $campo['placeholder'] ?? '' }}"></textarea>
                                    @elseif ($campoType === 'select')
                                        <select wire:model.defer="formulario_respuesta.{{ $campoKey }}"
                                            class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-amber-600 focus:outline-none focus:ring focus:ring-amber-500/40 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                            <option value="">Seleccione...</option>
                                            @foreach (($campo['options'] ?? []) as $option)
                                                <option value="{{ $option }}">{{ $option }}</option>
                                            @endforeach
                                        </select>
                                    @elseif ($campoType === 'checkbox')
                                        <label class="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                                            <input type="checkbox" wire:model.defer="formulario_respuesta.{{ $campoKey }}"
                                                class="rounded border-gray-300 text-amber-600 focus:ring-amber-500 dark:border-gray-600 dark:bg-gray-700">
                                            <span>{{ $campo['help'] ?? 'Marcar si aplica' }}</span>
                                        </label>
                                    @elseif ($campoType === 'date')
                                        <input type="date" wire:model.defer="formulario_respuesta.{{ $campoKey }}"
                                            class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-amber-600 focus:outline-none focus:ring focus:ring-amber-500/40 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                    @elseif ($campoType === 'number')
                                        <input type="number" wire:model.defer="formulario_respuesta.{{ $campoKey }}"
                                            class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-amber-600 focus:outline-none focus:ring focus:ring-amber-500/40 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                            placeholder="{{ $campo['placeholder'] ?? '' }}">
                                    @elseif ($campoType === 'file')
                                        <input type="file" wire:model="formulario_respuesta.{{ $campoKey }}"
                                            @if (!empty($campo['accept'])) accept="{{ $campo['accept'] }}" @endif
                                            class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-amber-600 focus:outline-none focus:ring focus:ring-amber-500/40 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                        <div class="text-xs text-gray-500 dark:text-gray-400">
                                            Tamano maximo sugerido: 20 MB.
                                            @if (!empty($campo['accept']))
                                                Tipos sugeridos: {{ $campo['accept'] }}
                                            @endif
                                        </div>
                                        @if ($editandoSolicitud && !empty($datos_formulario_actual[$campoKey] ?? null))
                                            <div class="text-xs text-amber-600 dark:text-amber-300">
                                                Archivo actual: {{ $datos_formulario_actual[$campoKey] }}
                                            </div>
                                        @endif
                                    @else
                                        <input type="text" wire:model.defer="formulario_respuesta.{{ $campoKey }}"
                                            class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-amber-600 focus:outline-none focus:ring focus:ring-amber-500/40 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                            placeholder="{{ $campo['placeholder'] ?? '' }}">
                                    @endif

                                    @if (!empty($campo['help']) && $campoType !== 'checkbox')
                                        <div class="text-xs text-gray-500 dark:text-gray-400">{{ $campo['help'] }}</div>
                                    @endif

                                    @error('formulario_respuesta.' . $campoKey)
                                        <div class="text-xs text-red-500">{{ $message }}</div>
                                    @enderror
                                </div>
                            @endif
                        @empty
                            <div class="text-sm text-gray-500 dark:text-gray-400">
                                Este tipo aun no tiene campos definidos.
                            </div>
                        @endforelse
                    </div>
                @endif
            </div>

            <div class="flex justify-end gap-2 border-t border-gray-200 p-4 dark:border-gray-700">
                <button wire:click="cerrarSidebar" class="rounded border px-4 py-2 text-sm dark:border-gray-600 dark:text-white">
                    Cancelar
                </button>
                <button wire:click="guardarSolicitud" wire:loading.attr="disabled" wire:target="guardarSolicitud"
                    class="rounded bg-amber-600 px-4 py-2 text-sm text-white hover:bg-amber-700 disabled:cursor-not-allowed disabled:opacity-60">
                    <span wire:loading.remove wire:target="guardarSolicitud">{{ $editandoSolicitud ? 'Guardar cambios' : 'Enviar solicitud' }}</span>
                    <span wire:loading wire:target="guardarSolicitud">Enviando...</span>
                </button>
            </div>
            <x-spinner target="guardarSolicitud" />
        </div>
    </div>

    <x-confirmacion-eliminacion
        :visible="$confirmarEliminar"
        closeFlag="confirmarEliminar"
        action="eliminarSolicitudConfirmada"
        titulo="Eliminar solicitud"
        mensaje="Deseas eliminar esta solicitud? Tambien se eliminaran sus requerimientos y archivos relacionados."
        confirmLabel="Si, eliminar"
        cancelLabel="Volver" />

    <x-confirmacion-eliminacion
        :visible="$confirmarCierre"
        closeFlag="confirmarCierre"
        action="cerrarSolicitudConfirmada"
        titulo="Cerrar solicitud"
        mensaje="Deseas cerrar esta solicitud? Esto marcara el resultado como aceptado por el cliente."
        confirmLabel="Si, cerrar"
        cancelLabel="Volver" />

    <div x-cloak x-show="detalleSidebar" x-transition.opacity class="fixed inset-0 z-40 flex justify-end bg-black/40">
        <div class="flex-1" @click="$wire.cerrarDetalle()"></div>

        <div class="flex h-full w-full max-w-2xl flex-col border-l bg-white shadow-xl dark:bg-gray-900">
            <div class="flex items-center justify-between border-b border-gray-200 p-4 dark:border-gray-700">
                <div>
                    <h3 class="text-lg font-semibold text-stone-700 dark:text-white">Detalle de solicitud</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Consulta el formulario enviado y el resultado entregado.</p>
                </div>
                <button @click="$wire.cerrarDetalle()" class="text-gray-500 hover:text-black dark:hover:text-white">x</button>
            </div>

            <div class="flex-1 space-y-4 overflow-y-auto p-4">
                @if ($solicitudDetalle)
                    @php
                        $estadoClase = match ($solicitudDetalle->estado) {
                            'abierta' => 'bg-sky-100 text-sky-700 dark:bg-sky-900/40 dark:text-sky-300',
                            'en_proceso' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300',
                            'pendiente_cliente' => 'bg-violet-100 text-violet-700 dark:bg-violet-900/40 dark:text-violet-300',
                            'resuelto' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300',
                            'cerrada' => 'bg-stone-200 text-stone-700 dark:bg-stone-700 dark:text-stone-100',
                            'cancelada' => 'bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-300',
                            default => 'bg-stone-100 text-stone-700 dark:bg-stone-800 dark:text-stone-200',
                        };
                    @endphp

                    <div class="rounded-lg border border-stone-200 bg-stone-50/70 p-4 dark:border-stone-700 dark:bg-stone-800/40">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <h4 class="text-lg font-semibold text-stone-700 dark:text-white">{{ $solicitudDetalle->titulo }}</h4>
                            <span class="inline-flex whitespace-nowrap rounded-full px-2.5 py-1 text-xs font-medium {{ $estadoClase }}">
                                {{ $solicitudDetalle->estado === 'pendiente_cliente' ? 'En revision' : str_replace('_', ' ', $solicitudDetalle->estado) }}
                            </span>
                        </div>
                        <div class="mt-2 grid grid-cols-1 gap-2 text-sm text-gray-600 dark:text-gray-300 md:grid-cols-2">
                            <div><span class="font-medium">Tipo:</span> {{ $solicitudDetalle->tipoSolicitud?->nombre ?? '-' }}</div>
                            <div><span class="font-medium">Responsable:</span> {{ $solicitudDetalle->responsable?->name ?? '-' }}</div>
                            <div><span class="font-medium">Formulario:</span> {{ $solicitudDetalle->estado_formulario_label }}</div>
                            <div><span class="font-medium">Vencimiento:</span> {{ $solicitudDetalle->fecha_vencimiento?->format('d/m/Y') ?? '-' }}</div>
                        </div>
                    </div>

                    <div class="rounded-lg border border-sky-200 bg-sky-50/60 p-4 dark:border-sky-900/60 dark:bg-sky-950/20">
                        <h5 class="mb-3 font-semibold text-stone-700 dark:text-white">Formulario enviado</h5>
                        <div class="space-y-2">
                            @forelse ($solicitudDetalle->resumen_formulario as $campo)
                                @php
                                    $archivoFormulario = null;

                                    if (($campo['type'] ?? null) === 'file' && filled($campo['value'])) {
                                        $archivoFormulario = $solicitudDetalle->archivos->firstWhere('nombre', $campo['value']);
                                    }
                                @endphp
                                <div class="rounded border border-sky-100 px-3 py-2 text-sm dark:border-sky-900/30">
                                    <div class="font-medium text-stone-700 dark:text-white">{{ $campo['label'] }}</div>
                                    <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                        @if (filled($campo['value']))
                                            @if (($campo['type'] ?? null) === 'file' && $archivoFormulario)
                                                <a href="{{ $archivoFormulario->archivo ? \Illuminate\Support\Facades\Storage::disk('public')->url($archivoFormulario->archivo) : $archivoFormulario->archivo_drive_url }}"
                                                    target="_blank"
                                                    class="text-amber-600 hover:underline">
                                                    {{ $archivoFormulario->nombre }}
                                                </a>
                                            @else
                                                {{ is_array($campo['value']) ? implode(', ', $campo['value']) : $campo['value'] }}
                                            @endif
                                        @else
                                            Sin respuesta capturada.
                                        @endif
                                    </div>
                                </div>
                            @empty
                                <div class="text-sm text-gray-500 dark:text-gray-400">
                                    Esta solicitud no tiene campos definidos.
                                </div>
                            @endforelse
                        </div>
                    </div>

                    @if ($solicitudDetalle->usaFormularioComoCierre())
                        <div class="rounded-lg border border-emerald-200 bg-emerald-50/60 p-4 dark:border-emerald-900/60 dark:bg-emerald-950/20">
                            <div class="mb-3 flex items-center justify-between gap-2">
                                <h5 class="font-semibold text-stone-700 dark:text-white">Validacion del formulario</h5>
                                <span class="rounded-full bg-blue-100 px-2.5 py-1 text-xs text-blue-700 dark:bg-blue-900/40 dark:text-blue-300">
                                    {{ $solicitudDetalle->estado_formulario_label }}
                                </span>
                            </div>
                            <div class="text-sm text-gray-600 dark:text-gray-300">
                                Esta solicitud definida se cierra con la validacion del formulario enviado. Si hace falta informacion adicional, el despacho te enviara requerimientos extra.
                            </div>
                        </div>
                    @else
                        <div class="rounded-lg border border-emerald-200 bg-emerald-50/60 p-4 dark:border-emerald-900/60 dark:bg-emerald-950/20">
                            <div class="mb-3 flex items-center justify-between gap-2">
                                <h5 class="font-semibold text-stone-700 dark:text-white">Resultado</h5>
                                <span class="rounded-full bg-blue-100 px-2.5 py-1 text-xs text-blue-700 dark:bg-blue-900/40 dark:text-blue-300">
                                    {{ $solicitudDetalle->resultadoRequerimiento?->estado ? str_replace('_', ' ', $solicitudDetalle->resultadoRequerimiento->estado) : 'Sin resultado' }}
                                </span>
                            </div>

                            @if ($solicitudDetalle->resultadoRequerimiento?->respuesta_texto)
                                <div class="whitespace-pre-line rounded border border-emerald-100 bg-white/70 px-3 py-3 text-sm text-gray-700 dark:border-emerald-900/30 dark:bg-gray-800/40 dark:text-gray-300">
                                    {{ $solicitudDetalle->resultadoRequerimiento->respuesta_texto }}
                                </div>
                                <div class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                                    Respondido por {{ $solicitudDetalle->resultadoRequerimiento->respondidoPor?->name ?? '-' }}
                                    el {{ $solicitudDetalle->resultadoRequerimiento->respondido_at?->format('d/m/Y H:i') ?? '-' }}
                                </div>
                            @else
                                <div class="text-sm text-gray-500 dark:text-gray-400">
                                    Aun no se ha entregado un resultado para esta solicitud.
                                </div>
                            @endif

                            @if ($solicitudDetalle->resultadoRequerimiento && $solicitudDetalle->resultadoRequerimiento->archivos->count())
                                <div class="mt-4 space-y-2">
                                    <div class="text-sm font-medium text-stone-700 dark:text-white">Archivos del resultado</div>
                                    @foreach ($solicitudDetalle->resultadoRequerimiento->archivos as $archivo)
                                        <a href="{{ $archivo->archivo ? \Illuminate\Support\Facades\Storage::disk('public')->url($archivo->archivo) : $archivo->archivo_drive_url }}"
                                            target="_blank"
                                            class="block rounded border border-emerald-100 px-3 py-2 text-sm text-amber-600 hover:underline dark:border-emerald-900/30">
                                            {{ $archivo->nombre }}
                                        </a>
                                    @endforeach
                                </div>
                            @endif

                            @php
                                $clientePuedeCerrarDetalle = !$solicitudDetalle->usaFormularioComoCierre()
                                    && in_array($solicitudDetalle->estado, ['pendiente_cliente', 'resuelto'], true)
                                    && in_array($solicitudDetalle->resultadoRequerimiento?->estado, ['respondido', 'validado'], true);
                            @endphp

                            @if ($clientePuedeCerrarDetalle)
                                <div class="mt-4 flex justify-end">
                                    <button wire:click="confirmarCerrarSolicitud({{ $solicitudDetalle->id }})"
                                        class="rounded bg-emerald-600 px-4 py-2 text-sm text-white hover:bg-emerald-700">
                                        Cerrar solicitud
                                    </button>
                                </div>
                            @endif
                        </div>
                    @endif
                @else
                    <div class="text-sm text-gray-500 dark:text-gray-400">
                        No se pudo cargar el detalle de la solicitud.
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
