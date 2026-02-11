<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Bigmat - RRHH') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class'
        }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>

    <!-- Iconos -->
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('imagenes/ico/favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('imagenes/ico/favicon-16x16.png') }}">
    <link rel="shortcut icon" href="{{ asset('imagenes/ico/favicon.ico') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('imagenes/ico/apple-touch-icon.png') }}">
    <link rel="icon" type="image/png" sizes="192x192" href="{{ asset('imagenes/ico/android-chrome-192x192.png') }}">
    <link rel="icon" type="image/png" sizes="512x512" href="{{ asset('imagenes/ico/android-chrome-512x512.png') }}">
    <meta name="theme-color" content="#111827">

    <style>
        body {
            font-family: 'Inter', sans-serif;
        }

        .login-gradient {
            background: linear-gradient(135deg, #111827 0%, #1f2937 50%, #111827 100%);
        }

        .pattern-overlay {
            background-image: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.05'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
        }

        .input-focus:focus {
            box-shadow: 0 0 0 3px rgba(17, 24, 39, 0.15);
        }
    </style>
</head>

<body class="antialiased">
    <div class="min-h-screen flex">
        <!-- Panel izquierdo - Branding (oculto en móvil) -->
        <div class="hidden lg:flex lg:w-1/2 relative overflow-hidden"
            style="background: linear-gradient(135deg, #111827 0%, #1f2937 50%, #111827 100%);">
            <div class="absolute inset-0 flex flex-col justify-between p-12 text-white">
                <!-- Espacio superior -->
                <div></div>

                <!-- Contenido central -->
                <div class="space-y-6">
                    <!-- Logo grande -->
                    <img src="{{ asset('imagenes/logoBigmat.png') }}" alt="Bigmat" class="h-36">

                    <p class="text-lg max-w-md leading-relaxed" style="color: #9ca3af;">
                        Gestiona tu equipo, controla la producción y optimiza los recursos de tu empresa desde una única
                        plataforma.
                    </p>

                    <!-- Características -->
                    <div class="grid grid-cols-2 gap-4 pt-6">
                        <div class="flex items-center space-x-3">
                            <div class="w-10 h-10 rounded-lg bg-white/10 flex items-center justify-center">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                </svg>
                            </div>
                            <span class="text-sm" style="color: #9ca3af;">Gestión de Personal</span>
                        </div>
                        <div class="flex items-center space-x-3">
                            <div class="w-10 h-10 rounded-lg bg-white/10 flex items-center justify-center">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                                </svg>
                            </div>
                            <span class="text-sm" style="color: #9ca3af;">Control de Producción</span>
                        </div>
                        <div class="flex items-center space-x-3">
                            <div class="w-10 h-10 rounded-lg bg-white/10 flex items-center justify-center">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <span class="text-sm" style="color: #9ca3af;">Fichaje de Turnos</span>
                        </div>
                        <div class="flex items-center space-x-3">
                            <div class="w-10 h-10 rounded-lg bg-white/10 flex items-center justify-center">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                </svg>
                            </div>
                            <span class="text-sm" style="color: #9ca3af;">Informes y Métricas</span>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="text-sm" style="color: rgba(156, 163, 175, 0.6);">
                    &copy; {{ date('Y') }} Bigmat Los Palacios. Todos los derechos reservados.
                </div>
            </div>

            <!-- Decoración geométrica -->
            <div class="absolute -bottom-32 -right-32 w-96 h-96 bg-white/5 rounded-full"></div>
            <div class="absolute -top-16 -right-16 w-64 h-64 bg-white/5 rounded-full"></div>
        </div>

        <!-- Panel derecho - Formulario -->
        <div class="w-full lg:w-1/2 flex flex-col min-h-screen bg-gray-50 dark:bg-gray-900">

            <!-- Header móvil con gradiente -->
            <div class="lg:hidden relative overflow-hidden"
                style="background: linear-gradient(135deg, #111827 0%, #1f2937 100%);">
                <!-- Círculos decorativos -->
                <div class="absolute -top-10 -right-10 w-32 h-32 rounded-full"
                    style="background: rgba(255,255,255,0.1);"></div>
                <div class="absolute -bottom-8 -left-8 w-24 h-24 rounded-full"
                    style="background: rgba(255,255,255,0.05);"></div>

                <div class="relative px-6 pt-8 pb-12 text-center">
                    <img src="{{ asset('imagenes/logoBigmat.png') }}" alt="Bigmat" class="h-20 mx-auto">
                </div>

                <!-- Curva inferior -->
                <div class="absolute bottom-0 left-0 right-0">
                    <svg viewBox="0 0 1440 60" fill="none" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="none"
                        style="display: block; width: 100%; height: 30px;">
                        <path d="M0 60L1440 60L1440 0C1440 0 1082.5 40 720 40C357.5 40 0 0 0 0L0 60Z"
                            class="fill-gray-50 dark:fill-gray-900" />
                    </svg>
                </div>
            </div>

            <!-- Contenido del formulario -->
            <div class="flex-1 flex flex-col justify-center items-center px-4 py-6 sm:p-12">
                <div class="w-full max-w-md">

                    <!-- Título (visible en desktop, oculto en móvil ya que está en el header) -->
                    <div class="mb-8 hidden lg:block">
                        <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Iniciar sesión</h2>
                        <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                            Ingresa tus credenciales para acceder al sistema
                        </p>
                    </div>

                    <!-- Card del formulario en móvil -->
                    <div
                        class="lg:bg-transparent lg:shadow-none lg:p-0 bg-white dark:bg-gray-800 rounded-2xl shadow-lg dark:shadow-gray-900/50 px-4 py-6 sm:p-6 -mt-6 lg:mt-0 !p-4">
                        <!-- Título móvil dentro del card -->
                        <div class="lg:hidden mb-6 text-center">
                            <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100">Iniciar sesión</h2>
                        </div>

                        {{ $slot }}
                    </div>

                    <!-- Footer móvil -->
                    <div class="lg:hidden mt-8 text-center text-xs text-gray-400 dark:text-gray-500">
                        &copy; {{ date('Y') }} Bigmat Los Palacios. Todos los derechos reservados.
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>