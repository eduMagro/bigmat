<?php

namespace App\Http\Controllers;

use App\Models\Turno;
use App\Models\Festivo;
use App\Models\Empresa;
use App\Models\Obra;
use App\Models\Categoria;
use App\Models\AgrupacionTurno;
use App\Models\Configuracion;
use Illuminate\Http\Request;

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

        $responsablesVenTodos = Configuracion::getBool('responsables_ven_todos', false);
        $vacacionesSemanasCompletas = Configuracion::getBool('vacaciones_semanas_completas', false);
        $bolsaHorasDesde = \App\Services\ComputoHorasService::fechaInicioBolsa()->format('Y-m-d');
        $bolsaHorasToleranciaMin = \App\Services\ComputoHorasService::toleranciaMinutos();
        $bolsaHorasMinExtra = \App\Services\ComputoHorasService::minutosMinimosExtra();

        return view('configuracion.ajustes.index', compact(
            'turnos',
            'festivos',
            'anioActual',
            'empresas',
            'obras',
            'categorias',
            'agrupaciones',
            'responsablesVenTodos',
            'vacacionesSemanasCompletas',
            'bolsaHorasDesde',
            'bolsaHorasToleranciaMin',
            'bolsaHorasMinExtra'
        ));
    }

    /**
     * Guardar ajustes de visibilidad de usuarios.
     */
    public function actualizarVisibilidad(Request $request)
    {
        $request->validate([
            'responsables_ven_todos' => ['nullable', 'boolean'],
        ]);

        Configuracion::setBool(
            'responsables_ven_todos',
            $request->boolean('responsables_ven_todos')
        );

        return back()->with('success', 'Ajustes de visibilidad actualizados correctamente.');
    }

    /**
     * Guardar ajustes de vacaciones.
     */
    public function actualizarVacaciones(Request $request)
    {
        $request->validate([
            'vacaciones_semanas_completas' => ['nullable', 'boolean'],
        ]);

        Configuracion::setBool(
            'vacaciones_semanas_completas',
            $request->boolean('vacaciones_semanas_completas')
        );

        return back()->with('success', 'Ajustes de vacaciones actualizados correctamente.');
    }

    /**
     * Guardar ajustes de la bolsa de horas.
     */
    public function actualizarBolsaHoras(Request $request)
    {
        $validated = $request->validate([
            'bolsa_horas_desde' => ['required', 'date'],
            'bolsa_horas_tolerancia_min' => ['required', 'integer', 'min:0', 'max:180'],
            'bolsa_horas_min_extra' => ['required', 'integer', 'min:1', 'max:60'],
        ]);

        Configuracion::set('bolsa_horas_desde', $validated['bolsa_horas_desde']);
        Configuracion::set('bolsa_horas_tolerancia_min', (string) $validated['bolsa_horas_tolerancia_min']);
        Configuracion::set('bolsa_horas_min_extra', (string) $validated['bolsa_horas_min_extra']);

        return back()->with('success', 'Ajustes de la bolsa de horas actualizados correctamente.');
    }
}
