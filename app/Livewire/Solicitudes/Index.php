<?php

namespace App\Livewire\Solicitudes;

use App\Livewire\Shared\HasPerPage;
use App\Models\ArchivoAdjunto;
use App\Models\Cliente;
use App\Models\ObligacionClienteContador;
use App\Models\Solicitud;
use App\Models\SolicitudRequerimiento;
use App\Models\SolicitudTipo;
use App\Models\User;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination, HasPerPage;

    public string $vistaSolicitudes = 'todas';
    public string $sortField = 'created_at';
    public string $sortDirection = 'desc';
    public string $buscar = '';
    public string $estado = '';
    public string $origen = '';
    public string $responsable = '';
    public bool $sidebarVisible = false;
    public bool $editandoSolicitud = false;
    public ?int $solicitudEditandoId = null;
    public bool $confirmarCancelacion = false;
    public ?int $solicitudCancelarId = null;
    public bool $detalleSidebarVisible = false;
    public ?int $solicitudDetalleId = null;

    public ?int $cliente_id_form = null;
    public string $relacion_obligacion_form = 'sin_relacion';
    public ?int $periodo_anio_form = null;
    public ?int $periodo_mes_form = null;
    public ?int $obligacion_cliente_contador_id_form = null;
    public string $modo_solicitud_form = 'general';
    public ?int $tipo_solicitud_id_form = null;
    public string $origen_form = 'despacho';
    public string $titulo_form = '';
    public string $descripcion_form = '';
    public string $prioridad_form = '';
    public bool $requerimientoFormVisible = false;
    public bool $editandoRequerimiento = false;
    public ?int $requerimientoEditandoId = null;
    public bool $confirmarEliminarRequerimiento = false;
    public ?int $requerimientoEliminarId = null;
    public array $mostrarRechazoRequerimiento = [];
    public array $comentarioRechazoRequerimiento = [];
    public string $requerimiento_destinatario_tipo = 'cliente';
    public ?int $requerimiento_destinatario_user_id = null;
    public string $requerimiento_titulo = '';
    public string $requerimiento_descripcion = '';
    public string $requerimiento_fecha_limite = '';

    public array $clientesDisponibles = [];
    public array $tiposDisponibles = [];
    public array $obligacionesDisponibles = [];

    protected $paginationTheme = 'tailwind';
    protected $listeners = [
        'adjuntos-actualizados' => '$refresh',
        'requerimiento-actualizado' => '$refresh',
    ];

    public function mount(): void
    {
        if (request()->routeIs('solicitudes.create')) {
            $this->vistaSolicitudes = 'crear';
            $this->abrirSidebarCrear();
        } elseif (request()->routeIs('solicitudes.asignadas')) {
            $this->vistaSolicitudes = 'asignadas';
        }

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
        $this->editandoSolicitud = false;
        $this->solicitudEditandoId = null;
        $this->sidebarVisible = true;
    }

    public function cerrarSidebar(): void
    {
        $this->sidebarVisible = false;
        $this->resetFormulario();
        $this->editandoSolicitud = false;
        $this->solicitudEditandoId = null;
    }

    public function editarSolicitud(int $solicitudId): void
    {
        $user = auth()->user();

        $solicitud = Solicitud::query()
            ->with('cliente')
            ->whereKey($solicitudId)
            ->whereHas('cliente', function ($q) use ($user) {
                $q->where('despacho_id', $user->despacho_id);
            })
            ->first();

        if (!$solicitud) {
            return;
        }

        $this->resetFormulario();
        $this->editandoSolicitud = true;
        $this->solicitudEditandoId = $solicitud->id;
        $this->cliente_id_form = $solicitud->cliente_id;
        $this->modo_solicitud_form = $solicitud->modo_solicitud;
        $this->tipo_solicitud_id_form = $solicitud->tipo_solicitud_id;
        $this->origen_form = $solicitud->origen;
        $this->titulo_form = $solicitud->titulo;
        $this->descripcion_form = $solicitud->descripcion ?? '';
        $this->prioridad_form = $solicitud->prioridad ?? '';

        if ($solicitud->obligacion_cliente_contador_id && $solicitud->obligacionClienteContador) {
            $this->relacion_obligacion_form = 'con_relacion';
            $this->periodo_anio_form = $solicitud->obligacionClienteContador->ejercicio;
            $this->periodo_mes_form = $solicitud->obligacionClienteContador->mes;
            $this->cargarObligacionesPeriodo($solicitud->cliente_id, $this->periodo_anio_form, $this->periodo_mes_form);
            $this->obligacion_cliente_contador_id_form = $solicitud->obligacion_cliente_contador_id;
        }

        $this->sidebarVisible = true;
    }

    public function abrirDetalle(int $solicitudId): void
    {
        $user = auth()->user();

        $solicitud = Solicitud::query()
            ->whereKey($solicitudId)
            ->whereHas('cliente', function ($q) use ($user) {
                $q->where('despacho_id', $user->despacho_id);
            })
            ->first();

        if (!$solicitud) {
            return;
        }

        $this->solicitudDetalleId = $solicitud->id;
        $this->detalleSidebarVisible = true;
    }

    public function cerrarDetalle(): void
    {
        $this->detalleSidebarVisible = false;
        $this->solicitudDetalleId = null;
        $this->resetFormularioRequerimiento();
    }

    public function confirmarCancelacionSolicitud(int $solicitudId): void
    {
        $user = auth()->user();

        $solicitud = Solicitud::query()
            ->whereKey($solicitudId)
            ->whereNotIn('estado', ['cerrada', 'cancelada'])
            ->whereHas('cliente', function ($q) use ($user) {
                $q->where('despacho_id', $user->despacho_id);
            })
            ->first();

        if (!$solicitud) {
            return;
        }

        $this->solicitudCancelarId = $solicitud->id;
        $this->confirmarCancelacion = true;
    }

    public function cancelarSolicitudConfirmada(): void
    {
        $user = auth()->user();

        $solicitud = Solicitud::query()
            ->whereKey($this->solicitudCancelarId)
            ->whereNotIn('estado', ['cerrada', 'cancelada'])
            ->whereHas('cliente', function ($q) use ($user) {
                $q->where('despacho_id', $user->despacho_id);
            })
            ->first();

        if (!$solicitud) {
            $this->confirmarCancelacion = false;
            $this->solicitudCancelarId = null;
            return;
        }

        $solicitud->update([
            'estado' => 'cancelada',
            'cerrado_por_user_id' => $user->id,
            'comentario_cierre' => $solicitud->comentario_cierre ?: 'Solicitud cancelada desde bandeja.',
            'cerrada_at' => now(),
        ]);

        if ($this->solicitudDetalleId === $solicitud->id) {
            $this->solicitudDetalleId = $solicitud->id;
        }

        $this->confirmarCancelacion = false;
        $this->solicitudCancelarId = null;
        $this->dispatch('notify', message: 'Solicitud cancelada correctamente.');
    }

    public function abrirFormularioRequerimiento(): void
    {
        if (!$this->solicitudDetalleId) {
            return;
        }

        $this->resetFormularioRequerimiento();
        $this->editandoRequerimiento = false;
        $this->requerimientoEditandoId = null;
        $this->requerimientoFormVisible = true;
    }

    public function cerrarFormularioRequerimiento(): void
    {
        $this->requerimientoFormVisible = false;
        $this->resetFormularioRequerimiento();
        $this->editandoRequerimiento = false;
        $this->requerimientoEditandoId = null;
    }

    public function updatedRequerimientoDestinatarioTipo(string $value): void
    {
        if ($value === 'cliente') {
            $this->requerimiento_destinatario_user_id = null;
        }
    }

    public function guardarRequerimiento(): void
    {
        $solicitud = $this->solicitudDetalle();

        if (!$solicitud) {
            return;
        }

        $this->validate([
            'requerimiento_destinatario_tipo' => ['required', Rule::in(['cliente', 'interno'])],
            'requerimiento_destinatario_user_id' => ['nullable', 'integer'],
            'requerimiento_titulo' => ['required', 'string', 'max:255'],
            'requerimiento_descripcion' => ['nullable', 'string'],
            'requerimiento_fecha_limite' => ['nullable', 'date'],
        ]);

        $user = auth()->user();
        $destinatarioUserId = null;

        if ($this->requerimiento_destinatario_tipo === 'interno') {
            $destinatario = User::query()
                ->where('despacho_id', $solicitud->cliente->despacho_id)
                ->whereNull('cliente_id')
                ->find($this->requerimiento_destinatario_user_id);

            if (!$destinatario) {
                $this->addError('requerimiento_destinatario_user_id', 'Selecciona un usuario interno valido.');
                return;
            }

            $destinatarioUserId = $destinatario->id;
        }

        $payload = [
            'solicitud_id' => $solicitud->id,
            'destinatario_tipo' => $this->requerimiento_destinatario_tipo,
            'destinatario_user_id' => $destinatarioUserId,
            'titulo' => $this->requerimiento_titulo,
            'descripcion' => $this->requerimiento_descripcion ?: null,
            'fecha_limite' => $this->requerimiento_fecha_limite ?: null,
        ];

        if ($this->editandoRequerimiento && $this->requerimientoEditandoId) {
            $requerimiento = $solicitud->requerimientos()
                ->find($this->requerimientoEditandoId);

            if (!$requerimiento) {
                return;
            }

            $requerimiento->update($payload);
            $mensaje = 'Requerimiento actualizado correctamente.';
        } else {
            $payload['creado_por_user_id'] = $user->id;
            $payload['estado'] = 'abierto';
            SolicitudRequerimiento::create($payload);
            $mensaje = 'Requerimiento creado correctamente.';
        }

        $this->cerrarFormularioRequerimiento();
        $this->dispatch('requerimiento-actualizado');
        $this->dispatch('notify', message: $mensaje);
    }

    public function editarRequerimiento(int $requerimientoId): void
    {
        $solicitud = $this->solicitudDetalle();

        if (!$solicitud) {
            return;
        }

        $requerimiento = $solicitud->requerimientos()
            ->find($requerimientoId);

        if (!$requerimiento) {
            return;
        }

        $this->resetFormularioRequerimiento();
        $this->editandoRequerimiento = true;
        $this->requerimientoEditandoId = $requerimiento->id;
        $this->requerimiento_destinatario_tipo = $requerimiento->destinatario_tipo;
        $this->requerimiento_destinatario_user_id = $requerimiento->destinatario_user_id;
        $this->requerimiento_titulo = $requerimiento->titulo;
        $this->requerimiento_descripcion = $requerimiento->descripcion ?? '';
        $this->requerimiento_fecha_limite = $requerimiento->fecha_limite?->format('Y-m-d') ?? '';
        $this->requerimientoFormVisible = true;
    }

    public function confirmarEliminarRequerimiento(int $requerimientoId): void
    {
        $solicitud = $this->solicitudDetalle();

        if (!$solicitud || !$solicitud->requerimientos()->whereKey($requerimientoId)->exists()) {
            return;
        }

        $this->requerimientoEliminarId = $requerimientoId;
        $this->confirmarEliminarRequerimiento = true;
    }

    public function eliminarRequerimientoConfirmado(): void
    {
        $solicitud = $this->solicitudDetalle();

        if (!$solicitud) {
            $this->confirmarEliminarRequerimiento = false;
            $this->requerimientoEliminarId = null;
            return;
        }

        $requerimiento = $solicitud->requerimientos()
            ->with('archivos')
            ->find($this->requerimientoEliminarId);

        if (!$requerimiento) {
            $this->confirmarEliminarRequerimiento = false;
            $this->requerimientoEliminarId = null;
            return;
        }

        foreach ($requerimiento->archivos as $archivo) {
            if ($archivo->archivo) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($archivo->archivo);
            }

            $archivo->delete();
        }

        $requerimiento->delete();

        if ($this->requerimientoEditandoId === $requerimiento->id) {
            $this->cerrarFormularioRequerimiento();
        }

        $this->confirmarEliminarRequerimiento = false;
        $this->requerimientoEliminarId = null;
        $this->dispatch('requerimiento-actualizado');
        $this->dispatch('notify', message: 'Requerimiento eliminado correctamente.');
    }

    public function mostrarRechazoRespuesta(int $requerimientoId): void
    {
        $requerimiento = $this->requerimientoDesdeDetalle($requerimientoId);

        if (!$requerimiento || !$this->usuarioPuedeValidarRequerimiento($requerimiento) || $requerimiento->estado !== 'respondido') {
            return;
        }

        $this->mostrarRechazoRequerimiento[$requerimientoId] = true;
        $this->comentarioRechazoRequerimiento[$requerimientoId] = $requerimiento->comentario_validacion ?? '';
    }

    public function cancelarRechazoRespuesta(int $requerimientoId): void
    {
        $this->mostrarRechazoRequerimiento[$requerimientoId] = false;
        $this->comentarioRechazoRequerimiento[$requerimientoId] = '';
    }

    public function validarRespuestaRequerimiento(int $requerimientoId): void
    {
        $requerimiento = $this->requerimientoDesdeDetalle($requerimientoId);

        if (!$requerimiento || !$this->usuarioPuedeValidarRequerimiento($requerimiento) || $requerimiento->estado !== 'respondido') {
            return;
        }

        $requerimiento->update([
            'estado' => 'validado',
            'validado_por_user_id' => auth()->id(),
            'validado_at' => now(),
            'comentario_validacion' => null,
        ]);

        $this->dispatch('requerimiento-actualizado');
        $this->dispatch('notify', message: 'Respuesta validada correctamente.');
    }

    public function rechazarRespuestaRequerimiento(int $requerimientoId): void
    {
        $requerimiento = $this->requerimientoDesdeDetalle($requerimientoId);

        if (!$requerimiento || !$this->usuarioPuedeValidarRequerimiento($requerimiento) || $requerimiento->estado !== 'respondido') {
            return;
        }

        $comentario = trim((string) ($this->comentarioRechazoRequerimiento[$requerimientoId] ?? ''));

        if ($comentario === '') {
            $this->addError("comentarioRechazoRequerimiento.$requerimientoId", 'El comentario de rechazo es obligatorio.');
            return;
        }

        $requerimiento->update([
            'estado' => 'rechazado',
            'validado_por_user_id' => auth()->id(),
            'validado_at' => now(),
            'comentario_validacion' => $comentario,
        ]);

        $this->mostrarRechazoRequerimiento[$requerimientoId] = false;
        $this->comentarioRechazoRequerimiento[$requerimientoId] = $comentario;
        $this->dispatch('requerimiento-actualizado');
        $this->dispatch('notify', message: 'Respuesta rechazada correctamente.');
    }

    public function updatedClienteIdForm($value): void
    {
        $this->obligacion_cliente_contador_id_form = null;
        $this->cargarObligacionesPeriodo($value ? (int) $value : null, $this->periodo_anio_form, $this->periodo_mes_form);
    }

    public function updatedRelacionObligacionForm(string $value): void
    {
        $this->obligacion_cliente_contador_id_form = null;

        if ($value === 'sin_relacion') {
            $this->periodo_anio_form = null;
            $this->periodo_mes_form = null;
            $this->obligacionesDisponibles = [];
            return;
        }

        $this->periodo_anio_form ??= now()->year;
        $this->periodo_mes_form ??= now()->month;

        $this->cargarObligacionesPeriodo($this->cliente_id_form, $this->periodo_anio_form, $this->periodo_mes_form);
    }

    public function updatedPeriodoAnioForm($value): void
    {
        $this->obligacion_cliente_contador_id_form = null;
        $this->cargarObligacionesPeriodo($this->cliente_id_form, $value ? (int) $value : null, $this->periodo_mes_form);
    }

    public function updatedPeriodoMesForm($value): void
    {
        $this->obligacion_cliente_contador_id_form = null;
        $this->cargarObligacionesPeriodo($this->cliente_id_form, $this->periodo_anio_form, $value ? (int) $value : null);
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
            'relacion_obligacion_form' => ['required', Rule::in(['sin_relacion', 'con_relacion'])],
            'periodo_anio_form' => ['nullable', 'integer', 'min:2020', 'max:2100'],
            'periodo_mes_form' => ['nullable', 'integer', 'min:1', 'max:12'],
            'obligacion_cliente_contador_id_form' => ['nullable', 'integer'],
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

        $obligacionRelacionada = null;
        if ($this->relacion_obligacion_form === 'con_relacion') {
            if (!$this->periodo_anio_form || !$this->periodo_mes_form) {
                $this->addError('periodo_mes_form', 'Selecciona el periodo de la obligacion.');
                return;
            }

            if (!$this->obligacion_cliente_contador_id_form) {
                $this->addError('obligacion_cliente_contador_id_form', 'Selecciona una obligacion del periodo.');
                return;
            }

            $obligacionRelacionada = ObligacionClienteContador::query()
                ->with('obligacion')
                ->where('cliente_id', $cliente->id)
                ->where('ejercicio', $this->periodo_anio_form)
                ->where('mes', $this->periodo_mes_form)
                ->where('is_activa', true)
                ->find($this->obligacion_cliente_contador_id_form);

            if (!$obligacionRelacionada) {
                $this->addError('obligacion_cliente_contador_id_form', 'La obligacion seleccionada no es valida.');
                return;
            }
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

        $payload = [
            'cliente_id' => $cliente->id,
            'obligacion_id' => $obligacionRelacionada?->obligacion_id,
            'obligacion_cliente_contador_id' => $obligacionRelacionada?->id,
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
        ];

        if ($this->editandoSolicitud && $this->solicitudEditandoId) {
            $solicitud = Solicitud::query()
                ->whereKey($this->solicitudEditandoId)
                ->whereHas('cliente', function ($q) use ($user) {
                    $q->where('despacho_id', $user->despacho_id);
                })
                ->first();

            if (!$solicitud) {
                return;
            }

            $solicitud->update($payload);
            $mensaje = 'Solicitud actualizada correctamente.';
        } else {
            $payload['creado_por_user_id'] = $user->id;
            Solicitud::create($payload);
            $mensaje = 'Solicitud creada correctamente.';
        }

        $this->cerrarSidebar();
        $this->resetPage();
        $this->dispatch('notify', message: $mensaje);
    }

    public function render()
    {
        $user = auth()->user();

        $query = Solicitud::query()
            ->with(['cliente', 'responsable', 'obligacion', 'obligacionClienteContador.obligacion'])
            ->whereHas('cliente', function ($q) use ($user) {
                $q->where('despacho_id', $user->despacho_id);
            })
            ->when($this->vistaSolicitudes === 'asignadas', fn ($q) => $q->where('responsable_user_id', $user->id))
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
            'solicitudDetalle' => $this->solicitudDetalle(),
            'usuariosInternosRequerimiento' => $this->usuariosInternosRequerimiento(),
            'tituloModulo' => $this->vistaSolicitudes === 'asignadas' ? 'Solicitudes asignadas' : 'Solicitudes',
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

    private function cargarObligacionesPeriodo(?int $clienteId, ?int $anio, ?int $mes): void
    {
        if (!$clienteId || !$anio || !$mes || $this->relacion_obligacion_form !== 'con_relacion') {
            $this->obligacionesDisponibles = [];
            return;
        }

        $this->obligacionesDisponibles = ObligacionClienteContador::query()
            ->where('cliente_id', $clienteId)
            ->where('is_activa', true)
            ->where('ejercicio', $anio)
            ->where('mes', $mes)
            ->with('obligacion:id,nombre')
            ->orderByRaw('(select nombre from obligaciones where obligaciones.id = obligacion_cliente_contador.obligacion_id)')
            ->get()
            ->map(fn ($obligacionAsignada) => [
                'id' => $obligacionAsignada->id,
                'nombre' => $obligacionAsignada->obligacion?->nombre ?? 'Sin obligacion',
            ])
            ->toArray();
    }

    private function resetFormulario(): void
    {
        $this->resetValidation();
        $this->resetErrorBag();

        $this->cliente_id_form = null;
        $this->relacion_obligacion_form = 'sin_relacion';
        $this->periodo_anio_form = null;
        $this->periodo_mes_form = null;
        $this->obligacion_cliente_contador_id_form = null;
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

    private function solicitudDetalle(): ?Solicitud
    {
        if (!$this->solicitudDetalleId) {
            return null;
        }

        $user = auth()->user();

        return Solicitud::query()
            ->with([
                'cliente',
                'responsable',
                'creadoPor',
                'tipoSolicitud',
                'obligacion',
                'obligacionClienteContador.obligacion',
                'requerimientos.creadoPor',
                'requerimientos.destinatario',
                'requerimientos.respondidoPor',
                'requerimientos.archivos',
            ])
            ->whereKey($this->solicitudDetalleId)
            ->whereHas('cliente', function ($q) use ($user) {
                $q->where('despacho_id', $user->despacho_id);
            })
            ->first();
    }

    private function usuariosInternosRequerimiento()
    {
        $solicitud = $this->solicitudDetalle();

        if (!$solicitud) {
            return collect();
        }

        return User::query()
            ->where('despacho_id', $solicitud->cliente->despacho_id)
            ->whereNull('cliente_id')
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    private function resetFormularioRequerimiento(): void
    {
        $this->resetErrorBag([
            'requerimiento_destinatario_tipo',
            'requerimiento_destinatario_user_id',
            'requerimiento_titulo',
            'requerimiento_descripcion',
            'requerimiento_fecha_limite',
        ]);

        $this->requerimiento_destinatario_tipo = 'cliente';
        $this->requerimiento_destinatario_user_id = null;
        $this->requerimiento_titulo = '';
        $this->requerimiento_descripcion = '';
        $this->requerimiento_fecha_limite = '';
        $this->mostrarRechazoRequerimiento = [];
        $this->comentarioRechazoRequerimiento = [];
    }

    private function requerimientoDesdeDetalle(int $requerimientoId): ?SolicitudRequerimiento
    {
        $solicitud = $this->solicitudDetalle();

        if (!$solicitud) {
            return null;
        }

        return $solicitud->requerimientos()->find($requerimientoId);
    }

    private function usuarioPuedeValidarRequerimiento(SolicitudRequerimiento $requerimiento): bool
    {
        return (int) $requerimiento->solicitud->responsable_user_id === (int) auth()->id();
    }
}
