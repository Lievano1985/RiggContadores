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
        // Combos vacíos (Selecciona...)
        $this->filtroEjercicio = null;
        $this->filtroMes = null;

        // Modo automático activo
        $this->filtroEstatus = 'auto';
        $this->incluirVencidas = true;

        // Cargar ejercicios desde campo EJERCICIO
        $this->cargarEjerciciosDisponibles();
    }

    public function cargarEjerciciosDisponibles(): void
    {
        $this->ejerciciosDisponibles = ObligacionClienteContador::query()
            ->whereNotIn('estatus', $this->estatusExcluidosCliente)
            ->whereNotNull('ejercicio')
            ->select('ejercicio')
            ->distinct()
            ->orderByDesc('ejercicio')
            ->pluck('ejercicio')
            ->map(fn($v) => (int) $v)
            ->values()
            ->toArray();
    
        if (empty($this->ejerciciosDisponibles)) {
            $this->ejerciciosDisponibles = [(int) now()->year];
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
        // Al tocar filtros manuales salimos de modo automático
        $this->incluirVencidas = false;
    
        $this->resetPage();
    }
    

    public function updatedFiltroMes(): void
    {
        // Al tocar filtros manuales salimos de modo automático
        $this->incluirVencidas = false;
    
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

    // Reset UI
    $this->mostrarRechazoObligacion = false;
    $this->comentarioRechazoObligacion = '';
    $this->comentarioRechazoTarea = [];
    $this->mostrarRechazoTarea = [];

    // Cargar registro con tareas
    $registro = ObligacionClienteContador::with('tareasAsignadas')
        ->find($id);

    if ($registro) {

        // Comentario de obligación
        $this->comentarioRechazoObligacion = $registro->comentario ?? '';

        // Comentarios de tareas
        foreach ($registro->tareasAsignadas as $tarea) {

            $this->comentarioRechazoTarea[$tarea->id] = $tarea->comentario ?? '';

            // Si está rechazada, mostrar área de rechazo
            if ($tarea->estatus === 'rechazada') {
                $this->mostrarRechazoTarea[$tarea->id] = true;
            }
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

    /**
     * Admin marca tarea como revisada (solo si está realizada).
     */
    public function marcarTareaRevisada(int $tareaId): void
    {
        $tarea = TareaAsignada::find($tareaId);
        if (!$tarea) return;

        if ($tarea->estatus !== 'realizada') {
            $this->dispatch('notify', message: 'Solo se pueden revisar tareas en estatus "realizada".');
            return;
        }

        $tarea->estatus = 'revisada';
        $tarea->save();

        // Cierra área de rechazo si estaba abierta
        $this->mostrarRechazoTarea[$tareaId] = false;
        unset($this->comentarioRechazoTarea[$tareaId]);

        $this->dispatch('notify', message: 'Tarea marcada como revisada.');
    }

    /**
     * Admin rechaza una tarea y devuelve obligación a en_progreso.
     */
    public function rechazarTarea(int $tareaId): void
    {
        if (empty($this->comentarioRechazoTarea[$tareaId])) {
            $this->dispatch('notify', message: 'Escribe el motivo del rechazo.');

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

        $this->dispatch('notify', message: 'Tarea rechazada. Obligación devuelta al contador.');

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
            $this->dispatch('notify', message: 'Escribe el motivo del rechazo.');

            return;
        }

        $registro->estatus = 'rechazada';
        $registro->comentario = $this->comentarioRechazoObligacion;
        $registro->save();

        $this->cerrarSidebar();
        $this->dispatch('notify', message: 'Obligación rechazada y devuelta al contador.');
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
            $this->dispatch('notify', message: 'Solo se puede finalizar una obligación en estatus "realizada".');

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
        $this->dispatch('notify', message: 'Obligación finalizada correctamente.');
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
            ->whereNotIn('estatus', $this->estatusExcluidosCliente);
    
        // ✅ Filtro por rol (Spatie)
        $user = auth()->user();
        if ($user && $user->hasRole('supervisor')) {
            $query->where('contador_id', $user->id);
        }

        // FILTRO PERÍODO
        // Si estamos en modo inicial: mes actual + vencidas no cerradas (no finalizado)
        // FILTRO ESTATUS
        if ($this->filtroEstatus !== 'auto' && $this->filtroEstatus !== 'todos') {
            $query->where('estatus', $this->filtroEstatus);
        }

     // ===============================
// FILTRO ESTATUS (manual)
// ===============================
if ($this->filtroEstatus !== 'auto' && $this->filtroEstatus !== 'todos') {
    $query->where('estatus', $this->filtroEstatus);
}

// ===============================
// FILTRO AUTOMÁTICO (fecha_vencimiento)
// ===============================
if ($this->filtroEstatus === 'auto') {

    $query->where(function ($q) {

        // Mes actual por fecha real
        $q->where(function ($qq) {
            $qq->whereYear('fecha_vencimiento', now()->year)
               ->whereMonth('fecha_vencimiento', now()->month);
        })

        // Vencidas no finalizadas
        ->orWhere(function ($qq) {
            $qq->whereDate('fecha_vencimiento', '<', now())
               ->where('estatus', '!=', 'finalizado');
        });
    });

} else {

    // ===============================
    // FILTRO MANUAL (ejercicio / mes)
    // ===============================

    if ($this->filtroEjercicio) {
        $query->where('ejercicio', $this->filtroEjercicio);
    }

    if ($this->filtroMes) {
        $query->where('mes', $this->filtroMes);
    }
}


// ===============================
// BUSCADOR POR CLIENTE
// ===============================
if (!empty($this->search)) {
    $query->whereHas('cliente', function ($q) {
        $q->where('nombre', 'like', '%' . $this->search . '%')
          ->orWhere('razon_social', 'like', '%' . $this->search . '%')
          ->orWhere('nombre_comercial', 'like', '%' . $this->search . '%')
          ->orWhere('rfc', 'like', '%' . $this->search . '%');
    });
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
