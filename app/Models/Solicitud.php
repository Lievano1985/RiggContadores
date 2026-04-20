<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Solicitud extends Model
{
    protected $table = 'solicitudes';

    protected $fillable = [
        'cliente_id',
        'obligacion_id',
        'modo_solicitud',
        'tipo_solicitud_id',
        'origen',
        'titulo',
        'descripcion',
        'datos_formulario',
        'plantilla_snapshot',
        'estado',
        'prioridad',
        'responsable_user_id',
        'creado_por_user_id',
        'cerrado_por_user_id',
        'comentario_cierre',
        'cerrada_at',
    ];

    protected $casts = [
        'datos_formulario' => 'array',
        'plantilla_snapshot' => 'array',
        'cerrada_at' => 'datetime',
    ];

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function obligacion(): BelongsTo
    {
        return $this->belongsTo(Obligacion::class);
    }

    public function tipoSolicitud(): BelongsTo
    {
        return $this->belongsTo(SolicitudTipo::class, 'tipo_solicitud_id');
    }

    public function responsable(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsable_user_id');
    }

    public function creadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creado_por_user_id');
    }

    public function cerradoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cerrado_por_user_id');
    }
}
