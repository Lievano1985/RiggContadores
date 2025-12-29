<?php

/**
 * Componente Livewire: ValidacionesIndex
 * Autor: Luis Liévano - JL3 Digital
 * Descripción técnica:
 * - Lista obligaciones finalizadas por contadores.
 * - Permite revisar tareas asociadas.
 * - Incluye sidebar para validar y enviar al cliente.
 */

namespace App\Livewire\Control;

use App\Models\ObligacionClienteContador;
use Livewire\Component;
use Livewire\WithPagination;

class ValidacionesIndex extends Component
{
    use WithPagination;

    public string $search = '';
    public array $expandida = [];
    public bool $sidebarVisible = false;
    public ?int $obligacionIdSeleccionada = null;

    public function toggleExpandida(int $id)
    {
        $this->expandida[$id] = !($this->expandida[$id] ?? false);
    }

    public function abrirSidebar(int $id)
    {
        $this->obligacionIdSeleccionada = $id;
        $this->sidebarVisible = true;
    }

    public function cerrarSidebar()
    {
        $this->sidebarVisible = false;
        $this->obligacionIdSeleccionada = null;
    }

    public function render()
    {
        $obligaciones = ObligacionClienteContador::with([
                'cliente',
                'contador',
                'obligacion',
                'tareasAsignadas' => fn($q) => $q->orderBy('nombre'),
            ])
            ->where('estatus', 'declaracion_realizada')
            ->when($this->search !== '', function ($q) {
                $q->whereHas('cliente', fn($qc) =>
                    $qc->where('nombre', 'like', "%{$this->search}%")
                );
            })
            ->orderByDesc('fecha_termino')
            ->paginate(10);

        return view('livewire.control.validaciones-index', compact('obligaciones'));
    }
}

