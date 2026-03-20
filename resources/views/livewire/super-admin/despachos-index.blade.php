<div class="p-6 bg-white dark:bg-gray-900 text-gray-900 dark:text-white">
    <div class="flex justify-between items-center mb-4">
        <h2 class="text-xl font-bold text-stone-600">Gestion de Despachos</h2>
        <button wire:click="crear" class="px-4 py-2 bg-amber-600 text-white rounded hover:bg-amber-700">
            + Nuevo Despacho
        </button>
    </div>

    <div class="bg-white dark:bg-gray-900 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-stone-100 dark:bg-stone-900">
                <tr>
                    <x-sortable-th field="nombre" label="Nombre" :sort-field="$sortField" :sort-direction="$sortDirection" />
                    <x-sortable-th field="rfc" label="RFC" :sort-field="$sortField" :sort-direction="$sortDirection" />
                    <x-sortable-th field="correo_contacto" label="Correo" :sort-field="$sortField" :sort-direction="$sortDirection" />
                    <th class="px-4 py-2 text-left text-xs font-semibold">Teléfono</th>
                    <th class="px-4 py-2 text-left text-xs font-semibold">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                @forelse ($despachos as $d)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/60">
                        <td class="px-4 py-2">{{ $d->nombre }}</td>
                        <td class="px-4 py-2">{{ $d->rfc }}</td>
                        <td class="px-4 py-2">{{ $d->correo_contacto }}</td>
                        <td class="px-4 py-2">{{ $d->telefono_contacto }}</td>
                        <td class="px-4 py-2">
                            <div class="flex items-center gap-1">
                                <x-action-icon icon="edit" label="Editar" variant="primary"
                                    wire:click="editar({{ $d->id }})" />
                                <x-action-icon icon="trash" label="Eliminar" variant="danger"
                                    wire:click="confirmarEliminar({{ $d->id }})" />
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-4 text-center text-gray-500 dark:text-gray-400">
                            No hay despachos registrados.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        @include('livewire.shared.pagination-controls', ['paginator' => $despachos])
    </div>

    @if ($modalFormVisible)
        <div class="fixed inset-0 flex items-center justify-center bg-stone-600/50 z-50">
            <div class="bg-white dark:bg-gray-900 rounded-lg w-full max-w-lg shadow-lg text-gray-900 dark:text-white max-h-[90vh] overflow-y-auto p-6">
                <h3 class="text-lg font-semibold mb-4 text-stone-600">
                    {{ $isEdit ? 'Editar Despacho' : 'Crear Despacho' }}
                </h3>

                <form wire:submit.prevent="guardar" class="space-y-4">
                    <div>
                        <label class="block text-sm mb-1">Nombre</label>
                        <input type="text" wire:model.defer="nombre" class="w-full px-3 py-2 border rounded dark:bg-gray-700 dark:text-white focus:outline-amber-600 focus:outline">
                        @error('nombre') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm mb-1">RFC</label>
                        <input type="text" wire:model.defer="rfc" class="w-full px-3 py-2 border rounded dark:bg-gray-700 dark:text-white focus:outline-amber-600 focus:outline">
                        @error('rfc') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>

                   {{--  <div>
                        <label class="block text-sm mb-1">Correo de contacto</label>
                        <input type="email" wire:model.defer="correo_contacto" class="w-full px-3 py-2 border rounded dark:bg-gray-700 dark:text-white focus:outline-amber-600 focus:outline">
                        @error('correo_contacto') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm mb-1">Telefono</label>
                        <input type="text" wire:model.defer="telefono_contacto" class="w-full px-3 py-2 border rounded dark:bg-gray-700 dark:text-white focus:outline-amber-600 focus:outline">
                        @error('telefono_contacto') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div> --}}

                  {{--   <div>
                        <label class="block text-sm mb-1">Carpeta Drive (opcional)</label>
                        <input type="text" wire:model.defer="drive_folder_id" class="w-full px-3 py-2 border rounded dark:bg-gray-700 dark:text-white focus:outline-amber-600 focus:outline">
                        @error('drive_folder_id') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div> --}}

                    <div>
                        <label class="block text-sm mb-1">Politica de almacenamiento</label>
                        <select wire:model.live="politica_almacenamiento" class="w-full px-3 py-2 border rounded dark:bg-gray-700 dark:text-white focus:border-amber-600 focus:outline-none">
                         
                            <option value="">Selecciona una opcion</option>
                            <option value="storage_only">Laravel Storage</option>
                            <option value="drive_only">Google Drive</option>
                            <option value="both">Ambos</option>
                        </select>
                        @error('politica_almacenamiento') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>

                    @if (!$isEdit)
                        <hr class="my-4 border-gray-300 dark:border-gray-600" />
                        <h4 class="text-md font-semibold text-stone-600">Administrador del despacho</h4>

                        <div>
                            <label class="block text-sm mb-1">Nombre</label>
                            <input type="text" wire:model.defer="admin_name" class="w-full px-3 py-2 border rounded dark:bg-gray-700 dark:text-white focus:outline-amber-600 focus:outline">
                            @error('admin_name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-sm mb-1">Correo</label>
                            <input type="email" wire:model.defer="admin_email" class="w-full px-3 py-2 border rounded dark:bg-gray-700 dark:text-white focus:outline-amber-600 focus:outline">
                            @error('admin_email') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-sm mb-1">Contrasena</label>
                            <input type="password" wire:model.defer="admin_password" class="w-full px-3 py-2 border rounded dark:bg-gray-700 dark:text-white focus:outline-amber-600 focus:outline">
                            @error('admin_password') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                    @else
                        <hr class="my-4 border-gray-300 dark:border-gray-600" />
                        <h4 class="text-md font-semibold text-stone-600">Administrador asignado</h4>

                        <div>
                            <label class="block text-sm mb-1">Nombre</label>
                            <input type="text" value="{{ $admin_name }}" readonly class="w-full px-3 py-2 border rounded dark:bg-gray-700 dark:text-white opacity-60 cursor-not-allowed">
                        </div>

                        <div>
                            <label class="block text-sm mb-1">Correo</label>
                            <input type="email" value="{{ $admin_email }}" readonly class="w-full px-3 py-2 border rounded dark:bg-gray-700 dark:text-white opacity-60 cursor-not-allowed">
                        </div>
                    @endif

                    <div class="flex justify-end space-x-2 mt-4">
                        <button type="button" wire:click="resetForm" class="px-4 py-2 bg-amber-600 text-white rounded hover:bg-amber-700">
                            Cancelar
                        </button>
                        <button type="submit" class="px-4 py-2 bg-amber-600 text-white rounded hover:bg-amber-700">
                            Guardar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    @if ($confirmingDelete)
        <div class="fixed inset-0 flex items-center justify-center bg-stone-600 bg-opacity-50 z-50">
            <div class="bg-white dark:bg-gray-900 p-6 rounded-lg shadow-lg max-w-md w-full text-gray-900 dark:text-white">
                <h3 class="text-lg font-semibold mb-4 text-stone-600">Eliminar Despacho?</h3>
                <p class="mb-4">Esta accion no se puede deshacer.</p>
                <div class="flex justify-end space-x-2">
                    <button wire:click="resetForm" class="px-4 py-2 bg-amber-600 text-white rounded hover:bg-amber-700">
                        Cancelar
                    </button>
                    <button wire:click="eliminar" class="px-4 py-2 bg-amber-600 text-white rounded hover:bg-amber-700">
                        Eliminar
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
