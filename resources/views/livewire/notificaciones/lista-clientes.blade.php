<div class="p-6 dark:bg-gray-900 bg-white text-gray-900 dark:text-white">

    <!-- Título y Botón Crear Cliente -->
    <div class="flex justify-between items-center mb-4">
        <h2 class="text-xl font-bold text-stone-600">Clientes-Notificaciones</h2>


    </div>

    <!-- Tabla de clientes -->
    <div class="overflow-x-auto rounded shadow">
        <div class="mb-4">
            <input type="text" wire:model.live="buscar" placeholder="Buscar por nombre o RFC..."
                class="uppercase w-full px-3 py-2 border rounded-md 
            dark:bg-gray-700 dark:text-white 
            border-gray-300 dark:border-gray-600 
            focus:border-amber-600 focus:ring focus:ring-amber-500/40 
            focus:outline-none">
        </div>

        <table class="min-w-full divide-y divide-gray-300 dark:divide-gray-700">
            <thead class="bg-stone-100 dark:bg-stone-900">
                <tr>
                    <th class="px-4 py-2 text-left">Nombre</th>
                    <th class="px-4 py-2 text-left">RFC</th>
                    <th class="px-4 py-2 text-left">Correo</th>
                    <th class="px-4 py-2 text-left">Tipo</th>
                    @if (auth()->user()->hasRole('super_admin'))
                        <th class="px-4 py-2 text-left">Despacho</th>
                    @endif
                    <th class="px-4 py-2 text-left">Acciones</th>

                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                @forelse ($clientes as $cliente)
                    <tr>
                        <td class="px-4 py-2">{{ $cliente->nombre }}</td>
                        <td class="px-4 py-2">{{ $cliente->rfc }}</td>
                        <td class="px-4 py-2">{{ $cliente->correo }}</td>
                        <td class="px-4 py-2 capitalize">{{ str_replace('_', ' ', $cliente->tipo_persona) }}</td>
                        @if (auth()->user()->hasRole('super_admin'))
                            <td class="px-4 py-2">{{ $cliente->despacho->nombre ?? '-' }}</td>
                        @endif
                        <td class="px-4 py-2 space-x-2 flex justify-center items-center">


                            <a href="{{ route('clientes.notificaciones.show', $cliente->id) }}"
                                class="bg-amber-500 hover:bg-amber-600 text-white font-bold py-2 px-2 rounded inline-flex items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5" />
                                  </svg>
                                  
                            </a>
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

</div>
