<?php

/**
 * Servicio: GeneradorObligaciones
 * Autor: Luis Liévano - JL3 Digital
 * Descripción técnica:
 * - Genera instancias mensuales (o del periodo que inicia) en obligacion_cliente_contador.
 * - Excluye obligaciones 'únicas'.
 * - Solo genera si el periodo COMIENZA en el mes/año de referencia.
 * - Evita duplicados con la unique(cliente_id, obligacion_id, ejercicio, mes).
 * - Hereda contador_id, carpeta_drive_id y sin_carpeta del último periodo (si existe y sigue activa).
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
        $mesActual = (int) $ref->month;
        $anioActual = (int) $ref->year;

        $generadas = 0;
        $omitidas  = 0;
        $yaExist   = 0;

        // 1️⃣ Tomamos TODAS las obligaciones activas NO únicas del catálogo
        Obligacion::query()
            ->where('activa', true)
            ->whereNotIn('periodicidad', ['unica', 'única'])
            ->orderBy('nombre')
            ->chunk(200, function ($obligaciones) use (&$generadas, &$omitidas, &$yaExist, $mesActual, $anioActual) {

                foreach ($obligaciones as $ob) {
                    // 2️⃣ Solo si ESTE MES es inicio de ciclo para esta obligación
                    if (!$this->mesEsInicioDeCiclo($ob, $mesActual)) {
                        $omitidas++;
                        continue;
                    }

                    // 3️⃣ Buscar clientes que tienen esta obligación asignada (pivot)
                    $clientes = Cliente::whereIn('id', function ($q) use ($ob) {
                        $q->select('cliente_id')
                            ->from('obligacion_cliente_contador')
                            ->where('obligacion_id', $ob->id)
                            ->where('is_activa', true)
                            ->distinct();
                    })
                        ->select('id')
                        ->get();

                    if ($clientes->isEmpty()) {
                        $omitidas++;
                        continue;
                    }


                    foreach ($clientes as $cli) {
                        DB::beginTransaction();
                        try {
                            // 🟡 4️⃣ Excluir clientes con obligación dada de baja
                            $baja = ObligacionClienteContador::where('cliente_id', $cli->id)
                                ->where('obligacion_id', $ob->id)
                                ->where('is_activa', false)
                                ->exists();

                            if ($baja) {
                                $omitidas++;
                                DB::rollBack();
                                continue;
                            }

                            // 5️⃣ Evitar duplicado (ya existe para este periodo)
                            $existe = ObligacionClienteContador::query()
                                ->where('cliente_id', $cli->id)
                                ->where('obligacion_id', $ob->id)
                                ->where('ejercicio', $anioActual)
                                ->where('mes', $mesActual)
                                ->exists();

                            if ($existe) {
                                $yaExist++;
                                DB::rollBack();
                                continue;
                            }

                            // 6️⃣ Heredar último periodo (solo si estaba activo)
                            $ultimo = ObligacionClienteContador::query()
                                ->where('cliente_id', $cli->id)
                                ->where('obligacion_id', $ob->id)
                                ->where('is_activa', true) // 🟡 Solo hereda si la última sigue activa
                                ->orderByDesc('ejercicio')
                                ->orderByDesc('mes')
                                ->first();

                            $contadorId     = $ultimo?->contador_id;
                            $carpetaDriveId = $ultimo?->carpeta_drive_id;
                            $sinCarpeta     = (bool) ($ultimo?->sin_carpeta ?? false);

                            // 7️⃣ Calcular fecha de vencimiento
                            $fechaVenc = $ob->calcularFechaVencimiento($anioActual, $mesActual);
                            $occ = ObligacionClienteContador::create([
                                'cliente_id'          => $cli->id,
                                'obligacion_id'       => $ob->id,
                                'contador_id'         => $contadorId,
                                'carpeta_drive_id'    => $carpetaDriveId,
                                'sin_carpeta'         => $sinCarpeta,
                                'ejercicio'           => $anioActual,
                                'mes'                 => $mesActual,
                                'estatus'             => 'asignada',
                                'fecha_asignacion'    => now(),
                                'fecha_vencimiento'   => $fechaVenc?->toDateString(),
                                'revision'            => 1,
                                'obligacion_padre_id' => $ultimo?->id,
                                'is_activa'           => true, // aseguramos que inicien activas
                            ]);

                            // 8️⃣ Crear tareas del catálogo
                            $this->crearTareasPara($occ, $fechaVenc);

                            DB::commit();
                            $generadas++;
                        } catch (\Throwable $e) {
                            DB::rollBack();
                            $omitidas++;
                        }
                    }
                }
            });

        return [
            'generadas'  => $generadas,
            'omitidas'   => $omitidas,
            'ya_existian' => $yaExist,
        ];
    }

    /**
     * Determina si el mes dado es INICIO de ciclo según periodicidad.
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
            default         => 1,
        };

        $mesInicio = (int) ($ob->mes_inicio ?? 1);
        if ($duracion === 1) {
            return true;
        }

        $delta = ($mes - $mesInicio);
        $delta = ($delta % 12 + 12) % 12;

        return ($delta % $duracion) === 0;
    }

    /**
     * Crea tareas para la OCC desde el catálogo activo.
     */
    protected function crearTareasPara(ObligacionClienteContador $occ, ?Carbon $fechaVenc): void
    {
        $tareas = TareaCatalogo::query()
            ->where('obligacion_id', $occ->obligacion_id)
            ->where('activo', true)
            ->get();

        foreach ($tareas as $t) {
            $tareaAnterior = TareaAsignada::query()
                ->where('cliente_id', $occ->cliente_id)
                ->where('tarea_catalogo_id', $t->id)
                ->where(function ($query) use ($occ) {
                    $query->where('ejercicio', '<', $occ->ejercicio)
                        ->orWhere(function ($subQuery) use ($occ) {
                            $subQuery->where('ejercicio', $occ->ejercicio)
                                ->where('mes', '<', $occ->mes);
                        });
                })
                ->orderByDesc('ejercicio')
                ->orderByDesc('mes')
                ->orderByDesc('id')
                ->first();

            TareaAsignada::updateOrCreate(
                [
                    'cliente_id'                     => $occ->cliente_id,
                    'tarea_catalogo_id'              => $t->id,
                    'obligacion_cliente_contador_id' => $occ->id,
                    'ejercicio'                      => $occ->ejercicio,
                    'mes'                            => $occ->mes,
                ],
                [
                    'contador_id'      => $occ->contador_id,
                    'carpeta_drive_id' => $tareaAnterior?->sin_carpeta ? null : $tareaAnterior?->carpeta_drive_id,
                    'sin_carpeta'      => (bool) ($tareaAnterior?->sin_carpeta ?? false),
                    'fecha_asignacion' => now(),
                    'fecha_limite'     => $fechaVenc?->toDateString(),
                    'estatus'          => 'asignada',
                ]
            );
        }
    }

    /**
     * Agrega una tarea de catalogo a obligaciones activas que ya existen en el mes actual.
     */
    public function sincronizarTareaPeriodoActual(TareaCatalogo $tarea, ?Carbon $fechaReferencia = null): int
    {
        $ref = $fechaReferencia?->copy()->startOfMonth() ?? now()->copy()->startOfMonth();

        return $this->sincronizarTareaEnRango(
            $tarea,
            (int) $ref->year,
            (int) $ref->month,
            (int) $ref->year,
            (int) $ref->month
        );
    }

    public function sincronizarTareaEnRango(
        TareaCatalogo $tarea,
        int $anioInicio,
        int $mesInicio,
        int $anioFin,
        int $mesFin
    ): int {
        if (! $tarea->obligacion_id || ! $tarea->activo) {
            return 0;
        }

        $asignadas = 0;
        $periodoInicio = ($anioInicio * 100) + $mesInicio;
        $periodoFin = ($anioFin * 100) + $mesFin;

        ObligacionClienteContador::query()
            ->where('obligacion_id', $tarea->obligacion_id)
            ->whereRaw('(ejercicio * 100 + mes) between ? and ?', [$periodoInicio, $periodoFin])
            ->where('is_activa', true)
            ->chunkById(200, function ($obligaciones) use ($tarea, &$asignadas) {
                foreach ($obligaciones as $occ) {
                    $asignacion = TareaAsignada::firstOrCreate(
                        [
                            'cliente_id'                     => $occ->cliente_id,
                            'tarea_catalogo_id'              => $tarea->id,
                            'obligacion_cliente_contador_id' => $occ->id,
                            'ejercicio'                      => $occ->ejercicio,
                            'mes'                            => $occ->mes,
                        ],
                        [
                            'contador_id'      => $occ->contador_id,
                            'carpeta_drive_id' => null,
                            'sin_carpeta'      => false,
                            'fecha_asignacion' => now(),
                            'fecha_limite'     => $occ->fecha_vencimiento,
                            'estatus'          => 'asignada',
                        ]
                    );

                    if ($asignacion->wasRecentlyCreated) {
                        if (in_array($occ->estatus, [
                            'realizada',
                            'enviada_cliente',
                            'respuesta_cliente',
                            'respuesta_revisada',
                            'finalizado',
                        ], true)) {
                            $occ->update([
                                'estatus' => 'reabierta',
                                'fecha_termino' => null,
                                'fecha_finalizado' => null,
                            ]);
                        }

                        $asignadas++;
                    }
                }
            });

        return $asignadas;
    }

    public function quitarTareaPeriodoActual(TareaCatalogo $tarea, ?Carbon $fechaReferencia = null): int
    {
        $ref = $fechaReferencia?->copy()->startOfMonth() ?? now()->copy()->startOfMonth();
        $eliminadas = 0;

        TareaAsignada::query()
            ->where('tarea_catalogo_id', $tarea->id)
            ->where('ejercicio', (int) $ref->year)
            ->where('mes', (int) $ref->month)
            ->chunkById(200, function ($tareas) use (&$eliminadas) {
                foreach ($tareas as $tareaAsignada) {
                    $tareaAsignada->archivos()->delete();
                    $tareaAsignada->delete();
                    $eliminadas++;
                }
            });

        return $eliminadas;
    }

    /**
     * Genera manualmente obligaciones especificas para un cliente, en un mes/anio determinado.
     * No afecta la logica de CRON. Usado desde componente Livewire.
     */
    public function generarManualClienteObligaciones(Cliente $cliente, array $obligacionIds, int $anio, int $mes): array
    {
        $generadas = [];
        $omitidas = [];
        $yaExistian = [];

        foreach ($obligacionIds as $obligacionId) {
            $obligacion = Obligacion::find($obligacionId);
            if (!$obligacion || $obligacion->esUnica()) {
                $omitidas[] = $obligacionId;
                continue;
            }

            // Verificar si ya existe esa obligación para ese periodo
            $existe = ObligacionClienteContador::where([
                'cliente_id' => $cliente->id,
                'obligacion_id' => $obligacionId,
                'mes' => $mes,
                'ejercicio' => $anio,
            ])->first();

            if ($existe) {
                $yaExistian[] = $obligacionId;
                continue;
            }

            // Calcular vencimiento
            $fechaVenc = $obligacion->calcularFechaVencimiento($anio, $mes);

            // Heredar último periodo si existe
            $ultimo = ObligacionClienteContador::query()
                ->where('cliente_id', $cliente->id)
                ->where('obligacion_id', $obligacionId)
                ->where('is_activa', true)
                ->orderByDesc('ejercicio')
                ->orderByDesc('mes')
                ->first();

            $contadorId     = $ultimo?->contador_id ?? $cliente->contador_id;
            $carpetaDriveId = $ultimo?->carpeta_drive_id;
            $sinCarpeta     = (bool) ($ultimo?->sin_carpeta ?? false);

            // Crear obligación completa
            $occ = ObligacionClienteContador::create([
                'cliente_id'         => $cliente->id,
                'obligacion_id'      => $obligacionId,
                'contador_id'        => $contadorId,
                'carpeta_drive_id'   => $carpetaDriveId,
                'sin_carpeta'        => $sinCarpeta,
                'mes'                => $mes,
                'ejercicio'          => $anio,
                'estatus'            => 'asignada',
                'fecha_asignacion'   => now(),
                'fecha_vencimiento'  => $fechaVenc,
                'obligacion_padre_id' => $ultimo?->id,
                'revision'           => 1,
                'is_activa'          => true,
            ]);


            // Crear tareas asociadas
            $this->crearTareasPara($occ, $fechaVenc);

            $generadas[] = $occ->id;
        }

        return [
            'generadas'    => count($generadas),
            'omitidas'     => count($omitidas),
            'ya_existian'  => count($yaExistian),
            'ids_generadas' => $generadas,
            'omitidas_ids' => $omitidas,
            'ya_existian_ids' => $yaExistian,
        ];
    }
}
