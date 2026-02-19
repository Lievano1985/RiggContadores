<?php

namespace App\Livewire\Control;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\TareaAsignada;
use App\Models\TareaCatalogo;
use App\Models\User;
use App\Models\CarpetaDrive;
use App\Models\ObligacionClienteContador;
use App\Services\ArbolCarpetas;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class TareasAsignadasCrud extends Component
{
    use WithPagination;

    // === Variables públicas ===
    public $cliente;
    public $modalFormVisible = false;
    public $tareasCompletadas = false;
    public $modoEdicion = false;
    public $filtroEjercicio;
    public $filtroMes;
    public $tareaId;
    public $tarea_catalogo_id;
    public $contador_id;
    public $obligacion_cliente_contador_id;

    public $fecha_asignacion;
    public $fecha_limite;
    public $tiempo_estimado;
    public $carpeta_drive_id = null;
    public $obligacion_id = null; // 👈 este puede ser "sin" o un ID de pivot
    public $tarea_id = null;

    public $tareasDisponibles = [];
    public $carpetasSelect = [];
    public $arbolCarpetas = [];
    public $fechaLimiteMaxima = null;
    public $aniosDisponibles = [];
    public $buscarTarea = '';
    public bool $modoAutomatico = true;

    // === Reglas de validación base ===
    protected $rules = [
        'tarea_catalogo_id' => 'required|exists:tareas_catalogo,id',
        'contador_id' => 'required|exists:users,id',
        /*         'fecha_limite' => 'required|date|after_or_equal:fecha_asignacion',
 */
        'tiempo_estimado' => 'required|integer|min:1|max:1440',
        'carpeta_drive_id' => 'nullable|exists:carpeta_drives,id',
    ];

    protected $listeners = [
        'obligacionEliminada' => 'cargarTareasAsignadas',
        'obligacionesCambiadas' => 'actualizarDesdeObligaciones',
        'obligacionActualizada' => 'cargarTareasAsignadas'
    ];

    public function mount($cliente)
    {
        /*         Carbon::setTestNow(Carbon::create(2026, 1, 1));
      */
       
      if (!auth()->user()->hasAnyRole(['admin_despacho','supervisor'])) {
        abort(403);
    }
      $this->modoAutomatico = true;

        $this->cliente = $cliente;

        // ✅ AÑOS DISPONIBLES:
        // - YEAR(fecha_limite) cuando exista
        // - si fecha_limite es NULL, usar YEAR(created_at) como fallback
        $this->aniosDisponibles = TareaAsignada::where('cliente_id', $this->cliente->id)
            ->whereNotNull('ejercicio')
            ->distinct()
            ->orderBy('ejercicio', 'desc')
            ->pluck('ejercicio')
            ->toArray();


       /*  // ✅ Asegurar que el año actual exista en el combo
        $anioActual = now()->year;
        if (!in_array($anioActual, $this->aniosDisponibles)) {
            array_unshift($this->aniosDisponibles, $anioActual);
        } */

        // ✅ inicializa filtros (mismo patrón)
        $this->filtroEjercicio = null;
        $this->filtroMes = null;

        // Carga inicial
        $this->cargarTareasDisponibles();
        $this->verificarTareasCompletadas();
        $this->arbolCarpetas = ArbolCarpetas::obtenerArbol($cliente->id);
    }

    private function cargarTareasAsignadasFiltradas()
    {
        $query = TareaAsignada::with([
            'tareaCatalogo',
            'contador',
            'obligacionClienteContador.obligacion'
        ])
            ->where('cliente_id', $this->cliente->id)

            // 🔴 FILTRO CLAVE
            ->where(function ($q) {
                $q->whereNull('obligacion_cliente_contador_id') // tareas sin obligación
                    ->orWhereHas('obligacionClienteContador.obligacion', function ($o) {
                        $o->where('is_activa', 1); // solo obligaciones activas
                    });
            });

        // 🔍 Búsqueda
        if (!empty($this->buscarTarea)) {

            $texto = trim($this->buscarTarea);
        
            $query->where(function ($q) use ($texto) {
        
                // Buscar por nombre de tarea
                $q->whereHas('tareaCatalogo', function ($sub) use ($texto) {
                    $sub->where('nombre', 'like', "%{$texto}%");
                })
        
                // O buscar por nombre de obligación
                ->orWhereHas('obligacionClientecontador.obligacion', function ($sub) use ($texto) {
                    $sub->where('nombre', 'like', "%{$texto}%");
                });
        
            });
        
        }
        

        /* ===========================
         | AUTOMÁTICO
         =========================== */
        if ($this->modoAutomatico) {

            $inicioMes = now()->startOfMonth();
            $finMes    = now()->endOfMonth();

            $query->where(function ($q) use ($inicioMes, $finMes) {

                $q->whereBetween('fecha_limite', [$inicioMes, $finMes])

                    ->orWhere(function ($q2) use ($inicioMes) {
                        $q2->whereNotNull('fecha_limite')
                            ->whereDate('fecha_limite', '<', $inicioMes)
                            ->whereNotIn('estatus', ['terminada', 'cancelada', 'revisada']);
                    });
            });
        }

        /* ===========================
         | MANUAL
         =========================== */ else {
            $query->where('ejercicio', $this->filtroEjercicio)
                ->where('mes', $this->filtroMes);
        }

        return $query
            ->orderBy('fecha_limite', 'asc')
            ->paginate(10);
    }





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

    public function updatedBuscarTarea()
    {
        $this->resetPage();
    }
    // === Render del componente ===
    public function render()
    {

        if(!$this->tiempo_estimado){
            $this->tiempo_estimado = 5;
        }
        return view('livewire.control.tareas-asignadas', [
            'tareasAsignadas' => $this->cargarTareasAsignadasFiltradas(),

            'contadores' => User::role(['contador','supervisor'])->get(),
            'obligacionesAsignadas' => (function () {
                $base = ObligacionClienteContador::with(['obligacion'])
                    ->where('cliente_id', $this->cliente->id)
                    ->get()
                    ->filter(function ($pivot) {
                        $tareasAsignadas = TareaAsignada::where('cliente_id', $this->cliente->id)
                            ->where('obligacion_cliente_contador_id', $pivot->id)
                            ->pluck('tarea_catalogo_id')
                            ->toArray();

                        $tareasCatalogo = TareaCatalogo::where('obligacion_id', $pivot->obligacion_id)
                            ->pluck('id')
                            ->toArray();

                        $tareasDisponibles = array_diff($tareasCatalogo, $tareasAsignadas);
                        return count($tareasDisponibles) > 0;
                    })
                    ->values();

                if ($this->obligacion_id && $this->obligacion_id !== "sin" && !$base->contains('id', $this->obligacion_id)) {
                    $extra = ObligacionClienteContador::with('obligacion')->find($this->obligacion_id);
                    if ($extra) {
                        $base->push($extra);
                    }
                }

                return $base;
            })(),
            'carpetasDrive' => CarpetaDrive::where('cliente_id', $this->cliente->id)->get(),
        ]);
    }

    // === Cargar tareas disponibles según obligación seleccionada ===
    private function cargarTareasDisponibles()
    {
        $tareaCatalogoActualId = null;
        if ($this->tareaId) {
            $tareaCatalogoActualId = optional(TareaAsignada::find($this->tareaId))->tarea_catalogo_id;
        }

        $asignadas = TareaAsignada::query()
            ->where('cliente_id', $this->cliente->id)
            ->pluck('tarea_catalogo_id')
            ->toArray();

        $excluir = array_unique($asignadas);

        if ($tareaCatalogoActualId) {
            $excluir = array_diff($excluir, [$tareaCatalogoActualId]);
        }

        $catalogo = collect();

        if ($this->obligacion_id === "sin") {
            $catalogo = TareaCatalogo::query()
                ->whereNull('obligacion_id')
                ->when(!empty($excluir), fn($q) => $q->whereNotIn('id', $excluir))
                ->orderBy('nombre')
                ->get();
        } elseif ($this->obligacion_id) {
            $pivot = ObligacionClienteContador::find($this->obligacion_id);
            if ($pivot) {
                $catalogo = TareaCatalogo::query()
                    ->where('obligacion_id', $pivot->obligacion_id)
                    ->when(!empty($excluir), fn($q) => $q->whereNotIn('id', $excluir))
                    ->orderBy('nombre')
                    ->get();
            }
        }

        $this->tareasDisponibles = $catalogo;
    }

    // === Eventos y acciones del formulario ===
    public function crear()
    {
        $this->resetForm();
        $this->modoEdicion = false;
        $this->modalFormVisible = true;
    }

    public function editar($id)
    {
        $tarea = TareaAsignada::with(['tareaCatalogo', 'obligacionClienteContador.obligacion'])->findOrFail($id);

        $this->tareaId = $id;
        $this->tarea_catalogo_id = $tarea->tarea_catalogo_id;
        $this->contador_id = $tarea->contador_id;
        $this->obligacion_id = $tarea->obligacion_cliente_contador_id ?: "sin";
        $this->fecha_asignacion = $tarea->fecha_asignacion;
        $this->fecha_limite = $tarea->fecha_limite;
        $this->tiempo_estimado = $tarea->tiempo_estimado;
        $this->carpeta_drive_id = $tarea->carpeta_drive_id;

        $this->arbolCarpetas = ArbolCarpetas::obtenerArbol($this->cliente->id);

        $pivot = $tarea->obligacionClienteContador;
        $this->fechaLimiteMaxima = optional($pivot)->fecha_vencimiento
            ? Carbon::parse($pivot->fecha_vencimiento)->format('Y-m-d')
            : null;

        $this->modoEdicion = true;
        $this->cargarTareasDisponibles();
        $this->modalFormVisible = true;
    }

    public function guardar()
    {
        $this->validate();

        $fechaAsignacion = $this->contador_id ? now() : null;

        TareaAsignada::updateOrCreate(
            ['id' => $this->tareaId],
            [
                'tarea_catalogo_id' => $this->tarea_catalogo_id,
                'cliente_id' => $this->cliente->id,
                'contador_id' => $this->contador_id,
                'obligacion_cliente_contador_id' => $this->obligacion_id !== "sin" ? $this->obligacion_id : null,
                'fecha_asignacion' => $fechaAsignacion,
                'fecha_limite' => $this->fecha_limite,
                'tiempo_estimado' => $this->tiempo_estimado,
                'carpeta_drive_id' => $this->carpeta_drive_id,
            ]
        );

        $this->modalFormVisible = false;
        $this->resetForm();
        $this->cargarTareasDisponibles();
        $this->verificarTareasCompletadas();

        session()->flash('success', 'Tarea asignada correctamente.');
    }

    public function eliminar($id)
    {
        TareaAsignada::findOrFail($id)->delete();
        $this->cargarTareasDisponibles();
        session()->flash('success', 'Tarea eliminada.');
        $this->verificarTareasCompletadas();
    }

    public function cerrarModal()
    {
        $this->modalFormVisible = false;
        $this->resetForm();
        $this->cargarTareasDisponibles();
        $this->resetErrorBag();
    }

    public function resetForm()
    {
        $this->reset([
            'tarea_catalogo_id',
            'contador_id',
            'obligacion_id',
            'fecha_asignacion',
            'fecha_limite',
            'tiempo_estimado',
            'carpeta_drive_id',
            'tareaId',
        ]);
    }

    public function updatedObligacionId()
    {
        $this->tarea_catalogo_id = null;
        $this->cargarTareasDisponibles();

        if ($this->obligacion_id && $this->obligacion_id !== "sin") {
            $pivot = ObligacionClienteContador::find($this->obligacion_id);
            $this->fechaLimiteMaxima = optional($pivot)->fecha_vencimiento
                ? Carbon::parse($pivot->fecha_vencimiento)->format('Y-m-d')
                : null;
        } else {
            $this->fechaLimiteMaxima = null;
        }
    }

    public function cargarTareasAsignadas()
    {
        $this->resetPage();
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if ($this->fechaLimiteMaxima) {
                $fechaMax = Carbon::parse($this->fechaLimiteMaxima);

                if ($this->fecha_asignacion && Carbon::parse($this->fecha_asignacion)->gt($fechaMax)) {
                    $validator->errors()->add('fecha_asignacion', 'La fecha de asignación no puede ser posterior a la fecha de vencimiento de la obligación (' . $fechaMax->format('d/m/Y') . ').');
                }

                if ($this->fecha_limite && Carbon::parse($this->fecha_limite)->gt($fechaMax)) {
                    $validator->errors()->add('fecha_limite', 'La fecha límite no puede ser posterior a la fecha de vencimiento de la obligación (' . $fechaMax->format('d/m/Y') . ').');
                }
            }
        });
    }

    public function verificarTareasCompletadas()
    {
        $tareasAsignadas = TareaAsignada::where('cliente_id', $this->cliente->id)->get();
        $obligacionesAsignadas = ObligacionClienteContador::where('cliente_id', $this->cliente->id)->pluck('obligacion_id')->toArray();

        $tareasCatalogoPorObligacion = TareaCatalogo::where('activo', true)
            ->whereIn('obligacion_id', $obligacionesAsignadas)
            ->get()
            ->groupBy('obligacion_id');

        $porObligacionPendientes = collect();

        foreach ($tareasCatalogoPorObligacion as $obligacionId => $tareasCatalogo) {
            foreach ($tareasCatalogo as $tarea) {
                $yaAsignada = $tareasAsignadas->contains(function ($asignada) use ($tarea, $obligacionId) {
                    return $asignada->tarea_catalogo_id == $tarea->id &&
                        optional($asignada->obligacionClienteContador)->obligacion_id == $obligacionId;
                });

                if (!$yaAsignada) {
                    $porObligacionPendientes->push($tarea->id);
                }
            }
        }

        $this->tareasCompletadas = $porObligacionPendientes->isEmpty();
        $this->dispatch('estado-tareas', completed: $this->tareasCompletadas);
    }

    public function actualizarDesdeObligaciones()
    {
        $this->verificarTareasCompletadas();
    }
}
