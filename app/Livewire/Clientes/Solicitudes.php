<?php

namespace App\Livewire\Clientes;

use App\Models\Cliente;
use App\Models\Solicitud as SolicitudModel;
use App\Models\User;
use Illuminate\Validation\Rule;
use Livewire\Component;

class Solicitudes extends Component
{
    public Cliente $cliente;
    public $responsable_solicitudes_id = null;
    public $usuariosResponsables = [];

    public function mount(Cliente $cliente): void
    {
        $this->cliente = $cliente;
        $this->responsable_solicitudes_id = $cliente->responsable_solicitudes_id;
        $this->cargarUsuariosResponsables();
    }

    public function guardarResponsable(): void
    {
        if (!auth()->user()->hasAnyRole(['admin_despacho', 'supervisor'])) {
            abort(403);
        }

        $this->validate([
            'responsable_solicitudes_id' => [
                'nullable',
                'integer',
                Rule::in(collect($this->usuariosResponsables)->pluck('id')->all()),
            ],
        ]);

        $this->cliente->update([
            'responsable_solicitudes_id' => $this->responsable_solicitudes_id ?: null,
        ]);

        $this->cliente->refresh();
        $this->responsable_solicitudes_id = $this->cliente->responsable_solicitudes_id;

        $this->dispatch('notify', message: 'Responsable de solicitudes actualizado.');
    }

    private function cargarUsuariosResponsables(): void
    {
        $this->usuariosResponsables = User::query()
            ->where('despacho_id', $this->cliente->despacho_id)
            ->whereNull('cliente_id')
            ->orderBy('name')
            ->get(['id', 'name', 'email']);
    }

    public function render()
    {
        $solicitudes = SolicitudModel::query()
            ->with(['responsable', 'obligacion', 'creadoPor'])
            ->where('cliente_id', $this->cliente->id)
            ->latest()
            ->get();

        return view('livewire.clientes.solicitudes', compact('solicitudes'));
    }
}
