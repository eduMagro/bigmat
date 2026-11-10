<?php

namespace App\Http\Controllers;

use App\Models\Turno;
use App\Models\Festivo;
use App\Models\Empresa;
use App\Models\Obra;
use App\Models\Categoria;
use App\Models\AgrupacionTurno;
use App\Models\Configuracion;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

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
        $bolsaHorasToleranciaMin = \App\Services\ComputoHorasService::toleranciaMinutos();
        $bolsaHorasMinExtra = \App\Services\ComputoHorasService::minutosMinimosExtra();

        // Fichaje libre de geolocalización: exentos actuales y candidatos a añadir
        $usuariosFichajeLibre = User::where('fichaje_sin_geolocalizacion', true)
            ->orderBy('name')->orderBy('primer_apellido')
            ->get();
        $usuariosCandidatosFichajeLibre = User::where('estado', 'activo')
            ->where('fichaje_sin_geolocalizacion', false)
            ->orderBy('name')->orderBy('primer_apellido')
            ->get();

        return view('configuracion.ajustes.index', compact(
            'usuariosFichajeLibre',
            'usuariosCandidatosFichajeLibre',
            'turnos',
            'festivos',
            'anioActual',
            'empresas',
            'obras',
            'categorias',
            'agrupaciones',
            'responsablesVenTodos',
            'vacacionesSemanasCompletas',
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
            'bolsa_horas_tolerancia_min' => ['required', 'integer', 'min:0', 'max:180'],
            'bolsa_horas_min_extra' => ['required', 'integer', 'min:0', 'max:240'],
        ]);

        Configuracion::set('bolsa_horas_tolerancia_min', (string) $validated['bolsa_horas_tolerancia_min']);
        Configuracion::set('bolsa_horas_min_extra', (string) $validated['bolsa_horas_min_extra']);

        return back()->with('success', 'Ajustes de la bolsa de horas actualizados correctamente.');
    }

    /**
     * Permitir a un usuario fichar sin geolocalización.
     */
    public function agregarFichajeLibre(Request $request)
    {
        $validated = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        $user = User::findOrFail($validated['user_id']);
        $this->cambiarFichajeLibre($user, true);

        return back()->with('success', "{$user->nombre_completo} ya puede fichar sin geolocalización.");
    }

    /**
     * Volver a exigir la geolocalización a un usuario al fichar.
     */
    public function quitarFichajeLibre(User $user)
    {
        $this->cambiarFichajeLibre($user, false);

        return back()->with('success', "{$user->nombre_completo} vuelve a fichar con geolocalización.");
    }

    private function cambiarFichajeLibre(User $user, bool $activo): void
    {
        // Asignación directa: el campo no es fillable a propósito
        $user->fichaje_sin_geolocalizacion = $activo;
        $user->save();

        Log::info('Fichaje sin geolocalización ' . ($activo ? 'activado' : 'desactivado'), [
            'user_id' => $user->id,
            'usuario' => $user->nombre_completo,
            'cambiado_por' => auth()->id(),
        ]);
    }
}
