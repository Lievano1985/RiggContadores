<?php

/**
 * Componente Livewire: ValidacionesIndex
 * Autor: Luis Liévano - JL3 Digital
 * Descripción técnica:
 * - Bandeja de revisión interna (NO envía al cliente).
 * - Muestra obligaciones por período (ejercicio/mes) y vencidas no cerradas.
 * - Permite expandir para ver tareas.
 * - Sidebar para revisar tareas:
 *    - Tarea: realizada -> revisada
 *    - Rechazar tarea: -> rechazada y regresa obligación a en_progreso
 * - Finalizar obligación SOLO si:
 *    - obligación estatus = realizada
 *    - y (no tiene tareas) o (todas las tareas estatus = revisada)
 */

namespace App\Livewire\Control;

use App\Models\ObligacionClienteContador;
use App\Models\TareaAsignada;
use Illuminate\Support\Carbon;
use Livewire\Component;
use Livewire\WithPagination;

class ValidacionesIndex extends Component
{
    use WithPagination;

    /* ===========================
     | Filtros / UI
     * =========================== */
    public string $search = '';

    public ?int $filtroEjercicio = null; // Año
    public ?int $filtroMes = null;       // 1-12

    /** Si es true, en modo "carga inicial" incluimos vencidas no cerradas */
    public bool $incluirVencidas = true;

    /** Opciones de ejercicio */
    public array $ejerciciosDisponibles = [];

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
    /**filtro estatus */
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
        $hoy = Carbon::now();

        // Por defecto: período actual
        $this->filtroEjercicio = (int) $hoy->year;
        $this->filtroMes = (int) $hoy->month;

        // Carga ejercicios disponibles (para combo)
        $this->cargarEjerciciosDisponibles();
        $this->filtroEstatus = 'auto';
    }

    public function cargarEjerciciosDisponibles(): void
    {
        $this->ejerciciosDisponibles = ObligacionClienteContador::query()
            ->whereNotIn('estatus', $this->estatusExcluidosCliente)
            ->selectRaw('YEAR(fecha_vencimiento) as anio')
            ->whereNotNull('fecha_vencimiento')
            ->groupBy('anio')
            ->orderByDesc('anio')
            ->pluck('anio')
            ->map(fn($v) => (int) $v)
            ->values()
            ->toArray();

        // Si por alguna razón no hay datos, evita combos vacíos rotos
        if (empty($this->ejerciciosDisponibles)) {
            $this->ejerciciosDisponibles = [(int) Carbon::now()->year];
        }
    }

    /* ===========================
     | Hooks filtros
     * =========================== */


    public function updatedFiltroEstatus()
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
        // Si el admin cambia filtros, dejamos de incluir vencidas automáticamente
        $this->incluirVencidas = false;

        // Si filtroMes viene null, lo dejamos con mes actual
        if (!$this->filtroMes) {
            $this->filtroMes = (int) Carbon::now()->month;
        }

        $this->resetPage();
    }

    public function updatedFiltroMes(): void
    {
        $this->incluirVencidas = false;

        // Si filtroEjercicio viene null, lo dejamos con año actual
        if (!$this->filtroEjercicio) {
            $this->filtroEjercicio = (int) Carbon::now()->year;
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
        $this->expandida[$id] = !($this->expandida[$id] ?? false);
    }

    public function abrirSidebar(int $id): void
    {
        $this->obligacionIdSeleccionada = $id;
        $this->sidebarVisible = true;

        // Reset UI de rechazos
        $this->mostrarRechazoObligacion = false;
        $this->comentarioRechazoObligacion = '';
        $this->comentarioRechazoTarea = [];
        $this->mostrarRechazoTarea = [];
    }

    public function cerrarSidebar(): void
    {
        $this->sidebarVisible = false;
        $this->obligacionIdSeleccionada = null;
    }

    /* ===========================
     | Acciones Admin
     * =========================== */

    /**
     * Admin marca tarea como revisada (solo si está realizada).
     */
    public function marcarTareaRevisada(int $tareaId): void
    {
        $tarea = TareaAsignada::find($tareaId);
        if (!$tarea) return;

        if ($tarea->estatus !== 'realizada') {
            $this->dispatch('notify', message:'Solo se pueden revisar tareas en estatus "realizada".');
        return;
        }

        $tarea->estatus = 'revisada';
        $tarea->save();

        // Cierra área de rechazo si estaba abierta
        $this->mostrarRechazoTarea[$tareaId] = false;
        unset($this->comentarioRechazoTarea[$tareaId]);

        $this->dispatch('notify', message:'Tarea marcada como revisada.');

    }

    /**
     * Admin rechaza una tarea y devuelve obligación a en_progreso.
     */
    public function rechazarTarea(int $tareaId): void
    {
        if (empty($this->comentarioRechazoTarea[$tareaId])) {
            $this->dispatch('notify', message:'Escribe el motivo del rechazo.');

            return;
        }

        $tarea = TareaAsignada::find($tareaId);
        if (!$tarea) return;

        $tarea->estatus = 'rechazada';
        $tarea->comentario = $this->comentarioRechazoTarea[$tareaId];
        $tarea->save();

        // Regresar obligación a en_progreso
        $obligacion = $tarea->obligacionClienteContador ?? null;
        if ($obligacion) {
            $obligacion->estatus = 'en_progreso';
            $obligacion->save();
        }

        $this->dispatch('notify', message:'Tarea rechazada. Obligación devuelta al contador.');

        $this->cerrarSidebar();
    }

    /**
     * Rechazar obligación completa.
     * (opcional, lo dejamos activo; si no lo quieres, me dices y lo quitamos)
     */
    public function rechazarObligacion(): void
    {
        $registro = $this->obligacionIdSeleccionada
            ? ObligacionClienteContador::find($this->obligacionIdSeleccionada)
            : null;

        if (!$registro) return;

        if (!$this->comentarioRechazoObligacion) {
            $this->dispatch('notify', message:'Escribe el motivo del rechazo.');

            return;
        }

        $registro->estatus = 'rechazada';
        $registro->comentario = $this->comentarioRechazoObligacion;
        $registro->save();

        $this->cerrarSidebar();
        $this->dispatch('notify', message:'Obligación rechazada y devuelta al contador.');

    }

    /**
     * Finaliza obligación SOLO si:
     * - estatus obligación = realizada
     * - y (no tiene tareas) o (todas tareas = revisada)
     */
    public function finalizarObligacion(): void
    {
        if (!$this->obligacionIdSeleccionada) return;

        $registro = ObligacionClienteContador::with(['tareasAsignadas'])
            ->find($this->obligacionIdSeleccionada);

        if (!$registro) return;

        if ($registro->estatus !== 'realizada') {
            $this->dispatch('notify', message:'Solo se puede finalizar una obligación en estatus "realizada".');

            return;
        }

        $tareas = $registro->tareasAsignadas;

        if ($tareas->count() > 0) {
            $pendientes = $tareas->where('estatus', '!=', 'revisada')->count();
            if ($pendientes > 0) {
               
                $this->dispatch('notify', message:'No puedes finalizar: faltan tareas por revisar (estatus distinto a "revisada").');

                return;
            }
        }

        $registro->estatus = 'finalizado';
        $registro->save();

        $this->cerrarSidebar();
        $this->dispatch('notify', message:'Obligación finalizada correctamente.');

    }

    /* ===========================
     | Render / Query principal
     * =========================== */
    public function render()
    {
        $hoy = Carbon::now()->startOfDay();

        $query = ObligacionClienteContador::select('obligacion_cliente_contador.*')
    ->with([

                'cliente',
                'contador',
                'obligacion',
                'archivos',
                'tareasAsignadas.archivos',
                'tareasAsignadas.tareaCatalogo',
                'tareasAsignadas.contador',

            ])
            // Excluimos flujos del cliente (van en otro componente)
            ->whereNotIn('estatus', $this->estatusExcluidosCliente);

        // Búsqueda por cliente
        if ($this->search !== '') {
            $query->whereHas('cliente', function ($qc) {
                $qc->where('nombre', 'like', "%{$this->search}%");
            });
        }

        // FILTRO PERÍODO
        // Si estamos en modo inicial: mes actual + vencidas no cerradas (no finalizado)
        // FILTRO ESTATUS
        if ($this->filtroEstatus !== 'auto' && $this->filtroEstatus !== 'todos') {
            $query->where('estatus', $this->filtroEstatus);
        }

        // FILTRO PERÍODO
        if ($this->filtroEstatus === 'auto') {

            $ejercicioActual = (int) now()->year;
            $mesActual = (int) now()->month;

            $query->where(function ($q) use ($ejercicioActual, $mesActual) {

                // Mes actual
                $q->where(function ($qq) use ($ejercicioActual, $mesActual) {
                    $qq->whereYear('fecha_vencimiento', $ejercicioActual)
                        ->whereMonth('fecha_vencimiento', $mesActual);
                })

                    // Vencidas no cerradas
                    ->orWhere(function ($qq) {
                        $qq->whereDate('fecha_vencimiento', '<', now())
                            ->whereNotIn('estatus', ['finalizado']);
                    });
            });
        } else {

            if ($this->filtroEjercicio) {
                $query->whereYear('fecha_vencimiento', $this->filtroEjercicio);
            }

            if ($this->filtroMes) {
                $query->whereMonth('fecha_vencimiento', $this->filtroMes);
            }
        }


        $obligaciones = $query
            ->orderBy('fecha_vencimiento')
            ->orderByDesc('updated_at')
            ->paginate(10);

            $obligacionSeleccionada = $this->obligacionIdSeleccionada
            ? ObligacionClienteContador::select('obligacion_cliente_contador.*')
                ->with([
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
