<?php 

namespace App\Livewire\Catalogos;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\TareaCatalogo;
use App\Models\Obligacion;

class TareasCrud extends Component
{
    use WithPagination;

    public $search = '';
    public $obligacionFiltro = '';
    public $modalVisible = false;
    public $isEditing = false;
    public $confirmingDelete = false;
    public $tareaAEliminar = null;

    public $form = [
        'id' => null,
        'nombre' => '',
        'descripcion' => '',
        'obligacion_id' => null,
    ];

    protected $rules = [
        'form.nombre' => 'required|string|max:255',
        'form.descripcion' => 'nullable|string|max:1000',
        'form.obligacion_id' => 'nullable|exists:obligaciones,id',
    ];

    public function render()
    {
        $query = TareaCatalogo::query()
            ->with('obligacion')
            ->when($this->search, fn($q) =>
                $q->where('nombre', 'like', '%' . $this->search . '%'))
            ->when($this->obligacionFiltro, function ($q) {
                if ($this->obligacionFiltro === 'sin') {
                    // Filtro especial para tareas sin obligación
                    $q->whereNull('obligacion_id');
                } else {
                    $q->where('obligacion_id', $this->obligacionFiltro);
                }
            })
            ->orderBy('nombre');
    
        // Obligaciones que tienen tareas en catálogo
        $obligacionesConTareas = \App\Models\Obligacion::whereHas('tareasCatalogo')
            ->orderBy('nombre')
            ->get();
    
        return view('livewire.catalogos.tareas-crud', [
            'tareas' => $query->paginate(10),
            'obligaciones' => $obligacionesConTareas,
        ]);
    }
    
    // Reset de la paginación al cambiar filtros
    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingObligacionFiltro()
    {
        $this->resetPage();
    }

    public function crear()
    {
        $this->reset(['form']);
        $this->isEditing = false;
        $this->modalVisible = true;
    }

    public function editar($id)
    {
        $registro = TareaCatalogo::findOrFail($id);
        $this->form = $registro->only(['id', 'nombre', 'descripcion', 'obligacion_id']);
        $this->isEditing = true;
        $this->modalVisible = true;
    }

    public function guardar()
    {
        $this->validate();

        if ($this->isEditing && $this->form['id']) {
            TareaCatalogo::find($this->form['id'])->update($this->form);
        } else {
            TareaCatalogo::create($this->form);
        }

        $this->modalVisible = false;
        session()->flash('success', 'Tarea guardada correctamente.');
    }

    public function toggleActivo($id)
    {
        $tarea = TareaCatalogo::findOrFail($id);
        $tarea->activo = !$tarea->activo;
        $tarea->save();
    }

    public function eliminar($id)
    {
        $tarea = TareaCatalogo::findOrFail($id);

        if ($tarea->tareasAsignadas()->exists()) {
            session()->flash('error', 'No se puede eliminar esta tarea porque ya fue asignada.');
            return;
        }

        $tarea->delete();
        session()->flash('success', 'Tarea eliminada correctamente.');
    }

    public function confirmarEliminacion($id)
    {
        $this->tareaAEliminar = $id;
        $this->confirmingDelete = true;
    }

    public function eliminarConfirmada()
    {
        $tarea = TareaCatalogo::findOrFail($this->tareaAEliminar);

        if ($tarea->tareasAsignadas()->exists()) {
            session()->flash('error', 'No se puede eliminar esta tarea porque ya fue asignada.');
        } else {
            $tarea->delete();
            session()->flash('success', 'Tarea eliminada correctamente.');
        }

        $this->confirmingDelete = false;
        $this->tareaAEliminar = null;
    }
}
