<?php

namespace App\Livewire\Control;

use App\Models\Cliente;
use App\Models\ObligacionClienteContador;
use App\Models\User;
use App\Services\ArbolCarpetas;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class ObligacionesAsignadas extends Component
{
    public $cliente;
    public $clienteId;
    public bool $asignacionCompleta = false;

    // Inputs del formulario
    public $obligacion_id;
    public $contador_id;
    public $fecha_vencimiento;
    public $carpeta_drive_id;

    // Para mostrar el modal de confirmación
    public $confirmarEliminacion = false;
    public $asignacionAEliminar = null;
    public $tareasRelacionadas = [];
    public $clienteIdobligacionesCompletadas;

    // Para saber si estamos en modo edición
    public $modoEdicion = false;
    public $asignacionIdEditando = null;
    public $obligacionSeleccionada = null;

    // Listas
    public $obligacionesDisponibles = [];
    public $contadores             = [];
    public $asignaciones           = [];
    public array $arbolCarpetas    = [];
    public $obligacionesCompletadas;
    public int $formKey = 0;
    public bool $modalVisible = false;

    protected $listeners = [
        'obligacionActualizada' => 'actualizarAsignaciones'
    ];

    public function mount($cliente)
    {
        $this->cliente    = $cliente;
        $this->clienteId  = $cliente->id;

        $this->cargarObligacionesDisponibles();
        $this->contadores        = User::role('contador')->orderBy('name')->get();
        $this->verificarAsignacionesCompletas();
        $this->cargarAsignaciones();
        $this->cargarArbolCarpetas();
    }

    public function actualizarAsignaciones()
    {
        $this->cargarObligacionesDisponibles();
        $this->cargarAsignaciones();
        $this->verificarAsignacionesCompletas();
    }

    private function cargarObligacionesDisponibles()
    {
        $obligacionesCliente = Cliente::find($this->clienteId)
            ->obligaciones()
            ->orderBy('nombre')
            ->get();

        $yaAsignadas = DB::table('obligacion_cliente_contador')
            ->where('cliente_id', $this->clienteId)
            ->pluck('obligacion_id')
            ->toArray();

        $this->obligacionesDisponibles = $obligacionesCliente
            ->filter(fn($o) => !in_array($o->id, $yaAsignadas));
    }

    private function cargarAsignaciones()
    {
        $this->asignaciones = ObligacionClienteContador::with(['obligacion', 'contador', 'carpeta'])
            ->where('cliente_id', $this->clienteId)
            ->get();
    }

    private function cargarArbolCarpetas()
    {
        $servicio = new ArbolCarpetas();
        $this->arbolCarpetas = $servicio->obtenerArbol($this->clienteId);
    }

    // === Mostrar modal para crear ===
    public function mostrarModalCrear()
    {
        $this->resetFormulario();
        $this->modalVisible = true;
    }

    // === Mostrar modal para editar ===
    public function editarAsignacion($id)
    {
        $asignacion = ObligacionClienteContador::with('obligacion')->findOrFail($id);

        $this->obligacion_id        = $asignacion->obligacion_id;
        $this->obligacionSeleccionada = $asignacion->obligacion; // para mostrar el nombre
        $this->contador_id          = $asignacion->contador_id;
        $this->carpeta_drive_id     = $asignacion->carpeta_drive_id;
        $this->fecha_vencimiento    = $asignacion->fecha_vencimiento;

        $this->modoEdicion          = true;
        $this->asignacionIdEditando = $asignacion->id; // ✅ importante

        $this->modalVisible = true;
        $this->cargarArbolCarpetas();
    }

    // === Guardar (crear o editar) ===
    public function guardar()
    {
        $this->validate([
            'obligacion_id'     => 'required|exists:obligaciones,id',
            'contador_id'       => 'required|exists:users,id',
            'fecha_vencimiento' => 'nullable|date', // se vuelve opcional
            'carpeta_drive_id'  => 'nullable|exists:carpeta_drives,id',
        ]);

        // Obtener la obligación base
        $obligacionBase = \App\Models\Obligacion::findOrFail($this->obligacion_id);
        $periodicidad   = strtolower($obligacionBase->periodicidad ?? 'mensual');

        // === Validación de duplicados ===
        $existeQuery = ObligacionClienteContador::where('cliente_id', $this->clienteId)
            ->where('obligacion_id', $this->obligacion_id);

        if ($this->modoEdicion && $this->asignacionIdEditando) {
            $existeQuery->where('id', '!=', $this->asignacionIdEditando); // ✅ excluir actual
        }

        if ($existeQuery->exists()) {
            $this->addError('obligacion_id', 'Esta obligación ya fue asignada.');
            return;
        }

        // === EDICIÓN ===
        if ($this->modoEdicion && $this->asignacionIdEditando) {
            $asignacion = ObligacionClienteContador::findOrFail($this->asignacionIdEditando);

            if (!in_array($periodicidad, ['unica', 'única', 'eventual'], true) && empty($this->fecha_vencimiento)) {
                $fechaVenc = $obligacionBase->calcularFechaVencimiento(now()->year, now()->month)?->toDateString();
            } else {
                $fechaVenc = $this->fecha_vencimiento ? \Carbon\Carbon::parse($this->fecha_vencimiento)->toDateString() : null;
            }

            $asignacion->update([
                'contador_id'       => $this->contador_id,
                'fecha_vencimiento' => $fechaVenc,
                'carpeta_drive_id'  => $this->carpeta_drive_id,
            ]);

            session()->flash('success', 'Asignación actualizada correctamente.');
        }
        // === CREACIÓN ===
        else {
            if (!in_array($periodicidad, ['unica', 'única', 'eventual'], true)) {
                $fechaVenc = $this->fecha_vencimiento
                    ? \Carbon\Carbon::parse($this->fecha_vencimiento)->toDateString()
                    : $obligacionBase->calcularFechaVencimiento(now()->year, now()->month)?->toDateString();
            } else {
                $fechaVenc = $this->fecha_vencimiento ? \Carbon\Carbon::parse($this->fecha_vencimiento)->toDateString() : null;
            }

            ObligacionClienteContador::create([
                'cliente_id'        => $this->clienteId,
                'obligacion_id'     => $this->obligacion_id,
                'contador_id'       => $this->contador_id,
                'fecha_asignacion'  => now(),
                'fecha_vencimiento' => $fechaVenc,
                'carpeta_drive_id'  => $this->carpeta_drive_id,
                'estatus'           => 'asignada'
            ]);

            session()->flash('success', 'Obligación asignada correctamente.');
        }

        $this->resetFormulario();
        $this->cargarAsignaciones();
        $this->cargarObligacionesDisponibles();
        $this->verificarAsignacionesCompletas();
        $this->dispatch('obligacionesCambiadas');
    }

    public function getPeriodicidad($id)
    {
        return \App\Models\Obligacion::find($id)?->periodicidad ?? '';
    }

    public function eliminarAsignacion($id)
    {
        $asignacion = ObligacionClienteContador::findOrFail($id);
        $asignacion->tareasAsignadas()->delete();
        $asignacion->delete();

        $this->cargarAsignaciones();
        $this->cargarObligacionesDisponibles();
        $this->verificarAsignacionesCompletas();
        $this->dispatch('obligacionesCambiadas');

        session()->flash('success', 'Asignación y tareas relacionadas eliminadas.');
    }

    public function confirmarEliminacionAsignacion($id)
    {
        $asignacion = ObligacionClienteContador::findOrFail($id);
        $tareas     = $asignacion->tareasAsignadas()->with('tareaCatalogo')->get();

        if ($tareas->isNotEmpty()) {
            $this->asignacionAEliminar = $id;
            $this->tareasRelacionadas  = $tareas;
            $this->confirmarEliminacion = true;
        } else {
            $this->eliminarAsignacion($id);
        }
    }

    public function eliminarAsignacionConfirmada()
    {
        $asignacion = ObligacionClienteContador::findOrFail($this->asignacionAEliminar);
        $asignacion->tareasAsignadas()->delete();
        $asignacion->delete();

        $this->confirmarEliminacion = false;
        $this->asignacionAEliminar  = null;
        $this->tareasRelacionadas   = [];

        $this->cargarAsignaciones();
        $this->cargarObligacionesDisponibles();
        $this->dispatch('obligacionEliminada');
        $this->verificarAsignacionesCompletas();

        session()->flash('success', 'Obligación y tareas asociadas eliminadas.');
    }

    private function resetFormulario(): void
    {
        $this->obligacion_id        = '';
        $this->contador_id          = '';
        $this->fecha_vencimiento    = null;
        $this->carpeta_drive_id     = null;
        $this->modoEdicion          = false;
        $this->asignacionIdEditando = null;
        $this->formKey++;
        $this->resetErrorBag();
        $this->resetValidation();
        $this->modalVisible = false;
    }

    private function verificarAsignacionesCompletas()
    {
        $totalObligaciones = Cliente::find($this->clienteId)
            ->obligaciones()
            ->count();

        $obligacionesAsignadas = ObligacionClienteContador::where('cliente_id', $this->clienteId)
            ->count();

        $this->asignacionCompleta = ($totalObligaciones > 0 && $obligacionesAsignadas >= $totalObligaciones);
        $this->obligacionesCompletadas = $this->asignacionCompleta;

        $this->dispatch('estado-obligaciones', completed: $this->obligacionesCompletadas);
    }

    public function render()
    {
        return view('livewire.control.obligaciones-asignadas');
    }
}
