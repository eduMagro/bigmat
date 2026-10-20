<?php

namespace App\Services;

use App\Models\AsignacionTurno;
use App\Models\CompensacionHoras;
use App\Models\Configuracion;
use App\Models\Turno;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Cómputo de horas trabajadas a partir de las asignaciones de turno.
 *
 * Las horas se calculan con los fichajes reales: (salida - entrada) + (salida2 - entrada2),
 * gestionando turnos partidos y turnos que cruzan medianoche. Solo se contabilizan las
 * asignaciones con estado 'activo'. Los días pasados sin fichaje cuentan 0 horas y se
 * marcan como "incompletos".
 *
 * Bolsa de horas: por cada día fichado se compara lo fichado con el horario del turno
 * asignado y la diferencia se acumula en una bolsa por trabajador desde la fecha configurada
 * en 'bolsa_horas_desde'.
 *
 * El saldo se cuenta en horas enteras: una hora cuenta cuando se han completado al menos
 * 'bolsa_horas_min_extra' minutos de ella (50 por defecto). Fracciones menores no suman ni restan.
 *
 * - Horas extra (+): solo se generan si el trabajador entró a su hora (retraso máximo
 *   'bolsa_horas_tolerancia_min') y se miden desde la hora de fin del turno, no desde la entrada.
 * - Horas devueltas (-): si fichó menos horas que las teóricas del turno, las horas enteras
 *   que faltan restan.
 *
 * Los días sin fichaje, con un tramo abierto o sin turno con horario no entran en la bolsa.
 *
 * Compensaciones aprobadas (tabla compensaciones_horas): un día con compensación aprobada
 * cuenta en la bolsa por las horas de la compensación (con signo) y su fichaje se ignora,
 * para no contar dos veces una jornada recortada.
 */
class ComputoHorasService
{
    /** Turnos con horario de más de este número de horas se consideran comodín (p.ej. "Vacaciones"). */
    private const MAX_HORAS_TURNO = 16;

    /** @var Collection<int, Turno>|null */
    private static ?Collection $turnosCache = null;

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
     * Horas teóricas de un turno según su horario (ambos tramos si es partido).
     * Devuelve 0 si el turno no tiene horario utilizable.
     */
    public static function horasTeoricasTurno(?Turno $turno): float
    {
        if (!$turno || !$turno->hora_inicio || !$turno->hora_fin) {
            return 0.0;
        }

        $horas = self::horasTramo($turno->hora_inicio, $turno->hora_fin);

        if ($turno->es_partido && $turno->hora_inicio2 && $turno->hora_fin2) {
            $horas += self::horasTramo($turno->hora_inicio2, $turno->hora_fin2);
        }

        if ($horas > self::MAX_HORAS_TURNO) {
            return 0.0;
        }

        return round($horas, 2);
    }

    /**
     * Horas teóricas de una asignación según su turno.
     */
    public static function horasTeoricasAsignacion(AsignacionTurno $a): float
    {
        return self::horasTeoricasTurno(self::turnos()->get($a->turno_id));
    }

    /**
     * Saldo del día para la bolsa de horas, en horas enteras (positivo = horas extra,
     * negativo = horas devueltas). Devuelve null si el día no es computable: sin fichaje,
     * fichaje con un tramo abierto o sin turno con horario.
     *
     * Horas extra: solo si entró a su hora (retraso <= tolerancia); se miden desde la hora
     * de fin del turno y se redondean a horas enteras con horasEnteras(). Horas devueltas:
     * horas enteras que faltan respecto a las teóricas del turno.
     */
    public static function saldoDeAsignacion(AsignacionTurno $a): ?float
    {
        // Un tramo abierto (entrada sin salida) es un fichaje incompleto, no horas devueltas.
        if (($a->entrada && !$a->salida) || ($a->entrada2 && !$a->salida2)) {
            return null;
        }

        $fichadas = self::horasDeAsignacion($a);
        if ($fichadas <= 0) {
            return null;
        }

        $turno = self::turnos()->get($a->turno_id);
        $teoricas = self::horasTeoricasTurno($turno);
        if ($teoricas <= 0) {
            return null;
        }

        $diff = $fichadas - $teoricas;

        if ($diff > 0) {
            return self::horasExtraDelDia($a, $turno);
        }

        $deficitMin = (int) round(-$diff * 60);

        return (float) -self::horasEnteras($deficitMin);
    }

    /**
     * Convierte minutos en horas enteras: cada hora cuenta cuando se han completado al menos
     * 'bolsa_horas_min_extra' minutos de ella. Con 50: 49 min -> 0, 50 min -> 1, 1h49 -> 1, 1h50 -> 2.
     */
    public static function horasEnteras(int $minutos): int
    {
        $minimo = self::minutosMinimosExtra();

        if ($minutos < $minimo) {
            return 0;
        }

        return intdiv($minutos + (60 - $minimo), 60);
    }

    /**
     * Horas extra enteras del día medidas desde la hora de fin del turno (último tramo).
     * 0 si llegó tarde o si la salida no completa al menos una hora según horasEnteras().
     */
    private static function horasExtraDelDia(AsignacionTurno $a, Turno $turno): float
    {
        // Entrada puntual: retraso sobre la hora de inicio del turno dentro de la tolerancia
        $retrasoMin = self::minutosEntre($turno->hora_inicio, $a->entrada);
        if ($retrasoMin > self::toleranciaMinutos()) {
            return 0.0;
        }

        // Salida real del último tramo frente a la hora de fin del turno
        $usaTramo2 = $turno->es_partido && $turno->hora_inicio2 && $turno->hora_fin2;
        $horaFin = $usaTramo2 ? $turno->hora_fin2 : $turno->hora_fin;
        $salidaReal = $usaTramo2 ? $a->salida2 : $a->salida;

        if (!$salidaReal) {
            return 0.0;
        }

        $extraMin = self::minutosEntre($horaFin, $salidaReal);

        return (float) self::horasEnteras($extraMin);
    }

    /**
     * Minutos desde $desde hasta $hasta (horas del día), normalizados a [-12h, +12h] para
     * tolerar turnos y fichajes que cruzan medianoche. Positivo si $hasta es posterior.
     */
    private static function minutosEntre($desde, $hasta): int
    {
        $d = Carbon::parse($desde);
        $h = Carbon::parse($hasta);
        $min = ($h->hour * 60 + $h->minute) - ($d->hour * 60 + $d->minute);

        if ($min > 720) {
            $min -= 1440;
        } elseif ($min < -720) {
            $min += 1440;
        }

        return $min;
    }

    /**
     * Minutos de una hora que deben completarse para que cuente como hora entera en la bolsa
     * (ajuste 'bolsa_horas_min_extra', por defecto 50; entre 1 y 60).
     */
    public static function minutosMinimosExtra(): int
    {
        return min(60, max(1, (int) Configuracion::get('bolsa_horas_min_extra', '50')));
    }

    /** Fecha por defecto desde la que se acumula la bolsa: arranque del sistema, todos a cero. */
    public const BOLSA_DESDE_POR_DEFECTO = '2026-10-21';

    /**
     * Fecha desde la que se acumula la bolsa de horas (ajuste 'bolsa_horas_desde').
     * Por defecto, BOLSA_DESDE_POR_DEFECTO (fecha de puesta en marcha de la bolsa).
     */
    public static function fechaInicioBolsa(): Carbon
    {
        $valor = Configuracion::get('bolsa_horas_desde');

        if ($valor) {
            try {
                return Carbon::parse($valor)->startOfDay();
            } catch (\Throwable $e) {
                // valor corrupto: caer al valor por defecto
            }
        }

        return Carbon::parse(self::BOLSA_DESDE_POR_DEFECTO)->startOfDay();
    }

    /**
     * Retraso de entrada admitido, en minutos, para considerar que entró a su hora
     * (ajuste 'bolsa_horas_tolerancia_min', por defecto 15).
     */
    public static function toleranciaMinutos(): int
    {
        return max(0, (int) Configuracion::get('bolsa_horas_tolerancia_min', '15'));
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
     * Todos los turnos (incluidos borrados, para asignaciones antiguas) indexados por id.
     * Se cachea por request.
     *
     * @return Collection<int, Turno>
     */
    private static function turnos(): Collection
    {
        if (self::$turnosCache === null) {
            self::$turnosCache = Turno::withTrashed()->get()->keyBy('id');
        }

        return self::$turnosCache;
    }

    /**
     * Resumen de horas de una colección de asignaciones ya cargadas.
     *
     * - horas: fichadas reales
     * - previstas: teóricas según los turnos asignados (todos los días del rango)
     * - saldo: suma de diferencias diarias (fichadas - teóricas) de los días computables,
     *   más las compensaciones aprobadas del rango (que sustituyen al fichaje de su día)
     * - dias_con_saldo: días que han aportado al saldo (extra, devolución o compensación)
     *
     * @param  array<string, int>  $compensaciones  horas aprobadas por fecha (Y-m-d) dentro del rango
     * @return array{horas: float, previstas: float, saldo: float, dias_trabajados: int, dias_incompletos: int, dias_con_saldo: int, horas_compensacion: int}
     */
    public static function resumenDesdeColeccion(Collection $asignaciones, array $compensaciones = []): array
    {
        $hoy = Carbon::today()->toDateString();
        $horas = 0.0;
        $previstas = 0.0;
        $saldo = 0.0;
        $diasTrabajados = 0;
        $diasIncompletos = 0;
        $diasConSaldo = 0;

        foreach ($asignaciones as $a) {
            $h = self::horasDeAsignacion($a);
            $fecha = $a->fecha instanceof Carbon ? $a->fecha->toDateString() : (string) $a->fecha;

            $previstas += self::horasTeoricasAsignacion($a);

            if ($h > 0) {
                $horas += $h;
                $diasTrabajados++;

                // Un día con compensación aprobada cuenta por la compensación, no por el fichaje
                if (array_key_exists($fecha, $compensaciones)) {
                    continue;
                }

                $diff = self::saldoDeAsignacion($a);
                if ($diff !== null && $diff != 0.0) {
                    $saldo += $diff;
                    $diasConSaldo++;
                }
            } elseif ($fecha < $hoy && !array_key_exists($fecha, $compensaciones)) {
                $diasIncompletos++;
            }
        }

        $horasCompensacion = 0;
        foreach ($compensaciones as $horasComp) {
            if ((int) $horasComp !== 0) {
                $horasCompensacion += (int) $horasComp;
                $diasConSaldo++;
            }
        }
        $saldo += $horasCompensacion;

        return [
            'horas' => round($horas, 2),
            'previstas' => round($previstas, 2),
            'saldo' => round($saldo, 2),
            'dias_trabajados' => $diasTrabajados,
            'dias_incompletos' => $diasIncompletos,
            'dias_con_saldo' => $diasConSaldo,
            'horas_compensacion' => $horasCompensacion,
        ];
    }

    /**
     * Compensaciones aprobadas por usuario y fecha en un rango.
     * Si $hasta es null no se acota por arriba: las compensaciones aprobadas para días futuros
     * ya descuentan de la bolsa actual, para que el trabajador no pueda pedir dos veces lo mismo.
     *
     * @param  array<int>  $userIds
     * @return array<int, array<string, int>>  [user_id => [Y-m-d => horas]]
     */
    public static function compensacionesAprobadas(array $userIds, Carbon $desde, ?Carbon $hasta = null): array
    {
        if (empty($userIds)) {
            return [];
        }

        $q = CompensacionHoras::aprobadas()
            ->whereIn('user_id', $userIds)
            ->where('fecha', '>=', $desde->toDateString());

        if ($hasta) {
            $q->where('fecha', '<=', $hasta->toDateString());
        }

        $mapa = [];
        foreach ($q->get(['user_id', 'fecha', 'horas']) as $c) {
            $mapa[$c->user_id][$c->fecha->toDateString()] = (int) $c->horas;
        }

        return $mapa;
    }

    /**
     * Horas en solicitudes de compensación pendientes de un usuario (con signo).
     */
    public static function horasPendientesCompensacion(int $userId): int
    {
        return (int) CompensacionHoras::pendientes()->where('user_id', $userId)->sum('horas');
    }

    /**
     * Saldo de la bolsa de un usuario para decidir una nueva solicitud:
     * saldo actual (con compensaciones aprobadas, incluidas futuras), horas ya pedidas y
     * pendientes de resolver, y disponible = saldo + pendientes.
     *
     * @return array{saldo: float, pendientes: int, disponible: float, desde: string}
     */
    public static function saldoDisponible(int $userId): array
    {
        $bolsa = self::bolsaHorasUsuario($userId);
        $pendientes = self::horasPendientesCompensacion($userId);

        return [
            'saldo' => $bolsa['saldo'],
            'pendientes' => $pendientes,
            'disponible' => round($bolsa['saldo'] + $pendientes, 2),
            'desde' => $bolsa['desde'],
        ];
    }

    /**
     * Resumen de horas de un usuario en un rango de fechas.
     */
    public static function resumenRango(int $userId, $inicio, $fin): array
    {
        $asignaciones = AsignacionTurno::where('user_id', $userId)
            ->whereBetween('fecha', [
                Carbon::parse($inicio)->toDateString(),
                Carbon::parse($fin)->toDateString(),
            ])
            ->where('estado', 'activo')
            ->select('fecha', 'turno_id', 'entrada', 'salida', 'entrada2', 'salida2')
            ->get();

        return self::resumenDesdeColeccion($asignaciones);
    }

    /**
     * Bolsa de horas acumulada por trabajador desde la fecha configurada hasta $hasta.
     * Con $hasta = null se usa hoy para los fichajes y se incluyen además las compensaciones
     * aprobadas para días futuros.
     *
     * @param  array<int>|Collection  $userIds
     * @return array<int, array{saldo: float, horas_extra: float, horas_devueltas: float, horas_compensacion: int, dias_extra: int, dias_devueltos: int, dias_compensacion: int, desde: string, hasta: string}>
     */
    public static function bolsaHoras($userIds, ?Carbon $hasta = null): array
    {
        $userIds = collect($userIds)->all();

        if (empty($userIds)) {
            return [];
        }

        $desde = self::fechaInicioBolsa();
        $incluirFuturas = $hasta === null;
        $hasta = ($hasta ?? Carbon::today())->copy()->startOfDay();

        if ($hasta->lt($desde)) {
            $hasta = $desde->copy();
        }

        $compensaciones = self::compensacionesAprobadas($userIds, $desde, $incluirFuturas ? null : $hasta);

        $porUsuario = AsignacionTurno::whereIn('user_id', $userIds)
            ->whereBetween('fecha', [$desde->toDateString(), $hasta->toDateString()])
            ->where('estado', 'activo')
            ->whereNotNull('salida')
            ->select('user_id', 'fecha', 'turno_id', 'entrada', 'salida', 'entrada2', 'salida2')
            ->get()
            ->groupBy('user_id');

        $resultado = [];

        foreach ($userIds as $userId) {
            $extra = 0.0;
            $devueltas = 0.0;
            $diasExtra = 0;
            $diasDevueltos = 0;
            $compsUsuario = $compensaciones[$userId] ?? [];

            foreach ($porUsuario->get($userId, collect()) as $a) {
                $fecha = $a->fecha instanceof Carbon ? $a->fecha->toDateString() : (string) $a->fecha;

                // Un día con compensación aprobada cuenta por la compensación, no por el fichaje
                if (array_key_exists($fecha, $compsUsuario)) {
                    continue;
                }

                $diff = self::saldoDeAsignacion($a);
                if ($diff === null || $diff == 0.0) {
                    continue;
                }
                if ($diff > 0) {
                    $extra += $diff;
                    $diasExtra++;
                } else {
                    $devueltas += -$diff;
                    $diasDevueltos++;
                }
            }

            $horasCompensacion = 0;
            $diasCompensacion = 0;
            foreach ($compsUsuario as $horasComp) {
                if ($horasComp === 0) {
                    continue;
                }
                $horasCompensacion += $horasComp;
                $diasCompensacion++;
                if ($horasComp > 0) {
                    $extra += $horasComp;
                } else {
                    $devueltas += -$horasComp;
                }
            }

            $resultado[$userId] = [
                'saldo' => round($extra - $devueltas, 2),
                'horas_extra' => round($extra, 2),
                'horas_devueltas' => round($devueltas, 2),
                'horas_compensacion' => $horasCompensacion,
                'dias_extra' => $diasExtra,
                'dias_devueltos' => $diasDevueltos,
                'dias_compensacion' => $diasCompensacion,
                'desde' => $desde->toDateString(),
                'hasta' => $hasta->toDateString(),
            ];
        }

        return $resultado;
    }

    /**
     * Bolsa de horas de un único usuario.
     */
    public static function bolsaHorasUsuario(int $userId, ?Carbon $hasta = null): array
    {
        $bolsa = self::bolsaHoras([$userId], $hasta);

        return $bolsa[$userId] ?? self::bolsaVacia();
    }

    /**
     * Resumen de horas de la semana y del mes actuales (más la bolsa acumulada) para un usuario.
     *
     * @return array{semana: array, mes: array, bolsa: array}
     */
    public static function semanaYMesUsuario(int $userId): array
    {
        $resumen = self::semanaYMes([$userId]);

        return $resumen[$userId] ?? self::resumenVacioSemanaYMes();
    }

    /**
     * Resumen de horas de la semana y del mes actuales para varios usuarios (en lote),
     * más la bolsa de horas acumulada hasta hoy.
     *
     * @param  array<int>|Collection  $userIds
     * @return array<int, array{semana: array, mes: array, bolsa: array}>
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
            ->select('user_id', 'fecha', 'turno_id', 'entrada', 'salida', 'entrada2', 'salida2')
            ->get()
            ->groupBy('user_id');

        $bolsas = self::bolsaHoras($userIds);
        $compensaciones = self::compensacionesAprobadas($userIds, $min, $max);

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

            $compsUsuario = $compensaciones[$userId] ?? [];
            $compsSemana = array_filter($compsUsuario, fn($f) => $f >= $inicioSemanaStr && $f <= $finSemanaStr, ARRAY_FILTER_USE_KEY);
            $compsMes = array_filter($compsUsuario, fn($f) => $f >= $inicioMesStr && $f <= $finMesStr, ARRAY_FILTER_USE_KEY);

            $resultado[$userId] = [
                'semana' => self::resumenDesdeColeccion($semana->values(), $compsSemana),
                'mes' => self::resumenDesdeColeccion($mes->values(), $compsMes),
                'bolsa' => $bolsas[$userId] ?? self::bolsaVacia(),
            ];
        }

        return $resultado;
    }

    /**
     * Resumen mensual por trabajador para un mes/año concretos (en lote).
     * Incluye horas trabajadas, previstas, saldo del mes, bolsa acumulada a fin de mes,
     * días y conteo de ausencias por estado.
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
            ->select('user_id', 'fecha', 'estado', 'turno_id', 'entrada', 'salida', 'entrada2', 'salida2')
            ->get()
            ->groupBy('user_id');

        // Bolsa acumulada hasta el fin del mes consultado. Si el mes está en curso o es futuro,
        // la bolsa actual (fichajes hasta hoy más compensaciones aprobadas, también futuras).
        $hastaBolsa = $fin->lt(Carbon::today()) ? $fin : null;
        $bolsas = self::bolsaHoras($userIds, $hastaBolsa);
        $compensaciones = self::compensacionesAprobadas($userIds, $inicio, $fin);

        $resultado = [];

        foreach ($userIds as $userId) {
            $asignaciones = $porUsuario->get($userId, collect());

            $activas = $asignaciones->where('estado', 'activo')->values();
            $resumen = self::resumenDesdeColeccion($activas, $compensaciones[$userId] ?? []);

            $resultado[$userId] = array_merge($resumen, [
                'bolsa'         => ($bolsas[$userId] ?? self::bolsaVacia())['saldo'],
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
     * @return array{semana: array, mes: array, bolsa: array}
     */
    private static function resumenVacioSemanaYMes(): array
    {
        $vacio = [
            'horas' => 0.0, 'previstas' => 0.0, 'saldo' => 0.0,
            'dias_trabajados' => 0, 'dias_incompletos' => 0, 'dias_con_saldo' => 0,
            'horas_compensacion' => 0,
        ];

        return ['semana' => $vacio, 'mes' => $vacio, 'bolsa' => self::bolsaVacia()];
    }

    private static function bolsaVacia(): array
    {
        return [
            'saldo' => 0.0,
            'horas_extra' => 0.0,
            'horas_devueltas' => 0.0,
            'horas_compensacion' => 0,
            'dias_extra' => 0,
            'dias_devueltos' => 0,
            'dias_compensacion' => 0,
            'desde' => self::fechaInicioBolsa()->toDateString(),
            'hasta' => Carbon::today()->toDateString(),
        ];
    }
}
