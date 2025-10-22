<?php



namespace App\Livewire\Catalogos;

use App\Models\Regimen;
use Livewire\Component;
use Livewire\WithPagination;

class RegimenesCrud extends Component
{
    use WithPagination;

    public $regimenId;
    public $nombre;
    public $clave_sat;
    public $tipo_persona;
    public $modalFormVisible = false;
    public $isEdit = false;
    public $search = '';
    public $sortField = 'nombre';
    public $sortDirection = 'asc';
    protected $rules = [
        'nombre' => 'required|string|min:3',
        'clave_sat' => 'required|string|min:2|max:10',
        'tipo_persona' => 'required|in:física,moral,física/moral',
    ];

    public function render()
    {

        $regimen = Regimen::query()
        ->where('nombre', 'like', '%' . $this->search . '%')
        ->orWhere('clave_sat', 'like', '%' . $this->search . '%')
        ->orWhere('tipo_persona', 'like', '%' . $this->search . '%')

        ->orderBy($this->sortField, $this->sortDirection)
        ->paginate(10);


 

        return view('livewire.catalogos.regimenes-crud', [
            'regimenes' => $regimen
        ]);
    }

    public function showCreateForm()
    {
        $this->resetForm();
        $this->modalFormVisible = true;
        $this->isEdit = false;
    }

    public function showEditForm(Regimen $regimen)
    {
        $this->regimenId = $regimen->id;
        $this->nombre = $regimen->nombre;
        $this->clave_sat = $regimen->clave_sat;
        $this->tipo_persona = $regimen->tipo_persona;
        $this->modalFormVisible = true;
        $this->isEdit = true;
    }

    public function save()
    {
        $this->validate();

        if ($this->isEdit && $this->regimenId) {
            Regimen::find($this->regimenId)->update($this->only(['nombre', 'clave_sat', 'tipo_persona']));
        } else {
            Regimen::create($this->only(['nombre', 'clave_sat', 'tipo_persona']));
        }

        $this->modalFormVisible = false;
        $this->resetForm();
    }

    public function delete($id)
    {
        Regimen::find($id)?->delete();
    }

    public function resetForm()
    {
        $this->reset(['regimenId', 'nombre', 'clave_sat', 'tipo_persona']);
    }
    public function updatingSearch() { $this->resetPage(); }

public function sortBy($field) {
    if ($this->sortField === $field) {
        $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
    } else {
        $this->sortField = $field;
        $this->sortDirection = 'asc';
    }
}
}
