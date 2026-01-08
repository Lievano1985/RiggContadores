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
use Illuminate\Support\Facades\Storage;

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

        $registro->estatus = 'reabierta';
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

        $tarea->estatus = 'rechazada';
        $tarea->comentario = $this->comentarioRechazoTarea[$tareaId];
        $tarea->save();

        session()->flash('mensaje', 'Tarea rechazada correctamente.');
        $this->mostrarRechazoTarea[$tareaId] = false;
    }

    public function render()
    {
        $query = ObligacionClienteContador::with([
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
            ->orderByDesc('fecha_termino');

        $obligaciones = $query->paginate(10);

        $seleccionada = $this->obligacionIdSeleccionada
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
            'obligacionSeleccionada' => $seleccionada,
        ]);
    }
}
