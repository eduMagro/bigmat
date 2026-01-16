<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RedirectOperario
{
    /**
     * Rutas permitidas para operarios
     */
    protected $rutasPermitidas = [
        'usuarios.show',
        'usuarios.imagen',
        'usuarios.editarSubirImagen',
        'logout',
        // Calendario y datos del perfil
        'users.verEventos-turnos',
        'users.verResumen-asistencia',
        'usuarios.getVacationData',
        // Fichaje
        'users.fichar',
        // Vacaciones
        'vacaciones.solicitar',
        'vacaciones.misSolicitudesPendientes',
        'vacaciones.eliminarDiasSolicitud',
        'vacaciones.eliminarSolicitud',
        'vacaciones.eliminarEvento',
        // Revision de fichajes
        'usuarios.fichajes-rango',
        'revision-fichaje.store',
        // Alertas
        'alertas.index',
        'alertas.show',
        'alertas.store',
        'alertas.update',
        'alertas.destroy',
        'alertas.verSinLeer',
        'alertas.marcarLeidas',
        'alertas.obtenerHilo',
    ];

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        // Si el usuario es operario y está intentando acceder a una ruta no permitida
        if ($user && $user->esOperario()) {
            $rutaActual = $request->route()->getName();

            // Verificar si la ruta actual está permitida
            if (!in_array($rutaActual, $this->rutasPermitidas)) {
                return redirect()->route('usuarios.show', $user->id);
            }
        }

        return $next($request);
    }
}
