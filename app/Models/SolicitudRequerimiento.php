<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SolicitudRequerimiento extends Model
{
    protected $table = 'solicitud_requerimientos';

    protected $fillable = [
        'solicitud_id',
        'creado_por_user_id',
        'destinatario_tipo',
        'destinatario_user_id',
        'tipo',
        'titulo',
        'descripcion',
        'estado',
        'fecha_limite',
        'respuesta_texto',
        'respondido_por_user_id',
        'respondido_at',
        'validado_por_user_id',
        'validado_at',
        'comentario_validacion',
    ];

    protected $casts = [
        'fecha_limite' => 'datetime',
        'respondido_at' => 'datetime',
        'validado_at' => 'datetime',
    ];

    public function solicitud(): BelongsTo
    {
        return $this->belongsTo(Solicitud::class);
    }

    public function creadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creado_por_user_id');
    }

    public function destinatario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'destinatario_user_id');
    }

    public function respondidoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'respondido_por_user_id');
    }

    public function validadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validado_por_user_id');
    }

    public function archivos(): MorphMany
    {
        return $this->morphMany(ArchivoAdjunto::class, 'archivoable');
    }

    public function notificaciones(): HasMany
    {
        return $this->hasMany(SolicitudNotificacion::class, 'solicitud_requerimiento_id');
    }

    public function historial(): HasMany
    {
        return $this->hasMany(SolicitudHistorial::class, 'solicitud_requerimiento_id')->latest('created_at');
    }

    public function getClienteAttribute()
    {
        return $this->solicitud?->cliente;
    }

    public function getCarpetaDriveIdAttribute()
    {
        return $this->solicitud?->obligacionClienteContador?->carpeta_drive_id;
    }

    public function getMesAttribute()
    {
        return $this->solicitud?->obligacionClienteContador?->mes;
    }

    public function getEjercicioAttribute()
    {
        return $this->solicitud?->obligacionClienteContador?->ejercicio;
    }

    public function esResultado(): bool
    {
        return $this->tipo === 'resultado';
    }

    public function esRequerimientoFormulario(): bool
    {
        return !$this->esResultado()
            && $this->solicitud?->modo_solicitud === 'definida'
            && str_starts_with((string) $this->titulo, 'Completar formulario');
    }
}
