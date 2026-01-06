<?php

namespace App\Servicios\Turnos;

use App\Models\AsignacionTurno;
use App\Models\Maquina;
use Illuminate\Support\Carbon;

/**
 * Servicio para validar asignaciones de turnos
 * Detecta overlaps y conflictos antes de crear/actualizar
 */
class ValidadorAsignaciones
{
    /**
     * Verifica si un usuario ya tiene una asignación para una fecha dada
     *
     * @param int $userId
     * @param string $fecha Fecha en formato Y-m-d
     * @param int|null $excludeId ID de asignación a excluir (para updates)
     * @return AsignacionTurno|null La asignación existente o null
     */
    public static function tieneAsignacionEnFecha(int $userId, string $fecha, ?int $excludeId = null): ?AsignacionTurno
    {
        $query = AsignacionTurno::where('user_id', $userId)
            ->whereDate('fecha', $fecha);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->first();
    }

    /**
     * Verifica si hay overlap para una máquina en una fecha/turno específico
     *
     * @param int $maquinaId
     * @param string $fecha
     * @param int $turnoId
     * @param int|null $excludeUserId Usuario a excluir
     * @param int|null $maxCapacidad Capacidad máxima de la máquina (null = sin límite)
     * @return array ['hay_overlap' => bool, 'asignaciones_actuales' => int, 'asignaciones' => Collection]
     */
    public static function verificarCapacidadMaquina(
        int $maquinaId,
        string $fecha,
        int $turnoId,
        ?int $excludeUserId = null,
        ?int $maxCapacidad = null
    ): array {
        $query = AsignacionTurno::where('maquina_id', $maquinaId)
            ->whereDate('fecha', $fecha)
            ->where('turno_id', $turnoId);

        if ($excludeUserId) {
            $query->where('user_id', '!=', $excludeUserId);
        }

        $asignaciones = $query->get();
        $cantidad = $asignaciones->count();

        // Si no hay límite de capacidad, no hay overlap
        if ($maxCapacidad === null) {
            return [
                'hay_overlap' => false,
                'asignaciones_actuales' => $cantidad,
                'asignaciones' => $asignaciones,
            ];
        }

        return [
            'hay_overlap' => $cantidad >= $maxCapacidad,
            'asignaciones_actuales' => $cantidad,
            'capacidad_maxima' => $maxCapacidad,
            'asignaciones' => $asignaciones,
        ];
    }

    /**
     * Valida si se puede crear/actualizar una asignación
     *
     * @param int $userId
     * @param string $fecha
     * @param int $turnoId
     * @param int|null $maquinaId
     * @param int|null $excludeAsignacionId Para updates
     * @return array ['valido' => bool, 'errores' => array, 'warnings' => array]
     */
    public static function validarAsignacion(
        int $userId,
        string $fecha,
        int $turnoId,
        ?int $maquinaId = null,
        ?int $excludeAsignacionId = null
    ): array {
        $errores = [];
        $warnings = [];

        // 1. Verificar si el usuario ya tiene asignación ese día
        $asignacionExistente = self::tieneAsignacionEnFecha($userId, $fecha, $excludeAsignacionId);

        if ($asignacionExistente) {
            // Si el turno es diferente, es un error (no puede tener 2 turnos el mismo día)
            if ($asignacionExistente->turno_id !== $turnoId) {
                $errores[] = [
                    'tipo' => 'usuario_duplicado',
                    'mensaje' => "El usuario ya tiene una asignación para el {$fecha} en turno diferente.",
                    'asignacion_existente' => $asignacionExistente->id,
                ];
            } else {
                // Mismo turno, puede ser un update o duplicado
                $warnings[] = [
                    'tipo' => 'posible_duplicado',
                    'mensaje' => "El usuario ya tiene una asignación para el {$fecha} en el mismo turno.",
                    'asignacion_existente' => $asignacionExistente->id,
                ];
            }
        }

        // 2. Si hay máquina, verificar capacidad (opcional, por ahora sin límite)
        if ($maquinaId) {
            $maquina = Maquina::find($maquinaId);
            $maxCapacidad = $maquina?->capacidad_maxima ?? null; // Campo opcional en máquinas

            if ($maxCapacidad) {
                $verificacion = self::verificarCapacidadMaquina(
                    $maquinaId,
                    $fecha,
                    $turnoId,
                    $userId,
                    $maxCapacidad
                );

                if ($verificacion['hay_overlap']) {
                    $warnings[] = [
                        'tipo' => 'capacidad_maquina',
                        'mensaje' => "La máquina ya tiene {$verificacion['asignaciones_actuales']}/{$maxCapacidad} operarios en ese turno.",
                        'asignaciones_actuales' => $verificacion['asignaciones_actuales'],
                    ];
                }
            }
        }

        return [
            'valido' => empty($errores),
            'errores' => $errores,
            'warnings' => $warnings,
        ];
    }

    /**
     * Verifica si hay soft-deleted que bloquearía un insert
     *
     * @param int $userId
     * @param string $fecha
     * @return AsignacionTurno|null
     */
    public static function tieneSoftDeleted(int $userId, string $fecha): ?AsignacionTurno
    {
        return AsignacionTurno::onlyTrashed()
            ->where('user_id', $userId)
            ->whereDate('fecha', $fecha)
            ->first();
    }

    /**
     * Limpia soft-deleted para permitir nueva inserción
     *
     * @param int $userId
     * @param string $fecha
     * @return int Cantidad eliminadas
     */
    public static function limpiarSoftDeleted(int $userId, string $fecha): int
    {
        return AsignacionTurno::onlyTrashed()
            ->where('user_id', $userId)
            ->whereDate('fecha', $fecha)
            ->forceDelete();
    }
}
