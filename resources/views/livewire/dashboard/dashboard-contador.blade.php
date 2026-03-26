<div class="space-y-6">
    <section class="rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-700 dark:bg-gray-900">
        <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
            <div>
                <h2 class="text-2xl font-bold text-stone-600 dark:text-white">Dashboard de Contador</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Resumen operativo diario. Fecha: {{ $fechaHoy }}
                </p>
            </div>

            <div class="w-full rounded-xl border border-amber-200 bg-gradient-to-r from-amber-50 to-white p-4 dark:border-amber-900/50 dark:from-amber-950/40 dark:to-gray-900 md:max-w-sm">
                <div class="flex items-end justify-between gap-4">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-amber-700 dark:text-amber-300">
                            Obligaciones cerradas del mes
                        </p>
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">
                            {{ $kpis['obligaciones_terminadas_mes'] ?? 0 }} de {{ $kpis['obligaciones_asignadas_mes'] ?? 0 }}
                        </p>
                    </div>
                    <p class="text-4xl font-bold leading-none text-amber-600 dark:text-amber-400">
                        {{ $porcentajeObligacionesCerradas }}%
                    </p>
                </div>

                <div class="mt-3 h-3 w-full overflow-hidden rounded-full bg-amber-100 dark:bg-gray-800">
                    <div
                        class="h-full rounded-full bg-amber-600 transition-all duration-500"
                        style="width: {{ $porcentajeObligacionesCerradas }}%;"
                    ></div>
                </div>
            </div>
        </div>
    </section>

    <section class="space-y-4">
        <h3 class="text-base font-semibold text-stone-600 dark:text-white">Obligaciones</h3>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <a href="{{ route('contadores.asignaciones.index', ['tab' => 'obligaciones', 'ov' => 'asignadas_mes']) }}" class="block rounded-xl border border-stone-200 bg-gradient-to-br from-stone-50 to-white p-4 transition hover:border-amber-600 dark:border-stone-700 dark:from-stone-900 dark:to-gray-800">
                <p class="text-xs uppercase tracking-wide text-gray-500">1. Asignadas del mes</p>
                <p class="mt-2 text-2xl font-semibold text-stone-600 dark:text-white">{{ $kpis['obligaciones_asignadas_mes'] ?? 0 }}</p>
            </a>
            <a href="{{ route('contadores.asignaciones.index', ['tab' => 'obligaciones', 'ov' => 'atrasadas']) }}" class="block rounded-xl border border-red-200 bg-gradient-to-br from-red-50 to-white p-4 transition hover:border-amber-600 dark:border-red-900/50 dark:from-red-950/40 dark:to-gray-800">
                <p class="text-xs uppercase tracking-wide text-gray-500">2. Atrasadas</p>
                <p class="mt-2 text-2xl font-semibold text-red-600">{{ $kpis['obligaciones_atrasadas'] ?? 0 }}</p>
            </a>
            <a href="{{ route('contadores.asignaciones.index', ['tab' => 'obligaciones', 'ov' => 'terminadas_mes']) }}" class="block rounded-xl border border-emerald-200 bg-gradient-to-br from-emerald-50 to-white p-4 transition hover:border-amber-600 dark:border-emerald-900/50 dark:from-emerald-950/40 dark:to-gray-800">
                <p class="text-xs uppercase tracking-wide text-gray-500">3. Terminadas en el mes</p>
                <p class="mt-2 text-2xl font-semibold text-emerald-600">{{ $kpis['obligaciones_terminadas_mes'] ?? 0 }}</p>
            </a>
            <a href="{{ route('contadores.asignaciones.index', ['tab' => 'obligaciones', 'ov' => 'faltantes_mes']) }}" class="block rounded-xl border border-amber-200 bg-gradient-to-br from-amber-50 to-white p-4 transition hover:border-amber-600 dark:border-amber-900/50 dark:from-amber-950/40 dark:to-gray-800">
                <p class="text-xs uppercase tracking-wide text-gray-500">4. Faltantes en el mes</p>
                <p class="mt-2 text-2xl font-semibold text-amber-600">{{ $kpis['obligaciones_faltantes_mes'] ?? 0 }}</p>
            </a>
        </div>
    </section>

    <section class="space-y-4">
        <h3 class="text-base font-semibold text-stone-600 dark:text-white">Tareas</h3>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <a href="{{ route('contadores.asignaciones.index', ['tab' => 'tareas', 'tv' => 'asignadas_mes']) }}" class="block rounded-xl border border-sky-200 bg-gradient-to-br from-sky-50 to-white p-4 transition hover:border-amber-600 dark:border-sky-900/50 dark:from-sky-950/40 dark:to-gray-800">
                <p class="text-xs uppercase tracking-wide text-gray-500">1. Asignadas del mes</p>
                <p class="mt-2 text-2xl font-semibold text-stone-600 dark:text-white">{{ $kpis['tareas_asignadas_mes'] ?? 0 }}</p>
            </a>
            <a href="{{ route('contadores.asignaciones.index', ['tab' => 'tareas', 'tv' => 'atrasadas']) }}" class="block rounded-xl border border-rose-200 bg-gradient-to-br from-rose-50 to-white p-4 transition hover:border-amber-600 dark:border-rose-900/50 dark:from-rose-950/40 dark:to-gray-800">
                <p class="text-xs uppercase tracking-wide text-gray-500">2. Atrasadas</p>
                <p class="mt-2 text-2xl font-semibold text-red-600">{{ $kpis['tareas_atrasadas'] ?? 0 }}</p>
            </a>
            <a href="{{ route('contadores.asignaciones.index', ['tab' => 'tareas', 'tv' => 'terminadas_mes']) }}" class="block rounded-xl border border-teal-200 bg-gradient-to-br from-teal-50 to-white p-4 transition hover:border-amber-600 dark:border-teal-900/50 dark:from-teal-950/40 dark:to-gray-800">
                <p class="text-xs uppercase tracking-wide text-gray-500">3. Terminadas en el mes</p>
                <p class="mt-2 text-2xl font-semibold text-emerald-600">{{ $kpis['tareas_terminadas_mes'] ?? 0 }}</p>
            </a>
            <a href="{{ route('contadores.asignaciones.index', ['tab' => 'tareas', 'tv' => 'faltantes_mes']) }}" class="block rounded-xl border border-orange-200 bg-gradient-to-br from-orange-50 to-white p-4 transition hover:border-amber-600 dark:border-orange-900/50 dark:from-orange-950/40 dark:to-gray-800">
                <p class="text-xs uppercase tracking-wide text-gray-500">4. Faltantes en el mes</p>
                <p class="mt-2 text-2xl font-semibold text-amber-600">{{ $kpis['tareas_faltantes_mes'] ?? 0 }}</p>
            </a>
        </div>
    </section>

    <section class="grid grid-cols-1 gap-6 xl:grid-cols-2">
        <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
            <h3 class="mb-3 text-base font-semibold text-stone-600 dark:text-white">Mis tareas urgentes</h3>
            <div class="space-y-2 max-h-56 overflow-y-auto pr-1">
                @forelse($tareasUrgentes as $tarea)
                    <div class="min-h-[4.5rem] rounded-lg border border-gray-200 p-3 text-sm dark:border-gray-700">
                        <p class="font-medium text-gray-800 dark:text-gray-100">{{ $tarea['nombre'] }}</p>
                        <p class="text-gray-500 dark:text-gray-400">
                            {{ $tarea['cliente'] }} | Limite: {{ $tarea['fecha_limite'] }}
                        </p>
                    </div>
                @empty
                    <p class="text-sm text-gray-500 dark:text-gray-400">Sin tareas urgentes.</p>
                @endforelse
            </div>
        </div>

        <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
            <h3 class="mb-3 text-base font-semibold text-stone-600 dark:text-white">Mis obligaciones urgentes</h3>
            <div class="space-y-2 max-h-56 overflow-y-auto pr-1">
                @forelse($obligacionesUrgentes as $obligacion)
                    <div class="min-h-[4.5rem] rounded-lg border border-gray-200 p-3 text-sm dark:border-gray-700">
                        <p class="font-medium text-gray-800 dark:text-gray-100">{{ $obligacion['nombre'] }}</p>
                        <p class="text-gray-500 dark:text-gray-400">
                            {{ $obligacion['cliente'] }} | Vence: {{ $obligacion['fecha_vencimiento'] }}
                        </p>
                    </div>
                @empty
                    <p class="text-sm text-gray-500 dark:text-gray-400">Sin obligaciones urgentes.</p>
                @endforelse
            </div>
        </div>
    </section>

    <section class="grid grid-cols-1 gap-6 xl:grid-cols-2">
        <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
            <h3 class="mb-3 text-base font-semibold text-stone-600 dark:text-white">Rechazadas para corregir</h3>
            <div class="space-y-2">
                @forelse($rechazadas as $item)
                    <div class="rounded-lg border border-rose-200 bg-rose-50 p-3 text-sm dark:border-rose-900/60 dark:bg-rose-900/20">
                        <p class="font-medium text-rose-700 dark:text-rose-300">{{ $item['tipo'] }}: {{ $item['nombre'] }}</p>
                        <p class="text-rose-600/90 dark:text-rose-200/90">{{ $item['cliente'] }} | {{ $item['fecha'] }}</p>
                        @if(!empty($item['comentario']))
                            <p class="mt-1 text-xs text-rose-700 dark:text-rose-300">Motivo: {{ $item['comentario'] }}</p>
                        @endif
                    </div>
                @empty
                    <p class="text-sm text-gray-500 dark:text-gray-400">No tienes elementos rechazados.</p>
                @endforelse
            </div>
        </div>
    </section>

    <section class="rounded-xl border border-dashed border-gray-300 bg-white p-4 dark:border-gray-700 dark:bg-gray-900">
        <h3 class="text-sm font-semibold text-gray-600 dark:text-gray-300">Espacio reservado para graficas</h3>
        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
            Se deja listo este bloque para incorporar visualizaciones en fases posteriores.
        </p>
    </section>
</div>
