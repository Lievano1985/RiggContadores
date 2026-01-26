<div class="p-6 bg-white dark:bg-gray-900 text-gray-900 dark:text-white space-y-4">

    <!-- Encabezado y botón -->
    <div class="flex justify-between items-center">
        <h2 class="text-xl font-bold text-stone-600 dark:text-white">Catálogo de Regímenes</h2>
        <button wire:click="showCreateForm"
            class="px-4 py-2 bg-amber-950 text-white rounded hover:bg-amber-700">
            + Nuevo régimen
        </button>
    </div>

    <div class="flex items-center gap-4">
        <input type="text" wire:model.live.debounce.500ms="search"
            class="w-1/2 px-3 py-2 border rounded-md 
                   dark:bg-gray-700 dark:text-white 
                   border-gray-300 dark:border-gray-600 
                   focus:border-amber-600 focus:ring focus:ring-amber-500/40 
                   focus:outline-none"
            placeholder="Buscar por nombre o clave...">
    </div>

    <!-- Tabla -->
    <div class="overflow-x-auto rounded shadow">
        <table class="min-w-full divide-y divide-gray-300 dark:divide-gray-700">
            <thead class="bg-stone-100 dark:bg-stone-900">
                <tr>
                    <th class="px-4 py-2 text-left " wire:click="sortBy('nombre')">
                        Nombre
                        @if ($sortField === 'nombre')
                            <span>{{ $sortDirection === 'asc' ? '▲' : '▼' }}</span>
                        @endif
                    </th>
                    <th class="px-4 py-2 text-left " wire:click="sortBy('clave_sat')">
                        Clave
                        @if ($sortField === 'clave_sat')
                            <span>{{ $sortDirection === 'asc' ? '▲' : '▼' }}</span>
                        @endif
                    </th>
                    <th class="px-4 py-2 text-left " wire:click="sortBy('tipo_persona')">
                        Persona
                        @if ($sortField === 'tipo_persona')
                            <span>{{ $sortDirection === 'asc' ? '▲' : '▼' }}</span>
                        @endif
                    </th>
                    <th class="px-4 py-2 text-left">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                @foreach ($regimenes as $regimen)
                    <tr>
                        <td class="px-4 py-2">{{ $regimen->nombre }}</td>
                        <td class="px-4 py-2">{{ $regimen->clave_sat }}</td>
                        <td class="px-4 py-2 capitalize">{{ $regimen->tipo_persona }}</td>
                        <td class="px-4 py-2 space-x-2">
                            <button wire:click="showEditForm({{ $regimen->id }})"
                                class="text-amber-600 hover:underline">Editar</button>
                            <button wire:click="delete({{ $regimen->id }})"
                                class="text-red-600 hover:underline">Eliminar</button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <!-- Paginación -->
    <div class="mt-4">
        {{ $regimenes->links() }}
    </div>

    <!-- Modal -->
    @if ($modalFormVisible)
        <div class="fixed inset-0 flex items-center justify-center bg-stone-600/50 z-50">
            <div class="bg-white dark:bg-gray-900 p-6 rounded-lg shadow-lg w-full max-w-lg">
                <h3 class="text-lg font-semibold text-stone-600 dark:text-white mb-4">
                    {{ $isEdit ? 'Editar Régimen' : 'Nuevo Régimen' }}
                </h3>

                <form wire:submit.prevent="save" class="space-y-4">

                    <div>
                        <label class="block text-sm font-medium mb-1">Nombre</label>
                        <input type="text" wire:model.defer="nombre"
                            class="w-full px-3 py-2 border rounded-md 
                                   dark:bg-gray-700 dark:text-white 
                                   border-gray-300 dark:border-gray-600 
                                   focus:border-amber-600 focus:ring focus:ring-amber-500/40 
                                   focus:outline-none">
                        @error('nombre') <div class="text-red-500 text-xs mt-1">{{ $message }}</div> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1">Clave SAT</label>
                        <input type="text" wire:model.defer="clave_sat"
                            class="w-full px-3 py-2 border rounded-md 
                                   dark:bg-gray-700 dark:text-white 
                                   border-gray-300 dark:border-gray-600 
                                   focus:border-amber-600 focus:ring focus:ring-amber-500/40 
                                   focus:outline-none">
                        @error('clave_sat') <div class="text-red-500 text-xs mt-1">{{ 'Error en la clave ingresada' }}</div> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1">Tipo de persona</label>
                        <select wire:model.defer="tipo_persona"
                            class="w-full px-3 py-2 border rounded-md 
                                   dark:bg-gray-700 dark:text-white 
                                   border-gray-300 dark:border-gray-600 
                                   focus:border-amber-600 focus:ring focus:ring-amber-500/40 
                                   focus:outline-none">
                            <option value="">Seleccione...</option>
                            <option value="física">Persona Física</option>
                            <option value="moral">Persona Moral</option>
                            <option value="física/moral">Ambas</option>
                        </select>
                        @error('tipo_persona') <div class="text-red-500 text-xs mt-1">{{ $message }}</div> @enderror
                    </div>

                    <div class="flex justify-end space-x-2 pt-4">
                        <button type="button" wire:click="$set('modalFormVisible', false)"
                            class="bg-gray-300 dark:bg-gray-600 text-black dark:text-white px-4 py-2 rounded hover:bg-gray-400">
                            Cancelar
                        </button>
                        <button type="submit"
                            class="bg-amber-600 text-white px-4 py-2 rounded hover:bg-amber-700">
                            Guardar
                        </button>
                    </div>

                </form>
            </div>
        </div>
    @endif

</div>
