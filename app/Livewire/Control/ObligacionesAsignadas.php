<?php

namespace App\Livewire\Control;

use App\Models\Cliente;
use App\Models\ObligacionClienteContador;
use App\Models\TareaAsignada;
use App\Models\User;
use App\Services\ArbolCarpetas;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class ObligacionesAsignadas extends Component
{
    public $cliente;
    public $clienteId;
    public bool $asignacionCompleta = false;
    public $motivoBaja = '';
    public $asignacionABaja = null;
    public $confirmarBaja = false;
    // Inputs del formulario
    public $obligacion_id;
    public $contador_id;
    public $fecha_vencimiento;
    public $carpeta_drive_id;


    public $filtroEjercicio;
    public $filtroMes;
    // Para saber si estamos en modo edición
    public $modoEdicion = true;
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


    /**
     * Mostrar modal de baja lógica
     */
    public function confirmarBajaAsignacion($id)
    {
        $this->asignacionABaja = ObligacionClienteContador::findOrFail($id);
        $this->motivoBaja = '';
        $this->confirmarBaja = true;
    }

    /**
     * Confirmar y ejecutar baja lógica
     */
    public function darDeBajaAsignacionConfirmada()
    {
        $this->validate([
            'motivoBaja' => 'nullable|string|max:255',
        ]);

        try {
            DB::beginTransaction();

            $asignacion = ObligacionClienteContador::findOrFail($this->asignacionABaja->id);

            // 1️⃣ Dar de baja la asignación seleccionada
            $asignacion->update([
                'is_activa'   => false,
                'fecha_baja'  => now(),
                'motivo_baja' => $this->motivoBaja ?: 'Baja manual desde interfaz.',
            ]);

            // 1.1️⃣ Cancelar sus tareas
            TareaAsignada::where('obligacion_cliente_contador_id', $asignacion->id)
                ->update(['estatus' => 'cancelada']);

            // 2️⃣ Dar de baja TODAS las demás asignaciones activas del mismo cliente y obligación
            $otrasAsignaciones = ObligacionClienteContador::where('cliente_id', $asignacion->cliente_id)
                ->where('obligacion_id', $asignacion->obligacion_id)
                ->where('id', '!=', $asignacion->id)
                ->where('is_activa', true)
                ->get();

            foreach ($otrasAsignaciones as $otra) {
                $otra->update([
                    'is_activa'   => false,
                    'fecha_baja'  => now(),
                    'motivo_baja' => 'Baja automática al dar de baja otra instancia de la misma obligación.',
                ]);

                // 2.1️⃣ Cancelar también las tareas de esas asignaciones
                TareaAsignada::where('obligacion_cliente_contador_id', $otra->id)
                    ->update(['estatus' => 'cancelada']);
            }

            // 3️⃣ Si existe el vínculo pivote cliente_obligacion, se mantiene
            // (no se elimina, para que el checkbox siga mostrándose como seleccionado en Datos Fiscales)

            DB::commit();

            // 4️⃣ Reset de estados del componente
            $this->confirmarBaja = false;
            $this->asignacionABaja = null;
            $this->motivoBaja = '';

            $this->cargarAsignaciones();
            $this->cargarObligacionesDisponibles();
            $this->verificarAsignacionesCompletas();

            session()->flash('success', 'Obligación dada de baja correctamente (todas las instancias actualizadas).');
            $this->dispatch('obligacionesCambiadas');
        } catch (\Throwable $e) {
            DB::rollBack();
            session()->flash('error', 'Error al dar de baja: ' . $e->getMessage());
        }
    }

    /**
     * Reactiva una obligación dada de baja.
     */
    public function reactivarAsignacion($id)
    {
        try {
            DB::beginTransaction();

            $asignacion = ObligacionClienteContador::findOrFail($id);

            $asignacion->update([
                'is_activa' => true,
                'fecha_baja' => null,
                'motivo_baja' => null,
            ]);

            // Opcional: también podrías reabrir tareas canceladas si lo deseas:
            // $asignacion->tareasAsignadas()->where('estatus', 'cancelada')->update(['estatus' => 'asignada']);
            // ✅ Reactivar tareas canceladas
            $asignacion->tareasAsignadas()
                ->where('estatus', 'cancelada')
                ->update(['estatus' => 'asignada']);
            DB::commit();

            $this->cargarAsignaciones();
            $this->cargarObligacionesDisponibles();
            $this->verificarAsignacionesCompletas();

            session()->flash('success', 'Obligación reactivada correctamente.');
            $this->dispatch('obligacionesCambiadas');
        } catch (\Throwable $e) {
            DB::rollBack();
            session()->flash('error', 'Error al reactivar la obligación: ' . $e->getMessage());
        }
    }


    public function mount($cliente)
    {
        $this->cliente    = $cliente;
        $this->clienteId  = $cliente->id;

        $this->cargarObligacionesDisponibles();
        $this->contadores        = User::role('contador')->orderBy('name')->get();
        $this->verificarAsignacionesCompletas();
        $this->cargarAsignaciones();
        $this->cargarArbolCarpetas();
        $this->filtroEjercicio = now()->year;
        $this->filtroMes = now()->month;
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

        // ✅ Si estamos editando, permitimos que la obligación actual se muestre
        if ($this->modoEdicion && $this->asignacionIdEditando) {
            $obligacionActual = ObligacionClienteContador::find($this->asignacionIdEditando);
            if ($obligacionActual) {
                $yaAsignadas = array_diff($yaAsignadas, [$obligacionActual->obligacion_id]);
            }
        }

        $this->obligacionesDisponibles = $obligacionesCliente
            ->filter(fn($o) => !in_array($o->id, $yaAsignadas));
    }
    private function cargarAsignaciones()
    {
        $añoActual = now()->year;
        $mesActual = now()->month;
    
        $query = ObligacionClienteContador::with(['obligacion', 'contador', 'carpeta'])
            ->where('cliente_id', $this->clienteId)
            ->where('is_activa', true); // solo las activas
    
        // === FILTRO AUTOMÁTICO (inicio o mes actual) ===
        if (empty($this->filtroEjercicio) || empty($this->filtroMes) ||
            ((int)$this->filtroEjercicio === $añoActual && (int)$this->filtroMes === $mesActual)) {
    
            $query->where(function ($q) use ($añoActual, $mesActual) {
                $q->whereYear('fecha_vencimiento', $añoActual)
                  ->whereMonth('fecha_vencimiento', '<=', $mesActual);
            })
            ->where('estatus', '!=', 'finalizado');
        }
    
        // === FILTRO MANUAL (cuando el usuario elige año/mes) ===
        else {
            $query->whereYear('fecha_vencimiento', $this->filtroEjercicio)
                  ->whereMonth('fecha_vencimiento', $this->filtroMes);
        }
    
        // === Ordenar por más próximas primero ===
        $this->asignaciones = $query
            ->orderBy('fecha_vencimiento', 'asc')
            ->get();
    }
    


    public function updatedFiltroEjercicio()
    {
        $this->cargarAsignaciones();
    }

    public function updatedFiltroMes()
    {
        $this->cargarAsignaciones();
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

        $this->modoEdicion          = true;
        $this->asignacionIdEditando = $asignacion->id;

        // ✅ Cargar obligaciones disponibles considerando la actual
        $this->cargarObligacionesDisponibles();

        $this->obligacion_id        = $asignacion->obligacion_id;
        $this->obligacionSeleccionada = $asignacion->obligacion;
        $this->contador_id          = $asignacion->contador_id;
        $this->carpeta_drive_id     = $asignacion->carpeta_drive_id;
        $this->fecha_vencimiento    = $asignacion->fecha_vencimiento;

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
        // === Validación de duplicados (solo activas) ===
        $existeQuery = ObligacionClienteContador::where('cliente_id', $this->clienteId)
            ->where('obligacion_id', $this->obligacion_id)
            ->where('is_activa', true); // ✅ solo las activas

        if ($this->modoEdicion && $this->asignacionIdEditando) {
            $existeQuery->where('id', '!=', $this->asignacionIdEditando); // excluir actual
        }

        if ($existeQuery->exists()) {
            $this->addError('obligacion_id', 'Esta obligación ya fue asignada y sigue activa.');
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






    private function resetFormulario(): void
    {
        $this->obligacion_id        = '';
        $this->contador_id          = '';
        $this->fecha_vencimiento    = null;
        $this->carpeta_drive_id     = null;
        $this->modoEdicion = false;
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
