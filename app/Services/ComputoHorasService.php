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
 * Bolsa de horas: se lleva en MINUTOS y se alimenta exclusivamente de las solicitudes aprobadas
 * (tabla compensaciones_horas, pasos de 5 minutos), sin fecha de inicio: cuentan todas las aprobadas.
 *
 * - Horas extra (+): el trabajador las solicita para un día ya trabajado (hoy o pasado) y el
 *   responsable las aprueba o deniega tras comprobar el fichaje. Oficina también puede
 *   reconocerlas directamente.
 * - Compensación (-): el trabajador pide recortar una jornada (hoy o futura) contra su
 *   bolsa; oficina/responsable aprueban o la asignan directamente. La bolsa no puede quedar
 *   en negativo.
 *
 * El fichaje NO mueve la bolsa por sí solo: sirve de comprobación. Para ayudar a decidir,
 * diferenciaFichaje() calcula los minutos de más (o de menos) del día "según fichaje":
 * redondeados a 5 minutos, los extras solo si entró puntual (retraso máximo
 * 'bolsa_horas_tolerancia_min') y medidos desde la hora de fin del turno. El ajuste
 * 'bolsa_horas_min_extra' marca el mínimo de minutos extra que el cliente considera
 * reconocible (50 por defecto) y se muestra como aviso al revisar.
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
     * Diferencia orientativa del día según el fichaje, en minutos redondeados a 5 (positivo =
     * minutos extra según fichaje, negativo = minutos que faltaron). Devuelve null si el día no
     * es computable: sin fichaje, fichaje con un tramo abierto o sin turno con horario.
     *
     * No mueve la bolsa: es la referencia que ve el responsable al revisar una solicitud.
     * Minutos extra: solo si entró a su hora (retraso <= tolerancia); se miden desde la hora de
     * fin del turno. Minutos que faltan: respecto a las horas teóricas del turno.
     */
    public static function diferenciaFichaje(AsignacionTurno $a): ?int
    {
        // Un tramo abierto (entrada sin salida) es un fichaje incompleto
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
            return self::minutosExtraDelDia($a, $turno);
        }

        $deficitMin = (int) round(-$diff * 60);

        return -self::redondearPaso($deficitMin);
    }

    /**
     * Redondea minutos hacia abajo al paso de las solicitudes (5 min). 47 -> 45, 65 -> 65, 4 -> 0.
     */
    public static function redondearPaso(int $minutos): int
    {
        $paso = CompensacionHoras::PASO_MINUTOS;

        return $minutos <= 0 ? 0 : intdiv($minutos, $paso) * $paso;
    }

    /**
     * Minutos extra del día medidos desde la hora de fin del turno (último tramo), redondeados
     * a 5. 0 si llegó tarde.
     */
    private static function minutosExtraDelDia(AsignacionTurno $a, Turno $turno): int
    {
        if (!self::entradaPuntual($a, $turno)) {
            return 0;
        }

        // Salida real del último tramo frente a la hora de fin del turno
        $usaTramo2 = $turno->es_partido && $turno->hora_inicio2 && $turno->hora_fin2;
        $horaFin = $usaTramo2 ? $turno->hora_fin2 : $turno->hora_fin;
        $salidaReal = $usaTramo2 ? $a->salida2 : $a->salida;

        if (!$salidaReal) {
            return 0;
        }

        return self::redondearPaso(self::minutosEntre($horaFin, $salidaReal));
    }

    /**
     * Entrada puntual: retraso sobre la hora de inicio del turno dentro de la tolerancia.
     */
    private static function entradaPuntual(AsignacionTurno $a, Turno $turno): bool
    {
        if (!$a->entrada || !$turno->hora_inicio) {
            return false;
        }

        return self::minutosEntre($turno->hora_inicio, $a->entrada) <= self::toleranciaMinutos();
    }

    /**
     * Resumen del fichaje de un trabajador en un día concreto, para revisar una solicitud de
     * horas extra: turno y horario, fichajes, horas fichadas y teóricas y los minutos extra
     * orientativos según el fichaje. Null si ese día no tiene asignación activa.
     *
     * @return array{turno: ?string, horario: ?string, entrada: ?string, salida: ?string, entrada2: ?string, salida2: ?string, fichaje: ?string, horas_fichadas: float, horas_teoricas: float, extra_min: int, extra_txt: string, minimo_min: int, cumple_minimo: bool, puntual: ?bool, abierto: bool, sin_fichaje: bool}|null
     */
    public static function fichajeDelDia(int $userId, $fecha): ?array
    {
        $fechaStr = $fecha instanceof Carbon ? $fecha->toDateString() : Carbon::parse($fecha)->toDateString();

        $a = AsignacionTurno::where('user_id', $userId)
            ->whereDate('fecha', $fechaStr)
            ->where('estado', 'activo')
            ->select('id', 'fecha', 'turno_id', 'entrada', 'salida', 'entrada2', 'salida2')
            ->first();

        if (!$a) {
            return null;
        }

        $turno = self::turnos()->get($a->turno_id);
        $hhmm = fn($v) => $v ? substr((string) $v, 0, 5) : null;

        $horario = null;
        if ($turno && $turno->hora_inicio && $turno->hora_fin) {
            $horario = $hhmm($turno->hora_inicio) . '–' . $hhmm($turno->hora_fin);
            if ($turno->es_partido && $turno->hora_inicio2 && $turno->hora_fin2) {
                $horario .= ' y ' . $hhmm($turno->hora_inicio2) . '–' . $hhmm($turno->hora_fin2);
            }
        }

        $tramos = [];
        if ($a->entrada) {
            $tramos[] = $hhmm($a->entrada) . '–' . ($a->salida ? $hhmm($a->salida) : '…');
        }
        if ($a->entrada2) {
            $tramos[] = $hhmm($a->entrada2) . '–' . ($a->salida2 ? $hhmm($a->salida2) : '…');
        }

        $abierto = ($a->entrada && !$a->salida) || ($a->entrada2 && !$a->salida2);
        $diff = self::diferenciaFichaje($a);
        $extraMin = ($diff !== null && $diff > 0) ? $diff : 0;
        $minimo = self::minutosMinimosExtra();

        return [
            'turno' => $turno?->nombre,
            'horario' => $horario,
            'entrada' => $hhmm($a->entrada),
            'salida' => $hhmm($a->salida),
            'entrada2' => $hhmm($a->entrada2),
            'salida2' => $hhmm($a->salida2),
            'fichaje' => $tramos ? implode(' y ', $tramos) : null,
            'horas_fichadas' => self::horasDeAsignacion($a),
            'horas_teoricas' => self::horasTeoricasTurno($turno),
            'extra_min' => $extraMin,
            'extra_txt' => CompensacionHoras::formatearMinutos($extraMin),
            'minimo_min' => $minimo,
            'cumple_minimo' => $extraMin >= $minimo,
            'puntual' => ($turno && $a->entrada) ? self::entradaPuntual($a, $turno) : null,
            'abierto' => (bool) $abierto,
            'sin_fichaje' => !$a->entrada,
        ];
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
     * Mínimo de minutos extra según fichaje a partir del cual el cliente considera que hay
     * horas extra reconocibles (ajuste 'bolsa_horas_min_extra', por defecto 50; entre 0 y 240).
     * Es un aviso para quien revisa, no un bloqueo.
     */
    public static function minutosMinimosExtra(): int
    {
        return min(240, max(0, (int) Configuracion::get('bolsa_horas_min_extra', '50')));
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
     * - saldo: orientativo en horas, suma de diferencias diarias según fichaje de los días
     *   computables. No es la bolsa.
     * - compensacion_min: minutos aprobados en el rango (extras reconocidas menos compensadas),
     *   que es lo que de verdad ha movido la bolsa en ese periodo
     * - dias_con_saldo: días cuyo fichaje difiere del turno
     *
     * @param  array<string, int>  $compensaciones  minutos aprobados por fecha (Y-m-d) dentro del rango
     * @return array{horas: float, previstas: float, saldo: float, dias_trabajados: int, dias_incompletos: int, dias_con_saldo: int, compensacion_min: int}
     */
    public static function resumenDesdeColeccion(Collection $asignaciones, array $compensaciones = []): array
    {
        $hoy = Carbon::today()->toDateString();
        $horas = 0.0;
        $previstas = 0.0;
        $saldoMin = 0;
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

                $diff = self::diferenciaFichaje($a);
                if ($diff !== null && $diff !== 0) {
                    $saldoMin += $diff;
                    $diasConSaldo++;
                }
            } elseif ($fecha < $hoy && !array_key_exists($fecha, $compensaciones)) {
                $diasIncompletos++;
            }
        }

        $compensacionMin = 0;
        foreach ($compensaciones as $min) {
            $compensacionMin += (int) $min;
        }

        return [
            'horas' => round($horas, 2),
            'previstas' => round($previstas, 2),
            'saldo' => round($saldoMin / 60, 2),
            'dias_trabajados' => $diasTrabajados,
            'dias_incompletos' => $diasIncompletos,
            'dias_con_saldo' => $diasConSaldo,
            'compensacion_min' => $compensacionMin,
        ];
    }

    /**
     * Compensaciones aprobadas por usuario y fecha en un rango (minutos con signo).
     * Si $hasta es null no se acota por arriba: las compensaciones aprobadas para días futuros
     * ya descuentan de la bolsa actual, para que el trabajador no pueda pedir dos veces lo mismo.
     *
     * @param  array<int>  $userIds
     * @return array<int, array<string, int>>  [user_id => [Y-m-d => minutos]]
     */
    public static function compensacionesAprobadas(array $userIds, ?Carbon $desde = null, ?Carbon $hasta = null): array
    {
        if (empty($userIds)) {
            return [];
        }

        $q = CompensacionHoras::aprobadas()
            ->whereIn('user_id', $userIds);

        if ($desde) {
            $q->where('fecha', '>=', $desde->toDateString());
        }

        if ($hasta) {
            $q->where('fecha', '<=', $hasta->toDateString());
        }

        $mapa = [];
        foreach ($q->get(['user_id', 'fecha', 'minutos']) as $c) {
            $mapa[$c->user_id][$c->fecha->toDateString()] = (int) $c->minutos;
        }

        return $mapa;
    }

    /**
     * Saldo de la bolsa de un usuario para decidir una nueva solicitud, en minutos:
     * saldo actual (aprobadas, incluidas futuras), compensaciones pendientes (negativas, ya
     * comprometidas), extras pendientes (positivas, aún no cuentan) y
     * disponible = saldo + pendientes negativas.
     *
     * @return array{saldo_min: int, pendientes_min: int, pendientes_extra_min: int, disponible_min: int, saldo_txt: string, disponible_txt: string, desde: string}
     */
    public static function saldoDisponible(int $userId): array
    {
        $bolsa = self::bolsaHorasUsuario($userId);

        $pendientesNeg = (int) CompensacionHoras::pendientes()->where('user_id', $userId)->where('minutos', '<', 0)->sum('minutos');
        $pendientesPos = (int) CompensacionHoras::pendientes()->where('user_id', $userId)->where('minutos', '>', 0)->sum('minutos');
        $disponible = $bolsa['saldo_min'] + $pendientesNeg;

        return [
            'saldo_min' => $bolsa['saldo_min'],
            'pendientes_min' => $pendientesNeg,
            'pendientes_extra_min' => $pendientesPos,
            'disponible_min' => $disponible,
            'saldo_txt' => CompensacionHoras::formatearMinutos($bolsa['saldo_min']),
            'disponible_txt' => CompensacionHoras::formatearMinutos($disponible),
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
     * Bolsa de horas acumulada por trabajador, en minutos: solicitudes aprobadas desde la fecha
     * configurada. Con $hasta = null se incluyen también las aprobadas para días futuros.
     *
     * @param  array<int>|Collection  $userIds
     * @return array<int, array{saldo_min: int, extra_min: int, devueltas_min: int, saldo_txt: string, dias_extra: int, dias_devueltos: int, dias_compensacion: int, desde: string, hasta: string}>
     */
    public static function bolsaHoras($userIds, ?Carbon $hasta = null): array
    {
        $userIds = collect($userIds)->all();

        if (empty($userIds)) {
            return [];
        }

        $incluirFuturas = $hasta === null;
        $hasta = ($hasta ?? Carbon::today())->copy()->startOfDay();

        $compensaciones = self::compensacionesAprobadas($userIds, null, $incluirFuturas ? null : $hasta);

        $resultado = [];

        foreach ($userIds as $userId) {
            $extra = 0;
            $devueltas = 0;
            $diasExtra = 0;
            $diasDevueltos = 0;

            foreach ($compensaciones[$userId] ?? [] as $min) {
                if ($min > 0) {
                    $extra += $min;
                    $diasExtra++;
                } elseif ($min < 0) {
                    $devueltas += -$min;
                    $diasDevueltos++;
                }
            }

            $resultado[$userId] = [
                'saldo_min' => $extra - $devueltas,
                'extra_min' => $extra,
                'devueltas_min' => $devueltas,
                'saldo_txt' => CompensacionHoras::formatearMinutos($extra - $devueltas),
                'dias_extra' => $diasExtra,
                'dias_devueltos' => $diasDevueltos,
                'dias_compensacion' => $diasExtra + $diasDevueltos,
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
     * más la bolsa de horas acumulada.
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
     * Incluye horas trabajadas, previstas, saldo del mes, bolsa acumulada a fin de mes (minutos),
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
        // la bolsa actual (incluidas las compensaciones aprobadas para días futuros).
        $hastaBolsa = $fin->lt(Carbon::today()) ? $fin : null;
        $bolsas = self::bolsaHoras($userIds, $hastaBolsa);
        $compensaciones = self::compensacionesAprobadas($userIds, $inicio, $fin);

        $resultado = [];

        foreach ($userIds as $userId) {
            $asignaciones = $porUsuario->get($userId, collect());

            $activas = $asignaciones->where('estado', 'activo')->values();
            $resumen = self::resumenDesdeColeccion($activas, $compensaciones[$userId] ?? []);

            $resultado[$userId] = array_merge($resumen, [
                'bolsa_min'     => ($bolsas[$userId] ?? self::bolsaVacia())['saldo_min'],
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
            'compensacion_min' => 0,
        ];

        return ['semana' => $vacio, 'mes' => $vacio, 'bolsa' => self::bolsaVacia()];
    }

    private static function bolsaVacia(): array
    {
        return [
            'saldo_min' => 0,
            'extra_min' => 0,
            'devueltas_min' => 0,
            'saldo_txt' => CompensacionHoras::formatearMinutos(0),
            'dias_extra' => 0,
            'dias_devueltos' => 0,
            'dias_compensacion' => 0,
            'hasta' => Carbon::today()->toDateString(),
        ];
    }
}
