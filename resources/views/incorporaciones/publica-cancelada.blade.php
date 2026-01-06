<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Formulario no disponible</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center p-4">
    <div class="max-w-md w-full bg-white rounded-lg shadow-lg p-8 text-center">
        <!-- Icono -->
        <div class="inline-flex items-center justify-center w-20 h-20 bg-red-100 rounded-full mb-6">
            <svg class="w-10 h-10 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </div>

        <h1 class="text-2xl font-bold text-gray-800 mb-2">Formulario no disponible</h1>
        <p class="text-gray-600 mb-6">
            Este proceso de incorporación ha sido cancelado. Si crees que esto es un error,
            por favor contacta con el departamento de Recursos Humanos.
        </p>

        <p class="text-sm text-gray-500">
            {{ $incorporacion->empresa_nombre }}
        </p>
    </div>
</body>
</html>
