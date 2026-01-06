<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class VerificarClaveSeccion
{
    public function handle($request, Closure $next, $seccion)
    {
        if (!Session::get("clave_validada_$seccion")) {
            // Si es AJAX, responder con JSON
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['error' => 'Sección protegida por clave.'], 403);
            }
            // Redirigir con mensaje de error para SweetAlert
            return back()->with('error', 'Esta sección está protegida por clave. Introduce la clave para acceder.');
        }

        return $next($request);
    }
}
