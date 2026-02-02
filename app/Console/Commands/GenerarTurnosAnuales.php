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
        $turnoVacacionesId = $this->buscarTurnoPorNombre('vacaciones');

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

        $procesados = 0;
        $sinPlantilla = 0;

        foreach ($usuarios as $user) {
            if ($user->turno) {
                $this->generarTurnosLegacy($user, $inicio, $fin, $festivosArray);
                $procesados++;
            } else {
                $sinPlantilla++;
            }
        }

        $this->info("Usuarios procesados: {$procesados}");
        if ($sinPlantilla > 0) {
            $this->warn("Usuarios sin turno asignado: {$sinPlantilla}");
        }
        $this->info("Proceso completado correctamente.");

        return 0;
    }

    /**
     * Genera turnos basándose en el campo 'turno' del usuario
     */
    protected function generarTurnosLegacy(User $user, Carbon $inicio, Carbon $fin, array $festivosArray): void
    {
        $turnoMananaId     = $this->buscarTurnoPorNombre('mañana');
        $turnoTardeId      = $this->buscarTurnoPorNombre('tarde');
        $turnoNocheId      = $this->buscarTurnoPorNombre('noche');
        $turnoVacacionesId = $this->buscarTurnoPorNombre('vacaciones');

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

    /**
     * Busca un turno por nombre ignorando mayúsculas/minúsculas y tildes
     */
    protected function buscarTurnoPorNombre(string $nombre): ?int
    {
        $nombreNormalizado = $this->normalizarTexto($nombre);

        return Turno::get()->first(function ($turno) use ($nombreNormalizado) {
            return $this->normalizarTexto($turno->nombre) === $nombreNormalizado;
        })?->id;
    }

    /**
     * Normaliza un texto: minúsculas y sin tildes
     */
    protected function normalizarTexto(string $texto): string
    {
        $texto = mb_strtolower($texto);
        $texto = str_replace(
            ['á', 'é', 'í', 'ó', 'ú', 'ü', 'ñ'],
            ['a', 'e', 'i', 'o', 'u', 'u', 'n'],
            $texto
        );
        return trim($texto);
    }
}
