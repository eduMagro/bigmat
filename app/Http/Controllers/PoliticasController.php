<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class PoliticasController extends Controller
{
    /**
     * Mostrar la página de aceptación de políticas
     */
    public function mostrar()
    {
        $user = Auth::user();

        // Si ya aceptó, redirigir al dashboard o perfil
        if ($user->haAceptadoPoliticas()) {
            return $this->redirigirSegunRol($user);
        }

        return view('politicas.aceptar', [
            'user' => $user
        ]);
    }

    /**
     * Procesar la aceptación de políticas
     */
    public function aceptar(Request $request)
    {
        $request->validate([
            'acepta_privacidad' => 'required|accepted',
            'acepta_cookies' => 'required|accepted',
            'acepta_terminos' => 'required|accepted',
        ], [
            'acepta_privacidad.required' => 'Debe aceptar la política de privacidad.',
            'acepta_privacidad.accepted' => 'Debe aceptar la política de privacidad.',
            'acepta_cookies.required' => 'Debe aceptar la política de cookies.',
            'acepta_cookies.accepted' => 'Debe aceptar la política de cookies.',
            'acepta_terminos.required' => 'Debe aceptar los términos y condiciones.',
            'acepta_terminos.accepted' => 'Debe aceptar los términos y condiciones.',
        ]);

        $user = Auth::user();

        $user->update([
            'acepta_politica_privacidad' => true,
            'acepta_politica_cookies' => true,
            'fecha_aceptacion_politicas' => Carbon::now()->toDateString(),
        ]);

        return $this->redirigirSegunRol($user)
            ->with('success', 'Gracias por aceptar nuestras políticas. Ya puede utilizar la aplicación.');
    }

    /**
     * Mostrar política de privacidad (pública)
     */
    public function privacidad()
    {
        return view('politicas.privacidad');
    }

    /**
     * Mostrar política de cookies (pública)
     */
    public function cookies()
    {
        return view('politicas.cookies');
    }

    /**
     * Mostrar términos y condiciones (público)
     */
    public function terminos()
    {
        return view('politicas.terminos');
    }

    /**
     * Revocar aceptación de políticas
     * Según RGPD, el usuario puede retirar su consentimiento en cualquier momento
     */
    public function revocar(Request $request)
    {
        $user = Auth::user();

        $user->update([
            'acepta_politica_privacidad' => false,
            'acepta_politica_cookies' => false,
            'fecha_aceptacion_politicas' => null,
        ]);

        // Cerrar sesión ya que no puede usar la app sin aceptar
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')
            ->with('info', 'Has revocado tu consentimiento. Para volver a usar la aplicación, deberás aceptar las políticas nuevamente.');
    }

    /**
     * Redirigir al usuario según su rol
     */
    protected function redirigirSegunRol($user)
    {
        if ($user->esOperario()) {
            return redirect()->route('usuarios.show', $user->id);
        }

        return redirect()->route('dashboard');
    }
}
