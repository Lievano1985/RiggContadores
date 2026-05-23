<?php

namespace App\Livewire\Solicitudes;

use App\Models\SolicitudNotificacion;
use Livewire\Component;

class NotificationBell extends Component
{
    public function render()
    {
        $visible = auth()->check() && auth()->user()->hasAnyRole([
            'admin_despacho',
            'supervisor',
            'contador',
            'cliente',
        ]);

        $total = $visible
            ? SolicitudNotificacion::query()
                ->where('user_id', auth()->id())
                ->whereNull('leida_at')
                ->count()
            : 0;

        return view('livewire.solicitudes.notification-bell', [
            'visible' => $visible,
            'total' => $total,
        ]);
    }
}
