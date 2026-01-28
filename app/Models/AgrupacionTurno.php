<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AgrupacionTurno extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'agrupaciones_turnos';

    protected $fillable = [
        'nombre',
        'descripcion',
        'activo',
        'orden',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'orden' => 'integer',
    ];

    /**
     * Relación: Una agrupación tiene muchos días configurados
     */
    public function dias()
    {
        return $this->hasMany(AgrupacionTurnoDia::class, 'agrupacion_turno_id');
    }

    /**
     * Scope: Solo agrupaciones activas
     */
    public function scopeActivas($query)
    {
        return $query->where('activo', true);
    }

    /**
     * Scope: Ordenadas por el campo orden
     */
    public function scopeOrdenadas($query)
    {
        return $query->orderBy('orden')->orderBy('nombre');
    }

    /**
     * Obtener el turno asignado para un día de la semana específico
     *
     * @param int $diaSemana 0=Domingo, 1=Lunes, ..., 6=Sábado
     * @return Turno|null
     */
    public function getTurnoParaDia(int $diaSemana): ?Turno
    {
        $dia = $this->dias()->where('dia_semana', $diaSemana)->first();
        return $dia?->turno;
    }

    /**
     * Verificar si se trabaja en un día específico de la semana
     *
     * @param int $diaSemana 0=Domingo, 1=Lunes, ..., 6=Sábado
     * @return bool
     */
    public function trabajaEnDia(int $diaSemana): bool
    {
        $dia = $this->dias()->where('dia_semana', $diaSemana)->first();
        return $dia !== null && $dia->turno_id !== null;
    }

    /**
     * Obtener array con los días que se trabaja
     *
     * @return array
     */
    public function getDiasTrabajo(): array
    {
        return $this->dias()
            ->whereNotNull('turno_id')
            ->pluck('dia_semana')
            ->toArray();
    }
}
