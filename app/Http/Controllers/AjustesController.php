<?php

namespace App\Http\Controllers;

use App\Models\Turno;
use App\Models\Festivo;

class AjustesController extends Controller
{
    /**
     * Mostrar página de ajustes con turnos y festivos
     */
    public function index()
    {
        $turnos = Turno::ordenados()->get();
        $festivos = Festivo::orderBy('fecha', 'desc')->get();
        $anioActual = (int) date('Y');

        return view('configuracion.ajustes.index', compact('turnos', 'festivos', 'anioActual'));
    }
}
