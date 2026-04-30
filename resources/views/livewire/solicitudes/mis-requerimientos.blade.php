@php use Illuminate\Support\Facades\Storage; @endphp

<div wire:poll.10s x-data="{ sidebar: @entangle('sidebarVisible') }" class="p-6 bg-white dark:bg-gray-900 text-gray-900 dark:text-white rounded-lg shadow space-y-4">
    <div class="flex flex-wrap gap-2 justify-between items-center">
        <h2 class="text-xl font-bold text-stone-600 dark:text-white">Mis requerimientos</h2>
    </div>

    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
        <div class="flex flex-wrap items-center gap-2">
            <input type="text" placeholder="Buscar por cliente o requerimiento" wire:model.live="buscar"
                class="px-3 py-2 border rounded dark:bg-gray-700 dark:text-white focus:border-amber-600 focus:ring focus:ring-amber-500/40 focus:outline-none">

            <select wire:model.live="estado"
                class="px-3 py-2 border rounded dark:bg-gray-700 dark:text-white focus:border-amber-600 focus:ring focus:ring-amber-500/40 focus:outline-none">
                <option value="">Estado (todos)</option>
                <option value="abierto">Abierto</option>
                <option value="respondido">Respondido</option>
                <option value="validado">Validado</option>
                <option value="rechazado">Rechazado</option>
                <option value="cancelado">Cancelado</option>
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
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/60">
                        <td class="px-4 py-2">{{ $requerimiento->solicitud->cliente->nombre ?? ($requerimiento->solicitud->cliente->razon_social ?? '-') }}</td>
                        <td class="px-4 py-2">{{ $requerimiento->solicitud->titulo ?? '-' }}</td>
                        <td class="px-4 py-2">
                            <div class="font-medium">{{ $requerimiento->titulo }}</div>
                            @if ($requerimiento->descripcion)
                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ \Illuminate\Support\Str::limit($requerimiento->descripcion, 80) }}</div>
                            @endif
                        </td>
                        <td class="px-4 py-2">{{ str_replace('_', ' ', $requerimiento->estado) }}</td>
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

    <div x-cloak x-show="sidebar" x-transition.opacity class="fixed inset-0 bg-black/40 z-40 flex justify-end">
        <div class="flex-1" @click="$wire.cerrarSidebar()"></div>

        <div class="w-full max-w-xl bg-white dark:bg-gray-900 shadow-xl h-full border-l flex flex-col">
            <div class="p-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
                <h3 class="text-lg font-semibold text-stone-700 dark:text-white">Detalle de requerimiento</h3>
                <button @click="$wire.cerrarSidebar()" class="text-gray-500 hover:text-black dark:hover:text-white">x</button>
            </div>

            <div class="flex-1 overflow-y-auto p-4 space-y-6">
                @if ($requerimientoSeleccionado)
                    <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4 space-y-2">
                        <div class="font-semibold text-stone-700 dark:text-white">{{ $requerimientoSeleccionado->titulo }}</div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">Solicitud: {{ $requerimientoSeleccionado->solicitud->titulo ?? '-' }}</div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">Cliente: {{ $requerimientoSeleccionado->solicitud->cliente->nombre ?? ($requerimientoSeleccionado->solicitud->cliente->razon_social ?? '-') }}</div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">Responsable del caso: {{ $requerimientoSeleccionado->solicitud->responsable?->name ?? '-' }}</div>
                    </div>

                    <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4 space-y-3">
                        <h5 class="font-semibold text-stone-700 dark:text-white">Lo solicitado</h5>
                        <p class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-line">{{ $requerimientoSeleccionado->descripcion ?: 'Sin descripcion.' }}</p>
                        <div class="text-xs text-gray-500 dark:text-gray-400">
                            Creado por {{ $requerimientoSeleccionado->creadoPor?->name ?? '-' }} el {{ $requerimientoSeleccionado->created_at?->format('d/m/Y H:i') ?? '-' }}
                        </div>
                    </div>

                    <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4 space-y-3">
                        <h5 class="font-semibold text-stone-700 dark:text-white">Respuesta</h5>
                        <textarea wire:model="respuesta_texto" rows="4"
                            @disabled(in_array($requerimientoSeleccionado->estado, ['validado', 'cancelado']))
                            class="w-full px-3 py-2 border rounded-md dark:bg-gray-700 dark:text-white border-gray-300 dark:border-gray-600 focus:border-amber-600 focus:ring focus:ring-amber-500/40 focus:outline-none disabled:opacity-60"></textarea>
                        @error('respuesta_texto') <div class="text-red-500 text-xs mt-1">{{ $message }}</div> @enderror

                        @if ($requerimientoSeleccionado->estado === 'rechazado' && $requerimientoSeleccionado->comentario_validacion)
                            <div class="rounded-lg border border-red-200 dark:border-red-800 p-3 space-y-2">
                                <div class="text-sm font-medium text-red-700 dark:text-red-300">Respuesta rechazada</div>
                                <p class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-line">{{ $requerimientoSeleccionado->comentario_validacion }}</p>
                            </div>
                        @endif

                        <div class="rounded-lg border border-dashed border-gray-300 dark:border-gray-700 p-3">
                            <div class="text-sm font-medium mb-2 text-stone-700 dark:text-white">Documentos de respuesta</div>
                            <p class="mb-2 text-xs text-gray-500 dark:text-gray-400">
                                Adjunta documentos si aplica, aunque solo respondas con texto.
                            </p>
                            @livewire('shared.archivos-adjuntos-crud', ['modelo' => $requerimientoSeleccionado], key('mis-req-archivos-' . $requerimientoSeleccionado->id))
                        </div>

                        @if ($requerimientoSeleccionado->archivos->isNotEmpty())
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
                                @disabled(in_array($requerimientoSeleccionado->estado, ['validado', 'cancelado']))
                                class="bg-amber-600 text-white px-4 py-2 rounded hover:bg-amber-700 text-sm disabled:opacity-60">
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
