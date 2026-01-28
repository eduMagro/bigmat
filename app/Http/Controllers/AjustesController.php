<?php

namespace App\Http\Controllers;

use App\Models\Turno;
use App\Models\Festivo;
use App\Models\Empresa;
use App\Models\Obra;
use App\Models\Categoria;
use App\Models\AgrupacionTurno;

class AjustesController extends Controller
{
    /**
     * Mostrar página de ajustes con turnos, festivos, empresas, obras, categorías y agrupaciones
     */
    public function index()
    {
        $turnos = Turno::ordenados()->get();
        $festivos = Festivo::orderBy('fecha', 'desc')->get();
        $anioActual = (int) date('Y');
        $empresas = Empresa::all();
        $obras = Obra::all();
        $categorias = Categoria::withCount('users')->get();
        $agrupaciones = AgrupacionTurno::ordenadas()
            ->with(['dias.turno'])
            ->get();

        return view('configuracion.ajustes.index', compact(
            'turnos',
            'festivos',
            'anioActual',
            'empresas',
            'obras',
            'categorias',
            'agrupaciones'
        ));
    }
}
