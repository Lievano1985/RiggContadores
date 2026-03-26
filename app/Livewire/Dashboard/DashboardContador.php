<?php

namespace App\Livewire\Dashboard;

use App\Models\ObligacionClienteContador;
use App\Models\TareaAsignada;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class DashboardContador extends Component
{
    public string $fechaHoy = '';
    public array $kpis = [];
    public int $porcentajeObligacionesCerradas = 0;
    public array $tareasUrgentes = [];
    public array $obligacionesUrgentes = [];
    public array $rechazadas = [];

    public function mount(): void
    {
        $this->fechaHoy = Carbon::now()->translatedFormat('d \\d\\e F Y');

        $this->cargarDashboard();
    }

    private function cargarDashboard(): void
    {
        $contadorId = (int) Auth::id();
        $hoy = now();

        $this->kpis = $this->construirKpis($contadorId, $hoy);
        $this->porcentajeObligacionesCerradas = $this->calcularPorcentajeObligacionesCerradas();
        $this->tareasUrgentes = $this->consultarTareasUrgentes($contadorId, $hoy);
        $this->obligacionesUrgentes = $this->consultarObligacionesUrgentes($contadorId, $hoy);
        $this->rechazadas = $this->consultarRechazadas($contadorId);
    }

    private function construirKpis(int $contadorId, Carbon $hoy): array
    {
        $tareasBase = TareaAsignada::query()
            ->where('contador_id', $contadorId)
            ->whereHas('contador', fn ($q) => $q->whereKey($contadorId));
        $obligacionesBase = ObligacionClienteContador::query()
            ->where('contador_id', $contadorId)
            ->whereHas('contador', fn ($q) => $q->whereKey($contadorId))
            ->where('is_activa', true);
        $inicioMes = $hoy->copy()->startOfMonth();
        $finMes = $hoy->copy()->endOfMonth();

        $tareaCerrada = ['realizada', 'revisada', 'cerrada', 'terminada', 'cancelada'];
        $obligacionCerrada = [
            'realizada',
            'declaracion_realizada',
            'enviada_cliente',
            'respuesta_cliente',
            'respuesta_revisada',
            'finalizado',
        ];
        $obligacionAbierta = ['asignada', 'en_progreso', 'rechazada', 'reabierta'];

        $obligacionesAsignadasMes = (clone $obligacionesBase)
            ->whereBetween('fecha_vencimiento', [$inicioMes->toDateString(), $finMes->toDateString()])
            ->count();

        $obligacionesAtrasadas = (clone $obligacionesBase)
            ->whereNotNull('fecha_vencimiento')
            ->whereDate('fecha_vencimiento', '<', $inicioMes->toDateString())
            ->whereIn('estatus', $obligacionAbierta)
            ->count();

        $obligacionesTerminadasMes = (clone $obligacionesBase)
            ->whereBetween('fecha_vencimiento', [$inicioMes->toDateString(), $finMes->toDateString()])
            ->whereIn('estatus', $obligacionCerrada)
            ->count();

        $obligacionesFaltantesMes = (clone $obligacionesBase)
            ->whereBetween('fecha_vencimiento', [$inicioMes->toDateString(), $finMes->toDateString()])
            ->whereIn('estatus', $obligacionAbierta)
            ->count();

        $tareasAsignadasMes = (clone $tareasBase)
            ->whereBetween('fecha_limite', [$inicioMes->toDateString(), $finMes->toDateString()])
            ->count();

        $tareasAtrasadas = (clone $tareasBase)
            ->whereNotNull('fecha_limite')
            ->whereDate('fecha_limite', '<', $inicioMes->toDateString())
            ->where(function ($q) use ($tareaCerrada) {
                $q->where('estatus', 'rechazada')
                    ->orWhereNotIn('estatus', $tareaCerrada);
            })
            ->count();

        $tareasTerminadasMes = (clone $tareasBase)
            ->whereBetween('fecha_limite', [$inicioMes->toDateString(), $finMes->toDateString()])
            ->whereIn('estatus', $tareaCerrada)
            ->count();

        $tareasFaltantesMes = (clone $tareasBase)
            ->whereBetween('fecha_limite', [$inicioMes->toDateString(), $finMes->toDateString()])
            ->where(function ($q) use ($tareaCerrada) {
                $q->where('estatus', 'rechazada')
                    ->orWhereNotIn('estatus', $tareaCerrada);
            })
            ->count();

        return [
            'obligaciones_asignadas_mes' => $obligacionesAsignadasMes,
            'obligaciones_atrasadas' => $obligacionesAtrasadas,
            'obligaciones_terminadas_mes' => $obligacionesTerminadasMes,
            'obligaciones_faltantes_mes' => $obligacionesFaltantesMes,
            'tareas_asignadas_mes' => $tareasAsignadasMes,
            'tareas_atrasadas' => $tareasAtrasadas,
            'tareas_terminadas_mes' => $tareasTerminadasMes,
            'tareas_faltantes_mes' => $tareasFaltantesMes,
        ];
    }

    private function consultarTareasUrgentes(int $contadorId, Carbon $hoy): array
    {
        return TareaAsignada::query()
            ->with(['cliente:id,nombre,razon_social', 'tareaCatalogo:id,nombre'])
            ->where('contador_id', $contadorId)
            ->whereHas('contador', fn ($q) => $q->whereKey($contadorId))
            ->whereIn('estatus', ['asignada', 'en_progreso', 'reabierta', 'rechazada'])
            ->whereNotNull('fecha_limite')
            // Incluye atrasadas y proximas (siguientes 7 dias), en orden ascendente.
            ->whereDate('fecha_limite', '<=', $hoy->copy()->addDays(7)->toDateString())
            ->orderBy('fecha_limite')
            ->limit(50)
            ->get()
            ->map(function (TareaAsignada $t) {
                return [
                    'nombre' => $t->tareaCatalogo->nombre ?? 'Sin nombre',
                    'cliente' => $t->cliente->nombre ?? $t->cliente->razon_social ?? 'Sin cliente',
                    'fecha_limite' => $t->fecha_limite
                        ? Carbon::parse($t->fecha_limite)->format('Y-m-d')
                        : '-',
                ];
            })
            ->values()
            ->all();
    }

    private function consultarObligacionesUrgentes(int $contadorId, Carbon $hoy): array
    {
        $estatusAbiertas = ['asignada', 'en_progreso', 'rechazada', 'reabierta'];

        return ObligacionClienteContador::query()
            ->with(['cliente:id,nombre,razon_social', 'obligacion:id,nombre'])
            ->where('contador_id', $contadorId)
            ->whereHas('contador', fn ($q) => $q->whereKey($contadorId))
            ->where('is_activa', true)
            ->whereIn('estatus', $estatusAbiertas)
            ->whereNotNull('fecha_vencimiento')
            // Incluye atrasadas y proximas (siguientes 7 dias), en orden ascendente.
            ->whereDate('fecha_vencimiento', '<=', $hoy->copy()->addDays(7)->toDateString())
            ->orderBy('fecha_vencimiento')
            ->limit(50)
            ->get()
            ->map(function (ObligacionClienteContador $o) {
                return [
                    'nombre' => $o->obligacion->nombre ?? 'Sin nombre',
                    'cliente' => $o->cliente->nombre ?? $o->cliente->razon_social ?? 'Sin cliente',
                    'fecha_vencimiento' => $o->fecha_vencimiento
                        ? Carbon::parse($o->fecha_vencimiento)->format('Y-m-d')
                        : '-',
                ];
            })
            ->values()
            ->all();
    }

    private function consultarRechazadas(int $contadorId): array
    {
        $obligaciones = ObligacionClienteContador::query()
            ->with(['cliente:id,nombre,razon_social', 'obligacion:id,nombre'])
            ->where('contador_id', $contadorId)
            ->whereHas('contador', fn ($q) => $q->whereKey($contadorId))
            ->where('is_activa', true)
            ->where('estatus', 'rechazada')
            ->latest('updated_at')
            ->limit(6)
            ->get()
            ->map(function (ObligacionClienteContador $o) {
                return [
                    'tipo' => 'Obligacion',
                    'nombre' => $o->obligacion->nombre ?? 'Sin nombre',
                    'cliente' => $o->cliente->nombre ?? $o->cliente->razon_social ?? 'Sin cliente',
                    'fecha' => optional($o->updated_at)?->format('Y-m-d H:i'),
                    'comentario' => $o->comentario,
                ];
            });

        $tareas = TareaAsignada::query()
            ->with(['cliente:id,nombre,razon_social', 'tareaCatalogo:id,nombre'])
            ->where('contador_id', $contadorId)
            ->whereHas('contador', fn ($q) => $q->whereKey($contadorId))
            ->where('estatus', 'rechazada')
            ->latest('updated_at')
            ->limit(6)
            ->get()
            ->map(function (TareaAsignada $t) {
                return [
                    'tipo' => 'Tarea',
                    'nombre' => $t->tareaCatalogo->nombre ?? 'Sin nombre',
                    'cliente' => $t->cliente->nombre ?? $t->cliente->razon_social ?? 'Sin cliente',
                    'fecha' => optional($t->updated_at)?->format('Y-m-d H:i'),
                    'comentario' => $t->comentario,
                ];
            });

        return $obligaciones
            ->concat($tareas)
            ->sortByDesc('fecha')
            ->take(10)
            ->values()
            ->all();
    }

    public function render()
    {
        return view('livewire.dashboard.dashboard-contador');
    }

    private function calcularPorcentajeObligacionesCerradas(): int
    {
        $asignadas = (int) ($this->kpis['obligaciones_asignadas_mes'] ?? 0);
        $cerradas = (int) ($this->kpis['obligaciones_terminadas_mes'] ?? 0);

        if ($asignadas <= 0) {
            return 0;
        }

        return (int) round(($cerradas / $asignadas) * 100);
    }
}
