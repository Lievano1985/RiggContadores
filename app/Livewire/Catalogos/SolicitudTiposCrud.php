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
    public bool $activo = true;
    public bool $modalFormVisible = false;
    public bool $isEdit = false;
    public bool $confirmingDelete = false;
    public bool $sidebarFormularioVisible = false;
    public bool $modalPreviewVisible = false;
    public ?int $tipoAEliminar = null;
    public ?int $tipoFormularioId = null;
    public string $tipoFormularioNombre = '';
    public string $search = '';
    public string $sortField = 'nombre';
    public string $sortDirection = 'asc';
    public array $formularioCampos = [];
    public array $campoForm = [];
    public ?int $campoEditandoIndex = null;

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
        $this->activo = (bool) $tipo->activo;
        $this->isEdit = true;
        $this->modalFormVisible = true;
    }

    public function save(): void
    {
        $this->validate();

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

    public function abrirFormulario(int $id): void
    {
        $tipo = SolicitudTipo::findOrFail($id);

        $this->tipoFormularioId = $tipo->id;
        $this->tipoFormularioNombre = $tipo->nombre;
        $this->formularioCampos = $tipo->configuracion_formulario['secciones'][0]['campos'] ?? [];
        $this->sidebarFormularioVisible = true;
        $this->resetCampoForm();
    }

    public function cerrarFormulario(): void
    {
        $this->sidebarFormularioVisible = false;
        $this->modalPreviewVisible = false;
        $this->tipoFormularioId = null;
        $this->tipoFormularioNombre = '';
        $this->formularioCampos = [];
        $this->resetCampoForm();
    }

    public function abrirPreview(): void
    {
        $this->modalPreviewVisible = true;
    }

    public function cerrarPreview(): void
    {
        $this->modalPreviewVisible = false;
    }

    public function nuevoCampo(): void
    {
        $this->resetCampoForm();
    }

    public function editarCampo(int $index): void
    {
        $campo = $this->formularioCampos[$index] ?? null;
        if (!$campo) {
            return;
        }

        $this->campoEditandoIndex = $index;
        $this->campoForm = [
            'label' => (string) ($campo['label'] ?? ''),
            'key' => (string) ($campo['key'] ?? ''),
            'type' => (string) ($campo['type'] ?? 'text'),
            'required' => (bool) ($campo['required'] ?? false),
            'placeholder' => (string) ($campo['placeholder'] ?? ''),
            'help' => (string) ($campo['help'] ?? ''),
            'options_text' => isset($campo['options']) ? implode(PHP_EOL, (array) $campo['options']) : '',
            'accept' => (string) ($campo['accept'] ?? ''),
        ];
        $this->resetValidation();
        $this->resetErrorBag();
    }

    public function guardarCampo(): void
    {
        $this->validate([
            'campoForm.label' => ['required', 'string', 'max:255'],
            'campoForm.key' => ['required', 'string', 'max:255', 'regex:/^[a-z0-9_]+$/'],
            'campoForm.type' => ['required', Rule::in(['text', 'textarea', 'number', 'date', 'select', 'checkbox', 'file'])],
            'campoForm.required' => ['boolean'],
            'campoForm.placeholder' => ['nullable', 'string', 'max:255'],
            'campoForm.help' => ['nullable', 'string', 'max:500'],
            'campoForm.options_text' => ['nullable', 'string'],
            'campoForm.accept' => ['nullable', 'string', 'max:255'],
        ], [
            'campoForm.key.regex' => 'La clave solo puede contener letras minusculas, numeros y guion bajo.',
        ]);

        $duplicado = collect($this->formularioCampos)
            ->except($this->campoEditandoIndex !== null ? [$this->campoEditandoIndex] : [])
            ->contains(fn ($campo) => ($campo['key'] ?? null) === $this->campoForm['key']);

        if ($duplicado) {
            $this->addError('campoForm.key', 'La clave ya existe dentro de este formulario.');
            return;
        }

        $campo = [
            'key' => trim($this->campoForm['key']),
            'label' => trim($this->campoForm['label']),
            'type' => $this->campoForm['type'],
            'required' => (bool) $this->campoForm['required'],
        ];

        if (trim((string) $this->campoForm['placeholder']) !== '') {
            $campo['placeholder'] = trim($this->campoForm['placeholder']);
        }

        if (trim((string) $this->campoForm['help']) !== '') {
            $campo['help'] = trim($this->campoForm['help']);
        }

        if ($this->campoForm['type'] === 'select') {
            $options = collect(preg_split('/\r\n|\r|\n/', (string) $this->campoForm['options_text']))
                ->map(fn ($item) => trim((string) $item))
                ->filter()
                ->values()
                ->all();

            if (empty($options)) {
                $this->addError('campoForm.options_text', 'El campo select debe tener opciones.');
                return;
            }

            $campo['options'] = $options;
        }

        if ($this->campoForm['type'] === 'file' && trim((string) $this->campoForm['accept']) !== '') {
            $campo['accept'] = trim($this->campoForm['accept']);
        }

        if ($this->campoEditandoIndex !== null) {
            $this->formularioCampos[$this->campoEditandoIndex] = $campo;
        } else {
            $this->formularioCampos[] = $campo;
        }

        $this->resetCampoForm();
    }

    public function eliminarCampo(int $index): void
    {
        unset($this->formularioCampos[$index]);
        $this->formularioCampos = array_values($this->formularioCampos);

        if ($this->campoEditandoIndex === $index) {
            $this->resetCampoForm();
        }
    }

    public function subirCampo(int $index): void
    {
        if ($index <= 0 || !isset($this->formularioCampos[$index])) {
            return;
        }

        [$this->formularioCampos[$index - 1], $this->formularioCampos[$index]] = [$this->formularioCampos[$index], $this->formularioCampos[$index - 1]];
        $this->formularioCampos = array_values($this->formularioCampos);
    }

    public function bajarCampo(int $index): void
    {
        if (!isset($this->formularioCampos[$index], $this->formularioCampos[$index + 1])) {
            return;
        }

        [$this->formularioCampos[$index + 1], $this->formularioCampos[$index]] = [$this->formularioCampos[$index], $this->formularioCampos[$index + 1]];
        $this->formularioCampos = array_values($this->formularioCampos);
    }

    public function guardarFormulario(): void
    {
        if (!$this->tipoFormularioId) {
            return;
        }

        $configuracion = [
            'version' => 1,
            'secciones' => [
                [
                    'titulo' => 'Datos del formulario',
                    'campos' => array_values($this->formularioCampos),
                ],
            ],
        ];

        SolicitudTipo::findOrFail($this->tipoFormularioId)->update([
            'configuracion_formulario' => $configuracion,
        ]);

        $this->dispatch('notify', message: 'Formulario guardado correctamente.');
        $this->cerrarFormulario();
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
            'activo',
        ]);

        $this->aplica_para = 'ambos';
        $this->activo = true;
        $this->resetValidation();
        $this->resetErrorBag();
    }

    private function resetCampoForm(): void
    {
        $this->campoEditandoIndex = null;
        $this->campoForm = [
            'label' => '',
            'key' => '',
            'type' => 'text',
            'required' => false,
            'placeholder' => '',
            'help' => '',
            'options_text' => '',
            'accept' => '',
        ];
        $this->resetValidation();
        $this->resetErrorBag();
    }
}
