<?php

namespace App\Livewire;

use App\Models\AsignacionTurno;
use App\Models\Turno;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Url;
use Carbon\Carbon;

class AsignacionesTurnosTable extends Component
{
    use WithPagination;

    protected $paginationTheme = 'tailwind';

    // Filtros - usando #[Url] para mantenerlos en la URL
    #[Url]
    public $user_id = '';

    #[Url]
    public $empleado = '';

    #[Url]
    public $fecha_inicio = '';

    #[Url]
    public $fecha_fin = '';

    #[Url]
    public $obra = '';

    #[Url]
    public $turno = '';

    #[Url]
    public $entrada = '';

    #[Url]
    public $salida = '';

    #[Url]
    public $sort = 'fecha';

    #[Url]
    public $order = 'desc';

    public $perPage = 15;

    // Cuando cambia cualquier filtro, resetear a la página 1
    public function updated($property)
    {
        if ($property !== 'perPage') {
            $this->resetPage();
        }
    }

    public function sortBy($field)
    {
        if ($this->sort === $field) {
            $this->order = $this->order === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sort = $field;
            $this->order = 'asc';
        }
    }

    public function limpiarFiltros()
    {
        $this->reset([
            'user_id', 'empleado', 'fecha_inicio', 'fecha_fin', 'obra',
            'turno', 'entrada', 'salida'
        ]);
        $this->resetPage();
    }

    private function escapeLike(string $value): string
    {
        $value = str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $value);
        return "%{$value}%";
    }

    public function getFiltrosActivos()
    {
        $filtros = [];

        if (!empty($this->user_id)) {
            $filtros[] = "<strong>ID Empleado:</strong> {$this->user_id}";
        }
        if (!empty($this->empleado)) {
            $filtros[] = "<strong>Empleado:</strong> {$this->empleado}";
        }
        if (!empty($this->fecha_inicio) || !empty($this->fecha_fin)) {
            $rango = ($this->fecha_inicio ?: '—') . ' a ' . ($this->fecha_fin ?: '—');
            $filtros[] = "<strong>Fecha:</strong> {$rango}";
        }
        if (!empty($this->obra)) {
            $filtros[] = "<strong>Obra:</strong> {$this->obra}";
        }
        if (!empty($this->turno)) {
            $filtros[] = "<strong>Turno:</strong> {$this->turno}";
        }
        if (!empty($this->entrada)) {
            $filtros[] = "<strong>Entrada:</strong> {$this->entrada}";
        }
        if (!empty($this->salida)) {
            $filtros[] = "<strong>Salida:</strong> {$this->salida}";
        }

        if ($this->sort) {
            $filtros[] = '<strong>Ordenado por:</strong> ' . e($this->sort) . ' en <strong>' . ($this->order === 'asc' ? 'ascendente' : 'descendente') . '</strong>';
        }

        return $filtros;
    }

    public function aplicarFiltros($query)
    {
        // ID exacto
        if (!empty($this->user_id)) {
            $query->where('user_id', $this->user_id);
        }

        // Empleado: name + apellidos (contains)
        if (!empty($this->empleado)) {
            $like = $this->escapeLike($this->empleado);
            $query->whereHas('user', function ($q) use ($like) {
                $q->whereRaw(
                    "CONCAT_WS(' ', COALESCE(name,''), COALESCE(primer_apellido,''), COALESCE(segundo_apellido,'')) LIKE ? ESCAPE '\\\\'",
                    [$like]
                );
            });
        }

        // Rango de fechas inclusivo
        if (!empty($this->fecha_inicio) && !empty($this->fecha_fin)) {
            $ini = Carbon::parse($this->fecha_inicio)->startOfDay();
            $fin = Carbon::parse($this->fecha_fin)->endOfDay();
            $query->whereBetween('fecha', [$ini, $fin]);
        } elseif (!empty($this->fecha_inicio)) {
            $ini = Carbon::parse($this->fecha_inicio)->startOfDay();
            $query->where('fecha', '>=', $ini);
        } elseif (!empty($this->fecha_fin)) {
            $fin = Carbon::parse($this->fecha_fin)->endOfDay();
            $query->where('fecha', '<=', $fin);
        }

        // Obra (contains por nombre/columna 'obra')
        if (!empty($this->obra)) {
            $like = $this->escapeLike($this->obra);
            $query->whereHas('obra', function ($q) use ($like) {
                $q->whereRaw("obra LIKE ? ESCAPE '\\\\'", [$like]);
            });
        }

        // Turno (contains por 'nombre')
        if (!empty($this->turno)) {
            $like = $this->escapeLike($this->turno);
            $query->whereHas('turno', function ($q) use ($like) {
                $q->whereRaw("nombre LIKE ? ESCAPE '\\\\'", [$like]);
            });
        }

        // Entrada / Salida (TIME → CAST a CHAR antes del LIKE)
        if (!empty($this->entrada)) {
            $like = $this->escapeLike($this->entrada);
            $query->whereRaw("CAST(entrada AS CHAR) LIKE ? ESCAPE '\\\\'", [$like]);
        }
        if (!empty($this->salida)) {
            $like = $this->escapeLike($this->salida);
            $query->whereRaw("CAST(salida AS CHAR) LIKE ? ESCAPE '\\\\'", [$like]);
        }

        return $query;
    }

    public function render()
    {
        $query = AsignacionTurno::with(['user', 'turno', 'obra'])
            ->whereDate('fecha', '<=', Carbon::tomorrow())
            ->where('estado', 'activo')
            ->whereHas('turno', fn($q) => $q->where('nombre', '!=', 'vacaciones'))
            ->join('turnos', 'asignaciones_turnos.turno_id', '=', 'turnos.id')
            ->select('asignaciones_turnos.*');

        $query = $this->aplicarFiltros($query);

        // Aplicar ordenamiento
        $map = [
            'user_id'    => 'asignaciones_turnos.user_id',
            'fecha'      => 'asignaciones_turnos.fecha',
            'obra_id'    => 'asignaciones_turnos.obra_id',
            'turno_id'   => 'asignaciones_turnos.turno_id',
            'entrada'    => 'asignaciones_turnos.entrada',
            'salida'     => 'asignaciones_turnos.salida',
        ];

        $sortBy = array_key_exists($this->sort, $map) ? $map[$this->sort] : $map['fecha'];
        $orderDir = strtolower($this->order) === 'asc' ? 'asc' : 'desc';

        $query->reorder($sortBy, $orderDir);

        // Orden secundario estable
        if ($this->sort === 'fecha') {
            $query->orderByRaw("FIELD(turnos.nombre, 'mañana', 'tarde', 'noche')");
        }
        $query->orderBy('asignaciones_turnos.id', 'asc');

        $asignaciones = $query->paginate($this->perPage);

        // Turnos para los select
        $turnos = Turno::where('nombre', '!=', 'festivo')->orderBy('nombre')->get();

        return view('livewire.asignaciones-turnos-table', [
            'asignaciones' => $asignaciones,
            'turnos' => $turnos,
            'filtrosActivos' => $this->getFiltrosActivos(),
        ]);
    }
}
