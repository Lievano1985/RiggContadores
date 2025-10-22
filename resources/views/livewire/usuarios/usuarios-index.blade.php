<div class="p-6 bg-white dark:bg-gray-900 text-gray-900 dark:text-white">
    <div class="flex justify-between mb-4">
        <h2 class="text-xl font-bold text-stone-600">Gestión de Usuarios</h2>
        <button wire:click="crear" class="px-4 py-2 bg-amber-950 text-white rounded hover:bg-amber-700">
            + Crear Usuario
        </button>
    </div>

    <div class="overflow-x-auto rounded shadow">
        <table class="min-w-full divide-y divide-gray-300 dark:divide-gray-700">
            <thead class="bg-stone-100 dark:bg-stone-900">
                <tr>
                    <th class="px-4 py-2 text-left">Nombre</th>
                    <th class="px-4 py-2 text-left">Correo</th>
                    <th class="px-4 py-2 text-left">Rol</th>
                    <th class="px-4 py-2 text-left">Despacho</th>
                    <th class="px-4 py-2 text-left">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                @foreach($usuarios as $user)
                    <tr>
                        <td class="px-4 py-2">{{ $user->name }}</td>
                        <td class="px-4 py-2">{{ $user->email }}</td>
                        <td class="px-4 py-2">{{ $user->roles->pluck('name')->join(', ') }}</td>
                        <td class="px-4 py-2">{{ $user->despacho->nombre ?? '—' }}</td>
                        <td class="px-4 py-2 space-x-2">
                            <button wire:click="editar({{ $user->id }})" class="text-amber-600 hover:underline">Editar</button>
                            <button wire:click="eliminar({{ $user->id }})" class="text-red-600 hover:underline">Eliminar</button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="mt-4">
            {{ $usuarios->links() }}
        </div>
    </div>

    @if($modalFormVisible)
        <div class="fixed inset-0 flex items-center justify-center bg-stone-600 bg-opacity-50 z-50">
            <div class="bg-white dark:bg-gray-900 p-6 rounded-lg shadow-lg w-full max-w-2xl overflow-y-auto max-h-[90vh]">
                <h3 class="text-lg font-bold mb-4 text-stone-600">
                    {{ $modoEdicion ? 'Editar Usuario' : 'Nuevo Usuario' }}
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block mb-1">Nombre</label>
                        <input type="text" wire:model.defer="name" class="w-full px-3 py-2 border rounded dark:bg-gray-700 dark:text-white focus:outline-amber-600 focus:outline">
                        @error('name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block mb-1">Correo</label>
                        <input type="email" wire:model.defer="email" class="w-full px-3 py-2 border rounded dark:bg-gray-700 dark:text-white focus:outline-amber-600 focus:outline">
                        @error('email') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block mb-1">Rol</label>
                        <select wire:model.defer="rol" class="w-full px-3 py-2 border rounded dark:bg-gray-700 dark:text-white focus:outline-amber-600 focus:outline">
                            <option value="">Seleccione</option>
                            @foreach($roles as $rolItem)
                                <option value="{{ $rolItem }}">{{ $rolItem }}</option>
                            @endforeach
                        </select>
                        @error('rol') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block mb-1">Contraseña {{ $modoEdicion ? '(opcional)' : '' }}</label>
                        <input type="password" wire:model.defer="password" class="w-full px-3 py-2 border rounded dark:bg-gray-700 dark:text-white focus:outline-amber-600 focus:outline">
                        @error('password') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>

                    @if(auth()->user()->hasRole('super_admin'))
                        <div>
                            <label class="block mb-1">Despacho</label>
                            <select wire:model.defer="despacho_id" class="w-full px-3 py-2 border rounded dark:bg-gray-700 dark:text-white focus:outline-amber-600 focus:outline">
                                <option value="">Seleccione</option>
                                @foreach($despachos as $despacho)
                                    <option value="{{ $despacho->id }}">{{ $despacho->nombre }}</option>
                                @endforeach
                            </select>
                            @error('despacho_id') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                    @else
                        <input type="hidden" wire:model.defer="despacho_id">
                    @endif
                </div>

                <div class="flex justify-end space-x-2">
                    <button wire:click="$set('modalFormVisible', false)" class="px-4 py-2 bg-gray-300 dark:bg-gray-600 text-black dark:text-white rounded hover:bg-gray-400">Cancelar</button>
                    <button wire:click="guardar" class="px-4 py-2 bg-amber-600 text-white rounded hover:bg-amber-700">Guardar</button>
                </div>
            </div>
        </div>
    @endif
</div>
