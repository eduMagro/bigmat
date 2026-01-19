<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Turno extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'turnos';

    protected $fillable = [
        'nombre',
        'hora_inicio',
        'hora_fin',
        'offset_dias_inicio',
        'offset_dias_fin',
        'activo',
        'orden',
        'color',
        'color_texto',
        // Campos para turno partido
        'es_partido',
        'hora_inicio2',
        'hora_fin2',
        'offset_dias_inicio2',
        'offset_dias_fin2',
        // Campos legacy (mantener compatibilidad)
        'hora_entrada',
        'entrada_offset',
        'hora_salida',
        'salida_offset',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'es_partido' => 'boolean',
        'orden' => 'integer',
        'offset_dias_inicio' => 'integer',
        'offset_dias_fin' => 'integer',
        'offset_dias_inicio2' => 'integer',
        'offset_dias_fin2' => 'integer',
    ];

    public function asignaciones()
    {
        return $this->hasMany(AsignacionTurno::class, 'turno_id');
    }

    /**
     * Scope para obtener solo turnos activos
     */
    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    /**
     * Scope para ordenar turnos
     */
    public function scopeOrdenados($query)
    {
        return $query->orderBy('orden')->orderBy('hora_inicio');
    }
}
