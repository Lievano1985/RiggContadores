<div x-data="{ clienteDetalle: null, contadorDetalle: null, operacionModal: null, seguimientoModal: null, validacionesModal: null, enviosModal: null }" class="space-y-6">


    <section class="rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-700 dark:bg-gray-900">
        <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
            <div>
                <h2 class="text-2xl font-bold text-stone-600 dark:text-white">
                    Dashboard de {{ $dashboard['header']['role_label'] }}
                </h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    {{ $dashboard['header']['user_name'] }} | {{ $dashboard['header']['despacho'] }} |
                    {{ $dashboard['header']['fecha'] }}
                </p>
            </div>
        </div>
    </section>

        {{-- ############# operacion del despacho ############ --}}
    <section class="rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-700 dark:bg-gray-900">
        <div>
            <div>
                <h3 class="text-xl font-semibold text-stone-600 dark:text-white">Operacion del Despacho</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Clientes, Contratos y Asiganaciones</p>
            </div>
        </div>
        <section class="mt-4 grid grid-cols-1 gap-6 xl:grid-cols-[0.8fr_1.8fr]">
            <div class="rounded-xl border border-amber-200 bg-gradient-to-br from-amber-50 to-white p-5 dark:border-amber-900/50 dark:from-amber-950/40 dark:to-gray-900">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-amber-700 dark:text-amber-300">
                    Cobertura de Clientes
                </p>

                <div class="mt-4 flex flex-col items-center justify-center">
                    <div class="relative flex h-52 w-52 items-center justify-center">
                        <svg class="h-52 w-52 -rotate-90" viewBox="0 0 120 120">
                            <circle cx="60" cy="60" r="42" fill="none" stroke="currentColor" stroke-width="12" class="text-amber-100 dark:text-gray-700" />
                            <circle
                                cx="60"
                                cy="60"
                                r="42"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="12"
                                stroke-linecap="round"
                                class="text-amber-600"
                                stroke-dasharray="{{ 2 * pi() * 42 }}"
                                stroke-dashoffset="{{ (2 * pi() * 42) - ((2 * pi() * 42) * ($dashboard['header']['porcentaje_cobertura'] / 100)) }}"
                            />
                        </svg>
                        <div class="absolute text-center">
                            <p class="text-4xl font-bold leading-none text-amber-600 dark:text-amber-400">
                                {{ $dashboard['header']['porcentaje_cobertura'] }}%
                            </p>
                            <p class="mt-2 text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Cobertura</p>
                        </div>
                    </div>

                    <p class="mt-4 text-center text-sm text-gray-600 dark:text-gray-300">
                        {{ $dashboard['resumen_despacho']['clientes_completos'] }} de
                        {{ $dashboard['resumen_despacho']['clientes_evaluados'] }} clientes completos
                    </p>
                </div>
            </div>

            <div class="space-y-4">
            <h3 class="text-base font-semibold text-stone-600 dark:text-white">Indicadores</h3>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-5">
                <button
                    type="button"
                    @click="operacionModal = 'clientes_activos'"
                    class="cursor-pointer rounded-xl border border-stone-200 bg-gradient-to-br from-stone-50 to-white p-4 text-left transition hover:border-amber-600 dark:border-stone-700 dark:from-stone-900 dark:to-gray-800">
                    <p class="text-xs uppercase tracking-wide text-gray-500">Clientes activos</p>
                    <p class="mt-2 text-2xl font-semibold text-stone-600 dark:text-white">
                        {{ $dashboard['kpis']['clientes_activos'] }}</p>
                </button>
                <button
                    type="button"
                    @click="operacionModal = 'clientes_inactivos'"
                    class="cursor-pointer rounded-xl border border-gray-200 bg-gradient-to-br from-gray-50 to-white p-4 text-left transition hover:border-amber-600 dark:border-gray-700 dark:from-gray-900 dark:to-gray-800">
                    <p class="text-xs uppercase tracking-wide text-gray-500">Clientes inactivos</p>
                    <p class="mt-2 text-2xl font-semibold text-gray-600 dark:text-white">
                        {{ $dashboard['kpis']['clientes_inactivos'] }}</p>
                </button>
                <button
                    type="button"
                    @click="operacionModal = 'contratos_vigentes'"
                    class="cursor-pointer rounded-xl border border-emerald-200 bg-gradient-to-br from-emerald-50 to-white p-4 text-left transition hover:border-amber-600 dark:border-emerald-900/50 dark:from-emerald-950/40 dark:to-gray-800">
                    <p class="text-xs uppercase tracking-wide text-gray-500">Contratos vigentes</p>
                    <p class="mt-2 text-2xl font-semibold text-emerald-600">
                        {{ $dashboard['kpis']['contratos_vigentes'] }}
                    </p>
                </button>
                <button
                    type="button"
                    @click="operacionModal = 'contratos'"
                    class="cursor-pointer rounded-xl border border-amber-200 bg-gradient-to-br from-amber-50 to-white p-4 transition hover:border-amber-600 dark:border-amber-900/50 dark:from-amber-950/40 dark:to-gray-800">
                    <p class="text-xs uppercase tracking-wide text-gray-500">Por vencer</p>
                    <p class="mt-2 text-2xl font-semibold text-amber-600">
                        {{ $dashboard['kpis']['contratos_por_vencer'] }}
                    </p>
                </button>
                <button
                    type="button"
                    @click="operacionModal = 'contratos_vencidos'"
                    class="cursor-pointer rounded-xl border border-red-200 bg-gradient-to-br from-red-50 to-white p-4 text-left transition hover:border-amber-600 dark:border-red-900/50 dark:from-red-950/40 dark:to-gray-800">
                    <p class="text-xs uppercase tracking-wide text-gray-500">Contratos vencidos</p>
                    <p class="mt-2 text-2xl font-semibold text-red-600">{{ $dashboard['kpis']['contratos_vencidos'] }}
                    </p>
                </button>
                <button
                    type="button"
                    @click="operacionModal = 'sin_contrato'"
                    class="cursor-pointer rounded-xl border border-rose-200 bg-gradient-to-br from-rose-50 to-white p-4 text-left transition hover:border-amber-600 dark:border-rose-900/50 dark:from-rose-950/40 dark:to-gray-800">
                    <p class="text-xs uppercase tracking-wide text-gray-500">Sin contrato</p>
                    <p class="mt-2 text-2xl font-semibold text-rose-600">{{ $dashboard['kpis']['sin_contrato'] }}
                    </p>
                </button>
                <button
                    type="button"
                    @click="operacionModal = 'clientes_completos'"
                    class="cursor-pointer rounded-xl border border-teal-200 bg-gradient-to-br from-teal-50 to-white p-4 text-left transition hover:border-amber-600 dark:border-teal-900/50 dark:from-teal-950/40 dark:to-gray-800">
                    <p class="text-xs uppercase tracking-wide text-gray-500">Clientes completos</p>
                    <p class="mt-2 text-2xl font-semibold text-teal-600">{{ $dashboard['kpis']['clientes_completos'] }}
                    </p>
                </button>
                <button
                    type="button"
                    @click="operacionModal = 'incompletos'"
                    class="cursor-pointer rounded-xl border border-rose-200 bg-gradient-to-br from-rose-50 to-white p-4 transition hover:border-amber-600 dark:border-rose-900/50 dark:from-rose-950/40 dark:to-gray-800">
                    <p class="text-xs uppercase tracking-wide text-gray-500">Clientes con faltantes</p>
                    <p class="mt-2 text-2xl font-semibold text-rose-600">
                        {{ $dashboard['kpis']['clientes_incompletos'] }}
                    </p>
                </button>
                <button
                    type="button"
                    @click="operacionModal = 'obligaciones_incompletas'"
                    class="cursor-pointer rounded-xl border border-red-200 bg-gradient-to-br from-red-50 to-white p-4 text-left transition hover:border-amber-600 dark:border-red-900/50 dark:from-red-950/40 dark:to-gray-800">
                    <p class="text-xs uppercase tracking-wide text-gray-500">Obligaciones incompletas</p>
                    <p class="mt-2 text-2xl font-semibold text-red-600">
                        {{ $dashboard['kpis']['obligaciones_incompletas'] }}</p>
                </button>
                <button
                    type="button"
                    @click="operacionModal = 'tareas_incompletas'"
                    class="cursor-pointer rounded-xl border border-fuchsia-200 bg-gradient-to-br from-fuchsia-50 to-white p-4 text-left transition hover:border-amber-600 dark:border-fuchsia-900/50 dark:from-fuchsia-950/40 dark:to-gray-800">
                    <p class="text-xs uppercase tracking-wide text-gray-500">Tareas incompletas</p>
                    <p class="mt-2 text-2xl font-semibold text-fuchsia-600">
                        {{ $dashboard['kpis']['tareas_incompletas'] }}
                    </p>
                </button>
            </div>
            </div>
        </section>

    </section>

    {{-- ############# seguimietno por contador############ --}}
    <section class="rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-700 dark:bg-gray-900">
        <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
            <div>
                <h3 class="text-xl font-semibold text-stone-600 dark:text-white">Seguimiento de Actividades</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Vista global o individual de las obligaciones.
                    vencimiento real.</p>
            </div>

            <div
                class="w-full rounded-xl border border-indigo-200 bg-gradient-to-r from-indigo-50 to-white p-4 dark:border-indigo-900/50 dark:from-indigo-950/40 dark:to-gray-900 md:max-w-sm">
                <div class="flex items-end justify-between gap-4">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-indigo-700 dark:text-indigo-300">
                            Cumplimiento Global
                        </p>
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">
                            {{ $dashboard['seguimiento_contadores']['resumen_global']['obligaciones_cerradas'] }} de
                            {{ $dashboard['seguimiento_contadores']['resumen_global']['obligaciones_periodo'] }} cerradas
                        </p>
                    </div>
                    <p class="text-4xl font-bold leading-none text-indigo-600 dark:text-indigo-400">
                        {{ $dashboard['seguimiento_contadores']['resumen_global']['cumplimiento'] }}%
                    </p>
                </div>

                <div class="mt-3 h-3 w-full overflow-hidden rounded-full bg-indigo-100 dark:bg-gray-800">
                    <div class="h-full rounded-full bg-indigo-600 transition-all duration-500"
                        style="width: {{ $dashboard['seguimiento_contadores']['resumen_global']['cumplimiento'] }}%;"></div>
                </div>
            </div>
        </div>

        <section class="space-y-4 mt-4">
        <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
            <select wire:model.live="filtroEjercicio"
                class="px-3 py-2 border rounded dark:bg-gray-700 dark:text-white border-gray-300 dark:border-gray-600 focus:border-amber-600 focus:ring focus:ring-amber-500/40 focus:outline-none">
                @for ($year = now()->year + 1; $year >= now()->year - 3; $year--)
                    <option value="{{ $year }}">{{ $year }}</option>
                @endfor
            </select>

            <select wire:model.live="filtroMes"
                class="px-3 py-2 border rounded dark:bg-gray-700 dark:text-white border-gray-300 dark:border-gray-600 focus:border-amber-600 focus:ring focus:ring-amber-500/40 focus:outline-none">
                @foreach ([
                    '01' => 'Enero',
                    '02' => 'Febrero',
                    '03' => 'Marzo',
                    '04' => 'Abril',
                    '05' => 'Mayo',
                    '06' => 'Junio',
                    '07' => 'Julio',
                    '08' => 'Agosto',
                    '09' => 'Septiembre',
                    '10' => 'Octubre',
                    '11' => 'Noviembre',
                    '12' => 'Diciembre',
                ] as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>

            <select wire:model.live="filtroContador"
                class="px-3 py-2 border rounded dark:bg-gray-700 dark:text-white border-gray-300 dark:border-gray-600 focus:border-amber-600 focus:ring focus:ring-amber-500/40 focus:outline-none">
                <option value="">Todos los contadores</option>
                @foreach ($dashboard['filtros']['contadores'] as $contador)
                    <option value="{{ $contador['id'] }}">{{ $contador['nombre'] }}</option>
                @endforeach
            </select>
        </div>

        <div class="flex items-center gap-3">
            <h3 class="text-base font-semibold text-stone-600 dark:text-white">Indicadores</h3>
            <span class="rounded-full border border-gray-200 bg-gray-50 px-3 py-1 text-xs font-medium text-gray-600 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">
                {{ $dashboard['seguimiento_contadores']['contador_seleccionado']['nombre'] ?? 'Global' }}
            </span>
        </div>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
            <div
                class="rounded-xl border border-sky-200 bg-gradient-to-br from-sky-50 to-white p-4 dark:border-sky-900/50 dark:from-sky-950/40 dark:to-gray-800">
                <p class="text-xs uppercase tracking-wide text-gray-500">1. Asignadas del mes</p>
                <p class="mt-2 text-2xl font-semibold text-sky-600">
                    {{ $dashboard['seguimiento_contadores']['kpis']['obligaciones_periodo'] }}</p>
            </div>
            <button
                type="button"
                @click="seguimientoModal = 'atrasadas'"
                class="cursor-pointer rounded-xl border border-red-200 bg-gradient-to-br from-red-50 to-white p-4 text-left transition hover:border-amber-600 dark:border-red-900/50 dark:from-red-950/40 dark:to-gray-800">
                <p class="text-xs uppercase tracking-wide text-gray-500">2. Atrasadas</p>
                <p class="mt-2 text-2xl font-semibold text-red-600">
                    {{ $dashboard['seguimiento_contadores']['kpis']['obligaciones_atrasadas'] }}</p>
            </button>
            <div
                class="rounded-xl border border-emerald-200 bg-gradient-to-br from-emerald-50 to-white p-4 dark:border-emerald-900/50 dark:from-emerald-950/40 dark:to-gray-800">
                <p class="text-xs uppercase tracking-wide text-gray-500">3. Terminadas en el mes</p>
                <p class="mt-2 text-2xl font-semibold text-emerald-600">
                    {{ $dashboard['seguimiento_contadores']['kpis']['obligaciones_cerradas'] }}</p>
            </div>
            <button
                type="button"
                @click="seguimientoModal = 'faltantes'"
                class="cursor-pointer rounded-xl border border-amber-200 bg-gradient-to-br from-amber-50 to-white p-4 text-left transition hover:border-amber-600 dark:border-amber-900/50 dark:from-amber-950/40 dark:to-gray-800">
                <p class="text-xs uppercase tracking-wide text-gray-500">4. Faltantes en el mes</p>
                <p class="mt-2 text-2xl font-semibold text-amber-600">
                    {{ $dashboard['seguimiento_contadores']['kpis']['obligaciones_faltantes'] }}</p>
            </button>
            <button
                type="button"
                @click="seguimientoModal = 'urgentes'"
                class="cursor-pointer rounded-xl border border-orange-200 bg-gradient-to-br from-orange-50 to-white p-4 text-left transition hover:border-amber-600 dark:border-orange-900/50 dark:from-orange-950/40 dark:to-gray-800">
                <p class="text-xs uppercase tracking-wide text-gray-500">Obligaciones urgentes</p>
                <p class="mt-2 text-2xl font-semibold text-orange-600">
                    {{ $dashboard['seguimiento_contadores']['kpis']['obligaciones_urgentes'] }}</p>
            </button>
            <div
                class="rounded-xl border border-indigo-200 bg-gradient-to-br from-indigo-50 to-white p-4 dark:border-indigo-900/50 dark:from-indigo-950/40 dark:to-gray-800">
                <p class="text-xs uppercase tracking-wide text-gray-500">Cumplimiento del periodo</p>
                <p class="mt-2 text-2xl font-semibold text-indigo-600">
                    {{ $dashboard['seguimiento_contadores']['kpis']['cumplimiento'] }}%</p>
            </div>
        </div>
    </section>

        <section class="grid grid-cols-1 gap-6 xl:grid-cols-[1.6fr_1fr] mt-4">
        <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
            <h3 class="mb-3 text-base font-semibold text-stone-600 dark:text-white">Carga por contador</h3>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="text-left text-gray-500 dark:text-gray-400">
                            <th class="pb-2 pr-3">Contador</th>
                            <th class="pb-2 pr-3">Periodo</th>
                            <th class="pb-2 pr-3">Atrasadas</th>
                            <th class="pb-2 pr-3">Cerradas</th>
                            <th class="pb-2 pr-3">Faltantes</th>
                            <th class="pb-2">%</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($dashboard['seguimiento_contadores']['carga_por_contador'] as $fila)
                            <tr
                                @click='contadorDetalle = @json($fila)'
                                class="cursor-pointer border-t border-gray-200 transition hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-700/40"
                            >
                                <td class="py-2 pr-3 text-stone-600 dark:text-white">{{ $fila['nombre'] }}</td>
                                <td class="py-2 pr-3">{{ $fila['periodo'] }}</td>
                                <td class="py-2 pr-3 text-red-600">{{ $fila['atrasadas'] }}</td>
                                <td class="py-2 pr-3 text-emerald-600">{{ $fila['cerradas'] }}</td>
                                <td class="py-2 pr-3 text-amber-600">{{ $fila['faltantes'] }}</td>
                                <td class="py-2 font-medium text-indigo-600">{{ $fila['cumplimiento'] }}%</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-3 text-gray-500 dark:text-gray-400">No hay contadores con
                                    carga para este periodo.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="space-y-6">
            <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
                <h3 class="mb-3 text-base font-semibold text-stone-600 dark:text-white">Grafica de carga por responsable</h3>
                <div
                    x-data="seguimientoLineChart({
                        categories: @js(collect($dashboard['seguimiento_contadores']['carga_por_contador'])->pluck('nombre')->values()),
                        cerradas: @js(collect($dashboard['seguimiento_contadores']['carga_por_contador'])->pluck('cerradas')->values()),
                        faltantes: @js(collect($dashboard['seguimiento_contadores']['carga_por_contador'])->pluck('faltantes')->values()),
                        atrasadas: @js(collect($dashboard['seguimiento_contadores']['carga_por_contador'])->pluck('atrasadas')->values()),
                    })"
                    x-init="init()"
                    class="min-h-[320px]"
                >
                    <div x-ref="chart" class="h-80 w-full"></div>
                    <p x-show="!categories.length" class="text-sm text-gray-500 dark:text-gray-400">
                        Sin datos para graficar en este periodo.
                    </p>
                </div>
            </div>
        </div>
        </section>
    </section>

    <div
        x-show="seguimientoModal"
        x-cloak
        x-transition.opacity
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
    >
        <div
            @click.outside="seguimientoModal = null"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="w-full max-w-4xl rounded-2xl border border-gray-200 bg-white p-5 shadow-2xl dark:border-gray-700 dark:bg-gray-900"
        >
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h3 class="text-xl font-semibold text-stone-600 dark:text-white" x-text="{ atrasadas: 'Obligaciones atrasadas', faltantes: 'Faltantes del mes', urgentes: 'Obligaciones urgentes' }[seguimientoModal]"></h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400" x-text="{ atrasadas: 'Detalle de obligaciones vencidas de meses anteriores que siguen abiertas.', faltantes: 'Detalle de obligaciones del mes que siguen pendientes.', urgentes: 'Detalle de obligaciones próximas o vencidas dentro del periodo.' }[seguimientoModal]"></p>
                </div>

                <button
                    type="button"
                    @click="seguimientoModal = null"
                    class="rounded-lg border border-gray-200 px-3 py-2 text-sm text-gray-600 transition hover:border-amber-600 dark:border-gray-700 dark:text-gray-300"
                >
                    Cerrar
                </button>
            </div>

            <div x-show="seguimientoModal === 'atrasadas'" class="mt-5 max-h-[32rem] space-y-2 overflow-y-auto pr-1">
                @forelse($dashboard['seguimiento_contadores']['detalle_atrasadas'] as $item)
                    <div class="rounded-lg border border-red-200 bg-red-50 p-3 text-sm dark:border-red-900/50 dark:bg-red-900/20">
                        <p class="font-medium text-stone-700 dark:text-white">{{ $item['cliente'] }}</p>
                        <p class="mt-1 text-red-700 dark:text-red-300">{{ $item['obligacion'] }}</p>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            {{ $item['contador'] }} | {{ $item['fecha_vencimiento'] }} | {{ $item['estatus'] }}
                        </p>
                    </div>
                @empty
                    <p class="text-sm text-gray-500 dark:text-gray-400">Sin obligaciones atrasadas.</p>
                @endforelse
            </div>

            <div x-show="seguimientoModal === 'faltantes'" class="mt-5 max-h-[32rem] space-y-2 overflow-y-auto pr-1">
                @forelse($dashboard['seguimiento_contadores']['detalle_faltantes'] as $item)
                    <div class="rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm dark:border-amber-900/50 dark:bg-amber-900/20">
                        <p class="font-medium text-stone-700 dark:text-white">{{ $item['cliente'] }}</p>
                        <p class="mt-1 text-amber-700 dark:text-amber-300">{{ $item['obligacion'] }}</p>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            {{ $item['contador'] }} | {{ $item['fecha_vencimiento'] }} | {{ $item['estatus'] }}
                        </p>
                    </div>
                @empty
                    <p class="text-sm text-gray-500 dark:text-gray-400">Sin faltantes en el mes.</p>
                @endforelse
            </div>

            <div x-show="seguimientoModal === 'urgentes'" class="mt-5 max-h-[32rem] space-y-2 overflow-y-auto pr-1">
                @forelse($dashboard['seguimiento_contadores']['obligaciones_urgentes'] as $item)
                    <div @class([
                        'rounded-lg border p-3 text-sm',
                        'border-red-200 bg-red-50 dark:border-red-900/50 dark:bg-red-900/20' => $item['vencida'],
                        'border-amber-200 bg-amber-50 dark:border-amber-900/50 dark:bg-amber-900/20' => ! $item['vencida'],
                    ])>
                        <p class="font-medium text-stone-700 dark:text-white">{{ $item['cliente'] }}</p>
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">{{ $item['obligacion'] }}</p>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            {{ $item['contador'] }} | {{ $item['fecha_vencimiento'] }} | {{ $item['estatus'] }}
                        </p>
                    </div>
                @empty
                    <p class="text-sm text-gray-500 dark:text-gray-400">Sin obligaciones urgentes para este filtro.</p>
                @endforelse
            </div>
        </div>
    </div>

    <div
        x-show="contadorDetalle"
        x-cloak
        x-transition.opacity
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
    >
        <div
            @click.outside="contadorDetalle = null"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="w-full max-w-5xl rounded-2xl border border-gray-200 bg-white p-5 shadow-2xl dark:border-gray-700 dark:bg-gray-900"
        >
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h3 class="text-xl font-semibold text-stone-600 dark:text-white" x-text="contadorDetalle?.nombre"></h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Periodo: <span x-text="contadorDetalle?.periodo ?? 0"></span> |
                        Atrasadas: <span x-text="contadorDetalle?.atrasadas ?? 0"></span> |
                        Faltantes: <span x-text="contadorDetalle?.faltantes ?? 0"></span>
                    </p>
                </div>

                <button
                    type="button"
                    @click="contadorDetalle = null"
                    class="rounded-lg border border-gray-200 px-3 py-2 text-sm text-gray-600 transition hover:border-amber-600 dark:border-gray-700 dark:text-gray-300"
                >
                    Cerrar
                </button>
            </div>

            <div class="mt-5 grid grid-cols-1 gap-4 lg:grid-cols-2">
                <div class="rounded-xl border border-red-200 bg-red-50 p-4 dark:border-red-900/50 dark:bg-red-900/20">
                    <div class="flex items-center justify-between gap-3">
                        <h4 class="text-sm font-semibold text-red-700 dark:text-red-300">Atrasadas</h4>
                        <span class="rounded-full bg-red-100 px-2 py-1 text-xs font-medium text-red-700 dark:bg-red-900/40 dark:text-red-300" x-text="(contadorDetalle?.detalle_atrasadas || []).length"></span>
                    </div>

                    <div class="mt-3 max-h-80 space-y-2 overflow-y-auto pr-1">
                        <template x-for="item in (contadorDetalle?.detalle_atrasadas || [])" :key="`${item.cliente}-${item.obligacion}-${item.fecha_vencimiento}`">
                            <div class="rounded-lg border border-red-200 bg-white p-3 text-sm dark:border-red-900/40 dark:bg-gray-900">
                                <p class="font-medium text-stone-700 dark:text-white" x-text="item.cliente"></p>
                                <p class="mt-1 text-red-700 dark:text-red-300" x-text="item.obligacion"></p>
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                    Vence: <span x-text="item.fecha_vencimiento"></span> |
                                    <span x-text="item.estatus"></span>
                                </p>
                            </div>
                        </template>

                        <p x-show="!(contadorDetalle?.detalle_atrasadas || []).length" class="text-sm text-red-700 dark:text-red-300">
                            Sin obligaciones atrasadas.
                        </p>
                    </div>
                </div>

                <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 dark:border-amber-900/50 dark:bg-amber-900/20">
                    <div class="flex items-center justify-between gap-3">
                        <h4 class="text-sm font-semibold text-amber-700 dark:text-amber-300">Faltantes del mes</h4>
                        <span class="rounded-full bg-amber-100 px-2 py-1 text-xs font-medium text-amber-700 dark:bg-amber-900/40 dark:text-amber-300" x-text="(contadorDetalle?.detalle_faltantes || []).length"></span>
                    </div>

                    <div class="mt-3 max-h-80 space-y-2 overflow-y-auto pr-1">
                        <template x-for="item in (contadorDetalle?.detalle_faltantes || [])" :key="`${item.cliente}-${item.obligacion}-${item.fecha_vencimiento}`">
                            <div class="rounded-lg border border-amber-200 bg-white p-3 text-sm dark:border-amber-900/40 dark:bg-gray-900">
                                <p class="font-medium text-stone-700 dark:text-white" x-text="item.cliente"></p>
                                <p class="mt-1 text-amber-700 dark:text-amber-300" x-text="item.obligacion"></p>
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                    Vence: <span x-text="item.fecha_vencimiento"></span> |
                                    <span x-text="item.estatus"></span>
                                </p>
                            </div>
                        </template>

                        <p x-show="!(contadorDetalle?.detalle_faltantes || []).length" class="text-sm text-amber-700 dark:text-amber-300">
                            Sin obligaciones faltantes en el mes.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div
        x-show="operacionModal"
        x-cloak
        x-transition.opacity
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
    >
        <div
            @click.outside="operacionModal = null"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="w-full max-w-6xl rounded-2xl border border-gray-200 bg-white p-5 shadow-2xl dark:border-gray-700 dark:bg-gray-900"
        >
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h3 class="text-xl font-semibold text-stone-600 dark:text-white" x-text="{
                        contratos: 'Contratos por vencer',
                        incompletos: 'Clientes con faltantes',
                        clientes_activos: 'Clientes activos',
                        clientes_inactivos: 'Clientes inactivos',
                        contratos_vigentes: 'Contratos vigentes',
                        contratos_vencidos: 'Contratos vencidos',
                        sin_contrato: 'Clientes sin contrato',
                        clientes_completos: 'Clientes completos',
                        obligaciones_incompletas: 'Obligaciones incompletas',
                        tareas_incompletas: 'Tareas incompletas'
                    }[operacionModal]"></h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400" x-text="{
                        contratos: 'Detalle de contratos próximos a vencer o vencidos.',
                        incompletos: 'Detalle de clientes con obligaciones o tareas incompletas.',
                        clientes_activos: 'Listado de clientes activos.',
                        clientes_inactivos: 'Listado de clientes inactivos.',
                        contratos_vigentes: 'Clientes con contrato vigente.',
                        contratos_vencidos: 'Clientes con contrato vencido.',
                        sin_contrato: 'Clientes activos sin contrato registrado.',
                        clientes_completos: 'Clientes completos para operación.',
                        obligaciones_incompletas: 'Obligaciones que aún requieren contador o carpeta.',
                        tareas_incompletas: 'Tareas que aún requieren contador o carpeta.'
                    }[operacionModal]"></p>
                </div>

                <button
                    type="button"
                    @click="operacionModal = null"
                    class="rounded-lg border border-gray-200 px-3 py-2 text-sm text-gray-600 transition hover:border-amber-600 dark:border-gray-700 dark:text-gray-300"
                >
                    Cerrar
                </button>
            </div>

            <div x-show="operacionModal === 'contratos'" class="mt-5 max-h-[32rem] space-y-2 overflow-y-auto pr-1">
                @forelse($dashboard['contratos_por_vencer'] as $cliente)
                    <div @class([
                        'rounded-lg border p-3 text-sm',
                        'border-red-200 bg-red-50 dark:border-red-900/60 dark:bg-red-900/20' => $cliente['vencido'],
                        'border-amber-200 bg-amber-50 dark:border-amber-900/60 dark:bg-amber-900/20' => ! $cliente['vencido'],
                    ])>
                        <p @class([
                            'font-medium',
                            'text-red-700 dark:text-red-300' => $cliente['vencido'],
                            'text-amber-800 dark:text-amber-200' => ! $cliente['vencido'],
                        ])>{{ $cliente['nombre'] }}</p>
                        <p @class([
                            'text-sm',
                            'text-red-700/90 dark:text-red-200/80' => $cliente['vencido'],
                            'text-amber-700/90 dark:text-amber-200/80' => ! $cliente['vencido'],
                        ])>
                            Vigencia: {{ $cliente['vigencia'] ?? '-' }} |
                            @if ($cliente['vencido'])
                                Vencido hace {{ abs((int) ($cliente['dias'] ?? 0)) }} dias
                            @else
                                {{ $cliente['dias'] ?? 0 }} dias
                            @endif
                        </p>
                    </div>
                @empty
                    <p class="text-sm text-gray-500 dark:text-gray-400">Sin contratos por vencer.</p>
                @endforelse
            </div>

            <div x-show="['clientes_activos','clientes_inactivos','contratos_vigentes','contratos_vencidos','sin_contrato','clientes_completos'].includes(operacionModal)" class="mt-5 max-h-[32rem] space-y-2 overflow-y-auto pr-1">
                @foreach ([
                    'clientes_activos' => $dashboard['clientes_activos_lista'],
                    'clientes_inactivos' => $dashboard['clientes_inactivos_lista'],
                    'contratos_vigentes' => $dashboard['contratos_vigentes_lista'],
                    'contratos_vencidos' => $dashboard['contratos_vencidos_lista'],
                    'sin_contrato' => $dashboard['sin_contrato_lista'],
                    'clientes_completos' => $dashboard['clientes_completos_lista'],
                ] as $modalKey => $clientesLista)
                    <div x-show="operacionModal === '{{ $modalKey }}'" class="space-y-2">
                        @forelse($clientesLista as $cliente)
                            <div class="rounded-lg border border-gray-200 bg-gray-50 p-3 text-sm dark:border-gray-700 dark:bg-gray-800">
                                <p class="font-medium text-stone-700 dark:text-white">{{ $cliente['nombre'] }}</p>
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                    Vigencia: {{ $cliente['vigencia'] ?? 'Sin fecha' }}
                                </p>
                            </div>
                        @empty
                            <p class="text-sm text-gray-500 dark:text-gray-400">Sin registros para mostrar.</p>
                        @endforelse
                    </div>
                @endforeach
            </div>

            <div x-show="operacionModal === 'incompletos'" class="mt-5 grid grid-cols-1 gap-4 lg:grid-cols-[0.9fr_1.4fr]">
                <div class="max-h-[32rem] space-y-2 overflow-y-auto pr-1">
                    @forelse($dashboard['clientes_incompletos'] as $cliente)
                        <button
                            type="button"
                            @click='clienteDetalle = @json($cliente)'
                            class="block w-full rounded-lg border border-rose-200 bg-rose-50 p-3 text-left text-sm transition hover:border-amber-600 dark:border-rose-900/60 dark:bg-rose-900/20"
                        >
                            <p class="font-medium text-rose-700 dark:text-rose-300">{{ $cliente['nombre'] }}</p>
                        </button>
                    @empty
                        <p class="text-sm text-gray-500 dark:text-gray-400">No hay clientes incompletos.</p>
                    @endforelse
                </div>

                <div class="min-h-[24rem] rounded-xl border border-rose-200 bg-white p-5 dark:border-rose-900/50 dark:bg-gray-950">
                    <div x-show="clienteDetalle">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <h3 class="text-lg font-semibold text-stone-600 dark:text-white" x-text="clienteDetalle?.nombre"></h3>
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                    Vigencia: <span x-text="clienteDetalle?.vigencia || 'Sin fecha'"></span>
                                </p>
                            </div>

                            <button
                                type="button"
                                @click="clienteDetalle = null"
                                class="rounded-lg border border-gray-200 px-3 py-2 text-sm text-gray-600 transition hover:border-amber-600 dark:border-gray-700 dark:text-gray-300"
                            >
                                Limpiar
                            </button>
                        </div>

                        <div class="mt-4 grid grid-cols-1 gap-4 xl:grid-cols-2">
                            <a :href="clienteDetalle?.obligaciones_url || clienteDetalle?.expediente_url"
                                class="block rounded-xl border border-red-200 bg-red-50 p-4 transition hover:border-amber-600 dark:border-red-900/50 dark:bg-red-900/20">
                                <div class="flex items-center justify-between gap-3">
                                    <p class="text-xs uppercase tracking-wide text-red-700 dark:text-red-300">Obligaciones</p>
                                    <span class="text-xs font-medium text-red-700 dark:text-red-300">Abrir lista</span>
                                </div>
                                <div class="mt-3 overflow-x-auto">
                                    <table class="min-w-full text-sm">
                                        <thead>
                                            <tr class="text-left text-red-700 dark:text-red-300">
                                                <th class="pb-2 pr-3">Obligacion</th>
                                                <th class="pb-2 pr-3">Carpeta</th>
                                                <th class="pb-2">Contador</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <template x-for="item in (clienteDetalle?.detalle_obligaciones || [])" :key="item.nombre">
                                                <tr class="border-t border-red-200/70 dark:border-red-900/40">
                                                    <td class="py-2 pr-3 text-red-800 dark:text-red-200" x-text="item.nombre"></td>
                                                    <td class="py-2 pr-3">
                                                        <span class="rounded-full px-2 py-1 text-xs"
                                                            :class="item.carpeta ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300' : 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300'"
                                                            x-text="item.carpeta ? 'OK' : 'Falta'"></span>
                                                    </td>
                                                    <td class="py-2">
                                                        <span class="rounded-full px-2 py-1 text-xs"
                                                            :class="item.contador ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300' : 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300'"
                                                            x-text="item.contador || 'Falta'"></span>
                                                    </td>
                                                </tr>
                                            </template>
                                            <tr x-show="!(clienteDetalle?.detalle_obligaciones || []).length">
                                                <td colspan="3" class="py-2 text-red-700 dark:text-red-300">Sin faltantes en obligaciones.</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </a>

                            <a :href="clienteDetalle?.tareas_url || clienteDetalle?.expediente_url"
                                class="block rounded-xl border border-orange-200 bg-orange-50 p-4 transition hover:border-amber-600 dark:border-orange-900/50 dark:bg-orange-900/20">
                                <div class="flex items-center justify-between gap-3">
                                    <p class="text-xs uppercase tracking-wide text-orange-700 dark:text-orange-300">Tareas</p>
                                    <span class="text-xs font-medium text-orange-700 dark:text-orange-300">Abrir lista</span>
                                </div>
                                <div class="mt-3 overflow-x-auto">
                                    <table class="min-w-full text-sm">
                                        <thead>
                                            <tr class="text-left text-orange-700 dark:text-orange-300">
                                                <th class="pb-2 pr-3">Tarea</th>
                                                <th class="pb-2 pr-3">Carpeta</th>
                                                <th class="pb-2">Contador</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <template x-for="item in (clienteDetalle?.detalle_tareas || [])" :key="item.nombre">
                                                <tr class="border-t border-orange-200/70 dark:border-orange-900/40">
                                                    <td class="py-2 pr-3 text-orange-800 dark:text-orange-200" x-text="item.nombre"></td>
                                                    <td class="py-2 pr-3">
                                                        <span class="rounded-full px-2 py-1 text-xs"
                                                            :class="item.carpeta ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300' : 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300'"
                                                            x-text="item.carpeta ? 'OK' : 'Falta'"></span>
                                                    </td>
                                                    <td class="py-2">
                                                        <span class="rounded-full px-2 py-1 text-xs"
                                                            :class="item.contador ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300' : 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300'"
                                                            x-text="item.contador || 'Falta'"></span>
                                                    </td>
                                                </tr>
                                            </template>
                                            <tr x-show="!(clienteDetalle?.detalle_tareas || []).length">
                                                <td colspan="3" class="py-2 text-orange-700 dark:text-orange-300">Sin faltantes en tareas.</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </a>
                        </div>
                    </div>

                    <div x-show="!clienteDetalle" class="flex h-full min-h-[20rem] items-center justify-center">
                        <p class="text-sm text-gray-500 dark:text-gray-400">Selecciona un cliente de la lista para ver el detalle.</p>
                    </div>
                </div>
            </div>

            <div x-show="operacionModal === 'obligaciones_incompletas'" class="mt-5 max-h-[32rem] overflow-y-auto pr-1">
                <div class="space-y-2">
                    @forelse($dashboard['obligaciones_incompletas_lista'] as $item)
                        <div class="rounded-lg border border-red-200 bg-red-50 p-3 text-sm dark:border-red-900/50 dark:bg-red-900/20">
                            <p class="font-medium text-stone-700 dark:text-white">{{ $item['cliente'] }}</p>
                            <p class="mt-1 text-red-700 dark:text-red-300">{{ $item['nombre'] }}</p>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                Contador: {{ $item['contador'] }} | Carpeta: {{ $item['carpeta'] ? 'OK' : 'Falta' }} | {{ $item['fecha_vencimiento'] ?? 'Sin fecha' }}
                            </p>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500 dark:text-gray-400">Sin obligaciones incompletas.</p>
                    @endforelse
                </div>
            </div>

            <div x-show="operacionModal === 'tareas_incompletas'" class="mt-5 max-h-[32rem] overflow-y-auto pr-1">
                <div class="space-y-2">
                    @forelse($dashboard['tareas_incompletas_lista'] as $item)
                        <div class="rounded-lg border border-fuchsia-200 bg-fuchsia-50 p-3 text-sm dark:border-fuchsia-900/50 dark:bg-fuchsia-900/20">
                            <p class="font-medium text-stone-700 dark:text-white">{{ $item['cliente'] }}</p>
                            <p class="mt-1 text-fuchsia-700 dark:text-fuchsia-300">{{ $item['nombre'] }}</p>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                Contador: {{ $item['contador'] }} | Carpeta: {{ $item['carpeta'] ? 'OK' : 'Falta' }} | {{ $item['fecha_limite'] ?? 'Sin fecha' }}
                            </p>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500 dark:text-gray-400">Sin tareas incompletas.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <section class="rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-700 dark:bg-gray-900">
        <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
            <div>
                <h3 class="text-xl font-semibold text-stone-600 dark:text-white">Validaciones</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Pendientes de revisar, urgentes y rechazadas del periodo seleccionado.</p>
            </div>

            <div
                class="w-full rounded-xl border border-emerald-200 bg-gradient-to-r from-emerald-50 to-white p-4 dark:border-emerald-900/50 dark:from-emerald-950/40 dark:to-gray-900 md:max-w-sm">
                <div class="flex items-end justify-between gap-4">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-emerald-700 dark:text-emerald-300">
                            Pendientes por validar
                        </p>
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">
                            {{ $dashboard['validaciones']['kpis']['urgentes'] }} urgentes del total actual
                        </p>
                    </div>
                    <p class="text-4xl font-bold leading-none text-emerald-600 dark:text-emerald-400">
                        {{ $dashboard['validaciones']['kpis']['pendientes'] }}
                    </p>
                </div>
            </div>
        </div>

        <section class="space-y-4 mt-4">
            <h3 class="text-base font-semibold text-stone-600 dark:text-white">Indicadores</h3>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <button
                    type="button"
                    @click="validacionesModal = 'pendientes'"
                    class="cursor-pointer rounded-xl border border-sky-200 bg-gradient-to-br from-sky-50 to-white p-4 text-left transition hover:border-amber-600 dark:border-sky-900/50 dark:from-sky-950/40 dark:to-gray-800">
                    <p class="text-xs uppercase tracking-wide text-gray-500">Pendientes</p>
                    <p class="mt-2 text-2xl font-semibold text-sky-600">{{ $dashboard['validaciones']['kpis']['pendientes'] }}</p>
                </button>
                <button
                    type="button"
                    @click="validacionesModal = 'urgentes'"
                    class="cursor-pointer rounded-xl border border-red-200 bg-gradient-to-br from-red-50 to-white p-4 text-left transition hover:border-amber-600 dark:border-red-900/50 dark:from-red-950/40 dark:to-gray-800">
                    <p class="text-xs uppercase tracking-wide text-gray-500">Urgentes</p>
                    <p class="mt-2 text-2xl font-semibold text-red-600">{{ $dashboard['validaciones']['kpis']['urgentes'] }}</p>
                </button>
                <button
                    type="button"
                    @click="validacionesModal = 'rechazadas'"
                    class="cursor-pointer rounded-xl border border-rose-200 bg-gradient-to-br from-rose-50 to-white p-4 text-left transition hover:border-amber-600 dark:border-rose-900/50 dark:from-rose-950/40 dark:to-gray-800">
                    <p class="text-xs uppercase tracking-wide text-gray-500">Rechazadas</p>
                    <p class="mt-2 text-2xl font-semibold text-rose-600">{{ $dashboard['validaciones']['kpis']['rechazadas'] }}</p>
                </button>
                <button
                    type="button"
                    @click="validacionesModal = 'atendidas'"
                    class="cursor-pointer rounded-xl border border-amber-200 bg-gradient-to-br from-amber-50 to-white p-4 text-left transition hover:border-amber-600 dark:border-amber-900/50 dark:from-amber-950/40 dark:to-gray-800">
                    <p class="text-xs uppercase tracking-wide text-gray-500">Rechazadas atendidas</p>
                    <p class="mt-2 text-2xl font-semibold text-amber-600">{{ $dashboard['validaciones']['kpis']['rechazadas_atendidas'] }}</p>
                </button>
            </div>
        </section>

    </section>

    <section class="rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-700 dark:bg-gray-900">
        <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
            <div>
                <h3 class="text-xl font-semibold text-stone-600 dark:text-white">Envios al cliente</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Seguimiento de obligaciones listas para enviar y respuestas del cliente pendientes.</p>
            </div>

            <div
                class="w-full rounded-xl border border-cyan-200 bg-gradient-to-r from-cyan-50 to-white p-4 dark:border-cyan-900/50 dark:from-cyan-950/40 dark:to-gray-900 md:max-w-sm">
                <div class="flex items-end justify-between gap-4">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-cyan-700 dark:text-cyan-300">
                            Faltantes de envio
                        </p>
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">
                            {{ $dashboard['envios']['kpis']['respuestas_pendientes'] }} respuestas por revisar
                        </p>
                    </div>
                    <p class="text-4xl font-bold leading-none text-cyan-600 dark:text-cyan-400">
                        {{ $dashboard['envios']['kpis']['faltantes_envio'] }}
                    </p>
                </div>
            </div>
        </div>

        <section class="space-y-4 mt-4">
            <h3 class="text-base font-semibold text-stone-600 dark:text-white">Indicadores</h3>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-5">
                <button
                    type="button"
                    @click="enviosModal = 'listos'"
                    class="cursor-pointer rounded-xl border border-cyan-200 bg-gradient-to-br from-cyan-50 to-white p-4 text-left transition hover:border-amber-600 dark:border-cyan-900/50 dark:from-cyan-950/40 dark:to-gray-800">
                    <p class="text-xs uppercase tracking-wide text-gray-500">Listas para enviar</p>
                    <p class="mt-2 text-2xl font-semibold text-cyan-600">{{ $dashboard['envios']['kpis']['listos_para_enviar'] }}</p>
                </button>
                <button
                    type="button"
                    @click="enviosModal = 'enviadas'"
                    class="cursor-pointer rounded-xl border border-sky-200 bg-gradient-to-br from-sky-50 to-white p-4 text-left transition hover:border-amber-600 dark:border-sky-900/50 dark:from-sky-950/40 dark:to-gray-800">
                    <p class="text-xs uppercase tracking-wide text-gray-500">Enviadas</p>
                    <p class="mt-2 text-2xl font-semibold text-sky-600">{{ $dashboard['envios']['kpis']['enviadas'] }}</p>
                </button>
                <button
                    type="button"
                    @click="enviosModal = 'faltantes'"
                    class="cursor-pointer rounded-xl border border-amber-200 bg-gradient-to-br from-amber-50 to-white p-4 text-left transition hover:border-amber-600 dark:border-amber-900/50 dark:from-amber-950/40 dark:to-gray-800">
                    <p class="text-xs uppercase tracking-wide text-gray-500">Faltantes de envio</p>
                    <p class="mt-2 text-2xl font-semibold text-amber-600">{{ $dashboard['envios']['kpis']['faltantes_envio'] }}</p>
                </button>
                <button
                    type="button"
                    @click="enviosModal = 'respuestas_pendientes'"
                    class="cursor-pointer rounded-xl border border-orange-200 bg-gradient-to-br from-orange-50 to-white p-4 text-left transition hover:border-amber-600 dark:border-orange-900/50 dark:from-orange-950/40 dark:to-gray-800">
                    <p class="text-xs uppercase tracking-wide text-gray-500">Respuestas pendientes</p>
                    <p class="mt-2 text-2xl font-semibold text-orange-600">{{ $dashboard['envios']['kpis']['respuestas_pendientes'] }}</p>
                </button>
                <button
                    type="button"
                    @click="enviosModal = 'respuestas_revisadas'"
                    class="cursor-pointer rounded-xl border border-emerald-200 bg-gradient-to-br from-emerald-50 to-white p-4 text-left transition hover:border-amber-600 dark:border-emerald-900/50 dark:from-emerald-950/40 dark:to-gray-800">
                    <p class="text-xs uppercase tracking-wide text-gray-500">Respuestas revisadas</p>
                    <p class="mt-2 text-2xl font-semibold text-emerald-600">{{ $dashboard['envios']['kpis']['respuestas_revisadas'] }}</p>
                </button>
            </div>
        </section>
    </section>

    <div
        x-show="enviosModal"
        x-cloak
        x-transition.opacity
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
    >
        <div
            @click.outside="enviosModal = null"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="w-full max-w-4xl rounded-2xl border border-gray-200 bg-white p-5 shadow-2xl dark:border-gray-700 dark:bg-gray-900"
        >
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h3 class="text-xl font-semibold text-stone-600 dark:text-white" x-text="{ listos: 'Obligaciones listas para enviar', enviadas: 'Obligaciones enviadas', faltantes: 'Faltantes de envio', respuestas_pendientes: 'Respuestas pendientes', respuestas_revisadas: 'Respuestas revisadas' }[enviosModal]"></h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400" x-text="{ listos: 'Detalle de obligaciones ya finalizadas y listas para envio.', enviadas: 'Detalle de obligaciones ya enviadas al cliente.', faltantes: 'Detalle de obligaciones pendientes de envio.', respuestas_pendientes: 'Detalle de respuestas del cliente pendientes de revision.', respuestas_revisadas: 'Detalle de respuestas del cliente ya revisadas.' }[enviosModal]"></p>
                </div>

                <button
                    type="button"
                    @click="enviosModal = null"
                    class="rounded-lg border border-gray-200 px-3 py-2 text-sm text-gray-600 transition hover:border-amber-600 dark:border-gray-700 dark:text-gray-300"
                >
                    Cerrar
                </button>
            </div>

            <div x-show="['listos','faltantes'].includes(enviosModal)" class="mt-5 max-h-[32rem] space-y-2 overflow-y-auto pr-1">
                @forelse($dashboard['envios']['pendientes_envio'] as $item)
                    <div class="rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm dark:border-amber-900/50 dark:bg-amber-900/20">
                        <p class="font-medium text-stone-700 dark:text-white">{{ $item['cliente'] }}</p>
                        <p class="mt-1 text-amber-700 dark:text-amber-300">{{ $item['obligacion'] }}</p>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            {{ $item['contador'] }} | {{ $item['fecha_vencimiento'] }} | {{ $item['estatus'] }}
                        </p>
                    </div>
                @empty
                    <p class="text-sm text-gray-500 dark:text-gray-400">Sin obligaciones pendientes de envio.</p>
                @endforelse
            </div>

            <div x-show="enviosModal === 'enviadas'" class="mt-5 max-h-[32rem] space-y-2 overflow-y-auto pr-1">
                @forelse($dashboard['envios']['enviadas_lista'] as $item)
                    <div class="rounded-lg border border-sky-200 bg-sky-50 p-3 text-sm dark:border-sky-900/50 dark:bg-sky-900/20">
                        <p class="font-medium text-stone-700 dark:text-white">{{ $item['cliente'] }}</p>
                        <p class="mt-1 text-sky-700 dark:text-sky-300">{{ $item['obligacion'] }}</p>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            {{ $item['contador'] }} | {{ $item['fecha_vencimiento'] }} | {{ $item['estatus'] }}
                        </p>
                    </div>
                @empty
                    <p class="text-sm text-gray-500 dark:text-gray-400">Sin obligaciones enviadas.</p>
                @endforelse
            </div>

            <div x-show="enviosModal === 'respuestas_pendientes'" class="mt-5 max-h-[32rem] space-y-2 overflow-y-auto pr-1">
                @forelse($dashboard['envios']['respuestas_pendientes_lista'] as $item)
                    <div class="rounded-lg border border-orange-200 bg-orange-50 p-3 text-sm dark:border-orange-900/50 dark:bg-orange-900/20">
                        <p class="font-medium text-stone-700 dark:text-white">{{ $item['cliente'] }}</p>
                        <p class="mt-1 text-orange-700 dark:text-orange-300">{{ $item['obligacion'] }}</p>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            {{ $item['contador'] }} | {{ $item['fecha_vencimiento'] }} | {{ $item['estatus'] }}
                        </p>
                    </div>
                @empty
                    <p class="text-sm text-gray-500 dark:text-gray-400">Sin respuestas pendientes de revisar.</p>
                @endforelse
            </div>

            <div x-show="enviosModal === 'respuestas_revisadas'" class="mt-5 max-h-[32rem] space-y-2 overflow-y-auto pr-1">
                @forelse($dashboard['envios']['respuestas_revisadas_lista'] as $item)
                    <div class="rounded-lg border border-emerald-200 bg-emerald-50 p-3 text-sm dark:border-emerald-900/50 dark:bg-emerald-900/20">
                        <p class="font-medium text-stone-700 dark:text-white">{{ $item['cliente'] }}</p>
                        <p class="mt-1 text-emerald-700 dark:text-emerald-300">{{ $item['obligacion'] }}</p>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            {{ $item['contador'] }} | {{ $item['fecha_vencimiento'] }} | {{ $item['estatus'] }}
                        </p>
                    </div>
                @empty
                    <p class="text-sm text-gray-500 dark:text-gray-400">Sin respuestas revisadas.</p>
                @endforelse
            </div>
        </div>
    </div>

    <div
        x-show="validacionesModal"
        x-cloak
        x-transition.opacity
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
    >
        <div
            @click.outside="validacionesModal = null"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="w-full max-w-4xl rounded-2xl border border-gray-200 bg-white p-5 shadow-2xl dark:border-gray-700 dark:bg-gray-900"
        >
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h3 class="text-xl font-semibold text-stone-600 dark:text-white" x-text="{ pendientes: 'Pendientes por validar', urgentes: 'Validaciones urgentes', rechazadas: 'Rechazadas para seguimiento', atendidas: 'Rechazadas atendidas' }[validacionesModal]"></h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400" x-text="{ pendientes: 'Detalle de obligaciones listas para revisión.', urgentes: 'Detalle de pendientes con vencimiento inmediato.', rechazadas: 'Detalle de obligaciones rechazadas.', atendidas: 'Detalle de obligaciones rechazadas que ya fueron atendidas.' }[validacionesModal]"></p>
                </div>

                <button
                    type="button"
                    @click="validacionesModal = null"
                    class="rounded-lg border border-gray-200 px-3 py-2 text-sm text-gray-600 transition hover:border-amber-600 dark:border-gray-700 dark:text-gray-300"
                >
                    Cerrar
                </button>
            </div>

            <div x-show="validacionesModal === 'pendientes'" class="mt-5 max-h-[32rem] space-y-2 overflow-y-auto pr-1">
                @forelse($dashboard['validaciones']['bandeja'] as $item)
                    <div class="rounded-lg border border-sky-200 bg-sky-50 p-3 text-sm dark:border-sky-900/50 dark:bg-sky-900/20">
                        <p class="font-medium text-stone-700 dark:text-white">{{ $item['cliente'] }}</p>
                        <p class="mt-1 text-sky-700 dark:text-sky-300">{{ $item['obligacion'] }}</p>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $item['contador'] }} | {{ $item['fecha_vencimiento'] }} | {{ $item['estatus'] }}</p>
                    </div>
                @empty
                    <p class="text-sm text-gray-500 dark:text-gray-400">Sin pendientes por validar.</p>
                @endforelse
            </div>

            <div x-show="validacionesModal === 'urgentes'" class="mt-5 max-h-[32rem] space-y-2 overflow-y-auto pr-1">
                @forelse(collect($dashboard['validaciones']['bandeja'])->filter(fn ($item) => $item['urgente']) as $item)
                    <div class="rounded-lg border border-red-200 bg-red-50 p-3 text-sm dark:border-red-900/50 dark:bg-red-900/20">
                        <p class="font-medium text-stone-700 dark:text-white">{{ $item['cliente'] }}</p>
                        <p class="mt-1 text-red-700 dark:text-red-300">{{ $item['obligacion'] }}</p>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $item['contador'] }} | {{ $item['fecha_vencimiento'] }} | {{ $item['estatus'] }}</p>
                    </div>
                @empty
                    <p class="text-sm text-gray-500 dark:text-gray-400">Sin validaciones urgentes.</p>
                @endforelse
            </div>

            <div x-show="validacionesModal === 'rechazadas'" class="mt-5 max-h-[32rem] space-y-2 overflow-y-auto pr-1">
                @forelse($dashboard['validaciones']['rechazadas_seguimiento'] as $item)
                    <div class="rounded-lg border border-rose-200 bg-rose-50 p-3 text-sm dark:border-rose-900/50 dark:bg-rose-900/20">
                        <p class="font-medium text-stone-700 dark:text-white">{{ $item['cliente'] }}</p>
                        <p class="mt-1 text-rose-700 dark:text-rose-300">{{ $item['obligacion'] }}</p>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $item['contador'] }} | {{ $item['fecha_vencimiento'] }} | {{ $item['estatus'] }}</p>
                    </div>
                @empty
                    <p class="text-sm text-gray-500 dark:text-gray-400">Sin rechazadas para seguimiento.</p>
                @endforelse
            </div>

            <div x-show="validacionesModal === 'atendidas'" class="mt-5 max-h-[32rem] space-y-2 overflow-y-auto pr-1">
                @forelse($dashboard['validaciones']['rechazadas_atendidas_lista'] as $item)
                    <div class="rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm dark:border-amber-900/50 dark:bg-amber-900/20">
                        <p class="font-medium text-stone-700 dark:text-white">{{ $item['cliente'] }}</p>
                        <p class="mt-1 text-amber-700 dark:text-amber-300">{{ $item['obligacion'] }}</p>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $item['contador'] }} | {{ $item['fecha_vencimiento'] }} | {{ $item['estatus'] }}</p>
                    </div>
                @empty
                    <p class="text-sm text-gray-500 dark:text-gray-400">Sin rechazadas atendidas.</p>
                @endforelse
            </div>
        </div>
    </div>

    <section class="rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-700 dark:bg-gray-900">
        <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
            <div>
                <h3 class="text-xl font-semibold text-stone-600 dark:text-white">Solicitudes del cliente</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Espacio reservado para el módulo de solicitudes hechas por el cliente.</p>
            </div>

            <div
                class="w-full rounded-xl border border-violet-200 bg-gradient-to-r from-violet-50 to-white p-4 dark:border-violet-900/50 dark:from-violet-950/40 dark:to-gray-900 md:max-w-sm">
                <div class="flex items-end justify-between gap-4">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-violet-700 dark:text-violet-300">
                            Solicitudes pendientes
                        </p>
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">
                            Se activará cuando exista el módulo de solicitudes.
                        </p>
                    </div>
                    <p class="text-4xl font-bold leading-none text-violet-600 dark:text-violet-400">
                        0
                    </p>
                </div>
            </div>
        </div>

        <section class="space-y-4 mt-4">
            <h3 class="text-base font-semibold text-stone-600 dark:text-white">Indicadores</h3>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <div class="rounded-xl border border-slate-200 bg-gradient-to-br from-slate-50 to-white p-4 dark:border-slate-700 dark:from-slate-900 dark:to-gray-800">
                    <p class="text-xs uppercase tracking-wide text-gray-500">Solicitudes hechas</p>
                    <p class="mt-2 text-2xl font-semibold text-slate-600 dark:text-white">0</p>
                </div>
                <div class="rounded-xl border border-emerald-200 bg-gradient-to-br from-emerald-50 to-white p-4 dark:border-emerald-900/50 dark:from-emerald-950/40 dark:to-gray-800">
                    <p class="text-xs uppercase tracking-wide text-gray-500">Atendidas</p>
                    <p class="mt-2 text-2xl font-semibold text-emerald-600">0</p>
                </div>
                <div class="rounded-xl border border-amber-200 bg-gradient-to-br from-amber-50 to-white p-4 dark:border-amber-900/50 dark:from-amber-950/40 dark:to-gray-800">
                    <p class="text-xs uppercase tracking-wide text-gray-500">Faltantes</p>
                    <p class="mt-2 text-2xl font-semibold text-amber-600">0</p>
                </div>
                <div class="rounded-xl border border-red-200 bg-gradient-to-br from-red-50 to-white p-4 dark:border-red-900/50 dark:from-red-950/40 dark:to-gray-800">
                    <p class="text-xs uppercase tracking-wide text-gray-500">Vencidas</p>
                    <p class="mt-2 text-2xl font-semibold text-red-600">0</p>
                </div>
            </div>
        </section>

        <section class="mt-4 rounded-xl border border-dashed border-gray-300 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
            <h3 class="text-sm font-semibold text-gray-600 dark:text-gray-300">Espacio reservado para el módulo</h3>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                Aquí podremos integrar la bandeja de solicitudes, filtros, estatus, tiempos de respuesta y seguimiento del cliente.
            </p>
        </section>
    </section>

    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script>
        function seguimientoLineChart(data) {
            return {
                chart: null,
                categories: data.categories ?? [],
                cerradas: data.cerradas ?? [],
                faltantes: data.faltantes ?? [],
                atrasadas: data.atrasadas ?? [],
                init() {
                    if (!this.categories.length || typeof ApexCharts === 'undefined') {
                        return;
                    }

                    if (this.chart) {
                        this.chart.destroy();
                    }

                    const isDark = document.documentElement.classList.contains('dark');
                    const labelColor = isDark ? '#9ca3af' : '#6b7280';
                    const gridColor = isDark ? '#374151' : '#e5e7eb';

                    this.chart = new ApexCharts(this.$refs.chart, {
                        series: [
                            { name: 'Cerradas', data: this.cerradas },
                            { name: 'Faltantes', data: this.faltantes },
                            { name: 'Atrasadas', data: this.atrasadas },
                        ],
                        chart: {
                            type: 'bar',
                            height: 320,
                            toolbar: { show: false },
                            zoom: { enabled: false },
                        },
                        plotOptions: {
                            bar: {
                                horizontal: false,
                                columnWidth: '42%',
                                borderRadius: 4,
                            },
                        },
                        colors: ['#10b981', '#f59e0b', '#ef4444'],
                        markers: {
                            size: 0,
                        },
                        dataLabels: {
                            enabled: false,
                        },
                        xaxis: {
                            categories: this.categories,
                            labels: {
                                style: {
                                    colors: labelColor,
                                    fontSize: '12px',
                                },
                            },
                            axisBorder: { show: false },
                            axisTicks: { show: false },
                        },
                        yaxis: {
                            labels: {
                                style: {
                                    colors: labelColor,
                                    fontSize: '12px',
                                },
                            },
                        },
                        grid: {
                            borderColor: gridColor,
                            strokeDashArray: 4,
                        },
                        legend: {
                            position: 'top',
                            horizontalAlign: 'left',
                            labels: {
                                colors: labelColor,
                            },
                        },
                        tooltip: {
                            theme: isDark ? 'dark' : 'light',
                        },
                    });

                    this.chart.render();
                },
            };
        }
    </script>
</div>
