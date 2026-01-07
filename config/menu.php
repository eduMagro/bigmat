<?php

return [
    'main' => [
        [
            'id' => 'rrhh',
            'label' => 'Recursos Humanos',
            'icon' => '👥',
            'route' => 'dashboard',
            'color' => 'indigo',
            'submenu' => [
                [
                    'label' => 'Usuarios',
                    'route' => 'users.index',
                    'icon' => '👤',
                    'actions' => [
                        ['label' => 'Ver tabla usuarios', 'route' => 'users.index', 'permission' => 'ver'],
                    ]
                ],
                [
                    'label' => 'Incorporaciones',
                    'route' => 'incorporaciones.index',
                    'icon' => '📋',
                    'actions' => [
                        ['label' => 'Ver todas', 'route' => 'incorporaciones.index', 'permission' => 'ver'],
                        ['label' => 'Nueva incorporación', 'route' => 'incorporaciones.create', 'permission' => 'crear'],
                    ]
                ],
                [
                    'label' => 'Registrar Usuario',
                    'route' => 'register',
                    'icon' => '➕',
                    'actions' => [
                        ['label' => 'Crear nuevo usuario', 'route' => 'register', 'permission' => 'crear'],
                    ]
                ],
                [
                    'label' => 'Vacaciones',
                    'route' => 'vacaciones.index',
                    'icon' => '🌴',
                    'actions' => [
                        ['label' => 'Ver todas', 'route' => 'vacaciones.index', 'permission' => 'ver'],
                    ]
                ],
                [
                    'label' => 'Registros Entrada/Salida',
                    'route' => 'asignaciones-turnos.index',
                    'icon' => '🕐',
                    'actions' => [
                        ['label' => 'Ver registros', 'route' => 'asignaciones-turnos.index', 'permission' => 'ver'],
                    ]
                ],
                [
                    'label' => 'Nóminas',
                    'route' => 'nominas.index',
                    'icon' => '💰',
                    'actions' => [
                        ['label' => 'Ver nóminas', 'route' => 'nominas.index', 'permission' => 'ver'],
                    ]
                ],
                [
                    'label' => 'EPIs',
                    'route' => 'epis.index',
                    'icon' => '🦺',
                    'actions' => [
                        ['label' => 'Ver todos', 'route' => 'epis.index', 'permission' => 'ver'],
                    ]
                ],
                [
                    'label' => 'Planificacion',
                    'route' => 'planificacion.trabajadores',
                    'icon' => '📅',
                    'actions' => [
                        ['label' => 'Ver calendario', 'route' => 'planificacion.trabajadores', 'permission' => 'ver'],
                    ]
                ],
            ]
        ],
        [
            'id' => 'configuracion',
            'label' => 'Configuración',
            'icon' => '⚙️',
            'route' => 'empresas.index',
            'color' => 'gray',
            'submenu' => [
                [
                    'label' => 'Turnos',
                    'route' => 'turnos.index',
                    'icon' => '🕐',
                    'actions' => [
                        ['label' => 'Ver todos', 'route' => 'turnos.index', 'permission' => 'ver'],
                        ['label' => 'Nuevo turno', 'route' => 'turnos.create', 'permission' => 'crear'],
                    ]
                ],
                [
                    'label' => 'Departamentos y permisos',
                    'route' => 'departamentos.index',
                    'icon' => '🏛️',
                    'actions' => [
                        ['label' => 'Ver todos', 'route' => 'departamentos.index', 'permission' => 'ver'],
                        ['label' => 'Nuevo departamento', 'route' => 'departamentos.create', 'permission' => 'crear'],
                    ]
                ],
                [
                    'label' => 'Festivos',
                    'route' => 'festivos.index',
                    'icon' => '📅',
                    'actions' => [
                        ['label' => 'Ver festivos', 'route' => 'festivos.index', 'permission' => 'ver'],
                    ]
                ],
                [
                    'label' => 'Empresa',
                    'route' => 'empresas.index',
                    'icon' => '🏢',
                    'actions' => [
                        ['label' => 'Ver configuración', 'route' => 'empresas.index', 'permission' => 'ver'],
                    ]
                ],
            ]
        ],
    ],

    // Menús contextuales para cada módulo
    'context_menus' => [

        // RECURSOS HUMANOS
        'usuarios' => [
            'items' => [
                ['label' => 'Todos', 'route' => 'users.index', 'icon' => '👤'],
                ['label' => 'Nuevo Usuario', 'route' => 'register', 'icon' => '➕'],
            ],
            'config' => [
                'colorBase' => 'indigo',
                'style' => 'tabs',
                'mobileLabel' => 'Usuarios',
                'checkRole' => 'oficina',
            ]
        ],

        'incorporaciones' => [
            'items' => [
                ['label' => 'Todas', 'route' => 'incorporaciones.index', 'icon' => '📋'],
                ['label' => 'Nueva Incorporación', 'route' => 'incorporaciones.create', 'icon' => '➕'],
            ],
            'config' => [
                'colorBase' => 'indigo',
                'style' => 'tabs',
                'mobileLabel' => 'Incorporaciones',
            ]
        ],

        'departamentos' => [
            'items' => [
                ['label' => 'Todos', 'route' => 'departamentos.index', 'icon' => '🏛️'],
                ['label' => 'Nuevo Departamento', 'route' => 'departamentos.create', 'icon' => '➕'],
            ],
            'config' => [
                'colorBase' => 'indigo',
                'style' => 'tabs',
                'mobileLabel' => 'Dptos. y permisos',
            ]
        ],

        'vacaciones' => [
            'items' => [
                ['label' => 'Todas', 'route' => 'vacaciones.index', 'icon' => '🌴'],
            ],
            'config' => [
                'colorBase' => 'indigo',
                'style' => 'tabs',
                'mobileLabel' => 'Vacaciones',
            ]
        ],

        'turnos' => [
            'items' => [
                ['label' => 'Configuración', 'route' => 'turnos.index', 'icon' => '⚙️'],
                ['label' => 'Asignaciones', 'route' => 'asignaciones-turnos.index', 'icon' => '⏱️'],
            ],
            'config' => [
                'colorBase' => 'indigo',
                'style' => 'tabs',
                'mobileLabel' => 'Turnos',
            ]
        ],

        'nominas' => [
            'items' => [
                ['label' => 'Todas', 'route' => 'nominas.index', 'icon' => '💰'],
            ],
            'config' => [
                'colorBase' => 'indigo',
                'style' => 'tabs',
                'mobileLabel' => 'Nóminas',
            ]
        ],

        'epis' => [
            'items' => [
                ['label' => 'Todos', 'route' => 'epis.index', 'icon' => '🦺'],
            ],
            'config' => [
                'colorBase' => 'indigo',
                'style' => 'tabs',
                'mobileLabel' => 'EPIs',
            ]
        ],

        'empresas' => [
            'items' => [
                ['label' => 'Configuración', 'route' => 'empresas.index', 'icon' => '🏢'],
            ],
            'config' => [
                'colorBase' => 'gray',
                'style' => 'tabs',
                'mobileLabel' => 'Empresa',
            ]
        ],

        'festivos' => [
            'items' => [
                ['label' => 'Todos', 'route' => 'festivos.index', 'icon' => '📅'],
            ],
            'config' => [
                'colorBase' => 'gray',
                'style' => 'tabs',
                'mobileLabel' => 'Festivos',
            ]
        ],

    ],
];
