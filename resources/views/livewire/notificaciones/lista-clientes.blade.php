<div class="p-6 dark:bg-gray-900 bg-white text-gray-900 dark:text-white">

    <!-- Título y Botón Crear Cliente -->
    <div class="flex justify-between items-center mb-4">
        <h2 class="text-xl font-bold text-stone-600 dark:text-white">Notificaciones</h2>


    </div>
<div class="mb-4">
            <input type="text" wire:model.live="buscar" placeholder="Buscar por nombre o RFC..."
                class="uppercase w-full px-3 py-2 border rounded-md 
            dark:bg-gray-700 dark:text-white 
            border-gray-300 dark:border-gray-600 
            focus:border-amber-600 focus:ring focus:ring-amber-500/40 
            focus:outline-none">
        </div>
    <!-- Tabla de clientes -->
    <div class="bg-white dark:bg-gray-900 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-x-auto">
        

        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-stone-100 dark:bg-stone-900">
                <tr>
                    <x-sortable-th field="nombre" label="Nombre" :sort-field="$sortField" :sort-direction="$sortDirection" />
                    <x-sortable-th field="rfc" label="RFC" :sort-field="$sortField" :sort-direction="$sortDirection" />
                    <th class="px-4 py-2 text-left text-xs font-semibold">Correo</th>
                    <x-sortable-th field="tipo_persona" label="Tipo" :sort-field="$sortField" :sort-direction="$sortDirection" />
                    @if (auth()->user()->hasRole('super_admin'))
                        <th class="px-4 py-2 text-left text-xs font-semibold">Despacho</th>
                    @endif
                    <th class="px-4 py-2 text-left text-xs font-semibold">Acciones</th>

                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                @forelse ($clientes as $cliente)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/60">
                        <td class="px-4 py-2">{{ $cliente->nombre }}</td>
                        <td class="px-4 py-2">{{ $cliente->rfc }}</td>
                        <td class="px-4 py-2">{{ $cliente->correo }}</td>
                        <td class="px-4 py-2 capitalize">{{ str_replace('_', ' ', $cliente->tipo_persona) }}</td>
                        @if (auth()->user()->hasRole('super_admin'))
                            <td class="px-4 py-2">{{ $cliente->despacho->nombre ?? '-' }}</td>
                        @endif
                        <td class="px-4 py-2">
                            <div class="flex justify-center items-center">
                                <x-action-icon icon="eye" label="Notificaciones" variant="primary"
                                    :href="route('clientes.notificaciones.show', $cliente->id)" />
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-4 text-center text-gray-500 dark:text-gray-400">
                            No hay clientes registrados.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Paginación -->
    <div class="mt-4">
        @include('livewire.shared.pagination-controls', ['paginator' => $clientes])
    </div>

</div>
