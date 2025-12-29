<?php

/**
 * Servicio: GeneradorObligaciones
 * Autor: Luis Liévano - JL3 Digital
 * Descripción técnica:
 * - Genera instancias mensuales (o del periodo que inicia) en obligacion_cliente_contador.
 * - Excluye obligaciones 'únicas'.
 * - Solo genera si el periodo COMIENZA en el mes/año de referencia.
 * - Evita duplicados con la unique(cliente_id, obligacion_id, ejercicio, mes).
 * - Hereda contador_id y carpeta_drive_id del último periodo (si existe y sigue activa).
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
                    $clientes = $ob->clientes()->select('clientes.id')->get();
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

                            // 7️⃣ Calcular fecha de vencimiento
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


    /**
     * Genera manualmente obligaciones específicas para un cliente, en un mes/año determinado.
     * No afecta la lógica de CRON. Usado desde componente Livewire.
     * Autor: Luis Liévano - JL3 Digital
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

            // Crear obligación completa
            $occ = ObligacionClienteContador::create([
                'cliente_id'         => $cliente->id,
                'obligacion_id'      => $obligacionId,
                'contador_id'        => $contadorId,
                'carpeta_drive_id'   => $carpetaDriveId,
                'mes'                => $mes,
                'ejercicio'          => $anio,
                'estatus'            => 'asignada',
                'fecha_asignacion'   => now(),
                'fecha_vencimiento'  => $fechaVenc,
                'obligacion_padre_id' => $ultimo?->id,
                'revision'           => 1,
                'is_activa'          => true,
            ]);

            // Registrar en cliente_obligacion (si no existía)
            $cliente->obligaciones()->syncWithoutDetaching([$obligacionId]);

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
