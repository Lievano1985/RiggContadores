{{-- 
Componente Blade: Datos Fiscales
Autor: Luis Liévano - JL3 Digital
Descripción: Muestra regímenes, actividades y obligaciones periódicas y únicas.
--}}

<div class="p-6 bg-white dark:bg-gray-900 text-gray-900 dark:text-white rounded-lg shadow space-y-4"
     x-data
     x-on:mantener-modo-edicion.window="$wire.modoEdicion = true">

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
            <x-seccion-acordeon titulo="Regímenes fiscales" :open="true">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium mb-2">Buscar y seleccionar regímenes</label>
                        <input type="text" placeholder="Escribe para filtrar..."
                               wire:model.live="buscarRegimen"
                               class="w-full px-3 py-2 mb-2 border rounded dark:bg-gray-700 dark:text-white">
                        <div class="border rounded bg-white dark:bg-gray-800 shadow-inner p-2 max-h-60 overflow-y-auto">
                            @foreach ($regimenesDisponibles->filter(fn($r) => str_contains(strtolower($r->nombre), strtolower($buscarRegimen))) as $r)
                                <label class="flex items-center px-3 py-2 hover:bg-gray-100 dark:hover:bg-gray-700 cursor-pointer">
                                    <input type="checkbox" value="{{ $r->id }}"
                                           wire:model.live="regimenesSeleccionados"
                                           class="rounded border-gray-300 dark:bg-gray-700 text-amber-600 focus:ring-amber-500">
                                    <span class="ml-2">{{ $r->clave_sat }} - {{ $r->nombre }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-2">Seleccionados (previsualización)</label>
                        <ul class="list-disc list-inside text-sm space-y-1">
                            @forelse ($regimenesDisponibles->whereIn('id', $regimenesSeleccionados) as $r)
                                <li>{{ $r->clave_sat }} - {{ $r->nombre }}</li>
                            @empty
                                <li class="text-gray-500">Ninguno seleccionado</li>
                            @endforelse
                        </ul>
                    </div>
                </div>
            </x-seccion-acordeon>

            <!-- Actividades -->
            <x-seccion-acordeon titulo="Actividades económicas">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium mb-2">Buscar y seleccionar actividades</label>
                        <input type="text" placeholder="Escribe para filtrar..."
                               wire:model.live="buscarActividad"
                               class="w-full px-3 py-2 mb-2 border rounded dark:bg-gray-700 dark:text-white">
                        <div class="border rounded bg-white dark:bg-gray-800 shadow-inner p-2 max-h-60 overflow-y-auto">
                            @foreach ($actividadesFiltradas as $a)
                                <label class="flex items-center px-3 py-2 hover:bg-gray-100 dark:hover:bg-gray-700 cursor-pointer">
                                    <input type="checkbox" value="{{ $a->id }}" wire:model.live="actividadesSeleccionadas"
                                           class="rounded border-gray-300 dark:bg-gray-700 text-amber-600 focus:ring-amber-500">
                                    <span class="ml-2">{{ $a->nombre }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-2">Seleccionados (previsualización)</label>
                        <ul class="list-disc list-inside text-sm space-y-1">
                            @forelse ($actividadesDisponibles->whereIn('id', $actividadesSeleccionadas) as $a)
                                <li>{{ $a->nombre }}</li>
                            @empty
                                <li class="text-gray-500">Ninguna seleccionada</li>
                            @endforelse
                        </ul>
                    </div>
                </div>
            </x-seccion-acordeon>

            <!-- Obligaciones periódicas -->
            <x-seccion-acordeon titulo="Obligaciones fiscales periódicas">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium mb-2">Buscar y seleccionar obligaciones periódicas</label>
                        <input type="text" placeholder="Escribe para filtrar..."
                               wire:model.live="buscarObligacionPeriodica"
                               class="w-full px-3 py-2 mb-2 border rounded dark:bg-gray-700 dark:text-white">

                        <div class="border rounded bg-white dark:bg-gray-800 shadow-inner p-2 max-h-60 overflow-y-auto">
                            @foreach ($obligacionesPeriodicasFiltradas as $o)
                                @php
                                    $asignacion = \App\Models\ObligacionClienteContador::where('cliente_id', $cliente->id)
                                        ->where('obligacion_id', $o->id)
                                        ->latest()
                                        ->first();
                                @endphp

                                <label class="flex items-center px-3 py-2 hover:bg-gray-100 dark:hover:bg-gray-700 cursor-pointer">
                                    <input type="checkbox" value="{{ $o->id }}" wire:model.live="obligacionesSeleccionadas"
                                           class="rounded border-gray-300 dark:bg-gray-700 text-amber-600 focus:ring-amber-500">
                                    <span class="ml-2">{{ $o->nombre }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <!-- Columna derecha: Previsualización -->
                    <div>
                        <label class="block text-sm font-medium mb-2">Seleccionadas (previsualización)</label>
                        <ul class="list-disc list-inside text-sm space-y-2">
                            @forelse ($obligacionesPeriodicasDisponibles->whereIn('id', $obligacionesSeleccionadas) as $o)
                                @php
                                    $asignacion = \App\Models\ObligacionClienteContador::where('cliente_id', $cliente->id)
                                        ->where('obligacion_id', $o->id)
                                        ->latest()
                                        ->first();
                                @endphp

                                <li class="flex justify-between items-center">
                                    <div class="flex items-center gap-2">
                                        <span>{{ $o->nombre }}</span>
                                        @if($asignacion && !$asignacion->is_activa)
                                            <span class="px-2 py-0.5 text-xs font-semibold bg-red-100 text-red-800 rounded-full">
                                                Baja
                                            </span>
                                        @endif
                                    </div>

                                    <div class="flex items-center gap-2 text-xs">
                                        @if($asignacion && !$asignacion->is_activa)
                                            <button wire:click="reactivarObligacion({{ $o->id }})"
                                                    class="text-green-600 hover:underline">
                                                Reactivar
                                            </button>
                                            @hasrole('admin_despacho')
                                            <button wire:click="eliminarAsignacionTotal({{ $o->id }})"
                                                    class="text-red-600 hover:underline">
                                                Eliminar
                                            </button>
                                        @endhasrole
                                    </div>
                                        @endif

                                      
                                </li>
                            @empty
                                <li class="text-gray-500">Ninguna seleccionada</li>
                            @endforelse
                        </ul>
                    </div>
                </div>
            </x-seccion-acordeon>

            <!-- Obligaciones únicas -->
            <x-seccion-acordeon titulo="Obligaciones únicas (se crean una sola vez)">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium mb-2">Buscar y seleccionar obligaciones únicas</label>
                        <input type="text" placeholder="Escribe para filtrar..."
                               wire:model.live="buscarObligacionUnica"
                               class="w-full px-3 py-2 mb-2 border rounded dark:bg-gray-700 dark:text-white">

                        <div class="border rounded bg-white dark:bg-gray-800 shadow-inner p-2 max-h-60 overflow-y-auto">
                            @foreach ($obligacionesUnicasFiltradas as $o)
                                <label class="flex items-center px-3 py-2 hover:bg-gray-100 dark:hover:bg-gray-700 cursor-pointer">
                                    <input type="checkbox" value="{{ $o->id }}" wire:model.live="obligacionesUnicasSeleccionadas"
                                           class="rounded border-gray-300 dark:bg-gray-700 text-amber-600 focus:ring-amber-500">
                                    <span class="ml-2">{{ $o->nombre }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-2">Seleccionadas (previsualización)</label>
                        <ul class="list-disc list-inside text-sm space-y-1">
                            @forelse ($obligacionesUnicasDisponibles->whereIn('id', $obligacionesUnicasSeleccionadas) as $o)
                                <li>{{ $o->nombre }}</li>
                            @empty
                                <li class="text-gray-500">Ninguna seleccionada</li>
                            @endforelse
                        </ul>
                    </div>
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
