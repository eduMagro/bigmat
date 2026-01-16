<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Models\Seccion;
use App\Models\PermisoAcceso;

class VerificarAccesoSeccion
{
    /**
     * Deniega el acceso con redirect o JSON según el tipo de petición
     */
    private function denegarAcceso(Request $request, string $mensaje)
    {
        Log::debug('🚫 Acceso denegado', [
            'mensaje' => $mensaje,
            'url' => $request->fullUrl(),
            'ajax' => $request->ajax(),
            'expectsJson' => $request->expectsJson(),
        ]);

        // Si es petición AJAX o espera JSON
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['error' => $mensaje, 'message' => $mensaje], 403);
        }

        // Redirect al dashboard con mensaje de error
        return redirect()->route('dashboard')->with('error', $mensaje);
    }

    public function handle(Request $request, Closure $next): mixed
    {
        $usuarioAutenticado = Auth::user();
        if (!$usuarioAutenticado) {
            return $this->denegarAcceso($request, 'No autenticado.');
        }

        $correoUsuario = strtolower(trim($usuarioAutenticado->email));
        $nombreRutaActual = $request->route()?->getName() ?? '';
        $rolUsuario = strtolower((string) $usuarioAutenticado->rol);

        // === 1) Acceso total por correo (desde config/acceso.php) ===
        $correosAccesoTotal = config('acceso.correos_acceso_total', []);
        if (in_array($correoUsuario, $correosAccesoTotal, true)) {
            return $next($request);
        }

        // === 2) Acceso total para usuarios del departamento Administrador ===
        $esAdministrador = $usuarioAutenticado->departamentos()
            ->whereRaw('LOWER(nombre) = ?', ['administrador'])
            ->exists();

        if ($esAdministrador) {
            return $next($request);
        }

        // === 3) Rutas libres (desde config/acceso.php) ===
        $rutasLibres = config('acceso.rutas_libres', []);
        if (in_array($nombreRutaActual, $rutasLibres, true)) {
            return $next($request);
        }

        // === 4) Roles y permisos ===
        if ($rolUsuario === 'operario') {
            $prefijosOperario = config('acceso.prefijos_operario', []);
            $permitido = collect($prefijosOperario)->contains(
                fn($prefijo) => $nombreRutaActual === $prefijo || Str::startsWith($nombreRutaActual, $prefijo)
            );

            if (!$permitido) {
                Log::info('🚫 Ruta denegada para operario', [
                    'usuario' => $usuarioAutenticado->email,
                    'ruta' => $nombreRutaActual,
                ]);
                return $this->denegarAcceso($request, 'No tienes permiso para acceder.');
            }
            return $next($request);
        }

        if ($rolUsuario === 'transportista') {
            $prefijosTransportista = config('acceso.prefijos_transportista', []);
            $permitido = collect($prefijosTransportista)->contains(
                fn($prefijo) => $nombreRutaActual === $prefijo || Str::startsWith($nombreRutaActual, $prefijo)
            );

            if (!$permitido) {
                Log::info('🚫 Ruta denegada para transportista', [
                    'usuario' => $usuarioAutenticado->email,
                    'ruta' => $nombreRutaActual,
                ]);
                return $this->denegarAcceso($request, 'No tienes permiso para acceder.');
            }
            return $next($request);
        }

        // === 5) Usuarios de oficina - verificar secciones y departamentos ===
        if ($rolUsuario === 'oficina') {
            $accionRuta = strtolower(Str::afterLast($nombreRutaActual, '.'));
            $seccionBase = Str::before($nombreRutaActual, '.');

            // Buscar sección exacta primero, luego por prefijo
            $seccion = Seccion::whereRaw('LOWER(ruta) = ?', [strtolower($nombreRutaActual)])->first();
            if (!$seccion) {
                $seccion = Seccion::whereRaw('LOWER(ruta) LIKE ?', [strtolower($seccionBase) . '.%'])->first();
            }
            if (!$seccion) {
                Log::warning('❌ Ruta sin sección registrada', ['ruta' => $nombreRutaActual]);
                return $this->denegarAcceso($request, "La sección '{$seccionBase}' no está registrada en el sistema.");
            }

            // === Verificar acceso por DEPARTAMENTO ===
            $departamentosUsuario = $usuarioAutenticado->departamentos->pluck('id')->toArray();
            $departamentosSeccion = $seccion->departamentos->pluck('id')->toArray();
            $tieneAccesoPorDepartamento = !empty(array_intersect($departamentosUsuario, $departamentosSeccion));

            // Si NO tiene acceso por departamento, denegar
            if (!$tieneAccesoPorDepartamento) {
                Log::debug('❌ Sin acceso por departamento', [
                    'usuario' => $usuarioAutenticado->email,
                    'seccion' => $seccion->ruta,
                    'departamentos_usuario' => $departamentosUsuario,
                    'departamentos_seccion' => $departamentosSeccion,
                ]);
                return $this->denegarAcceso($request, "No tienes acceso a la sección '{$seccion->nombre}'.");
            }

            // === Verificar permisos granulares (ver/crear/editar) ===
            $permisos = PermisoAcceso::where('user_id', $usuarioAutenticado->id)
                ->where('seccion_id', $seccion->id)
                ->first();

            // Si no tiene permisos específicos, solo permitir VER (index/show)
            if (!$permisos) {
                $esAccionVer = in_array($accionRuta, ['index', 'show'])
                    || Str::startsWith($accionRuta, 'ver')
                    || Str::startsWith($accionRuta, 'show');

                if ($esAccionVer) {
                    Log::debug('✅ Acceso por departamento (solo ver)', [
                        'usuario' => $usuarioAutenticado->email,
                        'seccion' => $seccion->ruta,
                    ]);
                    return $next($request);
                }

                Log::debug('❌ Sin permisos para crear/editar', [
                    'usuario' => $usuarioAutenticado->email,
                    'seccion' => $seccion->ruta,
                    'accion' => $accionRuta,
                ]);
                return $this->denegarAcceso($request, "No tienes permisos para realizar esta acción en '{$seccion->nombre}'.");
            }

            // Convertir a collection para mantener compatibilidad con el código existente
            $permisos = collect([$permisos]);

            $autorizado = false;
            foreach ($permisos as $permiso) {
                if (
                    // Permiso de VER
                    (in_array($accionRuta, ['index', 'show']) || Str::startsWith($accionRuta, 'ver') || Str::startsWith($accionRuta, 'show')) && $permiso->puede_ver

                    // Permiso de CREAR
                    || (in_array($accionRuta, ['create', 'store']) || Str::startsWith($accionRuta, 'crear') || Str::startsWith($accionRuta, 'store')) && $permiso->puede_crear

                    // Permiso de EDITAR
                    || (in_array($accionRuta, ['edit', 'update', 'destroy'])
                        || Str::startsWith($accionRuta, 'editar')
                        || Str::startsWith($accionRuta, 'actualizar')
                        || Str::startsWith($accionRuta, 'update')
                        || Str::startsWith($accionRuta, 'destroy')
                        || Str::startsWith($accionRuta, 'delete')
                        || Str::startsWith($accionRuta, 'eliminar')
                        || Str::startsWith($accionRuta, 'activar')
                    ) && $permiso->puede_editar
                ) {
                    $autorizado = true;
                    break;
                }
            }
            if (!$autorizado) {
                Log::warning('❌ Acción no autorizada', [
                    'usuario' => $usuarioAutenticado->email,
                    'ruta' => $nombreRutaActual,
                    'accion' => $accionRuta,
                    'seccion' => $seccionBase,
                ]);
                return $this->denegarAcceso($request, 'No tienes permisos suficientes para realizar esta acción.');
            }

            return $next($request);
        }

        // === 5) Denegación por defecto (rol no reconocido) ===
        Log::warning('🚫 Ruta denegada - rol no reconocido', [
            'usuario' => $usuarioAutenticado->email,
            'ruta' => $nombreRutaActual,
            'rol' => $rolUsuario,
        ]);
        return $this->denegarAcceso($request, 'No tienes permiso para acceder.');
    }
}
