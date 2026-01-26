<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Turno;
use App\Models\AsignacionTurno;
use App\Models\Festivo;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class GenerarTurnosAnuales extends Command
{
    protected $signature = 'turnos:generar-anuales {--user= : ID de usuario específico para generar turnos}';
    protected $description = 'Genera turnos para trabajadores basándose en sus plantillas de turnos asignadas, excluyendo festivos y vacaciones';

    public function handle()
    {
        $userId = $this->option('user');

        if ($userId) {
            $usuarios = User::where('id', $userId)->get();
            if ($usuarios->isEmpty()) {
                $this->error("Usuario con ID {$userId} no encontrado.");
                return 1;
            }
        } else {
            $usuarios = User::all();
        }

        // Rango: desde mañana hasta fin de año
        $inicio = Carbon::now()->addDay()->startOfDay();
        $fin    = Carbon::now()->endOfYear();

        // ID del turno de vacaciones (para excluirlo de la limpieza)
        $turnoVacacionesId = Turno::where('nombre', 'vacaciones')->value('id');

        // Eliminar asignaciones existentes en el rango que NO sean vacaciones
        // Solo para los usuarios que vamos a procesar
        $queryEliminar = AsignacionTurno::withTrashed()
            ->whereDate('fecha', '>=', $inicio->toDateString())
            ->whereDate('fecha', '<=', $fin->toDateString())
            ->where(function ($query) use ($turnoVacacionesId) {
                $query->where('turno_id', '!=', $turnoVacacionesId)
                      ->orWhereNull('turno_id');
            });

        if ($userId) {
            $queryEliminar->where('user_id', $userId);
        }

        $eliminadas = $queryEliminar->forceDelete();

        $this->info("Asignaciones previas eliminadas (excepto vacaciones): {$eliminadas}");

        // Festivos desde BD por rango
        $festivosArray = $this->getFestivosEntre($inicio, $fin);

        $generados = 0;
        $sinPlantilla = 0;
        $conPlantillaLegacy = 0;

        foreach ($usuarios as $user) {
            // Priorizar agrupación de turnos sobre el campo legacy
            if ($user->agrupacion_turno_id) {
                $resultado = $this->generarTurnosDesdeAgrupacion($user, $inicio, $fin, $festivosArray, $turnoVacacionesId);
                $generados += $resultado;
            } elseif ($user->turno) {
                // Fallback al sistema legacy
                $this->generarTurnosLegacy($user, $inicio, $fin, $festivosArray);
                $conPlantillaLegacy++;
            } else {
                $sinPlantilla++;
            }
        }

        $this->info("Turnos generados desde plantillas: {$generados}");
        if ($conPlantillaLegacy > 0) {
            $this->info("Usuarios procesados con sistema legacy (campo turno): {$conPlantillaLegacy}");
        }
        if ($sinPlantilla > 0) {
            $this->warn("Usuarios sin plantilla asignada: {$sinPlantilla}");
        }
        $this->info("Proceso completado correctamente.");

        return 0;
    }

    /**
     * Genera turnos para un usuario basándose en su agrupación de turnos
     */
    protected function generarTurnosDesdeAgrupacion(User $user, Carbon $inicio, Carbon $fin, array $festivosArray, ?int $turnoVacacionesId): int
    {
        $agrupacion = $user->agrupacionTurno;

        if (!$agrupacion) {
            return 0;
        }

        // Cargar los días de la agrupación para optimizar
        $diasConfig = $agrupacion->dias()->with('turno')->get()->keyBy('dia_semana');

        // Días con turno de vacaciones ya asignados
        $diasVacaciones = AsignacionTurno::where('user_id', $user->id)
            ->where('turno_id', $turnoVacacionesId)
            ->pluck('fecha')
            ->map(fn($f) => Carbon::parse($f)->toDateString())
            ->toArray();

        $asignacionesCreadas = 0;

        // Iterar por cada día del rango
        for ($fecha = $inicio->copy(); $fecha->lte($fin); $fecha->addDay()) {
            $fechaStr = $fecha->toDateString();
            $diaSemana = $fecha->dayOfWeek;

            // Verificar si es festivo o vacaciones
            if (in_array($fechaStr, $festivosArray, true) || in_array($fechaStr, $diasVacaciones, true)) {
                continue;
            }

            // Obtener la configuración del día de la semana
            $diaConfig = $diasConfig->get($diaSemana);

            // Si no hay configuración para este día o no tiene turno, no se trabaja
            if (!$diaConfig || !$diaConfig->turno_id) {
                continue;
            }

            // Registrar/actualizar el turno del día
            AsignacionTurno::updateOrCreate(
                ['user_id' => $user->id, 'fecha' => $fechaStr],
                ['turno_id' => $diaConfig->turno_id]
            );

            $asignacionesCreadas++;
        }

        return $asignacionesCreadas;
    }

    /**
     * Sistema legacy: genera turnos basándose en el campo 'turno' del usuario
     * Se mantiene para compatibilidad hacia atrás
     */
    protected function generarTurnosLegacy(User $user, Carbon $inicio, Carbon $fin, array $festivosArray): void
    {
        $turnoMananaId     = Turno::where('nombre', 'mañana')->value('id');
        $turnoTardeId      = Turno::where('nombre', 'tarde')->value('id');
        $turnoNocheId      = Turno::where('nombre', 'noche')->value('id');
        $turnoVacacionesId = Turno::where('nombre', 'vacaciones')->value('id');

        // Días con turno de vacaciones ya asignados
        $diasVacaciones = AsignacionTurno::where('user_id', $user->id)
            ->where('turno_id', $turnoVacacionesId)
            ->pluck('fecha')
            ->map(fn($f) => Carbon::parse($f)->toDateString())
            ->toArray();

        // Qué turno toca según su modalidad actual
        if ($user->turno === 'nocturno') {
            $turnoAsignado = $turnoNocheId;
        } elseif ($user->turno === 'mañana') {
            $turnoAsignado = $turnoMananaId;
        } elseif ($user->turno === 'diurno') {
            $turnoAsignado = $turnoMananaId;
        } else {
            return;
        }

        // Iterar por cada día del rango
        for ($fecha = $inicio->copy(); $fecha->lte($fin); $fecha->addDay()) {

            $esSabado   = $fecha->dayOfWeek === Carbon::SATURDAY;
            $esDomingo  = $fecha->dayOfWeek === Carbon::SUNDAY;
            $esViernes  = $fecha->dayOfWeek === Carbon::FRIDAY;
            $fechaStr   = $fecha->toDateString();

            // Saltar días no laborables (finde, festivos, vacaciones)
            $esNoLaborable = $esSabado
                || $esDomingo
                || in_array($fechaStr, $festivosArray, true)
                || in_array($fechaStr, $diasVacaciones, true);

            if ($esNoLaborable) {
                if ($user->turno === 'diurno' && $esViernes) {
                    $turnoAsignado = ($turnoAsignado === $turnoMananaId) ? $turnoTardeId : $turnoMananaId;
                }
                continue;
            }

            AsignacionTurno::updateOrCreate(
                ['user_id' => $user->id, 'fecha' => $fechaStr],
                ['turno_id' => $turnoAsignado]
            );

            if ($user->turno === 'diurno' && $esViernes) {
                $turnoAsignado = ($turnoAsignado === $turnoMananaId) ? $turnoTardeId : $turnoMananaId;
            }
        }
    }

    /**
     * Devuelve un array de fechas festivas (Y-m-d) entre dos fechas
     */
    protected function getFestivosEntre(Carbon $inicio, Carbon $fin): array
    {
        return Festivo::whereDate('fecha', '>=', $inicio->toDateString())
            ->whereDate('fecha', '<=', $fin->toDateString())
            ->orderBy('fecha')
            ->pluck('fecha')
            ->map(fn($f) => Carbon::parse($f)->toDateString())
            ->toArray();
    }
}
