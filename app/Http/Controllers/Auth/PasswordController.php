<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use App\Models\User;

class PasswordController extends Controller
{
    /**
     * Update the user's password.
     */
    public function update(Request $request, $id): RedirectResponse
    {
        $authUser = auth()->user();

        // Verificar que solo los administradores puedan cambiar contraseñas
        if ($authUser->rol !== 'oficina') {
            return redirect()->route('dashboard')->with('error', 'No tienes permiso para actualizar contraseñas.');
        }

        // Validar la solicitud
        $validated = $request->validate([
            'password' => ['required', Password::defaults(), 'confirmed'],
        ]);

        // Encontrar el usuario a actualizar
        $user = User::findOrFail($id);

        // Actualizar la contraseña
        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        return back()->with('status', 'password-updated');
    }
}
