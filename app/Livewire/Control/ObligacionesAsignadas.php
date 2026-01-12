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
    // Para saber si estamos en modo edición
    public $modoEdicion = false;
    public $asignacionIdEditando = null;
    public $obligacionSeleccionada = null;
    //paginacion
    protected $paginationTheme = 'tailwind';
    public $perPage = 10;
    // Listas
    public $obligacionesDisponibles = [];
    public $contadores             = [];
    public array $arbolCarpetas    = [];
    public $obligacionesCompletadas;
    public int $formKey = 0;
    public bool $modalVisible = false;
    public $obligacionOriginalId; // Guarda la obligación original al editar

    protected $listeners = [
        'obligacionActualizada' => 'actualizarAsignaciones'
    ];

    use WithPagination;

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

            //$this->cargarAsignaciones();
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

            //$this->cargarAsignaciones();
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
        /*           Carbon::setTestNow(Carbon::create(2026, 1, 1));
 */

        $this->cliente    = $cliente;
        $this->clienteId  = $cliente->id;

        $this->aniosDisponibles = ObligacionClienteContador::where('cliente_id', $this->clienteId)
            ->selectRaw('DISTINCT COALESCE(YEAR(fecha_vencimiento), ejercicio, YEAR(created_at)) as year')
            ->whereNotNull(DB::raw('COALESCE(YEAR(fecha_vencimiento), ejercicio, YEAR(created_at))'))
            ->orderBy('year', 'desc')
            ->pluck('year')
            ->toArray();

        // Asegurar que el año actual exista en el combo
        $anioActual = now()->year;
        if (!in_array($anioActual, $this->aniosDisponibles)) {
            array_unshift($this->aniosDisponibles, $anioActual);
        }
        $this->filtroEjercicio = now()->year;
        $this->filtroMes = now()->month;

        $this->cargarObligacionesDisponibles();
        $this->contadores        = User::role('contador')->orderBy('name')->get();
        $this->verificarAsignacionesCompletas();
        //$this->cargarAsignaciones();
        $this->cargarArbolCarpetas();
    }

    public function actualizarAsignaciones()
    {
        $this->cargarObligacionesDisponibles();
        //$this->cargarAsignaciones();
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
        $anioActual = now()->year;
        $mesActual  = now()->month;
    
        $query = ObligacionClienteContador::with(['obligacion', 'contador', 'carpeta'])
            ->where('cliente_id', $this->clienteId)
            ->where('is_activa', true);
    
        // === FILTRO AUTOMÁTICO ===
        if (
            empty($this->filtroEjercicio) || empty($this->filtroMes) ||
            ((int)$this->filtroEjercicio === $anioActual && (int)$this->filtroMes === $mesActual)
        ) {
            $inicioMes = now()->startOfMonth()->toDateString();
            $finMes    = now()->endOfMonth()->toDateString();
    
            $query->where(function ($q) use ($inicioMes, $finMes) {
    
                $q->whereBetween('fecha_vencimiento', [$inicioMes, $finMes])
    
                  ->orWhere(function ($q2) use ($finMes) {
                      $q2->whereNotNull('fecha_vencimiento')
                         ->whereDate('fecha_vencimiento', '<=', $finMes)
                         ->where('estatus', '!=', 'finalizado');
                  })
    
                  ->orWhereNull('fecha_vencimiento');
            });
        }
        // === FILTRO MANUAL ===
        else {
            $query->where(function ($q) {
                $q->where('ejercicio', $this->filtroEjercicio)
                  ->where('mes', $this->filtroMes)
                  ->orWhereNull('fecha_vencimiento');
            });
        }
    
        return $query
            ->orderByRaw('fecha_vencimiento IS NULL DESC')
            ->orderBy('fecha_vencimiento', 'asc')
            ->paginate($this->perPage);
    }
    




    private function cargarAniosDisponibles(): void
    {
        $this->aniosDisponibles = ObligacionClienteContador::where('cliente_id', $this->clienteId)
            ->select('ejercicio')
            ->distinct()
            ->orderBy('ejercicio', 'desc')
            ->pluck('ejercicio')
            ->filter()
            ->values()
            ->toArray();

        // Si el filtro actual no existe en la lista, pon el más reciente (o el año actual)
        if (!empty($this->aniosDisponibles) && !in_array((int)$this->filtroEjercicio, $this->aniosDisponibles, true)) {
            $this->filtroEjercicio = $this->aniosDisponibles[0];
        }
    }


    public function updatedFiltroEjercicio()
    {
        $this->resetPage();

        //$this->cargarAsignaciones();
    }

    public function updatedFiltroMes()
    {
        $this->resetPage();

        //$this->cargarAsignaciones();
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

        $this->obligacion_id          = $asignacion->obligacion_id;
        $this->obligacionOriginalId   = $asignacion->obligacion_id; // ✅ CLAVE: guarda la original
        $this->obligacionSeleccionada = $asignacion->obligacion;

        $this->contador_id        = $asignacion->contador_id;
        $this->carpeta_drive_id   = $asignacion->carpeta_drive_id;
        $this->fecha_vencimiento  = $asignacion->fecha_vencimiento;

        $this->modalVisible = true;
        $this->cargarArbolCarpetas();
    }



    // === Guardar (crear o editar) ===
    public function guardar()
    {
        /* ============================================================
         | 1️⃣ VALIDACIÓN BÁSICA DE CAMPOS
         |============================================================ */
        $this->validate([
            'obligacion_id'     => 'required|exists:obligaciones,id',
            'contador_id'       => 'required|exists:users,id',
            'fecha_vencimiento' => 'nullable|date',
            'carpeta_drive_id'  => 'nullable|exists:carpeta_drives,id',
        ]);

        /* ============================================================
         | 2️⃣ OBTENER OBLIGACIÓN BASE (catálogo)
         |============================================================ */
        $obligacionBase = \App\Models\Obligacion::findOrFail($this->obligacion_id);

        // Normalizamos la periodicidad para evitar errores por acentos
        $periodicidad = strtolower($obligacionBase->periodicidad ?? 'mensual');

        /* ============================================================
         | 3️⃣ VALIDACIÓN DE DUPLICADOS (SOLO CUANDO APLICA)
         |
         | REGLA:
         | - Al CREAR → siempre validar
         | - Al EDITAR → solo validar si cambió la obligación
         |============================================================ */
        $validarDuplicado = true;

        // Si estoy editando y la obligación NO cambió, no valido duplicados
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
                ->where('is_activa', true) // solo obligaciones activas
                ->when($this->modoEdicion, function ($query) {
                    // excluir el registro que estoy editando
                    $query->where('id', '!=', $this->asignacionIdEditando);
                })
                ->exists();

            if ($existe) {
                $this->addError(
                    'obligacion_id',
                    'Esta obligación ya fue asignada y sigue activa.'
                );
                return;
            }
        }

        /* ============================================================
         | 4️⃣ EDICIÓN DE ASIGNACIÓN EXISTENTE
         |============================================================ */
        if ($this->modoEdicion && $this->asignacionIdEditando) {

            $asignacion = ObligacionClienteContador::findOrFail($this->asignacionIdEditando);

            // Si no es obligación única y no se manda fecha, se recalcula
            if (!in_array($periodicidad, ['unica', 'única', 'eventual'], true) && empty($this->fecha_vencimiento)) {
                $fechaVenc = $obligacionBase
                    ->calcularFechaVencimiento(now()->year, now()->month)
                    ?->toDateString();
            } else {
                $fechaVenc = $this->fecha_vencimiento
                    ? \Carbon\Carbon::parse($this->fecha_vencimiento)->toDateString()
                    : null;
            }

            // Actualizamos SOLO los campos editables
            $asignacion->update([
                'contador_id'       => $this->contador_id,
                'fecha_vencimiento' => $fechaVenc,
                'carpeta_drive_id'  => $this->carpeta_drive_id,
            ]);

            session()->flash('success', 'Asignación actualizada correctamente.');
        }

        /* ============================================================
         | 5️⃣ CREACIÓN DE NUEVA ASIGNACIÓN
         |============================================================ */ else {

            if (!in_array($periodicidad, ['unica', 'única', 'eventual'], true)) {
                $fechaVenc = $this->fecha_vencimiento
                    ? \Carbon\Carbon::parse($this->fecha_vencimiento)->toDateString()
                    : $obligacionBase
                    ->calcularFechaVencimiento(now()->year, now()->month)
                    ?->toDateString();
            } else {
                $fechaVenc = $this->fecha_vencimiento
                    ? \Carbon\Carbon::parse($this->fecha_vencimiento)->toDateString()
                    : null;
            }

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

            session()->flash('success', 'Obligación asignada correctamente.');
        }

        /* ============================================================
         | 6️⃣ LIMPIEZA Y REFRESCO DE ESTADO
         |============================================================ */
        $this->resetFormulario();
        //$this->cargarAsignaciones();
        $this->cargarObligacionesDisponibles();
        $this->verificarAsignacionesCompletas();

        // Notifica a otros componentes (tabs, contadores, etc.)
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
        $this->obligacionSeleccionada = null;
        $this->obligacionOriginalId = null; // ✅ limpiar

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
        return view('livewire.control.obligaciones-asignadas', [
            'asignaciones' => $this->cargarAsignaciones()
        ]);
    }
}
