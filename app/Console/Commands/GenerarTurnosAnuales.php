<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Turno;
use App\Models\AsignacionTurno;
use App\Models\AgrupacionTurno;
use App\Models\Festivo;
use Carbon\Carbon;

class GenerarTurnosAnuales extends Command
{
    protected $signature = 'turnos:generar-anuales
                            {--user= : ID de usuario específico (opcional)}
                            {--desde= : Fecha inicio (YYYY-MM-DD), por defecto mañana}
                            {--hasta= : Fecha fin (YYYY-MM-DD), por defecto fin de año}';

    protected $description = 'Genera turnos para todos los trabajadores según su agrupación asignada (campo turno: TURNO 1, TURNO 2, etc.)';

    protected $agrupacionesCache = [];

    public function handle()
    {
        // Precargar agrupaciones
        $this->precargarAgrupaciones();

        if (empty($this->agrupacionesCache)) {
            $this->error('No hay agrupaciones de turnos. Ejecuta: php artisan db:seed --class=TurnosYAgrupacionesSeeder');
            return 1;
        }

        // Obtener usuarios
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

        // Rango de fechas
        $inicio = $this->option('desde')
            ? Carbon::parse($this->option('desde'))->startOfDay()
            : Carbon::now()->addDay()->startOfDay();

        $fin = $this->option('hasta')
            ? Carbon::parse($this->option('hasta'))->endOfDay()
            : Carbon::now()->endOfYear();

        $this->info("Rango: {$inicio->toDateString()} a {$fin->toDateString()}");
        $this->info("Usuarios a procesar: {$usuarios->count()}");

        // ID del turno de vacaciones
        $turnoVacacionesId = $this->buscarTurnoPorNombre('vacaciones');

        // Festivos
        $festivosArray = $this->getFestivosEntre($inicio, $fin);
        $this->info("Festivos en el rango: " . count($festivosArray));

        $procesados = 0;
        $sinAgrupacion = 0;
        $asignacionesCreadas = 0;

        foreach ($usuarios as $user) {
            // Obtener agrupación del usuario según su campo 'turno'
            $agrupacion = $this->obtenerAgrupacionUsuario($user);

            if (!$agrupacion) {
                $sinAgrupacion++;
                continue;
            }

            // Eliminar asignaciones previas de este usuario (excepto vacaciones)
            $this->eliminarAsignacionesPrevias($user->id, $inicio, $fin, $turnoVacacionesId);

            // Generar turnos
            $diasAgrupacion = $agrupacion->dias()->with('turno')->get()->keyBy('dia_semana');
            $creadas = $this->generarTurnosParaUsuario($user, $diasAgrupacion, $inicio, $fin, $festivosArray, $turnoVacacionesId);

            $asignacionesCreadas += $creadas;
            $procesados++;
        }

        $this->info("Usuarios procesados: {$procesados}");
        if ($sinAgrupacion > 0) {
            $this->warn("Usuarios sin agrupación válida (campo turno vacío o no coincide): {$sinAgrupacion}");
        }
        $this->info("Asignaciones creadas: {$asignacionesCreadas}");
        $this->info("Proceso completado.");

        return 0;
    }

    protected function eliminarAsignacionesPrevias(int $userId, Carbon $inicio, Carbon $fin, ?int $turnoVacacionesId): void
    {
        $query = AsignacionTurno::withTrashed()
            ->where('user_id', $userId)
            ->whereDate('fecha', '>=', $inicio->toDateString())
            ->whereDate('fecha', '<=', $fin->toDateString());

        if ($turnoVacacionesId) {
            $query->where(function ($q) use ($turnoVacacionesId) {
                $q->where('turno_id', '!=', $turnoVacacionesId)
                  ->orWhereNull('turno_id');
            });
        }

        $query->forceDelete();
    }

    protected function obtenerAgrupacionUsuario(User $user): ?AgrupacionTurno
    {
        if (!$user->turno) {
            return null;
        }

        $turnoUsuario = $this->normalizarTexto($user->turno);

        foreach ($this->agrupacionesCache as $agrupacion) {
            $nombreAgrupacion = $this->normalizarTexto($agrupacion->nombre);

            // Coincidencia exacta
            if ($nombreAgrupacion === $turnoUsuario) {
                return $agrupacion;
            }

            // Coincidencia por número: "turno 1" con "1", "TURNO 2" con "2", etc.
            if (preg_match('/(\d+)/', $turnoUsuario, $matchUser) &&
                preg_match('/(\d+)/', $nombreAgrupacion, $matchAgrupacion)) {
                if ($matchUser[1] === $matchAgrupacion[1]) {
                    return $agrupacion;
                }
            }
        }

        return null;
    }

    protected function precargarAgrupaciones(): void
    {
        $this->agrupacionesCache = AgrupacionTurno::where('activo', true)->orderBy('orden')->get()->all();
    }

    protected function generarTurnosParaUsuario(
        User $user,
        $diasAgrupacion,
        Carbon $inicio,
        Carbon $fin,
        array $festivosArray,
        ?int $turnoVacacionesId
    ): int {
        $diasVacaciones = [];
        if ($turnoVacacionesId) {
            $diasVacaciones = AsignacionTurno::where('user_id', $user->id)
                ->where('turno_id', $turnoVacacionesId)
                ->pluck('fecha')
                ->map(fn($f) => Carbon::parse($f)->toDateString())
                ->toArray();
        }

        $asignacionesCreadas = 0;

        for ($fecha = $inicio->copy(); $fecha->lte($fin); $fecha->addDay()) {
            $fechaStr = $fecha->toDateString();
            $diaSemana = $fecha->dayOfWeek;

            if (in_array($fechaStr, $festivosArray, true) || in_array($fechaStr, $diasVacaciones, true)) {
                continue;
            }

            $diaAgrupacion = $diasAgrupacion->get($diaSemana);

            if (!$diaAgrupacion || !$diaAgrupacion->turno_id) {
                continue;
            }

            AsignacionTurno::updateOrCreate(
                ['user_id' => $user->id, 'fecha' => $fechaStr],
                ['turno_id' => $diaAgrupacion->turno_id]
            );

            $asignacionesCreadas++;
        }

        return $asignacionesCreadas;
    }

    protected function getFestivosEntre(Carbon $inicio, Carbon $fin): array
    {
        return Festivo::whereDate('fecha', '>=', $inicio->toDateString())
            ->whereDate('fecha', '<=', $fin->toDateString())
            ->pluck('fecha')
            ->map(fn($f) => Carbon::parse($f)->toDateString())
            ->toArray();
    }

    protected function buscarTurnoPorNombre(string $nombre): ?int
    {
        $nombreNormalizado = $this->normalizarTexto($nombre);

        return Turno::get()->first(function ($turno) use ($nombreNormalizado) {
            return $this->normalizarTexto($turno->nombre) === $nombreNormalizado;
        })?->id;
    }

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
