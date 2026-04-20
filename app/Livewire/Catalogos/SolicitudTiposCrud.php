<?php

namespace App\Livewire\Catalogos;

use App\Livewire\Shared\HasPerPage;
use App\Models\SolicitudTipo;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class SolicitudTiposCrud extends Component
{
    use WithPagination, HasPerPage;

    public ?int $tipoId = null;
    public string $nombre = '';
    public string $titulo_sugerido = '';
    public string $descripcion_sugerida = '';
    public string $prioridad_default = '';
    public string $aplica_para = 'ambos';
    public string $documentos_sugeridos_texto = '';
    public string $configuracion_formulario_texto = '';
    public bool $activo = true;
    public bool $modalFormVisible = false;
    public bool $isEdit = false;
    public bool $confirmingDelete = false;
    public ?int $tipoAEliminar = null;
    public string $search = '';
    public string $sortField = 'nombre';
    public string $sortDirection = 'asc';

    protected function rules(): array
    {
        return [
            'nombre' => [
                'required',
                'string',
                'min:3',
                'max:255',
                Rule::unique('solicitud_tipos', 'nombre')->ignore($this->tipoId),
            ],
            'titulo_sugerido' => ['nullable', 'string', 'max:255'],
            'descripcion_sugerida' => ['nullable', 'string'],
            'prioridad_default' => ['nullable', Rule::in(['baja', 'media', 'alta', 'urgente'])],
            'aplica_para' => ['required', Rule::in(['cliente', 'despacho', 'ambos'])],
            'configuracion_formulario_texto' => ['nullable', 'string'],
            'documentos_sugeridos_texto' => ['nullable', 'string'],
            'activo' => ['boolean'],
        ];
    }

    public function render()
    {
        $query = SolicitudTipo::query()
            ->where(function ($q) {
                $q->where('nombre', 'like', '%' . $this->search . '%')
                    ->orWhere('titulo_sugerido', 'like', '%' . $this->search . '%')
                    ->orWhere('aplica_para', 'like', '%' . $this->search . '%');
            });

        if (in_array($this->sortField, ['nombre', 'aplica_para', 'activo', 'created_at'], true)) {
            $query->orderBy($this->sortField, $this->sortDirection);
        } else {
            $query->orderBy('nombre', 'asc');
        }

        return view('livewire.catalogos.solicitud-tipos-crud', [
            'tipos' => $query->paginate($this->perPageValue($query, 10)),
        ]);
    }

    public function showCreateForm(): void
    {
        $this->resetForm();
        $this->isEdit = false;
        $this->modalFormVisible = true;
    }

    public function showEditForm(int $tipoId): void
    {
        $this->resetForm();

        $tipo = SolicitudTipo::findOrFail($tipoId);

        $this->tipoId = $tipo->id;
        $this->nombre = $tipo->nombre;
        $this->titulo_sugerido = $tipo->titulo_sugerido ?? '';
        $this->descripcion_sugerida = $tipo->descripcion_sugerida ?? '';
        $this->prioridad_default = $tipo->prioridad_default ?? '';
        $this->aplica_para = $tipo->aplica_para;
        $this->documentos_sugeridos_texto = implode(PHP_EOL, $tipo->documentos_sugeridos ?? []);
        $this->configuracion_formulario_texto = $tipo->configuracion_formulario
            ? json_encode($tipo->configuracion_formulario, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
            : '';
        $this->activo = (bool) $tipo->activo;
        $this->isEdit = true;
        $this->modalFormVisible = true;
    }

    public function save(): void
    {
        $this->validate();

        $configuracion = null;
        if (trim($this->configuracion_formulario_texto) !== '') {
            $configuracion = json_decode($this->configuracion_formulario_texto, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->addError('configuracion_formulario_texto', 'El JSON del formulario no es valido.');
                return;
            }
        }

        $documentos = collect(preg_split('/\r\n|\r|\n/', $this->documentos_sugeridos_texto))
            ->map(fn ($item) => trim((string) $item))
            ->filter()
            ->values()
            ->all();

        $payload = [
            'nombre' => $this->nombre,
            'titulo_sugerido' => $this->titulo_sugerido ?: null,
            'descripcion_sugerida' => $this->descripcion_sugerida ?: null,
            'prioridad_default' => $this->prioridad_default ?: null,
            'aplica_para' => $this->aplica_para,
            'documentos_sugeridos' => $documentos ?: null,
            'configuracion_formulario' => $configuracion,
            'activo' => $this->activo,
        ];

        if ($this->isEdit && $this->tipoId) {
            SolicitudTipo::findOrFail($this->tipoId)->update($payload);
        } else {
            SolicitudTipo::create($payload);
        }

        $this->modalFormVisible = false;
        $this->resetForm();
        $this->dispatch('notify', message: 'Tipo de solicitud guardado correctamente.');
    }

    public function toggleActivo(int $id): void
    {
        $tipo = SolicitudTipo::findOrFail($id);
        $tipo->activo = !$tipo->activo;
        $tipo->save();
    }

    public function confirmDelete(int $id): void
    {
        $this->tipoAEliminar = $id;
        $this->confirmingDelete = true;
    }

    public function deleteConfirmed(): void
    {
        $tipo = SolicitudTipo::findOrFail($this->tipoAEliminar);

        if ($tipo->solicitudes()->exists()) {
            $this->dispatch('notify', message: 'No se puede eliminar porque ya tiene solicitudes relacionadas.');
            $this->confirmingDelete = false;
            $this->tipoAEliminar = null;
            return;
        }

        $tipo->delete();
        $this->confirmingDelete = false;
        $this->tipoAEliminar = null;
        $this->dispatch('notify', message: 'Tipo de solicitud eliminado correctamente.');
    }

    public function sortBy(string $field): void
    {
        if (!in_array($field, ['nombre', 'aplica_para', 'activo', 'created_at'], true)) {
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
        $this->reset([
            'tipoId',
            'nombre',
            'titulo_sugerido',
            'descripcion_sugerida',
            'prioridad_default',
            'aplica_para',
            'documentos_sugeridos_texto',
            'configuracion_formulario_texto',
            'activo',
        ]);

        $this->aplica_para = 'ambos';
        $this->activo = true;
        $this->resetValidation();
        $this->resetErrorBag();
    }
}
