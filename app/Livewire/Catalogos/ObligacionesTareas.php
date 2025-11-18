<?php

/**
 * Componente Livewire: ObligacionesTareas
 * Descripción: Gestión unificada del catálogo de obligaciones y sus tareas hijas,
 *              con tabla expandible y sidebar para crear/editar.
 * Autor: Luis Liévano - JL3 Digital
 */

namespace App\Livewire\Catalogos;

use App\Models\Obligacion;
use App\Models\TareaCatalogo;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class ObligacionesTareas extends Component
{
    use WithPagination;
    public string $search = '';

    protected $paginationTheme = 'tailwind';

    public array $obligacionesExpandidas = [];

    public bool $sidebarVisible = false;
    public ?string $sidebarModo = null;

    public ?int $obligacionSeleccionadaId = null;
    public ?int $tareaSeleccionadaId = null;

    public array $formObligacion = [
        'nombre'        => '',
        'periodicidad'  => '',
        'mes_inicio'    => 1,
        'desfase_meses' => null,
        'dia_corte'     => null,
        'activa'        => true,
    ];

    public array $formTarea = [
        'obligacion_id' => null,
        'nombre'        => '',
        'descripcion'   => '',
    ];

    public array $periodicidades = [
        'mensual'       => 'Mensual',
        'bimestral'     => 'Bimestral',
        'trimestral'    => 'Trimestral',
        'cuatrimestral' => 'Cuatrimestral',
        'semestral'     => 'Semestral',
        'anual'         => 'Anual',
        'unica'         => 'Única',
        'eventual'      => 'Eventual',
    ];

    
    public function updatingPage()
    {
        $this->obligacionesExpandidas = [];
    }

    public function toggleObligacion(int $obligacionId): void
    {
        $actual = $this->obligacionesExpandidas[$obligacionId] ?? false;
        $this->obligacionesExpandidas[$obligacionId] = !$actual;
    }

    protected function resetSidebar(): void
    {
        $this->formObligacion = [
            'nombre'        => '',
            'periodicidad'  => '',
            'mes_inicio'    => 1,
            'desfase_meses' => null,
            'dia_corte'     => null,
            'activa'        => true,
        ];

        $this->formTarea = [
            'obligacion_id' => null,
            'nombre'        => '',
            'descripcion'   => '',
        ];

        $this->obligacionSeleccionadaId = null;
        $this->tareaSeleccionadaId = null;
    }

    public function cerrarSidebar(): void
    {
        $this->sidebarVisible = false;
        $this->sidebarModo    = null;
        $this->resetSidebar();
    }

    public function abrirCrearObligacion(): void
    {
        $this->resetSidebar();
        $this->sidebarModo = 'crear_obligacion';
        $this->sidebarVisible = true;
    }

    public function abrirEditarObligacion(int $id): void
    {
        $this->resetSidebar();
        $this->sidebarModo = 'editar_obligacion';
        $this->obligacionSeleccionadaId = $id;

        $ob = Obligacion::findOrFail($id);

        $this->formObligacion = [
            'nombre'        => $ob->nombre,
            'periodicidad'  => $ob->periodicidad,
            'mes_inicio'    => (int) ($ob->mes_inicio ?? 1),
            'desfase_meses' => $ob->desfase_meses,
            'dia_corte'     => $ob->dia_corte,
            'activa'        => (bool) $ob->activa,
        ];

        $this->sidebarVisible = true;
    }

    public function abrirCrearTarea(int $obligacionId): void
    {
        $this->resetSidebar();
        $this->sidebarModo = 'crear_tarea';
        $this->obligacionSeleccionadaId = $obligacionId;
        $this->formTarea['obligacion_id'] = $obligacionId;
        $this->sidebarVisible = true;
    }

    public function abrirEditarTarea(int $tareaId): void
    {
        $this->resetSidebar();
        $this->sidebarModo = 'editar_tarea';
        $this->tareaSeleccionadaId = $tareaId;

        $t = TareaCatalogo::findOrFail($tareaId);

        $this->formTarea = [
            'obligacion_id' => $t->obligacion_id,
            'nombre'        => $t->nombre,
            'descripcion'   => $t->descripcion,
        ];

        $this->sidebarVisible = true;
    }

    
    public function guardarObligacion(): void
    {
        $this->validate($this->reglasObligacion());
        $datos = $this->formObligacion;
        $datos['periodicidad'] = strtolower($datos['periodicidad'] ?? '');
        $datos['tipo'] = 'mixto';

        if (in_array($datos['periodicidad'], ['unica','única','eventual'], true)) {
            $datos['mes_inicio'] = 1;
            $datos['desfase_meses'] = null;
            $datos['dia_corte'] = null;
        }

        if ($this->sidebarModo === 'editar_obligacion') {
            Obligacion::findOrFail($this->obligacionSeleccionadaId)->update($datos);
        } else {
            Obligacion::create($datos);
        }

        $this->cerrarSidebar();
        $this->resetPage();
    }

    public function guardarTarea(): void
    {
        $this->validate($this->reglasTarea());
        $datos = $this->formTarea;
        $datos['activo'] = true;

        if ($this->sidebarModo === 'editar_tarea') {
            TareaCatalogo::findOrFail($this->tareaSeleccionadaId)->update($datos);
        } else {
            TareaCatalogo::create($datos);
        }

        $this->cerrarSidebar();
        $this->resetPage();
    }

    public function eliminarTarea(int $id): void
    {
        TareaCatalogo::findOrFail($id)->delete();
        $this->cerrarSidebar();
        $this->resetPage();
    }

    
    public function render()
    {
        $search = trim($this->search);
    
        $obligaciones = Obligacion::query()
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($q2) use ($search) {
                    // 1) Coincidencias por nombre de la obligación
                    $q2->where('nombre', 'like', "%{$search}%")
                        // 2) O coincidencias por nombre de la tarea
                        ->orWhereHas('tareasCatalogo', function ($qt) use ($search) {
                            $qt->where('nombre', 'like', "%{$search}%");
                        });
                });
            })
            ->withCount('tareasCatalogo')
            ->with(['tareasCatalogo' => fn($q) => $q->orderBy('nombre')])
            ->orderBy('nombre')
            ->paginate(10);
    
        return view('livewire.catalogos.obligaciones-tareas', [
            'obligaciones' => $obligaciones,
        ]);
    }
    
}
