<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Solicitud de horas contra la bolsa de horas del trabajador.
 *
 * minutos: entero con signo, en pasos de 5 minutos.
 *  - Positivo = horas extra: el trabajador pide que se le reconozca tiempo de un día ya trabajado
 *    (hoy o pasado). Al aprobarse suma a su bolsa.
 *  - Negativo = compensación: el trabajador se toma tiempo libre de un día (hoy o futuro). Al
 *    aprobarse resta de su bolsa, que no puede quedar en negativo.
 */
class CompensacionHoras extends Model
{
    protected $table = 'compensaciones_horas';

    public const ESTADO_PENDIENTE = 'pendiente';
    public const ESTADO_APROBADA = 'aprobada';
    public const ESTADO_DENEGADA = 'denegada';

    /** Paso mínimo de una solicitud, en minutos. */
    public const PASO_MINUTOS = 5;

    /** Máximo por solicitud: 12 horas. */
    public const MINUTOS_MAX = 720;

    protected $fillable = [
        'user_id',
        'fecha',
        'minutos',
        'estado',
        'comentario',
        'resuelto_por',
        'resuelto_at',
    ];

    protected $casts = [
        'fecha' => 'date',
        'minutos' => 'integer',
        'resuelto_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function resueltoPor()
    {
        return $this->belongsTo(User::class, 'resuelto_por');
    }

    public function scopePendientes($query)
    {
        return $query->where('estado', self::ESTADO_PENDIENTE);
    }

    public function scopeAprobadas($query)
    {
        return $query->where('estado', self::ESTADO_APROBADA);
    }

    /** Horas extra (positivas) frente a compensación (negativas). */
    public function esExtra(): bool
    {
        return $this->minutos > 0;
    }

    /** 'extra' | 'compensacion' */
    public function tipo(): string
    {
        return $this->esExtra() ? 'extra' : 'compensacion';
    }

    /** "Horas extra" / "Compensación" */
    public function nombreTipo(): string
    {
        return $this->esExtra() ? 'Horas extra' : 'Compensación';
    }

    /**
     * Etiqueta del tiempo con signo: "+3 h", "−1 h 05 min", "+45 min".
     */
    public function etiqueta(): string
    {
        return self::formatearMinutos($this->minutos);
    }

    /**
     * Formatea minutos como texto legible: 60 -> "1 h", 65 -> "1 h 05 min", 45 -> "45 min".
     * Con $signo se antepone "+" o "−" (U+2212).
     */
    public static function formatearMinutos(int $minutos, bool $signo = true): string
    {
        $abs = abs($minutos);
        $h = intdiv($abs, 60);
        $m = $abs % 60;

        if ($abs === 0) {
            $txt = '0 h';
        } elseif ($h > 0 && $m > 0) {
            $txt = sprintf('%d h %02d min', $h, $m);
        } elseif ($h > 0) {
            $txt = $h . ' h';
        } else {
            $txt = $m . ' min';
        }

        $prefijo = $signo ? ($minutos > 0 ? '+' : ($minutos < 0 ? '−' : '')) : '';

        return $prefijo . $txt;
    }

    /**
     * Etiqueta corta con tipo y tiempo: "Extra +3 h" / "Compensación −2 h".
     */
    public function etiquetaCorta(): string
    {
        return ($this->esExtra() ? 'Extra ' : 'Compensación ') . $this->etiqueta();
    }

    /**
     * Título que se muestra en los calendarios según el estado.
     */
    public function tituloCalendario(): string
    {
        $tipo = $this->esExtra() ? 'Extra' : 'Compensación';

        return match ($this->estado) {
            self::ESTADO_PENDIENTE => $tipo . ' pendiente ' . $this->etiqueta(),
            self::ESTADO_DENEGADA => $tipo . ' denegada ' . $this->etiqueta(),
            default => $tipo . ' ' . $this->etiqueta(),
        };
    }
}
