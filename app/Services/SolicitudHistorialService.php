<?php

namespace App\Services;

use App\Models\Solicitud;
use App\Models\SolicitudHistorial;
use App\Models\SolicitudRequerimiento;

class SolicitudHistorialService
{
    public static function registrar(
        Solicitud $solicitud,
        string $tipo,
        string $titulo,
        ?string $descripcion = null,
        ?int $userId = null,
        ?SolicitudRequerimiento $requerimiento = null,
        ?array $datos = null
    ): void {
        SolicitudHistorial::create([
            'solicitud_id' => $solicitud->id,
            'solicitud_requerimiento_id' => $requerimiento?->id,
            'user_id' => $userId,
            'tipo' => $tipo,
            'titulo' => $titulo,
            'descripcion' => $descripcion,
            'datos' => $datos,
            'created_at' => now(),
        ]);
    }
}
