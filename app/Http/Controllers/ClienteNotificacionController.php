<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ClienteNotificacionController extends Controller
{
    //
    public function show()
    {
        
        return view('clientes.notificaciones.show');
    }
}
