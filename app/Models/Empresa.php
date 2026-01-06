<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Empresa extends Model
{
    use HasFactory;

    protected $table = 'empresas';

    protected $fillable = [
        'nombre',
        'direccion',
        'localidad',
        'provincia',
        'codigo_postal',
        'telefono',
        'email',
        'nif',
        'numero_ss',
    ];

    /**
     * Relación: una empresa tiene muchos empleados (usuarios).
     */
    public function empleados()
    {
        return $this->hasMany(User::class, 'empresa_id');
    }

}
