<?php

/**
 * Modelo: Obligacion
 * Descripción: Catálogo de obligaciones y cálculo de vencimiento.
 * Notas: mes_inicio fijo (1). Para 'unica' no hay cálculo automático de vencimiento.
 * Autor: Luis Liévano - JL3 Digital
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class Obligacion extends Model
{
    protected $table = 'obligaciones';

    protected $fillable = [
        'nombre',
        'tipo',
        'periodicidad',
        'mes_inicio',
        'desfase_meses',
        'dia_corte',
        'activa',
    ];

    protected $casts = [
        'activa' => 'boolean',
    ];

    protected $attributes = [
        'mes_inicio' => 1,
    ];
    public function tareasCatalogo()
    {
        return $this->hasMany(\App\Models\TareaCatalogo::class, 'obligacion_id');
    }

    public function obligacionesAsignadas(): HasMany
    {
        return $this->hasMany(ObligacionClienteContador::class);
    }
    // En App\Models\Obligacion
    public function clientes()
    {
        // Ajusta el nombre de la tabla pivote si es diferente
        return $this->belongsToMany(\App\Models\Cliente::class, 'cliente_obligacion', 'obligacion_id', 'cliente_id');
    }

    /** Helper: true si la obligación es única/eventual */
    public function esUnica(): bool
    {
        return in_array(strtolower($this->periodicidad), ['unica', 'única', 'eventual'], true);
    }

/**
 * Cálculo de fecha de vencimiento según ejercicio/mes y reglas de ciclo.
 * Para 'unica' retorna null (fecha manual).
 *
 * ✅ Corrección ANUAL:
 * - Ya NO depende de "cierre + desfase" (eso podía empujar hasta +2 años).
 * - Para anual, el vencimiento es un MES FIJO del año siguiente:
 *     - PF: desfase_meses = 3 (marzo)
 *     - PM: desfase_meses = 4 (abril)
 * - dia_corte se toma del catálogo (o 17 por defecto) y se ajusta al último día del mes.
 */
public function calcularFechaVencimiento(int $ejercicio, int $mesPeriodo): ?Carbon
{
    if ($this->esUnica()) {
        return null;
    }

    $periodicidad = strtolower($this->periodicidad ?? 'mensual');

    // ✅ REGLA ESPECIAL PARA ANUAL (PF/PM ya son obligaciones distintas)
    // desfase_meses se interpreta como "mes objetivo" (3=marzo, 4=abril) del año siguiente.
    if ($periodicidad === 'anual') {
        $mesVencimiento = (int) ($this->desfase_meses ?? 3); // PF=3 / PM=4 (según catálogo)
        $añoVencimiento = $ejercicio + 1;

        // Normalizar por si alguien mete valores fuera de 1..12
        if ($mesVencimiento < 1) $mesVencimiento = 1;
        if ($mesVencimiento > 12) $mesVencimiento = 12;

        $fechaBase = Carbon::create($añoVencimiento, $mesVencimiento, 1)->startOfMonth();
        $ultimoDia = $fechaBase->copy()->endOfMonth()->day;

        $diaCorte = (int) ($this->dia_corte ?? 17);
        $dia = min($diaCorte, $ultimoDia);

        return Carbon::create($añoVencimiento, $mesVencimiento, $dia);
    }

    // === LÓGICA ORIGINAL PARA MENSUAL / BIMESTRAL / TRIMESTRAL / ETC. ===
    $duracion = match ($periodicidad) {
        'bimestral'     => 2,
        'trimestral'    => 3,
        'cuatrimestral' => 4,
        'semestral'     => 6,
        default         => 1, // mensual
    };

    // Mes/año de cierre del periodo
    $mesCierre = $mesPeriodo + $duracion - 1;
    $añoCierre = $ejercicio;

    if ($mesCierre > 12) {
        $mesCierre -= 12;
        $añoCierre++;
    }

    // Mes/año de vencimiento (desfase en meses)
    $mesVencimiento = $mesCierre + (int) ($this->desfase_meses ?? 1);
    $añoVencimiento = $añoCierre;

    if ($mesVencimiento > 12) {
        $mesVencimiento -= 12;
        $añoVencimiento++;
    }

    // Día de corte (topado al último día del mes)
    $fechaBase = Carbon::create($añoVencimiento, $mesVencimiento, 1)->startOfMonth();
    $ultimoDia = $fechaBase->copy()->endOfMonth()->day;

    $diaCorte = (int) ($this->dia_corte ?? 17);
    $dia = min($diaCorte, $ultimoDia);

    return Carbon::create($añoVencimiento, $mesVencimiento, $dia);
}

    /**
     * (Opcional) Meses permitidos para mes_inicio según periodicidad.
     * Útil para UI heredada; para 'unica' no aplica.
     */
    public static function mesInicioPermitido(string $periodicidad): array
    {
        $p = strtolower($periodicidad);
        return match ($p) {
            'bimestral'     => [1, 3, 5, 7, 9, 11],
            'trimestral'    => [1, 4, 7, 10],
            'cuatrimestral' => [1, 5, 9],
            'semestral'     => [1, 7],
            'anual'         => [1],
            default         => range(1, 12),
        };
    }
}
