<div class="p-6 bg-white dark:bg-gray-900 text-gray-900 dark:text-white rounded-lg shadow space-y-4">

    <div class="flex justify-between items-center">
        <h2 class="text-xl font-bold text-stone-600 dark:text-white">Catalogo de tipos de solicitud</h2>
        <button wire:click="showCreateForm"
            class="px-4 py-2 bg-amber-600 text-white rounded hover:bg-amber-700">
            + Nuevo tipo
        </button>
    </div>

    <div class="flex flex-col md:flex-row items-stretch md:items-center gap-4">
        <input type="text" wire:model.live.debounce.500ms="search"
            class="w-full px-3 py-2 border rounded-md
                   dark:bg-gray-700 dark:text-white
                   border-gray-300 dark:border-gray-600
                   focus:border-amber-600 focus:ring focus:ring-amber-500/40
                   focus:outline-none"
            placeholder="Buscar por nombre, titulo o aplica para...">
    </div>

    <div class="bg-white dark:bg-gray-900 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
            <thead class="bg-stone-100 dark:bg-stone-900">
                <tr>
                    <x-sortable-th field="nombre" label="Nombre" :sort-field="$sortField" :sort-direction="$sortDirection" />
                    <th class="px-4 py-2 text-left text-xs font-semibold">Titulo sugerido</th>
                    <x-sortable-th field="aplica_para" label="Aplica para" :sort-field="$sortField" :sort-direction="$sortDirection" />
                    <th class="px-4 py-2 text-left text-xs font-semibold">Prioridad</th>
                    <th class="px-4 py-2 text-left text-xs font-semibold">Documentos</th>
                    <x-sortable-th field="activo" label="Activo" :sort-field="$sortField" :sort-direction="$sortDirection" align="center" />
                    <th class="px-4 py-2 text-center text-xs font-semibold">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                @forelse ($tipos as $tipo)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/60">
                        <td class="px-4 py-2 font-medium">{{ $tipo->nombre }}</td>
                        <td class="px-4 py-2">{{ $tipo->titulo_sugerido ?: '-' }}</td>
                        <td class="px-4 py-2 capitalize">{{ $tipo->aplica_para }}</td>
                        <td class="px-4 py-2 capitalize">{{ $tipo->prioridad_default ?: '-' }}</td>
                        <td class="px-4 py-2">
                            {{ $tipo->documentos_sugeridos ? count($tipo->documentos_sugeridos) . ' sugeridos' : '-' }}
                        </td>
                        <td class="px-4 py-2 text-center">
                            <label class="inline-flex items-center cursor-pointer mt-1">
                                <input type="checkbox" wire:click="toggleActivo({{ $tipo->id }})"
                                    {{ $tipo->activo ? 'checked' : '' }} class="sr-only peer">
                                <div
                                    class="relative w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-amber-300 dark:peer-focus:ring-amber-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-amber-600 dark:peer-checked:bg-amber-600">
                                </div>
                            </label>
                        </td>
                        <td class="px-4 py-2 text-center">
                            <div class="flex items-center justify-center gap-1">
                                <x-action-icon icon="edit" label="Editar" variant="primary"
                                    wire:click="showEditForm({{ $tipo->id }})" />
                                <x-action-icon icon="trash" label="Eliminar" variant="danger"
                                    wire:click="confirmDelete({{ $tipo->id }})" />
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-4 text-center text-gray-500 dark:text-gray-400">
                            No hay tipos de solicitud registrados.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        @include('livewire.shared.pagination-controls', ['paginator' => $tipos])
    </div>

    @if ($modalFormVisible)
        <div class="fixed inset-0 flex items-center justify-center bg-stone-600/50 z-50 p-4">
            <div class="bg-white dark:bg-gray-900 p-6 rounded-lg shadow-lg w-full max-w-3xl max-h-[90vh] overflow-y-auto">
                <h3 class="text-lg font-semibold text-stone-600 dark:text-white mb-4">
                    {{ $isEdit ? 'Editar tipo de solicitud' : 'Nuevo tipo de solicitud' }}
                </h3>

                <form wire:submit.prevent="save" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium mb-1">Nombre</label>
                            <input type="text" wire:model.defer="nombre"
                                class="w-full px-3 py-2 border rounded-md dark:bg-gray-700 dark:text-white border-gray-300 dark:border-gray-600 focus:border-amber-600 focus:ring focus:ring-amber-500/40 focus:outline-none">
                            @error('nombre') <div class="text-red-500 text-xs mt-1">{{ $message }}</div> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-1">Titulo sugerido</label>
                            <input type="text" wire:model.defer="titulo_sugerido"
                                class="w-full px-3 py-2 border rounded-md dark:bg-gray-700 dark:text-white border-gray-300 dark:border-gray-600 focus:border-amber-600 focus:ring focus:ring-amber-500/40 focus:outline-none">
                            @error('titulo_sugerido') <div class="text-red-500 text-xs mt-1">{{ $message }}</div> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-1">Prioridad default</label>
                            <select wire:model.defer="prioridad_default"
                                class="w-full px-3 py-2 border rounded-md dark:bg-gray-700 dark:text-white border-gray-300 dark:border-gray-600 focus:border-amber-600 focus:ring focus:ring-amber-500/40 focus:outline-none">
                                <option value="">Seleccione...</option>
                                <option value="baja">Baja</option>
                                <option value="media">Media</option>
                                <option value="alta">Alta</option>
                                <option value="urgente">Urgente</option>
                            </select>
                            @error('prioridad_default') <div class="text-red-500 text-xs mt-1">{{ $message }}</div> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-1">Aplica para</label>
                            <select wire:model.defer="aplica_para"
                                class="w-full px-3 py-2 border rounded-md dark:bg-gray-700 dark:text-white border-gray-300 dark:border-gray-600 focus:border-amber-600 focus:ring focus:ring-amber-500/40 focus:outline-none">
                                <option value="cliente">Cliente</option>
                                <option value="despacho">Despacho</option>
                                <option value="ambos">Ambos</option>
                            </select>
                            @error('aplica_para') <div class="text-red-500 text-xs mt-1">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1">Descripcion sugerida</label>
                        <textarea wire:model.defer="descripcion_sugerida" rows="3"
                            class="w-full px-3 py-2 border rounded-md dark:bg-gray-700 dark:text-white border-gray-300 dark:border-gray-600 focus:border-amber-600 focus:ring focus:ring-amber-500/40 focus:outline-none"></textarea>
                        @error('descripcion_sugerida') <div class="text-red-500 text-xs mt-1">{{ $message }}</div> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1">Documentos sugeridos</label>
                        <textarea wire:model.defer="documentos_sugeridos_texto" rows="4"
                            placeholder="Un documento por linea"
                            class="w-full px-3 py-2 border rounded-md dark:bg-gray-700 dark:text-white border-gray-300 dark:border-gray-600 focus:border-amber-600 focus:ring focus:ring-amber-500/40 focus:outline-none"></textarea>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            Captura un documento sugerido por linea.
                        </p>
                        @error('documentos_sugeridos_texto') <div class="text-red-500 text-xs mt-1">{{ $message }}</div> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1">Configuracion de formulario JSON</label>
                        <textarea wire:model.defer="configuracion_formulario_texto" rows="8"
                            placeholder='{"campos":[{"key":"nss","label":"NSS","type":"text"}]}'
                            class="w-full px-3 py-2 border rounded-md font-mono text-sm dark:bg-gray-700 dark:text-white border-gray-300 dark:border-gray-600 focus:border-amber-600 focus:ring focus:ring-amber-500/40 focus:outline-none"></textarea>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            Opcional. Si lo capturas, debe ser JSON valido.
                        </p>
                        @error('configuracion_formulario_texto') <div class="text-red-500 text-xs mt-1">{{ $message }}</div> @enderror
                    </div>

                    <div>
                        <label class="inline-flex items-center cursor-pointer">
                            <input type="checkbox" wire:model.defer="activo" class="sr-only peer">
                            <div
                                class="relative w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-amber-300 dark:peer-focus:ring-amber-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-amber-600 dark:peer-checked:bg-amber-600">
                            </div>
                            <span class="ms-3 text-sm font-medium text-gray-900 dark:text-gray-300">Activo</span>
                        </label>
                    </div>

                    <div class="flex justify-end space-x-2 pt-4">
                        <button type="button" wire:click="$set('modalFormVisible', false)"
                            class="bg-amber-600 text-white px-4 py-2 rounded hover:bg-amber-700">
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

    <x-confirmacion-eliminacion :confirmingDelete="$confirmingDelete" action="deleteConfirmed" />
</div>
