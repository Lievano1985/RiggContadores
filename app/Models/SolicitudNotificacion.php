<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SolicitudNotificacion extends Model
{
    protected $table = 'solicitud_notificaciones';

    protected $fillable = [
        'user_id',
        'solicitud_id',
        'solicitud_requerimiento_id',
        'tipo',
        'titulo',
        'mensaje',
        'url',
        'datos',
        'leida_at',
    ];

    protected $casts = [
        'datos' => 'array',
        'leida_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function solicitud(): BelongsTo
    {
        return $this->belongsTo(Solicitud::class);
    }

    public function requerimiento(): BelongsTo
    {
        return $this->belongsTo(SolicitudRequerimiento::class, 'solicitud_requerimiento_id');
    }
}
