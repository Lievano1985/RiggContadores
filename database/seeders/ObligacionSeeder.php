<?php
/**
 * Seeder: ObligacionSeeder
 * Autor: Luis Liévano - JL3 Digital
 * Descripción: Catálogo de obligaciones fiscales/patronales/laborales MX.
 * Notas:
 * - desfase_meses = "mes límite" (1=enero,...,12=diciembre) cuando aplica.
 * - dia_corte = "día límite".
 * - Para mensual: día 17 (regla SAT/IMSS). Para anual PF/PM y PTU ver fechas abajo.
 */

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ObligacionSeeder extends Seeder
{
    public function run()
    {
        DB::table('obligaciones')->delete();

        DB::table('obligaciones')->insert([
            // =========================
            // === FEDERALES (MENSUAL)
            // =========================
            [
                'nombre' => 'Pago provisional de ISR',
                'tipo' => 'federal',
                'periodicidad' => 'mensual',
                'mes_inicio' => 1,
                'desfase_meses' => 1, // mes siguiente
                'dia_corte' => 17,
                'activa' => true,
            ],
            [
                'nombre' => 'Pago mensual de IVA',
                'tipo' => 'federal',
                'periodicidad' => 'mensual',
                'mes_inicio' => 1,
                'desfase_meses' => 1,
                'dia_corte' => 17,
                'activa' => true,
            ],
            [
                'nombre' => 'Pago mensual de IEPS',
                'tipo' => 'federal',
                'periodicidad' => 'mensual',
                'mes_inicio' => 1,
                'desfase_meses' => 1,
                'dia_corte' => 17,
                'activa' => true,
            ],
            [
                'nombre' => 'Retenciones ISR (nómina/honorarios/arrendamiento)',
                'tipo' => 'federal',
                'periodicidad' => 'mensual',
                'mes_inicio' => 1,
                'desfase_meses' => 1,
                'dia_corte' => 17,
                'activa' => true,
            ],
            [
                'nombre' => 'DIOT - Declaración informativa de operaciones con terceros',
                'tipo' => 'federal',
                'periodicidad' => 'mensual',
                'mes_inicio' => 1,
                'desfase_meses' => 1,
                'dia_corte' => 17, // regla general; puede ajustarse por plataforma
                'activa' => true,
            ],

            // =========================
            // === FEDERALES (ANUAL)
            // =========================
            [
                'nombre' => 'Declaración anual ISR - Personas Morales',
                'tipo' => 'federal',
                'periodicidad' => 'anual',
                'mes_inicio' => 1,
                'desfase_meses' => 3, // marzo
                'dia_corte' => 31,
                'activa' => true,
            ],
            [
                'nombre' => 'Declaración anual ISR - Personas Físicas',
                'tipo' => 'federal',
                'periodicidad' => 'anual',
                'mes_inicio' => 1,
                'desfase_meses' => 4, // abril
                'dia_corte' => 30,
                'activa' => true,
            ],

            // =========================
            // === LABORAL (PTU)
            // =========================
            [
                'nombre' => 'PTU - Reparto de utilidades (Personas Morales)',
                'tipo' => 'laboral',
                'periodicidad' => 'anual',
                'mes_inicio' => 1,
                'desfase_meses' => 5, // mayo
                'dia_corte' => 30,
                'activa' => true,
            ],
            [
                'nombre' => 'PTU - Reparto de utilidades (Personas Físicas)',
                'tipo' => 'laboral',
                'periodicidad' => 'anual',
                'mes_inicio' => 1,
                'desfase_meses' => 6, // junio
                'dia_corte' => 29,
                'activa' => true,
            ],

            // =========================
            // === PATRONALES
            // =========================
            [
                'nombre' => 'Pago mensual de cuotas IMSS (SUA)',
                'tipo' => 'patronal',
                'periodicidad' => 'mensual',
                'mes_inicio' => 1,
                'desfase_meses' => 1,
                'dia_corte' => 17,
                'activa' => true,
            ],
            [
                'nombre' => 'INFONAVIT - Aportaciones y amortizaciones (bimestral)',
                'tipo' => 'patronal',
                'periodicidad' => 'bimestral',
                'mes_inicio' => 1,
                'desfase_meses' => 1, // regla genérica; tu app puede mapear el calendario oficial por año
                'dia_corte' => 17,
                'activa' => true,
            ],
            [
                'nombre' => 'Determinación anual de Prima de Riesgo de Trabajo (IMSS)',
                'tipo' => 'patronal',
                'periodicidad' => 'anual',
                'mes_inicio' => 1,
                'desfase_meses' => 2, // febrero
                'dia_corte' => 28,     // último día hábil de feb; ajustar en años bisiestos si lo deseas
                'activa' => true,
            ],
            [
                'nombre' => 'Pago de aportaciones FONACOT',
                'tipo' => 'patronal',
                'periodicidad' => 'mensual',
                'mes_inicio' => 1,
                'desfase_meses' => 1,
                'dia_corte' => 17, // puede variar según convenio; usar como regla general
                'activa' => true,
            ],

            // =========================
            // === OBLIGACIONES "ÚNICA VEZ"
            // =========================
            [
                'nombre' => 'Inscripción en el RFC (inicio de actividades)',
                'tipo' => 'federal',
                'periodicidad' => 'única',
                'mes_inicio' => null,
                'desfase_meses' => null,
                'dia_corte' => null,
                'activa' => true,
            ],
            [
                'nombre' => 'Aviso de apertura de establecimiento ante SAT',
                'tipo' => 'federal',
                'periodicidad' => 'única',
                'mes_inicio' => null,
                'desfase_meses' => null,
                'dia_corte' => null,
                'activa' => true,
            ],
            [
                'nombre' => 'Alta patronal ante IMSS',
                'tipo' => 'patronal',
                'periodicidad' => 'única',
                'mes_inicio' => null,
                'desfase_meses' => null,
                'dia_corte' => null,
                'activa' => true,
            ],
            [
                'nombre' => 'Inscripción patronal ante INFONAVIT',
                'tipo' => 'patronal',
                'periodicidad' => 'única',
                'mes_inicio' => null,
                'desfase_meses' => null,
                'dia_corte' => null,
                'activa' => true,
            ],
            [
                'nombre' => 'Registro REPSE (servicios u obras especializadas)',
                'tipo' => 'patronal',
                'periodicidad' => 'única',
                'mes_inicio' => null,
                'desfase_meses' => null,
                'dia_corte' => null,
                'activa' => true,
            ],
            [
                'nombre' => 'Registro patronal ante FONACOT',
                'tipo' => 'patronal',
                'periodicidad' => 'única',
                'mes_inicio' => null,
                'desfase_meses' => null,
                'dia_corte' => null,
                'activa' => true,
            ],

            // =========================
            // === ESTATALES / LOCALES (GENÉRICAS)
            // =========================
            [
                'nombre' => 'Impuesto Sobre Nóminas (ISN) – estatal',
                'tipo' => 'estatal',
                'periodicidad' => 'mensual',
                'mes_inicio' => 1,
                'desfase_meses' => 1,
                'dia_corte' => 17, // puede variar por estado
                'activa' => true,
            ],
            [
                'nombre' => 'Impuesto sobre Hospedaje – estatal',
                'tipo' => 'estatal',
                'periodicidad' => 'mensual',
                'mes_inicio' => 1,
                'desfase_meses' => 1,
                'dia_corte' => 17, // puede variar por estado
                'activa' => true,
            ],
            [
                'nombre' => 'Tenencia / Control vehicular – estatal',
                'tipo' => 'estatal',
                'periodicidad' => 'anual',
                'mes_inicio' => 1,
                'desfase_meses' => 3, // marzo (genérico; varía por estado)
                'dia_corte' => 31,
                'activa' => true,
            ],
            [
                'nombre' => 'Licencia de funcionamiento municipal',
                'tipo' => 'local',
                'periodicidad' => 'anual',
                'mes_inicio' => 1,
                'desfase_meses' => 3, // muchas municipalidades cierran en Q1
                'dia_corte' => 31,
                'activa' => true,
            ],
        ]);
    }
}
