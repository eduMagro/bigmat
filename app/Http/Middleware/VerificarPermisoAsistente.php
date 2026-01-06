<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerificarPermisoAsistente
{
    /**
     * Verifica que el usuario tenga permiso para usar el asistente virtual
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        // Verificar que el usuario esté autenticado
        if (!$user) {
            return response()->json([
                'success' => false,
                'error' => 'No autenticado'
            ], 401);
        }

        // Verificar que tenga permiso para usar el asistente
        if (!$user->puede_usar_asistente) {
            return response()->json([
                'success' => false,
                'error' => 'No tienes permisos para usar el asistente virtual. Contacta con un administrador.'
            ], 403);
        }

        return $next($request);
    }
}
