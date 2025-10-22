<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Contrasena extends Model
{  

    
 
    protected $fillable = [
        'cliente_id',
        'portal',
        'url',
        'usuario',
        'correo',
        'contrasena',
        'vencimiento',
        'archivo_certificado', // ← ESTE
        'archivo_clave',        // ← Y ESTE
    ];

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }
}
