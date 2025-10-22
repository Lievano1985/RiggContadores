<?php
// Modelo: Cliente.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Cliente extends Model
{
    protected $fillable = [
        'despacho_id',
        'nombre',
        'rfc',
        'nombre_comercial',
        'razon_social',
        'correo',
        'telefono',
        'curp',
        'direccion',
        'codigo_postal',
        'tipo_persona',
        'activo',
        'tiene_trabajadores',
        'inicio_obligaciones',
        'fin_obligaciones',
        'contrato',
        'vigencia',
        'representante_legal',
        'rfc_representante',
        'correo_representante',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'tiene_trabajadores' => 'boolean',
        'inicio_obligaciones' => 'date',
        'fin_obligaciones' => 'date',
        'vigencia' => 'date',
    ];

    public function despacho(): BelongsTo
    {
        return $this->belongsTo(Despacho::class);
    }

    public function archivos(): HasMany
    {
        return $this->hasMany(Archivo::class);
    }

    public function carpetasDrive(): HasMany
    {
        return $this->hasMany(CarpetaDrive::class);
    }

    public function contrasenas(): HasMany
    {
        return $this->hasMany(Contrasena::class);
    }

    public function usuario(): HasOne
    {
        return $this->hasOne(User::class);
    }

    public function regimenes(): BelongsToMany
    {
        return $this->belongsToMany(Regimen::class)->withTimestamps();
    }

    public function actividadesEconomicas(): BelongsToMany
    {
        return $this->belongsToMany(ActividadEconomica::class)->withTimestamps();
    }
    public function obligaciones()
    {
        return $this->belongsToMany(Obligacion::class, 'cliente_obligacion');
    }
    public function todasLasObligaciones()
    {
        return $this->regimenes->flatMap->obligaciones->unique('id');
    }




    public function obligacionesAsignadas()
    {
        return $this->hasMany(ObligacionClienteContador::class);
    }

    public function tareasAsignadas()
    {
        return $this->hasMany(TareaAsignada::class);
    }


    public function getAsignacionesCompletasAttribute(): bool
    {
        $totalObligaciones = $this->obligaciones()->count();
        $obligacionesAsignadas = $this->obligacionesAsignadas()->count();

        if ($totalObligaciones === 0 || $obligacionesAsignadas < $totalObligaciones) {
            return false;
        }

        $obligacionesIds = $this->obligacionesAsignadas()->pluck('id');

        $tareasPorObligacion = \App\Models\TareaCatalogo::whereIn('obligacion_id', $this->obligacionesAsignadas()->pluck('obligacion_id'))
            ->where('activo', true)
            ->get()
            ->groupBy('obligacion_id');

        foreach ($tareasPorObligacion as $obligacionId => $tareas) {
            foreach ($tareas as $tarea) {
                $yaAsignada = $this->tareasAsignadas->contains(function ($asignada) use ($tarea, $obligacionId) {
                    return $asignada->tarea_catalogo_id == $tarea->id &&
                        optional($asignada->obligacionClienteContador)->obligacion_id == $obligacionId;
                });

                if (!$yaAsignada) return false;
            }
        }

        return true;
    }
}
