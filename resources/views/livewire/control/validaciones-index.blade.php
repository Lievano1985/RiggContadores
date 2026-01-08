<div x-data="{ sidebar: @entangle('sidebarVisible') }">

    {{-- Título y Filtro --}}
    <div class="flex justify-between items-center mb-4">
        <h2 class="text-xl font-bold text-stone-600 dark:text-white">Validar obligaciones realizadas prueba

        </h2>
        <input type="text" wire:model.live="search" placeholder="Buscar cliente..."
            class="px-3 py-2 border rounded dark:bg-gray-700 dark:text-white focus:outline-amber-600">
    </div>

    {{-- Tabla --}}
    <div class="bg-white dark:bg-gray-900 border rounded shadow-sm">
        <table class="min-w-full divide-y divide-gray-300 dark:divide-gray-700">
            <thead class="bg-stone-100 dark:bg-stone-900 text-xs">
                <tr>
                    <th class="px-2 py-2"></th>
                    <th class="px-3 py-2 text-left">Cliente</th>
                    <th class="px-3 py-2 text-left">Obligación</th>
                    <th class="px-3 py-2 text-left">Contador</th>
                    <th class="px-3 py-2 text-center">Tareas</th>
                    <th class="px-3 py-2 text-right">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-300 dark:divide-gray-700 text-sm">
                @forelse($obligaciones as $oc)
                    @php $open = $expandida[$oc->id] ?? false; @endphp

                    <tr wire:key="obligacion-{{ $oc->id }}" class="hover:bg-gray-50 dark:hover:bg-gray-800/60">
                        <td class="px-2">
                            <button wire:click="toggleExpandida({{ $oc->id }})" class="hover:underline">
                                {{ $open ? '−' : '+' }}
                            </button>
                        </td>
                        <td class="px-3 py-2">{{ $oc->cliente->nombre }}</td>
                        <td class="px-3 py-2">{{ $oc->obligacion->nombre }}</td>
                        <td class="px-3 py-2">{{ $oc->contador->name ?? '—' }}</td>
                        <td class="px-3 py-2 text-center">{{ $oc->tareasAsignadas->count() }}</td>
                        <td class="px-3 py-2 text-right">
                            <button wire:click="abrirSidebar({{ $oc->id }})"
                                class="bg-amber-600 text-white px-3 py-1 rounded hover:bg-amber-700 text-sm">
                                Revisar / Enviar
                            </button>
                        </td>
                    </tr>

                    {{-- Subfila con tareas --}}
                    @if ($open)
                        <tr wire:key="tareas-{{ $oc->id }}" class="bg-gray-50 dark:bg-gray-800/40">
                            <td colspan="6" class="px-6 pb-4 pt-1">
                                @if ($oc->tareasAsignadas->isEmpty())
                                    <p class="text-sm italic text-gray-500 dark:text-gray-400">
                                        Esta obligación no tiene tareas ligadas.
                                    </p>
                                @else
                                    <ul class="list-disc ml-4 text-gray-700 dark:text-gray-300 text-sm">
                                        @foreach ($oc->tareasAsignadas->sortBy(fn($t) => $t->tareaCatalogo->nombre ?? '') as $tarea)
                                            <li>
                                                {{ $tarea->tareaCatalogo->nombre ?? 'Sin nombre' }}
                                                — {{ ucfirst($tarea->estatus) }}
                                            </li>
                                        @endforeach
                                    </ul>
                                @endif
                            </td>
                        </tr>
                    @endif

                @empty
                    <tr>
                        <td colspan="6" class="py-6 text-center text-gray-500">No hay obligaciones para validar.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $obligaciones->links() }}</div>

  {{-- SIDEBAR --}}
<div x-cloak x-show="sidebar" x-transition.opacity class="fixed inset-0 bg-black/40 z-40 flex justify-end">
    <div class="flex-1" @click="$wire.cerrarSidebar()"></div>

    <div class="w-full max-w-xl bg-white dark:bg-gray-900 shadow-xl h-full border-l flex flex-col">

        {{-- Header --}}
        <div class="p-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
            <h3 class="text-lg font-semibold text-stone-700 dark:text-white">
                Revisión y validación
            </h3>
            <button @click="$wire.cerrarSidebar()" class="text-gray-500 hover:text-black">✕</button>
        </div>

        {{-- Contenido --}}
        <div class="flex-1 overflow-y-auto p-4 space-y-6">

            @if ($obligacionSeleccionada)

                {{-- OBLIGACIÓN --}}
                <div class="border p-4 rounded shadow-sm dark:border-gray-700">
                    <h4 class="font-semibold mb-2">Obligación</h4>

                    <p><strong>Cliente:</strong> {{ $obligacionSeleccionada->cliente->nombre }}</p>
                    <p><strong>Obligación:</strong> {{ $obligacionSeleccionada->obligacion->nombre }}</p>
                    <p><strong>Contador:</strong> {{ $obligacionSeleccionada->contador->name ?? '—' }}</p>
                    <p><strong>Comentario:</strong> {{ $obligacionSeleccionada->comentario ?? '—' }}</p>

                    {{-- Archivos de la obligación --}}
                    @if($obligacionSeleccionada->archivos->count())
                        <div class="mt-3 space-y-1">
                            <strong>Archivos:</strong>
                            @foreach($obligacionSeleccionada->archivos as $archivo)
                                @if($archivo->archivo)
                                    <a href="{{ Storage::disk('public')->url($archivo->archivo) }}"
                                       target="_blank"
                                       class="block text-blue-600 text-sm hover:underline">
                                        📄 {{ $archivo->nombre }}
                                    </a>
                                @endif

                                @if($archivo->archivo_drive_url)
                                    <a href="{{ $archivo->archivo_drive_url }}"
                                       target="_blank"
                                       class="block text-green-600 text-sm hover:underline">
                                        ☁️ {{ $archivo->nombre }}
                                    </a>
                                @endif
                            @endforeach
                        </div>
                    @endif

                    {{-- Rechazo obligación --}}
                    @if(!$mostrarRechazoObligacion)
                        <button wire:click="$set('mostrarRechazoObligacion', true)"
                            class="mt-3 text-sm text-red-600 border border-red-400 px-3 py-1 rounded">
                            Rechazar obligación
                        </button>
                    @else
                        <div class="mt-3">
                            <textarea wire:model.defer="comentarioRechazoObligacion"
                                class="w-full px-3 py-2 border rounded dark:bg-gray-700 dark:text-white"
                                placeholder="Motivo del rechazo"></textarea>

                            <div class="mt-2 text-right space-x-2">
                                <button wire:click="rechazarObligacion"
                                    class="bg-red-600 text-white px-4 py-1 rounded text-sm">
                                    Confirmar
                                </button>
                                <button wire:click="$set('mostrarRechazoObligacion', false)"
                                    class="px-3 py-1 border rounded text-sm">
                                    Cancelar
                                </button>
                            </div>
                        </div>
                    @endif
                </div>

                {{-- TAREAS --}}
                @if($obligacionSeleccionada->tareasAsignadas->count())
                    <div class="space-y-4">
                        <h4 class="font-semibold">Tareas asignadas</h4>

                        @foreach($obligacionSeleccionada->tareasAsignadas as $tarea)
                            <div class="border p-4 rounded dark:border-gray-700">
                                <p><strong>Tarea:</strong> {{ $tarea->tareaCatalogo->nombre }}</p>
                                <p><strong>Estatus:</strong> {{ ucfirst($tarea->estatus) }}</p>
                                <p><strong>Comentario:</strong> {{ $tarea->comentario ?? '—' }}</p>

                                {{-- Archivos de la tarea --}}
                                @if($tarea->archivos->count())
                                    <div class="mt-2 space-y-1">
                                        <strong>Archivos:</strong>
                                        @foreach($tarea->archivos as $archivo)
                                            @if($archivo->archivo)
                                                <a href="{{ Storage::disk('public')->url($archivo->archivo) }}"
                                                   target="_blank"
                                                   class="block text-blue-600 text-sm hover:underline">
                                                    📄 {{ $archivo->nombre }}
                                                </a>
                                            @endif

                                            @if($archivo->archivo_drive_url)
                                                <a href="{{ $archivo->archivo_drive_url }}"
                                                   target="_blank"
                                                   class="block text-green-600 text-sm hover:underline">
                                                    ☁️ {{ $archivo->nombre }}
                                                </a>
                                            @endif
                                        @endforeach
                                    </div>
                                @endif

                                {{-- Rechazo tarea --}}
                                @if(empty($mostrarRechazoTarea[$tarea->id]))
                                    <button wire:click="$set('mostrarRechazoTarea.'.$tarea->id, true)"
                                        class="mt-2 text-sm text-red-600 border border-red-400 px-3 py-1 rounded">
                                        Rechazar tarea
                                    </button>
                                @else
                                    <div class="mt-2">
                                        <textarea wire:model.defer="comentarioRechazoTarea.{{ $tarea->id }}"
                                            class="w-full px-3 py-2 border rounded dark:bg-gray-700 dark:text-white"
                                            placeholder="Motivo del rechazo"></textarea>

                                        <div class="mt-2 text-right space-x-2">
                                            <button wire:click="rechazarTarea({{ $tarea->id }})"
                                                class="bg-red-600 text-white px-4 py-1 rounded text-sm">
                                                Confirmar
                                            </button>
                                            <button wire:click="$set('mostrarRechazoTarea.'.$tarea->id, false)"
                                                class="px-3 py-1 border rounded text-sm">
                                                Cancelar
                                            </button>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif

            @endif
        </div>

        {{-- Footer --}}
        <div class="p-4 border-t text-right">
            <button wire:click="enviarAlCliente"
                class="bg-amber-600 text-white px-4 py-2 rounded">
                Enviar al cliente
            </button>
        </div>
    </div>
</div>


</div>
