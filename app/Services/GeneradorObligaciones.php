<?php
/**
 * Servicio: GeneradorObligaciones
 * Autor: Luis Liévano - JL3 Digital
 * Descripción técnica:
 * - Genera instancias mensuales (o del periodo que inicia) en obligacion_cliente_contador.
 * - Excluye obligaciones 'únicas'.
 * - Solo genera si el periodo COMIENZA en el mes/año de referencia.
 * - Evita duplicados con la unique(cliente_id, obligacion_id, ejercicio, mes).
 * - Hereda contador_id y carpeta_drive_id del último periodo (si existe).
 * - Crea tareas desde TareaCatalogo (activo = true) para cada obligación generada.
 */

namespace App\Services;

use App\Models\Cliente;
use App\Models\Obligacion;
use App\Models\ObligacionClienteContador;
use App\Models\TareaAsignada;
use App\Models\TareaCatalogo;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class GeneradorObligaciones
{
    /**
     * Punto de entrada principal.
     * @param Carbon|null $fechaReferencia  Mes/Año a generar (default = now())
     * @return array{generadas:int, omitidas:int, ya_existian:int}
     */
    public function generarParaPeriodo(?Carbon $fechaReferencia = null): array
    {
        $ref = $fechaReferencia?->copy()->startOfMonth() ?? now()->copy()->startOfMonth();
        $mesActual = (int)$ref->month;
        $anioActual = (int)$ref->year;

        $generadas = 0;
        $omitidas  = 0;
        $yaExist   = 0;

        // 1) Tomamos TODAS las obligaciones activas NO únicas del catálogo
        //    y buscamos los clientes que las tengan asignadas en pivot.
        Obligacion::query()
            ->where('activa', true)
            ->whereNotIn('periodicidad', ['unica', 'única'])
            ->orderBy('nombre')
            ->chunk(200, function ($obligaciones) use (&$generadas, &$omitidas, &$yaExist, $mesActual, $anioActual) {

                foreach ($obligaciones as $ob) {
                    // 2) Solo si ESTE MES es inicio de ciclo para esta obligación
                    if (!$this->mesEsInicioDeCiclo($ob, $mesActual)) {
                        $omitidas++;
                        continue;
                    }

                    // 3) Buscar todos los clientes que tienen esta obligación en pivot
                    $clientes = $ob->clientes()->select('clientes.id')->get(); // relación belongsToMany en tu modelo Obligacion
                    if ($clientes->isEmpty()) {
                        $omitidas++;
                        continue;
                    }

                    foreach ($clientes as $cli) {
                        DB::beginTransaction();
                        try {
                            // 4) Evitar duplicado por índice único
                            $existe = ObligacionClienteContador::query()
                                ->where('cliente_id', $cli->id)
                                ->where('obligacion_id', $ob->id)
                                ->where('ejercicio', $anioActual)
                                ->where('mes', $mesActual)
                                ->exists();

                            if ($existe) {
                                $yaExist++;
                                DB::rollBack(); // nada que hacer
                                continue;
                            }

                            // 5) Heredar ultimo periodo (si existe)
                            $ultimo = ObligacionClienteContador::query()
                                ->where('cliente_id', $cli->id)
                                ->where('obligacion_id', $ob->id)
                                ->orderByDesc('ejercicio')
                                ->orderByDesc('mes')
                                ->first();

                            $contadorId     = $ultimo?->contador_id;
                            $carpetaDriveId = $ultimo?->carpeta_drive_id;

                            // 6) Calcular fecha de vencimiento del periodo que inicia
                            $fechaVenc = $ob->calcularFechaVencimiento($anioActual, $mesActual);
                            $occ = ObligacionClienteContador::create([
                                'cliente_id'          => $cli->id,
                                'obligacion_id'       => $ob->id,
                                'contador_id'         => $contadorId,
                                'carpeta_drive_id'    => $carpetaDriveId,
                                'ejercicio'           => $anioActual,
                                'mes'                 => $mesActual,
                                'estatus'             => 'asignada',
                                'fecha_asignacion'    => now(),
                                'fecha_vencimiento'   => $fechaVenc?->toDateString(),
                                'revision'            => 1,
                                'obligacion_padre_id' => $ultimo?->id,
                            ]);

                            // 7) Crear tareas desde el catálogo activo
                            $this->crearTareasPara($occ, $fechaVenc);

                            DB::commit();
                            $generadas++;
                        } catch (\Throwable $e) {
                            DB::rollBack();
                            // Puedes loguear el error si lo deseas:
                            // \Log::error('GeneradorObligaciones', ['error' => $e->getMessage()]);
                            $omitidas++;
                        }
                    }
                }
            });

        return ['generadas' => $generadas, 'omitidas' => $omitidas, 'ya_existian' => $yaExist];
    }

    /**
     * ¿El mes dado es INICIO de ciclo para esta obligación?
     * Usa periodicidad y mes_inicio; mensual => siempre.
     */
    protected function mesEsInicioDeCiclo(Obligacion $ob, int $mes): bool
    {
        $p = strtolower($ob->periodicidad ?? 'mensual');
        $duracion = match ($p) {
            'bimestral'     => 2,
            'trimestral'    => 3,
            'cuatrimestral' => 4,
            'semestral'     => 6,
            'anual'         => 12,
            default         => 1, // mensual
        };

        // mes_inicio configurable (default 1 en tu modelo)
        $mesInicio = (int)($ob->mes_inicio ?? 1);
        if ($duracion === 1) {
            return true; // mensual: inicia cada mes
        }

        // Mes es inicio si (mes - mes_inicio) % duracion == 0 y (mes - mes_inicio) >= 0 (considerando ciclo anual)
        $delta = ($mes - $mesInicio);
        // normalizamos delta a [0..11] para ciclos cruzando año
        $delta = ($delta % 12 + 12) % 12;

        return ($delta % $duracion) === 0;
    }

    /**
     * Crea tareas para la OCC desde el catálogo activo de tareas.
     */
    protected function crearTareasPara(ObligacionClienteContador $occ, ?Carbon $fechaVenc): void
    {
        // Tareas del catálogo (solo activas) ligadas a la obligación
        $tareas = TareaCatalogo::query()
            ->where('obligacion_id', $occ->obligacion_id)
            ->where('activo', true)
            ->get();

        foreach ($tareas as $t) {
            TareaAsignada::updateOrCreate(
                [
                    'cliente_id'                     => $occ->cliente_id,
                    'tarea_catalogo_id'              => $t->id,
                    'obligacion_cliente_contador_id' => $occ->id,
                    'ejercicio'                      => $occ->ejercicio,
                    'mes'                            => $occ->mes,
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
