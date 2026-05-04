<?php

namespace App\Livewire\Solicitudes;

use App\Livewire\Shared\HasPerPage;
use App\Models\SolicitudRequerimiento;
use App\Services\SolicitudHistorialService;
use App\Services\SolicitudNotificacionService;
use Livewire\Component;
use Livewire\WithPagination;

class MisRequerimientos extends Component
{
    use WithPagination, HasPerPage;

    public string $buscar = '';
    public string $estado = '';
    public bool $sidebarVisible = false;
    public ?int $requerimientoIdSeleccionado = null;
    public string $respuesta_texto = '';

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
            ->with(['solicitud.cliente', 'creadoPor', 'archivos'])
            ->find($requerimientoId);

        if (!$requerimiento) {
            return;
        }

        $this->requerimientoIdSeleccionado = $requerimiento->id;
        $this->respuesta_texto = $requerimiento->respuesta_texto ?? '';
        $this->sidebarVisible = true;
    }

    public function cerrarSidebar(): void
    {
        $this->sidebarVisible = false;
        $this->requerimientoIdSeleccionado = null;
        $this->respuesta_texto = '';
    }

    public function guardarRespuesta(): void
    {
        $requerimiento = $this->requerimientoSeleccionado();

        if (!$requerimiento || in_array($requerimiento->estado, ['validado', 'cancelado'], true)) {
            return;
        }

        $this->validate([
            'respuesta_texto' => ['required', 'string'],
        ]);

        $this->dispatch('guardar-archivos-adjuntos', origen: 'requerimientos');
    }

    public function continuarGuardadoRespuesta(): void
    {
        $requerimiento = $this->requerimientoSeleccionado();

        if (!$requerimiento || in_array($requerimiento->estado, ['validado', 'cancelado'], true)) {
            return;
        }

        $this->validate([
            'respuesta_texto' => ['required', 'string'],
        ]);

        $requerimiento->update([
            'respuesta_texto' => $this->respuesta_texto,
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
            ->when($this->estado !== '', fn ($q) => $q->where('estado', $this->estado))
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
            ->where('tipo', 'normal')
            ->when($user->cliente_id, function ($q) use ($user) {
                $q->where('destinatario_tipo', 'cliente')
                    ->whereHas('solicitud', function ($solicitud) use ($user) {
                        $solicitud->where('cliente_id', $user->cliente_id);
                    });
            }, function ($q) use ($user) {
                $q->where('destinatario_tipo', 'interno')
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
                'solicitud.responsable',
                'creadoPor',
                'respondidoPor',
                'archivos',
            ])
            ->find($this->requerimientoIdSeleccionado);
    }
}
