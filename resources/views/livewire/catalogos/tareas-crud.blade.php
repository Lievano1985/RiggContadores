<div class="p-6 bg-white dark:bg-gray-900 rounded-lg shadow space-y-4">

    <div class="flex justify-between items-center">
        <h2 class="text-xl font-bold text-stone-600">Catalogo de tareas</h2>
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
            <option value="sin">Sin obligacion</option>
            @foreach ($obligaciones as $obligacion)
                <option value="{{ $obligacion->id }}">{{ $obligacion->nombre }}</option>
            @endforeach
        </select>
    </div>

    <div class="bg-white dark:bg-gray-900 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-x-auto mt-4">
    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-stone-100 dark:bg-stone-900">
            <tr>
                <x-sortable-th field="nombre" label="Nombre" :sort-field="$sortField" :sort-direction="$sortDirection" />
                <x-sortable-th field="obligacion" label="Obligación" :sort-field="$sortField" :sort-direction="$sortDirection" />
                <x-sortable-th field="activo" label="Activo" :sort-field="$sortField" :sort-direction="$sortDirection" align="center" />
                <th class="px-4 py-2 text-center text-xs font-semibold">Acciones</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200 dark:divide-gray-700 text-sm">
            @foreach ($tareas as $tarea)
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/60">
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
                        <div class="flex items-center justify-center gap-1">
                            <x-action-icon icon="edit" label="Editar" variant="info"
                                wire:click="editar({{ $tarea->id }})" />
                            <x-action-icon icon="trash" label="Eliminar" variant="danger"
                                wire:click="confirmarEliminacion({{ $tarea->id }})" />
                        </div>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
    </div>

    @include('livewire.shared.pagination-controls', ['paginator' => $tareas])

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
                    <label>Descripcion</label>
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
                    <label>Obligacion relacionada (opcional)</label>
                    <select wire:model="form.obligacion_id"
                    class="w-full px-3 py-2 border rounded-md 
                           dark:bg-gray-700 dark:text-white 
                           border-gray-300 dark:border-gray-600
                           focus:border-amber-600 focus:ring focus:ring-amber-500/40">
                    <option value="sin">Sin obligacion</option>
                
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
                        class="bg-amber-600 text-white px-4 py-2 rounded hover:bg-amber-700">
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

</div>

