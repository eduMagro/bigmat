<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Solicitud de compensación de horas contra la bolsa de horas del trabajador.
 *
 * horas: entero con signo. Negativo = el trabajador se toma horas libres (restan de su bolsa).
 * Positivo = se le reconocen horas extra (suman a su bolsa).
 */
class CompensacionHoras extends Model
{
    protected $table = 'compensaciones_horas';

    public const ESTADO_PENDIENTE = 'pendiente';
    public const ESTADO_APROBADA = 'aprobada';
    public const ESTADO_DENEGADA = 'denegada';

    public const HORAS_MAX = 12;

    protected $fillable = [
        'user_id',
        'fecha',
        'horas',
        'estado',
        'comentario',
        'resuelto_por',
        'resuelto_at',
    ];

    protected $casts = [
        'fecha' => 'date',
        'horas' => 'integer',
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

    /**
     * Etiqueta de las horas con signo: "+3 h" / "−3 h".
     */
    public function etiquetaHoras(): string
    {
        return self::formatearHoras($this->horas);
    }

    public static function formatearHoras(int $horas): string
    {
        return ($horas > 0 ? '+' : ($horas < 0 ? '−' : '')) . abs($horas) . ' h';
    }

    /**
     * Título que se muestra en los calendarios según el estado.
     */
    public function tituloCalendario(): string
    {
        return match ($this->estado) {
            self::ESTADO_PENDIENTE => 'C. pendiente ' . $this->etiquetaHoras(),
            self::ESTADO_DENEGADA => 'C. denegada ' . $this->etiquetaHoras(),
            default => 'Compensación ' . $this->etiquetaHoras(),
        };
    }
}
