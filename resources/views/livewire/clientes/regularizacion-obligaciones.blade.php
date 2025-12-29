<div>
    <div class="bg-white dark:bg-gray-900 p-6 rounded-lg shadow space-y-4">
        <h2 class="text-xl font-bold text-stone-600">Regularizar obligaciones pasadas</h2>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
           
            <div>
                <label class="block text-stone-600 mb-1">Año</label>
                <input type="number" wire:model.live="anio"
                    class="w-full border rounded px-3 py-2 dark:bg-gray-700 dark:text-white"
                    min="2020" max="{{ now()->year }}">
            </div>
            
            <div>
                <label class="block text-stone-600 mb-1">Mes</label>
                <select wire:model="mes" class="w-full border rounded px-3 py-2 dark:bg-gray-700 dark:text-white">
                    @forelse($this->mesesDisponibles as $m)
                        <option value="{{ $m }}">{{ ucfirst(\Carbon\Carbon::create()->month($m)->locale('es')->monthName) }}</option>
                    @empty
                        <option disabled>Seleccione un año válido</option>
                    @endforelse
                </select>
            </div>
            
            <div class="col-span-1 md:col-span-3">
                <label class="block text-sm font-medium mb-2 text-stone-600">Buscar y seleccionar obligaciones periódicas</label>
            
                <input type="text" placeholder="Escribe para filtrar..."
                    wire:model.live="buscarObligacion"
                    class="w-full px-3 py-2 mb-2 border rounded dark:bg-gray-700 dark:text-white focus:outline-amber-600 focus:outline">
            
                <div class="border rounded bg-white dark:bg-gray-800 shadow-inner p-2 max-h-60 overflow-y-auto">
                    @forelse ($obligacionesFiltradas as $ob)
                        <label class="flex items-center px-3 py-2 hover:bg-gray-100 dark:hover:bg-gray-700 cursor-pointer">
                            <input type="checkbox" value="{{ $ob->id }}"
                                wire:model.live="obligacionesSeleccionadas"
                                class="rounded border-gray-300 dark:bg-gray-700 text-amber-600 focus:ring-amber-500">
                            <span class="ml-2 text-gray-900 dark:text-white">{{ $ob->nombre }} ({{ $ob->periodicidad }})</span>
                        </label>
                    @empty
                        <p class="px-3 py-2 text-sm text-gray-500 dark:text-gray-400">No se encontraron obligaciones activas.</p>
                    @endforelse
                </div>
            </div>
            
        </div>

        <div class="flex justify-end space-x-2">
            <button wire:click="generar" class="bg-amber-600 hover:bg-amber-700 text-white px-4 py-2 rounded">
                Generar obligaciones
            </button>
        </div>

        @if ($resumen)
            <div class="mt-4 text-sm text-gray-800 dark:text-gray-200 space-y-1">
                <p><strong>Generadas:</strong> {{ $resumen['generadas'] ?? 0 }}</p>
                <p><strong>Ya existían:</strong> {{ $resumen['ya_existian'] ?? 0 }}</p>
                <p><strong>Omitidas:</strong> {{ $resumen['omitidas'] ?? 0 }}</p>
            </div>
        @endif
    </div>

</div>
