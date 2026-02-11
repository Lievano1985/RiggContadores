{{-- Vista: Catálogo de Obligaciones y Tareas Autor: Luis Liévano - JL3 Digital --}}
<div x-data="{ openSidebar: @entangle('sidebarVisible') }">

    {{-- ===================================== --}}
    {{-- ENCABEZADO --}}
    {{-- ===================================== --}}
    <div class="flex items-center justify-between mb-4">
        <h2 class="text-xl font-bold text-stone-600 dark:text-white"> Catálogo de Obligaciones y Tareas </h2>
        <button wire:click="abrirCrearObligacion"
            class="px-4 py-2 bg-amber-600 text-white rounded hover:bg-amber-700 transition"> + Nueva obligación </button>
    </div>

    {{-- BUSCADOR --}}
    <div class="mb-4">
        <label class="block text-xs font-semibold text-stone-600 dark:text-white mb-1"> Filtro (obligación o tarea)
        </label>
        <input type="text" wire:model.live.debounce.500ms="search" placeholder="Buscar..."
            class="w-full px-3 py-2 border rounded dark:bg-gray-700 dark:text-white border-gray-300 dark:border-gray-600 focus:border-amber-600 focus:ring focus:ring-amber-500/40 focus:outline-none">
    </div>

    {{-- ===================================== --}}
    {{-- TABLA DE OBLIGACIONES --}}
    {{-- ===================================== --}}
    <div class="bg-white dark:bg-gray-900 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-stone-100 dark:bg-stone-900">
                <tr>
                    <th class="px-4 py-2"></th>
                    <th class="px-4 py-2 text-left text-xs font-semibold">Obligación</th>

                    {{-- ✅ NUEVO: Categoría --}}
                    <th class="px-4 py-2 text-left text-xs font-semibold">Categoría</th>

                    <th class="px-4 py-2 text-left text-xs font-semibold">Periodicidad</th>
                    <th class="px-4 py-2 text-center text-xs font-semibold">Tareas</th>
                    <th class="px-4 py-2 text-center text-xs font-semibold">Activa</th>
                    <th class="px-4 py-2 text-right text-xs font-semibold">Acciones</th>
                </tr>
            </thead>

            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                @forelse ($obligaciones as $obligacion)
                    @php
                        $expandida = $obligacionesExpandidas[$obligacion->id] ?? false;
                        $esUnica = in_array(
                            strtolower($obligacion->periodicidad),
                            ['unica', 'única', 'eventual'],
                            true,
                        );
                    @endphp

                    {{-- FILA PRINCIPAL --}}
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/60">

                        {{-- Expansor --}}
                        <td class="px-2 py-2">
                            <button wire:click="toggleObligacion({{ $obligacion->id }})"
                                class="p-1 hover:border-amber-500 border border-transparent rounded">
                                @if ($obligacion->tareas_catalogo_count > 0)
                                    <span class="inline-block transition-transform">
                                        @if ($expandida)
                                            -
                                        @else
                                            +
                                        @endif
                                @endif
                                </span>
                            </button>
                        </td>

                        {{-- Nombre --}}
                        <td class="px-4 py-2">
                            <div class="text-sm font-medium">{{ $obligacion->nombre }}</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                @if ($esUnica)
                                    Única / eventual
                                @else
                                    Mes inicio: {{ $obligacion->mes_inicio }}, Desfase:
                                    {{ $obligacion->desfase_meses }}, Día corte: {{ $obligacion->dia_corte }}
                                @endif
                            </div>
                        </td>

                        {{-- ✅ NUEVO: Categoría (primera mayúscula / label bonito) --}}
                        <td class="px-4 py-2">
                            @php
                                $mapCategorias = [
                                    'obligacion' => 'Obligación',
                                    'proceso' => 'Proceso',
                                ];
                            @endphp
                            <span class="px-2 py-1 text-xs bg-gray-100 dark:bg-gray-800 rounded">
                                {{ $mapCategorias[$obligacion->categoria] ?? ucfirst($obligacion->categoria ?? '') }}
                            </span>
                        </td>

                        {{-- Periodicidad --}}
                        <td class="px-4 py-2">
                            <span class="px-2 py-1 text-xs bg-amber-100 dark:bg-amber-900/40 rounded">
                                {{ ucfirst($obligacion->periodicidad) }}
                            </span>
                        </td>

                        {{-- Conteo de tareas --}}
                        <td class="px-4 py-2 text-center">
                            <span class="px-2 py-1 text-xs bg-gray-100 dark:bg-gray-800 rounded">
                                {{ $obligacion->tareas_catalogo_count }}
                            </span>
                        </td>

                        {{-- Activa --}}
                        <td class="px-4 py-2 text-center">
                            @if ($obligacion->activa)
                                <span class="px-2 py-1 text-xs bg-green-100 dark:bg-green-900/40 rounded">Activa</span>
                            @else
                                <span class="px-2 py-1 text-xs bg-red-100 dark:bg-red-900/40 rounded">Inactiva</span>
                            @endif
                        </td>

                        {{-- Acciones --}}
                        <td class="px-4 py-2 text-right space-x-2">
                            @hasrole('admin_despacho')
                                <button wire:click="abrirCrearTarea({{ $obligacion->id }})"
                                    class="px-2 py-1 text-xs bg-amber-100 dark:bg-amber-900/40 rounded"> + Tarea </button>
                                <button wire:click="abrirEditarObligacion({{ $obligacion->id }})"
                                    class="px-2 py-1 text-xs border rounded"> Editar </button>
                                <button wire:click="eliminarObligacion({{ $obligacion->id }})"
                                    onclick="return confirm('¿Eliminar esta obligación y sus tareas?')"
                                    class="px-2 py-1 text-xs border border-red-400 text-red-600 rounded"> Eliminar </button>
                            @endhasrole
                        </td>
                    </tr>

                    {{-- ===================================== --}}
                    {{-- SUBFILAS DE TAREAS --}}
                    {{-- ===================================== --}}
                    @if ($expandida)
                        <tr class="bg-gray-50/60 dark:bg-gray-900/40">
                            <td colspan="7" class="px-6 pb-4 pt-1">
                                @if ($obligacion->tareasCatalogo->isEmpty())
                                    <p class="text-sm text-gray-500 dark:text-gray-400 italic mt-2">
                                        Esta obligación aún no tiene tareas registradas.
                                    </p>
                                @else
                                    <div
                                        class="border border-gray-200 dark:border-gray-700 rounded-lg mt-2 overflow-hidden">
                                        <table class="min-w-full text-sm">
                                            <thead class="bg-gray-100 dark:bg-gray-800/80">
                                                <tr>
                                                    <th class="px-3 py-2 text-left text-xs font-semibold">Tarea</th>
                                                    <th class="px-3 py-2 text-left text-xs font-semibold">Descripción
                                                    </th>
                                                    <th class="px-3 py-2 text-right text-xs font-semibold">Acciones</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                                @foreach ($obligacion->tareasCatalogo as $tarea)
                                                    <tr class="hover:bg-white dark:hover:bg-gray-800/70">
                                                        <td class="px-3 py-2">
                                                            <button wire:click="abrirEditarTarea({{ $tarea->id }})"
                                                                class="text-left text-sm font-medium text-stone-700 dark:text-gray-100 hover:underline">
                                                                {{ $tarea->nombre }}
                                                            </button>
                                                        </td>
                                                        <td class="px-3 py-2 text-sm"> {{ $tarea->descripcion ?: '—' }}
                                                        </td>
                                                        <td class="px-3 py-2 text-right">
                                                            <button wire:click="abrirEditarTarea({{ $tarea->id }})"
                                                                class="px-2 py-1 text-xs border rounded"> Editar
                                                            </button>
                                                            <button wire:click="eliminarTarea({{ $tarea->id }})"
                                                                onclick="return confirm('¿Eliminar esta tarea?')"
                                                                class="px-2 py-1 text-xs border border-red-400 text-red-600 rounded ml-1">
                                                                Eliminar
                                                            </button>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @endif

                @empty
                    <tr>
                        <td colspan="7" class="py-6 text-center text-gray-500"> No hay obligaciones registradas
                            todavía. </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-6"> {{ $obligaciones->links() }} </div>

    {{-- ===================================== --}}
    {{-- SIDEBAR (CREAR/EDITAR OBLIGACIÓN O TAREA) --}}
    {{-- ===================================== --}}
    <div x-cloak x-show="openSidebar" x-transition.opacity class="fixed inset-0 z-40 flex justify-end bg-black/40 ">
        {{-- Cerrar al fondo --}}
        <div class="flex-1" @click="$wire.cerrarSidebar()"></div>

        {{-- Panel lateral --}}
        <div x-show="openSidebar" x-transition
            class="w-full max-w-md h-full bg-white dark:bg-gray-900 shadow-xl border-l flex flex-col">

            {{-- Encabezado --}}
            <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                <h3 class="text-sm font-semibold text-stone-700 dark:text-gray-100">
                    @switch($sidebarModo)
                        @case('crear_obligacion')
                            Nueva obligación
                        @break

                        @case('editar_obligacion')
                            Editar obligación
                        @break

                        @case('crear_tarea')
                            Nueva tarea
                        @break

                        @case('editar_tarea')
                            Editar tarea
                        @break

                        @default
                            Detalles
                    @endswitch
                </h3>

                <button @click="$wire.cerrarSidebar()"
                    class="text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-100"> ✕ </button>
            </div>

            {{-- CONTENIDO --}}
            <div class="flex-1 overflow-y-auto px-4 py-4 space-y-4">

                {{-- ----------------------------------- --}}
                {{-- FORM OBLIGACIÓN --}}
                {{-- ----------------------------------- --}}
                @if (in_array($sidebarModo, ['crear_obligacion', 'editar_obligacion']))
                    @php
                        $esUnicaSidebar = in_array(
                            strtolower($formObligacion['periodicidad'] ?? ''),
                            ['unica', 'única', 'eventual'],
                            true,
                        );

                        // ✅ NUEVO: catálogo de categorías (sin tocar lógica del componente)
                        $categorias = [
                            'obligacion' => 'Obligación',
                            'proceso' => 'Proceso',
                        ];
                    @endphp

                    {{-- NOMBRE --}}
                    <div>
                        <label class="block text-sm mb-1">Nombre</label>
                        <input type="text" wire:model.defer="formObligacion.nombre"
                            class="w-full px-3 py-2 border rounded dark:bg-gray-700 dark:text-white">
                    </div>

                    {{-- ✅ NUEVO: CATEGORÍA (ENUM) --}}
                    <div>
                        <label class="block text-sm mb-1">Categoría</label>
                        <select wire:model.defer="formObligacion.categoria"
                            class="w-full px-3 py-2 border rounded dark:bg-gray-700 dark:text-white">
                            <option value="">Seleccione...</option>
                            @foreach ($categorias as $valor => $label)
                                <option value="{{ $valor }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- PERIODICIDAD --}}
                    <div>
                        <label class="block text-sm mb-1">Periodicidad</label>
                        <select wire:model.defer="formObligacion.periodicidad"
                            class="w-full px-3 py-2 border rounded dark:bg-gray-700 dark:text-white">
                            <option value="">Seleccione...</option>
                            @foreach ($periodicidades as $clave => $etiqueta)
                                <option value="{{ $clave }}">{{ $etiqueta }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- CAMPOS SOLO SI NO ES ÚNICA --}}
                    @unless ($esUnicaSidebar)
                        <div class="grid grid-cols-3 gap-3">
                            <div>
                                <label class="block text-sm mb-1">Mes inicio</label>
                                <select wire:model.defer="formObligacion.mes_inicio"
                                    class="w-full px-2 py-2 border rounded dark:bg-gray-700 dark:text-white">
                                    @for ($m = 1; $m <= 12; $m++)
                                        <option value="{{ $m }}">{{ $m }}</option>
                                    @endfor
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm mb-1">Desfase</label>
                                <input type="number" wire:model.defer="formObligacion.desfase_meses"
                                    class="w-full px-2 py-2 border rounded dark:bg-gray-700 dark:text-white">
                            </div>
                            <div>
                                <label class="block text-sm mb-1">Día corte</label>
                                <input type="number" wire:model.defer="formObligacion.dia_corte"
                                    class="w-full px-2 py-2 border rounded dark:bg-gray-700 dark:text-white">
                            </div>
                        </div>
                    @endunless

                    {{-- ACTIVA --}}
                    <div class="flex items-center space-x-2">
                        <input type="checkbox" wire:model.defer="formObligacion.activa">
                        <span class="text-sm">Obligación activa</span>
                    </div>
                @endif

                {{-- ----------------------------------- --}}
                {{-- FORM TAREA --}}
                {{-- ----------------------------------- --}}
                @if (in_array($sidebarModo, ['crear_tarea', 'editar_tarea']))

                    {{-- Obligación padre --}}
                    @if ($obligacionSeleccionadaId)
                        @php
                            $obPadre = $obligaciones->firstWhere('id', $obligacionSeleccionadaId);
                        @endphp
                        @if ($obPadre)
                            <div>
                                <label class="block text-xs text-gray-400 mb-1">Obligación</label>
                                <div class="px-3 py-2 border rounded bg-gray-100 dark:bg-gray-800">
                                    {{ $obPadre->nombre }}
                                </div>
                            </div>
                        @endif
                    @endif

                    {{-- Nombre --}}
                    <div>
                        <label class="block text-sm mb-1">Nombre de la tarea</label>
                        <input type="text" wire:model.defer="formTarea.nombre"
                            class="w-full px-3 py-2 border rounded dark:bg-gray-700 dark:text-white">
                    </div>

                    {{-- Descripción --}}
                    <div>
                        <label class="block text-sm mb-1">Descripción</label>
                        <textarea rows="4" wire:model.defer="formTarea.descripcion"
                            class="w-full px-3 py-2 border rounded dark:bg-gray-700 dark:text-white"></textarea>
                    </div>
                @endif
            </div>

            {{-- FOOTER --}}
            <div class="px-4 py-3 border-t flex justify-end space-x-3">
                <button @click="$wire.cerrarSidebar()" class="px-3 py-2 bg-gray-200 rounded"> Cancelar </button>

                {{-- Guardar --}}
                @if (in_array($sidebarModo, ['crear_obligacion', 'editar_obligacion']))
                    <button wire:click="guardarObligacion" class="px-4 py-2 bg-amber-600 text-white rounded"> Guardar
                    </button>
                @endif

                @if (in_array($sidebarModo, ['crear_tarea', 'editar_tarea']))
                    <button wire:click="guardarTarea" class="px-4 py-2 bg-amber-600 text-white rounded"> Guardar
                    </button>
                @endif
            </div>
        </div>
    </div>
</div>
