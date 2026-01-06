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
    protected $signature = 'turnos:generar-anuales';
    protected $description = 'Genera turnos rotativos para trabajadores diurnos y turno fijo para nocturnos durante todo el año excluyendo sábados, domingos y festivos';

    public function handle()
    {
        $usuarios = User::all();

        // Rango: desde mañana hasta fin de año
        $inicio = Carbon::now()->addDay()->startOfDay();
        $fin    = Carbon::now()->endOfYear();

        // ID del turno de vacaciones (para excluirlo de la limpieza)
        $turnoVacacionesId = Turno::where('nombre', 'vacaciones')->value('id');

        // Eliminar asignaciones existentes en el rango que NO sean vacaciones
        // Incluye soft-deleted para evitar conflictos de constraint único
        $eliminadas = AsignacionTurno::withTrashed()
            ->whereDate('fecha', '>=', $inicio->toDateString())
            ->whereDate('fecha', '<=', $fin->toDateString())
            ->where(function ($query) use ($turnoVacacionesId) {
                $query->where('turno_id', '!=', $turnoVacacionesId)
                      ->orWhereNull('turno_id');
            })
            ->forceDelete();

        $this->info("🗑️  Asignaciones previas eliminadas (excepto vacaciones): {$eliminadas}");

        // ✅ Festivos desde BD por rango (no API)
        $festivosArray = $this->getFestivosEntre($inicio, $fin);

        foreach ($usuarios as $user) {
            $this->generarTurnos($user, $inicio, $fin, $festivosArray);
        }

        $this->info("✅ Turnos generados correctamente respetando la rotación individual de cada trabajador.");
    }

    /**
     * Genera turnos para un usuario en el rango indicado, respetando festivos y vacaciones.
     */
    protected function generarTurnos(User $user, Carbon $inicio, Carbon $fin, array $festivosArray): void
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
            // Si usas "diurno" como rotativo mañana/tarde, empezamos por mañana
            $turnoAsignado = $turnoMananaId;
        } else {
            // No asignar si no encaja en ninguno
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
                // Si es viernes y el usuario es diurno, rotamos igual para la semana siguiente
                if ($user->turno === 'diurno' && $esViernes) {
                    $turnoAsignado = ($turnoAsignado === $turnoMananaId) ? $turnoTardeId : $turnoMananaId;
                }
                continue;
            }

            // Registrar/actualizar el turno del día
            AsignacionTurno::updateOrCreate(
                ['user_id' => $user->id, 'fecha' => $fechaStr],
                ['turno_id' => $turnoAsignado]
            );

            // Si es viernes y es diurno, alterna para la próxima semana
            if ($user->turno === 'diurno' && $esViernes) {
                $turnoAsignado = ($turnoAsignado === $turnoMananaId) ? $turnoTardeId : $turnoMananaId;
            }
        }
    }

    /**
     * Devuelve un array de fechas festivas (Y-m-d) entre dos fechas (incluidas) desde la tabla `festivos`.
     */
    protected function getFestivosEntre(Carbon $inicio, Carbon $fin): array
    {
        // Trae por rango, útil si el periodo abarca cambio de año
        return Festivo::whereDate('fecha', '>=', $inicio->toDateString())
            ->whereDate('fecha', '<=', $fin->toDateString())
            ->orderBy('fecha')
            ->pluck('fecha')
            ->map(fn($f) => Carbon::parse($f)->toDateString())
            ->toArray();
    }
}
