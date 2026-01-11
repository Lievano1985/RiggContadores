<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;
use App\Models\ArchivoAdjunto;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class TareaAsignada extends Model
{
    protected $table = 'tareas_asignadas';
    protected $casts = [
        'obligacion_cliente_contador_id' => 'integer',
    ];
    protected $fillable = [
        'cliente_id',
        'tarea_catalogo_id',
        'contador_id',
        'obligacion_cliente_contador_id',
        'carpeta_drive_id',
        'fecha_asignacion',
        'fecha_limite',
        'fecha_inicio',
        'fecha_termino',
        'ejercicio',
        'mes',
        'estatus',
        'archivo',
        'archivo_drive_url',
        'comentario',
        'tiempo_estimado',
    ];

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function tareaCatalogo()
    {
        return $this->belongsTo(TareaCatalogo::class, 'tarea_catalogo_id');
    }


    public function contador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'contador_id');
    }

    /*    public function obligacion(): BelongsTo
    {
        return $this->belongsTo(ObligacionClienteContador::class, 'obligacion_cliente_contador_id');
    } */
    // app/Models/TareaAsignada.php
    public function obligacionClienteContador()
    {
        return $this->belongsTo(ObligacionClienteContador::class, 'obligacion_cliente_contador_id');
    }


    public function carpetaDrive(): BelongsTo
    {
        return $this->belongsTo(CarpetaDrive::class);
    }

    public function getDuracionMinutosAttribute(): ?int
    {
        if ($this->fecha_inicio && $this->fecha_termino) {
            return Carbon::parse($this->fecha_inicio)->diffInMinutes(Carbon::parse($this->fecha_termino));
        }
        return null;
    }

    public function archivos(): MorphMany
    {
        return $this->morphMany(ArchivoAdjunto::class, 'archivoable');
    }
}
