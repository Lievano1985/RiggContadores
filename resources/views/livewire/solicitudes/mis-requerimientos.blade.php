@php use Illuminate\Support\Facades\Storage; @endphp

<div x-data="{ sidebar: @entangle('sidebarVisible') }" class="space-y-4 rounded-lg bg-white p-6 text-gray-900 shadow dark:bg-gray-900 dark:text-white">
    <div class="flex flex-wrap items-center justify-between gap-2">
        <h2 class="text-xl font-bold text-stone-600 dark:text-white">Mis requerimientos</h2>
    </div>

    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
        <div class="flex flex-wrap items-center gap-2">
            <input type="text" placeholder="Buscar por cliente o requerimiento" wire:model.live="buscar"
                class="rounded border px-3 py-2 focus:border-amber-600 focus:outline-none focus:ring focus:ring-amber-500/40 dark:bg-gray-700 dark:text-white">

            <select wire:model.live="estado"
                class="rounded border px-3 py-2 focus:border-amber-600 focus:outline-none focus:ring focus:ring-amber-500/40 dark:bg-gray-700 dark:text-white">
                <option value="activos">Estado (activos)</option>
                <option value="">Estado (todos)</option>
                <option value="abierto">Abierto</option>
                <option value="respondido">Respondido</option>
                <option value="validado">Validado</option>
                <option value="rechazado">Rechazado</option>
                <option value="cancelado">Cancelado</option>
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
                    <th class="px-4 py-2 text-left text-xs font-semibold">Cliente</th>
                    <th class="px-4 py-2 text-left text-xs font-semibold">Solicitud</th>
                    <th class="px-4 py-2 text-left text-xs font-semibold">Requerimiento</th>
                    <th class="px-4 py-2 text-left text-xs font-semibold">Estado</th>
                    <th class="px-4 py-2 text-left text-xs font-semibold">Limite</th>
                    <th class="px-4 py-2 text-left text-xs font-semibold">Creado por</th>
                    <th class="px-4 py-2 text-left text-xs font-semibold">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                @forelse ($requerimientos as $requerimiento)
                    @php
                        $estadoClase = match ($requerimiento->estado) {
                            'abierto' => 'bg-sky-100 text-sky-700 dark:bg-sky-900/40 dark:text-sky-300',
                            'respondido' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300',
                            'validado' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300',
                            'rechazado' => 'bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-300',
                            'cancelado' => 'bg-stone-200 text-stone-700 dark:bg-stone-700 dark:text-stone-100',
                            default => 'bg-stone-100 text-stone-700 dark:bg-stone-800 dark:text-stone-200',
                        };
                    @endphp
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/60">
                        <td class="px-4 py-2">{{ $requerimiento->solicitud->cliente->nombre ?? ($requerimiento->solicitud->cliente->razon_social ?? '-') }}</td>
                        <td class="px-4 py-2">{{ $requerimiento->solicitud->titulo ?? '-' }}</td>
                        <td class="px-4 py-2">
                            <div class="flex flex-wrap items-center gap-2">
                                <div class="font-medium">{{ $requerimiento->titulo }}</div>
                                @if ($requerimiento->tipo === 'resultado')
                                    <span class="rounded-full bg-blue-100 px-2.5 py-1 text-xs text-blue-700 dark:bg-blue-900/40 dark:text-blue-300">
                                        Resultado
                                    </span>
                                @endif
                            </div>
                            @if ($requerimiento->descripcion)
                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ \Illuminate\Support\Str::limit($requerimiento->descripcion, 80) }}</div>
                            @endif
                        </td>
                        <td class="px-4 py-2">
                            <span class="inline-flex whitespace-nowrap rounded-full px-2.5 py-1 text-xs font-medium {{ $estadoClase }}">
                                {{ str_replace('_', ' ', $requerimiento->estado) }}
                            </span>
                        </td>
                        <td class="px-4 py-2 whitespace-nowrap">{{ $requerimiento->fecha_limite?->format('d/m/Y') ?? '-' }}</td>
                        <td class="px-4 py-2">{{ $requerimiento->creadoPor?->name ?? '-' }}</td>
                        <td class="px-4 py-2">
                            <x-action-icon icon="eye" label="Ver" variant="primary"
                                wire:click="abrirDetalle({{ $requerimiento->id }})" />
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-4 text-center text-gray-500 dark:text-gray-400">
                            No tienes requerimientos asignados.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>
        {{ $requerimientos->links('vendor.pagination.tailwind-links-only') }}
    </div>

    <div x-cloak x-show="sidebar" x-transition.opacity class="fixed inset-0 z-40 flex justify-end bg-black/40">
        <div class="flex-1" @click="$wire.cerrarSidebar()"></div>

        <div wire:poll.10s="sidebarVisible" class="flex h-full w-full max-w-xl flex-col border-l bg-white shadow-xl dark:bg-gray-900">
            <div class="flex items-center justify-between border-b border-gray-200 p-4 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-stone-700 dark:text-white">Detalle de requerimiento</h3>
                <button @click="$wire.cerrarSidebar()" class="text-gray-500 hover:text-black dark:hover:text-white">x</button>
            </div>

            <div class="flex-1 space-y-6 overflow-y-auto p-4">
                @if ($requerimientoSeleccionado)
                    <div class="space-y-2 rounded-lg border border-gray-200 p-4 dark:border-gray-700">
                        <div class="font-semibold text-stone-700 dark:text-white">{{ $requerimientoSeleccionado->titulo }}</div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">Solicitud: {{ $requerimientoSeleccionado->solicitud->titulo ?? '-' }}</div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">Cliente: {{ $requerimientoSeleccionado->solicitud->cliente->nombre ?? ($requerimientoSeleccionado->solicitud->cliente->razon_social ?? '-') }}</div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">Responsable del caso: {{ $requerimientoSeleccionado->solicitud->responsable?->name ?? '-' }}</div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">Creada por: {{ $requerimientoSeleccionado->solicitud->creadoPor?->name ?? '-' }}</div>
                    </div>

                    <div class="space-y-3 rounded-lg border border-gray-200 p-4 dark:border-gray-700">
                        <h5 class="font-semibold text-stone-700 dark:text-white">Contexto de la solicitud</h5>
                        <p class="whitespace-pre-line text-sm text-gray-700 dark:text-gray-300">
                            {{ $requerimientoSeleccionado->solicitud->descripcion ?: 'Sin descripcion adicional en la solicitud.' }}
                        </p>

                        @if ($requerimientoSeleccionado->solicitud->archivos->isNotEmpty())
                            <div class="space-y-2">
                                <div class="text-xs font-medium text-stone-700 dark:text-white">Archivos de apoyo</div>
                                @foreach ($requerimientoSeleccionado->solicitud->archivos as $archivo)
                                    <a href="{{ $archivo->archivo ? Storage::disk('public')->url($archivo->archivo) : $archivo->archivo_drive_url }}"
                                        target="_blank"
                                        class="block text-sm text-amber-700 hover:underline dark:text-amber-300">
                                        {{ $archivo->nombre }}
                                    </a>
                                @endforeach
                            </div>
                        @endif

                        @if ($requerimientoSeleccionado->solicitud->modo_solicitud === 'definida' && !empty($requerimientoSeleccionado->solicitud->resumen_formulario))
                            <div class="space-y-3 rounded-lg border border-sky-200 bg-sky-50/70 p-4 dark:border-sky-900/60 dark:bg-sky-950/20">
                                <h6 class="font-semibold text-stone-700 dark:text-white">Formulario capturado</h6>

                                @foreach ($requerimientoSeleccionado->solicitud->resumen_formulario as $campo)
                                    @php
                                        $valorCampo = $campo['value'] ?? null;
                                        $tipoCampo = $campo['type'] ?? 'text';
                                        $archivoFormulario = null;

                                        if ($tipoCampo === 'file' && $valorCampo) {
                                            $archivoFormulario = $requerimientoSeleccionado->solicitud->archivos->firstWhere('nombre', $valorCampo);
                                        }
                                    @endphp

                                    <div class="rounded-lg border border-white/70 bg-white/70 p-3 dark:border-gray-700 dark:bg-gray-900/40">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span class="text-sm font-medium text-stone-700 dark:text-white">
                                                {{ $campo['label'] ?? ($campo['key'] ?? 'Campo') }}
                                            </span>
                                            @if (!empty($campo['required']))
                                                <span class="rounded-full bg-rose-100 px-2 py-0.5 text-[11px] font-medium text-rose-700 dark:bg-rose-900/40 dark:text-rose-300">
                                                    Requerido
                                                </span>
                                            @endif
                                        </div>

                                        @if ($tipoCampo === 'checkbox')
                                            <div class="mt-1 text-sm text-gray-700 dark:text-gray-300">
                                                {{ $valorCampo ? 'Si' : 'No' }}
                                            </div>
                                        @elseif ($tipoCampo === 'file')
                                            @if ($archivoFormulario)
                                                <a href="{{ $archivoFormulario->archivo ? Storage::disk('public')->url($archivoFormulario->archivo) : $archivoFormulario->archivo_drive_url }}"
                                                    target="_blank"
                                                    class="mt-1 block text-sm text-amber-700 hover:underline dark:text-amber-300">
                                                    {{ $archivoFormulario->nombre }}
                                                </a>
                                            @else
                                                <div class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                                    {{ $valorCampo ?: 'Sin archivo capturado.' }}
                                                </div>
                                            @endif
                                        @else
                                            <div class="mt-1 text-sm text-gray-700 dark:text-gray-300">
                                                {{ filled($valorCampo) ? $valorCampo : 'Sin respuesta capturada.' }}
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    <div class="space-y-3 rounded-lg border border-gray-200 p-4 dark:border-gray-700">
                        <h5 class="font-semibold text-stone-700 dark:text-white">Lo solicitado</h5>
                        <p class="whitespace-pre-line text-sm text-gray-700 dark:text-gray-300">{{ $requerimientoSeleccionado->descripcion ?: 'Sin descripcion.' }}</p>
                        <div class="text-xs text-gray-500 dark:text-gray-400">
                            Creado por {{ $requerimientoSeleccionado->creadoPor?->name ?? '-' }} el {{ $requerimientoSeleccionado->created_at?->format('d/m/Y H:i') ?? '-' }}
                        </div>
                    </div>

                    <div class="space-y-3 rounded-lg border border-gray-200 p-4 dark:border-gray-700">
                        <h5 class="font-semibold text-stone-700 dark:text-white">Respuesta</h5>
                        <textarea wire:model="respuesta_texto" rows="4"
                            @disabled(in_array($requerimientoSeleccionado->estado, ['validado', 'cancelado']))
                            class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-amber-600 focus:outline-none focus:ring focus:ring-amber-500/40 disabled:opacity-60 dark:border-gray-600 dark:bg-gray-700 dark:text-white"></textarea>
                        @error('respuesta_texto') <div class="mt-1 text-xs text-red-500">{{ $message }}</div> @enderror

                        @if ($requerimientoSeleccionado->esRequerimientoFormulario())
                            <div class="space-y-3 rounded-lg border border-sky-200 bg-sky-50/70 p-4 dark:border-sky-900/60 dark:bg-sky-950/20">
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <h6 class="font-semibold text-stone-700 dark:text-white">Formulario solicitado</h6>
                                    <span class="rounded-full bg-sky-100 px-2.5 py-1 text-xs text-sky-700 dark:bg-sky-900/40 dark:text-sky-300">
                                        {{ $requerimientoSeleccionado->solicitud->estado_formulario_label }}
                                    </span>
                                </div>

                                @foreach ($requerimientoSeleccionado->solicitud->campos_formulario as $campo)
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
                                                    @disabled(in_array($requerimientoSeleccionado->estado, ['validado', 'cancelado']))
                                                    class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-amber-600 focus:outline-none focus:ring focus:ring-amber-500/40 disabled:opacity-60 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                                    placeholder="{{ $campo['placeholder'] ?? '' }}"></textarea>
                                            @elseif ($campoType === 'select')
                                                <select wire:model.defer="formulario_respuesta.{{ $campoKey }}"
                                                    @disabled(in_array($requerimientoSeleccionado->estado, ['validado', 'cancelado']))
                                                    class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-amber-600 focus:outline-none focus:ring focus:ring-amber-500/40 disabled:opacity-60 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                                    <option value="">Seleccione...</option>
                                                    @foreach (($campo['options'] ?? []) as $option)
                                                        <option value="{{ $option }}">{{ $option }}</option>
                                                    @endforeach
                                                </select>
                                            @elseif ($campoType === 'checkbox')
                                                <label class="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                                                    <input type="checkbox" wire:model.defer="formulario_respuesta.{{ $campoKey }}"
                                                        @disabled(in_array($requerimientoSeleccionado->estado, ['validado', 'cancelado']))
                                                        class="rounded border-gray-300 text-amber-600 focus:ring-amber-500 dark:border-gray-600 dark:bg-gray-700">
                                                    <span>{{ $campo['help'] ?? 'Marcar si aplica' }}</span>
                                                </label>
                                            @elseif ($campoType === 'date')
                                                <input type="date" wire:model.defer="formulario_respuesta.{{ $campoKey }}"
                                                    @disabled(in_array($requerimientoSeleccionado->estado, ['validado', 'cancelado']))
                                                    class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-amber-600 focus:outline-none focus:ring focus:ring-amber-500/40 disabled:opacity-60 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                            @elseif ($campoType === 'number')
                                                <input type="number" wire:model.defer="formulario_respuesta.{{ $campoKey }}"
                                                    @disabled(in_array($requerimientoSeleccionado->estado, ['validado', 'cancelado']))
                                                    class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-amber-600 focus:outline-none focus:ring focus:ring-amber-500/40 disabled:opacity-60 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                                    placeholder="{{ $campo['placeholder'] ?? '' }}">
                                            @elseif ($campoType === 'file')
                                                <input type="file" wire:model="formulario_respuesta.{{ $campoKey }}"
                                                    @disabled(in_array($requerimientoSeleccionado->estado, ['validado', 'cancelado']))
                                                    @if (!empty($campo['accept'])) accept="{{ $campo['accept'] }}" @endif
                                                    class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-amber-600 focus:outline-none focus:ring focus:ring-amber-500/40 disabled:opacity-60 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                                    Tamano maximo sugerido: 20 MB.
                                                    @if (!empty($campo['accept']))
                                                        Tipos sugeridos: {{ $campo['accept'] }}
                                                    @endif
                                                </div>
                                                @php
                                                    $archivoActualFormulario = null;
                                                    $valorActualArchivo = $requerimientoSeleccionado->solicitud->datos_formulario[$campoKey] ?? null;

                                                    if ($valorActualArchivo) {
                                                        $archivoActualFormulario = $requerimientoSeleccionado->solicitud->archivos->firstWhere('nombre', $valorActualArchivo);
                                                    }
                                                @endphp
                                                @if ($archivoActualFormulario)
                                                    <a href="{{ $archivoActualFormulario->archivo ? Storage::disk('public')->url($archivoActualFormulario->archivo) : $archivoActualFormulario->archivo_drive_url }}"
                                                        target="_blank"
                                                        class="text-xs text-amber-600 hover:underline dark:text-amber-300">
                                                        Archivo actual: {{ $archivoActualFormulario->nombre }}
                                                    </a>
                                                @endif
                                            @else
                                                <input type="text" wire:model.defer="formulario_respuesta.{{ $campoKey }}"
                                                    @disabled(in_array($requerimientoSeleccionado->estado, ['validado', 'cancelado']))
                                                    class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-amber-600 focus:outline-none focus:ring focus:ring-amber-500/40 disabled:opacity-60 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
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
                                @endforeach
                            </div>
                        @endif

                        @if ($requerimientoSeleccionado->estado === 'rechazado' && $requerimientoSeleccionado->comentario_validacion)
                            <div class="space-y-2 rounded-lg border border-red-200 p-3 dark:border-red-800">
                                <div class="text-sm font-medium text-red-700 dark:text-red-300">Respuesta rechazada</div>
                                <p class="whitespace-pre-line text-sm text-gray-700 dark:text-gray-300">{{ $requerimientoSeleccionado->comentario_validacion }}</p>
                            </div>
                        @endif

                        @if (!$requerimientoSeleccionado->esRequerimientoFormulario())
                            <div class="rounded-lg border border-dashed border-gray-300 p-3 dark:border-gray-700">
                                <div class="mb-2 text-sm font-medium text-stone-700 dark:text-white">Documentos de respuesta</div>
                                <p class="mb-2 text-xs text-gray-500 dark:text-gray-400">
                                    Adjunta documentos si aplica, aunque solo respondas con texto.
                                </p>
                                @livewire('shared.archivos-adjuntos-crud', ['modelo' => $requerimientoSeleccionado], key('mis-req-archivos-' . $requerimientoSeleccionado->id))
                            </div>
                        @endif

                        @if (!$requerimientoSeleccionado->esRequerimientoFormulario() && $requerimientoSeleccionado->archivos->isNotEmpty())
                            <div class="space-y-2">
                                @foreach ($requerimientoSeleccionado->archivos as $archivo)
                                    <a href="{{ $archivo->archivo ? Storage::disk('public')->url($archivo->archivo) : $archivo->archivo_drive_url }}"
                                        target="_blank"
                                        class="block text-sm text-amber-700 hover:underline dark:text-amber-300">
                                        {{ $archivo->nombre }}
                                    </a>
                                @endforeach
                            </div>
                        @endif

                        <div class="flex justify-end">
                            <button wire:click="guardarRespuesta"
                                @click="window.dispatchEvent(new CustomEvent('spinner-on'))"
                                wire:loading.attr="disabled"
                                wire:target="guardarRespuesta"
                                @disabled(in_array($requerimientoSeleccionado->estado, ['validado', 'cancelado']))
                                class="rounded bg-amber-600 px-4 py-2 text-sm text-white hover:bg-amber-700 disabled:opacity-60">
                                Guardar respuesta
                            </button>
                        </div>
                    </div>
                @else
                    <div class="text-sm text-gray-500 dark:text-gray-400">
                        No se pudo cargar el requerimiento.
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
