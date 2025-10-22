<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Regimen extends Model
{
    protected $table = 'regimenes'; // 👈 importante

    protected $fillable = [
        'clave_sat',
        'nombre',
        'tipo_persona',
    ];
}
