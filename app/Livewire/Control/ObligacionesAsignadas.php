<?php

namespace App\Livewire\Control;

use App\Models\Cliente;
use App\Models\ObligacionClienteContador;
use App\Models\TareaAsignada;
use App\Models\User;
use App\Services\ArbolCarpetas;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Carbon\Carbon;
use Livewire\WithPagination;

class ObligacionesAsignadas extends Component
{
    use WithPagination;

    protected $paginationTheme = 'tailwind';

    /* =====================================================
     | PROPIEDADES
     ===================================================== */
    public $cliente;
    public $clienteId;

    // Modo automático / manual
    public bool $modoAutomatico = true;

    // Filtros
    public $aniosDisponibles = [];
    public $filtroEjercicio;
    public $filtroMes;

    // UI Baja lógica
    public $motivoBaja = '';
    public $asignacionABaja = null;
    public $confirmarBaja = false;

    // Edición
    public $modoEdicion = false;
    public $asignacionIdEditando = null;
    public $obligacionSeleccionada = null;
    public $obligacionOriginalId = null;

    // Inputs
    public $obligacion_id;
    public $contador_id;
    public $fecha_vencimiento;
    public $carpeta_drive_id;

    // Listas
    public $contadores = [];
    public array $arbolCarpetas = [];

    // Modal
    public bool $modalVisible = false;
    public int $formKey = 0;

    protected $listeners = [
        'obligacionActualizada' => 'refrescarComponente'
    ];

    /* =====================================================
     | INIT
     ===================================================== */
    public function mount($cliente)
    {
/* Carbon::setTestNow(Carbon::create(2026, 2, 2)); 
 */
if (!auth()->user()->hasAnyRole(['admin_despacho','supervisor'])) {
    abort(403);
}        $this->cliente   = $cliente;
        $this->clienteId = $cliente->id;

        $this->filtroEjercicio = null;
        $this->filtroMes = null;

        $this->cargarAniosDisponibles();

        $this->contadores = User::role(['contador','Supervisor'])->orderBy('name')->get();
        $this->cargarArbolCarpetas();
    }

    /* =====================================================
     | REFRESH CENTRAL
     ===================================================== */
    public function refrescarComponente()
    {
        $this->resetPage();
        $this->cargarAniosDisponibles();
        $this->dispatch('$refresh'); // 👈 fuerza render

    }

    /* =====================================================
     | CARGA COMBO EJERCICIO
     ===================================================== */
    private function cargarAniosDisponibles()
    {
        $this->aniosDisponibles = ObligacionClienteContador::where('cliente_id',$this->clienteId)
            ->whereNotNull('ejercicio')
            ->distinct()
            ->orderBy('ejercicio','desc')
            ->pluck('ejercicio')
            ->toArray();

        /* if (!empty($this->aniosDisponibles) &&
            !in_array($this->filtroEjercicio,$this->aniosDisponibles)) {
            $this->filtroEjercicio = $this->aniosDisponibles[0];
        } */
    }

    /* =====================================================
     | QUERY PRINCIPAL
     ===================================================== */
    private function queryAsignaciones()
    {
        $query = ObligacionClienteContador::with(['obligacion','contador','carpeta'])
            ->where('cliente_id',$this->clienteId)
            ->where('is_activa',true);

        if ($this->modoAutomatico) {

            $inicioMes = now()->startOfMonth();
            $finMes = now()->endOfMonth();

            $query->where(function($q) use ($inicioMes,$finMes){

                // Mes actual
                $q->whereBetween('fecha_vencimiento',[$inicioMes,$finMes])

                // Vencidas arrastradas
                ->orWhere(function($q2) use ($inicioMes){
                    $q2->whereNotNull('fecha_vencimiento')
                       ->whereDate('fecha_vencimiento','<',$inicioMes)
                       ->where('estatus','!=','finalizado');
                })

                // Sin fecha
                ->orWhereNull('fecha_vencimiento');

            });

        } else {

            // MODO MANUAL REAL
            $query->where('ejercicio',$this->filtroEjercicio)
                  ->where('mes',$this->filtroMes);
        }

        return $query->orderBy('fecha_vencimiento','asc');
    }

    /* =====================================================
     | FILTROS
     ===================================================== */
    public function updatedFiltroEjercicio()
    {
        $this->modoAutomatico = false;
        $this->resetPage();
    }

    public function updatedFiltroMes()
    {
        $this->modoAutomatico = false;
        $this->resetPage();
    }

    /* =====================================================
     | BAJA LÓGICA
     ===================================================== */
    public function confirmarBajaAsignacion($id)
    {
        $this->asignacionABaja = ObligacionClienteContador::findOrFail($id);
        $this->motivoBaja = '';
        $this->confirmarBaja = true;
    }

    public function darDeBajaAsignacionConfirmada()
    {
        $this->validate([
            'motivoBaja' => 'nullable|string|max:255'
        ]);
    
        DB::transaction(function () {
    
            $asig = ObligacionClienteContador::with('obligacion')
                ->findOrFail($this->asignacionABaja->id);
    
            // 🔥 SI ES OBLIGACIÓN ÚNICA → ELIMINACIÓN TOTAL
            if ($asig->obligacion->periodicidad === 'unica') {
    
                // Elimina tareas ligadas
                $asig->tareasAsignadas()->delete();
    
                // Elimina asignación
                $asig->delete();
    
                return;
            }
    
            // 🔁 SI ES PERIÓDICA → BAJA LÓGICA
            $asig->update([
                'is_activa'    => false,
                'fecha_baja'   => now(),
                'motivo_baja'  => $this->motivoBaja ?: 'Baja manual',
            ]);
        });
    
        $this->confirmarBaja = false;
        $this->asignacionABaja = null;
        $this->motivoBaja = '';
    
        $this->refrescarComponente();
        $this->dispatch('obligacionEliminada');
    
        session()->flash('success', 'Obligación procesada correctamente.');
    }
    

    /* =====================================================
     | ÁRBOL DRIVE
     ===================================================== */
    private function cargarArbolCarpetas()
    {
        $servicio = new ArbolCarpetas();
        $this->arbolCarpetas = $servicio->obtenerArbol($this->clienteId);
    }

    /* =====================================================
     | EDICIÓN
     ===================================================== */
    public function editarAsignacion($id)
    {
        $asig = ObligacionClienteContador::with('obligacion')->findOrFail($id);

        $this->modoEdicion = true;
        $this->asignacionIdEditando = $id;
        $this->obligacionSeleccionada = $asig->obligacion;

        $this->contador_id = $asig->contador_id;
        $this->fecha_vencimiento = $asig->fecha_vencimiento;
        $this->carpeta_drive_id = $asig->carpeta_drive_id;

        $this->modalVisible = true;
    }

    /* =====================================================
     | GUARDAR EDICIÓN
     ===================================================== */
    public function guardar()
    {
        $this->validate([
            'contador_id'=>'required|exists:users,id',
            'fecha_vencimiento'=>'nullable|date',
            'carpeta_drive_id'=>'nullable|exists:carpeta_drives,id'
        ]);

        $asig = ObligacionClienteContador::findOrFail($this->asignacionIdEditando);

        $asig->update([
            'contador_id'=>$this->contador_id,
            'fecha_vencimiento'=>$this->fecha_vencimiento,
            'carpeta_drive_id'=>$this->carpeta_drive_id
        ]);

        $this->resetFormulario();
        $this->refrescarComponente();

        session()->flash('success','Asignación actualizada.');
    }

    private function resetFormulario()
    {
        $this->contador_id=null;
        $this->fecha_vencimiento=null;
        $this->carpeta_drive_id=null;

        $this->modoEdicion=false;
        $this->asignacionIdEditando=null;
        $this->obligacionSeleccionada=null;

        $this->modalVisible=false;
        $this->formKey++;
    }

    /* =====================================================
     | RENDER
     ===================================================== */
    public function render()
    {
        return view('livewire.control.obligaciones-asignadas',[
            'asignaciones'=>$this->queryAsignaciones()->paginate(10)
        ]);
    }
}
