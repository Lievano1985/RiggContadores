<?php

namespace App\Livewire\Solicitudes;

use App\Livewire\Shared\HasPerPage;
use App\Models\Solicitud;
use App\Models\SolicitudRequerimiento;
use App\Services\SolicitudArchivosFormulario;
use App\Services\SolicitudHistorialService;
use App\Services\SolicitudNotificacionService;
use Illuminate\Validation\Rule;
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
    public array $nombresArchivosFormulario = [];

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
        $this->nombresArchivosFormulario = [];
        $this->formulario_respuesta = $this->mapearFormularioRespuesta($requerimiento);
        $this->sidebarVisible = true;
    }

    public function cerrarSidebar(): void
    {
        $this->sidebarVisible = false;
        $this->requerimientoIdSeleccionado = null;
        $this->respuesta_texto = '';
        $this->formulario_respuesta = [];
        $this->nombresArchivosFormulario = [];
    }

    public function guardarRespuesta(): void
    {
        try {
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
        } finally {
            if (($requerimiento ?? null) && $requerimiento->esRequerimientoFormulario()) {
                $this->apagarSpinner();
            }
        }
    }

    public function cancelarGuardadoRespuesta(): void
    {
        $this->dispatch('notify', message: 'Corrige los archivos antes de continuar.');
        $this->apagarSpinner();
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
                'solicitud.historial' => fn ($historial) => $historial->where(function ($eventos) {
                    $eventos->whereNull('solicitud_requerimiento_id')
                        ->orWhere('solicitud_requerimiento_id', $this->requerimientoIdSeleccionado);
                }),
                'solicitud.historial.user',
                'solicitud.historial.requerimiento.destinatario',
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

                if ($type === 'file') {
                    $rules["$ruleKey.*"] = ['required', 'file', 'max:20480'];
                }

                if ($type === 'multiselect') {
                    $rules["$ruleKey.*"] = ['required', 'string', 'distinct', Rule::in($campo['options'] ?? [])];
                }

                $rules[$ruleKey] = match ($type) {
                    'checkbox' => $required ? ['accepted'] : ['nullable', 'boolean'],
                    'number' => $required ? ['required', 'numeric'] : ['nullable', 'numeric'],
                    'date' => $required ? ['required', 'date'] : ['nullable', 'date'],
                    'select', 'text', 'textarea' => $required ? ['required'] : ['nullable'],
                    'multiselect' => $required ? ['required', 'array', 'min:1'] : ['nullable', 'array'],
                    'file' => $required && !$yaTieneArchivo ? ['required', 'array', 'min:1'] : ['nullable', 'array'],
                    default => ['nullable'],
                };
            }

            if (!empty($rules)) {
                $this->validate($rules);
            }

            $this->validate([
                'respuesta_texto' => ['nullable', 'string'],
                'nombresArchivosFormulario.*' => ['nullable', 'string', 'max:150'],
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
                ? []
                : (($campo['type'] ?? null) === 'multiselect' ? (array) ($datos[$key] ?? []) : ($datos[$key] ?? null));
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
                'multiselect' => array_values((array) $value),
                'file' => $datosActuales[$key] ?? null,
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
            $archivosFormulario = app(SolicitudArchivosFormulario::class)->guardar($requerimiento->solicitud, $this->formulario_respuesta, 'formulario_respuesta', $this->nombresArchivosFormulario);

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

        $requerimiento->solicitud->refresh();

        SolicitudHistorialService::registrar(
            $requerimiento->solicitud,
            'requerimiento_respondido',
            'Requerimiento respondido',
            'Se envio respuesta al requerimiento "' . $requerimiento->titulo . '".',
            auth()->id(),
            $requerimiento,
            [
                'texto' => $requerimiento->respuesta_texto,
                'formulario' => $requerimiento->esRequerimientoFormulario()
                    ? $requerimiento->solicitud->resumen_formulario
                    : null,
            ]
        );

        SolicitudNotificacionService::notificarRespuestaEnviada($requerimiento);

        $this->dispatch('requerimiento-actualizado');
        $this->cerrarSidebar();
        $this->dispatch('notify', message: 'Respuesta guardada correctamente.');
        $this->apagarSpinner();
    }

    private function apagarSpinner(): void
    {
        $this->js("window.dispatchEvent(new CustomEvent('spinner-off'))");
    }

}
