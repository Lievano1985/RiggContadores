<?php

/**
 * Componente Livewire: ValidacionesIndex
 * Autor: Luis Lievano - JL3 Digital
 * Descripcion tecnica:
 * - Bandeja de revision interna (NO envia al cliente).
 * - Muestra obligaciones por periodo (ejercicio/mes) y vencidas no cerradas.
 * - Permite expandir para ver tareas.
 * - Sidebar para revisar tareas:
 *    - Tarea: realizada -> revisada
 *    - Rechazar tarea: -> rechazada y regresa obligacion a en_progreso
 * - Finalizar obligacion SOLO si:
 *    - obligacion estatus = realizada
 *    - y (no tiene tareas) o (todas las tareas estatus = revisada)
 */

namespace App\Livewire\Control;

use App\Livewire\Shared\HasPerPage;
use App\Models\Cliente;
use App\Models\Obligacion;
use App\Models\ObligacionClienteContador;
use App\Models\TareaAsignada;
use App\Models\User;
use Illuminate\Support\Carbon;
use Livewire\Component;
use Livewire\WithPagination;

class ValidacionesIndex extends Component
{
    use WithPagination, HasPerPage;

    public string $sortField = 'fecha_vencimiento';
    public string $sortDirection = 'asc';

    /* ===========================
     | Filtros / UI
     * =========================== */
    public ?int $clienteSeleccionado = null;
    public string $buscarCliente = '';
    public array $clientesDisponibles = [];
    public string $search = '';
    public ?int $filtroObligacion = null;
    public ?int $filtroContador = null;
    public ?int $filtroEjercicio = null;
    public ?int $filtroMes = null;

    /** Si es true, en modo "carga inicial" incluimos vencidas no cerradas */
    public bool $incluirVencidas = true;

    /** Opciones de ejercicio */
    public array $ejerciciosDisponibles = [];
    public array $contadoresDisponibles = [];
    public array $obligacionesDisponibles = [];

    /** Expandir filas */
    public array $expandida = [];

    /** Sidebar */
    public bool $sidebarVisible = false;
    public ?int $obligacionIdSeleccionada = null;

    /** Rechazos */
    public string $comentarioRechazoObligacion = '';
    public bool $mostrarRechazoObligacion = false;

    public array $comentarioRechazoTarea = [];
    public array $mostrarRechazoTarea = [];

    /** filtro estatus */
    public string $filtroEstatus = 'auto';

    /* ===========================
     | Constantes de estatus
     * =========================== */
    private array $estatusExcluidosCliente = [
        'enviada_cliente',
        'respuesta_cliente',
        'respuesta_revisada',
    ];

    private array $estatusCerrados = [
        'finalizado',
    ];

    public function mount(): void
    {
        $this->filtroEjercicio = null;
        $this->filtroMes = null;
        $this->cargarClientes();
        $this->cargarContadores();
        $this->cargarObligacionesDisponibles();
        $this->filtroEstatus = 'auto';
        $this->incluirVencidas = true;
        $this->cargarEjerciciosDisponibles();
    }

    public function cargarClientes(): void
    {
        $this->clientesDisponibles = Cliente::query()
            ->orderBy('nombre')
            ->get()
            ->map(fn ($cliente) => [
                'id' => $cliente->id,
                'nombre' => $cliente->nombre ?? $cliente->razon_social,
            ])
            ->toArray();
    }

    public function cargarContadores(): void
    {
        $user = auth()->user();

        $query = User::query()
            ->whereHas('roles', fn ($q) => $q->whereIn('name', ['contador', 'supervisor']))
            ->orderBy('name');

        if ($user?->despacho_id) {
            $query->where('despacho_id', $user->despacho_id);
        }

        if ($user?->hasRole('supervisor')) {
            $query->whereKey($user->id);
            $this->filtroContador = $user->id;
        }

        $this->contadoresDisponibles = $query
            ->get()
            ->map(fn (User $contador) => [
                'id' => $contador->id,
                'nombre' => $contador->name,
            ])
            ->toArray();
    }

    public function cargarObligacionesDisponibles(): void
    {
        $user = auth()->user();

        $query = ObligacionClienteContador::query()
            ->select('obligacion_cliente_contador.obligacion_id', 'obligaciones.nombre')
            ->join('obligaciones', 'obligaciones.id', '=', 'obligacion_cliente_contador.obligacion_id')
            ->whereNotNull('obligacion_cliente_contador.obligacion_id')
            ->where('obligacion_cliente_contador.is_activa', true);

        if ($user?->despacho_id) {
            $query->whereHas('cliente', function ($q) use ($user) {
                $q->where('despacho_id', $user->despacho_id);
            });
        }

        if ($user?->hasRole('supervisor')) {
            $query->where('obligacion_cliente_contador.contador_id', $user->id);
        }

        $this->obligacionesDisponibles = $query
            ->distinct()
            ->orderBy('obligaciones.nombre')
            ->get()
            ->map(fn ($obligacion) => [
                'id' => (int) $obligacion->obligacion_id,
                'nombre' => $obligacion->nombre,
            ])
            ->values()
            ->toArray();
    }

    public function cargarEjerciciosDisponibles(): void
    {
        $this->ejerciciosDisponibles = ObligacionClienteContador::query()
            ->where('is_activa', true)
            ->whereNotNull('ejercicio')
            ->select('ejercicio')
            ->distinct()
            ->orderByDesc('ejercicio')
            ->pluck('ejercicio')
            ->map(fn ($valor) => (int) $valor)
            ->values()
            ->toArray();

        if (empty($this->ejerciciosDisponibles)) {
            $this->ejerciciosDisponibles = [(int) now()->year];
        }
    }

    /* ===========================
     | Hooks filtros
     * =========================== */
    public function updatedClienteSeleccionado(): void
    {
        $this->incluirVencidas = false;
        $this->resetPage();
    }

    public function updatedFiltroObligacion(): void
    {
        $this->resetPage();
    }

    public function updatedFiltroContador(): void
    {
        $this->resetPage();
    }

    public function updatedFiltroEstatus(): void
    {
        if ($this->filtroEstatus === 'auto') {
            $this->aplicarModoInicial();
        } else {
            $this->incluirVencidas = false;
            $this->resetPage();
        }
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedFiltroEjercicio(): void
    {
        $this->incluirVencidas = false;
        $this->resetPage();
    }

    public function updatedFiltroMes(): void
    {
        $this->incluirVencidas = false;
        $this->resetPage();
    }

    public function sortBy(string $field): void
    {
        if (! in_array($field, ['cliente', 'obligacion', 'contador', 'estatus', 'periodo', 'fecha_vencimiento'], true)) {
            return;
        }

        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }

        $this->resetPage();
    }

    public function aplicarModoInicial(): void
    {
        $hoy = Carbon::now();
        $this->filtroEjercicio = (int) $hoy->year;
        $this->filtroMes = (int) $hoy->month;
        $this->incluirVencidas = true;

        $this->resetPage();
    }

    /* ===========================
     | Expandir / Sidebar
     * =========================== */
    public function toggleExpandida(int $id): void
    {
        $this->expandida[$id] = ! ($this->expandida[$id] ?? false);
    }

    public function abrirSidebar(int $id): void
    {
        $this->obligacionIdSeleccionada = $id;
        $this->sidebarVisible = true;

        $this->mostrarRechazoObligacion = false;
        $this->comentarioRechazoObligacion = '';
        $this->comentarioRechazoTarea = [];
        $this->mostrarRechazoTarea = [];

        $registro = ObligacionClienteContador::with('tareasAsignadas')->find($id);

        if (! $registro) {
            return;
        }

        $this->comentarioRechazoObligacion = $registro->comentario ?? '';

        foreach ($registro->tareasAsignadas as $tarea) {
            $this->comentarioRechazoTarea[$tarea->id] = $tarea->comentario ?? '';

            if ($tarea->estatus === 'rechazada') {
                $this->mostrarRechazoTarea[$tarea->id] = true;
            }
        }
    }

    public function cerrarSidebar(): void
    {
        $this->sidebarVisible = false;
        $this->obligacionIdSeleccionada = null;
    }

    /* ===========================
     | Acciones Admin
     * =========================== */
    public function marcarTareaRevisada(int $tareaId): void
    {
        $tarea = TareaAsignada::find($tareaId);

        if (! $tarea) {
            return;
        }

        if ($tarea->estatus !== 'realizada') {
            $this->dispatch('notify', message: 'Solo se pueden revisar tareas en estatus "realizada".');

            return;
        }

        $tarea->estatus = 'revisada';
        $tarea->save();

        $this->mostrarRechazoTarea[$tareaId] = false;
        unset($this->comentarioRechazoTarea[$tareaId]);

        $this->dispatch('notify', message: 'Tarea marcada como revisada.');
    }

    public function rechazarTarea(int $tareaId): void
    {
        if (empty($this->comentarioRechazoTarea[$tareaId])) {
            $this->dispatch('notify', message: 'Escribe el motivo del rechazo.');

            return;
        }

        $tarea = TareaAsignada::find($tareaId);

        if (! $tarea) {
            return;
        }

        $tarea->estatus = 'rechazada';
        $tarea->comentario = $this->comentarioRechazoTarea[$tareaId];
        $tarea->save();

        $obligacion = $tarea->obligacionClienteContador ?? null;
        if ($obligacion) {
            $obligacion->estatus = 'en_progreso';
            $obligacion->save();
        }

        $this->dispatch('notify', message: 'Tarea rechazada. Obligacion devuelta al contador.');

        $this->cerrarSidebar();
    }

    public function rechazarObligacion(): void
    {
        $registro = $this->obligacionIdSeleccionada
            ? ObligacionClienteContador::find($this->obligacionIdSeleccionada)
            : null;

        if (! $registro) {
            return;
        }

        if (! $this->comentarioRechazoObligacion) {
            $this->dispatch('notify', message: 'Escribe el motivo del rechazo.');

            return;
        }

        $registro->estatus = 'rechazada';
        $registro->comentario = $this->comentarioRechazoObligacion;
        $registro->save();

        $this->cerrarSidebar();
        $this->dispatch('notify', message: 'Obligacion rechazada y devuelta al contador.');
    }

    public function finalizarObligacion(): void
    {
        if (! $this->obligacionIdSeleccionada) {
            return;
        }

        $registro = ObligacionClienteContador::with(['tareasAsignadas'])
            ->find($this->obligacionIdSeleccionada);

        if (! $registro) {
            return;
        }

        if ($registro->estatus !== 'realizada') {
            $this->dispatch('notify', message: 'Solo se puede finalizar una obligacion en estatus "realizada".');

            return;
        }

        $tareas = $registro->tareasAsignadas;

        if ($tareas->count() > 0) {
            $pendientes = $tareas->where('estatus', '!=', 'revisada')->count();
            if ($pendientes > 0) {
                $this->dispatch('notify', message: 'No puedes finalizar: faltan tareas por revisar (estatus distinto a "revisada").');

                return;
            }
        }

        $registro->estatus = 'finalizado';
        $registro->save();

        $this->cerrarSidebar();
        $this->dispatch('notify', message: 'Obligacion finalizada correctamente.');
    }

    /* ===========================
     | Render / Query principal
     * =========================== */
    public function render()
    {
        $query = ObligacionClienteContador::select('obligacion_cliente_contador.*')
            ->where('obligacion_cliente_contador.is_activa', true)
            ->with([
                'cliente',
                'contador',
                'obligacion',
                'archivos',
                'tareasAsignadas.archivos',
                'tareasAsignadas.tareaCatalogo',
                'tareasAsignadas.contador',
            ]);

        $user = auth()->user();
        if ($user && $user->hasRole('supervisor')) {
            $query->where('contador_id', $user->id);
        }

        if ($this->filtroContador) {
            $query->where('contador_id', $this->filtroContador);
        }

        if ($this->clienteSeleccionado) {
            $query->where('cliente_id', $this->clienteSeleccionado);
        }

        if ($this->filtroObligacion) {
            $query->where('obligacion_id', $this->filtroObligacion);
        }

        if ($this->filtroEstatus !== 'auto' && $this->filtroEstatus !== 'todos') {
            $query->where('estatus', $this->filtroEstatus);
        }

        if ($this->filtroEstatus === 'auto') {
            $query->whereNotIn('estatus', $this->estatusExcluidosCliente);
        }

        if ($this->filtroEstatus === 'auto' && $this->incluirVencidas) {
            $query->where(function ($q) {
                $q->where(function ($qq) {
                    $qq->whereYear('fecha_vencimiento', now()->year)
                        ->whereMonth('fecha_vencimiento', now()->month);
                })->orWhere(function ($qq) {
                    $qq->whereDate('fecha_vencimiento', '<', now())
                        ->where('estatus', '!=', 'finalizado');
                });
            });
        } else {
            if ($this->filtroEjercicio) {
                $query->where('ejercicio', $this->filtroEjercicio);
            }

            if ($this->filtroMes) {
                $query->where('mes', $this->filtroMes);
            }
        }

        if (! empty($this->search)) {
            $query->whereHas('cliente', function ($q) {
                $q->where('nombre', 'like', '%' . $this->search . '%')
                    ->orWhere('razon_social', 'like', '%' . $this->search . '%')
                    ->orWhere('nombre_comercial', 'like', '%' . $this->search . '%')
                    ->orWhere('rfc', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->sortField === 'cliente') {
            $query->orderBy(
                Cliente::select('nombre')
                    ->whereColumn('clientes.id', 'obligacion_cliente_contador.cliente_id')
                    ->limit(1),
                $this->sortDirection
            );
        } elseif ($this->sortField === 'obligacion') {
            $query->orderBy(
                Obligacion::select('nombre')
                    ->whereColumn('obligaciones.id', 'obligacion_cliente_contador.obligacion_id')
                    ->limit(1),
                $this->sortDirection
            );
        } elseif ($this->sortField === 'contador') {
            $query->orderBy(
                User::select('name')
                    ->whereColumn('users.id', 'obligacion_cliente_contador.contador_id')
                    ->limit(1),
                $this->sortDirection
            );
        } elseif ($this->sortField === 'periodo') {
            $query->orderBy('ejercicio', $this->sortDirection)
                ->orderBy('mes', $this->sortDirection);
        } elseif (in_array($this->sortField, ['estatus', 'fecha_vencimiento'], true)) {
            $query->orderBy($this->sortField, $this->sortDirection);
        } else {
            $query->orderBy('fecha_vencimiento')
                ->orderByDesc('updated_at');
        }

        $obligaciones = $query->paginate($this->perPageValue($query, 10));

        $obligacionSeleccionada = $this->obligacionIdSeleccionada
            ? ObligacionClienteContador::with([
                'cliente',
                'contador',
                'obligacion',
                'archivos',
                'tareasAsignadas.archivos',
                'tareasAsignadas.tareaCatalogo',
                'tareasAsignadas.contador',
            ])->find($this->obligacionIdSeleccionada)
            : null;

        return view('livewire.control.validaciones-index', [
            'obligaciones' => $obligaciones,
            'obligacionSeleccionada' => $obligacionSeleccionada,
            'ejerciciosDisponibles' => $this->ejerciciosDisponibles,
        ]);
    }
}
