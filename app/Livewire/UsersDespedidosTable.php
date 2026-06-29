<?php

namespace App\Livewire;

use App\Models\Categoria;
use App\Models\Empresa;
use App\Models\User;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class UsersDespedidosTable extends Component
{
    use WithPagination;

    protected $paginationTheme = 'tailwind';

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
    public $dni = '';

    #[Url]
    public $empresa_id = '';

    #[Url]
    public $rol = '';

    #[Url]
    public $categoria_id = '';

    #[Url]
    public $sort = 'fecha_baja';

    #[Url]
    public $order = 'desc';

    #[Url]
    public $perPage = 25;

    public function mount(): void
    {
        $allowed = [10, 25, 50, 100];
        $stored  = (int) session('users_despedidos.per_page', $this->perPage);
        $this->perPage = in_array($stored, $allowed, true) ? $stored : 25;
    }

    public function updated($property): void
    {
        if (in_array($property, ['sort', 'order'], true)) {
            return;
        }
        $this->resetPage();
    }

    public function updatedPerPage($value): void
    {
        $allowed = [10, 25, 50, 100];
        $parsed  = (int) $value;
        $this->perPage = in_array($parsed, $allowed, true) ? $parsed : 25;
        session(['users_despedidos.per_page' => $this->perPage]);
        $this->resetPage();
    }

    public function sortBy(string $field): void
    {
        if ($this->sort === $field) {
            $this->order = $this->order === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sort  = $field;
            $this->order = 'asc';
        }
    }

    public function limpiarFiltros(): void
    {
        $this->reset([
            'user_id', 'filtro_name', 'filtro_primer_apellido', 'filtro_segundo_apellido',
            'email', 'dni', 'empresa_id', 'rol', 'categoria_id',
        ]);
        $this->resetPage();
    }

    private function escapeLike(string $value): string
    {
        return '%' . str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $value) . '%';
    }

    public function getFiltrosActivos(): array
    {
        $filtros = [];

        if (!empty($this->user_id))                $filtros[] = "<strong>ID:</strong> {$this->user_id}";
        if (!empty($this->filtro_name))            $filtros[] = "<strong>Nombre:</strong> {$this->filtro_name}";
        if (!empty($this->filtro_primer_apellido)) $filtros[] = "<strong>Primer Apellido:</strong> {$this->filtro_primer_apellido}";
        if (!empty($this->filtro_segundo_apellido))$filtros[] = "<strong>Segundo Apellido:</strong> {$this->filtro_segundo_apellido}";
        if (!empty($this->email))                  $filtros[] = "<strong>Email:</strong> {$this->email}";
        if (!empty($this->dni))                    $filtros[] = "<strong>DNI:</strong> {$this->dni}";
        if (!empty($this->rol))                    $filtros[] = "<strong>Rol:</strong> {$this->rol}";

        if (!empty($this->empresa_id)) {
            $empresa   = Empresa::find($this->empresa_id);
            $filtros[] = "<strong>Empresa:</strong> " . ($empresa->nombre ?? "ID {$this->empresa_id}");
        }
        if (!empty($this->categoria_id)) {
            $categoria = Categoria::find($this->categoria_id);
            $filtros[] = "<strong>Categoría:</strong> " . ($categoria->nombre ?? "ID {$this->categoria_id}");
        }

        return $filtros;
    }

    public function render()
    {
        $query = User::withoutGlobalScopes()
            ->where('users.estado', 'despedido')
            ->select('users.*');

        if (!empty($this->user_id)) {
            $query->where('users.id', $this->user_id);
        }
        if (!empty($this->filtro_name)) {
            $query->whereRaw("users.name LIKE ? ESCAPE '\\\\'", [$this->escapeLike($this->filtro_name)]);
        }
        if (!empty($this->filtro_primer_apellido)) {
            $query->whereRaw("users.primer_apellido LIKE ? ESCAPE '\\\\'", [$this->escapeLike($this->filtro_primer_apellido)]);
        }
        if (!empty($this->filtro_segundo_apellido)) {
            $query->whereRaw("users.segundo_apellido LIKE ? ESCAPE '\\\\'", [$this->escapeLike($this->filtro_segundo_apellido)]);
        }
        if (!empty($this->email)) {
            $query->whereRaw("users.email LIKE ? ESCAPE '\\\\'", [$this->escapeLike($this->email)]);
        }
        if (!empty($this->dni)) {
            $query->whereRaw("users.dni LIKE ? ESCAPE '\\\\'", [$this->escapeLike($this->dni)]);
        }
        if (!empty($this->empresa_id)) {
            $query->where('users.empresa_id', $this->empresa_id);
        }
        if (!empty($this->categoria_id)) {
            $query->where('users.categoria_id', $this->categoria_id);
        }
        if (!empty($this->rol)) {
            $query->whereRaw("users.rol LIKE ? ESCAPE '\\\\'", [$this->escapeLike($this->rol)]);
        }

        $orderDir = strtolower($this->order) === 'desc' ? 'desc' : 'asc';

        if ($this->sort === 'empresa') {
            $query->leftJoin('empresas', 'empresas.id', '=', 'users.empresa_id');
        }
        if ($this->sort === 'categoria') {
            $query->leftJoin('categorias', 'categorias.id', '=', 'users.categoria_id');
        }

        switch ($this->sort) {
            case 'nombre_completo':
                $query->orderBy('users.name', $orderDir)
                      ->orderBy('users.primer_apellido', $orderDir)
                      ->orderBy('users.id', $orderDir);
                break;
            case 'empresa':
                $query->orderBy('empresas.nombre', $orderDir);
                break;
            case 'categoria':
                $query->orderBy('categorias.nombre', $orderDir);
                break;
            case 'fecha_baja':
                $query->orderByRaw("users.fecha_baja IS NULL {$orderDir}, users.fecha_baja {$orderDir}");
                break;
            default:
                $query->orderBy("users.{$this->sort}", $orderDir);
                break;
        }

        $registros = $query->paginate($this->perPage);
        $registros->load(['empresa', 'categoria']);

        return view('livewire.users-despedidos-table', [
            'registros'     => $registros,
            'empresas'      => Empresa::orderBy('nombre')->get(),
            'categorias'    => Categoria::orderBy('nombre')->get(),
            'roles'         => ['operario', 'oficina', 'transportista', 'visitante'],
            'filtrosActivos' => $this->getFiltrosActivos(),
        ]);
    }
}
