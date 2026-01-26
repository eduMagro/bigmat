<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AgrupacionTurnoDia extends Model
{
    use HasFactory;

    protected $table = 'agrupacion_turno_dias';

    // Constantes para los días de la semana (Carbon format)
    public const DOMINGO = 0;
    public const LUNES = 1;
    public const MARTES = 2;
    public const MIERCOLES = 3;
    public const JUEVES = 4;
    public const VIERNES = 5;
    public const SABADO = 6;

    // Nombres de días para mostrar
    public const DIAS_NOMBRES = [
        self::DOMINGO => 'Domingo',
        self::LUNES => 'Lunes',
        self::MARTES => 'Martes',
        self::MIERCOLES => 'Miércoles',
        self::JUEVES => 'Jueves',
        self::VIERNES => 'Viernes',
        self::SABADO => 'Sábado',
    ];

    // Abreviaciones para chips
    public const DIAS_ABREV = [
        self::DOMINGO => 'D',
        self::LUNES => 'L',
        self::MARTES => 'M',
        self::MIERCOLES => 'X',
        self::JUEVES => 'J',
        self::VIERNES => 'V',
        self::SABADO => 'S',
    ];

    protected $fillable = [
        'agrupacion_turno_id',
        'dia_semana',
        'turno_id',
    ];

    protected $casts = [
        'dia_semana' => 'integer',
    ];

    /**
     * Relación: Pertenece a una agrupación de turnos
     */
    public function agrupacion()
    {
        return $this->belongsTo(AgrupacionTurno::class, 'agrupacion_turno_id');
    }

    /**
     * Relación: Tiene un turno asignado (puede ser null)
     */
    public function turno()
    {
        return $this->belongsTo(Turno::class, 'turno_id');
    }

    /**
     * Obtener el nombre del día
     */
    public function getNombreDiaAttribute(): string
    {
        return self::DIAS_NOMBRES[$this->dia_semana] ?? 'Desconocido';
    }

    /**
     * Obtener la abreviación del día
     */
    public function getAbrevDiaAttribute(): string
    {
        return self::DIAS_ABREV[$this->dia_semana] ?? '?';
    }
}
