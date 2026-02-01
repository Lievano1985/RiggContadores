<?php

/**
 * Modelo: ObligacionClienteContador
 * Autor: Luis Liévano - JL3 Digital
 * Descripción: Representa una obligación asignada a un cliente. Incluye control de vigencia (is_activa, fecha_baja, motivo_baja),
 * seguimiento de estatus, trazabilidad de tareas y cálculo de progreso.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;use Illuminate\Database\Eloquent\Relations\MorphMany;
use App\Models\ArchivoAdjunto;
class ObligacionClienteContador extends Model
{
    protected $casts = [
        'id' => 'integer',
        'fecha_finalizado' => 'date',

    ];
    protected $table = 'obligacion_cliente_contador';

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
        'obligacion_padre_id',
        // Nuevos campos administrativos
        'is_activa',
        'fecha_baja',
        'motivo_baja',
    ];

    /* --------------------------------------------------------------------------
     | SCOPES
     |--------------------------------------------------------------------------*/

    /**
     * Filtra solo las obligaciones activas.
     */
    public function scopeActivas(Builder $query): Builder
    {
        return $query->where('is_activa', true);
    }

    /**
     * Filtra las obligaciones dadas de baja.
     */
    public function scopeInactivas(Builder $query): Builder
    {
        return $query->where('is_activa', false);
    }

    /**
     * Filtra las obligaciones listas para enviar al cliente.
     */
    public function scopeListasParaEnviar(Builder $query): Builder
    {
        return $query->where('estatus', 'declaracion_realizada')
            ->whereDoesntExist(function ($sub) {
                $sub->select(DB::raw(1))
                    ->from('tareas_asignadas as t')
                    ->whereColumn('t.obligacion_cliente_contador_id', 'obligacion_cliente_contador.id')
                    ->whereNotIn('t.estatus', ['terminada', 'revisada']);
            });
    }

    /* --------------------------------------------------------------------------
     | RELACIONES
     |--------------------------------------------------------------------------*/

    public function carpeta(): BelongsTo
    {
        return $this->belongsTo(CarpetaDrive::class, 'carpeta_drive_id');
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

    public function tareasAsignadas(): HasMany
    {
        return $this->hasMany(TareaAsignada::class, 'obligacion_cliente_contador_id');
    }

    public function obligacionPadre(): BelongsTo
    {
        return $this->belongsTo(self::class, 'obligacion_padre_id');
    }

    public function reenvios(): HasMany
    {
        return $this->hasMany(self::class, 'obligacion_padre_id');
    }

    /* --------------------------------------------------------------------------
     | MÉTODOS DE APOYO Y ACCESORES
     |--------------------------------------------------------------------------*/

    /**
     * Calcula el progreso de las tareas (valor 0.0 a 1.0).
     */
    public function getProgresoTareasAttribute(): float
    {
        $total = (int) $this->tareasAsignadas()->count();
        if ($total === 0) return 1.0;

        $ok = (int) $this->tareasAsignadas()
            ->whereIn('estatus', ['terminada', 'revisada'])
            ->count();

        return $ok / max($total, 1);
    }

    /**
     * Indica si la obligación está bloqueada por tareas pendientes.
     */
    public function getBloqueadaPorTareasAttribute(): bool
    {
        return $this->tieneTareasPendientes();
    }

    /**
     * Verifica si existen tareas no finalizadas.
     */
    public function tieneTareasPendientes(): bool
    {
        return $this->tareasAsignadas()
            ->whereNotIn('estatus', ['terminada', 'revisada'])
            ->exists();
    }

    /**
     * Calcula la duración en minutos entre inicio y término.
     */
    public function getDuracionMinutosAttribute(): ?int
    {
        if ($this->fecha_inicio && $this->fecha_termino) {
            return Carbon::parse($this->fecha_inicio)
                ->diffInMinutes(Carbon::parse($this->fecha_termino));
        }

        return null;
    }

    /* --------------------------------------------------------------------------
     | GESTIÓN DE BAJAS
     |--------------------------------------------------------------------------*/

    /**
     * Marca la obligación como dada de baja sin eliminarla.
     */
    // App/Models/ObligacionClienteContador.php
    public function darDeBaja(?string $motivo = null): void
    {
        // Evita relaciones cacheadas
        $this->refresh();

        // Marca baja
        $this->update([
            'is_activa'   => false,
            'fecha_baja'  => now(),
            'motivo_baja' => $motivo,
        ]);

        // Cancela tareas una por una (evita problemas de fillable/observers)
        $this->tareasAsignadas()->each(function ($t) {
            $t->update(['estatus' => 'cancelada']);
        });
    }

    public function archivos(): MorphMany
    {
        return $this->morphMany(ArchivoAdjunto::class, 'archivoable');
    }

}
