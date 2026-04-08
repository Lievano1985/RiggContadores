{{-- 
Componente Blade: Catalogo de Obligaciones
Autor: Luis Lievano - JL3 Digital
--}}
<div class="p-6 bg-white dark:bg-gray-900 text-gray-900 dark:text-white space-y-4">

    <!-- Encabezado -->
    <div class="flex justify-between items-center">
        <h2 class="text-xl font-bold text-stone-600 dark:text-white">Catalogo de Obligaciones</h2>
        <button wire:click="showCreateForm"
            class="px-4 py-2 bg-amber-600 text-white rounded hover:bg-amber-700 transition">
            + Nueva obligacion
        </button>
    </div>

    <!-- Buscador -->
    <div class="flex items-center gap-4">
        <input type="text" wire:model.live.debounce.500ms="search"
            class="w-1/2 px-3 py-2 border rounded-md dark:bg-gray-700 dark:text-white
                   border-gray-300 dark:border-gray-600
                   focus:border-amber-600 focus:ring focus:ring-amber-500/40 focus:outline-none"
            placeholder="Buscar por nombre, tipo, categoria o periodicidad...">
    </div>

    <!-- Tabla -->
    <div class="bg-white dark:bg-gray-900 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-stone-100 dark:bg-stone-900">
                <tr>
                    <x-sortable-th field="nombre" label="Nombre" :sort-field="$sortField" :sort-direction="$sortDirection" />
                    <x-sortable-th field="categoria" label="Categoría" :sort-field="$sortField" :sort-direction="$sortDirection" />
                    <x-sortable-th field="tipo" label="Tipo" :sort-field="$sortField" :sort-direction="$sortDirection" />
                    <x-sortable-th field="periodicidad" label="Periodicidad" :sort-field="$sortField" :sort-direction="$sortDirection" />
                    <th class="px-4 py-2 text-left text-xs font-semibold">Mes límite</th>
                    <th class="px-4 py-2 text-left text-xs font-semibold">Día límite</th>
                    <th class="px-4 py-2 text-center text-xs font-semibold">Enviable</th>
                    <th class="px-4 py-2 text-center text-xs font-semibold">Activa</th>
                    <th class="px-4 py-2 text-left text-xs font-semibold">Acciones</th>
                </tr>
            </thead>

            <tbody class="divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                @forelse ($obligaciones as $obligacion)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/60">
                        <td class="px-4 py-2">{{ $obligacion->nombre }}</td>

                        {{-- CATEGORIA --}}
                        <td class="px-4 py-2">
                            {{ $categorias[$obligacion->categoria] ?? ucfirst($obligacion->categoria) }}
                        </td>

                        <td class="px-4 py-2 capitalize">{{ $obligacion->tipo }}</td>
                        <td class="px-4 py-2 capitalize">{{ $obligacion->periodicidad }}</td>
                        <td class="px-4 py-2">{{ $obligacion->desfase_meses ?? '-' }}</td>
                        <td class="px-4 py-2">Dia {{ $obligacion->dia_corte ?? '-' }}</td>
                        <td class="px-4 py-2 text-center">
                            <select
                                wire:change="actualizarRequiereEnvioCliente({{ $obligacion->id }}, $event.target.value)"
                                class="mx-auto px-2 py-1 border rounded text-xs dark:bg-gray-700 dark:text-white border-gray-300 dark:border-gray-600 focus:border-amber-600 focus:ring focus:ring-amber-500/40 focus:outline-none">
                                <option value="1" @selected($obligacion->requiere_envio_cliente)>Si</option>
                                <option value="0" @selected(!$obligacion->requiere_envio_cliente)>No</option>
                            </select>
                        </td>
                        <td class="px-4 py-2 text-center">
                            <span
                                class="px-2 py-1 text-xs rounded {{ $obligacion->activa ? 'bg-green-600 text-white' : 'bg-gray-400 text-black' }}">
                                {{ $obligacion->activa ? 'Si' : 'No' }}
                            </span>
                        </td>
                        <td class="px-4 py-2">
                            <div class="flex items-center gap-1">
                                <x-action-icon icon="edit" label="Editar" variant="primary"
                                    wire:click="showEditForm({{ $obligacion->id }})" />
                                <x-action-icon icon="trash" label="Eliminar" variant="danger"
                                    wire:click="confirmarEliminacion({{ $obligacion->id }})" />
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="px-4 py-4 text-center text-gray-500 dark:text-gray-300">
                            No se encontraron registros.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Paginacion -->
    @include('livewire.shared.pagination-controls', ['paginator' => $obligaciones])

    <!-- Modal -->
    @if ($modalFormVisible)
        <div class="fixed inset-0 flex items-center justify-center bg-stone-600/50 z-50">
            <div class="bg-white dark:bg-gray-900 p-6 rounded-lg shadow-lg w-full max-w-2xl">
                <h3 class="text-lg font-semibold text-stone-600 dark:text-white mb-4">
                    {{ $isEdit ? 'Editar obligacion' : 'Nueva obligacion' }}
                </h3>

                <form wire:submit.prevent="save" class="space-y-4">

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                        <div>
                            <label class="block text-sm mb-1">Nombre</label>
                            <input type="text" wire:model.defer="nombre"
                                class="w-full px-3 py-2 border rounded dark:bg-gray-700 dark:text-white border-gray-300 dark:border-gray-600 focus:border-amber-600 focus:ring focus:ring-amber-500/40 focus:outline-none">
                        </div>

                        {{-- CATEGORIA --}}
                        <div>
                            <label class="block text-sm mb-1">Categoria</label>
                            <select wire:model.defer="categoria"
                                class="w-full px-3 py-2 border rounded dark:bg-gray-700 dark:text-white border-gray-300 dark:border-gray-600 focus:border-amber-600 focus:ring focus:ring-amber-500/40 focus:outline-none">
                                <option value="">Seleccione...</option>
                                @foreach ($categorias as $valor => $label)
                                    <option value="{{ $valor }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm mb-1">Periodicidad</label>
                            <select wire:model.live="periodicidad"
                                class="w-full px-3 py-2 border rounded dark:bg-gray-700 dark:text-white border-gray-300 dark:border-gray-600 focus:border-amber-600 focus:ring focus:ring-amber-500/40 focus:outline-none">
                                <option value="mensual">Mensual</option>
                                <option value="bimestral">Bimestral</option>
                                <option value="trimestral">Trimestral</option>
                                <option value="cuatrimestral">Cuatrimestral</option>
                                <option value="semestral">Semestral</option>
                                <option value="anual">Anual</option>
                                <option value="unica">Unica</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm mb-1">Tipo</label>
                            <select wire:model.defer="tipo"
                                class="w-full px-3 py-2 border rounded dark:bg-gray-700 dark:text-white border-gray-300 dark:border-gray-600 focus:border-amber-600 focus:ring focus:ring-amber-500/40 focus:outline-none">
                                <option value="">Seleccione...</option>
                                <option value="federal">Federal</option>
                                <option value="estatal">Estatal</option>
                                <option value="local">Local</option>
                                <option value="patronal">Patronal</option>
                            </select>
                        </div>

                        <div class="md:col-span-2">
                            <label class="flex items-center gap-2 text-sm mb-1">
                                <input type="checkbox" wire:model.defer="requiere_envio_cliente"
                                    class="rounded border-gray-300 text-amber-600 focus:ring-amber-500">
                                Requiere envio al cliente
                            </label>
                        </div>

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

    <x-confirmacion-eliminacion :confirmingDelete="$confirmingDelete" action="eliminarConfirmada" />
</div>

