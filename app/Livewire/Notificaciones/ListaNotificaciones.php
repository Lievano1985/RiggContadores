<?php

/**
 * Autor: Luis Liévano - JL3 Digital
 *
 * Componente: ListaNotificaciones
 * Función:
 * Muestra historial de notificaciones enviadas al cliente.
 */

namespace App\Livewire\Notificaciones;

use Livewire\Component;
use App\Models\NotificacionCliente;

class ListaNotificaciones extends Component
{
    public $cliente;
    public $sidebarVisible = false;
    public $notificacionSeleccionada = null;
    
    public function mount($cliente)
    {
        $this->cliente = $cliente;
    }
    public function abrirSidebar($id)
    {
        $this->notificacionSeleccionada =
            NotificacionCliente::with(['usuario','obligaciones','archivos'])
                ->findOrFail($id);
    
        $this->sidebarVisible = true;
    }
    
    public function cerrarSidebar()
    {
        $this->sidebarVisible = false;
        $this->notificacionSeleccionada = null;
    }
    
    public function render()
    {
        $notificaciones = NotificacionCliente::where('cliente_id', $this->cliente->id)
        ->with('usuario')
            ->orderByDesc('created_at')
            ->get();

        return view('livewire.notificaciones.lista-notificaciones', [
            'notificaciones' => $notificaciones
        ]);
    }
}
