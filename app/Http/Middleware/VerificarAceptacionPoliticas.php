<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class VerificarAceptacionPoliticas
{
    /**
     * Rutas excluidas de la verificación de políticas
     */
    protected $rutasExcluidas = [
        'logout',
        'politicas.mostrar',
        'politicas.aceptar',
        'politicas.privacidad',
        'politicas.cookies',
        'politicas.terminos',
    ];

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        // Si no hay usuario autenticado, continuar (el middleware auth se encargará)
        if (!$user) {
            return $next($request);
        }

        // Verificar si la ruta actual está excluida
        $rutaActual = $request->route()->getName();

        if ($this->esRutaExcluida($rutaActual)) {
            return $next($request);
        }

        // Verificar si el usuario ha aceptado las políticas
        if (!$user->haAceptadoPoliticas()) {
            // Si es una petición AJAX, devolver error JSON
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'error' => 'Debe aceptar las políticas de privacidad y cookies para continuar.',
                    'redirect' => route('politicas.mostrar')
                ], 403);
            }

            // Redirigir a la página de aceptación de políticas
            return redirect()->route('politicas.mostrar')
                ->with('info', 'Debe aceptar las políticas de privacidad y cookies para continuar usando la aplicación.');
        }

        return $next($request);
    }

    /**
     * Verificar si la ruta está excluida de la verificación
     */
    protected function esRutaExcluida(?string $ruta): bool
    {
        if (!$ruta) {
            return false;
        }

        // Verificar rutas exactas
        if (in_array($ruta, $this->rutasExcluidas)) {
            return true;
        }

        // Verificar patrones (api.*, sanctum.*, etc.)
        $patronesExcluidos = ['api.*', 'sanctum.*', 'livewire.*'];

        foreach ($patronesExcluidos as $patron) {
            $regex = str_replace('*', '.*', $patron);
            if (preg_match('/^' . $regex . '$/', $ruta)) {
                return true;
            }
        }

        return false;
    }
}
