<?php

namespace App\Services;

use App\Models\Seccion;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class SeccionAutoDetectService
{
    /**
     * Prefijos que deben ignorarse (rutas libres, APIs, etc.)
     */
    protected array $prefijosIgnorados = [
        'api',
        'sanctum',
        'livewire',
        'ignition',
        'password',
        'verification',
        'login',
        'logout',
        'register',
        'politica',
        'politicas',
        'ayuda',
        'dashboard',
        'profile',
        'proteger',
        'verificar-seccion',
        'incorporacion', // rutas públicas
        'secciones', // gestión de secciones (admin)
        'departamentos', // gestión de departamentos (admin)
        'fcm', // Firebase Cloud Messaging
    ];

    /**
     * Obtiene todos los prefijos de rutas del sistema agrupados
     */
    public function obtenerPrefijosRutas(): array
    {
        $rutas = Route::getRoutes();
        $prefijos = [];

        foreach ($rutas as $ruta) {
            $nombre = $ruta->getName();

            if (!$nombre || !Str::contains($nombre, '.')) {
                continue;
            }

            // Extraer el prefijo (primera parte antes del punto)
            $prefijo = Str::before($nombre, '.');

            // Ignorar prefijos de la lista
            if (in_array(strtolower($prefijo), $this->prefijosIgnorados)) {
                continue;
            }

            // Ignorar rutas que empiezan con guión bajo o son internas
            if (Str::startsWith($prefijo, '_')) {
                continue;
            }

            if (!isset($prefijos[$prefijo])) {
                $prefijos[$prefijo] = [
                    'prefijo' => $prefijo,
                    'rutas' => [],
                    'total_rutas' => 0,
                ];
            }

            $prefijos[$prefijo]['rutas'][] = $nombre;
            $prefijos[$prefijo]['total_rutas']++;
        }

        // Ordenar por nombre
        ksort($prefijos);

        return $prefijos;
    }

    /**
     * Compara prefijos detectados con secciones existentes
     */
    public function compararConSecciones(): array
    {
        $prefijos = $this->obtenerPrefijosRutas();
        $secciones = Seccion::all();

        // Crear un mapa de prefijos a secciones (soporta múltiples formatos)
        $seccionesPorPrefijo = [];
        $seccionesUsadas = [];

        foreach ($secciones as $seccion) {
            $ruta = strtolower($seccion->ruta);

            // Extraer el prefijo de la ruta (primera parte antes del punto)
            $prefijo = Str::before($ruta, '.');

            if (!isset($seccionesPorPrefijo[$prefijo])) {
                $seccionesPorPrefijo[$prefijo] = $seccion;
            }
        }

        $resultado = [
            'con_seccion' => [],
            'sin_seccion' => [],
            'secciones_huerfanas' => [],
        ];

        // Verificar qué prefijos tienen/no tienen sección
        foreach ($prefijos as $prefijo => $datos) {
            $prefijoLower = strtolower($prefijo);

            if (isset($seccionesPorPrefijo[$prefijoLower])) {
                $seccion = $seccionesPorPrefijo[$prefijoLower];
                $resultado['con_seccion'][] = [
                    'prefijo' => $prefijo,
                    'total_rutas' => $datos['total_rutas'],
                    'seccion_id' => $seccion->id,
                    'seccion_nombre' => $seccion->nombre,
                ];
                $seccionesUsadas[] = $seccion->id;
            } else {
                $resultado['sin_seccion'][] = [
                    'prefijo' => $prefijo,
                    'total_rutas' => $datos['total_rutas'],
                    'nombre_sugerido' => $this->generarNombreSugerido($prefijo),
                ];
            }
        }

        // Secciones que no corresponden a ningún prefijo actual
        foreach ($secciones as $seccion) {
            if (!in_array($seccion->id, $seccionesUsadas)) {
                $resultado['secciones_huerfanas'][] = [
                    'id' => $seccion->id,
                    'nombre' => $seccion->nombre,
                    'ruta' => $seccion->ruta,
                ];
            }
        }

        return $resultado;
    }

    /**
     * Genera un nombre legible para un prefijo
     */
    public function generarNombreSugerido(string $prefijo): string
    {
        $mapeo = [
            'alertas' => 'Alertas',
            'asignaciones-turnos' => 'Asignación de Turnos',
            'camiones' => 'Camiones',
            'categorias' => 'Categorías',
            'clientes' => 'Clientes',
            'clientes-almacen' => 'Clientes Almacén',
            'coladas' => 'Coladas',
            'convenios' => 'Convenios',
            'departamentos' => 'Departamentos',
            'distribuidores' => 'Distribuidores',
            'elementos' => 'Elementos',
            'empresas' => 'Empresas',
            'empresas-transporte' => 'Empresas de Transporte',
            'ensamblaje' => 'Ensamblaje',
            'entradas' => 'Entradas de Material',
            'epis' => 'EPIs',
            'estadisticas' => 'Estadísticas',
            'etiquetas' => 'Etiquetas',
            'etiquetas-ensamblaje' => 'Etiquetas de Ensamblaje',
            'fabricacion' => 'Fabricación',
            'fabricantes' => 'Fabricantes',
            'festivos' => 'Festivos',
            'funciones' => 'Funciones',
            'incorporaciones' => 'Incorporaciones',
            'incidencias' => 'Incidencias',
            'inventario-backups' => 'Backups de Inventario',
            'irpf-tramos' => 'Tramos IRPF',
            'localizaciones' => 'Localizaciones',
            'maquinas' => 'Máquinas',
            'movimientos' => 'Movimientos',
            'nominas' => 'Nóminas',
            'obras' => 'Obras',
            'paquetes' => 'Paquetes',
            'pedidos' => 'Pedidos',
            'pedidos-almacen-venta' => 'Pedidos Almacén',
            'pedidos_globales' => 'Pedidos Globales',
            'planificacion' => 'Planificación',
            'planillas' => 'Planillas',
            'porcentajes-ss' => 'Porcentajes SS',
            'porcentajesSS' => 'Porcentajes SS',
            'precios-material' => 'Precios de Material',
            'produccion' => 'Producción',
            'production' => 'Logs de Producción',
            'productos' => 'Productos',
            'productos-base' => 'Productos Base',
            'salidas' => 'Salidas',
            'salidas-almacen' => 'Salidas Almacén',
            'salidas-ferralla' => 'Salidas Ferralla',
            'secciones' => 'Secciones',
            'subetiquetas' => 'Sub-etiquetas',
            'tramosIrpf' => 'Tramos IRPF',
            'turnos' => 'Turnos',
            'ubicaciones' => 'Ubicaciones',
            'users' => 'Usuarios',
            'usuarios' => 'Usuarios',
            'vacaciones' => 'Vacaciones',
            'trabajadores-ficticios' => 'Trabajadores Ficticios',
            'eventos-ficticios-obra' => 'Eventos Ficticios Obra',
            'revision-fichaje' => 'Revisión de Fichajes',
            'documentos-empleado' => 'Documentos de Empleado',
            'papelera' => 'Papelera',
            'atajos' => 'Atajos de Teclado',
            'albaranes' => 'Albaranes',
            'mapa' => 'Mapa de Paquetes',
        ];

        if (isset($mapeo[$prefijo])) {
            return $mapeo[$prefijo];
        }

        // Generar nombre automáticamente
        return Str::title(str_replace(['-', '_'], ' ', $prefijo));
    }

    /**
     * Crea secciones para los prefijos que no tienen
     */
    public function crearSeccionesFaltantes(): array
    {
        $comparacion = $this->compararConSecciones();
        $creadas = [];

        foreach ($comparacion['sin_seccion'] as $item) {
            $seccion = Seccion::create([
                'nombre' => $item['nombre_sugerido'],
                'ruta' => $item['prefijo'] . '.',
                'mostrar_en_dashboard' => false,
            ]);

            $creadas[] = [
                'id' => $seccion->id,
                'nombre' => $seccion->nombre,
                'ruta' => $seccion->ruta,
            ];
        }

        return $creadas;
    }

    /**
     * Obtiene estadísticas del sistema de permisos
     */
    public function obtenerEstadisticas(): array
    {
        $comparacion = $this->compararConSecciones();

        return [
            'total_prefijos' => count($comparacion['con_seccion']) + count($comparacion['sin_seccion']),
            'con_seccion' => count($comparacion['con_seccion']),
            'sin_seccion' => count($comparacion['sin_seccion']),
            'secciones_huerfanas' => count($comparacion['secciones_huerfanas']),
            'cobertura' => count($comparacion['con_seccion']) > 0
                ? round((count($comparacion['con_seccion']) / (count($comparacion['con_seccion']) + count($comparacion['sin_seccion']))) * 100, 1)
                : 0,
        ];
    }
}
