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
use App\Models\User;

class ListaNotificaciones extends Component
{
    public $cliente;
    public $sidebarVisible = false;
    public $notificacionSeleccionada = null;
    public string $sortField = 'created_at';
    public string $sortDirection = 'desc';
    
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
        $query = NotificacionCliente::where('cliente_id', $this->cliente->id)
            ->with('usuario');

        if ($this->sortField === 'usuario') {
            $query->orderBy(
                User::select('name')
                    ->whereColumn('users.id', 'notificaciones_clientes.user_id')
                    ->limit(1),
                $this->sortDirection
            );
        } elseif (in_array($this->sortField, ['created_at', 'asunto', 'periodo_mes'], true)) {
            $query->orderBy($this->sortField, $this->sortDirection);
        } else {
            $query->orderByDesc('created_at');
        }

        $notificaciones = $query->get();

        return view('livewire.notificaciones.lista-notificaciones', [
            'notificaciones' => $notificaciones
        ]);
    }

    public function sortBy(string $field): void
    {
        if (!in_array($field, ['created_at', 'asunto', 'periodo_mes', 'usuario'], true)) {
            return;
        }

        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }
}
