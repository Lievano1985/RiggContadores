<div x-data="{ sidebar: @entangle('sidebarVisible') }">

<div class="bg-white dark:bg-gray-900 p-6 rounded-lg shadow">

    <h3 class="text-md font-semibold text-stone-600 mb-4">
        Historial de notificaciones
    </h3>

    <div class="bg-white dark:bg-gray-900 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-x-auto">
    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">

                <thead class="bg-stone-100 dark:bg-stone-900">
            <tr>
                <x-sortable-th field="created_at" label="Fecha" :sort-field="$sortField" :sort-direction="$sortDirection" class="px-3 py-2" />
                <x-sortable-th field="asunto" label="Asunto" :sort-field="$sortField" :sort-direction="$sortDirection" class="px-3 py-2" />
                <x-sortable-th field="periodo_mes" label="Periodo" :sort-field="$sortField" :sort-direction="$sortDirection" class="px-3 py-2" />
                <x-sortable-th field="usuario" label="Usuario" :sort-field="$sortField" :sort-direction="$sortDirection" class="px-3 py-2" />
                <th class="px-3 py-2 text-right text-xs font-semibold">Acciones</th>

            </tr>
        </thead>

        <tbody class="divide-y divide-gray-200 dark:divide-gray-700 text-sm">

            @forelse ($notificaciones as $n)
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/60">

                    <td class="px-3 py-2">
                        {{ $n->created_at->format('Y-m-d H:i') }}
                    </td>

                    <td class="px-3 py-2">
                        {{ $n->asunto }}
                    </td>

                    <td class="px-3 py-2">
                        {{ $n->periodo_mes }}/{{ $n->periodo_ejercicio }}
                    </td>

                    <td class="px-3 py-2">
                        {{ $n->usuario->name ?? '-' }}
                    </td>
                    <td class="px-3 py-2 text-right">
                        <x-action-icon icon="eye" label="Ver detalle" variant="primary"
                            wire:click="abrirSidebar({{ $n->id }})" />
                    </td>
                    
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="px-3 py-4 text-center text-gray-500">
                        No hay notificaciones registradas.
                    </td>
                </tr>
            @endforelse

        </tbody>

    </table>
    </div>
{{-- SIDEBAR --}}
<div x-cloak x-show="sidebar" x-transition.opacity
     class="fixed inset-0 bg-black/40 z-40 flex justify-end">

    <div class="flex-1" @click="$wire.cerrarSidebar()"></div>

    <div class="w-full max-w-xl bg-white dark:bg-gray-900 shadow-xl h-full
                border-l flex flex-col">

        {{-- Header --}}
        <div class="p-4 border-b dark:border-gray-700 flex justify-between items-center">
            <h3 class="text-lg font-semibold text-stone-700 dark:text-white">
                Detalle de notificación
            </h3>

            <button wire:click="cerrarSidebar"
                class="text-gray-500 hover:text-black">✕</button>
        </div>

        {{-- Contenido --}}
        <div class="flex-1 overflow-y-auto p-4 space-y-4">

            @if($notificacionSeleccionada)

                {{-- DATOS --}}
                <div class="border p-4 rounded dark:border-gray-700">
                    <p><strong>Asunto:</strong> {{ $notificacionSeleccionada->asunto }}</p>

                    <p class="mt-2">
                        <strong>Mensaje:</strong><br>
                        {{ $notificacionSeleccionada->mensaje }}
                    </p>

                    <p class="mt-2">
                        <strong>Periodo:</strong>
                        {{ $notificacionSeleccionada->periodo_mes }}/{{ $notificacionSeleccionada->periodo_ejercicio }}
                    </p>

                    <p class="mt-2">
                        <strong>Usuario:</strong>
                        {{ $notificacionSeleccionada->usuario->name ?? '-' }}
                    </p>
                </div>

                {{-- OBLIGACIONES --}}
                <div class="border p-4 rounded dark:border-gray-700">
                    <h4 class="font-semibold mb-2">Obligaciones</h4>

                    @forelse($notificacionSeleccionada->obligaciones as $ob)
                        <p class="text-sm">
                            • {{ $ob->obligacion->nombre ?? 'Obligación' }}
                        </p>
                    @empty
                        <p class="text-sm text-gray-500">Sin obligaciones</p>
                    @endforelse
                </div>

                {{-- ARCHIVOS --}}
                <div class="border p-4 rounded dark:border-gray-700">
                    <h4 class="font-semibold mb-2">Archivos enviados</h4>

                    @forelse($notificacionSeleccionada->archivos as $archivo)
                        <a href="{{ Storage::disk('public')->url($archivo->archivo) }}"
                           target="_blank"
                           class="block text-blue-600 text-sm hover:underline">
                            📄 {{ $archivo->nombre }}
                        </a>
                    @empty
                        <p class="text-sm text-gray-500">Sin archivos</p>
                    @endforelse
                </div>

            @endif

        </div>
    </div>
</div>

</div>
</div>
