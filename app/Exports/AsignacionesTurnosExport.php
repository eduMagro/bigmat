<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class AsignacionesTurnosExport implements FromCollection, WithHeadings
{
    protected $asignaciones;

    public function __construct($asignaciones)
    {
        $this->asignaciones = $asignaciones;
    }

    public function collection()
    {
        // devolvemos solo los campos que queremos
        return $this->asignaciones->map(function ($asignacion) {
            return [
                'ID Empleado'   => $asignacion->user->id,
                'Empleado'      => trim($asignacion->user->name . ' ' . $asignacion->user->primer_apellido . ' ' . $asignacion->user->segundo_apellido),
                'Fecha'         => $asignacion->fecha,
                'Lugar'         => $asignacion->obra->obra ?? '—',
                'Turno'         => $asignacion->turno->nombre ?? '—',
                'Máquina'       => $asignacion->maquina->nombre ?? '—',
                'Entrada'       => $asignacion->entrada ?? '—',
                'Salida'        => $asignacion->salida ?? '—',
            ];
        });
    }

    public function headings(): array
    {
        return [
            'ID Empleado',
            'Empleado',
            'Fecha',
            'Lugar',
            'Turno',
            'Máquina',
            'Entrada',
            'Salida',
        ];
    }
}
