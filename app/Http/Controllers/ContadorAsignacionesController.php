<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ContadorAsignacionesController extends Controller
{
    //
    public function index()
    {
        return view('contadores.asignaciones');
    }
}