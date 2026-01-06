<?php

namespace App\Servicios\Turnos;

use App\Models\Turno;
use Illuminate\Support\Collection;

/**
 * Servicio centralizado para mapeo de turnos a slots visuales del calendario
 *
 * Los slots visuales del calendario son:
 * - Noche: 00:00-08:00 (para turnos con hora_fin < hora_inicio, ej: 22:00-06:00)
 * - Mañana: 08:00-16:00 (para turnos con hora_inicio < 12)
 * - Tarde: 16:00-24:00 (para turnos con hora_inicio >= 12)
 */
class TurnoMapper
{
    // Constantes de IDs de turno (deben coincidir con la BD)
    public const TURNO_MANANA = 1;
    public const TURNO_TARDE = 2;
    public const TURNO_NOCHE = 3;
    public const TURNO_FESTIVO = 4;
    public const TURNO_VACACIONES = 10;

    // Slots visuales del calendario (8 horas cada uno)
    public const SLOT_NOCHE_INICIO = '00:00:00';
    public const SLOT_NOCHE_FIN = '08:00:00';
    public const SLOT_MANANA_INICIO = '08:00:00';
    public const SLOT_MANANA_FIN = '16:00:00';
    public const SLOT_TARDE_INICIO = '16:00:00';
    public const SLOT_TARDE_FIN = '24:00:00';

    /**
     * Cache de turnos
     */
    protected static ?Collection $turnosCache = null;

    /**
     * Obtiene todos los turnos de la BD (con cache)
     */
    public static function getTurnos(): Collection
    {
        if (self::$turnosCache === null) {
            self::$turnosCache = Turno::all();
        }
        return self::$turnosCache;
    }

    /**
     * Limpia el cache de turnos
     */
    public static function clearCache(): void
    {
        self::$turnosCache = null;
    }

    /**
     * Determina si un turno es nocturno basándose en sus horas
     */
    public static function esNocturno(?string $horaInicio, ?string $horaFin): bool
    {
        if (!$horaInicio || !$horaFin) {
            return false;
        }

        $hIni = (int) substr($horaInicio, 0, 2);
        $hFin = (int) substr($horaFin, 0, 2);

        return $hFin < $hIni;
    }

    /**
     * Obtiene el slot visual para un turno dado
     *
     * @param string|null $horaInicio Hora de inicio del turno (HH:mm o HH:mm:ss)
     * @param string|null $horaFin Hora de fin del turno (HH:mm o HH:mm:ss)
     * @param string $fechaStr Fecha en formato Y-m-d
     * @return array ['start' => 'Y-m-dTHH:mm:ss', 'end' => 'Y-m-dTHH:mm:ss']
     */
    public static function getSlotParaTurno(?string $horaInicio, ?string $horaFin, string $fechaStr): array
    {
        $horaInicio = $horaInicio ?? '06:00';
        $horaFin = $horaFin ?? '14:00';

        $hIni = (int) substr($horaInicio, 0, 2);
        $hFin = (int) substr($horaFin, 0, 2);
        $esNocturno = $hFin < $hIni;

        if ($esNocturno) {
            // Turno nocturno → slot 00:00-08:00
            return [
                'start' => $fechaStr . 'T' . self::SLOT_NOCHE_INICIO,
                'end' => $fechaStr . 'T' . self::SLOT_NOCHE_FIN,
            ];
        } elseif ($hIni < 12) {
            // Turno de mañana → slot 08:00-16:00
            return [
                'start' => $fechaStr . 'T' . self::SLOT_MANANA_INICIO,
                'end' => $fechaStr . 'T' . self::SLOT_MANANA_FIN,
            ];
        } else {
            // Turno de tarde → slot 16:00-24:00
            return [
                'start' => $fechaStr . 'T' . self::SLOT_TARDE_INICIO,
                'end' => $fechaStr . 'T' . self::SLOT_TARDE_FIN,
            ];
        }
    }

    /**
     * Obtiene el slot visual para un modelo de Turno
     */
    public static function getSlotParaTurnoModel(?Turno $turno, string $fechaStr): array
    {
        return self::getSlotParaTurno(
            $turno?->hora_inicio,
            $turno?->hora_fin,
            $fechaStr
        );
    }

    /**
     * Detecta el turno_id basándose en una hora dada
     *
     * @param int $hora Hora (0-23)
     * @param Collection|null $turnos Colección de turnos (si null, obtiene de BD)
     * @return int|null turno_id o null si no se encuentra
     */
    public static function getTurnoIdPorHora(int $hora, ?Collection $turnos = null): ?int
    {
        $turnos = $turnos ?? self::getTurnos();

        foreach ($turnos as $turno) {
            if (!$turno->hora_inicio || !$turno->hora_fin) {
                continue;
            }

            $hIni = (int) substr($turno->hora_inicio, 0, 2);
            $hFin = (int) substr($turno->hora_fin, 0, 2);
            $esNocturno = $hFin < $hIni;

            if ($esNocturno) {
                // Nocturno: 22:00-06:00 → acepta horas >= 22 o < 6
                if ($hora >= $hIni || $hora < $hFin) {
                    return $turno->id;
                }
            } else {
                // Normal: hora dentro del rango
                if ($hora >= $hIni && $hora < $hFin) {
                    return $turno->id;
                }
            }
        }

        // Fallback basado en slots visuales
        if ($hora >= 0 && $hora < 8) {
            return self::TURNO_NOCHE;
        } elseif ($hora >= 8 && $hora < 16) {
            return self::TURNO_MANANA;
        } else {
            return self::TURNO_TARDE;
        }
    }

    /**
     * Detecta el nombre del turno basándose en una hora dada
     *
     * @param int $hora Hora (0-23)
     * @return string 'noche', 'mañana' o 'tarde'
     */
    public static function getNombreTurnoPorHora(int $hora): string
    {
        // Basado en slots visuales del calendario
        if ($hora >= 0 && $hora < 8) {
            return 'noche';
        } elseif ($hora >= 8 && $hora < 16) {
            return 'mañana';
        } else {
            return 'tarde';
        }
    }

    /**
     * Obtiene el turno_id por nombre
     */
    public static function getTurnoIdPorNombre(string $nombre): ?int
    {
        $turnos = self::getTurnos();
        $turno = $turnos->firstWhere('nombre', strtolower($nombre));
        return $turno?->id;
    }

    /**
     * Obtiene la configuración de turnos para el frontend (JS)
     */
    public static function getConfigParaFrontend(): array
    {
        $turnos = self::getTurnos();

        return [
            'turnos' => $turnos->map(function ($turno) {
                return [
                    'id' => $turno->id,
                    'nombre' => $turno->nombre,
                    'hora_inicio' => $turno->hora_inicio,
                    'hora_fin' => $turno->hora_fin,
                    'color' => $turno->color ?? '#e5e7eb',
                ];
            })->values()->toArray(),
            'slots' => [
                'noche' => ['inicio' => self::SLOT_NOCHE_INICIO, 'fin' => self::SLOT_NOCHE_FIN],
                'manana' => ['inicio' => self::SLOT_MANANA_INICIO, 'fin' => self::SLOT_MANANA_FIN],
                'tarde' => ['inicio' => self::SLOT_TARDE_INICIO, 'fin' => self::SLOT_TARDE_FIN],
            ],
            'ids' => [
                'manana' => self::TURNO_MANANA,
                'tarde' => self::TURNO_TARDE,
                'noche' => self::TURNO_NOCHE,
                'festivo' => self::TURNO_FESTIVO,
                'vacaciones' => self::TURNO_VACACIONES,
            ],
        ];
    }

    /**
     * Valida si hay overlap entre dos rangos de turno
     */
    public static function hayOverlap(
        string $fecha1,
        int $turnoId1,
        string $fecha2,
        int $turnoId2
    ): bool {
        // Si son fechas diferentes, no hay overlap
        if ($fecha1 !== $fecha2) {
            return false;
        }

        // Si son el mismo turno en la misma fecha, hay overlap
        return $turnoId1 === $turnoId2;
    }
}
