<div class="p-6 bg-white dark:bg-gray-900 text-gray-900 dark:text-white space-y-4">

    <div class="flex justify-between items-center">
        <h2 class="text-xl font-bold text-stone-600 dark:text-white">Catálogo de Actividades Económicas</h2>
        <button wire:click="showCreateForm"
            class="px-4 py-2 bg-amber-950 text-white rounded hover:bg-amber-700">
            + Nueva actividad
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
    

    <div class="overflow-x-auto rounded shadow">
        <table class="min-w-full divide-y divide-gray-300 dark:divide-gray-700">
            <thead class="bg-stone-100 dark:bg-stone-900">
                <tr>
                    <th class="px-4 py-2 text-left cursor-pointer" wire:click="sortBy('nombre')">
                        Nombre
                        @if($sortField === 'nombre')
                            <span>{{ $sortDirection === 'asc' ? '▲' : '▼' }}</span>
                        @endif
                    </th>
                    <th class="px-4 py-2 text-left cursor-pointer" wire:click="sortBy('clave')">
                        Clave
                        @if($sortField === 'clave')
                            <span>{{ $sortDirection === 'asc' ? '▲' : '▼' }}</span>
                        @endif
                    </th>
                    <th class="px-4 py-2 text-left">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                @forelse ($actividades as $actividad)
                    <tr>
                        <td class="px-4 py-2">{{ $actividad->nombre }}</td>
                        <td class="px-4 py-2">{{ $actividad->clave }}</td>
                        <td class="px-4 py-2 space-x-2">
                            <button wire:click="showEditForm({{ $actividad->id }})"
                                class="text-amber-600 hover:underline">Editar</button>
                            <button wire:click="delete({{ $actividad->id }})"
                                class="text-red-600 hover:underline">Eliminar</button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="px-4 py-4 text-center text-gray-500 dark:text-gray-400">
                            No se encontraron resultados.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $actividades->links() }}
    </div>

    @if ($modalFormVisible)
        <div class="fixed inset-0 flex items-center justify-center bg-stone-600/50 z-50">
            <div class="bg-white dark:bg-gray-900 p-6 rounded-lg shadow-lg w-full max-w-lg">
                <h3 class="text-lg font-semibold text-stone-600 dark:text-white mb-4">
                    {{ $isEdit ? 'Editar Actividad' : 'Nueva Actividad Económica' }}
                </h3>

                <form wire:submit.prevent="save" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium mb-1">Nombre</label>
                        <input type="text" wire:model.defer="nombre"
                            class="w-full px-3 py-2 border rounded dark:bg-gray-700 dark:text-white focus:outline-amber-600">
                        @error('nombre') <div class="text-red-500 text-xs mt-1">{{ $message }}</div> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1">Clave</label>
                        <input type="text" wire:model.defer="clave"
                            class="w-full px-3 py-2 border rounded dark:bg-gray-700 dark:text-white focus:outline-amber-600">
                        @error('clave') <div class="text-red-500 text-xs mt-1">{{ $message }}</div> @enderror
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
