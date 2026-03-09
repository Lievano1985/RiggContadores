<?php

/**
 * Componente: DashboardContador
 * Autor: Luis Liévano - JL3 Digital
 *
 * Función:
 * - Mostrar resumen ejecutivo del contador.
 * - KPIs de tareas y obligaciones del mes actual.
 * - Resumen general de pendientes.
 */

namespace App\Livewire\Dashboard;

use Livewire\Component;
use Illuminate\Support\Carbon;
use App\Models\TareaAsignada;
use Illuminate\Support\Facades\Auth;
use App\Models\ObligacionClienteContador;
class DashboardContador extends Component
{
    public string $mesActual;

    public array $kpiTareas = [];
    public array $kpiObligaciones = [];
    public array $resumenGeneral = [];

    public function mount()
    {
        $this->mesActual = Carbon::now()->translatedFormat('F Y');

        $this->cargarTareasMes();
        $this->cargarObligacionesMes();
        $this->cargarResumenGeneral();
    }

    private function cargarTareasMes()
    {
        $contadorId = Auth::id();
    
        $mes = now()->month;
        $anio = now()->year;
    
        $query = TareaAsignada::where('contador_id', $contadorId)
            ->whereMonth('fecha_limite', $mes)
            ->whereYear('fecha_limite', $anio);
    
        $total = (clone $query)->count();
    
        if ($total === 0) {
            $this->kpiTareas = [
                'sin_datos' => true,
            ];
            return;
        }
    
        $terminadas = (clone $query)
            ->whereIn('estatus', ['terminada', 'revisada'])
            ->count();
    
        $pendientes = (clone $query)
            ->whereIn('estatus', ['asignada', 'iniciando', 'en_progreso', 'rechazada'])
            ->count();
    
        $vencidas = (clone $query)
            ->whereDate('fecha_limite', '<', now())
            ->whereNotIn('estatus', ['terminada', 'revisada'])
            ->count();
    
        $porcentaje = $total > 0
            ? round(($terminadas / $total) * 100, 2)
            : null;
    
        $this->kpiTareas = [
            'sin_datos' => false,
            'total' => $total,
            'terminadas' => $terminadas,
            'pendientes' => $pendientes,
            'vencidas' => $vencidas,
            'porcentaje' => $porcentaje,
        ];
    }

    private function cargarObligacionesMes()
{
    $contadorId = Auth::id();

    $mes = now()->month;
    $anio = now()->year;

    $query = ObligacionClienteContador::where('contador_id', $contadorId)
        ->whereMonth('fecha_vencimiento', $mes)
        ->whereYear('fecha_vencimiento', $anio);

    $total = (clone $query)->count();

    if ($total === 0) {
        $this->kpiObligaciones = [
            'sin_datos' => true,
        ];
        return;
    }

    $estatusCumplidos = [
        'realizada',
        'enviada_cliente',
        'respuesta_cliente',
        'respuesta_revisada',
        'finalizado'
    ];
    
    $cumplidas = (clone $query)
        ->whereIn('estatus', $estatusCumplidos)
        ->count();

    $declaradas = (clone $query)
        ->where('estatus', 'declaracion_realizada')
        ->count();

    $enProceso = (clone $query)
        ->whereNotIn('estatus', ['finalizado'])
        ->count();

    $vencidas = (clone $query)
        ->whereDate('fecha_vencimiento', '<', now())
        ->where('estatus', '!=', 'finalizado')
        ->count();

        $porcentaje = $total > 0
        ? round(($cumplidas / $total) * 100, 2)
        : null;
        $this->kpiObligaciones = [
            'sin_datos' => false,
            'total' => $total,
            'cumplidas' => $cumplidas,
            'en_proceso' => $total - $cumplidas,
            'vencidas' => $vencidas,
            'porcentaje' => $porcentaje,
        ];
}

    private function cargarResumenGeneral()
    {
        // Se implementará en el siguiente paso
        $this->resumenGeneral = [];
    }

    public function render()
    {
        return view('livewire.dashboard.dashboard-contador');
    }
}