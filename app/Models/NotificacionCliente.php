<?php

/**
 * Autor: Luis Liévano - JL3 Digital
 *
 * Modelo: NotificacionCliente
 * Tabla: notificaciones_clientes
 * Descripción:
 * Representa un envío de notificación al cliente con obligaciones y archivos asociados.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\ArchivoAdjunto;

class NotificacionCliente extends Model
{
    protected $table = 'notificaciones_clientes';

    protected $fillable = [
        'cliente_id',
        'user_id',
        'asunto',
        'mensaje',
        'periodo_mes',
        'periodo_ejercicio',
    ];

    // ============================
    // Relaciones
    // ============================

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function obligaciones()
    {
        return $this->belongsToMany(
            ObligacionClienteContador::class,
            'notificacion_obligacion',
            'notificacion_cliente_id',
            'obligacion_cliente_contador_id'
        );
    }

    public function archivos()
    {
        return $this->belongsToMany(
            ArchivoAdjunto::class,
            'notificacion_archivo',
            'notificacion_cliente_id',
            'archivo_id'
        );
    }
}
