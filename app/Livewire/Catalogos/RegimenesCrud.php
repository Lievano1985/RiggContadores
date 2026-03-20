<?php

namespace App\Livewire\Catalogos;

use App\Livewire\Shared\HasPerPage;
use App\Models\Regimen;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class RegimenesCrud extends Component
{
    use WithPagination, HasPerPage;

    public ?int $regimenId = null;
    public string $nombre = '';
    public string $clave_sat = '';
    public string $tipo_persona = '';
    public bool $modalFormVisible = false;
    public bool $isEdit = false;
    public string $search = '';
    public string $sortField = 'nombre';
    public string $sortDirection = 'asc';

    public function render()
    {
        $query = Regimen::query()
            ->where(function ($q) {
                $q->where('nombre', 'like', '%' . $this->search . '%')
                    ->orWhere('clave_sat', 'like', '%' . $this->search . '%')
                    ->orWhere('tipo_persona', 'like', '%' . $this->search . '%');
            });

        if (in_array($this->sortField, ['nombre', 'clave_sat', 'tipo_persona'], true)) {
            $query->orderBy($this->sortField, $this->sortDirection);
        } else {
            $query->orderBy('nombre', 'asc');
        }

        $regimenes = $query->paginate($this->perPageValue($query, 10));

        return view('livewire.catalogos.regimenes-crud', [
            'regimenes' => $regimenes,
        ]);
    }

    protected function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'min:3'],
            'clave_sat' => [
                'required',
                'string',
                'min:3',
                'max:10',
                Rule::unique('regimenes', 'clave_sat')->ignore($this->regimenId),
            ],
            'tipo_persona' => ['required', Rule::in(['fisica', 'moral', 'fisica/moral'])],
        ];
    }

    public function showCreateForm(): void
    {
        $this->resetForm();
        $this->modalFormVisible = true;
        $this->isEdit = false;
    }

    public function showEditForm(int $regimenId): void
    {
        $this->resetForm();
        $regimen = Regimen::findOrFail($regimenId);

        $this->regimenId = $regimen->id;
        $this->nombre = $regimen->nombre;
        $this->clave_sat = $regimen->clave_sat;
        $this->tipo_persona = $regimen->tipo_persona;
        $this->modalFormVisible = true;
        $this->isEdit = true;
    }

    public function save(): void
    {
        $this->validate();

        if ($this->isEdit && $this->regimenId) {
            Regimen::findOrFail($this->regimenId)->update(
                $this->only(['nombre', 'clave_sat', 'tipo_persona'])
            );
        } else {
            Regimen::create($this->only(['nombre', 'clave_sat', 'tipo_persona']));
        }

        $this->modalFormVisible = false;
        $this->resetForm();
    }

    public function delete(int $id): void
    {
        Regimen::find($id)?->delete();
    }

    public function sortBy(string $field): void
    {
        if (!in_array($field, ['nombre', 'clave_sat', 'tipo_persona'], true)) {
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

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    private function resetForm(): void
    {
        $this->reset(['regimenId', 'nombre', 'clave_sat', 'tipo_persona']);
        $this->resetValidation();
        $this->resetErrorBag();
    }
}
