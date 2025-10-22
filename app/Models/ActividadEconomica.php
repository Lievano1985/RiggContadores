<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActividadEconomica extends Model
{
    protected $fillable = [
        'nombre',
        'clave',
        'ponderacion',
    ];
}
