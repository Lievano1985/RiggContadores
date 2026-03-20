<?php

/**
 * Componente Livewire: ObligacionesTareas
 * Descripcion: Gestion unificada del catalogo de obligaciones y sus tareas hijas,
 *              con tabla expandible y sidebar para crear/editar.
 * Autor: Luis Lievano - JL3 Digital
 */

namespace App\Livewire\Catalogos;

use App\Livewire\Shared\HasPerPage;
use App\Models\Obligacion;
use App\Models\TareaCatalogo;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class ObligacionesTareas extends Component
{
    use WithPagination, HasPerPage;

    protected $paginationTheme = 'tailwind';

    public string $search = '';
    public string $sortField = 'categoria';
    public string $sortDirection = 'asc';
    public array $obligacionesExpandidas = [];

    public bool $sidebarVisible = false;
    public ?string $sidebarModo = null;

    public ?int $obligacionSeleccionadaId = null;
    public ?int $tareaSeleccionadaId = null;
    public array $categorias = [
        'obligacion' => 'Obligacion',
        'proceso'    => 'Proceso',
    ];
    /* =========================
     | FORMULARIO OBLIGACION
     ========================= */
    public array $formObligacion = [
        'nombre'        => '',
        'categoria'     => '',
        'periodicidad'  => '',
        'mes_inicio'    => 1,
        'desfase_meses' => null,
        'dia_corte'     => null,
        'activa'        => true,
    ];

    /* =========================
     | FORMULARIO TAREA
     ========================= */
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
        'unica'         => 'Unica',
        'eventual'      => 'Eventual',
    ];

    public function updatingPage()
    {
        $this->obligacionesExpandidas = [];
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function sortBy(string $field): void
    {
        if (!in_array($field, ['nombre', 'categoria', 'periodicidad', 'tareas', 'activa'], true)) {
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

    public function toggleObligacion(int $id): void
    {
        $this->obligacionesExpandidas[$id] = !($this->obligacionesExpandidas[$id] ?? false);
    }

    protected function resetSidebar(): void
    {
        $this->formObligacion = [
            'nombre'        => '',
            'categoria'     => '',
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
        $this->sidebarModo = null;
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
            'categoria'     => $ob->categoria,
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

    public function abrirEditarTarea(int $id): void
    {
        $this->resetSidebar();
        $this->sidebarModo = 'editar_tarea';
        $this->tareaSeleccionadaId = $id;

        $t = TareaCatalogo::findOrFail($id);

        $this->formTarea = [
            'obligacion_id' => $t->obligacion_id,
            'nombre'        => $t->nombre,
            'descripcion'   => $t->descripcion,
        ];

        $this->sidebarVisible = true;
    }

    protected function reglasObligacion(): array
    {
        return [
            'formObligacion.nombre'       => ['required', 'string', 'min:3', 'max:255'],
            'formObligacion.categoria'    => ['required', 'string', 'min:3', 'max:100'],
            'formObligacion.periodicidad' => ['required', Rule::in(array_keys($this->periodicidades))],
            'formObligacion.mes_inicio'   => ['nullable', 'integer', 'min:1', 'max:12'],
            'formObligacion.desfase_meses'=> ['nullable', 'integer', 'min:0', 'max:12'],
            'formObligacion.dia_corte'    => ['nullable', 'integer', 'min:1', 'max:31'],
            'formObligacion.activa'       => ['boolean'],
        ];
    }

    protected function reglasTarea(): array
    {
        return [
            'formTarea.obligacion_id' => ['required', 'exists:obligaciones,id'],
            'formTarea.nombre'        => ['required', 'string', 'min:3', 'max:255'],
            'formTarea.descripcion'   => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function guardarObligacion(): void
    {
        $this->validate($this->reglasObligacion());

        $datos = $this->formObligacion;
        $datos['periodicidad'] = strtolower($datos['periodicidad']);
        $datos['tipo'] = 'mixto';

        if (in_array($datos['periodicidad'], ['unica', 'unica', 'eventual'], true)) {
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
        $this->resetPage();
    }
    public function eliminarObligacion(int $obligacionId): void
    {
        $obligacion = Obligacion::with('tareasCatalogo')->findOrFail($obligacionId);
    
        // Eliminar primero las tareas hijas
        foreach ($obligacion->tareasCatalogo as $tarea) {
            $tarea->delete();
        }
    
        // Eliminar la obligacion
        $obligacion->delete();
    
        // Limpiar estados visuales
        unset($this->obligacionesExpandidas[$obligacionId]);
    
        // Refrescar paginación si es necesario
        $this->resetPage();
    }
    
    public function render()
    {
        $search = trim($this->search);

        $query = Obligacion::query()
            ->when($search !== '', function ($q) use ($search) {
                $q->where('nombre', 'like', "%{$search}%")
                  ->orWhere('categoria', 'like', "%{$search}%")
                  ->orWhereHas('tareasCatalogo', fn ($qt) =>
                      $qt->where('nombre', 'like', "%{$search}%")
                  );
            })
            ->withCount('tareasCatalogo')
            ->with(['tareasCatalogo' => fn ($q) => $q->orderBy('nombre')]);

        if ($this->sortField === 'tareas') {
            $query->orderBy('tareas_catalogo_count', $this->sortDirection)
                ->orderBy('nombre', 'asc');
        } elseif (in_array($this->sortField, ['nombre', 'categoria', 'periodicidad', 'activa'], true)) {
            $query->orderBy($this->sortField, $this->sortDirection)
                ->orderBy('nombre', 'asc');
        } else {
            $query->orderBy('categoria')->orderBy('nombre');
        }

        $obligaciones = $query->paginate($this->perPageValue($query, 10));

        return view('livewire.catalogos.obligaciones-tareas', compact('obligaciones'));
    }
}
