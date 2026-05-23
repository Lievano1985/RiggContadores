<?php

namespace App\Livewire\Solicitudes;

use App\Livewire\Shared\HasPerPage;
use App\Models\Cliente;
use App\Models\SolicitudNotificacion;
use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;

class MisNotificaciones extends Component
{
    use WithPagination, HasPerPage;

    public string $estado = 'pendientes';
    public string $usuario = '';
    public string $cliente = '';

    protected $paginationTheme = 'tailwind';

    public function updatedEstado(): void
    {
        $this->resetPage();
    }

    public function updatedUsuario(): void
    {
        $this->resetPage();
    }

    public function updatedCliente(): void
    {
        $this->resetPage();
    }

    public function marcarLeida(int $notificacionId): void
    {
        SolicitudNotificacion::query()
            ->where('user_id', auth()->id())
            ->whereKey($notificacionId)
            ->whereNull('leida_at')
            ->update(['leida_at' => now()]);
    }

    public function marcarTodasLeidas(): void
    {
        SolicitudNotificacion::query()
            ->where('user_id', auth()->id())
            ->whereNull('leida_at')
            ->update(['leida_at' => now()]);

        $this->dispatch('notify', message: 'Notificaciones marcadas como leidas.');
    }

    public function render()
    {
        $usuario = auth()->user();
        $puedeVerOtras = $this->usuarioPuedeVerNotificacionesDeOtros();

        $query = $this->consultaNotificaciones()
            ->when($this->estado === 'pendientes', fn ($q) => $q->whereNull('leida_at'))
            ->when($this->estado === 'leidas', fn ($q) => $q->whereNotNull('leida_at'))
            ->when($puedeVerOtras && $this->usuario !== '', function ($q) {
                $usuarioId = (int) $this->usuario;

                $q->where(function ($sub) use ($usuarioId) {
                    $sub->whereHas('requerimiento', fn ($req) => $req->where('destinatario_user_id', $usuarioId))
                        ->orWhereHas('solicitud', fn ($sol) => $sol->where('responsable_user_id', $usuarioId));
                });
            })
            ->when($puedeVerOtras && $this->cliente !== '', function ($q) {
                $clienteId = (int) $this->cliente;

                $q->whereHas('solicitud', fn ($sol) => $sol->where('cliente_id', $clienteId));
            })
            ->latest();

        $pendientes = SolicitudNotificacion::query()
            ->where('user_id', auth()->id())
            ->whereNull('leida_at')
            ->count();

        $usuariosFiltro = User::query()
            ->where('despacho_id', $usuario->despacho_id)
            ->whereNull('cliente_id')
            ->orderBy('name')
            ->get(['id', 'name']);

        $clientesFiltro = Cliente::query()
            ->where('despacho_id', $usuario->despacho_id)
            ->orderByRaw('coalesce(nombre, razon_social)')
            ->get(['id', 'nombre', 'razon_social']);

        return view('livewire.solicitudes.mis-notificaciones', [
            'notificaciones' => $query->paginate($this->perPageValue($query, 10)),
            'pendientes' => $pendientes,
            'usuariosFiltro' => $usuariosFiltro,
            'clientesFiltro' => $clientesFiltro,
            'puedeVerOtras' => $puedeVerOtras,
        ]);
    }

    private function usuarioPuedeVerNotificacionesDeOtros(): bool
    {
        return auth()->user()->hasAnyRole(['admin_despacho', 'supervisor']);
    }

    private function consultaNotificaciones()
    {
        $usuario = auth()->user();
        $puedeVerOtras = $this->usuarioPuedeVerNotificacionesDeOtros();

        return SolicitudNotificacion::query()
            ->with([
                'user',
                'solicitud.cliente',
                'solicitud.responsable',
                'solicitud.creadoPor',
                'requerimiento.destinatario',
                'requerimiento.respondidoPor',
                'requerimiento.creadoPor',
            ])
            ->when(!$puedeVerOtras, fn ($q) => $q->where('user_id', auth()->id()))
            ->when($puedeVerOtras, function ($q) use ($usuario) {
                $q->whereHas('user', function ($userQuery) use ($usuario) {
                    $userQuery->where('despacho_id', $usuario->despacho_id)
                        ->whereNull('cliente_id');
                });
            });
    }
}
