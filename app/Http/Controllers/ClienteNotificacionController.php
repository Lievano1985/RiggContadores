<?php

namespace App\Http\Controllers;

use App\Models\Cliente;

use Illuminate\Http\Request;

class ClienteNotificacionController extends Controller
{
    //
    public function show(Cliente $cliente)

    {
        return view('clientes.notificaciones.notificaciones-show', compact(
            'cliente'
        ));
    }
}
