<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class RoleMiddleware
{
    /**
     * Manejar una solicitud entrante.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  $role
     * @return mixed
     */
    public function handle($request, Closure $next, $role)
    {
        if (!Auth::check() || Auth::user()->role !== $role) {
            // Si es AJAX, responder con JSON
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['error' => 'Acceso denegado'], 403);
            }
            // Redirigir con mensaje de error para SweetAlert
            return back()->with('error', 'No tienes permiso para acceder a esta sección.');
        }

        return $next($request);
    }
}
