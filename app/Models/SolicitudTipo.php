<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SolicitudTipo extends Model
{
    protected $table = 'solicitud_tipos';

    protected $fillable = [
        'nombre',
        'titulo_sugerido',
        'descripcion_sugerida',
        'prioridad_default',
        'aplica_para',
        'documentos_sugeridos',
        'configuracion_formulario',
        'activo',
    ];

    protected $casts = [
        'documentos_sugeridos' => 'array',
        'configuracion_formulario' => 'array',
        'activo' => 'boolean',
    ];

    public function solicitudes(): HasMany
    {
        return $this->hasMany(Solicitud::class, 'tipo_solicitud_id');
    }
}
