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

    public $aniosDisponibles = [];
    public $filtroEjercicio;
    public $filtroMes;

    // Edición
    public $modoEdicion = false;
    public $asignacionIdEditando = null;
    public $obligacionSeleccionada = null;

    // Listas
    public $obligacionesDisponibles = [];
    public $contadores = [];
    public array $arbolCarpetas = [];

    public $obligacionesCompletadas;
    public int $formKey = 0;
    public bool $modalVisible = false;
    public $obligacionOriginalId;

    protected $listeners = [
        'obligacionActualizada' => 'actualizarAsignaciones'
    ];

    /* =======================================================
     | BAJA LÓGICA
     ======================================================= */
    public function confirmarBajaAsignacion($id)
    {
        $this->asignacionABaja = ObligacionClienteContador::findOrFail($id);
        $this->motivoBaja = '';
        $this->confirmarBaja = true;
    }

    public function darDeBajaAsignacionConfirmada()
    {
        $this->validate([
            'motivoBaja' => 'nullable|string|max:255',
        ]);

        try {
            DB::beginTransaction();

            $asignacion = ObligacionClienteContador::findOrFail($this->asignacionABaja->id);

            $asignacion->update([
                'is_activa'   => false,
                'fecha_baja'  => now(),
                'motivo_baja' => $this->motivoBaja ?: 'Baja manual desde interfaz.',
            ]);

            TareaAsignada::where('obligacion_cliente_contador_id', $asignacion->id)
                ->update(['estatus' => 'cancelada']);

            $otrasAsignaciones = ObligacionClienteContador::where('cliente_id', $asignacion->cliente_id)
                ->where('obligacion_id', $asignacion->obligacion_id)
                ->where('id', '!=', $asignacion->id)
                ->where('is_activa', true)
                ->get();

            foreach ($otrasAsignaciones as $otra) {
                $otra->update([
                    'is_activa'   => false,
                    'fecha_baja'  => now(),
                    'motivo_baja' => 'Baja automática al dar de baja otra instancia.',
                ]);

                TareaAsignada::where('obligacion_cliente_contador_id', $otra->id)
                    ->update(['estatus' => 'cancelada']);
            }

            DB::commit();

            $this->confirmarBaja = false;
            $this->asignacionABaja = null;
            $this->motivoBaja = '';

            $this->resetPage();

            session()->flash('success', 'Obligación dada de baja correctamente.');
            $this->dispatch('obligacionesCambiadas');

        } catch (\Throwable $e) {
            DB::rollBack();
            session()->flash('error', 'Error: ' . $e->getMessage());
        }
    }

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

            $asignacion->tareasAsignadas()
                ->where('estatus', 'cancelada')
                ->update(['estatus' => 'asignada']);

            DB::commit();

            $this->resetPage();

            session()->flash('success', 'Obligación reactivada.');
            $this->dispatch('obligacionesCambiadas');

        } catch (\Throwable $e) {
            DB::rollBack();
            session()->flash('error', 'Error: ' . $e->getMessage());
        }
    }

    /* =======================================================
     | INIT
     ======================================================= */
    public function mount($cliente)
    {
        $this->modoAutomatico = true;

/*         Carbon::setTestNow(Carbon::create(2026, 2, 2));
 */        $this->cliente   = $cliente;
        $this->clienteId = $cliente->id;

        $this->aniosDisponibles = ObligacionClienteContador::where('cliente_id', $this->clienteId)
        ->whereNotNull('ejercicio')
        ->distinct()
        ->orderBy('ejercicio', 'desc')
        ->pluck('ejercicio')
        ->toArray();
    

        $this->filtroEjercicio = now()->year;
        $this->filtroMes = now()->month;

        $this->contadores = User::role('contador')->orderBy('name')->get();
        $this->cargarObligacionesDisponibles();
        $this->verificarAsignacionesCompletas();
        $this->cargarArbolCarpetas();
    }

    public function actualizarAsignaciones()
    {
        $this->resetPage();
        $this->cargarObligacionesDisponibles();
        $this->verificarAsignacionesCompletas();
    }

    /* =======================================================
     | OBLIGACIONES DISPONIBLES
     ======================================================= */
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

        if ($this->modoEdicion && $this->asignacionIdEditando) {
            $actual = ObligacionClienteContador::find($this->asignacionIdEditando);
            if ($actual) {
                $yaAsignadas = array_diff($yaAsignadas, [$actual->obligacion_id]);
            }
        }

        $this->obligacionesDisponibles = $obligacionesCliente
            ->filter(fn($o) => !in_array($o->id, $yaAsignadas));
    }

    /* =======================================================
     | QUERY BASE PAGINADA
     ======================================================= */
     private function queryAsignaciones()
{
    $query = ObligacionClienteContador::with(['obligacion','contador','carpeta'])
        ->where('cliente_id', $this->clienteId)
        ->where('is_activa', true);

    if ($this->modoAutomatico) {

        $inicioMes = now()->startOfMonth();
        $finMes    = now()->endOfMonth();

        $query->where(function ($q) use ($inicioMes, $finMes) {

            // Mes actual
            $q->whereBetween('fecha_vencimiento', [$inicioMes, $finMes])

            // Vencidas
            ->orWhere(function ($q2) use ($inicioMes) {
                $q2->whereNotNull('fecha_vencimiento')
                   ->whereDate('fecha_vencimiento', '<', $inicioMes)
                   ->where('estatus','!=','finalizado');
            });
        });

    } else {

        // MODO MANUAL REAL
        $query->where('ejercicio', $this->filtroEjercicio)
              ->where('mes', $this->filtroMes);
    }

    return $query->orderBy('fecha_vencimiento','asc');
}

     
    /* =======================================================
     | FILTROS
     ======================================================= */
    public function updatedFiltroEjercicio()
    {    $this->modoAutomatico = false;

        $this->resetPage();
    }

    public function updatedFiltroMes()
    {    $this->modoAutomatico = false;

        $this->resetPage();
    }

    private function cargarArbolCarpetas()
    {
        $servicio = new ArbolCarpetas();
        $this->arbolCarpetas = $servicio->obtenerArbol($this->clienteId);
    }

    /* =======================================================
     | MODALES
     ======================================================= */
    public function mostrarModalCrear()
    {
        $this->resetFormulario();
        $this->modalVisible = true;
    }

    public function editarAsignacion($id)
    {
        $asignacion = ObligacionClienteContador::with('obligacion')->findOrFail($id);

        $this->modoEdicion = true;
        $this->asignacionIdEditando = $id;

        $this->cargarObligacionesDisponibles();

        $this->obligacion_id = $asignacion->obligacion_id;
        $this->obligacionOriginalId = $asignacion->obligacion_id;
        $this->obligacionSeleccionada = $asignacion->obligacion;

        $this->contador_id = $asignacion->contador_id;
        $this->carpeta_drive_id = $asignacion->carpeta_drive_id;
        $this->fecha_vencimiento = $asignacion->fecha_vencimiento;

        $this->modalVisible = true;
        $this->cargarArbolCarpetas();
    }

    /* =======================================================
     | GUARDAR
     ======================================================= */
    public function guardar()
    {
        $this->validate([
            'obligacion_id'     => 'required|exists:obligaciones,id',
            'contador_id'       => 'required|exists:users,id',
            'fecha_vencimiento' => 'nullable|date',
            'carpeta_drive_id'  => 'nullable|exists:carpeta_drives,id',
        ]);

        $obligacionBase = \App\Models\Obligacion::findOrFail($this->obligacion_id);
        $periodicidad = strtolower($obligacionBase->periodicidad ?? 'mensual');

        $validarDuplicado = true;

        if (
            $this->modoEdicion &&
            $this->asignacionIdEditando &&
            $this->obligacion_id == $this->obligacionOriginalId
        ) {
            $validarDuplicado = false;
        }

        if ($validarDuplicado) {
            $existe = ObligacionClienteContador::where('cliente_id', $this->clienteId)
                ->where('obligacion_id', $this->obligacion_id)
                ->where('is_activa', true)
                ->when($this->modoEdicion, fn($q) =>
                    $q->where('id', '!=', $this->asignacionIdEditando)
                )
                ->exists();

            if ($existe) {
                $this->addError('obligacion_id','Ya está asignada.');
                return;
            }
        }

        if ($this->modoEdicion) {

            $asignacion = ObligacionClienteContador::findOrFail($this->asignacionIdEditando);

            $fechaVenc = (!in_array($periodicidad,['unica','única','eventual'],true) && empty($this->fecha_vencimiento))
                ? $obligacionBase->calcularFechaVencimiento(now()->year, now()->month)?->toDateString()
                : ($this->fecha_vencimiento ? Carbon::parse($this->fecha_vencimiento)->toDateString() : null);

            $asignacion->update([
                'contador_id'       => $this->contador_id,
                'fecha_vencimiento' => $fechaVenc,
                'carpeta_drive_id'  => $this->carpeta_drive_id,
            ]);

            session()->flash('success','Asignación actualizada.');

        } else {

            $fechaVenc = (!in_array($periodicidad,['unica','única','eventual'],true))
                ? ($this->fecha_vencimiento
                    ? Carbon::parse($this->fecha_vencimiento)->toDateString()
                    : $obligacionBase->calcularFechaVencimiento(now()->year, now()->month)?->toDateString()
                  )
                : ($this->fecha_vencimiento ? Carbon::parse($this->fecha_vencimiento)->toDateString() : null);

            ObligacionClienteContador::create([
                'cliente_id'        => $this->clienteId,
                'obligacion_id'     => $this->obligacion_id,
                'contador_id'       => $this->contador_id,
                'fecha_asignacion'  => now(),
                'fecha_vencimiento' => $fechaVenc,
                'carpeta_drive_id'  => $this->carpeta_drive_id,
                'estatus'           => 'asignada',
                'is_activa'         => true,
            ]);

            session()->flash('success','Obligación asignada.');
        }

        $this->resetFormulario();
        $this->resetPage();
        $this->cargarObligacionesDisponibles();
        $this->verificarAsignacionesCompletas();

        $this->dispatch('obligacionesCambiadas');
    }

    /* =======================================================
     | UTILIDADES
     ======================================================= */
    private function resetFormulario(): void
    {
        $this->obligacion_id = '';
        $this->contador_id = '';
        $this->fecha_vencimiento = null;
        $this->carpeta_drive_id = null;

        $this->modoEdicion = false;
        $this->asignacionIdEditando = null;
        $this->obligacionSeleccionada = null;
        $this->obligacionOriginalId = null;

        $this->formKey++;
        $this->resetErrorBag();
        $this->resetValidation();
        $this->modalVisible = false;
    }

    private function verificarAsignacionesCompletas()
    {
        $total = Cliente::find($this->clienteId)->obligaciones()->count();
        $asignadas = ObligacionClienteContador::where('cliente_id',$this->clienteId)->count();

        $this->asignacionCompleta = ($total>0 && $asignadas >= $total);
        $this->obligacionesCompletadas = $this->asignacionCompleta;

        $this->dispatch('estado-obligaciones', completed: $this->obligacionesCompletadas);
    }

    /* =======================================================
     | RENDER
     ======================================================= */
    public function render()
    {
        return view('livewire.control.obligaciones-asignadas',[
            'asignaciones' => $this->queryAsignaciones()->paginate(10)
        ]);
    }
}
