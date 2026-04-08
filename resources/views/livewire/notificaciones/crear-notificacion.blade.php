<div x-data="{ selectorOpen: false }" class="bg-white dark:bg-gray-900 p-6 rounded-lg shadow">
    @php
        $obligacionesElegidas = collect($obligacionesDisponibles)->whereIn('id', $obligacionesSeleccionadas)->values();
        $archivosElegidos = collect($archivosDisponibles)->filter(
            fn ($archivo) => in_array((string) $archivo['id'], $archivoIdsSeleccionados, true)
        )->values();
        $hayPeriodoSeleccionado = !empty($periodo_ejercicio) && !empty($periodo_mes);
        $hayArchivosRelacionados = count($archivosDisponibles) > 0;
    @endphp

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
        <div>
            <label class="block text-sm mb-1 text-stone-600 dark:text-white">Ejercicio</label>
            <select wire:model.live="periodo_ejercicio"
                class="w-full px-3 py-2 border rounded dark:bg-gray-700 dark:text-white border-gray-300 dark:border-gray-600 focus:border-amber-600 focus:ring focus:ring-amber-500/40 focus:outline-none">
                <option value="">Selecciona...</option>

                @foreach ($ejerciciosDisponibles as $anio)
                    <option value="{{ $anio }}">{{ $anio }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-sm mb-1 text-stone-600 dark:text-white">Mes</label>
            <select wire:model.live="periodo_mes"
                class="w-full px-3 py-2 border rounded dark:bg-gray-700 dark:text-white border-gray-300 dark:border-gray-600 focus:border-amber-600 focus:ring focus:ring-amber-500/40 focus:outline-none">
                <option value="">Selecciona...</option>

                @foreach ($mesesManual as $num => $txt)
                    <option value="{{ $num }}">{{ $txt }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="mb-6">
        <div class="flex flex-wrap items-center gap-3">
            <button type="button"
                @click="selectorOpen = true"
                @disabled(!$hayPeriodoSeleccionado)
                class="px-4 py-2 rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-stone-700 dark:text-white hover:bg-stone-50 dark:hover:bg-gray-700 disabled:opacity-50 disabled:cursor-not-allowed">
                Seleccionar obligaciones y archivos
            </button>

            <span class="text-xs text-gray-500 dark:text-gray-400">
                {{ $hayPeriodoSeleccionado ? 'Selecciona obligaciones y revisa sus archivos relacionados en el popup.' : 'Primero selecciona ejercicio y mes.' }}
            </span>
        </div>

        @error('obligacionesSeleccionadas')
            <p class="text-sm text-red-600 mt-2">{{ $message }}</p>
        @enderror
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-6">
        <section class="border border-gray-200 dark:border-gray-700 rounded-xl bg-stone-50 dark:bg-gray-800/70 p-4">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-sm font-semibold text-stone-700 dark:text-white">
                    Obligaciones
                </h3>
                <span class="text-xs px-2 py-1 rounded-full bg-white dark:bg-gray-700 text-gray-600 dark:text-gray-300">
                    {{ $obligacionesElegidas->count() }} seleccionadas
                </span>
            </div>

            @if ($obligacionesElegidas->isNotEmpty())
                <div class="space-y-2 max-h-48 overflow-y-auto">
                    @foreach ($obligacionesElegidas as $oc)
                        <div class="flex items-center justify-between gap-3 rounded-lg bg-white dark:bg-gray-700 px-3 py-2 text-sm">
                            <span class="text-gray-800 dark:text-white">{{ $oc->obligacion->nombre ?? 'Obligacion' }}</span>
                            <button type="button"
                                wire:click="quitarObligacion({{ $oc->id }})"
                                class="text-red-600 hover:text-red-800 text-xs">
                                Quitar
                            </button>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Aun no has seleccionado obligaciones.
                </p>
            @endif
        </section>

        <section class="border border-gray-200 dark:border-gray-700 rounded-xl bg-stone-50 dark:bg-gray-800/70 p-4">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-sm font-semibold text-stone-700 dark:text-white">
                    Archivos
                </h3>
                <span class="text-xs px-2 py-1 rounded-full bg-white dark:bg-gray-700 text-gray-600 dark:text-gray-300">
                    {{ $archivosElegidos->count() }} seleccionados
                </span>
            </div>

            @if ($archivosElegidos->isNotEmpty())
                <div class="space-y-2 max-h-48 overflow-y-auto">
                    @foreach ($archivosElegidos as $archivo)
                        <div class="flex items-start justify-between gap-3 rounded-lg bg-white dark:bg-gray-700 px-3 py-2 text-sm">
                            <div class="min-w-0">
                                <div class="text-gray-800 dark:text-white break-words">{{ $archivo['nombre'] }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-300">
                                    {{ ucfirst($archivo['origen_tipo']) }}: {{ $archivo['origen_nombre'] }}
                                </div>
                            </div>
                            <button type="button"
                                wire:click="quitarArchivo({{ $archivo['id'] }})"
                                class="text-red-600 hover:text-red-800 text-xs">
                                Quitar
                            </button>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Aun no has dejado archivos seleccionados para enviar.
                </p>
            @endif
        </section>
    </div>

    <div class="mb-4">
        <label class="block text-sm mb-1 text-stone-600 dark:text-white">Asunto</label>
        <input type="text" wire:model.defer="asunto"
            class="w-full px-3 py-2 border rounded dark:bg-gray-700 dark:text-white border-gray-300 dark:border-gray-600 focus:border-amber-600 focus:ring focus:ring-amber-500/40 focus:outline-none">
        @error('asunto')
            <span class="text-red-500 text-sm">{{ $message }}</span>
        @enderror
    </div>

    <div class="mb-4">
        <label class="block text-sm mb-1 text-stone-600 dark:text-white">CC</label>
        <input type="text" wire:model.defer="cc"
            placeholder="correo1@dominio.com, correo2@dominio.com"
            class="w-full px-3 py-2 border rounded dark:bg-gray-700 dark:text-white border-gray-300 dark:border-gray-600 focus:border-amber-600 focus:ring focus:ring-amber-500/40 focus:outline-none">
        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
            Puedes separar varios correos con coma o punto y coma.
        </p>
        @error('cc')
            <span class="text-red-500 text-sm">{{ $message }}</span>
        @enderror
    </div>

    <div class="mb-6">
        <label class="block text-sm mb-1 text-stone-600 dark:text-white">Mensaje</label>
        <textarea rows="5" wire:model.defer="mensaje"
            class="w-full px-3 py-2 border rounded dark:bg-gray-700 dark:text-white border-gray-300 dark:border-gray-600 focus:border-amber-600 focus:ring focus:ring-amber-500/40 focus:outline-none"></textarea>

        @error('mensaje')
            <span class="text-red-500 text-sm">{{ $message }}</span>
        @enderror
    </div>

    <div class="flex justify-end">
        <button wire:click="guardar"
            class="bg-amber-600 hover:bg-amber-700 text-white px-4 py-2 rounded">
            Enviar notificacion
        </button>
    </div>

    <div x-cloak
        x-show="selectorOpen"
        x-transition.opacity
        class="fixed inset-0 z-50 bg-black/50 flex items-center justify-center p-4">
        <div class="absolute inset-0" @click="selectorOpen = false"></div>

        <div class="relative w-full max-w-6xl rounded-2xl bg-white dark:bg-gray-900 shadow-2xl border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="flex items-center justify-between px-5 py-4 border-b border-gray-200 dark:border-gray-700">
                <div>
                    <h3 class="text-lg font-semibold text-stone-700 dark:text-white">
                        Seleccionar obligaciones y archivos
                    </h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        La columna derecha se activa con los archivos relacionados a las obligaciones elegidas.
                    </p>
                </div>

                <button type="button"
                    @click="selectorOpen = false"
                    class="text-gray-500 hover:text-black dark:hover:text-white">
                    Cerrar
                </button>
            </div>

            <div class="grid grid-cols-1 xl:grid-cols-2 divide-y xl:divide-y-0 xl:divide-x divide-gray-200 dark:divide-gray-700">
                <section class="p-5">
                    <div class="flex items-center justify-between mb-3">
                        <h4 class="text-sm font-semibold text-stone-700 dark:text-white">
                            Obligaciones del periodo
                        </h4>
                        <span class="text-xs px-2 py-1 rounded-full bg-stone-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300">
                            {{ count($obligacionesSeleccionadas) }} seleccionadas
                        </span>
                    </div>

                    <input type="text"
                        placeholder="Escribe para filtrar..."
                        wire:model.live="buscarObligacion"
                        class="w-full px-3 py-2 mb-3 border rounded dark:bg-gray-700 dark:text-white border-gray-300 dark:border-gray-600 focus:border-amber-600 focus:ring focus:ring-amber-500/40 focus:outline-none">

                    <div class="border rounded-xl bg-white dark:bg-gray-800 shadow-inner p-2 max-h-[28rem] overflow-y-auto">
                        @forelse ($obligacionesFiltradas as $oc)
                            <label
                                wire:key="obligacion-modal-{{ $oc->id }}"
                                class="flex items-start gap-3 px-3 py-3 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 cursor-pointer">
                                <input type="checkbox"
                                    value="{{ $oc->id }}"
                                    wire:model.live="obligacionesSeleccionadas"
                                    class="mt-1 rounded border-gray-300 dark:bg-gray-700 text-amber-600 focus:ring-amber-500">

                                <div class="min-w-0">
                                    <div class="text-sm text-gray-900 dark:text-white">
                                        {{ $oc->obligacion->nombre ?? 'Obligacion' }}
                                    </div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                        Estatus: {{ $oc->estatus }}
                                    </div>
                                </div>
                            </label>
                        @empty
                            <p class="px-3 py-2 text-sm text-gray-500 dark:text-gray-400">
                                No hay obligaciones disponibles para el periodo seleccionado.
                            </p>
                        @endforelse
                    </div>
                </section>

                <section class="p-5">
                    <div class="flex items-center justify-between mb-3">
                        <h4 class="text-sm font-semibold text-stone-700 dark:text-white">
                            Archivos relacionados
                        </h4>
                        <span class="text-xs px-2 py-1 rounded-full bg-stone-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300">
                            {{ count($archivoIdsSeleccionados) }} seleccionados
                        </span>
                    </div>

                    @if (!count($obligacionesSeleccionadas))
                        <div class="border border-dashed border-gray-300 dark:border-gray-700 rounded-xl p-6 text-sm text-gray-500 dark:text-gray-400 bg-stone-50 dark:bg-gray-800/60">
                            Selecciona obligaciones en la columna izquierda para habilitar los archivos relacionados.
                        </div>
                    @elseif (!count($archivosDisponibles))
                        <div class="border border-dashed border-gray-300 dark:border-gray-700 rounded-xl p-6 text-sm text-gray-500 dark:text-gray-400 bg-stone-50 dark:bg-gray-800/60">
                            Las obligaciones seleccionadas no tienen archivos vinculados ni en la obligación ni en sus tareas relacionadas.
                        </div>
                    @else
                        <div class="border rounded-xl bg-white dark:bg-gray-800 shadow-inner p-2 max-h-[28rem] overflow-y-auto">
                            @foreach ($archivosDisponibles as $archivo)
                                <label
                                    wire:key="archivo-modal-{{ $archivo['id'] }}"
                                    class="flex items-start gap-3 px-3 py-3 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 cursor-pointer">
                                    <input type="checkbox"
                                        value="{{ $archivo['id'] }}"
                                        wire:model.live="archivoIdsSeleccionados"
                                        class="mt-1 rounded border-gray-300 dark:bg-gray-700 text-amber-600 focus:ring-amber-500">

                                    <div class="min-w-0">
                                        <div class="text-sm text-gray-900 dark:text-white break-words">
                                            {{ $archivo['nombre'] }}
                                        </div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ ucfirst($archivo['origen_tipo']) }}: {{ $archivo['origen_nombre'] }}
                                        </div>
                                        <div class="text-xs text-gray-400 dark:text-gray-500">
                                            {{ $archivo['detalle'] }}
                                        </div>
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    @endif
                </section>
            </div>

            <div class="flex items-center justify-between px-5 py-4 border-t border-gray-200 dark:border-gray-700 bg-stone-50 dark:bg-gray-800/60">
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    {{ count($obligacionesSeleccionadas) }} obligaciones y {{ count($archivoIdsSeleccionados) }} archivos listos.
                </p>

                <button type="button"
                    @click="selectorOpen = false"
                    class="px-4 py-2 rounded bg-amber-600 hover:bg-amber-700 text-white">
                    Aplicar seleccion
                </button>
            </div>
        </div>
    </div>
</div>
