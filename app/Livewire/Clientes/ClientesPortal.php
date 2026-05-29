<?php

namespace App\Livewire\Clientes;

use App\Livewire\Shared\HasPerPage;
use App\Models\Cliente;
use App\Models\Solicitud;
use App\Models\SolicitudRequerimiento;
use App\Models\SolicitudTipo;
use App\Services\SolicitudHistorialService;
use App\Services\SolicitudNotificacionService;
use App\Services\DriveService;
use App\Models\CarpetaDrive;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;

class ClientesPortal extends Component
{
    use WithPagination, HasPerPage, WithFileUploads;

    public string $buscar = '';
    public string $estado = '';
    public bool $sidebarVisible = false;
    public bool $detalleSidebarVisible = false;
    public bool $editandoSolicitud = false;
    public bool $confirmarEliminar = false;
    public bool $confirmarCierre = false;
    public ?int $tipo_solicitud_id_form = null;
    public ?int $solicitud_id_form = null;
    public ?int $solicitudEliminarId = null;
    public ?int $solicitudCerrarId = null;
    public ?int $solicitudDetalleId = null;
    public string $fecha_resultado_form = '';
    public array $formulario_respuesta = [];
    public array $datos_formulario_actual = [];

    protected $paginationTheme = 'tailwind';

    public function updatingBuscar(): void
    {
        $this->resetPage();
    }

    public function updatedEstado(): void
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

    public function abrirDetalle(int $solicitudId): void
    {
        $user = auth()->user();

        Solicitud::query()
            ->where('cliente_id', $user->cliente_id)
            ->where('creado_por_user_id', $user->id)
            ->findOrFail($solicitudId);

        $this->solicitudDetalleId = $solicitudId;
        $this->detalleSidebarVisible = true;
    }

    public function cerrarDetalle(): void
    {
        $this->detalleSidebarVisible = false;
        $this->solicitudDetalleId = null;
    }

    public function updatedTipoSolicitudIdForm($value): void
    {
        $tipo = $value ? $this->tiposDisponibles()->firstWhere('id', (int) $value) : null;
        $this->formulario_respuesta = $this->formularioInicial($tipo?->configuracion_formulario ?? []);
        $this->resetValidation();
        $this->resetErrorBag();
    }

    public function guardarSolicitud(): void
    {
        $user = auth()->user();
        $clienteId = (int) ($user->cliente_id ?? 0);

        if (!$clienteId) {
            $this->dispatch('notify', message: 'No se encontro un cliente asociado a tu usuario.');
            return;
        }

        $tipo = $this->tiposDisponibles()->firstWhere('id', (int) $this->tipo_solicitud_id_form);

        if (!$tipo) {
            $this->addError('tipo_solicitud_id_form', 'Selecciona un tipo de solicitud valido.');
            return;
        }

        $cliente = Cliente::query()->find($clienteId);

        if (!$cliente || !$cliente->responsable_solicitudes_id) {
            $this->dispatch('notify', message: 'El cliente no tiene responsable de solicitudes asignado.');
            return;
        }

        $this->validarFormularioTipo($tipo->configuracion_formulario ?? []);

        $datosFormulario = $this->normalizarFormulario($tipo->configuracion_formulario ?? []);

        DB::transaction(function () use ($user, $cliente, $tipo, $datosFormulario) {
            if ($this->editandoSolicitud && $this->solicitud_id_form) {
                $solicitud = Solicitud::query()
                    ->where('cliente_id', $cliente->id)
                    ->where('creado_por_user_id', $user->id)
                    ->findOrFail($this->solicitud_id_form);

                $datosActuales = is_array($solicitud->datos_formulario) ? $solicitud->datos_formulario : [];

                $solicitud->update([
                    'tipo_solicitud_id' => $tipo->id,
                    'titulo' => $tipo->titulo_sugerido ?: $tipo->nombre,
                    'descripcion' => $tipo->descripcion_sugerida ?: null,
                    'datos_formulario' => array_replace($datosActuales, $datosFormulario),
                    'estado_formulario' => 'respondido',
                    'plantilla_snapshot' => [
                        'tipo_id' => $tipo->id,
                        'nombre' => $tipo->nombre,
                        'titulo_sugerido' => $tipo->titulo_sugerido,
                        'descripcion_sugerida' => $tipo->descripcion_sugerida,
                        'prioridad_default' => $tipo->prioridad_default,
                        'configuracion_formulario' => $tipo->configuracion_formulario,
                    ],
                    'prioridad' => $tipo->prioridad_default ?: 'media',
                ]);

                $archivosFormulario = $this->guardarArchivosFormulario($solicitud, $tipo->configuracion_formulario ?? []);

                if (!empty($archivosFormulario)) {
                    $solicitud->update([
                        'datos_formulario' => array_replace(
                            is_array($solicitud->datos_formulario) ? $solicitud->datos_formulario : [],
                            $archivosFormulario
                        ),
                    ]);
                }

                if ($solicitud->resultadoRequerimiento && $solicitud->usaFormularioComoCierre()) {
                    $solicitud->resultadoRequerimiento->update([
                        'estado' => 'cancelado',
                        'comentario_validacion' => 'No aplica para solicitudes definidas creadas por el cliente.',
                    ]);
                }

                $this->asegurarRequerimientoResultado($solicitud, $user, (int) $cliente->responsable_solicitudes_id);

                SolicitudHistorialService::registrar(
                    $solicitud,
                    'solicitud_actualizada',
                    'Solicitud actualizada',
                    'El cliente actualizo la solicitud "' . $solicitud->titulo . '".',
                    $user->id
                );

                $this->cerrarSidebar();
                $this->dispatch('notify', message: 'Solicitud actualizada correctamente.');
                return;
            }

            $solicitud = Solicitud::create([
                'cliente_id' => $cliente->id,
                'obligacion_id' => null,
                'obligacion_cliente_contador_id' => null,
                'modo_solicitud' => 'definida',
                'tipo_solicitud_id' => $tipo->id,
                'origen' => 'cliente',
                'titulo' => $tipo->titulo_sugerido ?: $tipo->nombre,
                'descripcion' => $tipo->descripcion_sugerida ?: null,
                'datos_formulario' => $datosFormulario,
                'estado_formulario' => 'respondido',
                'plantilla_snapshot' => [
                    'tipo_id' => $tipo->id,
                    'nombre' => $tipo->nombre,
                    'titulo_sugerido' => $tipo->titulo_sugerido,
                    'descripcion_sugerida' => $tipo->descripcion_sugerida,
                    'prioridad_default' => $tipo->prioridad_default,
                    'configuracion_formulario' => $tipo->configuracion_formulario,
                ],
                'estado' => 'abierta',
                'prioridad' => $tipo->prioridad_default ?: 'media',
                'responsable_user_id' => $cliente->responsable_solicitudes_id,
                'creado_por_user_id' => $user->id,
            ]);

            $archivosFormulario = $this->guardarArchivosFormulario($solicitud, $tipo->configuracion_formulario ?? []);

            if (!empty($archivosFormulario)) {
                foreach ($archivosFormulario as $key => $valor) {
                    $datosFormulario[$key] = $valor;
                }

                $solicitud->update([
                    'datos_formulario' => $datosFormulario,
                ]);
            }

            SolicitudHistorialService::registrar(
                $solicitud,
                'solicitud_creada',
                'Solicitud creada',
                'El cliente creo la solicitud "' . $solicitud->titulo . '".',
                $user->id
            );

            $resultadoRequerimiento = $this->asegurarRequerimientoResultado($solicitud, $user, (int) $cliente->responsable_solicitudes_id);

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

            SolicitudNotificacionService::notificarSolicitudCreada($solicitud);

            $this->cerrarSidebar();
            $this->dispatch('notify', message: 'Solicitud enviada correctamente.');
        });
    }

    public function editarSolicitud(int $solicitudId): void
    {
        $user = auth()->user();
        $solicitud = Solicitud::query()
            ->with(['tipoSolicitud', 'resultadoRequerimiento'])
            ->where('cliente_id', $user->cliente_id)
            ->where('creado_por_user_id', $user->id)
            ->findOrFail($solicitudId);

        $this->resetFormulario();
        $this->editandoSolicitud = true;
        $this->solicitud_id_form = $solicitud->id;
        $this->tipo_solicitud_id_form = $solicitud->tipo_solicitud_id;
        $this->datos_formulario_actual = is_array($solicitud->datos_formulario) ? $solicitud->datos_formulario : [];

        $tipo = $this->tiposDisponibles()->firstWhere('id', (int) $solicitud->tipo_solicitud_id);
        $this->formulario_respuesta = $this->mapearFormularioExistente(
            $tipo?->configuracion_formulario ?? [],
            $this->datos_formulario_actual
        );

        $this->sidebarVisible = true;
    }

    public function confirmarEliminarSolicitud(int $solicitudId): void
    {
        $user = auth()->user();

        Solicitud::query()
            ->where('cliente_id', $user->cliente_id)
            ->where('creado_por_user_id', $user->id)
            ->findOrFail($solicitudId);

        $this->solicitudEliminarId = $solicitudId;
        $this->confirmarEliminar = true;
    }

    public function confirmarCerrarSolicitud(int $solicitudId): void
    {
        $user = auth()->user();

        $solicitud = Solicitud::query()
            ->with('resultadoRequerimiento')
            ->where('cliente_id', $user->cliente_id)
            ->where('creado_por_user_id', $user->id)
            ->findOrFail($solicitudId);

        if (!$this->clientePuedeCerrarSolicitud($solicitud)) {
            $this->dispatch('notify', message: 'La solicitud aun no esta lista para cerrarse.');
            return;
        }

        $this->solicitudCerrarId = $solicitudId;
        $this->confirmarCierre = true;
    }

    public function cerrarSolicitudConfirmada(): void
    {
        $user = auth()->user();

        $solicitud = Solicitud::query()
            ->with('resultadoRequerimiento')
            ->where('cliente_id', $user->cliente_id)
            ->where('creado_por_user_id', $user->id)
            ->whereKey($this->solicitudCerrarId)
            ->whereNotIn('estado', ['cerrada', 'cancelada'])
            ->first();

        if (!$solicitud) {
            $this->confirmarCierre = false;
            $this->solicitudCerrarId = null;
            return;
        }

        if (!$this->clientePuedeCerrarSolicitud($solicitud)) {
            $this->confirmarCierre = false;
            $this->solicitudCerrarId = null;
            $this->dispatch('notify', message: 'La solicitud aun no esta lista para cerrarse.');
            return;
        }

        DB::transaction(function () use ($solicitud, $user) {
            $resultado = $solicitud->resultadoRequerimiento;

            if (!$solicitud->usaFormularioComoCierre() && $resultado && $resultado->estado === 'respondido') {
                $resultado->update([
                    'estado' => 'validado',
                    'validado_por_user_id' => $user->id,
                    'validado_at' => now(),
                    'comentario_validacion' => 'Resultado aceptado y solicitud cerrada por el cliente.',
                ]);

                SolicitudHistorialService::registrar(
                    $solicitud,
                    'resultado_validado',
                    'Resultado validado',
                    'El cliente valido el resultado entregado.',
                    $user->id,
                    $resultado
                );
            }

            $solicitud->update([
                'estado' => 'cerrada',
                'cerrado_por_user_id' => $user->id,
                'comentario_cierre' => $solicitud->comentario_cierre ?: 'Solicitud cerrada por el cliente.',
                'cerrada_at' => now(),
            ]);

            SolicitudHistorialService::registrar(
                $solicitud,
                'solicitud_cerrada',
                'Solicitud cerrada',
                $solicitud->comentario_cierre ?: 'Solicitud cerrada por el cliente.',
                $user->id
            );

            SolicitudNotificacionService::notificarSolicitudCerrada($solicitud);
        });

        $this->confirmarCierre = false;
        $this->solicitudCerrarId = null;
        $this->dispatch('notify', message: 'Solicitud cerrada correctamente.');
    }

    public function eliminarSolicitudConfirmada(): void
    {
        $user = auth()->user();
        $solicitudId = $this->solicitudEliminarId;

        if (!$solicitudId) {
            return;
        }

        $solicitud = Solicitud::query()
            ->with(['archivos', 'requerimientos.archivos'])
            ->where('cliente_id', $user->cliente_id)
            ->where('creado_por_user_id', $user->id)
            ->findOrFail($solicitudId);

        foreach ($solicitud->archivos as $archivo) {
            if ($archivo->archivo) {
                Storage::disk('public')->delete($archivo->archivo);
            }
            $archivo->delete();
        }

        foreach ($solicitud->requerimientos as $requerimiento) {
            foreach ($requerimiento->archivos as $archivo) {
                if ($archivo->archivo) {
                    Storage::disk('public')->delete($archivo->archivo);
                }
                $archivo->delete();
            }
        }

        $solicitud->delete();

        $this->confirmarEliminar = false;
        $this->solicitudEliminarId = null;
        $this->dispatch('notify', message: 'Solicitud eliminada correctamente.');
    }

    public function render()
    {
        $user = auth()->user();

        $query = Solicitud::query()
            ->with(['responsable', 'tipoSolicitud', 'resultadoRequerimiento'])
            ->where('cliente_id', $user->cliente_id)
            ->where('creado_por_user_id', $user->id)
            ->when($this->buscar !== '', function ($q) {
                $buscar = trim($this->buscar);

                $q->where(function ($sub) use ($buscar) {
                    $sub->where('titulo', 'like', "%{$buscar}%")
                        ->orWhere('descripcion', 'like', "%{$buscar}%")
                        ->orWhereHas('tipoSolicitud', fn ($tipo) => $tipo->where('nombre', 'like', "%{$buscar}%"));
                });
            })
            ->when($this->estado !== '', fn ($q) => $q->where('estado', $this->estado))
            ->latest();

        $tipoSeleccionado = $this->tiposDisponibles()->firstWhere('id', (int) $this->tipo_solicitud_id_form);
        $solicitudDetalle = null;

        if ($this->solicitudDetalleId) {
            $solicitudDetalle = Solicitud::query()
                ->with([
                    'responsable',
                    'tipoSolicitud',
                    'archivos',
                    'resultadoRequerimiento.archivos',
                    'resultadoRequerimiento.respondidoPor',
                ])
                ->where('cliente_id', $user->cliente_id)
                ->where('creado_por_user_id', $user->id)
                ->find($this->solicitudDetalleId);
        }

        return view('livewire.clientes.clientes-portal', [
            'solicitudes' => $query->paginate($this->perPageValue($query, 10)),
            'tiposDisponibles' => $this->tiposDisponibles(),
            'tipoSeleccionado' => $tipoSeleccionado,
            'camposFormulario' => $tipoSeleccionado?->configuracion_formulario['secciones'][0]['campos'] ?? [],
            'solicitudDetalle' => $solicitudDetalle,
        ]);
    }

    private function tiposDisponibles()
    {
        return SolicitudTipo::query()
            ->where('activo', true)
            ->whereIn('aplica_para', ['cliente', 'ambos'])
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'titulo_sugerido', 'descripcion_sugerida', 'prioridad_default', 'configuracion_formulario']);
    }

    private function asegurarRequerimientoResultado(Solicitud $solicitud, $user, int $responsableUserId): ?SolicitudRequerimiento
    {
        if ($solicitud->usaFormularioComoCierre()) {
            return null;
        }

        $resultadoRequerimiento = $solicitud->resultadoRequerimiento()->first();
        $descripcionResultado = 'Entrega aqui el resultado final esperado de la solicitud para su validacion y cierre.';
        $fechaLimite = now()->addDays(2)->toDateString();

        if ($resultadoRequerimiento) {
            $resultadoRequerimiento->update([
                'estado' => $resultadoRequerimiento->estado === 'cancelado' ? 'abierto' : $resultadoRequerimiento->estado,
                'destinatario_tipo' => 'interno',
                'destinatario_user_id' => $responsableUserId,
                'descripcion' => $descripcionResultado,
                'fecha_limite' => $resultadoRequerimiento->fecha_limite ?: $fechaLimite,
            ]);

            return $resultadoRequerimiento;
        }

        return SolicitudRequerimiento::create([
            'solicitud_id' => $solicitud->id,
            'creado_por_user_id' => $solicitud->creado_por_user_id ?? $user->id,
            'destinatario_tipo' => 'interno',
            'destinatario_user_id' => $responsableUserId,
            'tipo' => 'resultado',
            'titulo' => 'Resultado esperado',
            'descripcion' => $descripcionResultado,
            'estado' => 'abierto',
            'fecha_limite' => $fechaLimite,
        ]);
    }

    private function formularioInicial(array $configuracion): array
    {
        $campos = $configuracion['secciones'][0]['campos'] ?? [];
        $respuesta = [];

        foreach ($campos as $campo) {
            $key = $campo['key'] ?? null;

            if (!$key) {
                continue;
            }

            $respuesta[$key] = ($campo['type'] ?? 'text') === 'checkbox' ? false : null;
        }

        return $respuesta;
    }

    private function validarFormularioTipo(array $configuracion): void
    {
        $campos = $configuracion['secciones'][0]['campos'] ?? [];
        $rules = [];

        foreach ($campos as $campo) {
            $key = $campo['key'] ?? null;

            if (!$key) {
                continue;
            }

            $required = (bool) ($campo['required'] ?? false);
            $type = $campo['type'] ?? 'text';
            $ruleKey = "formulario_respuesta.$key";
            $yaTieneArchivo = $type === 'file' && !empty($this->datos_formulario_actual[$key] ?? null);

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

    private function normalizarFormulario(array $configuracion): array
    {
        $campos = $configuracion['secciones'][0]['campos'] ?? [];
        $normalizado = [];

        foreach ($campos as $campo) {
            $key = $campo['key'] ?? null;

            if (!$key) {
                continue;
            }

            $type = $campo['type'] ?? 'text';
            $value = $this->formulario_respuesta[$key] ?? null;

            $normalizado[$key] = match ($type) {
                'checkbox' => (bool) $value,
                'number' => $value === null || $value === '' ? null : (is_numeric($value) ? $value + 0 : $value),
                'file' => is_object($value) && method_exists($value, 'getClientOriginalName')
                    ? $value->getClientOriginalName()
                    : ($this->datos_formulario_actual[$key] ?? null),
                default => $value === '' ? null : $value,
            };
        }

        return $normalizado;
    }

    private function mapearFormularioExistente(array $configuracion, array $datos): array
    {
        $campos = $configuracion['secciones'][0]['campos'] ?? [];
        $respuesta = [];

        foreach ($campos as $campo) {
            $key = $campo['key'] ?? null;

            if (!$key) {
                continue;
            }

            $type = $campo['type'] ?? 'text';
            $valor = $datos[$key] ?? null;

            $respuesta[$key] = $type === 'checkbox'
                ? (bool) $valor
                : ($type === 'file' ? null : $valor);
        }

        return $respuesta;
    }

    private function guardarArchivosFormulario(Solicitud $solicitud, array $configuracion): array
    {
        $campos = $configuracion['secciones'][0]['campos'] ?? [];
        $archivosGuardados = [];
        $cliente = $solicitud->cliente;
        $despacho = $cliente?->despacho;

        if (!$cliente || !$despacho) {
            return $archivosGuardados;
        }

        foreach ($campos as $campo) {
            $key = $campo['key'] ?? null;
            $type = $campo['type'] ?? 'text';

            if (!$key || $type !== 'file') {
                continue;
            }

            $archivo = $this->formulario_respuesta[$key] ?? null;

            if (!$archivo || !is_object($archivo) || !method_exists($archivo, 'getClientOriginalName')) {
                continue;
            }

            $nombreFinal = $this->construirNombreArchivoFormulario($cliente->rfc, $campo['label'] ?? $key, $archivo);
            $rutaStorage = null;
            $urlDrive = null;

            if (in_array($despacho->politica_almacenamiento, ['storage_only', 'both'])) {
                $rutaStorage = $archivo->storeAs('adjuntos', $nombreFinal, 'public');
            }

            if (in_array($despacho->politica_almacenamiento, ['drive_only', 'both'])) {
                $folderId = null;

                if ($solicitud->carpeta_drive_id) {
                    $folderId = CarpetaDrive::find($solicitud->carpeta_drive_id)?->drive_folder_id;
                }

                if ($folderId) {
                    $drive = app(DriveService::class);
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

            $archivosGuardados[$key] = $nombreFinal;
        }

        return $archivosGuardados;
    }

    private function construirNombreArchivoFormulario(string $rfc, string $nombreCampo, $archivo): string
    {
        $extension = strtolower($archivo->getClientOriginalExtension());
        $mes = now()->format('m');
        $anio = now()->format('y');
        $segundos = now()->format('s');
        $rfcNormalizado = \Str::upper($rfc);
        $nombreNormalizado = \Str::slug($nombreCampo, '-');

        return "{$anio}-{$mes}-{$rfcNormalizado}-{$nombreNormalizado}-{$segundos}.{$extension}";
    }

    private function clientePuedeCerrarSolicitud(Solicitud $solicitud): bool
    {
        if ((int) $solicitud->creado_por_user_id !== (int) auth()->id()) {
            return false;
        }

        if (in_array($solicitud->estado, ['cerrada', 'cancelada'], true)) {
            return false;
        }

        if ($solicitud->usaFormularioComoCierre()) {
            return false;
        }

        $resultado = $solicitud->resultadoRequerimiento;

        if (!$resultado) {
            return false;
        }

        return in_array($solicitud->estado, ['pendiente_cliente', 'resuelto'], true)
            && in_array($resultado->estado, ['respondido', 'validado'], true);
    }

    private function resetFormulario(): void
    {
        $this->resetValidation();
        $this->resetErrorBag();
        $this->editandoSolicitud = false;
        $this->confirmarCierre = false;
        $this->tipo_solicitud_id_form = null;
        $this->solicitud_id_form = null;
        $this->solicitudEliminarId = null;
        $this->solicitudCerrarId = null;
        $this->solicitudDetalleId = null;
        $this->fecha_resultado_form = '';
        $this->formulario_respuesta = [];
        $this->datos_formulario_actual = [];
    }
}
