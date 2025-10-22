<?php

namespace App\Livewire\Catalogos;

use App\Models\ActividadEconomica;
use Livewire\Component;
use Livewire\WithPagination;

class ActividadesCrud extends Component
{
    use WithPagination;

    public $actividadId;
    public $nombre;
    public $clave;
    public $ponderacion;
    public $modalFormVisible = false;
    public $isEdit = false;

    public $search = '';
    public $sortField = 'nombre';
    public $sortDirection = 'asc';

    protected $rules = [
        'nombre' => 'required|string|min:3',
        'clave' => 'required|string|min:3|max:10',
        'ponderacion' => 'nullable|numeric|min:0',
    ];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function render()
    {
        $actividades = ActividadEconomica::query()
            ->where('nombre', 'like', '%' . $this->search . '%')
            ->orWhere('clave', 'like', '%' . $this->search . '%')
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate(10);

        return view('livewire.catalogos.actividades-crud', [
            'actividades' => $actividades,
        ]);
    }

    public function showCreateForm()
    {
        $this->resetForm();
        $this->modalFormVisible = true;
        $this->isEdit = false;
    }

    public function showEditForm(ActividadEconomica $actividad)
    {
        $this->actividadId = $actividad->id;
        $this->nombre = $actividad->nombre;
        $this->clave = $actividad->clave;
        $this->ponderacion = $actividad->ponderacion;
        $this->modalFormVisible = true;
        $this->isEdit = true;
    }

    public function save()
    {
        $this->validate();

        if ($this->isEdit && $this->actividadId) {
            ActividadEconomica::find($this->actividadId)
                ->update($this->only(['nombre', 'clave', 'ponderacion']));
        } else {
            ActividadEconomica::create($this->only(['nombre', 'clave', 'ponderacion']));
        }

        $this->modalFormVisible = false;
        $this->resetForm();
    }

    public function delete($id)
    {
        ActividadEconomica::find($id)?->delete();
    }

    public function resetForm()
    {
        $this->reset(['actividadId', 'nombre', 'clave', 'ponderacion']);
    }
}
