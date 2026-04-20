<?php

namespace App\Livewire\Solicitudes;

use App\Livewire\Shared\HasPerPage;
use App\Models\Cliente;
use App\Models\Obligacion;
use App\Models\ObligacionClienteContador;
use App\Models\Solicitud;
use App\Models\SolicitudTipo;
use App\Models\User;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination, HasPerPage;

    public string $sortField = 'created_at';
    public string $sortDirection = 'desc';
    public string $buscar = '';
    public string $estado = '';
    public string $origen = '';
    public string $responsable = '';
    public bool $sidebarVisible = false;

    public ?int $cliente_id_form = null;
    public ?int $obligacion_id_form = null;
    public string $modo_solicitud_form = 'general';
    public ?int $tipo_solicitud_id_form = null;
    public string $origen_form = 'despacho';
    public string $titulo_form = '';
    public string $descripcion_form = '';
    public string $prioridad_form = '';

    public array $clientesDisponibles = [];
    public array $tiposDisponibles = [];
    public array $obligacionesDisponibles = [];

    protected $paginationTheme = 'tailwind';

    public function mount(): void
    {
        $this->cargarOpcionesFormulario();
    }

    public function updatingBuscar(): void
    {
        $this->resetPage();
    }

    public function updatedEstado(): void
    {
        $this->resetPage();
    }

    public function updatedOrigen(): void
    {
        $this->resetPage();
    }

    public function updatedResponsable(): void
    {
        $this->resetPage();
    }

    public function abrirSidebarCrear(): void
    {
        $this->resetFormulario();
        $this->sidebarVisible = true;
    }

    public function cerrarSidebar(): void
    {
        $this->sidebarVisible = false;
        $this->resetFormulario();
    }

    public function updatedClienteIdForm($value): void
    {
        $this->obligacion_id_form = null;
        $this->cargarObligacionesCliente($value ? (int) $value : null);
    }

    public function updatedModoSolicitudForm(string $value): void
    {
        if ($value === 'general') {
            $this->tipo_solicitud_id_form = null;
        }
    }

    public function updatedTipoSolicitudIdForm($value): void
    {
        if (!$value) {
            return;
        }

        $tipo = SolicitudTipo::query()
            ->where('activo', true)
            ->find($value);

        if (!$tipo) {
            return;
        }

        $this->titulo_form = $tipo->titulo_sugerido ?: $tipo->nombre;
        $this->descripcion_form = $tipo->descripcion_sugerida ?: '';
        $this->prioridad_form = $tipo->prioridad_default ?: '';
    }

    public function guardarSolicitud(): void
    {
        $this->validate([
            'cliente_id_form' => ['required', 'integer'],
            'obligacion_id_form' => ['nullable', 'integer'],
            'modo_solicitud_form' => ['required', Rule::in(['general', 'definida'])],
            'tipo_solicitud_id_form' => ['nullable', 'integer'],
            'origen_form' => ['required', Rule::in(['cliente', 'despacho'])],
            'titulo_form' => ['required', 'string', 'max:255'],
            'descripcion_form' => ['nullable', 'string'],
            'prioridad_form' => ['nullable', Rule::in(['baja', 'media', 'alta', 'urgente'])],
        ]);

        $user = auth()->user();

        $cliente = Cliente::query()
            ->where('despacho_id', $user->despacho_id)
            ->findOrFail($this->cliente_id_form);

        if (!$cliente->responsable_solicitudes_id) {
            $this->addError('cliente_id_form', 'El cliente no tiene responsable de solicitudes asignado.');
            return;
        }

        $obligacionId = null;
        if ($this->obligacion_id_form) {
            $obligacion = Obligacion::query()->find($this->obligacion_id_form);
            if (!$obligacion) {
                $this->addError('obligacion_id_form', 'La obligacion seleccionada no es valida.');
                return;
            }
            $obligacionId = $obligacion->id;
        }

        $tipo = null;
        if ($this->modo_solicitud_form === 'definida') {
            if (!$this->tipo_solicitud_id_form) {
                $this->addError('tipo_solicitud_id_form', 'Selecciona un tipo de solicitud.');
                return;
            }

            $tipo = SolicitudTipo::query()
                ->where('activo', true)
                ->find($this->tipo_solicitud_id_form);

            if (!$tipo) {
                $this->addError('tipo_solicitud_id_form', 'El tipo de solicitud no es valido.');
                return;
            }
        }

        Solicitud::create([
            'cliente_id' => $cliente->id,
            'obligacion_id' => $obligacionId,
            'modo_solicitud' => $this->modo_solicitud_form,
            'tipo_solicitud_id' => $tipo?->id,
            'origen' => $this->origen_form,
            'titulo' => $this->titulo_form,
            'descripcion' => $this->descripcion_form ?: null,
            'datos_formulario' => null,
            'plantilla_snapshot' => $tipo ? [
                'tipo_id' => $tipo->id,
                'nombre' => $tipo->nombre,
                'titulo_sugerido' => $tipo->titulo_sugerido,
                'descripcion_sugerida' => $tipo->descripcion_sugerida,
                'prioridad_default' => $tipo->prioridad_default,
                'documentos_sugeridos' => $tipo->documentos_sugeridos,
                'configuracion_formulario' => $tipo->configuracion_formulario,
            ] : null,
            'estado' => 'abierta',
            'prioridad' => $this->prioridad_form ?: null,
            'responsable_user_id' => $cliente->responsable_solicitudes_id,
            'creado_por_user_id' => $user->id,
        ]);

        $this->cerrarSidebar();
        $this->resetPage();
        $this->dispatch('notify', message: 'Solicitud creada correctamente.');
    }

    public function render()
    {
        $user = auth()->user();

        $query = Solicitud::query()
            ->with(['cliente', 'responsable', 'obligacion'])
            ->whereHas('cliente', function ($q) use ($user) {
                $q->where('despacho_id', $user->despacho_id);
            })
            ->when($this->buscar !== '', function ($q) {
                $buscar = trim($this->buscar);

                $q->where(function ($sub) use ($buscar) {
                    $sub->where('titulo', 'like', "%{$buscar}%")
                        ->orWhere('descripcion', 'like', "%{$buscar}%")
                        ->orWhereHas('cliente', function ($cliente) use ($buscar) {
                            $cliente->where('nombre', 'like', "%{$buscar}%")
                                ->orWhere('razon_social', 'like', "%{$buscar}%")
                                ->orWhere('rfc', 'like', "%{$buscar}%");
                        });
                });
            })
            ->when($this->estado !== '', fn ($q) => $q->where('estado', $this->estado))
            ->when($this->origen !== '', fn ($q) => $q->where('origen', $this->origen))
            ->when($this->responsable !== '', fn ($q) => $q->where('responsable_user_id', $this->responsable));

        if ($this->sortField === 'cliente') {
            $query->orderByRaw("(select coalesce(clientes.nombre, clientes.razon_social) from clientes where clientes.id = solicitudes.cliente_id) {$this->sortDirection}");
        } elseif ($this->sortField === 'responsable') {
            $query->orderBy(
                User::select('name')
                    ->whereColumn('users.id', 'solicitudes.responsable_user_id')
                    ->limit(1),
                $this->sortDirection
            );
        } elseif (in_array($this->sortField, ['titulo', 'estado', 'origen', 'created_at'], true)) {
            $query->orderBy($this->sortField, $this->sortDirection);
        } else {
            $query->latest('created_at');
        }

        $responsables = User::query()
            ->where('despacho_id', $user->despacho_id)
            ->whereNull('cliente_id')
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('livewire.solicitudes.index', [
            'solicitudes' => $query->paginate($this->perPageValue($query, 10)),
            'responsables' => $responsables,
            'responsableClienteSeleccionado' => $this->clienteSeleccionadoResponsable(),
            'tipoSeleccionado' => $this->tipoSeleccionado(),
        ]);
    }

    public function sortBy(string $field): void
    {
        if (!in_array($field, ['cliente', 'titulo', 'estado', 'origen', 'responsable', 'created_at'], true)) {
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

    private function cargarOpcionesFormulario(): void
    {
        $despachoId = auth()->user()->despacho_id;

        $this->clientesDisponibles = Cliente::query()
            ->where('despacho_id', $despachoId)
            ->orderByRaw('coalesce(nombre, razon_social)')
            ->get()
            ->map(fn ($cliente) => [
                'id' => $cliente->id,
                'nombre' => $cliente->nombre ?: $cliente->razon_social,
            ])
            ->toArray();

        $this->tiposDisponibles = SolicitudTipo::query()
            ->where('activo', true)
            ->orderBy('nombre')
            ->get()
            ->map(fn ($tipo) => [
                'id' => $tipo->id,
                'nombre' => $tipo->nombre,
                'aplica_para' => $tipo->aplica_para,
            ])
            ->toArray();
    }

    private function cargarObligacionesCliente(?int $clienteId): void
    {
        if (!$clienteId) {
            $this->obligacionesDisponibles = [];
            return;
        }

        $obligacionIds = ObligacionClienteContador::query()
            ->where('cliente_id', $clienteId)
            ->where('is_activa', true)
            ->whereNotNull('obligacion_id')
            ->distinct()
            ->pluck('obligacion_id');

        $this->obligacionesDisponibles = Obligacion::query()
            ->whereIn('id', $obligacionIds)
            ->orderBy('nombre')
            ->get(['id', 'nombre'])
            ->map(fn ($obligacion) => [
                'id' => $obligacion->id,
                'nombre' => $obligacion->nombre,
            ])
            ->toArray();
    }

    private function resetFormulario(): void
    {
        $this->resetValidation();
        $this->resetErrorBag();

        $this->cliente_id_form = null;
        $this->obligacion_id_form = null;
        $this->modo_solicitud_form = 'general';
        $this->tipo_solicitud_id_form = null;
        $this->origen_form = 'despacho';
        $this->titulo_form = '';
        $this->descripcion_form = '';
        $this->prioridad_form = '';
        $this->obligacionesDisponibles = [];
    }

    private function clienteSeleccionadoResponsable(): ?User
    {
        if (!$this->cliente_id_form) {
            return null;
        }

        return Cliente::query()
            ->with('responsableSolicitudes')
            ->find($this->cliente_id_form)
            ?->responsableSolicitudes;
    }

    private function tipoSeleccionado(): ?SolicitudTipo
    {
        if (!$this->tipo_solicitud_id_form) {
            return null;
        }

        return SolicitudTipo::query()->find($this->tipo_solicitud_id_form);
    }
}
