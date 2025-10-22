{{-- 
Componente Blade: Datos Fiscales
Autor: Luis Liévano - JL3 Digital
Descripción: Muestra regímenes, actividades, y obligaciones periódicas y únicas.
--}}

<div class="p-6 bg-white dark:bg-gray-900 text-gray-900 dark:text-white rounded-lg shadow space-y-4">
    
    <!-- Toggle edición -->

    <div class="mb-4">
        <div wire:key="modo-{{ $modoKey }}">

        <label class="inline-flex items-center cursor-pointer">
            <input type="checkbox" wire:model.live="modoEdicion" class="sr-only peer">
            <div
                class="relative w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-amber-300 dark:peer-focus:ring-amber-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-amber-600 dark:peer-checked:bg-amber-600">
            </div>
            <span class="ms-3 text-sm font-medium text-gray-900 dark:text-gray-300">Modo edición</span>
        </label>
        </div>
    </div>



    @if ($modoEdicion)
        <form wire:submit.prevent="guardar" class="space-y-6">

            <!-- Regímenes -->
            <x-seccion-acordeon titulo="Regímenes fiscales">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                    @foreach ($regimenesDisponibles as $regimen)
                        <label class="flex items-center space-x-2">
                            <input type="checkbox" value="{{ $regimen->id }}" wire:model="regimenesSeleccionados"
                                class="rounded border-gray-300 dark:bg-gray-700 text-amber-600 focus:ring-amber-500">
                            <span>{{ $regimen->nombre }}</span>
                        </label>
                    @endforeach
                </div>
            </x-seccion-acordeon>

            <!-- Actividades -->
            <x-seccion-acordeon titulo="Actividades económicas">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                    @foreach ($actividadesDisponibles as $actividad)
                        <label class="flex items-center space-x-2">
                            <input type="checkbox" value="{{ $actividad->id }}" wire:model="actividadesSeleccionadas"
                                class="rounded border-gray-300 dark:bg-gray-700 text-amber-600 focus:ring-amber-500">
                            <span>{{ $actividad->nombre }}</span>
                        </label>
                    @endforeach
                </div>
            </x-seccion-acordeon>

            <!-- Obligaciones periódicas -->
            <x-seccion-acordeon titulo="Obligaciones fiscales periódicas">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                    @foreach ($obligacionesPeriodicasDisponibles as $obligacion)
                        <label class="flex items-center space-x-2">
                            <input type="checkbox" value="{{ $obligacion->id }}" wire:model="obligacionesSeleccionadas"
                                class="rounded border-gray-300 dark:bg-gray-700 text-amber-600 focus:ring-amber-500">
                            <span>{{ $obligacion->nombre }}</span>
                        </label>
                    @endforeach
                </div>
            </x-seccion-acordeon>

            <!-- Obligaciones únicas -->
            <x-seccion-acordeon titulo="Obligaciones únicas (se crean una sola vez)">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                    @foreach ($obligacionesUnicasDisponibles as $obligacion)
                        <label class="flex items-center space-x-2">
                            <input type="checkbox" value="{{ $obligacion->id }}" wire:model="obligacionesUnicasSeleccionadas"
                                class="rounded border-gray-300 dark:bg-gray-700 text-amber-600 focus:ring-amber-500">
                            <span>{{ $obligacion->nombre }}</span>
                        </label>
                    @endforeach
                </div>
            </x-seccion-acordeon>

            <div class="flex justify-end space-x-2">
                <button type="submit" class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded">
                    Guardar cambios
                </button>
            </div>
        </form>
    @else

        <x-lista-resumen titulo="Regímenes fiscales" :items="$cliente->regimenes->pluck('nombre')" />
        <x-lista-resumen titulo="Actividades económicas" :items="$cliente->actividadesEconomicas->pluck('nombre')" />
        <x-lista-resumen titulo="Obligaciones fiscales" :items="$cliente->obligaciones->pluck('nombre')" />
    @endif
</div>
