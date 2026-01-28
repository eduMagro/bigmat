<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Sistema de Permisos Simplificado
    |--------------------------------------------------------------------------
    |
    | Niveles de acceso:
    | 1. ACCESO TOTAL: Usuarios en departamentos Administrador, Administración, Programador
    | 2. RESPONSABLES: Acceso a todo excepto configuración (ajustes, departamentos, etc.)
    | 3. USUARIOS BÁSICOS: Solo mi-perfil y alertas
    |
    | La lógica principal está en el middleware VerificarAccesoSeccion
    |
    */

    // Correos con acceso total (bypass de todas las restricciones)
    'correos_acceso_total' => [
        'eduardo.magro@pacoreyes.com',
    ],

];
