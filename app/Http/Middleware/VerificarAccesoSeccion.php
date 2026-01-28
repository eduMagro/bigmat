<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class VerificarAccesoSeccion
{
    /**
     * Rutas de configuración (restringidas para responsables)
     */
    protected $rutasConfiguracion = [
        'ajustes.',
        'departamentos.',
        'secciones.',
        'irpf-tramos.',
        'seguridad-social.',
        'convenios.',
    ];

    /**
     * Rutas permitidas para usuarios básicos (no admin, no responsable)
     */
    protected $rutasBasicas = [
        // Mi perfil
        'usuarios.show',
        'usuarios.imagen',
        'usuarios.editarSubirImagen',
        'users.verEventos-turnos',
        'users.verResumen-asistencia',
        'usuarios.getVacationData',
        // Fichaje
        'users.fichar',
        // Vacaciones (solicitar)
        'vacaciones.solicitar',
        'vacaciones.misSolicitudesPendientes',
        'vacaciones.eliminarDiasSolicitud',
        'vacaciones.eliminarSolicitud',
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
        // Políticas
        'politicas.privacidad',
        'politicas.cookies',
        'politicas.terminos',
        'politicas.mostrar',
        'politicas.aceptar',
        // Logout
        'logout',
        // Contrato propio
        'incorporaciones.descargarMiContrato',
        // Nóminas propias
        'nominas.crearDescargarMes',
    ];

    /**
     * Deniega el acceso con redirect o JSON según el tipo de petición
     */
    private function denegarAcceso(Request $request, string $mensaje)
    {
        Log::debug('🚫 Acceso denegado', [
            'mensaje' => $mensaje,
            'url' => $request->fullUrl(),
            'usuario' => Auth::user()?->email,
        ]);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['error' => $mensaje, 'message' => $mensaje], 403);
        }

        // Redirigir a mi-perfil si no tiene acceso
        $user = Auth::user();
        if ($user) {
            return redirect()->route('usuarios.show', $user->id)->with('error', $mensaje);
        }

        return redirect()->route('login')->with('error', $mensaje);
    }

    public function handle(Request $request, Closure $next): mixed
    {
        $usuario = Auth::user();

        if (!$usuario) {
            return $this->denegarAcceso($request, 'No autenticado.');
        }

        $nombreRuta = $request->route()?->getName() ?? '';

        // === 0) BYPASS: Correos con acceso total ===
        $correosAccesoTotal = config('acceso.correos_acceso_total', []);
        if (in_array(strtolower(trim($usuario->email)), $correosAccesoTotal, true)) {
            return $next($request);
        }

        // === 1) ACCESO TOTAL: Administrador, Administración, Programador ===
        if ($usuario->tieneAccesoTotal()) {
            return $next($request);
        }

        // === 2) RESPONSABLES: Acceso a todo EXCEPTO configuración ===
        if ($usuario->esResponsableDepartamento()) {
            // Verificar si intenta acceder a rutas de configuración
            foreach ($this->rutasConfiguracion as $rutaConfig) {
                if (Str::startsWith($nombreRuta, $rutaConfig)) {
                    return $this->denegarAcceso($request, 'No tienes acceso a la configuración del sistema.');
                }
            }
            // Tiene acceso a todo lo demás
            return $next($request);
        }

        // === 3) USUARIOS BÁSICOS: Solo mi-perfil y alertas ===
        // Verificar si la ruta está en las permitidas
        foreach ($this->rutasBasicas as $rutaPermitida) {
            if ($nombreRuta === $rutaPermitida || Str::startsWith($nombreRuta, $rutaPermitida . '.')) {
                return $next($request);
            }
        }

        // Si llegamos aquí, el usuario no tiene permiso
        return $this->denegarAcceso($request, 'No tienes permiso para acceder a esta sección.');
    }
}
