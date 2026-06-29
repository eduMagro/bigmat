<?php

namespace App\Livewire;

use App\Models\User;
use App\Services\ComputoHorasService;
use Carbon\Carbon;
use Livewire\Component;
use Livewire\Attributes\Url;

class ResumenHorasMensual extends Component
{
    #[Url]
    public $anio;

    #[Url]
    public $mes;

    #[Url]
    public $buscar = '';

    public function mount()
    {
        $this->anio = $this->anio ?: (int) Carbon::now()->year;
        $this->mes = $this->mes ?: (int) Carbon::now()->month;
    }

    public function mesAnterior()
    {
        $fecha = Carbon::create((int) $this->anio, (int) $this->mes, 1)->subMonth();
        $this->anio = $fecha->year;
        $this->mes = $fecha->month;
    }

    public function mesSiguiente()
    {
        $fecha = Carbon::create((int) $this->anio, (int) $this->mes, 1)->addMonth();
        $this->anio = $fecha->year;
        $this->mes = $fecha->month;
    }

    public function render()
    {
        $auth = auth()->user();

        $query = User::where('estado', 'activo')
            ->visiblesPara($auth)
            ->with('categoria')
            ->orderBy('name');

        if (!empty($this->buscar)) {
            $like = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $this->buscar) . '%';
            $query->whereRaw(
                "CONCAT_WS(' ', COALESCE(name,''), COALESCE(primer_apellido,''), COALESCE(segundo_apellido,'')) LIKE ? ESCAPE '\\\\'",
                [$like]
            );
        }

        $trabajadores = $query->get();

        $resumen = ComputoHorasService::resumenMensual(
            $trabajadores->pluck('id'),
            (int) $this->anio,
            (int) $this->mes
        );

        $filas = $trabajadores->map(function ($t) use ($resumen) {
            $r = $resumen[$t->id] ?? [];
            return [
                'id'              => $t->id,
                'nombre'          => $t->nombre_completo ?? $t->name,
                'foto'            => $t->ruta_imagen ?? null,
                'categoria'       => $t->categoria?->nombre,
                'horas'           => $r['horas'] ?? 0,
                'dias_trabajados' => $r['dias_trabajados'] ?? 0,
                'dias_incompletos' => $r['dias_incompletos'] ?? 0,
                'vacaciones'      => $r['vacaciones'] ?? 0,
                'baja'            => $r['baja'] ?? 0,
                'justificada'     => $r['justificada'] ?? 0,
                'injustificada'   => $r['injustificada'] ?? 0,
                'curso'           => $r['curso'] ?? 0,
            ];
        });

        $meses = [
            1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
            5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
            9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
        ];

        $anioActual = (int) Carbon::now()->year;

        return view('livewire.resumen-horas-mensual', [
            'filas'      => $filas,
            'totalHoras' => $filas->sum('horas'),
            'meses'      => $meses,
            'anios'      => range($anioActual, $anioActual - 5),
        ]);
    }
}
