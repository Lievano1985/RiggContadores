<div class="bg-white dark:bg-gray-900 p-6 rounded-lg shadow">


    {{-- ========================= --}}
    {{-- Periodo --}}
    {{-- ========================= --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">

        {{-- Ejercicio --}}
        <div>
            <label class="block text-sm mb-1">Ejercicio</label>
            <select wire:model.live="periodo_ejercicio"
                class="w-full px-3 py-2 border rounded
                       dark:bg-gray-700 dark:text-white
                       focus:outline-amber-600">
    
                <option value="">Selecciona...</option>
    
                @foreach ($ejerciciosDisponibles as $anio)
                    <option value="{{ $anio }}">{{ $anio }}</option>
                @endforeach
            </select>
        </div>
    
        {{-- Mes --}}
        <div>
            <label class="block text-sm mb-1">Mes</label>
            <select wire:model.live="periodo_mes"
                class="w-full px-3 py-2 border rounded
                       dark:bg-gray-700 dark:text-white
                       focus:outline-amber-600">
    
                <option value="">Selecciona...</option>
    
                @foreach ($mesesManual as $num => $txt)
                    <option value="{{ $num }}">{{ $txt }}</option>
                @endforeach
            </select>
        </div>
    
    </div>
    
    {{-- ========================= --}}
    {{-- Obligaciones --}}
    {{-- ========================= --}}
    <div class="mb-6 text-sm">

        <label class="block text-sm font-medium mb-2 text-stone-600 dark:text-gray-200">
            Buscar y seleccionar obligaciones del periodo
        </label>
    
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    
            {{-- COLUMNA IZQUIERDA --}}
            <div>
    
                <input type="text"
                    placeholder="Escribe para filtrar..."
                    wire:model.live="buscarObligacion"
                    class="w-full px-3 py-2 mb-2 border rounded
                           dark:bg-gray-700 dark:text-white
                           focus:outline-amber-600">
    
                <div
                    class="border rounded bg-white dark:bg-gray-800
                           shadow-inner p-2 max-h-60 overflow-y-auto">
    
                    @forelse ($obligacionesFiltradas as $oc)
                        <label
                            wire:key="obligacion-{{ $oc->id }}"
                            class="flex text-sm items-center px-3 py-2
                                   hover:bg-gray-100 dark:hover:bg-gray-700
                                   cursor-pointer">
    
                            <input type="checkbox"
                                value="{{ $oc->id }}"
                                wire:model.live="obligacionesSeleccionadas"
                                class="rounded border-gray-300
                                       dark:bg-gray-700
                                       text-amber-600
                                       focus:ring-amber-500">
    
                            <span class="ml-2 text-gray-900 dark:text-white">
                                {{ $oc->obligacion->nombre ?? 'Obligación' }}
                            </span>
    
                        </label>
                    @empty
                        <p class="px-3 py-2 text-sm text-gray-500 dark:text-gray-400">
                            No hay obligaciones disponibles.
                        </p>
                    @endforelse
    
                </div>
            </div>
    
            {{-- COLUMNA DERECHA --}}
            <div>
    
                <h3 class="text-sm font-semibold mb-2 text-stone-600 dark:text-gray-200">
                    Seleccionadas ({{ count($obligacionesSeleccionadas) }})
                </h3>
    
                <div
                    class="border rounded bg-stone-50 dark:bg-gray-800
                           p-2 max-h-60 overflow-y-auto">
    
                    @forelse ($obligacionesSeleccionadas as $id)
    
                        @php
                            $oc = $obligacionesDisponibles->firstWhere('id', $id);
                        @endphp
    
                        @if ($oc)
                            <div
                                class="flex justify-between items-center px-3 py-2 text-sm
                                       bg-white dark:bg-gray-700 rounded mb-1">
    
                                <span class="text-gray-800 dark:text-white">
                                    {{ $oc->obligacion->nombre }}
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
    

    {{-- ========================= --}}
    {{-- Archivos detectados --}}
    {{-- ========================= --}}
    @if (count($archivosSeleccionados))

        <div class="mb-6">
            <h3 class="text-md font-semibold text-stone-600 mb-2">
                Archivos que se enviarán
            </h3>

            <ul class="list-disc ml-5 text-sm">
                @foreach ($archivosSeleccionados as $archivo)
                    <li>
                        {{ $archivo->nombre ?? basename($archivo->ruta) }}
                    </li>
                @endforeach
            </ul>
        </div>

    @endif

    {{-- ========================= --}}
    {{-- Asunto --}}
    {{-- ========================= --}}
    <div class="mb-4">
        <label class="block text-sm mb-1">Asunto</label>
        <input type="text" wire:model.defer="asunto"
            class="w-full px-3 py-2 border rounded
                   dark:bg-gray-700 dark:text-white
                   focus:outline-amber-600">
        @error('asunto')
            <span class="text-red-500 text-sm">{{ $message }}</span>
        @enderror
    </div>

    {{-- ========================= --}}
    {{-- Mensaje --}}
    {{-- ========================= --}}
    <div class="mb-6">
        <label class="block text-sm mb-1">Mensaje</label>
        <textarea rows="5" wire:model.defer="mensaje"
            class="w-full px-3 py-2 border rounded
                   dark:bg-gray-700 dark:text-white
                   focus:outline-amber-600"></textarea>

        @error('mensaje')
            <span class="text-red-500 text-sm">{{ $message }}</span>
        @enderror
    </div>

    {{-- ========================= --}}
    {{-- Botón --}}
    {{-- ========================= --}}
    <div class="flex justify-end">
        <button wire:click="guardar"
            class="bg-stone-600 hover:bg-stone-700
                   text-white px-4 py-2 rounded">
            Enviar notificación
        </button>
    </div>
    {{-- ========================= --}}
    {{-- Mensaje éxito --}}
    {{-- ========================= --}}

    <x-notification />
</div>
