<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="base-url" content="{{ url('') }}">
    <meta name="google" content="notranslate">



    <title>{{ $title ?? config('app.name') }}</title>

    <!-- ✅ Preconexión a fuentes (mejora FCP) -->
    <link rel="preconnect" href="https://fonts.bunny.net" crossorigin>
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet">

    <!-- ✅ Iconos para dispositivos -->
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('imagenes/ico/favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('imagenes/ico/favicon-16x16.png') }}">
    <link rel="shortcut icon" href="{{ asset('imagenes/ico/favicon.ico') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('imagenes/ico/apple-touch-icon.png') }}">
    <link rel="icon" type="image/png" sizes="192x192" href="{{ asset('imagenes/ico/android-chrome-192x192.png') }}">
    <link rel="icon" type="image/png" sizes="512x512" href="{{ asset('imagenes/ico/android-chrome-512x512.png') }}">
    <meta name="theme-color" content="#111827">

    <!-- Vite Assets -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Librerías adicionales -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.14.5/dist/sweetalert2.all.min.js" defer></script>

    <!-- FullCalendar (solo si es necesario en esta vista) -->
    @stack('calendar')

    <!-- Livewire Styles -->
    @livewireStyles

    <script>
        // Prevenir que errores de scripts externos rompan Livewire
        window.addEventListener('error', function (e) {
            if (e.message && e.message.includes('browser is not defined')) {
                console.warn('Error de browser API ignorado:', e.message);
                e.preventDefault();
                return true;
            }
        });
    </script>

    <style>
        /* Oculta cualquier elemento marcado con x-cloak hasta que Alpine quite el atributo */
        [x-cloak] {
            display: none !important;
        }

        /* Asegurar que el top-header nunca desaparezca durante la carga */
        #top-navigation {
            visibility: visible !important;
            opacity: 1 !important;
            display: block !important;
        }

        /* Desactivar transition-colors en la carga inicial para evitar flash */
        html:not(.alpine-ready) #top-navigation {
            transition: none !important;
        }

        /* Escala global solo en pantallas grandes */
        @media (min-width: 768px) {
            html {
                font-size: 70%;
            }
        }

        body {
            margin: 0;
            padding: 0;
            width: 100%;
            height: 100%;
            overflow-x: hidden;
            background-color: #f9fafb;
        }

        .dark body {
            background-color: #111827;
        }

        * {
            box-sizing: border-box;
            -webkit-font-smoothing: antialiased;
            text-rendering: optimizeLegibility;
        }

        /* Personalizar barra de progreso de Livewire Navigate */
        .livewire-progress-bar {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #3b82f6 0%, #2563eb 50%, #1d4ed8 100%);
            z-index: 99999;
            box-shadow: 0 2px 10px rgba(59, 130, 246, 0.5);
            transition: opacity 0.3s ease;
        }

        .livewire-progress-bar::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            animation: shimmer 1s infinite;
        }

        @keyframes shimmer {
            0% {
                transform: translateX(-100%);
            }

            100% {
                transform: translateX(100%);
            }
        }

        /* Overlay de transición para navegación */
        .navigation-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(2px);
            z-index: 9999;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.2s ease;
        }

        .dark .navigation-overlay {
            background: rgba(17, 24, 39, 0.8);
        }

        .navigation-overlay.active {
            opacity: 1;
            pointer-events: all;
        }

        /* Spinner de carga */
        .navigation-spinner {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 40px;
            height: 40px;
            border: 3px solid #e5e7eb;
            border-top-color: #3b82f6;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }

        .dark .navigation-spinner {
            border-color: #374151;
            border-top-color: #60a5fa;
        }

        @keyframes spin {
            to {
                transform: translate(-50%, -50%) rotate(360deg);
            }
        }
    </style>
</head>

<body class="font-sans antialiased transition-colors duration-200" @auth data-user-id="{{ auth()->id() }}" @endauth>
    <!-- Overlay de navegación -->
    <div id="navigation-overlay" class="navigation-overlay">
        <div class="navigation-spinner"></div>
    </div>

    <div class="flex h-screen bg-gray-100 dark:bg-gray-900 overflow-hidden">
        <!-- Sidebar Menu Enhanced (persiste entre navegaciones) -->
        @persist('sidebar')
        <x-sidebar-menu-enhanced />
        @endpersist

        <!-- Main Content Area -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Top Header Enhanced (persiste entre navegaciones) -->
            @persist('header')
            <x-top-header-enhanced />
            @endpersist

            <!-- Alerts -->
            @include('layouts.alerts')

            <!-- Prompt para notificaciones push -->
            @auth
            <x-notification-prompt />
            @endauth

            <!-- Page Content -->
            <main class="flex-1 overflow-y-auto bg-blue-100/50 dark:bg-gray-900 transition-colors">
                <div class="py-4 md:px-6 h-full">
                    <!-- Breadcrumbs -->
                    <x-breadcrumbs />

                    <!-- Page Heading -->
                    @isset($header)
                    <header class="mb-6">
                        <div
                            class="bg-white dark:bg-gray-800 shadow-sm rounded-lg px-6 py-4 border border-gray-200 dark:border-gray-700 transition-colors">
                            {{ $header }}
                        </div>
                    </header>
                    @endisset

                    <!-- Main Content -->
                    <div class="page-content pb-4">
                        {{ $slot }}
                    </div>

                    <!-- Footer con enlaces legales -->
                    <footer id="footer-global" class="mt-8 pb-4 text-center text-xs text-gray-400 dark:text-gray-500">
                        <div class="flex justify-center items-center gap-2 flex-wrap">
                            <a href="{{ route('politicas.privacidad') }}"
                                class="hover:text-gray-600 dark:hover:text-gray-300 transition">Politica de
                                Privacidad</a>
                            <span>|</span>
                            <a href="{{ route('politicas.cookies') }}"
                                class="hover:text-gray-600 dark:hover:text-gray-300 transition">Politica de Cookies</a>
                            <span>|</span>
                            <a href="{{ route('politicas.terminos') }}"
                                class="hover:text-gray-600 dark:hover:text-gray-300 transition">Terminos y
                                Condiciones</a>
                        </div>
                    </footer>
                </div>
            </main>
        </div>
    </div>
    <!-- Livewire Scripts -->
    <script data-navigate-once>
        // Interceptar fetch para corregir URLs de Livewire en subdirectorio
        (function () {
            const originalFetch = window.fetch;
            const baseUrl = '{{ url("") }}';

            window.fetch = function (url, options) {
                // Si es una petición a /livewire/, añadir el prefijo correcto
                if (typeof url === 'string' && url.startsWith('/livewire/')) {
                    url = baseUrl + url;
                    console.log('Livewire URL corregida:', url);
                }
                return originalFetch.call(this, url, options);
            };
        })();
    </script>
    @livewireScripts(['navigate' => true])

    @stack('scripts')

    <!-- Sistema de limpieza global para Livewire SPA - Previene acumulación de listeners -->
    <script data-navigate-once>
        // Sistema de limpieza global para inicializadores de JavaScript
        window.pageInitializers = window.pageInitializers || [];

        document.addEventListener('livewire:navigating', () => {
            // Limpiar todos los inicializadores registrados antes de navegar
            window.pageInitializers.forEach(init => {
                document.removeEventListener('livewire:navigated', init);
            });
            window.pageInitializers = [];
        });
    </script>

    <!-- Dark Mode Support Script -->
    <script data-navigate-once>
        // Aplicar dark mode desde localStorage al cargar
        if (localStorage.getItem('dark_mode') === 'true') {
            document.documentElement.classList.add('dark');
        }

        // Marcar cuando Alpine está listo para habilitar transiciones
        document.addEventListener('alpine:init', () => {
            document.documentElement.classList.add('alpine-ready');
        });

        // Re-aplicar en cada navegación de Livewire
        document.addEventListener('livewire:navigated', () => {
            if (localStorage.getItem('dark_mode') === 'true') {
                document.documentElement.classList.add('dark');
            }
        });

        // Sistema SPA personalizado compatible con Vite
        class CustomSPA {
            constructor() {
                this.progressBar = this.createProgressBar();
                this.overlay = document.getElementById('navigation-overlay');
                this.isNavigating = false;
                this.executedScripts = new Set();
                this.collectExistingScripts();
                this.init();
            }

            createProgressBar() {
                const bar = document.createElement('div');
                bar.className = 'livewire-progress-bar';
                bar.style.width = '0%';
                bar.style.display = 'none';
                document.body.appendChild(bar);
                return bar;
            }

            showProgress() {
                this.progressBar.style.display = 'block';
                this.progressBar.style.width = '0%';
                setTimeout(() => this.progressBar.style.width = '70%', 50);
                this.overlay?.classList.add('active');
            }

            hideProgress() {
                this.progressBar.style.width = '100%';
                setTimeout(() => {
                    this.progressBar.style.display = 'none';
                    this.progressBar.style.width = '0%';
                }, 300);
                this.overlay?.classList.remove('active');
            }

            init() {
                document.addEventListener('click', (e) => {
                    const link = e.target.closest('a[data-spa-link]');
                    if (link && !this.isNavigating) {
                        e.preventDefault();
                        this.navigate(link.href);
                    }
                });

                window.addEventListener('popstate', (e) => {
                    if (e.state && e.state.spa && !this.isNavigating) {
                        this.navigate(window.location.href, false);
                    }
                });

                document.addEventListener('livewire:init', () => {
                    if (typeof Livewire !== 'undefined' && Livewire.hook) {
                        Livewire.hook('url.changed', () => false);
                    }
                });
            }

            async navigate(url, pushState = true) {
                if (this.isNavigating) {
                    console.log('⏸️ Navegación ya en curso, ignorando...');
                    return;
                }
                console.log('🚀 Iniciando SPA navigation a:', url);
                this.isNavigating = true;
                this.showProgress();

                try {
                    console.log('📡 Haciendo fetch...');
                    const response = await fetch(url, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            Accept: 'text/html',
                        },
                    });

                    if (!response.ok) {
                        console.error('❌ Fetch failed:', response.status);
                        throw new Error('Navigation failed');
                    }

                    console.log('✅ Fetch exitoso, parseando HTML...');
                    const html = await response.text();
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');
                    const newMain = doc.querySelector('main');
                    const currentMain = document.querySelector('main');
                    const newScripts = newMain ? Array.from(newMain.querySelectorAll('script')) : [];

                    if (!newMain || !currentMain) {
                        console.error('❌ No se encontró <main> en la página nueva o la actual');
                        window.location.href = url;
                        return;
                    }

                    console.log('🔄 Reemplazando contenido...');
                    if (typeof Alpine !== 'undefined' && Alpine.morph) {
                        console.log('✨ Usando Alpine.morph');
                        Alpine.morph(currentMain, newMain);
                    } else {
                        console.log('⚠️ Alpine.morph no disponible, usando innerHTML');
                        currentMain.innerHTML = newMain.innerHTML;
                    }

                    this.executeScripts(newScripts);
                    document.title = doc.title;
                    window.scrollTo({
                        top: 0,
                        behavior: 'instant'
                    });

                    if (pushState) {
                        setTimeout(() => {
                            console.log('🔗 Actualizando URL a:', url);
                            window.history.pushState({
                                spa: true
                            }, '', url);
                        }, 100);
                    }

                    console.log('✅ Navegación SPA completada exitosamente');
                } catch (error) {
                    console.error('❌ SPA navigation error:', error);
                    console.log('🔄 Fallback a navegación normal');
                    window.location.href = url;
                } finally {
                    this.hideProgress();
                    this.isNavigating = false;
                }
            }

            executeScripts(scripts) {
                scripts.forEach((oldScript) => {
                    if (oldScript.hasAttribute('data-navigate-once')) return;
                    const forceReload = oldScript.hasAttribute('data-navigate-reload');
                    const signature = this.getScriptSignature(oldScript);
                    if (!forceReload && this.executedScripts.has(signature)) return;

                    const script = document.createElement('script');
                    Array.from(oldScript.attributes).forEach(({
                        name,
                        value
                    }) => {
                        script.setAttribute(name, value);
                    });

                    if (oldScript.textContent) {
                        script.textContent = oldScript.textContent;
                    }

                    document.body.appendChild(script);
                    script.remove();

                    if (!forceReload) {
                        this.executedScripts.add(signature);
                    }
                });
            }

            collectExistingScripts() {
                document.querySelectorAll('script').forEach((script) => {
                    const signature = this.getScriptSignature(script);
                    this.executedScripts.add(signature);
                });
            }

            getScriptSignature(script) {
                if (script.src) return `src:${script.src}`;
                return `inline:${(script.textContent || '').trim()}`;
            }
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => new CustomSPA());
        } else {
            new CustomSPA();
        }

        const navigationOverlay = document.getElementById('navigation-overlay');
        let navigationTimeout = null;
        let isInitialLoad = true;

        // Marcar que ya no es la carga inicial después de que la página esté lista
        document.addEventListener('livewire:navigated', () => {
            isInitialLoad = false;
        }, { once: true });

        document.addEventListener('livewire:navigating', () => {
            // No mostrar overlay en la carga inicial
            if (isInitialLoad) return;

            navigationTimeout = setTimeout(() => {
                navigationOverlay?.classList.add('active');
            }, 100);
        });

        document.addEventListener('livewire:navigated', () => {
            if (navigationTimeout) {
                clearTimeout(navigationTimeout);
                navigationTimeout = null;
            }
            navigationOverlay?.classList.remove('active');
        });

        document.addEventListener('livewire:navigate-failed', () => {
            console.error('Navegación fallida');
            navigationOverlay?.classList.remove('active');
            if (navigationTimeout) {
                clearTimeout(navigationTimeout);
                navigationTimeout = null;
            }
        });
    </script>
</body>

</html>