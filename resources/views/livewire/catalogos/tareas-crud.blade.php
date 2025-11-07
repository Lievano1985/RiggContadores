<div class="p-6 bg-white dark:bg-gray-900 rounded-lg shadow space-y-4">

    <div class="flex justify-between items-center">
        <h2 class="text-xl font-bold text-stone-600">Catálogo de tareas</h2>
        <button wire:click="crear" class="bg-amber-600 hover:bg-amber-700 text-white px-4 py-2 rounded">
            + Nueva tarea
        </button>
    </div>

    <div class="flex space-x-4 items-center">
        <input type="text" wire:model.live.debounce.500ms="search" placeholder="Buscar por nombre"
            class="w-1/2 px-3 py-2 border rounded-md 
                   dark:bg-gray-700 dark:text-white 
                   border-gray-300 dark:border-gray-600 
                   focus:border-amber-600 focus:ring focus:ring-amber-500/40 
                   focus:outline-none" />

        <select wire:model.live="obligacionFiltro"
            class="w-full px-3 py-2 border rounded-md 
                   dark:bg-gray-700 dark:text-white 
                   border-gray-300 dark:border-gray-600 
                   focus:border-amber-600 focus:ring focus:ring-amber-500/40 
                   focus:outline-none">
            <option value="">-- Todas --</option>
            <option value="sin">Sin obligación</option>
            @foreach($obligaciones as $obligacion)
                <option value="{{ $obligacion->id }}">{{ $obligacion->nombre }}</option>
            @endforeach
        </select>
    </div>

    <table class="min-w-full divide-y divide-gray-300 dark:divide-gray-700 mt-4">
        <thead class="bg-stone-100 dark:bg-stone-900">
            <tr>
                <th class="px-4 py-2 text-left">Nombre</th>
                <th class="px-4 py-2 text-left">Obligación</th>
                <th class="px-4 py-2 text-center">Activo</th>
                <th class="px-4 py-2 text-center">Acciones</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
            @foreach ($tareas as $tarea)
                <tr>
                    <td class="px-4 py-2">{{ $tarea->nombre }}</td>
                    <td class="px-4 py-2">{{ $tarea->obligacion->nombre ?? 'General' }}</td>
                    <td class="px-4 py-2 text-center">

                        <label class="inline-flex items-center cursor-pointer mt-2">
                            <input type="checkbox" wire:click="toggleActivo({{ $tarea->id }})"
                                {{ $tarea->activo ? 'checked' : '' }} class="sr-only peer ">
                            <div
                                class="relative w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-amber-300 dark:peer-focus:ring-amber-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-amber-600 dark:peer-checked:bg-amber-600">
                            </div>
                        </label>
                    </td>
                    <td class="px-4 py-2 text-center">
                        <button wire:click="editar({{ $tarea->id }})"
                            class="text-blue-600 hover:underline">Editar</button>

                        <button wire:click="confirmarEliminacion({{ $tarea->id }})"
                            class="text-red-600 hover:underline">
                            Eliminar
                        </button>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{ $tareas->links() }}

    @if ($modalVisible)
        <div class="fixed inset-0 flex items-center justify-center bg-black/50 z-50">
            <div class="bg-white dark:bg-gray-900 p-6 rounded-lg shadow-lg w-full max-w-md space-y-4">

                <h3 class="text-lg font-bold text-stone-600">
                    {{ $isEditing ? 'Editar tarea' : 'Nueva tarea' }}
                </h3>

                <div>
                    <label>Nombre</label>
                    <input type="text" wire:model.defer="form.nombre"
                        class="w-full px-3 py-2 border rounded-md 
                               dark:bg-gray-700 dark:text-white 
                               border-gray-300 dark:border-gray-600 
                               focus:border-amber-600 focus:ring focus:ring-amber-500/40 
                               focus:outline-none" />
                    @error('form.nombre')
                        <span class="text-red-600 text-sm">{{ $message }}</span>
                    @enderror
                </div>

                <div>
                    <label>Descripción</label>
                    <textarea wire:model.defer="form.descripcion"
                        class="w-full px-3 py-2 border rounded-md 
                               dark:bg-gray-700 dark:text-white 
                               border-gray-300 dark:border-gray-600 
                               focus:border-amber-600 focus:ring focus:ring-amber-500/40 
                               focus:outline-none"></textarea>
                    @error('form.descripcion')
                        <span class="text-red-600 text-sm">{{ $message }}</span>
                    @enderror
                </div>

                <div>
                    <label>Obligación relacionada (opcional)</label>
                    <select wire:model.defer="form.obligacion_id"
                        class="w-full px-3 py-2 border rounded-md 
                               dark:bg-gray-700 dark:text-white 
                               border-gray-300 dark:border-gray-600 
                               focus:border-amber-600 focus:ring focus:ring-amber-500/40 
                               focus:outline-none">
                        <option value="">Sin obligación</option>
                        @foreach ($obligaciones as $obligacion)
                            <option value="{{ $obligacion->id }}">{{ $obligacion->nombre }}</option>
                        @endforeach
                    </select>
                    @error('form.obligacion_id')
                        <span class="text-red-600 text-sm">{{ $message }}</span>
                    @enderror
                </div>

                <div class="flex justify-end space-x-2">
                    <button wire:click="$set('modalVisible', false)"
                        class="bg-gray-300 dark:bg-gray-600 text-black dark:text-white px-4 py-2 rounded">
                        Cancelar
                    </button>
                    <button wire:click="guardar" class="bg-amber-600 hover:bg-amber-700 text-white px-4 py-2 rounded">
                        Guardar
                    </button>
                </div>

            </div>
        </div>
    @endif
    {{--     confirmacion de eliminacion --}}
    <x-confirmacion-eliminacion :confirmingDelete="$confirmingDelete" action="eliminarConfirmada" />
    <x-notification />

</div>
