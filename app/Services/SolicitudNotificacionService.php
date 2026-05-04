<?php

namespace App\Services;

use App\Models\Solicitud;
use App\Models\SolicitudNotificacion;
use App\Models\SolicitudRequerimiento;
use App\Models\User;

class SolicitudNotificacionService
{
    public static function notificarSolicitudCreada(Solicitud $solicitud): void
    {
        $solicitud->loadMissing('cliente', 'responsable', 'creadoPor');

        $titulo = 'Nueva solicitud asignada';
        $mensaje = 'Se creo la solicitud "' . $solicitud->titulo . '" para ' . ($solicitud->cliente->nombre ?: $solicitud->cliente->razon_social ?: 'cliente') . '.';

        self::crearParaUsuarios(
            self::usuariosSeguimiento($solicitud, [$solicitud->responsable_user_id]),
            [
                'solicitud_id' => $solicitud->id,
                'tipo' => 'solicitud_creada',
                'titulo' => $titulo,
                'mensaje' => $mensaje,
                'url' => self::urlSolicitudesPara($solicitud->responsable),
                'datos' => [
                    'cliente_id' => $solicitud->cliente_id,
                ],
            ]
        );
    }

    public static function notificarRequerimientoCreado(SolicitudRequerimiento $requerimiento): void
    {
        $requerimiento->loadMissing('solicitud.cliente', 'destinatario');

        $destinatarios = [];

        if ($requerimiento->destinatario_tipo === 'interno' && $requerimiento->destinatario_user_id) {
            $destinatarios[] = $requerimiento->destinatario_user_id;
        }

        self::crearParaUsuarios(
            self::usuariosSeguimiento($requerimiento->solicitud, $destinatarios),
            [
                'solicitud_id' => $requerimiento->solicitud_id,
                'solicitud_requerimiento_id' => $requerimiento->id,
                'tipo' => 'requerimiento_creado',
                'titulo' => 'Nuevo requerimiento',
                'mensaje' => 'Se genero el requerimiento "' . $requerimiento->titulo . '" en la solicitud "' . $requerimiento->solicitud->titulo . '".',
                'url' => $requerimiento->destinatario_tipo === 'interno'
                    ? route('mis-requerimientos')
                    : route('solicitudes.index'),
                'datos' => [
                    'destinatario_tipo' => $requerimiento->destinatario_tipo,
                ],
            ]
        );
    }

    public static function notificarRespuestaEnviada(SolicitudRequerimiento $requerimiento): void
    {
        $requerimiento->loadMissing('solicitud.cliente', 'solicitud.creadoPor', 'solicitud.responsable');

        if ($requerimiento->tipo === 'resultado') {
            $destinatarios = self::usuariosSeguimiento($requerimiento->solicitud, [$requerimiento->solicitud->creado_por_user_id]);
            $url = self::urlSolicitudesPara($requerimiento->solicitud->creadoPor);
            $titulo = 'Resultado entregado';
            $mensaje = 'El resultado de la solicitud "' . $requerimiento->solicitud->titulo . '" esta listo para revision.';
            $tipo = 'resultado_entregado';
        } else {
            $destinatarios = self::usuariosSeguimiento($requerimiento->solicitud, [$requerimiento->solicitud->responsable_user_id]);
            $url = self::urlSolicitudesPara($requerimiento->solicitud->responsable);
            $titulo = 'Respuesta recibida';
            $mensaje = 'El requerimiento "' . $requerimiento->titulo . '" fue respondido.';
            $tipo = 'requerimiento_respondido';
        }

        self::crearParaUsuarios($destinatarios, [
            'solicitud_id' => $requerimiento->solicitud_id,
            'solicitud_requerimiento_id' => $requerimiento->id,
            'tipo' => $tipo,
            'titulo' => $titulo,
            'mensaje' => $mensaje,
            'url' => $url,
            'datos' => [
                'requerimiento_tipo' => $requerimiento->tipo,
            ],
        ]);
    }

    public static function notificarRechazo(SolicitudRequerimiento $requerimiento): void
    {
        $requerimiento->loadMissing('solicitud.cliente', 'solicitud.responsable');

        $destinatarios = $requerimiento->tipo === 'resultado'
            ? self::usuariosSeguimiento($requerimiento->solicitud, [$requerimiento->solicitud->responsable_user_id])
            : self::usuariosSeguimiento($requerimiento->solicitud, array_filter([$requerimiento->destinatario_user_id]));

        self::crearParaUsuarios($destinatarios, [
            'solicitud_id' => $requerimiento->solicitud_id,
            'solicitud_requerimiento_id' => $requerimiento->id,
            'tipo' => $requerimiento->tipo === 'resultado' ? 'resultado_rechazado' : 'requerimiento_rechazado',
            'titulo' => $requerimiento->tipo === 'resultado' ? 'Resultado devuelto' : 'Respuesta rechazada',
            'mensaje' => $requerimiento->tipo === 'resultado'
                ? 'El resultado de la solicitud "' . $requerimiento->solicitud->titulo . '" fue devuelto para correccion.'
                : 'Se rechazo la respuesta del requerimiento "' . $requerimiento->titulo . '".',
            'url' => $requerimiento->tipo === 'resultado'
                ? route('solicitudes.asignadas')
                : ($requerimiento->destinatario_tipo === 'interno' ? route('mis-requerimientos') : route('solicitudes.index')),
            'datos' => [
                'comentario' => $requerimiento->comentario_validacion,
            ],
        ]);
    }

    public static function notificarSolicitudCerrada(Solicitud $solicitud): void
    {
        $solicitud->loadMissing('cliente', 'creadoPor');

        self::crearParaUsuarios(
            self::usuariosSeguimiento($solicitud, [$solicitud->creado_por_user_id]),
            [
                'solicitud_id' => $solicitud->id,
                'tipo' => 'solicitud_cerrada',
                'titulo' => 'Solicitud cerrada',
                'mensaje' => 'La solicitud "' . $solicitud->titulo . '" fue cerrada.',
                'url' => self::urlSolicitudesPara($solicitud->creadoPor),
                'datos' => [],
            ]
        );
    }

    private static function crearParaUsuarios(array $userIds, array $payload): void
    {
        $userIds = User::query()
            ->whereIn('id', collect($userIds)->filter()->map(fn ($id) => (int) $id)->unique()->values())
            ->whereNull('cliente_id')
            ->pluck('id');

        if ($userIds->isEmpty()) {
            return;
        }

        $rows = $userIds->map(fn ($userId) => array_merge($payload, [
            'user_id' => $userId,
            'datos' => array_key_exists('datos', $payload) ? ($payload['datos'] ? json_encode($payload['datos'], JSON_UNESCAPED_UNICODE) : null) : null,
            'created_at' => now(),
            'updated_at' => now(),
        ]))->all();

        SolicitudNotificacion::insert($rows);
    }

    private static function usuariosSeguimiento(Solicitud $solicitud, array $baseUserIds = []): array
    {
        $despachoId = $solicitud->cliente?->despacho_id;

        $seguimiento = User::query()
            ->where('despacho_id', $despachoId)
            ->whereNull('cliente_id')
            ->role(['admin_despacho', 'supervisor'])
            ->pluck('id')
            ->all();

        return array_values(array_unique(array_merge($baseUserIds, $seguimiento)));
    }

    private static function urlSolicitudesPara(?User $user): string
    {
        if ($user && $user->hasRole('contador')) {
            return route('solicitudes.asignadas');
        }

        return route('solicitudes.index');
    }
}
