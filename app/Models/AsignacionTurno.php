<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AsignacionTurno extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'asignaciones_turnos'; // Asegúrate de que coincide con la tabla en la BD
    protected $fillable = [
        'user_id', 'obra_id', 'turno_id', 'estado', 'anio_cargo', 'maquina_id',
        'entrada', 'salida', 'entrada2', 'salida2', 'fecha',
        'justificante_ruta', 'horas_justificadas', 'justificante_observaciones', 'justificante_subido_at'
    ];

    protected $casts = [
        'fecha' => 'date',
        'justificante_subido_at' => 'datetime',
        'horas_justificadas' => 'decimal:2',
    ];

    /**
     * Relación con el turno (cada asignación pertenece a un turno).
     */
    public function turno()
    {
        return $this->belongsTo(Turno::class, 'turno_id');
    }
    public function obra()
    {
        return $this->belongsTo(Obra::class, 'obra_id');
    }

    /**
     * Relación con el usuario (cada asignación pertenece a un usuario).
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
