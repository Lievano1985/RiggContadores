<div>
    <div class="bg-white dark:bg-gray-900 text-gray-900 dark:text-white p-6 space-y-6">

        {{-- Header --}}
        <div>
            <h2 class="text-2xl font-bold text-stone-600 dark:text-white">
                Dashboard – {{ $mesActual }}
            </h2>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Resumen ejecutivo de rendimiento mensual
            </p>
        </div>
    
        {{-- KPIs Tareas --}}
        <div class="bg-stone-100 dark:bg-gray-800 p-4 rounded-lg shadow">
            <h3 class="text-lg font-semibold text-stone-600 mb-4">
                📊 Tareas del mes
            </h3>
        
            @if($kpiTareas['sin_datos'] ?? false)
                <p class="text-gray-500 dark:text-gray-400">
                    Sin tareas asignadas este mes.
                </p>
            @else
                <div class="grid grid-cols-2 md:grid-cols-5 gap-4 text-center">
        
                    <div>
                        <p class="text-sm text-gray-500">Total</p>
                        <p class="text-xl font-bold">{{ $kpiTareas['total'] }}</p>
                    </div>
        
                    <div>
                        <p class="text-sm text-gray-500">Terminadas</p>
                        <p class="text-xl font-bold text-green-600">
                            {{ $kpiTareas['terminadas'] }}
                        </p>
                    </div>
        
                    <div>
                        <p class="text-sm text-gray-500">Pendientes</p>
                        <p class="text-xl font-bold text-yellow-600">
                            {{ $kpiTareas['pendientes'] }}
                        </p>
                    </div>
        
                    <div>
                        <p class="text-sm text-gray-500">Vencidas</p>
                        <p class="text-xl font-bold text-red-600">
                            {{ $kpiTareas['vencidas'] }}
                        </p>
                    </div>
        
                    <div>
                        <p class="text-sm text-gray-500">% Cumplimiento</p>
                        <p class="text-xl font-bold text-stone-600">
                            {{ $kpiTareas['porcentaje'] }}%
                        </p>
                    </div>
        
                </div>
            @endif
        </div>
    
        {{-- KPIs Obligaciones --}}
        <div class="bg-stone-100 dark:bg-gray-800 p-4 rounded-lg shadow">
            <h3 class="text-lg font-semibold text-stone-600 mb-4">
                📑 Obligaciones del mes
            </h3>
        
            @if($kpiObligaciones['sin_datos'] ?? false)
                <p class="text-gray-500 dark:text-gray-400">
                    Sin obligaciones asignadas este mes.
                </p>
            @else
                <div class="grid grid-cols-2 md:grid-cols-5 gap-4 text-center">
        
                    <div>
                        <p class="text-sm text-gray-500">Total</p>
                        <p class="text-xl font-bold">
                            {{ $kpiObligaciones['total'] }}
                        </p>
                    </div>
        
                    <div>
                        <p class="text-sm text-gray-500">Cumplidas</p>
                        <p class="text-xl font-bold text-green-600">
                            {{ $kpiObligaciones['cumplidas'] }}
                        </p>
                    </div>
        
                    <div>
                        <p class="text-sm text-gray-500">En proceso</p>
                        <p class="text-xl font-bold text-yellow-600">
                            {{ $kpiObligaciones['en_proceso'] }}
                        </p>
                    </div>
        
                    <div>
                        <p class="text-sm text-gray-500">Vencidas</p>
                        <p class="text-xl font-bold text-red-600">
                            {{ $kpiObligaciones['vencidas'] }}
                        </p>
                    </div>
        
                    <div>
                        <p class="text-sm text-gray-500">% Cumplimiento</p>
                        <p class="text-xl font-bold text-stone-600">
                            {{ $kpiObligaciones['porcentaje'] }}%
                        </p>
                    </div>
        
                </div>
            @endif
        </div>
        {{-- Resumen General --}}
        <div class="bg-stone-100 dark:bg-gray-800 p-4 rounded-lg shadow">
            <h3 class="text-lg font-semibold text-stone-600 mb-2">
                ⚠ Resumen General
            </h3>
    
            <div>
                Próximamente...
            </div>
        </div>
    
    </div></div>
