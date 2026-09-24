<?php

namespace App\Http\Controllers;

use App\Models\Cliente;

use Illuminate\Http\Request;

class ClienteNotificacionController extends Controller
{
    //
    public function show(Request $request, Cliente $cliente)

    {
        $usuario = $request->user();

        abort_unless(
            $usuario->hasAnyRole(['super_admin', 'admin_despacho', 'supervisor', 'contador']),
            403
        );

        abort_if(
            !$usuario->hasRole('super_admin') && $cliente->despacho_id !== $usuario->despacho_id,
            404
        );

        return view('clientes.notificaciones.notificaciones-show', compact(
            'cliente'
        ));
    }
}
