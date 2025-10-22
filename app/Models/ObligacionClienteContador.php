<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class ObligacionClienteContador extends Model
{
    protected $table = 'obligacion_cliente_contador';

 /*    protected $fillable = [
        'cliente_id',
        'obligacion_id',
        'contador_id',
        'fecha_asignacion',
        'fecha_vencimiento',
        'carpeta_drive_id',
        'estatus',
        'fecha_inicio',
        'fecha_termino',
        'fecha_finalizado',
        'archivo_resultado',
        'numero_operacion',
        'archivo_cliente',
        'comentario',
        'revision',
        'obligacion_padre_id',
        'ejercicio',
        'mes',
     
    
    
    ];
 */
protected $fillable = [
    'cliente_id', 
    'obligacion_id', 
    'contador_id', 
    'carpeta_drive_id',
    'fecha_asignacion', 
    'fecha_vencimiento', 
    'mes', 
    'ejercicio',
    'estatus', 
    'fecha_inicio', 
    'fecha_termino', 
    'fecha_finalizado',
    'archivo_resultado',
     'numero_operacion', 
     'archivo_cliente',
    'comentario', 
    'revision', 
    'obligacion_padre_id'
];




    public function scopeListasParaEnviar(Builder $q): Builder
    {
        return $q->where('estatus', 'declaracion_realizada')
            ->whereDoesntExist(function ($sub) {
                $sub->select(DB::raw(1))
                    ->from('tareas_asignadas as t')
                    ->whereColumn('t.obligacion_cliente_contador_id', 'obligacion_cliente_contador.id')
                    ->whereNotIn('t.estatus', ['terminada', 'revisada']);
            });
    }


    public function carpeta()
    {
        return $this->belongsTo(CarpetaDrive::class, 'carpeta_drive_id');
    }


    // % progreso (0..1)
    public function getProgresoTareasAttribute(): float
    {
        $total = (int) $this->tareasAsignadas()->count();
        if ($total === 0) return 1.0;
        $ok = (int) $this->tareasAsignadas()
            ->whereIn('estatus', ['terminada', 'revisada'])
            ->count();
        return $ok / max($total, 1);
    }
    // RELACIONES
    // Accessor de solo lectura (no requiere columna)
    public function getBloqueadaPorTareasAttribute(): bool
    {
        return $this->tieneTareasPendientes();
    }

    // ¿Tiene tareas ligadas pendientes?
    public function tieneTareasPendientes(): bool
    {
        return $this->tareasAsignadas()
            ->whereNotIn('estatus', ['terminada', 'revisada']) // deja solo 'terminada' si no usas 'revisada'
            ->exists();
    }
    public function tareasAsignadas()
    {
        return $this->hasMany(\App\Models\TareaAsignada::class, 'obligacion_cliente_contador_id');
    }


    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function obligacion(): BelongsTo
    {
        return $this->belongsTo(Obligacion::class);
    }


    public function contador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'contador_id');
    }

    public function obligacionPadre(): BelongsTo
    {
        return $this->belongsTo(self::class, 'obligacion_padre_id');
    }

    public function reenvios(): HasMany
    {
        return $this->hasMany(self::class, 'obligacion_padre_id');
    }

    // ACCESOR PARA DURACIÓN (en minutos)
    public function getDuracionMinutosAttribute(): ?int
    {
        if ($this->fecha_inicio && $this->fecha_termino) {
            return Carbon::parse($this->fecha_inicio)->diffInMinutes(Carbon::parse($this->fecha_termino));
        }

        return null;
    }



}
