<?php

namespace App\Models;

use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Model;


class VacacionesSolicitud extends Model
{
    protected $table = 'solicitudes_vacaciones';

    protected $fillable = [
        'user_id',
        'fecha_inicio',
        'fecha_fin',
        'estado',
        'observaciones',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Días de solicitudes pendientes repartidos por periodo, para el año consultado.
     * Devuelve: anterior, actual, periodo_gracia (ene-mar) y post_gracia (abr-dic).
     *
     * Cuenta días naturales: en BigMat el cupo es de 30 días naturales, así que
     * los fines de semana y los festivos también consumen vacaciones.
     */
    public static function diasPendientesPorPeriodo(int $userId, int $anio): array
    {
        $conteo = [
            'anterior' => 0,
            'actual' => 0,
            'periodo_gracia' => 0,
            'post_gracia' => 0,
        ];

        $solicitudes = static::where('user_id', $userId)
            ->where('estado', 'pendiente')
            ->get(['fecha_inicio', 'fecha_fin']);

        foreach ($solicitudes as $solicitud) {
            foreach (CarbonPeriod::create($solicitud->fecha_inicio, $solicitud->fecha_fin) as $fecha) {
                if ($fecha->year === $anio - 1) {
                    $conteo['anterior']++;
                    continue;
                }

                if ($fecha->year !== $anio) {
                    continue;
                }

                $conteo['actual']++;

                if ($fecha->month <= 3) {
                    $conteo['periodo_gracia']++;
                } else {
                    $conteo['post_gracia']++;
                }
            }
        }

        return $conteo;
    }
}
