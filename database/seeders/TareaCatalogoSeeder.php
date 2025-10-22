<?php
/**
 * Seeder: TareaCatalogoSeeder
 * Autor: Luis Liévano - JL3 Digital
 * Descripción: Catálogo base de tareas ligadas a obligaciones y genéricas.
 */

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\TareaCatalogo;
use App\Models\Obligacion;

class TareaCatalogoSeeder extends Seeder
{
    public function run(): void
    {
        // === Tareas ligadas a obligaciones ===
        $catalogo = [
            'ISR' => [
                'Revisión de facturas de ingresos y egresos',
                'Determinación de pagos provisionales',
                'Elaboración y revisión de declaración mensual',
                'Carga de declaración en SAT',
            ],
            'IVA' => [
                'Clasificación de facturas de compras y ventas',
                'Determinación de IVA acreditable y trasladado',
                'Elaboración de declaración mensual de IVA',
                'Envío de declaración en el portal SAT',
            ],
            'DIOT' => [
                'Revisión de proveedores y clientes',
                'Integración de XMLs',
                'Elaboración de archivo DIOT',
                'Envío en la plataforma SAT',
            ],
            'IMSS' => [
                'Captura de incidencias en SUA',
                'Determinación de cuotas obrero-patronales',
                'Generación de archivo de pago',
                'Pago en ventanilla/banca en línea',
            ],
            'INFONAVIT' => [
                'Revisión de créditos vigentes',
                'Cálculo de aportaciones bimestrales',
                'Generación de archivo SUA',
                'Envío de pago al banco',
            ],
            'PTU' => [
                'Determinación de base gravable',
                'Cálculo de monto individual de reparto',
                'Entrega de PTU a trabajadores',
                'Generación de constancias de pago',
            ],
        ];

        foreach ($catalogo as $obligacion => $tareas) {
            $obligacionModel = Obligacion::where('nombre', 'like', "%$obligacion%")->first();

            foreach ($tareas as $nombre) {
                TareaCatalogo::updateOrCreate(
                    ['nombre' => $nombre],
                    ['obligacion_id' => $obligacionModel?->id] // ligado si se encuentra la obligación
                );
            }

            $this->command->info("Tareas creadas/actualizadas para: $obligacion");
        }

        // === Tareas genéricas (sin obligación asociada) ===
        $genericas = [
            'Conciliación bancaria',
            'Revisión de CFDIs emitidos y recibidos',
            'Elaboración de reportes contables internos',
            'Archivo de pólizas contables',
            'Envío de información a clientes',
            'Seguimiento a aclaraciones con SAT',
            'Subida de documentos a portal de cliente',
            'Atención a requerimientos fiscales',
            'Revisión de contratos y actas',
            'Digitalización y respaldo de documentos',
        ];

        foreach ($genericas as $nombre) {
            TareaCatalogo::updateOrCreate(
                ['nombre' => $nombre],
                ['obligacion_id' => null] // explícitamente genérica
            );
        }

        $this->command->info('Tareas genéricas creadas correctamente.');
    }
}
