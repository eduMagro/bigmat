<?php

namespace App\Livewire;

use App\Models\User;
use App\Models\Empresa;
use App\Models\Categoria;
use App\Models\Obra;
use App\Models\Turno;
use App\Models\AgrupacionTurno;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Url;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class UsersTable extends Component
{
    use WithPagination;

    protected $paginationTheme = 'tailwind';

    // Filtros - usando #[Url] para mantenerlos en la URL
    #[Url]
    public $user_id = '';

    #[Url]
    public $filtro_name = '';

    #[Url]
    public $filtro_primer_apellido = '';

    #[Url]
    public $filtro_segundo_apellido = '';

    #[Url]
    public $email = '';

    #[Url]
    public $movil_personal = '';

    #[Url]
    public $movil_empresa = '';

    #[Url]
    public $numero_corto = '';

    #[Url]
    public $dni = '';

    #[Url]
    public $empresa_id = '';

    #[Url]
    public $rol = '';

    #[Url]
    public $categoria_id = '';

    #[Url]
    public $turno = '';

    #[Url]
    public $estado = '';

    #[Url]
    public $sort = 'id';

    #[Url]
    public $order = 'asc';

    public $perPage = 15;

    // Cache keys
    private const CACHE_TTL = 300; // 5 minutos

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
            'user_id', 'filtro_name', 'filtro_primer_apellido', 'filtro_segundo_apellido', 'email', 'movil_personal', 'movil_empresa',
            'numero_corto', 'dni', 'empresa_id', 'rol', 'categoria_id',
            'turno', 'estado'
        ]);
        $this->resetPage();
    }

    private function escapeLike(string $value): string
    {
        $value = str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $value);
        return "%{$value}%";
    }

    /**
     * Obtener empresas cacheadas
     */
    private function getEmpresas()
    {
        return Cache::remember('users_table_empresas', self::CACHE_TTL, function () {
            return Empresa::orderBy('nombre')->get(['id', 'nombre']);
        });
    }

    /**
     * Obtener categorías cacheadas
     */
    private function getCategorias()
    {
        return Cache::remember('users_table_categorias', self::CACHE_TTL, function () {
            return Categoria::orderBy('nombre')->get(['id', 'nombre']);
        });
    }

    /**
     * Obtener turnos cacheados
     */
    private function getTurnos()
    {
        return Cache::remember('users_table_turnos', self::CACHE_TTL, function () {
            return Turno::where('activo', true)->ordenados()->get();
        });
    }

    /**
     * Obtener obras cacheadas
     */
    private function getObras()
    {
        return Cache::remember('users_table_obras', self::CACHE_TTL, function () {
            return Obra::select('id', 'obra')->orderBy('obra')->get();
        });
    }

    /**
     * Obtener agrupaciones de turnos con sus días (cacheado)
     */
    private function getAgrupacionesTurnos()
    {
        return Cache::remember('users_table_agrupaciones', self::CACHE_TTL, function () {
            return AgrupacionTurno::activas()->ordenadas()->with('dias.turno')->get();
        });
    }

    /**
     * Preparar datos de plantillas para el modal JS (cacheado)
     */
    private function getPlantillasParaModal()
    {
        return Cache::remember('users_table_plantillas_modal', self::CACHE_TTL, function () {
            $agrupacionesTurnos = $this->getAgrupacionesTurnos();
            $diasAbrev = [0 => 'D', 1 => 'L', 2 => 'M', 3 => 'X', 4 => 'J', 5 => 'V', 6 => 'S'];

            return $agrupacionesTurnos->map(function($a) use ($diasAbrev) {
                $dias = [];
                foreach ([1, 2, 3, 4, 5, 6, 0] as $d) {
                    $diaConfig = $a->dias->firstWhere('dia_semana', $d);
                    $dias[$d] = [
                        'abrev' => $diasAbrev[$d],
                        'turno' => $diaConfig && $diaConfig->turno ? $diaConfig->turno->nombre : null,
                        'color' => $diaConfig && $diaConfig->turno ? $diaConfig->turno->color : null
                    ];
                }
                return [
                    'id' => $a->id,
                    'nombre' => $a->nombre,
                    'descripcion' => $a->descripcion,
                    'dias' => $dias
                ];
            });
        });
    }

    public function getFiltrosActivos()
    {
        $filtros = [];

        if (!empty($this->user_id)) {
            $filtros[] = "<strong>ID:</strong> {$this->user_id}";
        }
        if (!empty($this->filtro_name)) {
            $filtros[] = "<strong>Nombre:</strong> {$this->filtro_name}";
        }
        if (!empty($this->filtro_primer_apellido)) {
            $filtros[] = "<strong>Primer Apellido:</strong> {$this->filtro_primer_apellido}";
        }
        if (!empty($this->filtro_segundo_apellido)) {
            $filtros[] = "<strong>Segundo Apellido:</strong> {$this->filtro_segundo_apellido}";
        }
        if (!empty($this->email)) {
            $filtros[] = "<strong>Email:</strong> {$this->email}";
        }
        if (!empty($this->movil_personal)) {
            $filtros[] = "<strong>Móvil Personal:</strong> {$this->movil_personal}";
        }
        if (!empty($this->movil_empresa)) {
            $filtros[] = "<strong>Móvil Empresa:</strong> {$this->movil_empresa}";
        }
        if (!empty($this->numero_corto)) {
            $filtros[] = "<strong>Nº Corporativo:</strong> {$this->numero_corto}";
        }
        if (!empty($this->dni)) {
            $filtros[] = "<strong>DNI:</strong> {$this->dni}";
        }
        if (!empty($this->empresa_id)) {
            // Usar datos cacheados en lugar de query adicional
            $empresas = $this->getEmpresas();
            $empresa = $empresas->firstWhere('id', $this->empresa_id);
            $filtros[] = "<strong>Empresa:</strong> " . ($empresa->nombre ?? "ID {$this->empresa_id}");
        }
        if (!empty($this->categoria_id)) {
            // Usar datos cacheados en lugar de query adicional
            $categorias = $this->getCategorias();
            $categoria = $categorias->firstWhere('id', $this->categoria_id);
            $filtros[] = "<strong>Categoría:</strong> " . ($categoria->nombre ?? "ID {$this->categoria_id}");
        }
        if (!empty($this->turno)) {
            $filtros[] = "<strong>Turno:</strong> {$this->turno}";
        }
        if (!empty($this->rol)) {
            $filtros[] = "<strong>Rol:</strong> {$this->rol}";
        }
        if (!empty($this->estado)) {
            $filtros[] = "<strong>Estado:</strong> " . ucfirst($this->estado);
        }

        // Añadir ordenamiento
        if (!empty($this->sort)) {
            $nombresCampos = [
                'id' => 'ID',
                'nombre_completo' => 'Nombre',
                'email' => 'Email',
                'numero_corto' => 'Nº Corporativo',
                'dni' => 'DNI',
                'empresa' => 'Empresa',
                'rol' => 'Rol',
                'categoria' => 'Categoría',
                'turno' => 'Turno',
                'estado' => 'Estado',
            ];

            $nombreCampo = $nombresCampos[$this->sort] ?? ucfirst($this->sort);
            $direccion = $this->order === 'asc' ? '↑ Ascendente' : '↓ Descendente';
            $filtros[] = "<strong>Ordenado por:</strong> {$nombreCampo} ({$direccion})";
        }

        return $filtros;
    }

    public function aplicarFiltros($query)
    {
        if (!empty($this->user_id)) {
            $query->where('users.id', $this->user_id);
        }

        if (!empty($this->filtro_name)) {
            $like = $this->escapeLike($this->filtro_name);
            $query->whereRaw("users.name LIKE ? ESCAPE '\\\\'", [$like]);
        }

        if (!empty($this->filtro_primer_apellido)) {
            $like = $this->escapeLike($this->filtro_primer_apellido);
            $query->whereRaw("users.primer_apellido LIKE ? ESCAPE '\\\\'", [$like]);
        }

        if (!empty($this->filtro_segundo_apellido)) {
            $like = $this->escapeLike($this->filtro_segundo_apellido);
            $query->whereRaw("users.segundo_apellido LIKE ? ESCAPE '\\\\'", [$like]);
        }

        if (!empty($this->email)) {
            $like = $this->escapeLike($this->email);
            $query->whereRaw("users.email LIKE ? ESCAPE '\\\\'", [$like]);
        }

        if (!empty($this->movil_personal)) {
            $like = $this->escapeLike($this->movil_personal);
            $query->whereRaw("users.movil_personal LIKE ? ESCAPE '\\\\'", [$like]);
        }

        if (!empty($this->movil_empresa)) {
            $like = $this->escapeLike($this->movil_empresa);
            $query->whereRaw("users.movil_empresa LIKE ? ESCAPE '\\\\'", [$like]);
        }

        if (!empty($this->numero_corto)) {
            $like = $this->escapeLike($this->numero_corto);
            $query->whereRaw("CAST(users.numero_corto AS CHAR) LIKE ? ESCAPE '\\\\'", [$like]);
        }

        if (!empty($this->dni)) {
            $like = $this->escapeLike($this->dni);
            $query->whereRaw("users.dni LIKE ? ESCAPE '\\\\'", [$like]);
        }

        if (!empty($this->empresa_id)) {
            $query->where('users.empresa_id', $this->empresa_id);
        }

        if (!empty($this->categoria_id)) {
            $query->where('users.categoria_id', $this->categoria_id);
        }

        if (!empty($this->turno)) {
            $like = $this->escapeLike($this->turno);
            $query->whereRaw("users.turno LIKE ? ESCAPE '\\\\'", [$like]);
        }

        if (!empty($this->rol)) {
            $like = $this->escapeLike($this->rol);
            $query->whereRaw("users.rol LIKE ? ESCAPE '\\\\'", [$like]);
        }

        if (!empty($this->estado)) {
            $query->where('users.estado', $this->estado);
        }

        return $query;
    }

    public function render()
    {
        $query = User::query()->select('users.*');

        $query = $this->aplicarFiltros($query);

        // Joins necesarios para ordenamiento
        if ($this->sort === 'empresa') {
            $query->leftJoin('empresas', 'empresas.id', '=', 'users.empresa_id');
        }
        if ($this->sort === 'categoria') {
            $query->leftJoin('categorias', 'categorias.id', '=', 'users.categoria_id');
        }

        // Aplicar ordenamiento
        $orderDir = strtolower($this->order) === 'desc' ? 'desc' : 'asc';

        // Manejar ordenamiento según columna
        switch ($this->sort) {
            case 'nombre_completo':
                $query->orderByRaw("CONCAT_WS(' ', users.name, users.primer_apellido, users.segundo_apellido) {$orderDir}");
                break;
            case 'numero_corto':
                $query->orderByRaw("CAST(users.numero_corto AS UNSIGNED) {$orderDir}");
                break;
            case 'empresa':
                $query->orderBy('empresas.nombre', $orderDir);
                break;
            case 'categoria':
                $query->orderBy('categorias.nombre', $orderDir);
                break;
            case 'email':
                $query->orderBy('users.email', $orderDir);
                break;
            case 'dni':
                $query->orderBy('users.dni', $orderDir);
                break;
            case 'rol':
                $query->orderBy('users.rol', $orderDir);
                break;
            case 'turno':
                $query->orderBy('users.turno', $orderDir);
                break;
            case 'estado':
                $query->orderBy('users.estado', $orderDir);
                break;
            default:
                $query->orderBy('users.id', $orderDir);
                break;
        }

        // Paginar con eager loading optimizado
        $registrosUsuarios = $query->with(['empresa:id,nombre', 'categoria:id,nombre'])->paginate($this->perPage);

        return view('livewire.users-table', [
            'registrosUsuarios' => $registrosUsuarios,
            'empresas' => $this->getEmpresas(),
            'categorias' => $this->getCategorias(),
            'roles' => ['operario', 'oficina', 'transportista', 'visitante'],
            'turnos' => $this->getTurnos(),
            'plantillasParaModal' => $this->getPlantillasParaModal(),
            'filtrosActivos' => $this->getFiltrosActivos(),
            'obras' => $this->getObras(),
        ]);
    }
}
