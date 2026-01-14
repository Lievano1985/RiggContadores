<?php

/**
 * Componente Livewire: ValidacionesIndex
 * Autor: Luis Liévano - JL3 Digital
 * Descripción técnica:
 * - Lista obligaciones con estatus 'realizada'.
 * - Permite expandir para ver tareas asignadas.
 * - Sidebar para revisar, ver detalles y enviar al cliente.
 */

namespace App\Livewire\Control;

use App\Models\ObligacionClienteContador;
use App\Models\TareaAsignada;
use Livewire\Component;
use Livewire\WithPagination;

class ValidacionesIndex extends Component
{
    use WithPagination;

    public string $search = '';
    public array $expandida = [];

    public bool $sidebarVisible = false;
    public ?int $obligacionIdSeleccionada = null;

    public string $comentarioRechazoObligacion = '';
    public array $comentarioRechazoTarea = [];
    public array $mostrarRechazoTarea = [];

    public bool $mostrarRechazoObligacion = false;

    public function toggleExpandida(int $id): void
    {
        $this->expandida[$id] = !($this->expandida[$id] ?? false);
    }

    public function abrirSidebar(int $id): void
    {
        $this->obligacionIdSeleccionada = $id;
        $this->sidebarVisible = true;
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

    public function enviarAlCliente(): void
    {
        if (!$this->obligacionIdSeleccionada) return;

        $registro = ObligacionClienteContador::find($this->obligacionIdSeleccionada);
        if (!$registro) return;

        $registro->estatus = 'enviada_cliente';
        $registro->save();

        $this->cerrarSidebar();
        session()->flash('mensaje', 'Obligación enviada al cliente correctamente.');
    }

    public function rechazarObligacion(): void
    {
        $registro = ObligacionClienteContador::find($this->obligacionIdSeleccionada);
        if (!$registro || !$this->comentarioRechazoObligacion) return;

        $registro->estatus = 'rechazada';
        $registro->comentario = $this->comentarioRechazoObligacion;
        $registro->save();

        $this->cerrarSidebar();
        session()->flash('mensaje', 'Obligación rechazada y devuelta al contador.');
    }

    public function rechazarTarea(int $tareaId): void
    {
        if (empty($this->comentarioRechazoTarea[$tareaId])) return;
    
        $tarea = TareaAsignada::find($tareaId);
        if (!$tarea) return;
    
        // 1️⃣ Rechazar tarea
        $tarea->estatus = 'rechazada';
        $tarea->comentario = $this->comentarioRechazoTarea[$tareaId];
        $tarea->save();
    
        // 2️⃣ Regresar obligación a en_progreso
        $obligacion = $tarea->obligacionClienteContador ?? null;
    
        if ($obligacion) {
            $obligacion->estatus = 'en_progreso';
            $obligacion->save();
        }
    
        session()->flash('mensaje', 'Tarea rechazada. Obligación devuelta al contador.');
    
        // 3️⃣ Cerrar sidebar
        $this->cerrarSidebar();
    }
    
    public function render()
    {
        $obligaciones = ObligacionClienteContador::with([
                'cliente',
                'contador',
                'obligacion',
                'archivos',
                'tareasAsignadas.archivos',
                'tareasAsignadas.tareaCatalogo',
            ])
            ->where('estatus', 'realizada')
            ->when($this->search !== '', function ($q) {
                $q->whereHas('cliente', fn($qc) =>
                    $qc->where('nombre', 'like', "%{$this->search}%")
                );
            })
            ->orderByDesc('fecha_termino')
            ->paginate(10);

        $obligacionSeleccionada = $this->obligacionIdSeleccionada
            ? ObligacionClienteContador::with([
                'cliente',
                'contador',
                'obligacion',
                'archivos',
                'tareasAsignadas.archivos',
                'tareasAsignadas.tareaCatalogo',
            ])->find($this->obligacionIdSeleccionada)
            : null;

        return view('livewire.control.validaciones-index', [
            'obligaciones' => $obligaciones,
            'obligacionSeleccionada' => $obligacionSeleccionada,
        ]);
    }
}
