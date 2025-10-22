<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Despacho extends Model
{
    protected $fillable = [
        'nombre',
        'rfc',
        'correo_contacto',
        'telefono_contacto',
        'drive_folder_id',
        'politica_almacenamiento',
    ];
    

    public function clientes(): HasMany
    {
        return $this->hasMany(Cliente::class);
    }
}
