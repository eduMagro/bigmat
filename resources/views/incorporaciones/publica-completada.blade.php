<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Formulario Completado - {{ $incorporacion->empresa_nombre }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center p-4">
    <div class="max-w-md w-full bg-white rounded-lg shadow-lg p-8 text-center">
        <!-- Icono de éxito -->
        <div class="inline-flex items-center justify-center w-20 h-20 bg-green-100 rounded-full mb-6">
            <svg class="w-10 h-10 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
        </div>

        <h1 class="text-2xl font-bold text-gray-800 mb-2">Formulario completado</h1>
        <p class="text-gray-600 mb-6">
            Hemos recibido tu documentación correctamente. El equipo de Recursos Humanos revisará
            los datos y se pondrá en contacto contigo.
        </p>

        <!-- Resumen -->
        <div class="bg-gray-50 rounded-lg p-4 text-left mb-6">
            <h3 class="font-medium text-gray-800 mb-2">Datos enviados:</h3>
            <dl class="text-sm space-y-1">
                <div class="flex justify-between">
                    <dt class="text-gray-500">DNI:</dt>
                    <dd class="font-medium">{{ $incorporacion->dni }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-500">Email:</dt>
                    <dd class="font-medium">{{ $incorporacion->email }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-500">Teléfono:</dt>
                    <dd class="font-medium">{{ $incorporacion->telefono }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-500">Fecha de envío:</dt>
                    <dd class="font-medium">{{ $incorporacion->datos_completados_at->format('d/m/Y H:i') }}</dd>
                </div>
            </dl>
        </div>

        <p class="text-sm text-gray-500">
            {{ $incorporacion->empresa_nombre }}
        </p>
    </div>
</body>
</html>
