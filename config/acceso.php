<?php

return [

    // 📌 Prefijos que los operarios pueden usar en rutas (middleware)
    'prefijos_operario' => [
        'produccion.trabajadores',
        'users.',
        'usuarios.show',
        'usuarios.imagen',
        'usuarios.editarSubirImagen',
        'users.verEventos-turnos',
        'users.verResumen-asistencia',
        'alertas.',
        'productos.',
        'pedidos.',
        'ayuda.',
        'maquinas.',
        'etiquetas.',      // ✅ acceso para fabricar etiquetas
        'elementos.',      // ✅ acceso para dividir/cambiar máquina elementos
        'subetiquetas.',   // ✅ acceso para crear/mover subetiquetas
        'paquetes.',       // ✅ acceso para crear/gestionar paquetes
        'localizaciones.', // ✅ acceso para ubicar paquetes en mapa
        'api.',            // ✅ acceso a rutas API (productos, paquetes, etc.)
        'entradas.',       // ✅ acceso permitido
        'movimientos.',    // ✅ acceso permitido
        'ubicaciones.',
        'inventario-backups.', // ✅ acceso para ver backups de inventario
        'incorporaciones.descargarMiContrato', // ✅ acceso para descargar su contrato
        'usuarios.getVacationData', // ✅ acceso para ver datos de vacaciones propios
    ],

    // 📌 Prefijos que deben salir en el dashboard para operarios
    'prefijos_operario_dashboard' => [
        'produccion.trabajadores',
        'users.',
        'alertas.',
        'productos.',
        'pedidos.',
        'ayuda.',
        'maquinas.',
        'ubicaciones.',
        // 👀 aquí NO ponemos movimientos ni entradas
    ],


    // 📌 Prefijos permitidos para TRANSPORTISTAS
    'prefijos_transportista' => [
        'users.',
        'alertas.',
        'vacaciones.solicitar',
        'planificacion.index',
        'usuarios.editarSubirImagen',
        'usuarios.imagen',
        'nominas.crearDescargarMes',
    ],

    // 📌 Rutas libres (si las necesitas en helpers también)
    'rutas_libres' => [
        'politica.privacidad',
        'politica.cookies',
        'politicas.aceptar',
        'ayuda.index',
        'usuarios.show',
        'usuarios.index',
        'usuarios.imagen',
        'usuarios.editarSubirImagen',
        'nominas.crearDescargarMes',
        'incorporaciones.descargarMiContrato', // ✅ descargar mi contrato
        'turno.cambiarMaquina',
        'salida.completarDesdeMovimiento',
        'alertas.index',
        'alertas.store',
        'alertas.update',
        'alertas.destroy',
        'alertas.marcarLeidas',
        'alertas.verSinLeer',
        'vacaciones.solicitar',
        'users.verEventos-turnos',
        'users.verResumen-asistencia',
        'usuarios.getVacationData', // ✅ datos de vacaciones propios
        'api.mapaNave', // ✅ mapa de nave (usado en grúa y otras vistas)
    ],

    // 📌 Correos con acceso total
    'correos_acceso_total' => [
        'eduardo.magro@pacoreyes.com',
        'edumagrolemus@hotmail.com',

    ],

];
