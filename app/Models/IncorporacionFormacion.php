<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IncorporacionFormacion extends Model
{
    protected $table = 'incorporacion_formaciones';

    public $timestamps = false;

    protected $fillable = [
        'incorporacion_id',
        'tipo',
        'nombre',
        'archivo',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    // Tipos de formación para HPR Servicios
    const TIPOS_HPR = [
        'curso_20h_generico' => 'Curso 20H modalidad genérica',
        'curso_6h_ferralla' => 'Curso 6H específico (Ferralla)',
        'otros_cursos' => 'Otros cursos',
    ];

    // Tipos de formación para Hierros Paco Reyes
    const TIPOS_HIERROS = [
        'formacion_generica_puesto' => 'Formación genérica del puesto',
        'formacion_especifica_puesto' => 'Formación específica del puesto',
    ];

    public function incorporacion()
    {
        return $this->belongsTo(Incorporacion::class);
    }

    public function getTipoNombreAttribute()
    {
        $todos = array_merge(self::TIPOS_HPR, self::TIPOS_HIERROS);
        return $todos[$this->tipo] ?? $this->tipo;
    }

    public function getArchivoUrlAttribute()
    {
        return $this->archivo ? asset('storage/incorporaciones/' . $this->archivo) : null;
    }
}
