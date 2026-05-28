<?php

namespace App\Livewire\Solicitudes;

use App\Livewire\Shared\HasPerPage;
use App\Models\CarpetaDrive;
use App\Models\Solicitud;
use App\Models\SolicitudRequerimiento;
use App\Services\DriveService;
use App\Services\SolicitudHistorialService;
use App\Services\SolicitudNotificacionService;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class MisRequerimientos extends Component
{
    use WithPagination, HasPerPage, WithFileUploads;

    public string $buscar = '';
    public string $estado = 'activos';
    public bool $sidebarVisible = false;
    public ?int $requerimientoIdSeleccionado = null;
    public string $respuesta_texto = '';
    public array $formulario_respuesta = [];

    protected $paginationTheme = 'tailwind';
    protected $listeners = [
        'archivos-ok-requerimientos' => 'continuarGuardadoRespuesta',
        'archivos-error-requerimientos' => 'cancelarGuardadoRespuesta',
        'adjuntos-actualizados' => '$refresh',
        'requerimiento-actualizado' => '$refresh',
    ];

    public function updatingBuscar(): void
    {
        $this->resetPage();
    }

    public function updatedEstado(): void
    {
        $this->resetPage();
    }

    public function abrirDetalle(int $requerimientoId): void
    {
        $requerimiento = $this->queryBase()
            ->with(['solicitud.cliente', 'solicitud.archivos', 'creadoPor', 'archivos'])
            ->find($requerimientoId);

        if (!$requerimiento) {
            return;
        }

        $this->requerimientoIdSeleccionado = $requerimiento->id;
        $this->respuesta_texto = $requerimiento->respuesta_texto ?? '';
        $this->formulario_respuesta = $this->mapearFormularioRespuesta($requerimiento);
        $this->sidebarVisible = true;
    }

    public function cerrarSidebar(): void
    {
        $this->sidebarVisible = false;
        $this->requerimientoIdSeleccionado = null;
        $this->respuesta_texto = '';
        $this->formulario_respuesta = [];
    }

    public function guardarRespuesta(): void
    {
        $requerimiento = $this->requerimientoSeleccionado();

        if (!$requerimiento || in_array($requerimiento->estado, ['validado', 'cancelado'], true)) {
            return;
        }

        if (!$this->validarRespuestaRequerimiento($requerimiento)) {
            return;
        }

        if ($requerimiento->esRequerimientoFormulario()) {
            $this->continuarGuardadoRespuesta();
            return;
        }

        $this->dispatch('guardar-archivos-adjuntos', origen: 'requerimientos');
    }

    public function cancelarGuardadoRespuesta(): void
    {
        $this->dispatch('notify', message: 'Corrige los archivos antes de continuar.');
    }

    public function render()
    {
        $query = $this->queryBase()
            ->with(['solicitud.cliente', 'creadoPor'])
            ->when($this->buscar !== '', function ($q) {
                $buscar = trim($this->buscar);

                $q->where(function ($sub) use ($buscar) {
                    $sub->where('titulo', 'like', "%{$buscar}%")
                        ->orWhere('descripcion', 'like', "%{$buscar}%")
                        ->orWhereHas('solicitud.cliente', function ($cliente) use ($buscar) {
                            $cliente->where('nombre', 'like', "%{$buscar}%")
                                ->orWhere('razon_social', 'like', "%{$buscar}%")
                                ->orWhere('rfc', 'like', "%{$buscar}%");
                        });
                });
            })
            ->when($this->estado === 'activos', fn ($q) => $q->whereNotIn('estado', ['validado', 'cancelado']))
            ->when(!in_array($this->estado, ['', 'activos'], true), fn ($q) => $q->where('estado', $this->estado))
            ->latest();

        return view('livewire.solicitudes.mis-requerimientos', [
            'requerimientos' => $query->paginate($this->perPageValue($query, 10)),
            'requerimientoSeleccionado' => $this->requerimientoSeleccionado(),
        ]);
    }

    private function queryBase()
    {
        $user = auth()->user();

        return SolicitudRequerimiento::query()
            ->when($user->cliente_id, function ($q) use ($user) {
                $q->where('destinatario_tipo', 'cliente')
                    ->whereIn('tipo', ['normal', 'resultado'])
                    ->whereHas('solicitud', function ($solicitud) use ($user) {
                        $solicitud->where('cliente_id', $user->cliente_id);
                    });
            }, function ($q) use ($user) {
                $q->whereIn('tipo', ['normal', 'resultado'])
                    ->where('destinatario_tipo', 'interno')
                    ->where('destinatario_user_id', $user->id);
            });
    }

    private function requerimientoSeleccionado(): ?SolicitudRequerimiento
    {
        if (!$this->requerimientoIdSeleccionado) {
            return null;
        }

        return $this->queryBase()
            ->with([
                'solicitud.cliente',
                'solicitud.archivos',
                'solicitud.responsable',
                'solicitud.creadoPor',
                'creadoPor',
                'respondidoPor',
                'archivos',
            ])
            ->find($this->requerimientoIdSeleccionado);
    }

    private function validarRespuestaRequerimiento(SolicitudRequerimiento $requerimiento): bool
    {
        if ($requerimiento->esRequerimientoFormulario()) {
            $rules = [];
            $datosActuales = is_array($requerimiento->solicitud->datos_formulario)
                ? $requerimiento->solicitud->datos_formulario
                : [];

            foreach ($requerimiento->solicitud->campos_formulario as $campo) {
                $key = $campo['key'] ?? null;

                if (!$key) {
                    continue;
                }

                $required = (bool) ($campo['required'] ?? false);
                $type = $campo['type'] ?? 'text';
                $ruleKey = "formulario_respuesta.$key";
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

            $this->validate([
                'respuesta_texto' => ['nullable', 'string'],
            ]);

            return true;
        }

        $this->validate([
            'respuesta_texto' => ['required', 'string'],
        ]);

        return true;
    }

    private function mapearFormularioRespuesta(SolicitudRequerimiento $requerimiento): array
    {
        if (!$requerimiento->esRequerimientoFormulario()) {
            return [];
        }

        $datos = is_array($requerimiento->solicitud->datos_formulario)
            ? $requerimiento->solicitud->datos_formulario
            : [];

        $respuesta = [];

        foreach ($requerimiento->solicitud->campos_formulario as $campo) {
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

    private function formularioRespuestaNormalizada(SolicitudRequerimiento $requerimiento): array
    {
        $normalizado = [];
        $datosActuales = is_array($requerimiento->solicitud->datos_formulario)
            ? $requerimiento->solicitud->datos_formulario
            : [];

        foreach ($requerimiento->solicitud->campos_formulario as $campo) {
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
                    : ($datosActuales[$key] ?? null),
                default => $value === '' ? null : $value,
            };
        }

        return $normalizado;
    }

    public function continuarGuardadoRespuesta(): void
    {
        $requerimiento = $this->requerimientoSeleccionado();

        if (!$requerimiento || in_array($requerimiento->estado, ['validado', 'cancelado'], true)) {
            return;
        }

        if (!$this->validarRespuestaRequerimiento($requerimiento)) {
            return;
        }

        if ($requerimiento->esRequerimientoFormulario()) {
            $datosFormulario = $this->formularioRespuestaNormalizada($requerimiento);
            $archivosFormulario = $this->guardarArchivosFormulario($requerimiento->solicitud);

            if (!empty($archivosFormulario)) {
                $datosFormulario = array_replace($datosFormulario, $archivosFormulario);
            }

            $requerimiento->solicitud->update([
                'datos_formulario' => $datosFormulario,
                'estado_formulario' => 'respondido',
                'estado' => 'pendiente_cliente',
            ]);
        }

        if ($requerimiento->tipo === 'resultado') {
            $requerimiento->solicitud->update([
                'estado' => 'pendiente_cliente',
            ]);
        }

        $requerimiento->update([
            'respuesta_texto' => trim($this->respuesta_texto) !== '' ? $this->respuesta_texto : null,
            'respondido_por_user_id' => auth()->id(),
            'respondido_at' => now(),
            'estado' => 'respondido',
            'comentario_validacion' => null,
            'validado_por_user_id' => null,
            'validado_at' => null,
        ]);

        SolicitudHistorialService::registrar(
            $requerimiento->solicitud,
            'requerimiento_respondido',
            'Requerimiento respondido',
            'Se envio respuesta al requerimiento "' . $requerimiento->titulo . '".',
            auth()->id(),
            $requerimiento
        );

        SolicitudNotificacionService::notificarRespuestaEnviada($requerimiento);

        $this->dispatch('requerimiento-actualizado');
        $this->cerrarSidebar();
        $this->dispatch('notify', message: 'Respuesta guardada correctamente.');
    }

    private function guardarArchivosFormulario(Solicitud $solicitud): array
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

            $archivo = $this->formulario_respuesta[$key] ?? null;

            if (!$archivo || !is_object($archivo) || !method_exists($archivo, 'getClientOriginalName')) {
                continue;
            }

            $nombreAnterior = $datosActuales[$key] ?? null;
            if ($nombreAnterior) {
                $archivoAnterior = $solicitud->archivos()->where('nombre', $nombreAnterior)->first();

                if ($archivoAnterior) {
                    if ($archivoAnterior->archivo) {
                        Storage::disk('public')->delete($archivoAnterior->archivo);
                    }

                    $archivoAnterior->delete();
                }
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
}
