<?php

namespace App\Http\Controllers;
use App\Models\Cliente;

use Illuminate\Http\Request;

class ClienteExpedienteController extends Controller
{
 


    public function show(Cliente $cliente)
    {
        $obligacionesCompletadas = \App\Models\ObligacionClienteContador::where('cliente_id', $cliente->id)
            ->where('estatus', '!=', 'finalizado')
            ->doesntExist();
    
        $tareasCompletadas = \App\Models\TareaAsignada::where('cliente_id', $cliente->id)
            ->whereNull('fecha_termino')
            ->doesntExist();
    
        return view('clientes.expediente.show', compact(
            'cliente',
            'obligacionesCompletadas',
            'tareasCompletadas'
        ));
    }
    
}
