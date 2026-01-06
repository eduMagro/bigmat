<?php

namespace App\Exports;

use App\Models\User;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Illuminate\Database\Eloquent\Collection;

class UsersExport implements FromCollection, WithHeadings, WithMapping
{
    protected $usuarios;

    public function __construct(Collection $usuarios)
    {
        $this->usuarios = $usuarios;
    }

    public function collection()
    {
        return $this->usuarios;
    }

    public function map($user): array
    {
        return [
            $user->id,
            $user->name,
            $user->primer_apellido,
            $user->segundo_apellido,
            $user->email,
            $user->empresa->nombre ?? '—',
            $user->movil_personal,
            $user->movil_empresa,
            $user->dni,
            $user->rol,
            $user->categoria->nombre ?? '—',
        ];
    }

    public function headings(): array
    {
        return [
            'ID',
            'Nombre',
            'Primer Apellido',
            'Segundo Apellido',
            'Email',
            'Empresa',
            'Móvil Personal',
            'Móvil Empresa',
            'DNI',
            'Rol',
            'Categoría',
        ];
    }
}
