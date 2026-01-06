<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Modelo145 extends Model
{
    use HasFactory;

    protected $table = 'modelo_145';

    protected $fillable = [
        'user_id',
        'estado_civil',
        'hijos_a_cargo',
        'hijos_menores_3',
        'ascendientes_mayores_65',
        'ascendientes_mayores_75',
        'discapacidad_porcentaje',
        'discapacidad_familiares',
        'contrato_indefinido',
        'fecha_declaracion',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
