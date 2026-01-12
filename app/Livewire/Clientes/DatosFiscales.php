<?php

/**
 * Componente Livewire: DatosFiscales
 * Autor: Luis Liévano - JL3 Digital
 *
 * Función:
 * - Configura regímenes, actividades y obligaciones.
 * - Usa obligacion_cliente_contador como fuente real.
 * - Soporta alta, baja lógica, reactivación y eliminación definitiva.
 */

namespace App\Livewire\Clientes;

use Livewire\Component;
use Illuminate\Support\Facades\DB;

use App\Models\{
    Cliente,
    Regimen,
    ActividadEconomica,
    Obligacion,
    ObligacionClienteContador,
    TareaCatalogo,
    TareaAsignada
};

class DatosFiscales extends Component
{
    /* ============================================================
     | PROPIEDADES
     |============================================================ */
    public Cliente $cliente;

    public $obligacionesEstado = [];

    // Filtros
    public $buscarRegimen = '';
    public $buscarActividad = '';
    public $buscarObligacionPeriodica = '';
    public $buscarObligacionUnica = '';

    // Catálogos
    public $regimenesDisponibles = [];
    public $actividadesDisponibles = [];
    public $obligacionesPeriodicasDisponibles = [];
    public $obligacionesUnicasDisponibles = [];

    // Selecciones
    public $regimenesSeleccionados = [];
    public $actividadesSeleccionadas = [];
    public $obligacionesSeleccionadas = [];
    public $obligacionesUnicasSeleccionadas = [];

    // UI
    public bool $modoEdicion = false;
    public int $modoKey = 0;

    protected $listeners = [
        'DatosFiscalesActualizados' => 'cargarDatos'
    ];

    /* ============================================================
     | CICLO DE VIDA
     |============================================================ */

    public function mount(Cliente $cliente)
    {
        $this->cliente = $cliente;
        $this->cargarDatos();
    }

    public function cargarDatos()
    {
        $this->initializeLists();
    }

    /* ============================================================
     | CARGA INICIAL
     |============================================================ */

    protected function initializeLists(): void
    {
        /* ACTIVIDADES */
        $this->actividadesDisponibles = ActividadEconomica::orderBy('nombre')->get();

        $this->actividadesSeleccionadas = $this->cliente
            ->actividadesEconomicas()
            ->pluck('actividad_economica_id')
            ->toArray();

        /* REGÍMENES */
        $this->loadRegimenesDisponibles();

        $this->regimenesSeleccionados = $this->cliente
            ->regimenes()
            ->pluck('regimenes.id')
            ->toArray();

        /* OBLIGACIONES */
        $this->loadObligacionesDisponibles();

        $asignaciones = ObligacionClienteContador::where('cliente_id', $this->cliente->id)
            ->select('obligacion_id', 'is_activa')
            ->get();

        $this->obligacionesSeleccionadas = [];

        foreach ($asignaciones as $a) {
            if ($a->is_activa) {
                $this->obligacionesSeleccionadas[$a->obligacion_id] = true;
            }
        }

        $this->obligacionesEstado = $asignaciones
            ->pluck('is_activa', 'obligacion_id')
            ->toArray();

        $this->obligacionesUnicasSeleccionadas = [];
    }

    /* ============================================================
     | CATÁLOGOS
     |============================================================ */

    protected function loadRegimenesDisponibles(): void
    {
        $this->regimenesDisponibles = Regimen::where(function ($q) {
            $q->where('tipo_persona', $this->cliente->tipo_persona)
              ->orWhere('tipo_persona', 'física/moral');
        })
        ->orderBy('nombre')
        ->get();
    }

    protected function loadObligacionesDisponibles(): void
    {
        $this->obligacionesPeriodicasDisponibles = Obligacion::where('periodicidad', '!=', 'unica')
            ->when(!$this->cliente->tiene_trabajadores,
                fn($q) => $q->where('tipo', '!=', 'patronal')
            )
            ->orderBy('nombre')
            ->get();

        $this->obligacionesUnicasDisponibles = Obligacion::where('periodicidad', 'unica')
            ->orderBy('nombre')
            ->get();
    }

    /* ============================================================
     | GUARDAR
     |============================================================ */

    public function guardar(): void
    {
        // Regímenes y actividades (aquí sí sync)
        $this->cliente->regimenes()->sync($this->regimenesSeleccionados);
        $this->cliente->actividadesEconomicas()->sync($this->actividadesSeleccionadas);

        /* ================= OBLIGACIONES ================= */

        $seleccionadas = collect($this->obligacionesSeleccionadas)
            ->filter()
            ->keys()
            ->map(fn($v) => (int)$v)
            ->toArray();

        $actuales = ObligacionClienteContador::where('cliente_id', $this->cliente->id)
            ->pluck('obligacion_id')
            ->unique()
            ->toArray();

        $nuevas   = array_diff($seleccionadas, $actuales);
        $quitadas = array_diff($actuales, $seleccionadas);

        if (!empty($nuevas)) {
            $this->crearAsignacionesYtareasIniciales($nuevas);
        }

        if (!empty($quitadas)) {
            foreach ($quitadas as $id) {
                $this->darDeBajaObligacion($id);
            }
        }

        if (!empty($this->obligacionesUnicasSeleccionadas)) {
            $this->crearUnicasYtareas($this->obligacionesUnicasSeleccionadas);
            $this->obligacionesUnicasSeleccionadas = [];
        }

        session()->flash('message', 'Datos fiscales actualizados correctamente.');

        $this->modoEdicion = false;
        $this->modoKey++;

        $this->dispatch('obligacionActualizada');
    }

    /* ============================================================
     | CREACIÓN
     |============================================================ */

    protected function crearAsignacionesYtareasIniciales(array $ids): void
    {
        $anio = now()->year;
        $mes  = now()->month;

        foreach ($ids as $id) {

            $obligacion = Obligacion::find($id);
            if (!$obligacion) continue;

            $fechaVenc = $obligacion->calcularFechaVencimiento($anio, $mes);

            $asignacion = ObligacionClienteContador::updateOrCreate(
                [
                    'cliente_id'    => $this->cliente->id,
                    'obligacion_id' => $id,
                    'ejercicio'     => $anio,
                    'mes'           => $mes,
                ],
                [
                    'estatus'           => 'asignada',
                    'fecha_asignacion'  => now(),
                    'fecha_vencimiento' => $fechaVenc?->toDateString(),
                    'is_activa'         => true,
                    'fecha_baja'        => null,
                    'motivo_baja'       => null,
                ]
            );

            $tareas = TareaCatalogo::where('obligacion_id', $id)
                ->where('activo', true)
                ->get();

            foreach ($tareas as $t) {
                TareaAsignada::updateOrCreate(
                    [
                        'cliente_id' => $this->cliente->id,
                        'tarea_catalogo_id' => $t->id,
                        'obligacion_cliente_contador_id' => $asignacion->id,
                        'ejercicio' => $anio,
                        'mes' => $mes,
                    ],
                    [
                        'fecha_asignacion' => now(),
                        'fecha_limite'     => $fechaVenc?->toDateString(),
                        'estatus'          => 'asignada',
                    ]
                );
            }
        }
    }

    protected function crearUnicasYtareas(array $ids): void
    {
        $anio = now()->year;
        $mes  = now()->month;

        foreach ($ids as $id) {

            $asignacion = ObligacionClienteContador::updateOrCreate(
                [
                    'cliente_id'    => $this->cliente->id,
                    'obligacion_id' => $id,
                    'ejercicio'     => $anio,
                    'mes'           => $mes,
                ],
                [
                    'estatus' => 'asignada',
                    'fecha_asignacion' => now(),
                    'fecha_vencimiento' => null,
                    'is_activa' => true,
                ]
            );

            $tareas = TareaCatalogo::where('obligacion_id', $id)
                ->where('activo', true)
                ->get();

            foreach ($tareas as $t) {
                TareaAsignada::updateOrCreate(
                    [
                        'cliente_id' => $this->cliente->id,
                        'tarea_catalogo_id' => $t->id,
                        'obligacion_cliente_contador_id' => $asignacion->id,
                        'ejercicio' => $anio,
                        'mes' => $mes,
                    ],
                    [
                        'fecha_asignacion' => now(),
                        'fecha_limite' => null,
                        'estatus' => 'asignada',
                    ]
                );
            }
        }
    }

    /* ============================================================
     | BAJA / REACTIVAR / ELIMINAR
     |============================================================ */

    public function darDeBajaObligacion($id): void
    {
        ObligacionClienteContador::where('cliente_id', $this->cliente->id)
            ->where('obligacion_id', $id)
            ->update([
                'is_activa' => false,
                'fecha_baja' => now(),
                'motivo_baja' => 'Baja desde datos fiscales.',
            ]);

        $this->modoEdicion = true;
        $this->dispatch('mantenerModoEdicion');
    }

    public function reactivarObligacion($id): void
    {
        DB::transaction(function () use ($id) {

            $asignaciones = ObligacionClienteContador::where('cliente_id', $this->cliente->id)
                ->where('obligacion_id', $id)
                ->where('is_activa', false)
                ->get();

            foreach ($asignaciones as $a) {

                $a->update([
                    'is_activa' => true,
                    'fecha_baja' => null,
                    'motivo_baja' => null,
                ]);

                $a->tareasAsignadas()
                    ->where('estatus', 'cancelada')
                    ->update(['estatus' => 'asignada']);
            }
        });

        $this->dispatch('mantenerModoEdicion');
        $this->dispatch('obligacionActualizada');
    }

    public function eliminarAsignacionTotal($id): void
    {
        $asignaciones = ObligacionClienteContador::where('cliente_id', $this->cliente->id)
            ->where('obligacion_id', $id)
            ->get();

        foreach ($asignaciones as $a) {
            TareaAsignada::where('obligacion_cliente_contador_id', $a->id)->delete();
            $a->delete();
        }

        $this->modoEdicion = true;
        $this->dispatch('mantenerModoEdicion');
        $this->dispatch('obligacionActualizada');
    }

    /* ============================================================
     | CONSULTAS
     |============================================================ */

    public function getObligacionesVigentes()
    {
        return ObligacionClienteContador::where('cliente_id', $this->cliente->id)
            ->where('is_activa', true)
            ->with('obligacion')
            ->get();
    }

    /* ============================================================
     | RENDER
     |============================================================ */

    public function render()
    {
        return view('livewire.clientes.datos-fiscales', [
            'regimenesFiltrados' => $this->regimenesDisponibles,
            'actividadesFiltradas' => $this->actividadesDisponibles,
            'obligacionesPeriodicasFiltradas' => $this->obligacionesPeriodicasDisponibles,
            'obligacionesUnicasFiltradas' => $this->obligacionesUnicasDisponibles,
        ]);
    }
}
