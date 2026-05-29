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
use App\Services\SolicitudHistorialService;
use App\Services\SolicitudNotificacionService;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination, HasPerPage, WithFileUploads;

    public string $vistaSolicitudes = 'todas';
    public string $sortField = 'created_at';
    public string $sortDirection = 'desc';
    public string $buscar = '';
    public string $estado = 'activos';
    public string $origen = '';
    public string $responsable = '';
    public bool $sidebarVisible = false;
    public bool $editandoSolicitud = false;
    public ?int $solicitudEditandoId = null;
    public bool $confirmarCancelacion = false;
    public ?int $solicitudCancelarId = null;
    public bool $confirmarCierre = false;
    public ?int $solicitudCerrarId = null;
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
    public string $fecha_resultado_form = '';
    public array $solicitud_archivos_form = [];
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
    public array $respuestaResultado = [];
    public array $formularioResultado = [];

    public array $clientesDisponibles = [];
    public array $tiposDisponibles = [];
    public array $obligacionesDisponibles = [];

    protected $paginationTheme = 'tailwind';
    protected $listeners = [
        'adjuntos-actualizados' => '$refresh',
        'requerimiento-actualizado' => '$refresh',
        'archivos-ok-resultado' => 'continuarGuardadoResultado',
        'archivos-error-resultado' => 'cancelarGuardadoResultado',
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
        if (!$this->usuarioPuedeCrearSolicitud()) {
            return;
        }

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
            ->with(['cliente', 'resultadoRequerimiento'])
            ->whereKey($solicitudId)
            ->where($this->scopeSolicitudesUsuario($user))
            ->first();

        if (!$solicitud || !$this->usuarioPuedeEditarSolicitud($solicitud)) {
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
        $this->fecha_resultado_form = $solicitud->resultadoRequerimiento?->fecha_limite?->format('Y-m-d') ?? '';

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
            ->where($this->scopeSolicitudesUsuario($user))
            ->first();

        if (!$solicitud) {
            return;
        }

        $this->solicitudDetalleId = $solicitud->id;
        $this->respuestaResultado = SolicitudRequerimiento::query()
            ->where('solicitud_id', $solicitud->id)
            ->where('tipo', 'resultado')
            ->pluck('respuesta_texto', 'id')
            ->filter(fn ($valor) => $valor !== null)
            ->toArray();
        $this->formularioResultado = $this->mapearFormularioResultadoSolicitud($solicitud);
        $this->detalleSidebarVisible = true;
    }

    public function cerrarDetalle(): void
    {
        $this->detalleSidebarVisible = false;
        $this->solicitudDetalleId = null;
        $this->resetFormularioRequerimiento();
        $this->respuestaResultado = [];
        $this->formularioResultado = [];
    }

    public function confirmarCancelacionSolicitud(int $solicitudId): void
    {
        $user = auth()->user();

        $solicitud = Solicitud::query()
            ->whereKey($solicitudId)
            ->whereNotIn('estado', ['cerrada', 'cancelada'])
            ->where($this->scopeSolicitudesUsuario($user))
            ->first();

        if (!$solicitud || !$this->usuarioPuedeCancelarSolicitud($solicitud)) {
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
            ->where($this->scopeSolicitudesUsuario($user))
            ->first();

        if (!$solicitud || !$this->usuarioPuedeCancelarSolicitud($solicitud)) {
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

        SolicitudHistorialService::registrar(
            $solicitud,
            'solicitud_cancelada',
            'Solicitud cancelada',
            $solicitud->comentario_cierre ?: 'Solicitud cancelada desde bandeja.',
            $user->id
        );

        if ($this->solicitudDetalleId === $solicitud->id) {
            $this->solicitudDetalleId = $solicitud->id;
        }

        $this->confirmarCancelacion = false;
        $this->solicitudCancelarId = null;
        $this->dispatch('notify', message: 'Solicitud cancelada correctamente.');
    }

    public function confirmarCierreSolicitud(int $solicitudId): void
    {
        $user = auth()->user();

        $solicitud = Solicitud::query()
            ->with('requerimientos')
            ->whereKey($solicitudId)
            ->whereNotIn('estado', ['cerrada', 'cancelada'])
            ->where($this->scopeSolicitudesUsuario($user))
            ->first();

        if (!$solicitud) {
            return;
        }

        if (!$this->usuarioPuedeCerrarSolicitud($solicitud)) {
            return;
        }

        if ($this->solicitudTieneRequerimientosPendientes($solicitud)) {
            $this->dispatch('notify', message: 'No puedes cerrar la solicitud mientras existan requerimientos pendientes.');
            return;
        }

        $this->solicitudCerrarId = $solicitud->id;
        $this->confirmarCierre = true;
    }

    public function cerrarSolicitudConfirmada(): void
    {
        $user = auth()->user();

        $solicitud = Solicitud::query()
            ->with('requerimientos')
            ->whereKey($this->solicitudCerrarId)
            ->whereNotIn('estado', ['cerrada', 'cancelada'])
            ->where($this->scopeSolicitudesUsuario($user))
            ->first();

        if (!$solicitud) {
            $this->confirmarCierre = false;
            $this->solicitudCerrarId = null;
            return;
        }

        if (!$this->usuarioPuedeCerrarSolicitud($solicitud)) {
            $this->confirmarCierre = false;
            $this->solicitudCerrarId = null;
            return;
        }

        if ($this->solicitudTieneRequerimientosPendientes($solicitud)) {
            $this->confirmarCierre = false;
            $this->solicitudCerrarId = null;
            $this->dispatch('notify', message: 'No puedes cerrar la solicitud mientras existan requerimientos pendientes.');
            return;
        }

        $solicitud->update([
            'estado' => 'cerrada',
            'cerrado_por_user_id' => $user->id,
            'comentario_cierre' => $solicitud->comentario_cierre ?: 'Solicitud cerrada desde bandeja.',
            'cerrada_at' => now(),
        ]);

        SolicitudHistorialService::registrar(
            $solicitud,
            'solicitud_cerrada',
            'Solicitud cerrada',
            $solicitud->comentario_cierre ?: 'Solicitud cerrada desde bandeja.',
            $user->id
        );

        SolicitudNotificacionService::notificarSolicitudCerrada($solicitud);

        $this->confirmarCierre = false;
        $this->solicitudCerrarId = null;
        $this->dispatch('notify', message: 'Solicitud cerrada correctamente.');
    }

    public function abrirFormularioRequerimiento(): void
    {
        $solicitud = $this->solicitudDetalle();

        if (!$solicitud || !$this->usuarioPuedeOperarRequerimientos($solicitud)) {
            return;
        }

        $this->resetFormularioRequerimiento();
        $this->editandoRequerimiento = false;
        $this->requerimientoEditandoId = null;
        $this->requerimientoFormVisible = true;
        $this->dispatch('enfocar-formulario-requerimiento');
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

        if (!$solicitud || !$this->usuarioPuedeOperarRequerimientos($solicitud)) {
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
            'tipo' => 'normal',
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
            SolicitudHistorialService::registrar(
                $solicitud,
                'requerimiento_actualizado',
                'Requerimiento actualizado',
                'Se actualizo el requerimiento "' . $requerimiento->titulo . '".',
                $user->id,
                $requerimiento
            );
            $mensaje = 'Requerimiento actualizado correctamente.';
        } else {
            $payload['creado_por_user_id'] = $user->id;
            $payload['estado'] = 'abierto';
            $requerimiento = SolicitudRequerimiento::create($payload);
            SolicitudHistorialService::registrar(
                $solicitud,
                'requerimiento_creado',
                'Requerimiento creado',
                'Se creo el requerimiento "' . $requerimiento->titulo . '".',
                $user->id,
                $requerimiento
            );
            SolicitudNotificacionService::notificarRequerimientoCreado($requerimiento);
            $mensaje = 'Requerimiento creado correctamente.';
        }

        $this->cerrarFormularioRequerimiento();
        $this->dispatch('requerimiento-actualizado');
        $this->dispatch('notify', message: $mensaje);
    }

    public function editarRequerimiento(int $requerimientoId): void
    {
        $solicitud = $this->solicitudDetalle();

        if (!$solicitud || !$this->usuarioPuedeOperarRequerimientos($solicitud)) {
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
        $this->dispatch('enfocar-formulario-requerimiento');
    }

    public function confirmarEliminarRequerimiento(int $requerimientoId): void
    {
        $solicitud = $this->solicitudDetalle();

        if (!$solicitud || !$this->usuarioPuedeOperarRequerimientos($solicitud) || !$solicitud->requerimientos()->whereKey($requerimientoId)->exists()) {
            return;
        }

        $this->requerimientoEliminarId = $requerimientoId;
        $this->confirmarEliminarRequerimiento = true;
    }

    public function eliminarRequerimientoConfirmado(): void
    {
        $solicitud = $this->solicitudDetalle();

        if (!$solicitud || !$this->usuarioPuedeOperarRequerimientos($solicitud)) {
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

        SolicitudHistorialService::registrar(
            $solicitud,
            'requerimiento_eliminado',
            'Requerimiento eliminado',
            'Se elimino el requerimiento "' . $requerimiento->titulo . '".',
            auth()->id(),
            $requerimiento
        );

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

        if ($requerimiento->tipo === 'resultado') {
            $requerimiento->solicitud->update([
                'estado' => 'resuelto',
            ]);
            if ($this->resultadoUsaFormulario($requerimiento)) {
                $requerimiento->solicitud->update([
                    'estado_formulario' => 'validado',
                ]);
            }

            $this->cerrarSolicitudPorRequerimientoPrincipalValidado(
                $requerimiento->solicitud,
                $requerimiento,
                'Solicitud cerrada al validar el resultado final.'
            );
        } elseif ($requerimiento->esRequerimientoFormulario()) {
            $requerimiento->solicitud->update([
                'estado_formulario' => 'validado',
                'estado' => $requerimiento->solicitud->usaFormularioComoCierre() ? 'resuelto' : $requerimiento->solicitud->estado,
            ]);

            if ($requerimiento->solicitud->usaFormularioComoCierre()) {
                $this->cerrarSolicitudPorRequerimientoPrincipalValidado(
                    $requerimiento->solicitud,
                    $requerimiento,
                    'Solicitud cerrada al validar el formulario definido.'
                );
            }
        }

        SolicitudHistorialService::registrar(
            $requerimiento->solicitud,
            $requerimiento->tipo === 'resultado' ? 'resultado_validado' : 'requerimiento_validado',
            $requerimiento->tipo === 'resultado' ? 'Resultado validado' : 'Respuesta validada',
            $requerimiento->tipo === 'resultado'
                ? 'Se valido el resultado final de la solicitud.'
                : 'Se valido la respuesta del requerimiento "' . $requerimiento->titulo . '".',
            auth()->id(),
            $requerimiento
        );

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

        if ($requerimiento->tipo === 'resultado') {
            $requerimiento->solicitud->update([
                'estado' => 'en_proceso',
            ]);
            if ($this->resultadoUsaFormulario($requerimiento)) {
                $requerimiento->solicitud->update([
                    'estado_formulario' => 'pendiente',
                ]);
            }
        } elseif ($requerimiento->esRequerimientoFormulario()) {
            $requerimiento->solicitud->update([
                'estado_formulario' => 'pendiente',
                'estado' => 'en_proceso',
            ]);
        }

        SolicitudHistorialService::registrar(
            $requerimiento->solicitud,
            $requerimiento->tipo === 'resultado' ? 'resultado_rechazado' : 'requerimiento_rechazado',
            $requerimiento->tipo === 'resultado' ? 'Resultado rechazado' : 'Respuesta rechazada',
            $comentario,
            auth()->id(),
            $requerimiento
        );

        SolicitudNotificacionService::notificarRechazo($requerimiento);

        $this->mostrarRechazoRequerimiento[$requerimientoId] = false;
        $this->comentarioRechazoRequerimiento[$requerimientoId] = $comentario;
        $this->dispatch('requerimiento-actualizado');
        $this->dispatch('notify', message: 'Respuesta rechazada correctamente.');
    }

    public function guardarRespuestaResultado(int $requerimientoId): void
    {
        $requerimiento = $this->requerimientoDesdeDetalle($requerimientoId);

        if (!$requerimiento || $requerimiento->tipo !== 'resultado' || !$this->usuarioPuedeResponderResultado($requerimiento)) {
            return;
        }

        $this->validate([
            "respuestaResultado.$requerimientoId" => ['required', 'string'],
        ]);

        if ($this->resultadoUsaFormulario($requerimiento)) {
            $this->validarFormularioResultado($requerimiento->solicitud);
        }

        $this->dispatch('guardar-archivos-adjuntos', origen: 'resultado');
    }

    public function continuarGuardadoResultado(): void
    {
        $requerimiento = $this->requerimientoResultadoDesdeDetalle();

        if (!$requerimiento || !$this->usuarioPuedeResponderResultado($requerimiento)) {
            return;
        }

        $this->validate([
            "respuestaResultado.$requerimiento->id" => ['required', 'string'],
        ]);

        if ($this->resultadoUsaFormulario($requerimiento)) {
            $this->validarFormularioResultado($requerimiento->solicitud);

            $datosFormulario = $this->normalizarFormularioResultado($requerimiento->solicitud);
            $archivosFormulario = $this->guardarArchivosFormularioResultado($requerimiento->solicitud);

            if (!empty($archivosFormulario)) {
                $datosFormulario = array_replace($datosFormulario, $archivosFormulario);
            }

            $requerimiento->solicitud->update([
                'datos_formulario' => $datosFormulario,
                'estado_formulario' => 'respondido',
            ]);
        }

        $requerimiento->update([
            'respuesta_texto' => $this->respuestaResultado[$requerimiento->id],
            'respondido_por_user_id' => auth()->id(),
            'respondido_at' => now(),
            'estado' => 'respondido',
            'comentario_validacion' => null,
            'validado_por_user_id' => null,
            'validado_at' => null,
        ]);

        $requerimiento->solicitud->update([
            'estado' => 'pendiente_cliente',
        ]);

        SolicitudHistorialService::registrar(
            $requerimiento->solicitud,
            'resultado_entregado',
            'Resultado entregado',
            'El contador responsable entrego el resultado para revision.',
            auth()->id(),
            $requerimiento
        );

        SolicitudNotificacionService::notificarRespuestaEnviada($requerimiento);

        $this->dispatch('requerimiento-actualizado');
        $this->dispatch('notify', message: 'Resultado guardado correctamente.');
    }

    public function cancelarGuardadoResultado(): void
    {
        $this->dispatch('notify', message: 'Corrige los archivos antes de continuar.');
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

    public function updatedOrigenForm(string $value): void
    {
        if ($value === 'cliente' && !$this->usuarioPuedeCrearSolicitudParaCliente()) {
            $this->origen_form = 'despacho';
            return;
        }

        $this->cargarOpcionesFormulario();
        $this->cargarObligacionesPeriodo($this->cliente_id_form, $this->periodo_anio_form, $this->periodo_mes_form);
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
        if (!$this->usuarioPuedeCrearSolicitud()) {
            return;
        }

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
            'fecha_resultado_form' => ['nullable', 'date'],
            'solicitud_archivos_form.*' => ['nullable', 'file', 'max:20480'],
        ]);

        $user = auth()->user();

        $cliente = Cliente::query()
            ->where('despacho_id', $user->despacho_id)
            ->findOrFail($this->cliente_id_form);

        if (!$cliente->responsable_solicitudes_id) {
            $this->addError('cliente_id_form', 'El cliente no tiene responsable de solicitudes asignado.');
            return;
        }

        if ($this->origen_form === 'cliente') {
            if (!$this->usuarioPuedeCrearSolicitudParaCliente()) {
                $this->addError('origen_form', 'Solo el usuario encargado del cliente puede crear solicitudes dirigidas al cliente.');
                return;
            }

            if ((int) $cliente->responsable_solicitudes_id !== (int) $user->id) {
                $this->addError('cliente_id_form', 'Solo puedes crear solicitudes dirigidas al cliente para tus clientes asignados.');
                return;
            }
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
            'estado_formulario' => $this->resolverEstadoFormularioInicial(
                $this->modo_solicitud_form,
                $this->origen_form
            ),
            'plantilla_snapshot' => $tipo ? [
                'tipo_id' => $tipo->id,
                'nombre' => $tipo->nombre,
                'titulo_sugerido' => $tipo->titulo_sugerido,
                'descripcion_sugerida' => $tipo->descripcion_sugerida,
                'prioridad_default' => $tipo->prioridad_default,
                'configuracion_formulario' => $tipo->configuracion_formulario,
            ] : null,
            'estado' => 'abierta',
            'prioridad' => $this->prioridadResolvida($tipo?->prioridad_default),
            'responsable_user_id' => $cliente->responsable_solicitudes_id,
        ];

        if ($this->editandoSolicitud && $this->solicitudEditandoId) {
            $solicitud = Solicitud::query()
                ->whereKey($this->solicitudEditandoId)
                ->where($this->scopeSolicitudesUsuario($user))
                ->first();

            if (!$solicitud) {
                return;
            }

            if ($this->modo_solicitud_form === 'definida') {
                $payload['datos_formulario'] = $solicitud->datos_formulario;
            }

            if ($this->modo_solicitud_form === 'general') {
                $payload['datos_formulario'] = null;
                $payload['estado_formulario'] = 'no_aplica';
            } elseif (!empty($solicitud->datos_formulario) && in_array($solicitud->estado_formulario, ['respondido', 'validado'], true)) {
                $payload['estado_formulario'] = $solicitud->estado_formulario;
            }

            $solicitud->update($payload);

            $this->guardarArchivosSolicitud($solicitud);

            $this->sincronizarRequerimientoResultado($solicitud, $user, $cliente->responsable_solicitudes_id);

            $this->asegurarRequerimientoFormulario($solicitud, $user, $tipo);

            SolicitudHistorialService::registrar(
                $solicitud,
                'solicitud_actualizada',
                'Solicitud actualizada',
                'Se actualizaron los datos generales de la solicitud.',
                $user->id
            );

            $mensaje = 'Solicitud actualizada correctamente.';
        } else {
            $payload['creado_por_user_id'] = $user->id;
            $solicitud = Solicitud::create($payload);

            $this->guardarArchivosSolicitud($solicitud);

            $resultadoRequerimiento = $this->sincronizarRequerimientoResultado($solicitud, $user, $cliente->responsable_solicitudes_id);

            SolicitudHistorialService::registrar(
                $solicitud,
                'solicitud_creada',
                'Solicitud creada',
                'Se creo la solicitud "' . $solicitud->titulo . '".',
                $user->id
            );

            if ($resultadoRequerimiento) {
                SolicitudHistorialService::registrar(
                    $solicitud,
                    'resultado_generado',
                    'Requerimiento resultado generado',
                    'Se genero automaticamente el requerimiento de resultado esperado.',
                    $user->id,
                    $resultadoRequerimiento
                );

                SolicitudNotificacionService::notificarRequerimientoCreado($resultadoRequerimiento);
            }

            $this->asegurarRequerimientoFormulario($solicitud, $user, $tipo);

            SolicitudNotificacionService::notificarSolicitudCreada($solicitud);

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
            ->with(['cliente', 'responsable', 'creadoPor', 'obligacion', 'obligacionClienteContador.obligacion', 'resultadoRequerimiento'])
            ->where($this->scopeSolicitudesUsuario($user))
            ->when($this->vistaSolicitudes === 'asignadas', function ($q) use ($user) {
                $q->where('responsable_user_id', $user->id);

                if ($user->hasRole('contador')) {
                    $q->whereDoesntHave('requerimientos', function ($requerimiento) use ($user) {
                        $requerimiento
                            ->whereIn('estado', ['abierto', 'respondido', 'rechazado'])
                            ->where(function ($principal) {
                                $principal->where('tipo', 'resultado')
                                    ->orWhere('titulo', 'like', 'Completar formulario%');
                            })
                            ->where('destinatario_tipo', 'interno')
                            ->where('destinatario_user_id', $user->id);
                    });
                }
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
            ->when($this->estado === 'activos', fn ($q) => $q->whereNotIn('estado', ['cerrada', 'cancelada']))
            ->when(!in_array($this->estado, ['', 'activos'], true), fn ($q) => $q->where('estado', $this->estado))
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
            'solicitudEditandoActual' => $this->solicitudEditandoActual(),
            'usuariosInternosRequerimiento' => $this->usuariosInternosRequerimiento(),
            'tituloModulo' => $this->vistaSolicitudes === 'asignadas' ? 'Solicitudes asignadas' : 'Solicitudes',
            'puedeCrearSolicitud' => $this->usuarioPuedeCrearSolicitud(),
            'puedeCrearSolicitudParaCliente' => $this->usuarioPuedeCrearSolicitudParaCliente(),
            'usuarioEsAdminOSupervisor' => $this->usuarioEsAdminOSupervisor(),
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
        $user = auth()->user();

        $clientesQuery = Cliente::query()
            ->where('despacho_id', $user->despacho_id);

        if ($this->origen_form === 'cliente') {
            if (!$this->usuarioPuedeCrearSolicitudParaCliente()) {
                $this->clientesDisponibles = [];
                $this->cliente_id_form = null;
            } else {
                $clientesQuery->where('responsable_solicitudes_id', $user->id);
            }
        }

        $this->clientesDisponibles = $clientesQuery
            ->orderByRaw('coalesce(nombre, razon_social)')
            ->get()
            ->map(fn ($cliente) => [
                'id' => $cliente->id,
                'nombre' => $cliente->nombre ?: $cliente->razon_social,
            ])
            ->toArray();

        if ($this->cliente_id_form && !collect($this->clientesDisponibles)->contains(fn ($cliente) => (int) $cliente['id'] === (int) $this->cliente_id_form)) {
            $this->cliente_id_form = null;
        }

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
        $this->fecha_resultado_form = '';
        $this->solicitud_archivos_form = [];
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

    private function resolverEstadoFormularioInicial(string $modoSolicitud, string $dirigidaA): ?string
    {
        if ($modoSolicitud !== 'definida') {
            return 'no_aplica';
        }

        if (auth()->user()->hasRole('cliente')) {
            return 'respondido';
        }

        return 'pendiente';
    }

    private function asegurarRequerimientoFormulario(Solicitud $solicitud, User $user, ?SolicitudTipo $tipo): void
    {
        if (
            $solicitud->modo_solicitud !== 'definida'
            || $solicitud->estado_formulario !== 'pendiente'
        ) {
            return;
        }

        $titulo = 'Completar formulario';
        if ($tipo?->nombre) {
            $titulo .= ': ' . $tipo->nombre;
        }

        $existente = $solicitud->requerimientos()
            ->where('tipo', 'normal')
            ->where('titulo', $titulo)
            ->first();

        if ($existente) {
            return;
        }

        $campos = $solicitud->campos_formulario;
        $listaCampos = collect($campos)
            ->map(function (array $campo) {
                $label = $campo['label'] ?? 'Campo';
                $required = !empty($campo['required']) ? ' (requerido)' : '';

                return '- ' . $label . $required;
            })
            ->implode("\n");

        $descripcion = 'Completa el formulario solicitado para continuar con la solicitud.';

        if ($tipo?->nombre) {
            $descripcion .= "\n\nTipo de solicitud: " . $tipo->nombre . '.';
        }

        if ($listaCampos !== '') {
            $descripcion .= "\n\nCampos esperados:\n" . $listaCampos;
        }

        $destinatarioTipo = $solicitud->origen === 'cliente' ? 'cliente' : 'interno';
        $destinatarioUserId = $destinatarioTipo === 'interno'
            ? $solicitud->responsable_user_id
            : null;

        $requerimiento = SolicitudRequerimiento::create([
            'solicitud_id' => $solicitud->id,
            'creado_por_user_id' => $user->id,
            'destinatario_tipo' => $destinatarioTipo,
            'destinatario_user_id' => $destinatarioUserId,
            'tipo' => 'normal',
            'titulo' => $titulo,
            'descripcion' => $descripcion,
            'estado' => 'abierto',
            'fecha_limite' => $this->fechaResultadoResuelta(),
        ]);

        SolicitudHistorialService::registrar(
            $solicitud,
            'requerimiento_creado',
            'Requerimiento de formulario creado',
            'Se genero automaticamente el requerimiento para completar el formulario de la solicitud.',
            $user->id,
            $requerimiento
        );

        SolicitudNotificacionService::notificarRequerimientoCreado($requerimiento);
    }

    private function sincronizarRequerimientoResultado(Solicitud $solicitud, User $user, int $responsableUserId): ?SolicitudRequerimiento
    {
        $resultadoRequerimiento = $solicitud->resultadoRequerimiento()->first();
        $destinatarioTipo = $solicitud->origen === 'cliente' ? 'cliente' : 'interno';
        $destinatarioUserId = $solicitud->origen === 'cliente' ? null : $responsableUserId;
        $descripcionResultado = $solicitud->origen === 'cliente'
            ? 'Responde aqui con la informacion final solicitada para su validacion y cierre.'
            : 'Entrega aqui el resultado final esperado de la solicitud para su validacion y cierre.';

        if ($solicitud->usaFormularioComoCierre()) {
            if ($resultadoRequerimiento && $resultadoRequerimiento->estado !== 'cancelado') {
                $resultadoRequerimiento->update([
                    'estado' => 'cancelado',
                    'comentario_validacion' => 'No aplica para solicitudes definidas dirigidas al cliente.',
                ]);
            }

            return null;
        }

        if ($resultadoRequerimiento) {
            $resultadoRequerimiento->update([
                'estado' => $resultadoRequerimiento->estado === 'cancelado' ? 'abierto' : $resultadoRequerimiento->estado,
                'destinatario_tipo' => $destinatarioTipo,
                'destinatario_user_id' => $destinatarioUserId,
                'descripcion' => $descripcionResultado,
                'fecha_limite' => $this->fechaResultadoResuelta(),
            ]);

            return $resultadoRequerimiento;
        }

        return SolicitudRequerimiento::create([
            'solicitud_id' => $solicitud->id,
            'creado_por_user_id' => $solicitud->creado_por_user_id ?? $user->id,
            'destinatario_tipo' => $destinatarioTipo,
            'destinatario_user_id' => $destinatarioUserId,
            'tipo' => 'resultado',
            'titulo' => 'Resultado esperado',
            'descripcion' => $descripcionResultado,
            'estado' => 'abierto',
            'fecha_limite' => $this->fechaResultadoResuelta(),
        ]);
    }

    private function fechaResultadoResuelta(): string
    {
        return $this->fecha_resultado_form !== ''
            ? $this->fecha_resultado_form
            : now()->addDays(2)->toDateString();
    }

    private function prioridadResolvida(?string $prioridadDefaultTipo = null): string
    {
        return $this->prioridad_form !== ''
            ? $this->prioridad_form
            : ($prioridadDefaultTipo ?: 'media');
    }

    private function cerrarSolicitudPorRequerimientoPrincipalValidado(Solicitud $solicitud, SolicitudRequerimiento $requerimiento, string $comentarioCierre): void
    {
        if ($this->solicitudTieneRequerimientosPendientes($solicitud)) {
            return;
        }

        $solicitud->update([
            'estado' => 'cerrada',
            'cerrado_por_user_id' => auth()->id(),
            'comentario_cierre' => $solicitud->comentario_cierre ?: $comentarioCierre,
            'cerrada_at' => now(),
        ]);

        SolicitudHistorialService::registrar(
            $solicitud,
            'solicitud_cerrada',
            'Solicitud cerrada',
            $solicitud->comentario_cierre ?: $comentarioCierre,
            auth()->id(),
            $requerimiento
        );

        SolicitudNotificacionService::notificarSolicitudCerrada($solicitud);
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
                'resultadoRequerimiento',
                'historial.user',
                'historial.requerimiento',
                'requerimientos.creadoPor',
                'requerimientos.destinatario',
                'requerimientos.respondidoPor',
                'requerimientos.archivos',
            ])
            ->whereKey($this->solicitudDetalleId)
            ->where($this->scopeSolicitudesUsuario($user))
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
        if ($requerimiento->tipo === 'resultado') {
            return (int) $requerimiento->solicitud->creado_por_user_id === (int) auth()->id();
        }

        return (int) $requerimiento->creado_por_user_id === (int) auth()->id();
    }

    private function solicitudTieneRequerimientosPendientes(Solicitud $solicitud): bool
    {
        return $solicitud->requerimientos->contains(function ($requerimiento) use ($solicitud) {
            if ($solicitud->usaFormularioComoCierre() && $requerimiento->tipo === 'resultado') {
                return false;
            }

            return !in_array($requerimiento->estado, ['validado', 'cancelado'], true);
        });
    }

    private function usuarioPuedeCrearSolicitud(): bool
    {
        return auth()->check();
    }

    private function usuarioPuedeCrearSolicitudParaCliente(): bool
    {
        if (!auth()->check()) {
            return false;
        }

        $user = auth()->user();

        return Cliente::query()
            ->where('despacho_id', $user->despacho_id)
            ->where('responsable_solicitudes_id', $user->id)
            ->exists();
    }

    private function solicitudEditandoActual(): ?Solicitud
    {
        if (!$this->editandoSolicitud || !$this->solicitudEditandoId) {
            return null;
        }

        return Solicitud::query()
            ->with('archivos')
            ->find($this->solicitudEditandoId);
    }

    private function guardarArchivosSolicitud(Solicitud $solicitud): void
    {
        if (empty($this->solicitud_archivos_form)) {
            return;
        }

        $cliente = $solicitud->cliente;
        $despacho = $cliente?->despacho;

        if (!$cliente || !$despacho) {
            return;
        }

        foreach ($this->solicitud_archivos_form as $archivo) {
            if (!$archivo || !is_object($archivo) || !method_exists($archivo, 'getClientOriginalName')) {
                continue;
            }

            $nombreFinal = $this->construirNombreArchivoSolicitud($cliente->rfc, $solicitud->titulo, $archivo);
            $rutaStorage = null;
            $urlDrive = null;

            if (in_array($despacho->politica_almacenamiento, ['storage_only', 'both'])) {
                $rutaStorage = $archivo->storeAs('adjuntos', $nombreFinal, 'public');
            }

            if (in_array($despacho->politica_almacenamiento, ['drive_only', 'both'])) {
                $folderId = null;

                if ($solicitud->carpeta_drive_id) {
                    $folderId = \App\Models\CarpetaDrive::find($solicitud->carpeta_drive_id)?->drive_folder_id;
                }

                if ($folderId) {
                    $drive = app(\App\Services\DriveService::class);
                    $res = $drive->subirArchivo(
                        $nombreFinal,
                        $archivo,
                        $folderId,
                        $archivo->getMimeType()
                    );

                    $urlDrive = is_array($res) ? ($res['webViewLink'] ?? null) : $res;
                }
            }

            $solicitud->archivos()->create([
                'nombre' => $nombreFinal,
                'archivo' => $rutaStorage,
                'archivo_drive_url' => $urlDrive,
            ]);
        }

        $this->solicitud_archivos_form = [];
    }

    private function construirNombreArchivoSolicitud(string $rfc, string $titulo, $archivo): string
    {
        $extension = strtolower($archivo->getClientOriginalExtension());
        $mes = now()->format('m');
        $anio = now()->format('y');
        $segundos = now()->format('s');
        $rfcNormalizado = \Str::upper($rfc);
        $tituloNormalizado = \Str::slug($titulo, '-');

        return "{$anio}-{$mes}-{$rfcNormalizado}-solicitud-{$tituloNormalizado}-{$segundos}.{$extension}";
    }

    private function usuarioEsAdminOSupervisor(): bool
    {
        return auth()->user()->hasAnyRole(['admin_despacho', 'supervisor']);
    }

    private function usuarioPuedeEditarSolicitud(Solicitud $solicitud): bool
    {
        return (int) $solicitud->creado_por_user_id === (int) auth()->id();
    }

    private function usuarioPuedeCancelarSolicitud(Solicitud $solicitud): bool
    {
        return $this->usuarioEsAdminOSupervisor()
            || (int) $solicitud->creado_por_user_id === (int) auth()->id();
    }

    private function usuarioPuedeCerrarSolicitud(Solicitud $solicitud): bool
    {
        if ($this->usuarioEsAdminOSupervisor()) {
            return true;
        }

        return (int) $solicitud->creado_por_user_id === (int) auth()->id();
    }

    private function usuarioPuedeOperarRequerimientos(Solicitud $solicitud): bool
    {
        return $this->usuarioEsAdminOSupervisor()
            || (int) $solicitud->creado_por_user_id === (int) auth()->id();
    }

    private function usuarioPuedeResponderResultado(SolicitudRequerimiento $requerimiento): bool
    {
        return $requerimiento->tipo === 'resultado'
            && (int) $requerimiento->destinatario_user_id === (int) auth()->id()
            && !in_array($requerimiento->estado, ['validado', 'cancelado'], true);
    }

    private function requerimientoResultadoDesdeDetalle(): ?SolicitudRequerimiento
    {
        $solicitud = $this->solicitudDetalle();

        if (!$solicitud) {
            return null;
        }

        return $solicitud->requerimientos->firstWhere('tipo', 'resultado');
    }

    private function resultadoUsaFormulario(SolicitudRequerimiento $requerimiento): bool
    {
        return $requerimiento->tipo === 'resultado'
            && $requerimiento->solicitud?->modo_solicitud === 'definida'
            && $requerimiento->solicitud?->origen === 'despacho';
    }

    private function mapearFormularioResultadoSolicitud(Solicitud $solicitud): array
    {
        if ($solicitud->modo_solicitud !== 'definida') {
            return [];
        }

        $datos = is_array($solicitud->datos_formulario) ? $solicitud->datos_formulario : [];
        $respuesta = [];

        foreach ($solicitud->campos_formulario as $campo) {
            $key = $campo['key'] ?? null;

            if (!$key) {
                continue;
            }

            if (($campo['type'] ?? 'text') === 'checkbox') {
                $respuesta[$key] = (bool) ($datos[$key] ?? false);
                continue;
            }

            $respuesta[$key] = ($campo['type'] ?? 'text') === 'file'
                ? null
                : ($datos[$key] ?? null);
        }

        return $respuesta;
    }

    private function validarFormularioResultado(Solicitud $solicitud): void
    {
        $rules = [];
        $datosActuales = is_array($solicitud->datos_formulario) ? $solicitud->datos_formulario : [];

        foreach ($solicitud->campos_formulario as $campo) {
            $key = $campo['key'] ?? null;

            if (!$key) {
                continue;
            }

            $required = (bool) ($campo['required'] ?? false);
            $type = $campo['type'] ?? 'text';
            $ruleKey = "formularioResultado.$key";
            $yaTieneArchivo = $type === 'file' && !empty($datosActuales[$key] ?? null);

            $rules[$ruleKey] = match ($type) {
                'checkbox' => $required ? ['accepted'] : ['nullable', 'boolean'],
                'number' => $required ? ['required', 'numeric'] : ['nullable', 'numeric'],
                'date' => $required ? ['required', 'date'] : ['nullable', 'date'],
                'select', 'text', 'textarea' => $required ? ['required'] : ['nullable'],
                'file' => ($required && !$yaTieneArchivo)
                    ? ['required', 'file', 'max:20480']
                    : ['nullable', 'file', 'max:20480'],
                default => ['nullable'],
            };
        }

        if (!empty($rules)) {
            $this->validate($rules);
        }
    }

    private function normalizarFormularioResultado(Solicitud $solicitud): array
    {
        $normalizado = [];
        $datosActuales = is_array($solicitud->datos_formulario) ? $solicitud->datos_formulario : [];

        foreach ($solicitud->campos_formulario as $campo) {
            $key = $campo['key'] ?? null;

            if (!$key) {
                continue;
            }

            $type = $campo['type'] ?? 'text';
            $value = $this->formularioResultado[$key] ?? null;

            $normalizado[$key] = match ($type) {
                'checkbox' => (bool) $value,
                'number' => $value === null || $value === '' ? null : (is_numeric($value) ? $value + 0 : $value),
                'file' => is_object($value) && method_exists($value, 'getClientOriginalName')
                    ? $value->getClientOriginalName()
                    : ($datosActuales[$key] ?? null),
                default => $value === '' ? null : $value,
            };
        }

        return $normalizado;
    }

    private function guardarArchivosFormularioResultado(Solicitud $solicitud): array
    {
        $archivosGuardados = [];
        $cliente = $solicitud->cliente;
        $despacho = $cliente?->despacho;
        $datosActuales = is_array($solicitud->datos_formulario) ? $solicitud->datos_formulario : [];

        if (!$cliente || !$despacho) {
            return $archivosGuardados;
        }

        foreach ($solicitud->campos_formulario as $campo) {
            $key = $campo['key'] ?? null;
            $type = $campo['type'] ?? 'text';

            if (!$key || $type !== 'file') {
                continue;
            }

            $archivo = $this->formularioResultado[$key] ?? null;

            if (!$archivo || !is_object($archivo) || !method_exists($archivo, 'getClientOriginalName')) {
                continue;
            }

            $nombreAnterior = $datosActuales[$key] ?? null;
            if ($nombreAnterior) {
                $archivoAnterior = $solicitud->archivos()->where('nombre', $nombreAnterior)->first();

                if ($archivoAnterior) {
                    if ($archivoAnterior->archivo) {
                        \Illuminate\Support\Facades\Storage::disk('public')->delete($archivoAnterior->archivo);
                    }

                    $archivoAnterior->delete();
                }
            }

            $extension = strtolower($archivo->getClientOriginalExtension());
            $mes = now()->format('m');
            $anio = now()->format('y');
            $segundos = now()->format('s');
            $rfcNormalizado = \Str::upper($cliente->rfc);
            $nombreNormalizado = \Str::slug($campo['label'] ?? $key, '-');
            $nombreFinal = "{$anio}-{$mes}-{$rfcNormalizado}-{$nombreNormalizado}-{$segundos}.{$extension}";

            $rutaStorage = null;
            $urlDrive = null;

            if (in_array($despacho->politica_almacenamiento, ['storage_only', 'both'])) {
                $rutaStorage = $archivo->storeAs('adjuntos', $nombreFinal, 'public');
            }

            if (in_array($despacho->politica_almacenamiento, ['drive_only', 'both'])) {
                $folderId = null;

                if ($solicitud->carpeta_drive_id) {
                    $folderId = \App\Models\CarpetaDrive::find($solicitud->carpeta_drive_id)?->drive_folder_id;
                }

                if ($folderId) {
                    $drive = app(\App\Services\DriveService::class);
                    $res = $drive->subirArchivo($nombreFinal, $archivo, $folderId, $archivo->getMimeType());
                    $urlDrive = is_array($res) ? ($res['webViewLink'] ?? null) : $res;
                }
            }

            $solicitud->archivos()->create([
                'nombre' => $nombreFinal,
                'archivo' => $rutaStorage,
                'archivo_drive_url' => $urlDrive,
            ]);

            $archivosGuardados[$key] = $nombreFinal;
        }

        return $archivosGuardados;
    }

    private function scopeSolicitudesUsuario(User $user): \Closure
    {
        return function ($query) use ($user) {
            $query->whereHas('cliente', function ($cliente) use ($user) {
                $cliente->where('despacho_id', $user->despacho_id);

                if ($user->hasRole('cliente')) {
                    $cliente->where('id', $user->cliente_id);
                }
            });

            if ($user->hasRole('contador')) {
                $query->where(function ($sub) use ($user) {
                    $sub->where('creado_por_user_id', $user->id)
                        ->orWhere(function ($asignadaPorCliente) use ($user) {
                            $asignadaPorCliente
                                ->where('responsable_user_id', $user->id)
                                ->where('origen', 'cliente');
                        });
                });
            }

            if ($user->hasRole('cliente')) {
                $query->where('creado_por_user_id', $user->id);
            }
        };
    }
}
