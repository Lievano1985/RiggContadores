<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Solicitud extends Model
{
    protected $table = 'solicitudes';

    protected $fillable = [
        'cliente_id',
        'obligacion_id',
        'obligacion_cliente_contador_id',
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

    public function obligacionClienteContador(): BelongsTo
    {
        return $this->belongsTo(ObligacionClienteContador::class, 'obligacion_cliente_contador_id');
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

    public function requerimientos(): HasMany
    {
        return $this->hasMany(SolicitudRequerimiento::class, 'solicitud_id');
    }

    public function notificaciones(): HasMany
    {
        return $this->hasMany(SolicitudNotificacion::class, 'solicitud_id');
    }

    public function historial(): HasMany
    {
        return $this->hasMany(SolicitudHistorial::class, 'solicitud_id')->latest('created_at');
    }

    public function resultadoRequerimiento(): HasOne
    {
        return $this->hasOne(SolicitudRequerimiento::class, 'solicitud_id')
            ->where('tipo', 'resultado');
    }

    public function getFechaVencimientoAttribute()
    {
        return $this->resultadoRequerimiento?->fecha_limite;
    }

    public function getObligacionEtiquetaAttribute(): string
    {
        if ($this->obligacionClienteContador?->obligacion) {
            $mes = $this->obligacionClienteContador->mes;
            $ejercicio = $this->obligacionClienteContador->ejercicio;
            $periodo = $mes && $ejercicio
                ? ucfirst(\Carbon\Carbon::create()->month($mes)->translatedFormat('F')) . ' ' . $ejercicio
                : null;

            return trim($this->obligacionClienteContador->obligacion->nombre . ($periodo ? ' - ' . $periodo : ''));
        }

        return $this->obligacion?->nombre ?? '-';
    }
}
