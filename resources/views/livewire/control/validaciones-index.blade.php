<div x-data="{ sidebar: @entangle('sidebarVisible') }">

    {{-- Título y Filtro --}}
    <div class="flex items-center justify-between mb-4">
        <h2 class="text-xl font-bold text-stone-600 dark:text-white">Validar obligaciones realizadas</h2>

        <input type="text" wire:model.live="search" placeholder="Buscar cliente..."
            class="px-3 py-2 border rounded dark:bg-gray-700 dark:text-white focus:outline-amber-600">
    </div>

    {{-- Tabla principal --}}
    <div class="bg-white dark:bg-gray-900 rounded-lg shadow-sm border dark:border-gray-700">
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

                    {{-- Fila principal --}}
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/60">
                        <td class="px-2">
                            <button wire:click="toggleExpandida({{ $oc->id }})" class="hover:underline">
                                {{ $open ? '−' : '+' }}
                            </button>
                        </td>
                        <td class="px-3 py-2">{{ $oc->cliente->nombre }}</td>
                        <td class="px-3 py-2">{{ $oc->obligacion->nombre }}</td>
                        <td class="px-3 py-2">{{ $oc->contador->name ?? '—' }}</td>
                        <td class="px-3 py-2 text-center">
                            {{ $oc->tareasAsignadas->count() }}
                        </td>
                        <td class="px-3 py-2 text-right">
                            <button wire:click="abrirSidebar({{ $oc->id }})"
                                class="text-sm bg-amber-600 text-white px-3 py-1 rounded hover:bg-amber-700">
                                Revisar / Enviar
                            </button>
                        </td>
                    </tr>

                    {{-- Subfila: tareas asignadas --}}
                    @if ($open)
                        <tr class="bg-gray-50 dark:bg-gray-800/40">
                            <td colspan="6" class="px-6 pb-4 pt-1">
                                @if ($oc->tareasAsignadas->isEmpty())
                                    <p class="text-sm italic text-gray-500 dark:text-gray-400">
                                        Esta obligación no tiene tareas ligadas.
                                    </p>
                                @else
                                    <ul class="list-disc ml-4 text-gray-700 dark:text-gray-300 text-sm">
                                        @foreach($oc->tareasAsignadas as $tarea)
                                            <li>{{ $tarea->nombre }} — {{ ucfirst($tarea->estatus) }}</li>
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

    {{-- Sidebar --}}
    <div x-cloak x-show="sidebar" x-transition.opacity class="fixed inset-0 bg-black/40 z-40 flex justify-end">
        <div class="flex-1" @click="$wire.cerrarSidebar()"></div>

        <div x-show="sidebar" x-transition class="w-full max-w-lg bg-white dark:bg-gray-900 shadow-xl h-full border-l">
            <div class="p-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
                <h3 class="text-lg font-semibold text-stone-700 dark:text-white">Revisión de obligación</h3>
                <button @click="$wire.cerrarSidebar()" class="text-gray-500 hover:text-black">✕</button>
            </div>

            <div class="p-4 overflow-y-auto space-y-4">
                {{-- Aquí puedes mostrar datos: cliente, obligación, archivo, comentarios --}}
                <p class="text-sm text-gray-600 dark:text-gray-300 italic">Contenido pendiente por definir...</p>
            </div>

            <div class="p-4 border-t border-gray-200 dark:border-gray-700 text-right space-x-2">
                <button @click="$wire.cerrarSidebar()" class="bg-gray-300 dark:bg-gray-700 px-4 py-2 rounded">
                    Cancelar
                </button>
                <button class="bg-amber-600 text-white px-4 py-2 rounded hover:bg-amber-700">
                    Enviar al cliente
                </button>
            </div>
        </div>
    </div>

</div>
