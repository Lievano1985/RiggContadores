<?php

namespace App\Services\Dashboard;

use App\Models\Cliente;
use App\Models\ObligacionClienteContador;
use App\Models\TareaAsignada;
use App\Models\User;
use Illuminate\Support\Carbon;

class OperationalDashboardBuilder
{
    public function build(User $user, string $roleLabel, array $filters = []): array
    {
        $hoy = now()->startOfDay();
        $limiteContrato = $hoy->copy()->addDays(30)->endOfDay();
        $ejercicio = (int) ($filters['ejercicio'] ?? $hoy->year);
        $mes = (int) ($filters['mes'] ?? $hoy->month);
        $contadorId = ! empty($filters['contador_id']) ? (int) $filters['contador_id'] : null;
        $inicioPeriodo = Carbon::create($ejercicio, $mes, 1)->startOfMonth();
        $finPeriodo = $inicioPeriodo->copy()->endOfMonth();
        $estatusCerrados = [
            'realizada',
            'declaracion_realizada',
            'enviada_cliente',
            'respuesta_cliente',
            'respuesta_revisada',
            'finalizado',
        ];
        $estatusAbiertos = [
            'asignada',
            'en_progreso',
            'rechazada',
            'reabierta',
        ];
        $estatusExcluidosValidacion = [
            'enviada_cliente',
            'respuesta_cliente',
            'respuesta_revisada',
        ];

        $clientesBase = Cliente::query()
            ->when($user->despacho_id, fn ($q) => $q->where('despacho_id', $user->despacho_id));

        $obligacionesBase = ObligacionClienteContador::query()
            ->where('is_activa', true)
            ->whereHas('cliente', function ($q) use ($user) {
                if ($user->despacho_id) {
                    $q->where('despacho_id', $user->despacho_id);
                }
            });

        $tareasBase = TareaAsignada::query()
            ->where(function ($q) {
                $q->whereNull('estatus')->orWhere('estatus', '!=', 'cancelada');
            })
            ->whereHas('cliente', function ($q) use ($user) {
                if ($user->despacho_id) {
                    $q->where('despacho_id', $user->despacho_id);
                }
            });

        $clientesActivosBase = (clone $clientesBase)->where('activo', true);

        $clientesConMetricas = (clone $clientesActivosBase)
            ->with([
                'obligacionesAsignadas' => fn ($q) => $q
                    ->where('is_activa', true)
                    ->with(['obligacion:id,nombre', 'contador:id,name']),
                'tareasAsignadas' => fn ($q) => $q
                    ->where(function ($w) {
                        $w->whereNull('estatus')->orWhere('estatus', '!=', 'cancelada');
                    })
                    ->with(['tareaCatalogo:id,nombre', 'contador:id,name']),
            ])
            ->withCount([
                'obligacionesAsignadas as obligaciones_activas_count' => fn ($q) => $q->where('is_activa', true),
                'obligacionesAsignadas as obligaciones_sin_contador_count' => fn ($q) => $q->where('is_activa', true)->whereNull('contador_id'),
                'obligacionesAsignadas as obligaciones_sin_carpeta_count' => fn ($q) => $q->where('is_activa', true)->whereNull('carpeta_drive_id'),
                'tareasAsignadas as tareas_activas_count' => fn ($q) => $q->where(function ($w) {
                    $w->whereNull('estatus')->orWhere('estatus', '!=', 'cancelada');
                }),
                'tareasAsignadas as tareas_sin_contador_count' => fn ($q) => $q->where(function ($w) {
                    $w->whereNull('estatus')->orWhere('estatus', '!=', 'cancelada');
                })->whereNull('contador_id'),
                'tareasAsignadas as tareas_sin_carpeta_count' => fn ($q) => $q->where(function ($w) {
                    $w->whereNull('estatus')->orWhere('estatus', '!=', 'cancelada');
                })->whereNull('carpeta_drive_id'),
            ])
            ->orderBy('nombre')
            ->get()
            ->map(function (Cliente $cliente) use ($hoy) {
                $contratoVigente = $cliente->vigencia && $cliente->vigencia->copy()->startOfDay()->gte($hoy);
                $sinFaltantes = $cliente->obligaciones_sin_contador_count === 0
                    && $cliente->obligaciones_sin_carpeta_count === 0
                    && $cliente->tareas_sin_contador_count === 0
                    && $cliente->tareas_sin_carpeta_count === 0;

                $clienteCompleto = $contratoVigente
                    && $sinFaltantes
                    && ($cliente->obligaciones_activas_count + $cliente->tareas_activas_count) > 0;

                $detalleObligaciones = $cliente->obligacionesAsignadas
                    ->filter(fn ($item) => empty($item->contador_id) || empty($item->carpeta_drive_id))
                    ->map(fn ($item) => [
                        'cliente_id' => $cliente->id,
                        'expediente_url' => route('clientes.expediente.show', $cliente->id),
                        'obligaciones_url' => route('clientes.expediente.show', ['cliente' => $cliente->id, 'tab' => 'obligaciones']) . '#obligaciones',
                        'nombre' => $item->obligacion->nombre ?? 'Sin obligacion',
                        'contador' => $item->contador ? $item->contador->name : null,
                        'carpeta' => ! empty($item->carpeta_drive_id),
                    ])
                    ->values()
                    ->all();

                $detalleTareas = $cliente->tareasAsignadas
                    ->filter(fn ($item) => empty($item->contador_id) || empty($item->carpeta_drive_id))
                    ->map(fn ($item) => [
                        'cliente_id' => $cliente->id,
                        'expediente_url' => route('clientes.expediente.show', $cliente->id),
                        'tareas_url' => route('clientes.expediente.show', ['cliente' => $cliente->id, 'tab' => 'tareas']) . '#tareas',
                        'nombre' => $item->tareaCatalogo->nombre ?? 'Sin tarea',
                        'contador' => $item->contador ? $item->contador->name : null,
                        'carpeta' => ! empty($item->carpeta_drive_id),
                    ])
                    ->values()
                    ->all();

                return [
                    'id' => $cliente->id,
                    'nombre' => $cliente->nombre ?: ($cliente->razon_social ?: 'Sin nombre'),
                    'expediente_url' => route('clientes.expediente.show', $cliente->id),
                    'obligaciones_url' => route('clientes.expediente.show', ['cliente' => $cliente->id, 'tab' => 'obligaciones']) . '#obligaciones',
                    'tareas_url' => route('clientes.expediente.show', ['cliente' => $cliente->id, 'tab' => 'tareas']) . '#tareas',
                    'vigencia' => optional($cliente->vigencia)?->format('Y-m-d'),
                    'contrato_vigente' => $contratoVigente,
                    'obligaciones_activas_count' => (int) $cliente->obligaciones_activas_count,
                    'obligaciones_sin_contador_count' => (int) $cliente->obligaciones_sin_contador_count,
                    'obligaciones_sin_carpeta_count' => (int) $cliente->obligaciones_sin_carpeta_count,
                    'tareas_activas_count' => (int) $cliente->tareas_activas_count,
                    'tareas_sin_contador_count' => (int) $cliente->tareas_sin_contador_count,
                    'tareas_sin_carpeta_count' => (int) $cliente->tareas_sin_carpeta_count,
                    'cliente_completo' => $clienteCompleto,
                    'detalle_obligaciones' => $detalleObligaciones,
                    'detalle_tareas' => $detalleTareas,
                ];
            });

        $clientesCompletos = $clientesConMetricas->where('cliente_completo', true)->values();
        $clientesIncompletos = $clientesConMetricas->where('cliente_completo', false)->values();
        $porcentajeCobertura = $clientesConMetricas->count() > 0
            ? (int) round(($clientesCompletos->count() / $clientesConMetricas->count()) * 100)
            : 0;

        $contratosPorVencer = (clone $clientesActivosBase)
            ->whereNotNull('vigencia')
            ->whereDate('vigencia', '<=', $limiteContrato->toDateString())
            ->orderBy('vigencia')
            ->limit(8)
            ->get()
            ->map(function (Cliente $cliente) use ($hoy) {
                $vigencia = $cliente->vigencia ? Carbon::parse($cliente->vigencia)->startOfDay() : null;
                $vencido = $vigencia ? $vigencia->lt($hoy) : false;

                return [
                    'id' => $cliente->id,
                    'nombre' => $cliente->nombre ?: ($cliente->razon_social ?: 'Sin nombre'),
                    'vigencia' => optional($cliente->vigencia)?->format('Y-m-d'),
                    'dias' => $vigencia ? $hoy->diffInDays($vigencia, false) : null,
                    'vencido' => $vencido,
                ];
            })
            ->all();

        $contadores = User::query()
            ->when($user->despacho_id, fn ($q) => $q->where('despacho_id', $user->despacho_id))
            ->whereHas('roles', fn ($q) => $q->whereIn('name', ['contador', 'supervisor']))
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (User $contador) => [
                'id' => $contador->id,
                'nombre' => $contador->name,
            ])
            ->values();

        $obligacionesSeguimientoBase = (clone $obligacionesBase)
            ->whereNotNull('contador_id')
            ->whereNotNull('fecha_vencimiento')
            ->with(['cliente:id,nombre,razon_social', 'obligacion:id,nombre', 'contador:id,name']);

        $obligacionesPeriodoBase = (clone $obligacionesSeguimientoBase)
            ->whereYear('fecha_vencimiento', $ejercicio)
            ->whereMonth('fecha_vencimiento', $mes)
            ->when($contadorId, fn ($q) => $q->where('contador_id', $contadorId));

        $obligacionesPeriodoGlobalBase = (clone $obligacionesSeguimientoBase)
            ->whereYear('fecha_vencimiento', $ejercicio)
            ->whereMonth('fecha_vencimiento', $mes);

        $obligacionesAtrasadasBase = (clone $obligacionesSeguimientoBase)
            ->whereDate('fecha_vencimiento', '<', $inicioPeriodo->toDateString())
            ->whereIn('estatus', $estatusAbiertos)
            ->when($contadorId, fn ($q) => $q->where('contador_id', $contadorId));

        $obligacionesCerradasBase = (clone $obligacionesPeriodoBase)
            ->whereIn('estatus', $estatusCerrados);

        $obligacionesCerradasGlobalBase = (clone $obligacionesPeriodoGlobalBase)
            ->whereIn('estatus', $estatusCerrados);

        $obligacionesFaltantesBase = (clone $obligacionesPeriodoBase)
            ->whereIn('estatus', $estatusAbiertos);

        $cargaPorContador = $contadores->map(function (array $contador) use (
            $obligacionesSeguimientoBase,
            $inicioPeriodo,
            $ejercicio,
            $mes,
            $estatusAbiertos,
            $estatusCerrados
        ) {
            $periodo = (clone $obligacionesSeguimientoBase)
                ->where('contador_id', $contador['id'])
                ->whereYear('fecha_vencimiento', $ejercicio)
                ->whereMonth('fecha_vencimiento', $mes);

            $totalPeriodo = (clone $periodo)->count();
            $cerradas = (clone $periodo)->whereIn('estatus', $estatusCerrados)->count();
            $faltantes = (clone $periodo)->whereIn('estatus', $estatusAbiertos)->count();
            $atrasadas = (clone $obligacionesSeguimientoBase)
                ->where('contador_id', $contador['id'])
                ->whereDate('fecha_vencimiento', '<', $inicioPeriodo->toDateString())
                ->whereIn('estatus', $estatusAbiertos)
                ->count();

            $detalleAtrasadas = (clone $obligacionesSeguimientoBase)
                ->where('contador_id', $contador['id'])
                ->whereDate('fecha_vencimiento', '<', $inicioPeriodo->toDateString())
                ->whereIn('estatus', $estatusAbiertos)
                ->orderBy('fecha_vencimiento')
                ->limit(12)
                ->get()
                ->map(fn (ObligacionClienteContador $obligacion) => [
                    'cliente' => $obligacion->cliente?->nombre ?: ($obligacion->cliente?->razon_social ?: 'Sin cliente'),
                    'obligacion' => $obligacion->obligacion?->nombre ?: 'Sin obligacion',
                    'fecha_vencimiento' => optional($obligacion->fecha_vencimiento)?->format('Y-m-d'),
                    'estatus' => $obligacion->estatus ?: 'Sin estatus',
                ])
                ->values()
                ->all();

            $detalleFaltantes = (clone $obligacionesSeguimientoBase)
                ->where('contador_id', $contador['id'])
                ->whereYear('fecha_vencimiento', $ejercicio)
                ->whereMonth('fecha_vencimiento', $mes)
                ->whereIn('estatus', $estatusAbiertos)
                ->orderBy('fecha_vencimiento')
                ->limit(12)
                ->get()
                ->map(fn (ObligacionClienteContador $obligacion) => [
                    'cliente' => $obligacion->cliente?->nombre ?: ($obligacion->cliente?->razon_social ?: 'Sin cliente'),
                    'obligacion' => $obligacion->obligacion?->nombre ?: 'Sin obligacion',
                    'fecha_vencimiento' => optional($obligacion->fecha_vencimiento)?->format('Y-m-d'),
                    'estatus' => $obligacion->estatus ?: 'Sin estatus',
                ])
                ->values()
                ->all();

            return [
                'id' => $contador['id'],
                'nombre' => $contador['nombre'],
                'periodo' => $totalPeriodo,
                'atrasadas' => $atrasadas,
                'cerradas' => $cerradas,
                'faltantes' => $faltantes,
                'cumplimiento' => $totalPeriodo > 0 ? (int) round(($cerradas / $totalPeriodo) * 100) : 0,
                'detalle_atrasadas' => $detalleAtrasadas,
                'detalle_faltantes' => $detalleFaltantes,
            ];
        })->sortBy([
            ['atrasadas', 'desc'],
            ['faltantes', 'desc'],
            ['nombre', 'asc'],
        ])->values();

        $obligacionesUrgentes = (clone $obligacionesSeguimientoBase)
            ->whereIn('estatus', $estatusAbiertos)
            ->whereDate('fecha_vencimiento', '<=', $finPeriodo->toDateString())
            ->when($contadorId, fn ($q) => $q->where('contador_id', $contadorId))
            ->orderBy('fecha_vencimiento')
            ->limit(8)
            ->get()
            ->map(function (ObligacionClienteContador $obligacion) use ($hoy) {
                $fechaVencimiento = $obligacion->fecha_vencimiento
                    ? Carbon::parse($obligacion->fecha_vencimiento)->startOfDay()
                    : null;

                return [
                    'cliente' => $obligacion->cliente?->nombre ?: ($obligacion->cliente?->razon_social ?: 'Sin cliente'),
                    'obligacion' => $obligacion->obligacion?->nombre ?: 'Sin obligacion',
                    'contador' => $obligacion->contador?->name ?: 'Sin contador',
                    'fecha_vencimiento' => $fechaVencimiento?->format('Y-m-d'),
                    'estatus' => $obligacion->estatus ?: 'Sin estatus',
                    'vencida' => $fechaVencimiento ? $fechaVencimiento->lt($hoy) : false,
                ];
            })
            ->values()
            ->all();

        $contadorSeleccionado = null;

        if ($contadorId) {
            $contadorSeleccionado = $cargaPorContador->firstWhere('id', $contadorId);
        }

        $validacionesBase = (clone $obligacionesBase)
            ->whereNotIn('estatus', $estatusExcluidosValidacion)
            ->whereYear('fecha_vencimiento', $ejercicio)
            ->whereMonth('fecha_vencimiento', $mes)
            ->when($contadorId, fn ($q) => $q->where('contador_id', $contadorId))
            ->with(['cliente:id,nombre,razon_social', 'obligacion:id,nombre', 'contador:id,name']);

        $validacionesPendientesBase = (clone $validacionesBase)
            ->where('estatus', 'realizada');

        $validacionesUrgentesBase = (clone $validacionesPendientesBase)
            ->whereDate('fecha_vencimiento', '<=', $hoy->toDateString());

        $validacionesRechazadasBase = (clone $validacionesBase)
            ->where('estatus', 'rechazada');

        $validacionesAtendidasBase = (clone $validacionesBase)
            ->whereNotNull('comentario')
            ->where('estatus', '!=', 'rechazada')
            ->whereIn('estatus', ['en_progreso', 'realizada', 'finalizado']);

        $bandejaValidacion = (clone $validacionesPendientesBase)
            ->orderBy('fecha_vencimiento')
            ->limit(10)
            ->get()
            ->map(function (ObligacionClienteContador $obligacion) use ($hoy) {
                $fechaVencimiento = $obligacion->fecha_vencimiento
                    ? Carbon::parse($obligacion->fecha_vencimiento)->startOfDay()
                    : null;

                return [
                    'cliente' => $obligacion->cliente?->nombre ?: ($obligacion->cliente?->razon_social ?: 'Sin cliente'),
                    'obligacion' => $obligacion->obligacion?->nombre ?: 'Sin obligacion',
                    'contador' => $obligacion->contador?->name ?: 'Sin contador',
                    'fecha_vencimiento' => $fechaVencimiento?->format('Y-m-d'),
                    'estatus' => $obligacion->estatus ?: 'Sin estatus',
                    'urgente' => $fechaVencimiento ? $fechaVencimiento->lte($hoy) : false,
                ];
            })
            ->values()
            ->all();

        $rechazadasSeguimiento = (clone $validacionesRechazadasBase)
            ->orderByDesc('updated_at')
            ->limit(10)
            ->get()
            ->map(fn (ObligacionClienteContador $obligacion) => [
                'cliente' => $obligacion->cliente?->nombre ?: ($obligacion->cliente?->razon_social ?: 'Sin cliente'),
                'obligacion' => $obligacion->obligacion?->nombre ?: 'Sin obligacion',
                'contador' => $obligacion->contador?->name ?: 'Sin contador',
                'fecha_vencimiento' => optional($obligacion->fecha_vencimiento)?->format('Y-m-d'),
                'estatus' => $obligacion->estatus ?: 'Sin estatus',
            ])
            ->values()
            ->all();

        $enviosBase = (clone $obligacionesBase)
            ->whereYear('fecha_vencimiento', $ejercicio)
            ->whereMonth('fecha_vencimiento', $mes)
            ->when($contadorId, fn ($q) => $q->where('contador_id', $contadorId))
            ->with(['cliente:id,nombre,razon_social', 'obligacion:id,nombre', 'contador:id,name']);

        $enviosListosBase = (clone $enviosBase)
            ->where('estatus', 'finalizado');

        $enviosRealizadosBase = (clone $enviosBase)
            ->where('estatus', 'enviada_cliente');

        $respuestasPendientesBase = (clone $enviosBase)
            ->where('estatus', 'respuesta_cliente');

        $respuestasRevisadasBase = (clone $enviosBase)
            ->where('estatus', 'respuesta_revisada');

        $pendientesEnvio = (clone $enviosListosBase)
            ->orderBy('fecha_vencimiento')
            ->limit(10)
            ->get()
            ->map(fn (ObligacionClienteContador $obligacion) => [
                'cliente' => $obligacion->cliente?->nombre ?: ($obligacion->cliente?->razon_social ?: 'Sin cliente'),
                'obligacion' => $obligacion->obligacion?->nombre ?: 'Sin obligacion',
                'contador' => $obligacion->contador?->name ?: 'Sin contador',
                'fecha_vencimiento' => optional($obligacion->fecha_vencimiento)?->format('Y-m-d'),
                'estatus' => $obligacion->estatus ?: 'Sin estatus',
            ])
            ->values()
            ->all();

        $respuestasPendientes = (clone $respuestasPendientesBase)
            ->orderByDesc('updated_at')
            ->limit(10)
            ->get()
            ->map(fn (ObligacionClienteContador $obligacion) => [
                'cliente' => $obligacion->cliente?->nombre ?: ($obligacion->cliente?->razon_social ?: 'Sin cliente'),
                'obligacion' => $obligacion->obligacion?->nombre ?: 'Sin obligacion',
                'contador' => $obligacion->contador?->name ?: 'Sin contador',
                'fecha_vencimiento' => optional($obligacion->fecha_vencimiento)?->format('Y-m-d'),
                'estatus' => $obligacion->estatus ?: 'Sin estatus',
            ])
            ->values()
            ->all();

        return [
            'header' => [
                'role_label' => $roleLabel,
                'user_name' => $user->name,
                'despacho' => $user->despacho?->nombre ?: 'Sin despacho',
                'fecha' => now()->translatedFormat('d \\d\\e F Y'),
                'porcentaje_cobertura' => $porcentajeCobertura,
            ],
            'filtros' => [
                'ejercicio' => (string) $ejercicio,
                'mes' => str_pad((string) $mes, 2, '0', STR_PAD_LEFT),
                'contador_id' => $contadorId ? (string) $contadorId : '',
                'contadores' => $contadores->all(),
            ],
            'kpis' => [
                'clientes_activos' => (clone $clientesBase)->where('activo', true)->count(),
                'clientes_inactivos' => (clone $clientesBase)->where('activo', false)->count(),
                'contratos_vigentes' => (clone $clientesActivosBase)->whereNotNull('vigencia')->whereDate('vigencia', '>=', $hoy->toDateString())->count(),
                'contratos_por_vencer' => (clone $clientesActivosBase)->whereNotNull('vigencia')->whereBetween('vigencia', [$hoy->toDateString(), $limiteContrato->toDateString()])->count(),
                'contratos_vencidos' => (clone $clientesActivosBase)->where(function ($q) use ($hoy) {
                    $q->whereNull('vigencia')->orWhereDate('vigencia', '<', $hoy->toDateString());
                })->count(),
                'clientes_completos' => $clientesCompletos->count(),
                'clientes_incompletos' => $clientesIncompletos->count(),
                'obligaciones_sin_contador' => (clone $obligacionesBase)->whereNull('contador_id')->count(),
                'obligaciones_sin_carpeta' => (clone $obligacionesBase)->whereNull('carpeta_drive_id')->count(),
                'tareas_sin_contador' => (clone $tareasBase)->whereNull('contador_id')->count(),
                'tareas_sin_carpeta' => (clone $tareasBase)->whereNull('carpeta_drive_id')->count(),
            ],
            'contratos_por_vencer' => $contratosPorVencer,
            'clientes_incompletos' => $clientesIncompletos->take(8)->values()->all(),
            'resumen_despacho' => [
                'clientes_evaluados' => $clientesConMetricas->count(),
                'clientes_completos' => $clientesCompletos->count(),
                'clientes_incompletos' => $clientesIncompletos->count(),
            ],
            'seguimiento_contadores' => [
                'resumen_global' => [
                    'obligaciones_periodo' => (clone $obligacionesPeriodoGlobalBase)->count(),
                    'obligaciones_cerradas' => (clone $obligacionesCerradasGlobalBase)->count(),
                    'cumplimiento' => (clone $obligacionesPeriodoGlobalBase)->count() > 0
                        ? (int) round(((clone $obligacionesCerradasGlobalBase)->count() / (clone $obligacionesPeriodoGlobalBase)->count()) * 100)
                        : 0,
                ],
                'kpis' => [
                    'contadores_activos' => $contadores->count(),
                    'obligaciones_periodo' => (clone $obligacionesPeriodoBase)->count(),
                    'obligaciones_atrasadas' => (clone $obligacionesAtrasadasBase)->count(),
                    'obligaciones_cerradas' => (clone $obligacionesCerradasBase)->count(),
                    'obligaciones_faltantes' => (clone $obligacionesFaltantesBase)->count(),
                    'cumplimiento' => (clone $obligacionesPeriodoBase)->count() > 0
                        ? (int) round(((clone $obligacionesCerradasBase)->count() / (clone $obligacionesPeriodoBase)->count()) * 100)
                        : 0,
                ],
                'carga_por_contador' => $cargaPorContador->all(),
                'contadores_con_atraso' => $cargaPorContador->take(5)->all(),
                'contador_seleccionado' => $contadorSeleccionado,
                'obligaciones_urgentes' => $obligacionesUrgentes,
            ],
            'validaciones' => [
                'kpis' => [
                    'pendientes' => (clone $validacionesPendientesBase)->count(),
                    'urgentes' => (clone $validacionesUrgentesBase)->count(),
                    'rechazadas' => (clone $validacionesRechazadasBase)->count(),
                    'rechazadas_atendidas' => (clone $validacionesAtendidasBase)->count(),
                ],
                'bandeja' => $bandejaValidacion,
                'rechazadas_seguimiento' => $rechazadasSeguimiento,
            ],
            'envios' => [
                'kpis' => [
                    'listos_para_enviar' => (clone $enviosListosBase)->count(),
                    'enviadas' => (clone $enviosRealizadosBase)->count(),
                    'faltantes_envio' => (clone $enviosListosBase)->count(),
                    'respuestas_pendientes' => (clone $respuestasPendientesBase)->count(),
                    'respuestas_revisadas' => (clone $respuestasRevisadasBase)->count(),
                ],
                'pendientes_envio' => $pendientesEnvio,
                'respuestas_pendientes_lista' => $respuestasPendientes,
            ],
        ];
    }
}
