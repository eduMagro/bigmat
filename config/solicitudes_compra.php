<?php

$hprBaseUrl = rtrim((string) env('HPR_BASE_URL', 'http://localhost'), '/');

return [
    // Configuración para consumir la API de solicitudes de compra de HPR.
    'hpr' => [
        'base_url' => $hprBaseUrl,
        'api_base_url' => rtrim((string) env('HPR_SOLICITUDES_API_BASE_URL', $hprBaseUrl . '/api/solicitudes-compra'), '/'),
        'api_token' => env('SOLICITUDES_COMPRA_API_TOKEN'),
    ],
];

