<?php
/**
 * Componente Livewire: ObligacionesCrud
 * Descripción: CRUD del catálogo de obligaciones. Incluye 'unica'.
 * Autor: Luis Liévano - JL3 Digital
 */

namespace App\Livewire\Catalogos;

use App\Models\Obligacion;
use Livewire\Component;
use Livewire\WithPagination;

class ObligacionesCrud extends Component
{
    use WithPagination;

    public $obligacionAEliminar;
    public $confirmingDelete = false;

    public $obligacionId;
    public $nombre;
    public $tipo;
    public $periodicidad = 'mensual';
    public $mes_inicio = 1;
    public $desfase_meses = 1;
    public $dia_corte = 17;
    public $activa = true;

    public $modalFormVisible = false;
    public $isEdit = false;
    public $search = '';
    public $sortField = 'nombre';
    public $sortDirection = 'asc';
    public $mesesPermitidos = [];

    protected $rules = [
        'nombre'         => 'required|string|min:3',
        'tipo'           => 'required|string|in:federal,estatal,local,patronal',
        // Incluye 'unica'
        'periodicidad'   => 'required|in:mensual,bimestral,trimestral,cuatrimestral,semestral,anual,unica',
        'mes_inicio'     => 'required|integer|min:1|max:12',
        'desfase_meses'  => 'required|integer|min:0|max:12',
        'dia_corte'      => 'required|integer|min:1|max:31',
        'activa'         => 'boolean',
    ];

    public function updatedPeriodicidad($value)
    {
        $this->mesesPermitidos = Obligacion::mesInicioPermitido($value);
        $this->mes_inicio = $this->mesesPermitidos[0] ?? 1;

        // Para 'unica' podemos sugerir desfase 0 (no se usa en cálculo)
        if (strtolower($value) === 'unica') {
            $this->desfase_meses = 0;
        }
    }

    public function mount()
    {
        $this->mesesPermitidos = Obligacion::mesInicioPermitido($this->periodicidad);
    }

    public function render()
    {
        $obligaciones = Obligacion::query()
            ->where(function ($q) {
                $q->where('nombre', 'like', "%{$this->search}%")
                  ->orWhere('tipo', 'like', "%{$this->search}%")
                  ->orWhere('periodicidad', 'like', "%{$this->search}%");
            })
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate(10);

        return view('livewire.catalogos.obligaciones-crud', [
            'obligaciones' => $obligaciones,
        ]);
    }

    public function showCreateForm()
    {
        $this->resetForm();
        $this->modalFormVisible = true;
        $this->isEdit = false;
    }

    public function showEditForm(Obligacion $obligacion)
    {
        $this->fill([
            'obligacionId'   => $obligacion->id,
            'nombre'         => $obligacion->nombre,
            'tipo'           => $obligacion->tipo,
            'periodicidad'   => $obligacion->periodicidad,
            'mes_inicio'     => $obligacion->mes_inicio,
            'desfase_meses'  => $obligacion->desfase_meses,
            'dia_corte'      => $obligacion->dia_corte,
            'activa'         => $obligacion->activa,
        ]);

        $this->mesesPermitidos = Obligacion::mesInicioPermitido($this->periodicidad);
        $this->modalFormVisible = true;
        $this->isEdit = true;
    }

    public function save()
    {
        $this->validate();

        // Validación de mes_inicio solo si NO es única
        $permitidos = Obligacion::mesInicioPermitido($this->periodicidad);
        if (strtolower($this->periodicidad) !== 'unica' && !in_array($this->mes_inicio, $permitidos, true)) {
            $this->addError('mes_inicio', 'El mes de inicio no es válido para la periodicidad seleccionada.');
            return;
        }

        $data = $this->only([
            'nombre', 'tipo', 'periodicidad', 'mes_inicio', 'desfase_meses', 'dia_corte', 'activa'
        ]);

        if ($this->isEdit && $this->obligacionId) {
            Obligacion::findOrFail($this->obligacionId)->update($data);
        } else {
            Obligacion::create($data);
        }

        $this->modalFormVisible = false;
        $this->resetForm();
        session()->flash('success', 'Obligación guardada correctamente.');
    }

    public function confirmarEliminacion($id)
    {
        $this->obligacionAEliminar = $id;
        $this->confirmingDelete = true;
    }

    public function eliminarConfirmada()
    {
        $obligacion = Obligacion::findOrFail($this->obligacionAEliminar);

        if ($obligacion->obligacionesAsignadas()->exists()) {
            session()->flash('error', 'No se puede eliminar esta obligación porque ya fue asignada.');
        } else {
            $obligacion->delete();
            session()->flash('success', 'Obligación eliminada correctamente.');
        }

        $this->confirmingDelete = false;
        $this->obligacionAEliminar = null;
    }

    public function resetForm()
    {
        $this->reset([
            'obligacionId', 'nombre', 'tipo', 'periodicidad',
            'mes_inicio', 'desfase_meses', 'dia_corte', 'activa'
        ]);

        $this->periodicidad = 'mensual';
        $this->mesesPermitidos = Obligacion::mesInicioPermitido('mensual');
        $this->mes_inicio = 1;
        $this->desfase_meses = 1;
        $this->dia_corte = 17;
        $this->activa = true;
    }

    public function updatingSearch() { $this->resetPage(); }

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }
}
