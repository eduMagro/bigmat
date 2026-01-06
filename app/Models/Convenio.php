<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Convenio extends Model
{
    use HasFactory;

    protected $table = 'convenio';

    protected $fillable = [
        'categoria_id',
        'salario_base',
        'liquido_minimo_pactado',
        'plus_asistencia',
        'plus_actividad',
        'plus_productividad',
        'plus_absentismo',
        'plus_transporte',
        'prorrateo_pagasextras'
    ];

    public $timestamps = false;

    public function categoria()
    {
        return $this->belongsTo(Categoria::class);
    }
}
