<?php
/**
 * Comando Artisan: obligaciones:generar
 * Autor: Luis Liévano - JL3 Digital
 * Descripción técnica:
 * Ejecuta el servicio GeneradorObligaciones para crear automáticamente las obligaciones
 * correspondientes al mes/año indicado o, por defecto, al mes actual.
 *
 * Uso manual:
 *   php artisan obligaciones:generar
 *   php artisan obligaciones:generar --mes=10 --anio=2025
 *
 * Uso automático (CRON):
 *   Programar en Kernel.php -> monthlyOn(1, '01:05')
 */

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\GeneradorObligaciones;
use Carbon\Carbon;

class GenerarObligacionesMensuales extends Command
{
    /**
     * Nombre del comando y opciones.
     */
    protected $signature = 'obligaciones:generar {--mes=} {--anio=}';
    protected $description = 'Genera automáticamente las obligaciones del periodo que inicia, excluyendo solo las únicas y creando sus tareas.';

    /**
     * Ejecuta el comando.
     */
    public function handle(GeneradorObligaciones $generador): int
    {
        // Determinar mes/año de referencia
        $mes  = $this->option('mes');
        $anio = $this->option('anio');

        $ref = now()->startOfMonth();
        if ($mes && $anio) {
            $ref = Carbon::createFromDate((int)$anio, (int)$mes, 1)->startOfMonth();
        } elseif ($mes) {
            $ref = Carbon::createFromDate((int)now()->year, (int)$mes, 1)->startOfMonth();
        } elseif ($anio) {
            $ref = Carbon::createFromDate((int)$anio, (int)now()->month, 1)->startOfMonth();
        }

        $this->info("🔄 Generando obligaciones para el periodo {$ref->format('Y-m')}...");
        $resultado = $generador->generarParaPeriodo($ref);

        $this->line("✅ Generadas: {$resultado['generadas']}");
        $this->line("⚠️  Omitidas:  {$resultado['omitidas']}");
        $this->line("ℹ️  Ya existían: {$resultado['ya_existian']}");
        $this->info("Proceso completado correctamente.");

        return self::SUCCESS;
    }
}
