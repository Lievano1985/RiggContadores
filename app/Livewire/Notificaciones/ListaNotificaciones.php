<?php

/**
 * Autor: Luis Liévano - JL3 Digital
 *
 * Componente: ListaNotificaciones
 * Función:
 * Muestra historial de notificaciones enviadas al cliente.
 */

namespace App\Livewire\Notificaciones;

use Livewire\Component;
use App\Models\Cliente;
use App\Models\NotificacionCliente;
use App\Models\ObligacionClienteContador;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ListaNotificaciones extends Component
{
    public $cliente;
    public $sidebarVisible = false;
    public $notificacionSeleccionada = null;
    public string $sortField = 'created_at';
    public string $sortDirection = 'desc';
    public string $fecha_desde = '';
    public string $fecha_hasta = '';
    public string $periodo_mes = '';
    public string $periodo_ejercicio = '';
    public string $cliente_filtro = '';
    public string $obligacion_filtro = '';
    public array $mesesManual = [
        1 => 'Enero',
        2 => 'Febrero',
        3 => 'Marzo',
        4 => 'Abril',
        5 => 'Mayo',
        6 => 'Junio',
        7 => 'Julio',
        8 => 'Agosto',
        9 => 'Septiembre',
        10 => 'Octubre',
        11 => 'Noviembre',
        12 => 'Diciembre',
    ];
    
    public function mount($cliente)
    {
        $this->cliente = $cliente;
        $this->cliente_filtro = (string) $cliente->id;
    }
    public function abrirSidebar($id)
    {
        $this->notificacionSeleccionada =
            NotificacionCliente::with(['usuario','obligaciones','archivos'])
                ->findOrFail($id);
    
        $this->sidebarVisible = true;
    }
    
    public function cerrarSidebar()
    {
        $this->sidebarVisible = false;
        $this->notificacionSeleccionada = null;
    }
    
    public function render()
    {
        $clientes = $this->clientesDisponibles();
        $ejercicios = $this->ejerciciosDisponibles();
        $obligaciones = $this->obligacionesDisponibles();

        $query = NotificacionCliente::query()
            ->with(['usuario', 'cliente'])
            ->when($this->cliente_filtro !== '', fn ($q) => $q->where('cliente_id', $this->cliente_filtro))
            ->when($this->fecha_desde !== '', fn ($q) => $q->whereDate('created_at', '>=', $this->fecha_desde))
            ->when($this->fecha_hasta !== '', fn ($q) => $q->whereDate('created_at', '<=', $this->fecha_hasta))
            ->when($this->periodo_mes !== '', fn ($q) => $q->where('periodo_mes', (int) $this->periodo_mes))
            ->when($this->periodo_ejercicio !== '', fn ($q) => $q->where('periodo_ejercicio', (int) $this->periodo_ejercicio))
            ->when($this->obligacion_filtro !== '', function ($q) {
                $q->whereHas('obligaciones', function ($sub) {
                    $sub->where('obligacion_id', (int) $this->obligacion_filtro);
                });
            });

        if ($this->sortField === 'usuario') {
            $query->orderBy(
                User::select('name')
                    ->whereColumn('users.id', 'notificaciones_clientes.user_id')
                    ->limit(1),
                $this->sortDirection
            );
        } elseif ($this->sortField === 'cliente') {
            $query->orderBy(
                Cliente::select('nombre')
                    ->whereColumn('clientes.id', 'notificaciones_clientes.cliente_id')
                    ->limit(1),
                $this->sortDirection
            );
        } elseif (in_array($this->sortField, ['created_at', 'asunto', 'periodo_mes'], true)) {
            $query->orderBy($this->sortField, $this->sortDirection);
        } else {
            $query->orderByDesc('created_at');
        }

        $notificaciones = $query->get();

        return view('livewire.notificaciones.lista-notificaciones', [
            'notificaciones' => $notificaciones,
            'clientesDisponibles' => $clientes,
            'ejerciciosDisponibles' => $ejercicios,
            'obligacionesDisponibles' => $obligaciones,
        ]);
    }

    public function sortBy(string $field): void
    {
        if (!in_array($field, ['created_at', 'asunto', 'periodo_mes', 'usuario', 'cliente'], true)) {
            return;
        }

        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    private function clientesDisponibles()
    {
        return Cliente::query()
            ->whereHas('obligacionesAsignadas')
            ->when(!auth()->user()->hasRole('super_admin'), function ($q) {
                $q->where('despacho_id', auth()->user()->despacho_id);
            })
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'razon_social']);
    }

    private function ejerciciosDisponibles(): array
    {
        return NotificacionCliente::query()
            ->when($this->cliente_filtro !== '', fn ($q) => $q->where('cliente_id', $this->cliente_filtro))
            ->whereNotNull('periodo_ejercicio')
            ->pluck('periodo_ejercicio')
            ->unique()
            ->sortDesc()
            ->values()
            ->all();
    }

    private function obligacionesDisponibles()
    {
        $idsNotificaciones = NotificacionCliente::query()
            ->select('id')
            ->when($this->cliente_filtro !== '', fn ($q) => $q->where('cliente_id', $this->cliente_filtro))
            ->when($this->fecha_desde !== '', fn ($q) => $q->whereDate('created_at', '>=', $this->fecha_desde))
            ->when($this->fecha_hasta !== '', fn ($q) => $q->whereDate('created_at', '<=', $this->fecha_hasta))
            ->when($this->periodo_mes !== '', fn ($q) => $q->where('periodo_mes', (int) $this->periodo_mes))
            ->when($this->periodo_ejercicio !== '', fn ($q) => $q->where('periodo_ejercicio', (int) $this->periodo_ejercicio));

        $obligacionIds = DB::table('notificacion_obligacion')
            ->join('obligacion_cliente_contador', 'obligacion_cliente_contador.id', '=', 'notificacion_obligacion.obligacion_cliente_contador_id')
            ->whereIn('notificacion_cliente_id', $idsNotificaciones)
            ->pluck('obligacion_cliente_contador.obligacion_id')
            ->unique()
            ->filter()
            ->values()
            ->all();

        if (empty($obligacionIds)) {
            return collect();
        }

        return ObligacionClienteContador::query()
            ->with('obligacion')
            ->whereIn('obligacion_id', $obligacionIds)
            ->get()
            ->pluck('obligacion')
            ->filter()
            ->unique('id')
            ->sortBy('nombre')
            ->values();
    }
}
