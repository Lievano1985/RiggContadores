<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use App\Models\ArchivoAdjunto;
use App\Models\CarpetaDrive;

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
        'estado_formulario',
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
        return $this->hasMany(SolicitudHistorial::class, 'solicitud_id')
            ->orderByDesc('created_at')
            ->orderByDesc('id');
    }

    public function archivos(): MorphMany
    {
        return $this->morphMany(ArchivoAdjunto::class, 'archivoable');
    }

    public function resultadoRequerimiento(): HasOne
    {
        return $this->hasOne(SolicitudRequerimiento::class, 'solicitud_id')
            ->where('tipo', 'resultado')
            ->where('estado', '!=', 'cancelado');
    }

    public function getFechaVencimientoAttribute()
    {
        if ($this->usaResultadoComoCierre()) {
            return $this->resultadoRequerimiento?->fecha_limite;
        }

        $requerimientoFormulario = $this->relationLoaded('requerimientos')
            ? $this->requerimientos->first(function ($requerimiento) {
                return $requerimiento->tipo === 'normal'
                    && $requerimiento->estado !== 'cancelado'
                    && str_starts_with((string) $requerimiento->titulo, 'Completar formulario');
            })
            : $this->requerimientos()
                ->where('tipo', 'normal')
                ->where('estado', '!=', 'cancelado')
                ->where('titulo', 'like', 'Completar formulario%')
                ->first();

        return $requerimientoFormulario?->fecha_limite;
    }

    public function getCarpetaDriveIdAttribute()
    {
        if ($this->obligacionClienteContador?->carpeta_drive_id) {
            return $this->obligacionClienteContador->carpeta_drive_id;
        }

        return CarpetaDrive::query()
            ->where('cliente_id', $this->cliente_id)
            ->where('nombre', '3-Generales')
            ->value('id');
    }

    public function getMesAttribute()
    {
        return $this->obligacionClienteContador?->mes;
    }

    public function getEjercicioAttribute()
    {
        return $this->obligacionClienteContador?->ejercicio;
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

    public function getEstadoFormularioLabelAttribute(): string
    {
        return match ($this->estado_formulario) {
            'no_aplica' => 'No aplica',
            'pendiente' => 'Pendiente',
            'respondido' => 'Respondido',
            'validado' => 'Validado',
            default => '-',
        };
    }

    public function getCamposFormularioAttribute(): array
    {
        return $this->plantilla_snapshot['configuracion_formulario']['secciones'][0]['campos'] ?? [];
    }

    public function getResumenFormularioAttribute(): array
    {
        $datos = is_array($this->datos_formulario) ? $this->datos_formulario : [];

        return collect($this->campos_formulario)
            ->map(function (array $campo) use ($datos) {
                $key = $campo['key'] ?? null;

                return [
                    'key' => $key,
                    'label' => $campo['label'] ?? $key ?? 'Campo',
                    'type' => $campo['type'] ?? 'text',
                    'required' => (bool) ($campo['required'] ?? false),
                    'value' => $key ? ($datos[$key] ?? null) : null,
                ];
            })
            ->all();
    }

    public function usaFormularioComoCierre(): bool
    {
        return $this->modo_solicitud === 'definida' && !$this->esCreadaPorCliente();
    }

    public function usaResultadoComoCierre(): bool
    {
        return !$this->usaFormularioComoCierre();
    }

    public function esCreadaPorCliente(): bool
    {
        return (int) ($this->creadoPor?->cliente_id ?? 0) > 0;
    }
}
