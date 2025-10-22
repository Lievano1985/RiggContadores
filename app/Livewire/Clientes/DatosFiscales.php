<?php
/**
 * Componente Livewire: DatosFiscales
 * Descripción: Configura regímenes, actividades y obligaciones del cliente.
 * Fase 2.5: separa obligaciones periódicas y únicas; las únicas se crean una sola vez.
 * Autor: Luis Liévano - JL3 Digital
 */

namespace App\Livewire\Clientes;

use App\Models\Cliente;
use App\Models\Regimen;
use App\Models\ActividadEconomica;
use App\Models\Obligacion;
use App\Models\ObligacionClienteContador;
use App\Models\TareaCatalogo;
use App\Models\TareaAsignada;
use Livewire\Component;

class DatosFiscales extends Component
{
    public Cliente $cliente;

    // Listas
    public $regimenesDisponibles = [];
    public $actividadesDisponibles = [];
    public $obligacionesPeriodicasDisponibles = [];
    public $obligacionesUnicasDisponibles = [];

    // Selecciones
    public $regimenesSeleccionados = [];
    public $actividadesSeleccionadas = [];
    public $obligacionesSeleccionadas = [];        // periódicas
    public $obligacionesUnicasSeleccionadas = [];  // únicas (disparadores)

    // UI
    public bool $modoEdicion = false;
    public int $modoKey = 0;

    protected $listeners = [
        'DatosFiscalesActualizados' => 'CargarDatosFiscales'
    ];

    public function mount(Cliente $cliente)
    {
        $this->cliente = $cliente;
        $this->initializeLists();
    }

    public function CargarDatosFiscales()
    {
        $this->initializeLists();
        $this->removeOrphans();
    }

    protected function initializeLists(): void
    {
        // Actividades
        $this->actividadesDisponibles = ActividadEconomica::orderBy('nombre')->get();
        $this->actividadesSeleccionadas = $this->cliente->actividadesEconomicas()
            ->pluck('actividad_economica_id')->toArray();

        // Regímenes
        $this->loadRegimenesDisponibles();
        $this->regimenesSeleccionados = $this->cliente->regimenes()
            ->pluck('regimen_id')->toArray();

        // Obligaciones separadas
        $this->loadObligacionesDisponibles();

        // Solo preseleccionamos periódicas en pivot
        $this->obligacionesSeleccionadas = $this->cliente->obligaciones()
            ->where('periodicidad', '!=', 'unica')
            ->pluck('obligacion_id')
            ->toArray();

        // Las únicas no se guardan en pivot (se marcan al vuelo)
        $this->obligacionesUnicasSeleccionadas = [];
    }

    protected function loadRegimenesDisponibles(): void
    {
        $this->regimenesDisponibles = Regimen::where(fn($q) =>
            $q->where('tipo_persona', $this->cliente->tipo_persona)
              ->orWhere('tipo_persona', 'física/moral')
        )->orderBy('nombre')->get();
    }

    protected function loadObligacionesDisponibles(): void
    {
        // Periódicas (aplican filtro patronal si no tiene trabajadores)
        $periodicas = Obligacion::where('periodicidad', '!=', 'unica')
            ->when(!$this->cliente->tiene_trabajadores, fn($q) => $q->where('tipo', '!=', 'patronal'))
            ->orderBy('nombre')
            ->get();

        // Únicas (no aplicar filtro patronal por defecto; si lo deseas, lo agregamos)
        $unicas = Obligacion::where('periodicidad', 'unica')
            ->orderBy('nombre')
            ->get();

        $this->obligacionesPeriodicasDisponibles = $periodicas;
        $this->obligacionesUnicasDisponibles = $unicas;
    }

    protected function removeOrphans(): void
    {
        // Regímenes
        $allowedR = $this->regimenesDisponibles->pluck('id')->toArray();
        $orphanR = array_diff($this->regimenesSeleccionados, $allowedR);
        if (!empty($orphanR)) {
            $this->cliente->regimenes()->detach($orphanR);
            $this->regimenesSeleccionados = array_values(array_intersect($this->regimenesSeleccionados, $allowedR));
        }

        // Obligaciones periódicas
        $allowedP = $this->obligacionesPeriodicasDisponibles->pluck('id')->toArray();
        $orphanP = array_diff($this->obligacionesSeleccionadas, $allowedP);
        if (!empty($orphanP)) {
            $this->cliente->obligaciones()->detach($orphanP);
            ObligacionClienteContador::where('cliente_id', $this->cliente->id)
                ->whereIn('obligacion_id', $orphanP)
                ->delete();

            $this->obligacionesSeleccionadas = array_values(array_intersect($this->obligacionesSeleccionadas, $allowedP));
            $this->dispatch('obligacionActualizada');
        }

        // Las únicas no quedan en pivot, no hay huérfanos que limpiar ahí
    }

    public function updatedClienteTipoPersona($value): void
    {
        $this->cliente->tipo_persona = $value;
        $this->loadRegimenesDisponibles();

        $allowed = $this->regimenesDisponibles->pluck('id')->toArray();
        $orphan = array_diff($this->regimenesSeleccionados, $allowed);
        if (!empty($orphan)) {
            $this->cliente->regimenes()->detach($orphan);
            $this->regimenesSeleccionados = array_values(array_intersect($this->regimenesSeleccionados, $allowed));
        }
    }

    public function updatedClienteTieneTrabajadores($value): void
    {
        $this->cliente->tiene_trabajadores = $value;
        $this->loadObligacionesDisponibles();

        $allowed = $this->obligacionesPeriodicasDisponibles->pluck('id')->toArray();
        $orphan = array_diff($this->obligacionesSeleccionadas, $allowed);
        if (!empty($orphan)) {
            $this->eliminarAsignacionesYtareasDeObligaciones($orphan);
        }
    }

    public function guardar(): void
    {
        // Regímenes y actividades
        $this->cliente->regimenes()->sync($this->regimenesSeleccionados);
        $this->cliente->actividadesEconomicas()->sync($this->actividadesSeleccionadas);

        // 1) Periódicas: se sincronizan en pivot y se crean asignaciones/tareas
        $sincronizacion = $this->cliente->obligaciones()->sync($this->obligacionesSeleccionadas);

        if (!empty($sincronizacion['attached'])) {
            $this->crearAsignacionesYtareasIniciales($sincronizacion['attached']);
        }

        if (!empty($sincronizacion['detached'])) {
            $this->eliminarAsignacionesYtareasDeObligaciones($sincronizacion['detached']);
        }

        // 2) Únicas: se crean una sola vez y NO se dejan en pivot
        if (!empty($this->obligacionesUnicasSeleccionadas)) {
            $this->crearUnicasYtareas($this->obligacionesUnicasSeleccionadas);
            $this->obligacionesUnicasSeleccionadas = [];
        }

        session()->flash('message', 'Datos fiscales actualizados correctamente.');
        $this->modoEdicion = false;
        $this->modoKey++;
        $this->dispatch('obligacionActualizada');
    }

    /**
     * Crea asignaciones/tareas iniciales para obligaciones periódicas
     * usando el cálculo de vencimiento del modelo.
     */
    protected function crearAsignacionesYtareasIniciales(array $idsObligaciones): void
    {
        $anioActual = now()->year;
        $mesActual  = now()->month;

        foreach ($idsObligaciones as $obligacionId) {
            $obligacion = Obligacion::find($obligacionId);
            if (!$obligacion) continue;

            // Determinar mes efectivo según bloque (periódicas)
            $periodicidad = strtolower($obligacion->periodicidad ?? 'mensual');
            switch ($periodicidad) {
                case 'mensual':
                    $mesEfectivo = $mesActual; break;
                case 'bimestral':
                    $mesEfectivo = ($mesActual % 2 === 0) ? $mesActual - 1 : $mesActual; break;
                case 'trimestral':
                    foreach ([1,4,7,10] as $ini) if ($mesActual >= $ini) $mesEfectivo = $ini; break;
                case 'cuatrimestral':
                    foreach ([1,5,9] as $ini) if ($mesActual >= $ini) $mesEfectivo = $ini; break;
                case 'semestral':
                    foreach ([1,7] as $ini) if ($mesActual >= $ini) $mesEfectivo = $ini; break;
                case 'anual':
                    $mesEfectivo = 1; break;
                default:
                    $mesEfectivo = $mesActual; break;
            }

            $fechaVenc = $obligacion->calcularFechaVencimiento($anioActual, $mesEfectivo);

            $asignacion = ObligacionClienteContador::updateOrCreate(
                [
                    'cliente_id'    => $this->cliente->id,
                    'obligacion_id' => $obligacionId,
                    'ejercicio'     => $anioActual,
                    'mes'           => $mesEfectivo,
                ],
                [
                    'estatus'           => 'asignada',
                    'fecha_asignacion'  => now(),
                    'fecha_vencimiento' => $fechaVenc?->toDateString(),
                ]
            );

            $tareas = TareaCatalogo::where('obligacion_id', $obligacionId)->where('activo', true)->get();
            foreach ($tareas as $t) {
                TareaAsignada::updateOrCreate(
                    [
                        'cliente_id'                     => $this->cliente->id,
                        'tarea_catalogo_id'              => $t->id,
                        'obligacion_cliente_contador_id' => $asignacion->id,
                        'ejercicio'                      => $anioActual,
                        'mes'                            => $mesEfectivo,
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

    /**
     * Crea una sola vez las obligaciones 'únicas' y sus tareas.
     * La fecha de vencimiento queda null (el contador la definirá en ObligacionesAsignadas).
     */
    protected function crearUnicasYtareas(array $idsObligacionesUnicas): void
    {
        $anioActual = now()->year;
        $mesActual  = now()->month;

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
                    'estatus'           => 'asignada',
                    'fecha_asignacion'  => now(),
                    'fecha_vencimiento' => null, // fecha manual posterior
                ]
            );

            $tareas = TareaCatalogo::where('obligacion_id', $obligacionId)->where('activo', true)->get();
            foreach ($tareas as $t) {
                TareaAsignada::updateOrCreate(
                    [
                        'cliente_id'                     => $this->cliente->id,
                        'tarea_catalogo_id'              => $t->id,
                        'obligacion_cliente_contador_id' => $asignacion->id,
                        'ejercicio'                      => $anioActual,
                        'mes'                            => $mesActual,
                    ],
                    [
                        'fecha_asignacion' => now(),
                        'fecha_limite'     => null, // manual
                        'estatus'          => 'asignada',
                    ]
                );
            }
        }
    }

    protected function eliminarAsignacionesYtareasDeObligaciones(array $ids): void
    {
        if (empty($ids)) return;

        $asignaciones = ObligacionClienteContador::where('cliente_id', $this->cliente->id)
            ->whereIn('obligacion_id', $ids)
            ->get();

        foreach ($asignaciones as $asignacion) {
            TareaAsignada::where('obligacion_cliente_contador_id', $asignacion->id)->delete();
            $asignacion->delete();
        }

        $this->cliente->obligaciones()->detach($ids);

        $this->obligacionesSeleccionadas = array_values(
            array_intersect($this->obligacionesSeleccionadas, $this->obligacionesPeriodicasDisponibles->pluck('id')->toArray())
        );

        $this->dispatch('obligacionActualizada');
    }

    public function render()
    {
        return view('livewire.clientes.datos-fiscales');
    }
}
