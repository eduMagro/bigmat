<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use App\Models\Seccion;
use App\Models\Departamento;

/**
 * 📌 Devuelve el prefijo base de una ruta Laravel (antes del primer punto).
 * Ejemplo: 'usuarios.index' → 'usuarios.'
 */
if (!function_exists('obtenerPrefijoBaseDeRuta')) {
    function obtenerPrefijoBaseDeRuta(string $nombreRutaActual): string
    {
        $prefijoBase = Str::before($nombreRutaActual, '.');
        return $prefijoBase !== '' ? strtolower($prefijoBase) . '.' : '';
    }
}

/**
 * 📌 Comprueba si el usuario autenticado tiene acceso a una ruta.
 * - Usa la configuración en config/acceso.php
 * - Deniega por defecto para roles no reconocidos
 */
if (!function_exists('usuarioTieneAcceso')) {
    function usuarioTieneAcceso(?string $nombreRutaActual): bool
    {
        $usuarioAutenticado = Auth::user();
        if (!$usuarioAutenticado || !$nombreRutaActual) {
            return false;
        }

        $rolUsuario = strtolower((string) $usuarioAutenticado->rol);

        // === Admin: acceso total
        if (in_array($rolUsuario, ['admin', 'administrador'], true)) {
            return true;
        }

        // === Operario: por prefijos configurados
        if ($rolUsuario === 'operario') {
            $prefijosPermitidosOperario = config('acceso.prefijos_operario', []);

            return collect($prefijosPermitidosOperario)->contains(
                fn(string $prefijoPermitido) =>
                $nombreRutaActual === $prefijoPermitido
                    || Str::startsWith($nombreRutaActual, $prefijoPermitido)
            );
        }

        // === Oficina: validación por departamentos ↔ secciones
        if ($rolUsuario === 'oficina') {
            $prefijoBaseRuta = obtenerPrefijoBaseDeRuta($nombreRutaActual);
            if ($prefijoBaseRuta === '') {
                return false;
            }

            $seccion = Seccion::with('departamentos')
                ->whereRaw('LOWER(ruta) = ?', [strtolower($prefijoBaseRuta)])
                ->first();

            if (!$seccion) {
                return false;
            }

            $departamentosUsuario = $usuarioAutenticado->departamentos()->pluck('id')->toArray();
            if (empty($departamentosUsuario)) {
                return false;
            }

            $departamentosSeccion = $seccion->departamentos->pluck('id')->toArray();

            return count(array_intersect($departamentosUsuario, $departamentosSeccion)) > 0;
        }

        // === Cualquier otro rol → denegar por defecto
        return false;
    }
}
