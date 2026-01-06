<?php

namespace App\Services;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Cache;

class MenuBuilder
{
    /**
     * Construye el menú filtrado para el usuario actual
     */
    public static function buildForUser($user)
    {
        if (!$user) {
            return [];
        }

        // Cachear el menú por usuario durante 30 minutos
        return Cache::remember("menu_user_{$user->id}", 1800, function () use ($user) {
            $menu = config('menu.main');

            if (!$menu || !is_array($menu)) {
                return [];
            }

            $filteredMenu = [];

            foreach ($menu as $section) {
                $filteredSection = self::filterSection($section, $user);
                if ($filteredSection) {
                    $filteredMenu[] = $filteredSection;
                }
            }

            return $filteredMenu;
        });
    }

    /**
     * Filtra una sección del menú según permisos del usuario
     * Los items sin permiso se marcan como 'disabled' en lugar de ocultarse
     */
    private static function filterSection($section, $user)
    {
        // No verificamos la ruta de la sección principal (ej: secciones.produccion)
        // porque son rutas virtuales. Solo verificamos los items del submenu.

        $filteredSubmenu = [];
        $hasAccessibleItems = false;

        if (isset($section['submenu'])) {
            foreach ($section['submenu'] as $item) {
                $filteredItem = $item;
                $canAccess = self::userCanAccessRoute($item['route'], $user);

                // Marcar como disabled si no tiene acceso
                $filteredItem['disabled'] = !$canAccess;

                if ($canAccess) {
                    $hasAccessibleItems = true;

                    // Filtrar acciones según permisos
                    if (isset($item['actions'])) {
                        $filteredItem['actions'] = array_filter($item['actions'], function ($action) use ($user) {
                            return self::userCanAccessRoute($action['route'], $user);
                        });
                    }
                }

                $filteredSubmenu[] = $filteredItem;
            }
        }

        // Si no tiene ningún submenú accesible, no mostrar la sección
        if (!$hasAccessibleItems) {
            return null;
        }

        $section['submenu'] = $filteredSubmenu;
        return $section;
    }

    /**
     * Verifica si el usuario puede acceder a una ruta
     */
    private static function userCanAccessRoute($routeName, $user)
    {
        // Verificar si la ruta existe
        if (!Route::has($routeName)) {
            return false;
        }

        // Acceso total para correos específicos
        $emailsAccesoTotal = config('acceso.correos_acceso_total', []);
        if (in_array(strtolower(trim($user->email)), $emailsAccesoTotal)) {
            return true;
        }

        // Aquí se puede integrar con el sistema de permisos existente
        // Por ahora, permitimos acceso básico según rol

        // Operarios solo ven módulos específicos
        if ($user->rol === 'operario') {
            $prefijosOperario = config('acceso.prefijos_operario_dashboard', []);
            foreach ($prefijosOperario as $prefijo) {
                if ($routeName === $prefijo || str_starts_with($routeName, $prefijo)) {
                    return true;
                }
            }
            return false;
        }

        // Transportistas solo ven módulos específicos
        if ($user->rol === 'transportista') {
            $prefijosTransportista = config('acceso.prefijos_transportista', []);
            return in_array($routeName, $prefijosTransportista, true);
        }

        // Oficina: verificar permisos en base de datos
        if ($user->rol === 'oficina') {
            return self::checkDatabasePermissions($routeName, $user);
        }

        return false;
    }

    /**
     * Verifica permisos en base de datos
     */
    private static function checkDatabasePermissions($routeName, $user)
    {
        // Extraer el identificador base de la ruta (ej: "maquinas" de "maquinas.index")
        $routeParts = explode('.', $routeName);
        $baseRoute = $routeParts[0];

        // Buscar la sección correspondiente
        $seccion = \DB::table('secciones')
            ->where('ruta', 'LIKE', "{$baseRoute}%")
            ->orWhere('ruta', $routeName)
            ->first();

        if (!$seccion) {
            // Si no hay sección específica, permitir acceso a secciones principales
            if (str_starts_with($routeName, 'secciones.')) {
                return true;
            }
            return false;
        }

        // Verificar permiso directo del usuario
        $permisoDirecto = \DB::table('permisos_acceso')
            ->where('user_id', $user->id)
            ->where('seccion_id', $seccion->id)
            ->where('puede_ver', true)
            ->exists();

        if ($permisoDirecto) {
            return true;
        }

        // Verificar permisos heredados por departamento
        $departamentosUsuario = $user->departamentos->pluck('id')->toArray();

        if (empty($departamentosUsuario)) {
            return false;
        }

        $permisoPorDept = \DB::table('departamento_seccion')
            ->whereIn('departamento_id', $departamentosUsuario)
            ->where('seccion_id', $seccion->id)
            ->exists();

        return $permisoPorDept;
    }

    /**
     * Obtiene la ruta actual y genera breadcrumbs
     */
    public static function getBreadcrumbs()
    {
        $currentRoute = Route::currentRouteName();
        $breadcrumbs = [
            ['label' => 'Dashboard', 'route' => 'dashboard']
        ];

        $menu = config('menu.main');

        foreach ($menu as $section) {
            if ($section['route'] === $currentRoute) {
                $breadcrumbs[] = ['label' => $section['label'], 'route' => $section['route']];
                return $breadcrumbs;
            }

            if (isset($section['submenu'])) {
                foreach ($section['submenu'] as $item) {
                    if ($item['route'] === $currentRoute) {
                        $breadcrumbs[] = ['label' => $section['label'], 'route' => $section['route']];
                        $breadcrumbs[] = ['label' => $item['label'], 'route' => $item['route']];
                        return $breadcrumbs;
                    }

                    if (isset($item['actions'])) {
                        foreach ($item['actions'] as $action) {
                            if ($action['route'] === $currentRoute) {
                                $breadcrumbs[] = ['label' => $section['label'], 'route' => $section['route']];
                                $breadcrumbs[] = ['label' => $item['label'], 'route' => $item['route']];
                                $breadcrumbs[] = ['label' => $action['label'], 'route' => null];
                                return $breadcrumbs;
                            }
                        }
                    }
                }
            }
        }

        return $breadcrumbs;
    }

    /**
     * Limpia el caché del menú de un usuario
     */
    public static function clearUserCache($userId)
    {
        Cache::forget("menu_user_{$userId}");
    }

    /**
     * Limpia el caché del menú de todos los usuarios
     */
    public static function clearAllCache()
    {
        // Esto requeriría iterar sobre todos los usuarios
        // o usar tags de cache si el driver lo soporta
        Cache::flush();
    }
}
