<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SolicitudHistorial extends Model
{
    protected $table = 'solicitud_historial';

    public $timestamps = false;

    const UPDATED_AT = null;

    protected $fillable = [
        'solicitud_id',
        'solicitud_requerimiento_id',
        'user_id',
        'tipo',
        'titulo',
        'descripcion',
        'datos',
        'created_at',
    ];

    protected $casts = [
        'datos' => 'array',
        'created_at' => 'datetime',
    ];

    public function solicitud(): BelongsTo
    {
        return $this->belongsTo(Solicitud::class);
    }

    public function requerimiento(): BelongsTo
    {
        return $this->belongsTo(SolicitudRequerimiento::class, 'solicitud_requerimiento_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
