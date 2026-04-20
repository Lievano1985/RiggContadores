<div class="space-y-6">
    <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm p-5 space-y-4">
        <div>
            <h3 class="text-lg font-semibold text-stone-600 dark:text-white">Responsable de futuras solicitudes</h3>
            <p class="text-sm text-gray-600 dark:text-gray-300">
                El responsable definido aqui se usara solo en las nuevas solicitudes del cliente.
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium mb-1">Responsable actual</label>
                <div class="w-full px-3 py-2 border rounded-md bg-gray-50 dark:bg-gray-800 border-gray-300 dark:border-gray-600 text-gray-800 dark:text-gray-100">
                    {{ $cliente->responsableSolicitudes?->name ?? 'Sin responsable asignado' }}
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Nuevo responsable</label>
                <select wire:model="responsable_solicitudes_id"
                    @disabled(!auth()->user()->hasAnyRole(['admin_despacho', 'supervisor']))
                    class="w-full px-3 py-2 border rounded-md dark:bg-gray-700 dark:text-white border-gray-300 dark:border-gray-600 focus:border-amber-600 focus:ring focus:ring-amber-500/40 focus:outline-none disabled:opacity-60">
                    <option value="">Sin asignar</option>
                    @foreach ($usuariosResponsables as $usuario)
                        <option value="{{ $usuario->id }}">
                            {{ $usuario->name }}{{ $usuario->email ? ' - ' . $usuario->email : '' }}
                        </option>
                    @endforeach
                </select>
                @error('responsable_solicitudes_id')
                    <div class="text-red-500 text-xs mt-1">{{ $message }}</div>
                @enderror
            </div>
        </div>

        @if (auth()->user()->hasAnyRole(['admin_despacho', 'supervisor']))
            <div class="text-right">
                <button wire:click="guardarResponsable" class="px-4 py-2 bg-amber-600 text-white rounded hover:bg-amber-700">
                    Guardar responsable
                </button>
            </div>
            <x-spinner target="guardarResponsable" />
        @else
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Solo administrador y supervisor pueden cambiar este responsable.
            </p>
        @endif
    </div>

    <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm p-5">
        <h3 class="text-lg font-semibold text-stone-600 dark:text-white">Historial de solicitudes</h3>
        <div class="mt-4 overflow-x-auto">
            <table class="min-w-full text-sm divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-stone-100 dark:bg-stone-900">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-semibold">Titulo</th>
                        <th class="px-4 py-2 text-left text-xs font-semibold">Origen</th>
                        <th class="px-4 py-2 text-left text-xs font-semibold">Estado</th>
                        <th class="px-4 py-2 text-left text-xs font-semibold">Responsable</th>
                        <th class="px-4 py-2 text-left text-xs font-semibold">Obligacion</th>
                        <th class="px-4 py-2 text-left text-xs font-semibold">Creada por</th>
                        <th class="px-4 py-2 text-left text-xs font-semibold">Fecha</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse ($solicitudes as $solicitud)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/60">
                            <td class="px-4 py-2">
                                <div class="font-medium text-gray-900 dark:text-white">{{ $solicitud->titulo }}</div>
                                @if ($solicitud->descripcion)
                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ \Illuminate\Support\Str::limit($solicitud->descripcion, 90) }}
                                    </div>
                                @endif
                            </td>
                            <td class="px-4 py-2 capitalize">{{ $solicitud->origen }}</td>
                            <td class="px-4 py-2">{{ str_replace('_', ' ', $solicitud->estado) }}</td>
                            <td class="px-4 py-2">{{ $solicitud->responsable?->name ?? '-' }}</td>
                            <td class="px-4 py-2">{{ $solicitud->obligacion?->nombre ?? '-' }}</td>
                            <td class="px-4 py-2">{{ $solicitud->creadoPor?->name ?? '-' }}</td>
                            <td class="px-4 py-2 whitespace-nowrap">{{ $solicitud->created_at?->format('d/m/Y H:i') ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-4 text-center text-gray-500 dark:text-gray-400">
                                No hay solicitudes registradas para este cliente.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
