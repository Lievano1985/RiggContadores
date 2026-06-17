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
use App\Services\GeneradorObligaciones;
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
    public bool $modalSincronizarTarea = false;
    public bool $modalEliminarTarea = false;
    public ?int $tareaPendienteSincronizarId = null;
    public ?int $tareaPendienteEliminarId = null;
    public int $sincronizarAnioInicio;
    public int $sincronizarMesInicio;
    public int $sincronizarAnioFin;
    public int $sincronizarMesFin;
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
        'requiere_envio_cliente' => false,
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

    public function mount(): void
    {
        $this->inicializarRangoSincronizacion();
    }

    public function getAniosDisponiblesProperty(): array
    {
        return range(2010, now()->year);
    }

    protected function inicializarRangoSincronizacion(): void
    {
        $this->sincronizarAnioInicio = now()->year;
        $this->sincronizarMesInicio = now()->month;
        $this->sincronizarAnioFin = now()->year;
        $this->sincronizarMesFin = now()->month;
    }

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
            'requiere_envio_cliente' => false,
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
            'requiere_envio_cliente' => (bool) $ob->requiere_envio_cliente,
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
            'formObligacion.requiere_envio_cliente' => ['boolean'],
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
            $tarea = TareaCatalogo::findOrFail($this->tareaSeleccionadaId);
            $tarea->update($datos);
            $mensaje = 'Tarea actualizada correctamente.';
            $this->dispatch('notify', message: $mensaje);
        } else {
            $tarea = TareaCatalogo::create($datos);
            $this->tareaPendienteSincronizarId = $tarea->id;
            $this->inicializarRangoSincronizacion();
            $this->modalSincronizarTarea = true;
            $this->dispatch('notify', message: 'Tarea creada correctamente. Elige si deseas generarla en periodos existentes.');
        }

        $this->cerrarSidebar();
        $this->resetPage();
    }

    public function eliminarTarea(int $id): void
    {
        $this->tareaPendienteEliminarId = $id;
        $this->modalEliminarTarea = true;
    }

    public function cerrarModalSincronizarTarea(): void
    {
        $this->modalSincronizarTarea = false;
        $this->tareaPendienteSincronizarId = null;
        $this->inicializarRangoSincronizacion();
    }

    public function noGenerarTareaEnPeriodos(): void
    {
        $this->cerrarModalSincronizarTarea();
        $this->dispatch('notify', message: 'La tarea quedo en catalogo para futuras obligaciones.');
    }

    public function generarTareaPeriodoActual(): void
    {
        $tarea = TareaCatalogo::findOrFail($this->tareaPendienteSincronizarId);

        $asignadas = app(GeneradorObligaciones::class)->sincronizarTareaPeriodoActual($tarea);

        $this->cerrarModalSincronizarTarea();
        $this->resetPage();
        $this->dispatch('notify', message: "Se asigno la tarea a {$asignadas} obligaciones del periodo actual.");
    }

    public function generarTareaRango(): void
    {
        $this->validate([
            'sincronizarAnioInicio' => ['required', 'integer', 'min:2010', 'max:' . now()->year],
            'sincronizarMesInicio' => ['required', 'integer', 'min:1', 'max:12'],
            'sincronizarAnioFin' => ['required', 'integer', 'min:2010', 'max:' . now()->year],
            'sincronizarMesFin' => ['required', 'integer', 'min:1', 'max:12'],
        ]);

        $periodoInicio = ($this->sincronizarAnioInicio * 100) + $this->sincronizarMesInicio;
        $periodoFin = ($this->sincronizarAnioFin * 100) + $this->sincronizarMesFin;

        if ($periodoInicio > $periodoFin) {
            $this->addError('sincronizarMesInicio', 'El periodo inicial no puede ser mayor al periodo final.');
            return;
        }

        $periodoActual = (now()->year * 100) + now()->month;
        if ($periodoFin > $periodoActual) {
            $this->addError('sincronizarMesFin', 'No se pueden generar tareas en periodos futuros.');
            return;
        }

        $tarea = TareaCatalogo::findOrFail($this->tareaPendienteSincronizarId);

        $asignadas = app(GeneradorObligaciones::class)->sincronizarTareaEnRango(
            $tarea,
            $this->sincronizarAnioInicio,
            $this->sincronizarMesInicio,
            $this->sincronizarAnioFin,
            $this->sincronizarMesFin
        );

        $this->cerrarModalSincronizarTarea();
        $this->resetPage();
        $this->dispatch('notify', message: "Se asigno la tarea a {$asignadas} obligaciones del rango seleccionado.");
    }

    public function cancelarEliminacionTarea(): void
    {
        $this->modalEliminarTarea = false;
        $this->tareaPendienteEliminarId = null;
    }

    public function quitarTareaSoloCatalogo(): void
    {
        $tarea = TareaCatalogo::findOrFail($this->tareaPendienteEliminarId);
        $tarea->update(['activo' => false]);

        $this->cancelarEliminacionTarea();
        $this->resetPage();
        $this->dispatch('notify', message: 'Tarea desactivada del catalogo. No se tocaron tareas ya asignadas.');
    }

    public function quitarTareaCatalogoYPeriodoActual(): void
    {
        $tarea = TareaCatalogo::findOrFail($this->tareaPendienteEliminarId);
        $eliminadas = app(GeneradorObligaciones::class)->quitarTareaPeriodoActual($tarea);
        $tarea->update(['activo' => false]);

        $this->cancelarEliminacionTarea();
        $this->resetPage();
        $this->dispatch('notify', message: "Tarea desactivada del catalogo. Se quitaron {$eliminadas} tareas del periodo actual.");
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

    public function toggleRequiereEnvioCliente(int $id): void
    {
        $obligacion = Obligacion::findOrFail($id);
        $obligacion->update([
            'requiere_envio_cliente' => ! $obligacion->requiere_envio_cliente,
        ]);
    }

    public function actualizarRequiereEnvioCliente(int $id, string $valor): void
    {
        Obligacion::findOrFail($id)->update([
            'requiere_envio_cliente' => $valor === '1',
        ]);
    }
    
    public function render()
    {
        $search = trim($this->search);

        $query = Obligacion::query()
            ->when($search !== '', function ($q) use ($search) {
                $q->where('nombre', 'like', "%{$search}%")
                  ->orWhere('categoria', 'like', "%{$search}%")
                  ->orWhereHas('tareasCatalogo', fn ($qt) =>
                      $qt->where('activo', true)
                          ->where('nombre', 'like', "%{$search}%")
                  );
            })
            ->withCount(['tareasCatalogo' => fn ($q) => $q->where('activo', true)])
            ->with(['tareasCatalogo' => fn ($q) => $q->where('activo', true)->orderBy('nombre')]);

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
