<div x-data="{ clienteDetalle: null, contadorDetalle: null }" class="space-y-6">


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
        <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
            <div>
                <h3 class="text-xl font-semibold text-stone-600 dark:text-white">Operacion del Despacho</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Clientes, Contratos y Asiganaciones</p>
            </div>

            <div
                class="w-full rounded-xl border border-amber-200 bg-gradient-to-r from-amber-50 to-white p-4 dark:border-amber-900/50 dark:from-amber-950/40 dark:to-gray-900 md:max-w-sm">
                <div class="flex items-end justify-between gap-4">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-amber-700 dark:text-amber-300">
                            Cobertura de Clientes
                        </p>
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">
                            {{ $dashboard['resumen_despacho']['clientes_completos'] }} de
                            {{ $dashboard['resumen_despacho']['clientes_evaluados'] }} clientes completos
                        </p>
                    </div>
                    <p class="text-4xl font-bold leading-none text-amber-600 dark:text-amber-400">
                        {{ $dashboard['header']['porcentaje_cobertura'] }}%
                    </p>
                </div>

                <div class="mt-3 h-3 w-full overflow-hidden rounded-full bg-amber-100 dark:bg-gray-800">
                    <div class="h-full rounded-full bg-amber-600 transition-all duration-500"
                        style="width: {{ $dashboard['header']['porcentaje_cobertura'] }}%;"></div>
                </div>
            </div>
        </div>
        <section class="space-y-4">
            <h3 class="text-base font-semibold text-stone-600 dark:text-white">Indicadores</h3>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-5">
                <div
                    class="rounded-xl border border-stone-200 bg-gradient-to-br from-stone-50 to-white p-4 dark:border-stone-700 dark:from-stone-900 dark:to-gray-800">
                    <p class="text-xs uppercase tracking-wide text-gray-500">Clientes activos</p>
                    <p class="mt-2 text-2xl font-semibold text-stone-600 dark:text-white">
                        {{ $dashboard['kpis']['clientes_activos'] }}</p>
                </div>
                <div
                    class="rounded-xl border border-gray-200 bg-gradient-to-br from-gray-50 to-white p-4 dark:border-gray-700 dark:from-gray-900 dark:to-gray-800">
                    <p class="text-xs uppercase tracking-wide text-gray-500">Clientes inactivos</p>
                    <p class="mt-2 text-2xl font-semibold text-gray-600 dark:text-white">
                        {{ $dashboard['kpis']['clientes_inactivos'] }}</p>
                </div>
                <div
                    class="rounded-xl border border-emerald-200 bg-gradient-to-br from-emerald-50 to-white p-4 dark:border-emerald-900/50 dark:from-emerald-950/40 dark:to-gray-800">
                    <p class="text-xs uppercase tracking-wide text-gray-500">Contratos vigentes</p>
                    <p class="mt-2 text-2xl font-semibold text-emerald-600">
                        {{ $dashboard['kpis']['contratos_vigentes'] }}
                    </p>
                </div>
                <div
                    class="rounded-xl border border-amber-200 bg-gradient-to-br from-amber-50 to-white p-4 dark:border-amber-900/50 dark:from-amber-950/40 dark:to-gray-800">
                    <p class="text-xs uppercase tracking-wide text-gray-500">Por vencer</p>
                    <p class="mt-2 text-2xl font-semibold text-amber-600">
                        {{ $dashboard['kpis']['contratos_por_vencer'] }}
                    </p>
                </div>
                <div
                    class="rounded-xl border border-red-200 bg-gradient-to-br from-red-50 to-white p-4 dark:border-red-900/50 dark:from-red-950/40 dark:to-gray-800">
                    <p class="text-xs uppercase tracking-wide text-gray-500">Contratos vencidos</p>
                    <p class="mt-2 text-2xl font-semibold text-red-600">{{ $dashboard['kpis']['contratos_vencidos'] }}
                    </p>
                </div>
                <div
                    class="rounded-xl border border-teal-200 bg-gradient-to-br from-teal-50 to-white p-4 dark:border-teal-900/50 dark:from-teal-950/40 dark:to-gray-800">
                    <p class="text-xs uppercase tracking-wide text-gray-500">Clientes completos</p>
                    <p class="mt-2 text-2xl font-semibold text-teal-600">{{ $dashboard['kpis']['clientes_completos'] }}
                    </p>
                </div>
                <div
                    class="rounded-xl border border-rose-200 bg-gradient-to-br from-rose-50 to-white p-4 dark:border-rose-900/50 dark:from-rose-950/40 dark:to-gray-800">
                    <p class="text-xs uppercase tracking-wide text-gray-500">Clientes con faltantes</p>
                    <p class="mt-2 text-2xl font-semibold text-rose-600">
                        {{ $dashboard['kpis']['clientes_incompletos'] }}
                    </p>
                </div>
                <div
                    class="rounded-xl border border-red-200 bg-gradient-to-br from-red-50 to-white p-4 dark:border-red-900/50 dark:from-red-950/40 dark:to-gray-800">
                    <p class="text-xs uppercase tracking-wide text-gray-500">Obligaciones sin contador</p>
                    <p class="mt-2 text-2xl font-semibold text-red-600">
                        {{ $dashboard['kpis']['obligaciones_sin_contador'] }}</p>
                </div>
                <div
                    class="rounded-xl border border-orange-200 bg-gradient-to-br from-orange-50 to-white p-4 dark:border-orange-900/50 dark:from-orange-950/40 dark:to-gray-800">
                    <p class="text-xs uppercase tracking-wide text-gray-500">Obligaciones sin carpeta</p>
                    <p class="mt-2 text-2xl font-semibold text-orange-600">
                        {{ $dashboard['kpis']['obligaciones_sin_carpeta'] }}</p>
                </div>
                <div
                    class="rounded-xl border border-fuchsia-200 bg-gradient-to-br from-fuchsia-50 to-white p-4 dark:border-fuchsia-900/50 dark:from-fuchsia-950/40 dark:to-gray-800">
                    <p class="text-xs uppercase tracking-wide text-gray-500">Tareas sin contador / carpeta</p>
                    <p class="mt-2 text-2xl font-semibold text-fuchsia-600">
                        {{ $dashboard['kpis']['tareas_sin_contador'] + $dashboard['kpis']['tareas_sin_carpeta'] }}
                    </p>
                </div>
            </div>
        </section>

        <section class="grid grid-cols-1 gap-6 xl:grid-cols-2 mt-4 ">
            <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
                <h3 class="mb-3 text-base font-semibold text-stone-600 dark:text-white">Contratos por vencer</h3>
                <div class="space-y-2">
                    @forelse($dashboard['contratos_por_vencer'] as $cliente)
                        <div @class([
                            'rounded-lg border p-3 text-sm',
                            'border-red-200 bg-red-50 dark:border-red-900/60 dark:bg-red-900/20' =>
                                $cliente['vencido'],
                            'border-amber-200 bg-amber-50 dark:border-amber-900/60 dark:bg-amber-900/20' => !$cliente[
                                'vencido'
                            ],
                        ])>
                            <p @class([
                                'font-medium',
                                'text-red-700 dark:text-red-300' => $cliente['vencido'],
                                'text-amber-800 dark:text-amber-200' => !$cliente['vencido'],
                            ])>{{ $cliente['nombre'] }}</p>
                            <p @class([
                                'text-sm',
                                'text-red-700/90 dark:text-red-200/80' => $cliente['vencido'],
                                'text-amber-700/90 dark:text-amber-200/80' => !$cliente['vencido'],
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
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
                <h3 class="mb-3 text-base font-semibold text-stone-600 dark:text-white">Clientes incompletos</h3>
                <div class="space-y-2 max-h-80 overflow-y-auto pr-1">
                    @forelse($dashboard['clientes_incompletos'] as $cliente)
                        <button type="button" @click='clienteDetalle = @json($cliente)'
                            class="block w-full rounded-lg border border-rose-200 bg-rose-50 p-3 text-left text-sm transition hover:border-amber-600 dark:border-rose-900/60 dark:bg-rose-900/20">
                            <p class="font-medium text-rose-700 dark:text-rose-300">{{ $cliente['nombre'] }}</p>
                        </button>
                    @empty
                        <p class="text-sm text-gray-500 dark:text-gray-400">No hay clientes incompletos.</p>
                    @endforelse
                </div>
            </div>

        </section>

        <section x-show="clienteDetalle" x-cloak
            class="rounded-xl border border-rose-200 bg-white p-5 dark:border-rose-900/50 dark:bg-gray-900 mt-4">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h3 class="text-lg font-semibold text-stone-600 dark:text-white" x-text="clienteDetalle?.nombre">
                    </h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Vigencia: <span x-text="clienteDetalle?.vigencia || 'Sin fecha'"></span>
                    </p>
                </div>

                <button type="button" @click="clienteDetalle = null"
                    class="rounded-lg border border-gray-200 px-3 py-2 text-sm text-gray-600 transition hover:border-amber-600 dark:border-gray-700 dark:text-gray-300">
                    Cerrar
                </button>
            </div>

            <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                <a :href="clienteDetalle?.obligaciones_url || clienteDetalle?.detalle_obligaciones?.[0]?.obligaciones_url ||
                    clienteDetalle?.expediente_url"
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
                                <template x-for="item in (clienteDetalle?.detalle_obligaciones || [])"
                                    :key="item.nombre">
                                    <tr class="border-t border-red-200/70 dark:border-red-900/40">
                                        <td class="py-2 pr-3 text-red-800 dark:text-red-200" x-text="item.nombre"></td>
                                        <td class="py-2 pr-3">
                                            <span class="rounded-full px-2 py-1 text-xs"
                                                :class="item.carpeta ?
                                                    'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300' :
                                                    'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300'"
                                                x-text="item.carpeta ? 'OK' : 'Falta'"></span>
                                        </td>
                                        <td class="py-2">
                                            <span class="rounded-full px-2 py-1 text-xs"
                                                :class="item.contador ?
                                                    'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300' :
                                                    'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300'"
                                                x-text="item.contador || 'Falta'"></span>
                                        </td>
                                    </tr>
                                </template>
                                <tr x-show="!(clienteDetalle?.detalle_obligaciones || []).length">
                                    <td colspan="3" class="py-2 text-red-700 dark:text-red-300">Sin faltantes en
                                        obligaciones.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </a>

                <a :href="clienteDetalle?.tareas_url || clienteDetalle?.detalle_tareas?.[0]?.tareas_url || clienteDetalle
                    ?.expediente_url"
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
                                <template x-for="item in (clienteDetalle?.detalle_tareas || [])"
                                    :key="item.nombre">
                                    <tr class="border-t border-orange-200/70 dark:border-orange-900/40">
                                        <td class="py-2 pr-3 text-orange-800 dark:text-orange-200"
                                            x-text="item.nombre">
                                        </td>
                                        <td class="py-2 pr-3">
                                            <span class="rounded-full px-2 py-1 text-xs"
                                                :class="item.carpeta ?
                                                    'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300' :
                                                    'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300'"
                                                x-text="item.carpeta ? 'OK' : 'Falta'"></span>
                                        </td>
                                        <td class="py-2">
                                            <span class="rounded-full px-2 py-1 text-xs"
                                                :class="item.contador ?
                                                    'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300' :
                                                    'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300'"
                                                x-text="item.contador || 'Falta'"></span>
                                        </td>
                                    </tr>
                                </template>
                                <tr x-show="!(clienteDetalle?.detalle_tareas || []).length">
                                    <td colspan="3" class="py-2 text-orange-700 dark:text-orange-300">Sin faltantes
                                        en
                                        tareas.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </a>
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
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-5">
            <div
                class="rounded-xl border border-sky-200 bg-gradient-to-br from-sky-50 to-white p-4 dark:border-sky-900/50 dark:from-sky-950/40 dark:to-gray-800">
                <p class="text-xs uppercase tracking-wide text-gray-500">1. Asignadas del mes</p>
                <p class="mt-2 text-2xl font-semibold text-sky-600">
                    {{ $dashboard['seguimiento_contadores']['kpis']['obligaciones_periodo'] }}</p>
            </div>
            <div
                class="rounded-xl border border-red-200 bg-gradient-to-br from-red-50 to-white p-4 dark:border-red-900/50 dark:from-red-950/40 dark:to-gray-800">
                <p class="text-xs uppercase tracking-wide text-gray-500">2. Atrasadas</p>
                <p class="mt-2 text-2xl font-semibold text-red-600">
                    {{ $dashboard['seguimiento_contadores']['kpis']['obligaciones_atrasadas'] }}</p>
            </div>
            <div
                class="rounded-xl border border-emerald-200 bg-gradient-to-br from-emerald-50 to-white p-4 dark:border-emerald-900/50 dark:from-emerald-950/40 dark:to-gray-800">
                <p class="text-xs uppercase tracking-wide text-gray-500">3. Terminadas en el mes</p>
                <p class="mt-2 text-2xl font-semibold text-emerald-600">
                    {{ $dashboard['seguimiento_contadores']['kpis']['obligaciones_cerradas'] }}</p>
            </div>
            <div
                class="rounded-xl border border-amber-200 bg-gradient-to-br from-amber-50 to-white p-4 dark:border-amber-900/50 dark:from-amber-950/40 dark:to-gray-800">
                <p class="text-xs uppercase tracking-wide text-gray-500">4. Faltantes en el mes</p>
                <p class="mt-2 text-2xl font-semibold text-amber-600">
                    {{ $dashboard['seguimiento_contadores']['kpis']['obligaciones_faltantes'] }}</p>
            </div>
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
                <h3 class="mb-3 text-base font-semibold text-stone-600 dark:text-white">Obligaciones urgentes</h3>
                <div class="space-y-2 max-h-72 overflow-y-auto pr-1">
                    @forelse($dashboard['seguimiento_contadores']['obligaciones_urgentes'] as $item)
                        <div @class([
                            'rounded-lg border p-3 text-sm',
                            'border-red-200 bg-red-50 dark:border-red-900/50 dark:bg-red-900/20' =>
                                $item['vencida'],
                            'border-amber-200 bg-amber-50 dark:border-amber-900/50 dark:bg-amber-900/20' => !$item[
                                'vencida'
                            ],
                        ])>
                            <p class="font-medium text-stone-700 dark:text-white">{{ $item['cliente'] }}</p>
                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">{{ $item['obligacion'] }}</p>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $item['contador'] }} |
                                {{ $item['fecha_vencimiento'] }} | {{ $item['estatus'] }}</p>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500 dark:text-gray-400">Sin obligaciones urgentes para este filtro.
                        </p>
                    @endforelse
                </div>
            </div>
        </div>
        </section>
    </section>

    <div
        x-show="contadorDetalle"
        x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
    >
        <div
            @click.outside="contadorDetalle = null"
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
                <div class="rounded-xl border border-sky-200 bg-gradient-to-br from-sky-50 to-white p-4 dark:border-sky-900/50 dark:from-sky-950/40 dark:to-gray-800">
                    <p class="text-xs uppercase tracking-wide text-gray-500">Pendientes</p>
                    <p class="mt-2 text-2xl font-semibold text-sky-600">{{ $dashboard['validaciones']['kpis']['pendientes'] }}</p>
                </div>
                <div class="rounded-xl border border-red-200 bg-gradient-to-br from-red-50 to-white p-4 dark:border-red-900/50 dark:from-red-950/40 dark:to-gray-800">
                    <p class="text-xs uppercase tracking-wide text-gray-500">Urgentes</p>
                    <p class="mt-2 text-2xl font-semibold text-red-600">{{ $dashboard['validaciones']['kpis']['urgentes'] }}</p>
                </div>
                <div class="rounded-xl border border-rose-200 bg-gradient-to-br from-rose-50 to-white p-4 dark:border-rose-900/50 dark:from-rose-950/40 dark:to-gray-800">
                    <p class="text-xs uppercase tracking-wide text-gray-500">Rechazadas</p>
                    <p class="mt-2 text-2xl font-semibold text-rose-600">{{ $dashboard['validaciones']['kpis']['rechazadas'] }}</p>
                </div>
                <div class="rounded-xl border border-amber-200 bg-gradient-to-br from-amber-50 to-white p-4 dark:border-amber-900/50 dark:from-amber-950/40 dark:to-gray-800">
                    <p class="text-xs uppercase tracking-wide text-gray-500">Rechazadas atendidas</p>
                    <p class="mt-2 text-2xl font-semibold text-amber-600">{{ $dashboard['validaciones']['kpis']['rechazadas_atendidas'] }}</p>
                </div>
            </div>
        </section>

        <section class="grid grid-cols-1 gap-6 xl:grid-cols-2 mt-4">
            <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
                <h3 class="mb-3 text-base font-semibold text-stone-600 dark:text-white">Bandeja de validacion</h3>
                <div class="space-y-2 max-h-80 overflow-y-auto pr-1">
                    @forelse($dashboard['validaciones']['bandeja'] as $item)
                        <div @class([
                            'rounded-lg border p-3 text-sm',
                            'border-red-200 bg-red-50 dark:border-red-900/50 dark:bg-red-900/20' => $item['urgente'],
                            'border-sky-200 bg-sky-50 dark:border-sky-900/50 dark:bg-sky-900/20' => ! $item['urgente'],
                        ])>
                            <p class="font-medium text-stone-700 dark:text-white">{{ $item['cliente'] }}</p>
                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">{{ $item['obligacion'] }}</p>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                {{ $item['contador'] }} | {{ $item['fecha_vencimiento'] }} | {{ $item['estatus'] }}
                            </p>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500 dark:text-gray-400">Sin pendientes de validacion.</p>
                    @endforelse
                </div>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
                <h3 class="mb-3 text-base font-semibold text-stone-600 dark:text-white">Rechazadas para seguimiento</h3>
                <div class="space-y-2 max-h-80 overflow-y-auto pr-1">
                    @forelse($dashboard['validaciones']['rechazadas_seguimiento'] as $item)
                        <div class="rounded-lg border border-rose-200 bg-rose-50 p-3 text-sm dark:border-rose-900/50 dark:bg-rose-900/20">
                            <p class="font-medium text-stone-700 dark:text-white">{{ $item['cliente'] }}</p>
                            <p class="mt-1 text-rose-700 dark:text-rose-300">{{ $item['obligacion'] }}</p>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                {{ $item['contador'] }} | {{ $item['fecha_vencimiento'] }} | {{ $item['estatus'] }}
                            </p>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500 dark:text-gray-400">Sin rechazadas para seguimiento.</p>
                    @endforelse
                </div>
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
                <div class="rounded-xl border border-cyan-200 bg-gradient-to-br from-cyan-50 to-white p-4 dark:border-cyan-900/50 dark:from-cyan-950/40 dark:to-gray-800">
                    <p class="text-xs uppercase tracking-wide text-gray-500">Listas para enviar</p>
                    <p class="mt-2 text-2xl font-semibold text-cyan-600">{{ $dashboard['envios']['kpis']['listos_para_enviar'] }}</p>
                </div>
                <div class="rounded-xl border border-sky-200 bg-gradient-to-br from-sky-50 to-white p-4 dark:border-sky-900/50 dark:from-sky-950/40 dark:to-gray-800">
                    <p class="text-xs uppercase tracking-wide text-gray-500">Enviadas</p>
                    <p class="mt-2 text-2xl font-semibold text-sky-600">{{ $dashboard['envios']['kpis']['enviadas'] }}</p>
                </div>
                <div class="rounded-xl border border-amber-200 bg-gradient-to-br from-amber-50 to-white p-4 dark:border-amber-900/50 dark:from-amber-950/40 dark:to-gray-800">
                    <p class="text-xs uppercase tracking-wide text-gray-500">Faltantes de envio</p>
                    <p class="mt-2 text-2xl font-semibold text-amber-600">{{ $dashboard['envios']['kpis']['faltantes_envio'] }}</p>
                </div>
                <div class="rounded-xl border border-orange-200 bg-gradient-to-br from-orange-50 to-white p-4 dark:border-orange-900/50 dark:from-orange-950/40 dark:to-gray-800">
                    <p class="text-xs uppercase tracking-wide text-gray-500">Respuestas pendientes</p>
                    <p class="mt-2 text-2xl font-semibold text-orange-600">{{ $dashboard['envios']['kpis']['respuestas_pendientes'] }}</p>
                </div>
                <div class="rounded-xl border border-emerald-200 bg-gradient-to-br from-emerald-50 to-white p-4 dark:border-emerald-900/50 dark:from-emerald-950/40 dark:to-gray-800">
                    <p class="text-xs uppercase tracking-wide text-gray-500">Respuestas revisadas</p>
                    <p class="mt-2 text-2xl font-semibold text-emerald-600">{{ $dashboard['envios']['kpis']['respuestas_revisadas'] }}</p>
                </div>
            </div>
        </section>

        <section class="grid grid-cols-1 gap-6 xl:grid-cols-2 mt-4">
            <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
                <h3 class="mb-3 text-base font-semibold text-stone-600 dark:text-white">Obligaciones pendientes de envio</h3>
                <div class="space-y-2 max-h-80 overflow-y-auto pr-1">
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
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
                <h3 class="mb-3 text-base font-semibold text-stone-600 dark:text-white">Respuestas del cliente pendientes</h3>
                <div class="space-y-2 max-h-80 overflow-y-auto pr-1">
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
            </div>
        </section>
    </section>
</div>
