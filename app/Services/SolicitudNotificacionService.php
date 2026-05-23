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
        $solicitud->loadMissing('cliente.usuario', 'responsable', 'creadoPor', 'requerimientos');

        $titulo = 'Nueva solicitud asignada';
        $mensaje = 'Se creo la solicitud "' . $solicitud->titulo . '" para ' . ($solicitud->cliente->nombre ?: $solicitud->cliente->razon_social ?: 'cliente') . '.';

        self::crearParaUsuarios(
            self::usuariosSeguimiento($solicitud, self::debeNotificarResponsablePorSolicitud($solicitud) ? [$solicitud->responsable_user_id] : []),
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

        $tieneRequerimientoActivoParaCliente = $solicitud->requerimientos
            ->contains(fn ($requerimiento) => $requerimiento->destinatario_tipo === 'cliente' && $requerimiento->estado !== 'cancelado');

        $debeNotificarClienteDirecto = $solicitud->origen === 'cliente'
            && !$solicitud->creadoPor?->hasRole('cliente')
            && !$tieneRequerimientoActivoParaCliente
            && !($solicitud->modo_solicitud === 'definida' && $solicitud->estado_formulario === 'pendiente');

        if ($debeNotificarClienteDirecto) {
            $clienteUserId = $solicitud->cliente?->usuario?->id;

            if ($clienteUserId) {
                self::crearParaUsuarios(
                    [$clienteUserId],
                    [
                        'solicitud_id' => $solicitud->id,
                        'tipo' => 'solicitud_recibida',
                        'titulo' => 'Nueva solicitud',
                        'mensaje' => 'Se genero una nueva solicitud para ti: "' . $solicitud->titulo . '".',
                        'url' => route('Clientes.portal'),
                        'datos' => [
                            'cliente_id' => $solicitud->cliente_id,
                        ],
                    ]
                );
            }
        }

    }

    public static function notificarRequerimientoCreado(SolicitudRequerimiento $requerimiento): void
    {
        $requerimiento->loadMissing('solicitud.cliente.usuario', 'destinatario');

        self::crearParaUsuarios(
            self::destinatariosDelRequerimiento($requerimiento),
            [
                'solicitud_id' => $requerimiento->solicitud_id,
                'solicitud_requerimiento_id' => $requerimiento->id,
                'tipo' => 'requerimiento_creado',
                'titulo' => 'Nuevo requerimiento',
                'mensaje' => 'Se genero el requerimiento "' . $requerimiento->titulo . '" en la solicitud "' . $requerimiento->solicitud->titulo . '".',
                'url' => route('mis-requerimientos'),
                'datos' => [
                    'destinatario_tipo' => $requerimiento->destinatario_tipo,
                ],
            ]
        );
    }

    public static function notificarRespuestaEnviada(SolicitudRequerimiento $requerimiento): void
    {
        $requerimiento->loadMissing('solicitud.cliente', 'solicitud.creadoPor', 'solicitud.responsable', 'creadoPor');

        if ($requerimiento->tipo === 'resultado') {
            $destinatarios = self::usuariosSeguimiento($requerimiento->solicitud, [$requerimiento->solicitud->creado_por_user_id]);
            $url = self::urlSolicitudesPara($requerimiento->solicitud->creadoPor);
            $titulo = 'Respuesta recibida';
            $mensaje = 'El requerimiento "' . $requerimiento->titulo . '" fue respondido.';
            $tipo = 'resultado_entregado';
        } else {
            $destinatarios = self::usuariosSeguimiento($requerimiento->solicitud, [$requerimiento->creado_por_user_id]);
            $url = self::urlSolicitudesPara($requerimiento->creadoPor);
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
        $requerimiento->loadMissing('solicitud.cliente.usuario', 'solicitud.responsable');

        $destinatarios = $requerimiento->tipo === 'resultado'
            ? self::usuariosSeguimiento($requerimiento->solicitud, [$requerimiento->solicitud->responsable_user_id])
            : self::usuariosSeguimiento($requerimiento->solicitud, self::destinatariosDelRequerimiento($requerimiento));

        self::crearParaUsuarios($destinatarios, [
            'solicitud_id' => $requerimiento->solicitud_id,
            'solicitud_requerimiento_id' => $requerimiento->id,
            'tipo' => $requerimiento->tipo === 'resultado' ? 'resultado_rechazado' : 'requerimiento_rechazado',
            'titulo' => $requerimiento->tipo === 'resultado' ? 'Resultado devuelto' : 'Respuesta rechazada',
            'mensaje' => $requerimiento->tipo === 'resultado'
                ? 'El resultado de la solicitud "' . $requerimiento->solicitud->titulo . '" fue devuelto para correccion.'
                : 'Se rechazo la respuesta del requerimiento "' . $requerimiento->titulo . '".',
            'url' => route('mis-requerimientos'),
            'datos' => [
                'comentario' => $requerimiento->comentario_validacion,
            ],
        ]);
    }

    public static function notificarSolicitudCerrada(Solicitud $solicitud): void
    {
        $solicitud->loadMissing('cliente', 'creadoPor', 'responsable');

        $destinatariosSeguimiento = self::usuariosSeguimiento($solicitud, [$solicitud->creado_por_user_id]);

        self::crearParaUsuarios(
            $destinatariosSeguimiento,
            [
                'solicitud_id' => $solicitud->id,
                'tipo' => 'solicitud_cerrada',
                'titulo' => 'Solicitud cerrada',
                'mensaje' => 'La solicitud "' . $solicitud->titulo . '" fue cerrada.',
                'url' => self::urlSolicitudesPara($solicitud->creadoPor),
                'datos' => [],
            ]
        );

        if (
            $solicitud->responsable_user_id
            && !in_array((int) $solicitud->responsable_user_id, array_map('intval', $destinatariosSeguimiento), true)
        ) {
            self::crearParaUsuarios(
                [$solicitud->responsable_user_id],
                [
                    'solicitud_id' => $solicitud->id,
                    'tipo' => 'solicitud_cerrada',
                    'titulo' => 'Solicitud cerrada',
                    'mensaje' => 'La solicitud "' . $solicitud->titulo . '" fue cerrada.',
                    'url' => self::urlSolicitudesPara($solicitud->responsable),
                    'datos' => [],
                ]
            );
        }
    }

    private static function crearParaUsuarios(array $userIds, array $payload): void
    {
        $actorId = auth()->id();

        $userIds = User::query()
            ->whereIn('id', collect($userIds)
                ->filter()
                ->map(fn ($id) => (int) $id)
                ->reject(fn ($id) => $actorId && $id === (int) $actorId)
                ->unique()
                ->values())
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

    private static function destinatariosDelRequerimiento(SolicitudRequerimiento $requerimiento): array
    {
        if ($requerimiento->destinatario_tipo === 'interno' && $requerimiento->destinatario_user_id) {
            return [(int) $requerimiento->destinatario_user_id];
        }

        if ($requerimiento->destinatario_tipo === 'cliente') {
            $clienteUserId = $requerimiento->solicitud?->cliente?->usuario?->id;

            return $clienteUserId ? [(int) $clienteUserId] : [];
        }

        return [];
    }

    private static function urlSolicitudesPara(?User $user): string
    {
        if ($user && $user->hasRole('cliente')) {
            return route('Clientes.portal');
        }

        if ($user && $user->hasRole('contador')) {
            return route('solicitudes.asignadas');
        }

        return route('solicitudes.index');
    }

    private static function debeNotificarResponsablePorSolicitud(Solicitud $solicitud): bool
    {
        return !$solicitud->requerimientos
            ->contains(fn ($requerimiento) => $requerimiento->estado !== 'cancelado');
    }
}
