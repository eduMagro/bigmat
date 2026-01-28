<?php

namespace App\Livewire;

use App\Models\User;
use Livewire\Component;
use Livewire\Attributes\Lazy;
use Illuminate\Support\Facades\Cache;

#[Lazy]
class UsersTableMobile extends Component
{
    public function placeholder()
    {
        return view('livewire.placeholders.users-mobile');
    }

    public function render()
    {
        $usuarioActual = auth()->user();
        $cacheKey = 'users_mobile_' . ($usuarioActual?->id ?? 'guest');

        $contactosAgenda = Cache::remember($cacheKey, 300, function () use ($usuarioActual) {
            return User::select([
                    'id', 'name', 'primer_apellido', 'segundo_apellido',
                    'email', 'movil_personal', 'movil_empresa', 'numero_corto',
                    'dni', 'empresa_id', 'categoria_id', 'rol', 'turno', 'imagen'
                ])
                ->visiblesPara($usuarioActual)
                ->with(['empresa:id,nombre', 'categoria:id,nombre'])
                ->orderBy('name')
                ->orderBy('primer_apellido')
                ->get()
                ->map(fn($user) => [
                    'id' => $user->id,
                    'nombre' => $user->name,
                    'primer_apellido' => $user->primer_apellido,
                    'segundo_apellido' => $user->segundo_apellido,
                    'nombre_completo' => trim("{$user->name} {$user->primer_apellido} {$user->segundo_apellido}"),
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
                ])
                ->values();
        });

        return view('livewire.users-table-mobile', [
            'contactosAgenda' => $contactosAgenda,
        ]);
    }
}
