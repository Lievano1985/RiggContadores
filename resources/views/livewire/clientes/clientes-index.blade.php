<div class="p-6 dark:bg-gray-900 bg-white text-gray-900 dark:text-white">

    <!-- Título y Botón Crear Cliente -->
    <div class="flex justify-between items-center mb-4">
        <h2 class="text-xl font-bold text-stone-600">Clientes</h2>

        <button wire:click="abrirModalCrear" class="px-4 py-2 bg-amber-950 text-white rounded hover:bg-amber-700">
            + Crear Cliente
        </button>
    </div>

    <!-- Tabla de clientes -->
    <div class="overflow-x-auto rounded shadow">
        <table class="min-w-full divide-y divide-gray-300 dark:divide-gray-700">
            <thead class="bg-stone-100 dark:bg-stone-900">
                <tr>
                    <th class="px-4 py-2 text-left">Nombre</th>
                    <th class="px-4 py-2 text-left">RFC</th>
                    <th class="px-4 py-2 text-left">Teléfono</th>
                    <th class="px-4 py-2 text-left">Correo</th>
                    <th class="px-4 py-2 text-left">Tipo</th>
                    @if (auth()->user()->hasRole('super_admin'))
                        <th class="px-4 py-2 text-left">Despacho</th>
                    @endif
                    <th class="px-4 py-2 text-left">Acciones</th>
                    <th class="px-4 py-2 text-left">Asignaciones</th>

                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                @forelse ($clientes as $cliente)
                    <tr>
                        <td class="px-4 py-2">{{ $cliente->nombre }}</td>
                        <td class="px-4 py-2">{{ $cliente->rfc }}</td>
                        <td class="px-4 py-2">{{ $cliente->telefono }}</td>
                        <td class="px-4 py-2">{{ $cliente->correo }}</td>
                        <td class="px-4 py-2 capitalize">{{ str_replace('_', ' ', $cliente->tipo_persona) }}</td>
                        @if (auth()->user()->hasRole('super_admin'))
                            <td class="px-4 py-2">{{ $cliente->despacho->nombre ?? '-' }}</td>
                        @endif
                        <td class="px-4 py-2 space-x-2">
                            {{--  <button wire:click="abrirModalEditar({{ $cliente->id }})"
                                class="text-stone-600 hover:underline">Editar</button> --}}
                                @hasrole('admin_despacho')
                            <button wire:click="confirmarEliminar({{ $cliente->id }})"
                                class="text-red-600 hover:underline">Eliminar</button>
                                @endhasrole

                            <a href="{{ route('clientes.expediente.show', $cliente->id) }}"
                                class="text-green-600 hover:underline">Expediente</a>

                        </td>
                        <td class="px-4 py-2">
                            @if ($cliente->asignaciones_completas)
                                <span class="px-2 py-1 text-xs bg-green-100 text-green-800 rounded-full">✔
                                    Completo</span>
                            @else
                                <span class="px-2 py-1 text-xs bg-red-100 text-red-800 rounded-full">⚠ Incompleto</span>
                            @endif
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
        {{ $clientes->links() }}
    </div>

    <!-- Modal Crear/Editar -->
    @if ($modalFormVisible)
        <div class="fixed inset-0 flex items-center justify-center bg-stone-600/50 z-50">
            <div
                class="bg-white dark:bg-gray-900 text-gray-900 dark:text-white p-6 rounded-lg w-full max-w-2xl shadow-lg overflow-y-auto max-h-[90vh]">
                <h3 class="text-lg font-semibold mb-4 text-stone-600">
                    {{ $clienteId ? 'Editar Cliente' : 'Crear Cliente' }}
                </h3>

                <form wire:submit.prevent="guardar" class="space-y-4" x-data="{ tipo: @entangle('tipo_persona') }">

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                        <div>
                            <label class="block text-sm mb-1">Tipo de Persona</label>
                            <select wire:model.live="tipo_persona"
                                class="w-full px-3 py-2 border rounded dark:bg-gray-700 dark:text-white 
                                   focus:border-amber-600 focus:outline-none"
                                required>
                                <option value="">-- Selecciona --</option>
                                <option value="fisica">Persona Física</option>
                                <option value="moral">Persona Moral</option>
                            </select>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        </div>




                        <!-- Resto de campos igual -->
                        <div>
                            <label class="block text-sm mb-1">Razón social</label>
                            <input type="text" wire:model.defer="razon_social"
                                class="uppercase w-full px-3 py-2 border rounded dark:bg-gray-700 dark:text-white focus:outline-amber-600 focus:outline focus:outline"
                                oninput="this.value = this.value.toUpperCase()" required>
                        </div>
                        <div>
                            <label class="block text-sm mb-1">Nombre comercial</label>
                            <input type="text" wire:model.defer="nombre"
                                class="uppercase w-full px-3 py-2 border rounded dark:bg-gray-700 dark:text-white focus:outline-amber-600 focus:outline focus:outline"
                                oninput="this.value = this.value.toUpperCase()" required>
                        </div>



                        <div>
                            <label class="block text-sm mb-1">RFC</label>
                            <input type="text" wire:model.defer="rfc"
                                class="uppercase w-full px-3 py-2 border rounded dark:bg-gray-700 dark:text-white focus:outline-amber-600 focus:outline focus:outline"
                                oninput="this.value = this.value.toUpperCase()" required>
                            @error('rfc')
                                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="block text-sm mb-1">CURP</label>
                            <input type="text" wire:model.defer="curp" @disabled($tipo_persona === 'moral')
                                class="uppercase w-full px-3 py-2 border rounded dark:bg-gray-700 dark:text-white 
                                      focus:outline-amber-600 focus:outline disabled:bg-gray-200 disabled:dark:bg-gray-600 disabled:cursor-not-allowed"
                                oninput="this.value = this.value.toUpperCase()"
                                @if ($tipo_persona === 'fisica') required @endif>
                            @error('curp')
                                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm mb-1">Correo</label>
                            <input type="email" wire:model.defer="correo"
                                class="w-full px-3 py-2 border rounded dark:bg-gray-700 dark:text-white focus:outline-amber-600 focus:outline"
                                required>
                        </div>

                        <div>
                            <label class="block text-sm mb-1">Teléfono</label>
                            <input type="text" wire:model.defer="telefono"
                                class="w-full px-3 py-2 border rounded dark:bg-gray-700 dark:text-white focus:outline-amber-600 focus:outline">
                        </div>

                        {{--   <div>
                        <label class="block text-sm mb-1">Tiene trabajadores</label>
                        <select wire:model.defer="tiene_trabajadores"
                                class="w-full px-3 py-2 border rounded dark:bg-gray-700 dark:text-white focus:border-amber-600 focus:outline-none">
                            <option value="1">Sí</option>
                            <option value="0">No</option>
                        </select>
                    </div>
             --}}
                    </div>

                    <div class="flex justify-end space-x-2 mt-6">
                        <button type="button" wire:click="$set('modalFormVisible', false)"
                            class="px-4 py-2 bg-gray-300 dark:bg-gray-600 text-black dark:text-white rounded hover:bg-gray-400">
                            Cancelar
                        </button>
                        <button type="submit" class="px-4 py-2 bg-stone-600 text-white rounded hover:bg-stone-700">
                            Guardar
                        </button>
                    </div>
                </form>
                <x-spinner target="guardar" />

            
            </div>
        </div>
    @endif
    @if ($confirmingDelete)
        <div class="fixed inset-0 flex items-center justify-center bg-stone-600/70 z-50">
            <div
                class="bg-white dark:bg-gray-900 p-6 rounded-lg shadow-lg border border-amber-900 text-center w-full max-w-md mx-auto">
                <p class="text-stone-600 dark:text-white text-lg font-semibold mb-4">
                    ¿Estás seguro que deseas eliminar este cliente?
                </p>
                <div class="flex justify-center space-x-4">
                    <button wire:click="eliminar" class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700">
                        Sí, eliminar
                    </button>
                    <button wire:click="$set('confirmingDelete', false)"
                        class="px-4 py-2 bg-gray-300 dark:bg-gray-600 text-black dark:text-white rounded hover:bg-gray-400">
                        Cancelar
                    </button>
                </div>
            </div>
        </div>
    @endif
    @if (session()->has('message'))
        <div x-data="{ show: true }" x-init="setTimeout(() => show = false, 3000)" x-show="show"
            x-transition:leave="transition ease-in duration-500"
            x-transition:leave-start="opacity-100 transform translate-y-0"
            x-transition:leave-end="opacity-0 transform -translate-y-10"
            class="fixed bottom-6 left-1/2 transform -translate-x-1/2 z-50 w-full max-w-sm p-4 text-sm text-green-800 bg-green-200 rounded-lg shadow-lg dark:bg-green-200 dark:text-green-900">
            {{ session('message') }}
        </div>
    @endif
</div>
