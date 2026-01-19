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

class PerfilController extends Controller
{


    public function show(User $user)
    {
        // 🔒 Si quieres que solo pueda ver su propio perfil:
        if (Auth::id() !== $user->id) {
            return back()->with('error', 'No tienes permiso para ver este perfil.');
        }

        // Recarga el usuario con todas sus relaciones necesarias (solo RRHH)
        $user = User::with([
            'empresa',
            'categoria',
            'convenio',
            'departamentos',
            'alertasLeidas',
            'asignacionesTurnos.turno',
            'permisosAcceso',
            'incorporacion',
        ])->findOrFail($user->id);

        // Turnos disponibles para mostrarlos si hace falta
        $turnos = Turno::all();

        // Resumen de asistencias
        $resumen = $this->getResumenAsistencia($user);

        // Horas trabajadas del mes
        $horasMensuales = $this->getHorasMensuales($user);

        // Solicitudes de vacaciones pendientes del usuario
        $solicitudesVacaciones = VacacionesSolicitud::where('user_id', $user->id)
            ->where('estado', 'pendiente')
            ->orderBy('fecha_inicio')
            ->get();

        // Configuración del calendario (para fichajes y visualización)
        $esOficina = Auth::user()->rol === 'oficina';
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
                // Todos pueden solicitar vacaciones desde su perfil
                'canRequestVacations' => true,
                'canEditHours' => false,
                'canAssignShifts' => false,
                'canAssignStates' => false,
            ],
            'turnos' => $turnos->map(fn($t) => ['nombre' => $t->nombre])->values()->toArray(),
            'fechaIncorporacion' => $user->fecha_incorporacion_efectiva ? $user->fecha_incorporacion_efectiva->format('Y-m-d') : null,
            'diasVacacionesAsignados' => $user->asignacionesTurnos()
                ->where('estado', 'vacaciones')
                ->count(),
        ];

        return view('perfil.show', compact(
            'user',
            'turnos',
            'resumen',
            'horasMensuales',
            'config',
            'solicitudesVacaciones'
        ));
    }
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
    private function getHorasMensuales(User $user): array
    {
        $inicioMes = Carbon::now()->startOfMonth();
        $hoy = Carbon::now()->toDateString();
        $finMes = Carbon::now()->endOfMonth();

        // Todas las asignaciones activas del mes
        $asignacionesMes = $user->asignacionesTurnos()
            ->whereBetween('fecha', [$inicioMes->toDateString(), $finMes->toDateString()])
            ->where('estado', 'activo')
            ->get();

        $horasTrabajadas = 0;
        $diasConErrores = 0;
        $diasHastaHoy = 0;
        $totalAsignacionesMes = $asignacionesMes->count();

        foreach ($asignacionesMes as $asignacion) {
            if ($asignacion->fecha <= $hoy) {
                $diasHastaHoy++;
            }

            $horasDia = 0;
            $tieneError = false;

            // Primera jornada
            $horaEntrada = $asignacion->entrada ? Carbon::parse($asignacion->entrada) : null;
            $horaSalida = $asignacion->salida ? Carbon::parse($asignacion->salida) : null;

            if ($horaEntrada && $horaSalida) {
                $horasDia += $horaSalida->diffInMinutes($horaEntrada) / 60;
            } elseif ($asignacion->fecha < $hoy) {
                $tieneError = true;
            }

            // Segunda jornada (turno partido)
            $horaEntrada2 = $asignacion->entrada2 ? Carbon::parse($asignacion->entrada2) : null;
            $horaSalida2 = $asignacion->salida2 ? Carbon::parse($asignacion->salida2) : null;

            if ($horaEntrada2 && $horaSalida2) {
                $horasDia += $horaSalida2->diffInMinutes($horaEntrada2) / 60;
            } elseif ($horaEntrada2 && !$horaSalida2 && $asignacion->fecha < $hoy) {
                // Tiene entrada2 pero no salida2 y ya pasó el día
                $tieneError = true;
            }

            // Si no tiene horas registradas, usar 8 horas por defecto
            if ($horasDia == 0 && $horaEntrada && $horaSalida) {
                $horasDia = 8;
            }

            $horasTrabajadas += $horasDia;

            if ($tieneError) {
                $diasConErrores++;
            }
        }

        // Horas que debería llevar hasta hoy
        $horasDeberiaLlevar = ($diasHastaHoy) * 8;

        // Horas planificadas en el mes completo
        $horasPlanificadasMes = $totalAsignacionesMes * 8;

        return [
            'horas_trabajadas' => round($horasTrabajadas, 2),
            'horas_deberia_llevar' => $horasDeberiaLlevar,
            'dias_con_errores' => $diasConErrores,
            'horas_planificadas_mes' => $horasPlanificadasMes,
        ];
    }
}
