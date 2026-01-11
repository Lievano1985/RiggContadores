<div>
    <div class="bg-white dark:bg-gray-900 p-6 rounded-lg shadow space-y-4">
        <h2 class="text-xl font-bold text-stone-600 dark:text-white">
            Regularizar obligaciones atrasadas
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">

            {{-- Año --}}
            <div>
                <label class="block text-stone-600 dark:text-gray-200 mb-1">Año</label>
                <select wire:model.live="anio"
                    class="w-full border rounded px-3 py-2 dark:bg-gray-700 dark:text-white focus:outline-amber-600 focus:outline">
                    @foreach ($this->aniosDisponibles as $a)
                        <option value="{{ $a }}">{{ $a }}</option>
                    @endforeach
                </select>
                @error('anio')
                    <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Mes --}}
            <div>
                <label class="block text-stone-600 dark:text-gray-200 mb-1">Mes</label>
                <select wire:model.live="mes"
                    class="w-full border rounded px-3 py-2 dark:bg-gray-700 dark:text-white focus:outline-amber-600 focus:outline">
                    @forelse($this->mesesDisponibles as $m)
                        <option value="{{ $m }}">
                            {{ ucfirst(\Carbon\Carbon::create()->month($m)->locale('es')->monthName) }}
                        </option>
                    @empty
                        <option disabled>No hay meses disponibles</option>
                    @endforelse
                </select>
                @error('mes')
                    <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Buscador + lista --}}
            {{-- Buscador + listas --}}
            <div class="col-span-1 md:col-span-3">
                <label class="block text-sm font-medium mb-2 text-stone-600 dark:text-gray-200">
                    Buscar y seleccionar obligaciones periódicas
                </label>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                    {{-- COLUMNA IZQUIERDA: BUSCADOR + TODAS --}}
                    <div>
                        <input type="text" placeholder="Escribe para filtrar..." wire:model.live="buscarObligacion"
                            class="w-full px-3 py-2 mb-2 border rounded dark:bg-gray-700 dark:text-white focus:outline-amber-600 focus:outline">

                        <div class="border rounded bg-white dark:bg-gray-800 shadow-inner p-2 max-h-60 overflow-y-auto">
                            @forelse ($this->obligacionesFiltradas as $ob)
                                <label wire:key="obligacion-{{ $ob->id }}"
                                    class="flex items-center px-3 py-2 hover:bg-gray-100 dark:hover:bg-gray-700 cursor-pointer">

                                    <input type="checkbox" value="{{ $ob->id }}"
                                        wire:model.live="obligacionesSeleccionadas"
                                        class="rounded border-gray-300 dark:bg-gray-700 text-amber-600 focus:ring-amber-500">

                                    <span class="ml-2 text-gray-900 dark:text-white">
                                        {{ $ob->nombre }} ({{ $ob->periodicidad }})
                                    </span>
                                </label>
                            @empty
                                <p class="px-3 py-2 text-sm text-gray-500 dark:text-gray-400">
                                    No se encontraron obligaciones periódicas activas.
                                </p>
                            @endforelse
                        </div>
                    </div>

                    {{-- COLUMNA DERECHA: SELECCIONADAS --}}
                    <div>
                        <h3 class="text-sm font-semibold mb-2 text-stone-600 dark:text-gray-200">
                            Seleccionadas ({{ count($obligacionesSeleccionadas) }})
                        </h3>

                        <div class="border rounded bg-stone-50 dark:bg-gray-800 p-2 max-h-60 overflow-y-auto">

                            @forelse ($obligacionesSeleccionadas as $id)
                                @php
                                    $ob =
                                        $this->obligacionesFiltradas->firstWhere('id', $id) ??
                                        \App\Models\Obligacion::find($id);
                                @endphp

                                @if ($ob)
                                    <div
                                        class="flex justify-between items-center px-3 py-2 text-sm
                                    bg-white dark:bg-gray-700 rounded mb-1">

                                        <span class="text-gray-800 dark:text-white">
                                            {{ $ob->nombre }}
                                        </span>

                                        <button
                                        wire:click="quitarObligacion({{ $id }})"
                                        class="text-red-600 hover:text-red-800 text-xs">
                                        Quitar
                                    </button>
                                    
                                    
                                    </div>
                                @endif
                            @empty
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    Aún no has seleccionado obligaciones.
                                </p>
                            @endforelse

                        </div>
                    </div>

                </div>

                @error('obligacionesSeleccionadas')
                    <p class="text-sm text-red-600 mt-2">{{ $message }}</p>
                @enderror
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
