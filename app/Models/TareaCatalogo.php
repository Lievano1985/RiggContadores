<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TareaCatalogo extends Model
{
    protected $table = 'tareas_catalogo';

    protected $fillable = [
        'nombre',
        'descripcion',
        'obligacion_id',
        'activo',
    ];

    public function tareasAsignadas(): HasMany
    {
        return $this->hasMany(TareaAsignada::class);
    }

    public function obligacion(): BelongsTo
    {
        return $this->belongsTo(Obligacion::class);
    }
    
}
