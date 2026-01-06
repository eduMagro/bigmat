<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Epi extends Model
{
    protected $table = 'epis';

    protected $fillable = [
        'codigo',
        'nombre',
        'categoria',
        'descripcion',
        'imagen_path',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    public function usuarios()
    {
        return $this->belongsToMany(User::class, 'epis_usuario')
            ->withPivot(['id', 'cantidad', 'entregado_en', 'devuelto_en', 'notas'])
            ->withTimestamps();
    }

    public function asignaciones()
    {
        return $this->hasMany(EpiUsuario::class, 'epi_id');
    }
}

