<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClienteTareaConfiguracion extends Model
{
    protected $table = 'cliente_tarea_configuraciones';

    protected $fillable = [
        'cliente_id',
        'tarea_catalogo_id',
        'activa',
    ];

    protected $casts = [
        'activa' => 'boolean',
    ];

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function tareaCatalogo(): BelongsTo
    {
        return $this->belongsTo(TareaCatalogo::class);
    }
}
