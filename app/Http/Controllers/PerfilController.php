<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Turno;
use App\Models\AsignacionTurno;
use App\Models\VacacionesSolicitud;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class PerfilController extends Controller
{
    // Cache TTL en segundos
    private const CACHE_TTL = 300; // 5 minutos

    public function show(User $user)
    {
        // 🔒 Si quieres que solo pueda ver su propio perfil:
        if (Auth::id() !== $user->id) {
            return back()->with('error', 'No tienes permiso para ver este perfil.');
        }

        // Recarga el usuario con todas sus relaciones necesarias
        $user = User::with([
            'empresa',
            'categoria',
            'convenio',
            'departamentos',
            'alertasLeidas',
            'asignacionesTurnos.turno',
            'permisosAcceso',
            'incorporacion.documentos', // Añadir eager loading de documentos
        ])->findOrFail($user->id);

        // Turnos disponibles cacheados
        $turnos = $this->getTurnosCached();

        // Resumen de asistencias (optimizado)
        $resumen = $this->getResumenAsistencia($user);

        // Resumen de horas reales fichadas (semana y mes en curso)
        $horasResumen = \App\Services\ComputoHorasService::semanaYMesUsuario($user->id);

        // Solicitudes de vacaciones pendientes del usuario
        $solicitudesVacaciones = VacacionesSolicitud::where('user_id', $user->id)
            ->where('estado', 'pendiente')
            ->orderBy('fecha_inicio')
            ->get();

        // Contar días de vacaciones asignados (usar datos ya cargados)
        $diasVacacionesAsignados = $user->asignacionesTurnos
            ->where('estado', 'vacaciones')
            ->count();

        // Configuración del calendario
        $config = [
            'userId' => $user->id,
            'locale' => 'es',
            'csrfToken' => csrf_token(),
            'routes' => [
                'eventosUrl' => route('users.verEventos-turnos', $user->id),
                'resumenUrl' => route('users.verResumen-asistencia', ['user' => $user->id]),
                'vacacionesStoreUrl' => route('vacaciones.solicitar'),
                'vacacionesDeleteUrl' => url('/vacaciones/solicitud'),
                'storeUrl' => route('asignaciones-turnos.store'),
                'destroyUrl' => route('asignaciones-turnos.destroyByPost'),
                'vacationDataUrl' => route('usuarios.getVacationData', ['user' => $user->id]),
                'misSolicitudesPendientesUrl' => route('vacaciones.misSolicitudesPendientes'),
                'eliminarSolicitudUrl' => url('/vacaciones/solicitud'),
                'eliminarDiasSolicitudUrl' => route('vacaciones.eliminarDiasSolicitud'),
                'fichajesRangoUrl' => route('usuarios.fichajes-rango', ['id' => $user->id]),
                'revisionFichajeStoreUrl' => route('revision-fichaje.store'),
                'eliminarEstadoVacacionesUrl' => route('vacaciones.eliminarEvento'),
            ],
            'enableListMonth' => true,
            'mobileBreakpoint' => 768,
            'permissions' => [
                'canRequestVacations' => true,
                'canEditHours' => false,
                'canAssignShifts' => false,
                'canAssignStates' => false,
            ],
            'turnos' => $turnos->map(fn($t) => ['nombre' => $t->nombre])->values()->toArray(),
            'fechaIncorporacion' => $user->fecha_incorporacion_efectiva ? $user->fecha_incorporacion_efectiva->format('Y-m-d') : null,
            'diasVacacionesAsignados' => $diasVacacionesAsignados,
        ];

        return view('perfil.show', compact(
            'user',
            'turnos',
            'resumen',
            'horasResumen',
            'config',
            'solicitudesVacaciones'
        ));
    }

    /**
     * Obtener turnos cacheados
     */
    private function getTurnosCached()
    {
        return Cache::remember('perfil_turnos', self::CACHE_TTL, function () {
            return Turno::all();
        });
    }

    /**
     * Resumen de asistencia optimizado con una sola query
     */
    private function getResumenAsistencia(User $user): array
    {
        $inicioAño = Carbon::now()->startOfYear();

        $conteos = AsignacionTurno::select('estado', DB::raw('count(*) as total'))
            ->where('user_id', $user->id)
            ->where('fecha', '>=', $inicioAño)
            ->groupBy('estado')
            ->pluck('total', 'estado');

        return [
            'diasVacaciones' => $conteos['vacaciones'] ?? 0,
            'faltasInjustificadas' => $conteos['injustificada'] ?? 0,
            'faltasJustificadas' => $conteos['justificada'] ?? 0,
            'diasBaja' => $conteos['baja'] ?? 0,
        ];
    }

    /**
     * Horas mensuales optimizado - usa cálculo en base de datos cuando es posible
     */
}
