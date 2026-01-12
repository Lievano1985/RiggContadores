<?php

/**
 * Componente Livewire: DatosFiscales
 * Autor: Luis Liévano - JL3 Digital
 * Descripción técnica:
 * - Configura regímenes, actividades y obligaciones del cliente.
 * - Administra altas, bajas lógicas y reactivaciones de obligaciones.
 * - Evita recargas globales que alteraban el estado visual.
 */

namespace App\Livewire\Clientes;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\{
    Cliente,
    Regimen,
    ActividadEconomica,
    Obligacion,
    ObligacionClienteContador,
    TareaCatalogo,
    TareaAsignada
};
use Livewire\Component;

class DatosFiscales extends Component
{
    /* ============================================================
     | 🔹 PROPIEDADES PRINCIPALES
     |============================================================ */
    public Cliente $cliente;
    public $obligacionesEstado = [];

    // Filtros de búsqueda (inputs)
    public $buscarRegimen = '';
    public $buscarActividad = '';
    public $buscarObligacionPeriodica = '';
    public $buscarObligacionUnica = '';

    // Listas de opciones disponibles
    public $regimenesDisponibles = [];
    public $actividadesDisponibles = [];
    public $obligacionesPeriodicasDisponibles = [];
    public $obligacionesUnicasDisponibles = [];

    // Selecciones actuales
    public $regimenesSeleccionados = [];
    public $actividadesSeleccionadas = [];
    public $obligacionesSeleccionadas = [];
    public $obligacionesUnicasSeleccionadas = [];

    // Control de estado del formulario
    public bool $modoEdicion = false;
    public int $modoKey = 0;

    protected $listeners = [
        'DatosFiscalesActualizados' => 'CargarDatosFiscales',
    ];

    /* ============================================================
     | 🔹 CICLO DE VIDA Y CARGA INICIAL
     |============================================================ */

    public function mount(Cliente $cliente)
    {
        $this->cliente = $cliente;
        $this->initializeLists();
    }


    public function CargarDatosFiscales()
    {
        $this->initializeLists();
    }

    /**
     * Inicializa todas las listas disponibles y las selecciones actuales.
     * ✅ Solo carga obligaciones activas (ya no mezcla bajas ni pivote base).
     */
    /**
     * Inicializa todas las listas (actividades, regímenes y obligaciones)
     * mostrando tanto las obligaciones activas como las dadas de baja.
     */
    protected function initializeLists(): void
    {
        /* ============================================================
     | 🔹 ACTIVIDADES
     |============================================================ */
        $this->actividadesDisponibles = ActividadEconomica::orderBy('nombre')->get();
        $this->actividadesSeleccionadas = $this->cliente->actividadesEconomicas()
            ->pluck('actividad_economica_id')
            ->toArray();

        /* ============================================================
     | 🔹 REGÍMENES
     |============================================================ */
        $this->loadRegimenesDisponibles();
        $this->regimenesSeleccionados = $this->cliente->regimenes()
            ->pluck('regimenes.id')
            ->toArray();

        /* ============================================================
     | 🔹 OBLIGACIONES (periodicidad, tipo, estado)
     |============================================================ */
        $this->loadObligacionesDisponibles();

        // 🧩 Obtenemos todas las obligaciones del cliente (activas e inactivas)
        $obligacionesCliente = ObligacionClienteContador::where('cliente_id', $this->cliente->id)
            ->select('obligacion_id', 'is_activa')
            ->get();

        // 🟢 IDs de todas las obligaciones (para que se muestren todas)
        // 🟢 Inicializar como mapa booleano por ID (solo activas)
        $this->obligacionesSeleccionadas = [];

        foreach ($obligacionesCliente as $o) {
            if ($o->is_activa) {
                $this->obligacionesSeleccionadas[$o->obligacion_id] = true;
            }
        }


        // 🟡 Creamos un arreglo auxiliar con su estado (true=activa / false=baja)
        $this->obligacionesEstado = $obligacionesCliente
            ->pluck('is_activa', 'obligacion_id')
            ->toArray();

        // 🧾 Limpiar únicas seleccionadas
        $this->obligacionesUnicasSeleccionadas = [];
    }


    /* ============================================================
     | 🔹 CARGA DE CATÁLOGOS
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
            ->when(!$this->cliente->tiene_trabajadores, fn($q) => $q->where('tipo', '!=', 'patronal'))
            ->orderBy('nombre')
            ->get();

        $this->obligacionesUnicasDisponibles = Obligacion::where('periodicidad', 'unica')
            ->orderBy('nombre')
            ->get();
    }

    /* ============================================================
     | 🔹 GUARDAR CAMBIOS GENERALES
     |============================================================ */

    public function guardar(): void
    {
        // 🔸 Sincronizar regímenes y actividades
        $this->cliente->regimenes()->sync($this->regimenesSeleccionados);
        $this->cliente->actividadesEconomicas()->sync($this->actividadesSeleccionadas);

        // 🔸 Sincronizar obligaciones del cliente (solo pivot)
        // 🔥 Convertir mapa booleano a IDs reales
        $seleccionadas = collect($this->obligacionesSeleccionadas)
            ->filter()   // solo true
            ->keys()     // IDs reales
            ->map(fn($v) => (int)$v)
            ->toArray();

        // Sincronizar pivot
        $sincronizacion = $this->cliente->obligaciones()
            ->sync($seleccionadas);

        // Crear nuevas asignaciones si se agregaron
        if (!empty($sincronizacion['attached'])) {
            $this->crearAsignacionesYtareasIniciales($sincronizacion['attached']);
        }

        // Dar de baja si se quitaron
        if (!empty($sincronizacion['detached'])) {
            foreach ($sincronizacion['detached'] as $id) {
                $this->darDeBajaObligacion($id);
            }
        }

        // ⚠️ Eliminado: ya no se reactivan automáticamente todas las seleccionadas.
        // Este bloque causaba la reactivación global.

        // Crear obligaciones únicas si se seleccionaron
        if (!empty($this->obligacionesUnicasSeleccionadas)) {
            $this->crearUnicasYtareas($this->obligacionesUnicasSeleccionadas);
            $this->obligacionesUnicasSeleccionadas = [];
        }

        // Mensaje y refresco visual
        session()->flash('message', 'Datos fiscales actualizados correctamente.');
        $this->modoEdicion = false;
        $this->modoKey++;
        $this->dispatch('obligacionActualizada');
    }

    /* ============================================================
     | 🔹 CREACIÓN DE ASIGNACIONES Y TAREAS
     |============================================================ */

    protected function crearAsignacionesYtareasIniciales(array $idsObligaciones): void
    {
        $anioActual = now()->year;
        $mesActual = now()->month;

        foreach ($idsObligaciones as $obligacionId) {
            $obligacion = Obligacion::find($obligacionId);
            if (!$obligacion) continue;

            $fechaVenc = $obligacion->calcularFechaVencimiento($anioActual, $mesActual);

            // Crear o actualizar asignación
            $asignacion = ObligacionClienteContador::updateOrCreate(
                [
                    'cliente_id'    => $this->cliente->id,
                    'obligacion_id' => $obligacionId,
                    'ejercicio'     => $anioActual,
                    'mes'           => $mesActual,
                ],
                [
                    'estatus'          => 'asignada',
                    'fecha_asignacion' => now(),
                    'fecha_vencimiento' => $fechaVenc?->toDateString(),
                    'is_activa'        => true,
                    'fecha_baja'       => null,
                    'motivo_baja'      => null,
                ]
            );

            // Crear tareas relacionadas
            $tareas = TareaCatalogo::where('obligacion_id', $obligacionId)
                ->where('activo', true)
                ->get();

            foreach ($tareas as $t) {
                TareaAsignada::updateOrCreate(
                    [
                        'cliente_id'                    => $this->cliente->id,
                        'tarea_catalogo_id'             => $t->id,
                        'obligacion_cliente_contador_id' => $asignacion->id,
                        'ejercicio'                     => $anioActual,
                        'mes'                           => $mesActual,
                    ],
                    [
                        'fecha_asignacion' => now(),
                        'fecha_limite'     => $fechaVenc?->toDateString(),
                        'estatus'          => 'asignada',
                    ]
                );
            }
        }

        $this->dispatch('obligacionActualizada');
    }

    protected function crearUnicasYtareas(array $idsObligacionesUnicas): void
    {
        $anioActual = now()->year;
        $mesActual = now()->month;

        foreach ($idsObligacionesUnicas as $obligacionId) {
            $ob = Obligacion::find($obligacionId);
            if (!$ob) continue;

            $asignacion = ObligacionClienteContador::updateOrCreate(
                [
                    'cliente_id'    => $this->cliente->id,
                    'obligacion_id' => $obligacionId,
                    'ejercicio'     => $anioActual,
                    'mes'           => $mesActual,
                ],
                [
                    'estatus'          => 'asignada',
                    'fecha_asignacion' => now(),
                    'fecha_vencimiento' => null,
                    'is_activa'        => true,
                ]
            );

            $tareas = TareaCatalogo::where('obligacion_id', $obligacionId)
                ->where('activo', true)
                ->get();

            foreach ($tareas as $t) {
                TareaAsignada::updateOrCreate(
                    [
                        'cliente_id'                    => $this->cliente->id,
                        'tarea_catalogo_id'             => $t->id,
                        'obligacion_cliente_contador_id' => $asignacion->id,
                        'ejercicio'                     => $anioActual,
                        'mes'                           => $mesActual,
                    ],
                    [
                        'fecha_asignacion' => now(),
                        'fecha_limite'     => null,
                        'estatus'          => 'asignada',
                    ]
                );
            }
        }
    }

    /* ============================================================
     | 🔹 BAJA LÓGICA
     |============================================================ */

    public function darDeBajaObligacion($obligacionId): void
    {
        $asignaciones = ObligacionClienteContador::where('cliente_id', $this->cliente->id)
            ->where('obligacion_id', $obligacionId)
            ->get();

        foreach ($asignaciones as $a) {
            $a->update([
                'is_activa'   => false,
                'fecha_baja'  => now(),
                'motivo_baja' => 'Baja desde datos fiscales.',
            ]);

            // Cancelar tareas activas
            TareaAsignada::where('obligacion_cliente_contador_id', $a->id)
                ->update(['estatus' => 'cancelada']);
        }

        $this->modoEdicion = true;
        $this->dispatch('mantenerModoEdicion');
    }

    /* ============================================================
     | ♻️ REACTIVAR UNA OBLIGACIÓN ESPECÍFICA
     |============================================================ */

    public function reactivarObligacion($obligacionId): void
    {
        try {
            DB::beginTransaction();

            // Buscar todas las asignaciones inactivas de esa obligación
            $asignaciones = ObligacionClienteContador::where('cliente_id', $this->cliente->id)
                ->where('obligacion_id', $obligacionId)
                ->where('is_activa', false)
                ->get();

            if ($asignaciones->isEmpty()) {
                session()->flash('error', 'No hay asignaciones inactivas para esta obligación.');
                DB::rollBack();
                return;
            }

            foreach ($asignaciones as $a) {
                $a->update([
                    'is_activa'   => true,
                    'fecha_baja'  => null,
                    'motivo_baja' => null,
                ]);

                // Reactivar tareas canceladas
                $a->tareasAsignadas()
                    ->where('estatus', 'cancelada')
                    ->update(['estatus' => 'asignada']);
            }

            DB::commit();

            // Actualizar solo esta obligación en el estado Livewire
            if (!in_array($obligacionId, $this->obligacionesSeleccionadas)) {
                $this->obligacionesSeleccionadas[] = $obligacionId;
            }

            session()->flash('success', 'Obligación reactivada correctamente.');
            $this->dispatch('mantenerModoEdicion');
            $this->dispatch('obligacionActualizada');
        } catch (\Throwable $e) {
            DB::rollBack();
            session()->flash('error', 'Error al reactivar la obligación: ' . $e->getMessage());
        }
    }

    /* ============================================================
     | 🗑️ ELIMINACIÓN DEFINITIVA (solo admin)
     |============================================================ */

    public function eliminarAsignacionTotal($obligacionId): void
    {
        $asignaciones = ObligacionClienteContador::where('cliente_id', $this->cliente->id)
            ->where('obligacion_id', $obligacionId)
            ->get();

        foreach ($asignaciones as $a) {
            TareaAsignada::where('obligacion_cliente_contador_id', $a->id)->delete();
            $a->delete();
        }

        $this->cliente->obligaciones()->detach($obligacionId);

        $this->modoEdicion = true;
        $this->dispatch('mantenerModoEdicion');
        $this->dispatch('obligacionActualizada');
    }

    /* ============================================================
     | 🔹 RENDERIZADO
     |============================================================ */

    public function render()
    {
        return view('livewire.clientes.datos-fiscales', [
            'regimenesFiltrados'            => $this->regimenesDisponibles,
            'actividadesFiltradas'          => $this->actividadesDisponibles,
            'obligacionesPeriodicasFiltradas' => $this->obligacionesPeriodicasDisponibles,
            'obligacionesUnicasFiltradas'   => $this->obligacionesUnicasDisponibles,
        ]);
    }
}
