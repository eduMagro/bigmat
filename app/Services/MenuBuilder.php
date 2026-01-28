<?php

namespace App\Services;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class MenuBuilder
{
    /**
     * Rutas de configuración (restringidas para responsables)
     */
    private static $rutasConfiguracion = [
        'ajustes.',
        'departamentos.',
        'secciones.',
        'irpf-tramos.',
        'seguridad-social.',
        'convenios.',
    ];

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

            // Usuarios básicos (no admin, no responsable) no ven el menú
            if (!$user->tieneAccesoTotal() && !$user->esResponsableDepartamento()) {
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
     */
    private static function filterSection($section, $user)
    {
        $filteredSubmenu = [];
        $hasAccessibleItems = false;

        if (isset($section['submenu'])) {
            foreach ($section['submenu'] as $item) {
                $filteredItem = $item;
                $canAccess = self::userCanAccessRoute($item['route'], $user);

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

        if (!$hasAccessibleItems) {
            return null;
        }

        $section['submenu'] = $filteredSubmenu;
        return $section;
    }

    /**
     * Verifica si el usuario puede acceder a una ruta
     *
     * Sistema simplificado:
     * 1. ACCESO TOTAL: Administrador, Administración, Programador -> todo
     * 2. RESPONSABLES: Todo excepto configuración
     * 3. USUARIOS BÁSICOS: Solo mi-perfil y alertas (no ven menú)
     */
    private static function userCanAccessRoute($routeName, $user)
    {
        // Verificar si la ruta existe
        if (!Route::has($routeName)) {
            return false;
        }

        // Acceso total por correo específico
        $emailsAccesoTotal = config('acceso.correos_acceso_total', []);
        if (in_array(strtolower(trim($user->email)), $emailsAccesoTotal)) {
            return true;
        }

        // === 1) ACCESO TOTAL: Administrador, Administración, Programador ===
        if ($user->tieneAccesoTotal()) {
            return true;
        }

        // === 2) RESPONSABLES: Todo excepto configuración ===
        if ($user->esResponsableDepartamento()) {
            // Verificar si es ruta de configuración
            foreach (self::$rutasConfiguracion as $rutaConfig) {
                if (Str::startsWith($routeName, $rutaConfig)) {
                    return false;
                }
            }
            return true;
        }

        // === 3) USUARIOS BÁSICOS: No tienen acceso al menú ===
        return false;
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
        Cache::flush();
    }
}
