<div x-data="{ sidebar: @entangle('sidebarFormularioVisible') }" class="p-6 bg-white dark:bg-gray-900 text-gray-900 dark:text-white rounded-lg shadow space-y-4">

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
                                <x-action-icon icon="document-text" label="Formulario" variant="info"
                                    wire:click="abrirFormulario({{ $tipo->id }})" />
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

    <div x-cloak x-show="sidebar" class="fixed inset-0 z-40">
        <div x-cloak x-show="sidebar" x-transition.opacity
            class="absolute inset-0 bg-black/40"
            @click="$wire.cerrarFormulario()"></div>

        <div x-cloak x-show="sidebar"
            x-transition:enter="transform transition ease-out duration-300"
            x-transition:enter-start="translate-x-full opacity-95"
            x-transition:enter-end="translate-x-0 opacity-100"
            x-transition:leave="transform transition ease-in duration-220"
            x-transition:leave-start="translate-x-0 opacity-100"
            x-transition:leave-end="translate-x-full opacity-95"
            class="absolute inset-y-0 right-0 w-full max-w-2xl bg-white dark:bg-gray-900 shadow-xl border-l flex flex-col">
            <div class="p-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
                <div>
                    <h3 class="text-lg font-semibold text-stone-700 dark:text-white">Formulario predefinido</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ $tipoFormularioNombre }}</p>
                </div>
                <button @click="$wire.cerrarFormulario()" class="text-gray-500 hover:text-black dark:hover:text-white">x</button>
            </div>

            <div class="flex-1 overflow-y-auto p-4 space-y-6">
                <div>
                    <h4 class="font-semibold text-stone-700 dark:text-white">Campos del formulario</h4>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Define los campos del formulario y usa el boton de previsualizacion para revisar el resultado.
                    </p>
                </div>

                <div class="flex justify-end">
                    <button wire:click="abrirPreview" type="button"
                        class="px-4 py-2 border rounded text-sm dark:border-gray-600 dark:text-white hover:border-amber-600">
                        Previsualizar
                    </button>
                </div>

                <div class="space-y-3">
                    @forelse ($formularioCampos as $index => $campo)
                        <div class="border rounded-lg p-4 dark:border-gray-700 bg-white dark:bg-gray-900">
                            <div class="flex flex-wrap gap-3 justify-between items-start">
                                <div class="space-y-1">
                                    <div class="font-medium text-stone-700 dark:text-white">
                                        {{ $campo['label'] }}
                                    </div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                        `{{ $campo['key'] }}` | {{ $campo['type'] }}{{ !empty($campo['required']) ? ' | requerido' : '' }}
                                    </div>
                                    @if (!empty($campo['options']))
                                        <div class="text-xs text-gray-500 dark:text-gray-400">
                                            Opciones: {{ implode(', ', $campo['options']) }}
                                        </div>
                                    @endif
                                    @if (!empty($campo['accept']))
                                        <div class="text-xs text-gray-500 dark:text-gray-400">
                                            Tipos permitidos: {{ $campo['accept'] }}
                                        </div>
                                    @endif
                                </div>

                                <div class="flex items-center gap-1">
                                    <button wire:click="subirCampo({{ $index }})" class="px-2 py-1 text-xs border rounded dark:border-gray-600">Subir</button>
                                    <button wire:click="bajarCampo({{ $index }})" class="px-2 py-1 text-xs border rounded dark:border-gray-600">Bajar</button>
                                    <button wire:click="editarCampo({{ $index }})" class="px-2 py-1 text-xs border rounded dark:border-gray-600">Editar</button>
                                    <button wire:click="eliminarCampo({{ $index }})" class="px-2 py-1 text-xs border rounded text-red-600 dark:border-gray-600">Eliminar</button>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="border rounded-lg p-4 text-sm text-gray-500 dark:text-gray-400 dark:border-gray-700">
                            Este formulario aun no tiene campos definidos.
                        </div>
                    @endforelse
                </div>

                <div class="border rounded-lg p-4 space-y-4 dark:border-gray-700">
                    <h4 class="font-semibold text-stone-700 dark:text-white">
                        {{ $campoEditandoIndex !== null ? 'Editar campo' : 'Nuevo campo' }}
                    </h4>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium mb-1">Etiqueta</label>
                            <input type="text" wire:model.defer="campoForm.label"
                                class="w-full px-3 py-2 border rounded-md dark:bg-gray-700 dark:text-white border-gray-300 dark:border-gray-600 focus:border-amber-600 focus:ring focus:ring-amber-500/40 focus:outline-none">
                            @error('campoForm.label') <div class="text-red-500 text-xs mt-1">{{ $message }}</div> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-1">Clave</label>
                            <input type="text" wire:model.defer="campoForm.key"
                                class="w-full px-3 py-2 border rounded-md dark:bg-gray-700 dark:text-white border-gray-300 dark:border-gray-600 focus:border-amber-600 focus:ring focus:ring-amber-500/40 focus:outline-none">
                            @error('campoForm.key') <div class="text-red-500 text-xs mt-1">{{ $message }}</div> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-1">Tipo</label>
                            <select wire:model.live="campoForm.type"
                                class="w-full px-3 py-2 border rounded-md dark:bg-gray-700 dark:text-white border-gray-300 dark:border-gray-600 focus:border-amber-600 focus:ring focus:ring-amber-500/40 focus:outline-none">
                                <option value="text">Text</option>
                                <option value="textarea">Textarea</option>
                                <option value="number">Number</option>
                                <option value="date">Date</option>
                                <option value="select">Select</option>
                                <option value="checkbox">Checkbox</option>
                                <option value="file">File</option>
                            </select>
                            @error('campoForm.type') <div class="text-red-500 text-xs mt-1">{{ $message }}</div> @enderror
                        </div>

                        <div>
                            <label class="inline-flex items-center cursor-pointer mt-7">
                                <input type="checkbox" wire:model.defer="campoForm.required" class="sr-only peer">
                                <div
                                    class="relative w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-amber-300 dark:peer-focus:ring-amber-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-amber-600 dark:peer-checked:bg-amber-600">
                                </div>
                                <span class="ms-3 text-sm font-medium text-gray-900 dark:text-gray-300">Requerido</span>
                            </label>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium mb-1">Placeholder</label>
                            <input type="text" wire:model.defer="campoForm.placeholder"
                                class="w-full px-3 py-2 border rounded-md dark:bg-gray-700 dark:text-white border-gray-300 dark:border-gray-600 focus:border-amber-600 focus:ring focus:ring-amber-500/40 focus:outline-none">
                            @error('campoForm.placeholder') <div class="text-red-500 text-xs mt-1">{{ $message }}</div> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-1">Ayuda</label>
                            <input type="text" wire:model.defer="campoForm.help"
                                class="w-full px-3 py-2 border rounded-md dark:bg-gray-700 dark:text-white border-gray-300 dark:border-gray-600 focus:border-amber-600 focus:ring focus:ring-amber-500/40 focus:outline-none">
                            @error('campoForm.help') <div class="text-red-500 text-xs mt-1">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    @if (($campoForm['type'] ?? 'text') === 'select')
                        <div>
                            <label class="block text-sm font-medium mb-1">Opciones</label>
                            <textarea wire:model.defer="campoForm.options_text" rows="4"
                                placeholder="Una opcion por linea"
                                class="w-full px-3 py-2 border rounded-md dark:bg-gray-700 dark:text-white border-gray-300 dark:border-gray-600 focus:border-amber-600 focus:ring focus:ring-amber-500/40 focus:outline-none"></textarea>
                            @error('campoForm.options_text') <div class="text-red-500 text-xs mt-1">{{ $message }}</div> @enderror
                        </div>
                    @endif

                    @if (($campoForm['type'] ?? 'text') === 'file')
                        <div>
                            <label class="block text-sm font-medium mb-1">Tipos permitidos</label>
                            <input type="text" wire:model.defer="campoForm.accept"
                                placeholder=".pdf,.xml,image/*"
                                class="w-full px-3 py-2 border rounded-md dark:bg-gray-700 dark:text-white border-gray-300 dark:border-gray-600 focus:border-amber-600 focus:ring focus:ring-amber-500/40 focus:outline-none">
                            @error('campoForm.accept') <div class="text-red-500 text-xs mt-1">{{ $message }}</div> @enderror
                        </div>
                    @endif

                    <div class="flex justify-end gap-2">
                        <button wire:click="nuevoCampo" type="button" class="px-4 py-2 border rounded text-sm dark:border-gray-600">
                            Limpiar
                        </button>
                        <button wire:click="guardarCampo" type="button" class="px-4 py-2 bg-amber-600 text-white rounded hover:bg-amber-700 text-sm">
                            Guardar campo
                        </button>
                    </div>
                </div>

            </div>

            <div class="p-4 border-t border-gray-200 dark:border-gray-700 flex justify-end gap-2">
                <button wire:click="cerrarFormulario" class="px-4 py-2 border rounded text-sm dark:border-gray-600 dark:text-white">
                    Cancelar
                </button>
                <button wire:click="guardarFormulario" class="bg-amber-600 text-white px-4 py-2 rounded hover:bg-amber-700 text-sm">
                    Guardar formulario
                </button>
            </div>
        </div>
    </div>

    @if ($modalPreviewVisible)
        <div class="fixed inset-0 flex items-center justify-center bg-stone-600/50 z-50 p-4">
            <div class="bg-white dark:bg-gray-900 p-6 rounded-lg shadow-lg w-full max-w-3xl max-h-[90vh] overflow-y-auto">
                <div class="flex justify-between items-center mb-4">
                    <div>
                        <h3 class="text-lg font-semibold text-stone-700 dark:text-white">Previsualizacion del formulario</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ $tipoFormularioNombre }}</p>
                    </div>
                    <button wire:click="cerrarPreview" class="text-gray-500 hover:text-black dark:hover:text-white">x</button>
                </div>

                @if (count($formularioCampos))
                    <div class="space-y-4">
                        @foreach ($formularioCampos as $campo)
                            <div>
                                <label class="block text-sm font-medium mb-1">
                                    {{ $campo['label'] }}
                                    @if (!empty($campo['required']))
                                        <span class="text-red-500">*</span>
                                    @endif
                                </label>

                                @if (($campo['type'] ?? 'text') === 'textarea')
                                    <textarea rows="3" disabled
                                        placeholder="{{ $campo['placeholder'] ?? '' }}"
                                        class="w-full px-3 py-2 border rounded-md bg-gray-50 dark:bg-gray-800 dark:text-white border-gray-300 dark:border-gray-600"></textarea>
                                @elseif (($campo['type'] ?? 'text') === 'select')
                                    <select disabled
                                        class="w-full px-3 py-2 border rounded-md bg-gray-50 dark:bg-gray-800 dark:text-white border-gray-300 dark:border-gray-600">
                                        <option>Seleccione...</option>
                                        @foreach (($campo['options'] ?? []) as $option)
                                            <option>{{ $option }}</option>
                                        @endforeach
                                    </select>
                                @elseif (($campo['type'] ?? 'text') === 'checkbox')
                                    <label class="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                                        <input type="checkbox" disabled class="rounded border-gray-300 dark:border-gray-600">
                                        <span>{{ $campo['help'] ?? 'Opcion seleccionable' }}</span>
                                    </label>
                                @elseif (($campo['type'] ?? 'text') === 'file')
                                    <input type="file" disabled
                                        class="w-full px-3 py-2 border rounded-md bg-gray-50 dark:bg-gray-800 dark:text-white border-gray-300 dark:border-gray-600">
                                    @if (!empty($campo['accept']))
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                            Permitidos: {{ $campo['accept'] }}
                                        </p>
                                    @endif
                                @else
                                    <input type="{{ ($campo['type'] ?? 'text') === 'number' ? 'number' : (($campo['type'] ?? 'text') === 'date' ? 'date' : 'text') }}"
                                        disabled
                                        placeholder="{{ $campo['placeholder'] ?? '' }}"
                                        class="w-full px-3 py-2 border rounded-md bg-gray-50 dark:bg-gray-800 dark:text-white border-gray-300 dark:border-gray-600">
                                @endif

                                @if (!empty($campo['help']) && ($campo['type'] ?? 'text') !== 'checkbox')
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                        {{ $campo['help'] }}
                                    </p>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-sm text-gray-500 dark:text-gray-400">
                        Aun no hay campos para previsualizar.
                    </div>
                @endif

                <div class="flex justify-end mt-6">
                    <button wire:click="cerrarPreview"
                        class="bg-amber-600 text-white px-4 py-2 rounded hover:bg-amber-700">
                        Cerrar
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
