<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RedirectOperario
{
    /**
     * Handle an incoming request.
     *
     * Este middleware actúa como respaldo de seguridad.
     * La lógica principal de permisos está en VerificarAccesoSeccion.
     *
     * Redirige a usuarios básicos (no admin, no responsable) al perfil
     * si intentan acceder al dashboard u otras rutas principales.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if (!$user) {
            return $next($request);
        }

        // Si tiene acceso total o es responsable, continuar normalmente
        if ($user->tieneAccesoTotal() || $user->esResponsableDepartamento()) {
            return $next($request);
        }

        // Usuarios básicos: redirigir desde dashboard a su perfil
        $rutaActual = $request->route()->getName();

        if ($rutaActual === 'dashboard') {
            return redirect()->route('usuarios.show', $user->id);
        }

        return $next($request);
    }
}
