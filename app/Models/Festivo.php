<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Festivo extends Model
{
    use HasFactory;

    protected $table = 'festivos';

    protected $fillable = [
        'titulo',
        'fecha',
        'anio',
    ];

    protected $casts = [
        'fecha' => 'date',
    ];

    // Scope para filtrar por año
    public function scopeDelAnio($query, $anio)
    {
        return $query->where('anio', $anio);
    }

    /**
     * Devuelve los eventos de festivos listos para FullCalendar
     */
    //     $eventos = Festivo::eventosCalendario();        // año actual
    //     $eventos = Festivo::eventosCalendario(2026);    // año concreto
    public static function eventosCalendario(?int $anio = null)
    {
        $anio = $anio ?? (int) date('Y');

        return self::delAnio($anio)
            ->orderBy('fecha')
            ->get(['id', 'titulo', 'fecha', 'anio'])
            ->map(function ($f) {
                return [
                    'id'              => 'festivo-' . $f->id,
                    'title'           => $f->titulo,
                    'start'           => $f->fecha->toDateString(),
                    'allDay'          => true,
                    'backgroundColor' => '#ff0000',
                    'borderColor'     => '#b91c1c',
                    'textColor'       => 'white',
                    'editable'        => true,
                    'classNames'      => ['fc-event-festivo'],
                    'extendedProps'   => [
                        'festivo_id' => $f->id,
                        'tipo'       => 'festivo',
                        'anio'       => $f->anio ?? (int) $f->fecha->format('Y'),
                    ],
                ];
            });
    }
}
