<?php

namespace App\Services;

use App\Models\AsignacionTurno;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Cómputo de horas trabajadas a partir de las asignaciones de turno.
 *
 * Las horas se calculan con los fichajes reales: (salida - entrada) + (salida2 - entrada2),
 * gestionando turnos partidos y turnos que cruzan medianoche. Solo se contabilizan las
 * asignaciones con estado 'activo'. Los días pasados sin fichaje cuentan 0 horas y se
 * marcan como "incompletos".
 */
class ComputoHorasService
{
    /**
     * Horas reales fichadas de una asignación (suma de ambos tramos).
     */
    public static function horasDeAsignacion(AsignacionTurno $a): float
    {
        $horas = self::horasTramo($a->entrada, $a->salida)
            + self::horasTramo($a->entrada2, $a->salida2);

        return round($horas, 2);
    }

    /**
     * Horas de un tramo entrada/salida. Si la salida es anterior o igual a la entrada
     * se asume que el tramo cruza medianoche y se añade un día.
     */
    private static function horasTramo($entrada, $salida): float
    {
        if (!$entrada || !$salida) {
            return 0.0;
        }

        $e = Carbon::parse($entrada);
        $s = Carbon::parse($salida);

        if ($s->lessThanOrEqualTo($e)) {
            $s = $s->copy()->addDay();
        }

        return $e->diffInMinutes($s) / 60;
    }

    /**
     * Resumen de horas de una colección de asignaciones ya cargadas.
     *
     * @return array{horas: float, dias_trabajados: int, dias_incompletos: int}
     */
    public static function resumenDesdeColeccion(Collection $asignaciones): array
    {
        $hoy = Carbon::today()->toDateString();
        $horas = 0.0;
        $diasTrabajados = 0;
        $diasIncompletos = 0;

        foreach ($asignaciones as $a) {
            $h = self::horasDeAsignacion($a);
            $fecha = $a->fecha instanceof Carbon ? $a->fecha->toDateString() : (string) $a->fecha;

            if ($h > 0) {
                $horas += $h;
                $diasTrabajados++;
            } elseif ($fecha < $hoy) {
                $diasIncompletos++;
            }
        }

        return [
            'horas' => round($horas, 2),
            'dias_trabajados' => $diasTrabajados,
            'dias_incompletos' => $diasIncompletos,
        ];
    }

    /**
     * Resumen de horas de un usuario en un rango de fechas.
     *
     * @return array{horas: float, dias_trabajados: int, dias_incompletos: int}
     */
    public static function resumenRango(int $userId, $inicio, $fin): array
    {
        $asignaciones = AsignacionTurno::where('user_id', $userId)
            ->whereBetween('fecha', [
                Carbon::parse($inicio)->toDateString(),
                Carbon::parse($fin)->toDateString(),
            ])
            ->where('estado', 'activo')
            ->select('fecha', 'entrada', 'salida', 'entrada2', 'salida2')
            ->get();

        return self::resumenDesdeColeccion($asignaciones);
    }

    /**
     * Resumen de horas de la semana y del mes actuales para un usuario.
     *
     * @return array{semana: array, mes: array}
     */
    public static function semanaYMesUsuario(int $userId): array
    {
        $resumen = self::semanaYMes([$userId]);

        return $resumen[$userId] ?? self::resumenVacioSemanaYMes();
    }

    /**
     * Resumen de horas de la semana y del mes actuales para varios usuarios (en lote).
     * Una única consulta para todos los usuarios y rangos.
     *
     * @param  array<int>|Collection  $userIds
     * @return array<int, array{semana: array, mes: array}>
     */
    public static function semanaYMes($userIds): array
    {
        $userIds = collect($userIds)->all();

        if (empty($userIds)) {
            return [];
        }

        $inicioMes = Carbon::now()->startOfMonth();
        $finMes = Carbon::now()->endOfMonth();
        $inicioSemana = Carbon::now()->startOfWeek();
        $finSemana = Carbon::now()->endOfWeek();

        // Rango envolvente que cubre semana y mes (la semana puede cruzar el mes).
        $min = $inicioSemana->lt($inicioMes) ? $inicioSemana : $inicioMes;
        $max = $finSemana->gt($finMes) ? $finSemana : $finMes;

        $porUsuario = AsignacionTurno::whereIn('user_id', $userIds)
            ->whereBetween('fecha', [$min->toDateString(), $max->toDateString()])
            ->where('estado', 'activo')
            ->select('user_id', 'fecha', 'entrada', 'salida', 'entrada2', 'salida2')
            ->get()
            ->groupBy('user_id');

        $inicioSemanaStr = $inicioSemana->toDateString();
        $finSemanaStr = $finSemana->toDateString();
        $inicioMesStr = $inicioMes->toDateString();
        $finMesStr = $finMes->toDateString();

        $resultado = [];

        foreach ($userIds as $userId) {
            $asignaciones = $porUsuario->get($userId, collect());

            $semana = $asignaciones->filter(function ($a) use ($inicioSemanaStr, $finSemanaStr) {
                $f = $a->fecha instanceof Carbon ? $a->fecha->toDateString() : (string) $a->fecha;
                return $f >= $inicioSemanaStr && $f <= $finSemanaStr;
            });

            $mes = $asignaciones->filter(function ($a) use ($inicioMesStr, $finMesStr) {
                $f = $a->fecha instanceof Carbon ? $a->fecha->toDateString() : (string) $a->fecha;
                return $f >= $inicioMesStr && $f <= $finMesStr;
            });

            $resultado[$userId] = [
                'semana' => self::resumenDesdeColeccion($semana->values()),
                'mes' => self::resumenDesdeColeccion($mes->values()),
            ];
        }

        return $resultado;
    }

    /**
     * Resumen mensual por trabajador para un mes/año concretos (en lote).
     * Incluye horas trabajadas, días y conteo de ausencias por estado.
     *
     * @param  array<int>|Collection  $userIds
     * @return array<int, array>
     */
    public static function resumenMensual($userIds, int $anio, int $mes): array
    {
        $userIds = collect($userIds)->all();

        if (empty($userIds)) {
            return [];
        }

        $inicio = Carbon::create($anio, $mes, 1)->startOfMonth();
        $fin = $inicio->copy()->endOfMonth();

        $porUsuario = AsignacionTurno::whereIn('user_id', $userIds)
            ->whereBetween('fecha', [$inicio->toDateString(), $fin->toDateString()])
            ->select('user_id', 'fecha', 'estado', 'entrada', 'salida', 'entrada2', 'salida2')
            ->get()
            ->groupBy('user_id');

        $resultado = [];

        foreach ($userIds as $userId) {
            $asignaciones = $porUsuario->get($userId, collect());

            $activas = $asignaciones->where('estado', 'activo')->values();
            $resumen = self::resumenDesdeColeccion($activas);

            $resultado[$userId] = array_merge($resumen, [
                'vacaciones'    => $asignaciones->where('estado', 'vacaciones')->count(),
                'baja'          => $asignaciones->where('estado', 'baja')->count(),
                'justificada'   => $asignaciones->where('estado', 'justificada')->count(),
                'injustificada' => $asignaciones->where('estado', 'injustificada')->count(),
                'curso'         => $asignaciones->where('estado', 'curso')->count(),
            ]);
        }

        return $resultado;
    }

    /**
     * @return array{semana: array, mes: array}
     */
    private static function resumenVacioSemanaYMes(): array
    {
        $vacio = ['horas' => 0.0, 'dias_trabajados' => 0, 'dias_incompletos' => 0];

        return ['semana' => $vacio, 'mes' => $vacio];
    }
}
