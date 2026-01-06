<?php

namespace App\Livewire;

use App\Models\User;
use Livewire\Component;

class UsersTableMobile extends Component
{
    public function render()
    {
        $contactosAgenda = User::with(['empresa', 'categoria'])
            ->orderBy('name')
            ->orderBy('primer_apellido')
            ->orderBy('segundo_apellido')
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'nombre' => $user->name,
                    'primer_apellido' => $user->primer_apellido,
                    'segundo_apellido' => $user->segundo_apellido,
                    'nombre_completo' => $user->nombre_completo,
                    'email' => $user->email,
                    'movil_personal' => $user->movil_personal,
                    'movil_empresa' => $user->movil_empresa,
                    'numero_corto' => $user->numero_corto,
                    'dni' => $user->dni,
                    'empresa' => $user->empresa->nombre ?? null,
                    'categoria' => $user->categoria->nombre ?? null,
                    'rol' => $user->rol,
                    'turno' => $user->turno,
                    'imagen' => $user->rutaImagen,
                ];
            })
            ->values();

        return view('livewire.users-table-mobile', [
            'contactosAgenda' => $contactosAgenda,
        ]);
    }
}
