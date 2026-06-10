<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class RegularizacionFiscal extends Model
{
    protected $table = 'regularizaciones_fiscales';

    protected $fillable = [
        'cliente_id',
        'user_id',
        'anio',
        'mes',
        'generadas',
        'ya_existian',
        'omitidas',
        'obligaciones_solicitadas',
        'resumen',
    ];

    protected $casts = [
        'anio' => 'integer',
        'mes' => 'integer',
        'generadas' => 'integer',
        'ya_existian' => 'integer',
        'omitidas' => 'integer',
        'obligaciones_solicitadas' => 'array',
        'resumen' => 'array',
    ];

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function obligaciones(): BelongsToMany
    {
        return $this->belongsToMany(
            ObligacionClienteContador::class,
            'regularizacion_obligacion',
            'regularizacion_fiscal_id',
            'obligacion_cliente_contador_id'
        )->withTimestamps();
    }
}
