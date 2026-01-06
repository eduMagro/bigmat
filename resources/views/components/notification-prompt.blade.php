{{-- Componente para solicitar permiso de notificaciones push --}}
<div x-data="notificationPrompt()"
     x-show="showPrompt"
     x-cloak
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="opacity-0 translate-y-4"
     x-transition:enter-end="opacity-100 translate-y-0"
     x-transition:leave="transition ease-in duration-200"
     x-transition:leave-start="opacity-100 translate-y-0"
     x-transition:leave-end="opacity-0 translate-y-4"
     class="fixed bottom-4 right-4 z-50 max-w-sm bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 p-4">

    <div class="flex items-start gap-3">
        <div class="flex-shrink-0">
            <svg class="w-6 h-6 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
            </svg>
        </div>

        <div class="flex-1">
            <h4 class="text-sm font-medium text-gray-900 dark:text-white">
                Activar notificaciones
            </h4>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Recibe alertas importantes incluso cuando no estés en la aplicación.
            </p>

            <div class="mt-3 flex gap-2">
                <button @click="requestPermission()"
                        class="px-3 py-1.5 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-md transition-colors">
                    Activar
                </button>
                <button @click="dismiss()"
                        class="px-3 py-1.5 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-md transition-colors">
                    Ahora no
                </button>
            </div>
        </div>

        <button @click="dismiss()" class="flex-shrink-0 text-gray-400 hover:text-gray-500">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
    </div>
</div>

{{-- Prompt para instalar la PWA --}}
<div x-data="pwaInstallPrompt()"
     x-show="showInstallPrompt"
     x-cloak
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="opacity-0 translate-y-4"
     x-transition:enter-end="opacity-100 translate-y-0"
     x-transition:leave="transition ease-in duration-200"
     x-transition:leave-start="opacity-100 translate-y-0"
     x-transition:leave-end="opacity-0 translate-y-4"
     class="fixed bottom-4 left-4 z-50 max-w-sm bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 p-4">

    <div class="flex items-start gap-3">
        <div class="flex-shrink-0">
            <svg class="w-6 h-6 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
            </svg>
        </div>

        <div class="flex-1">
            <h4 class="text-sm font-medium text-gray-900 dark:text-white">
                Instalar aplicacion
            </h4>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Instala HPR Manager en tu dispositivo para un acceso mas rapido.
            </p>

            <div class="mt-3 flex gap-2">
                <button @click="installApp()"
                        class="px-3 py-1.5 text-sm font-medium text-white bg-green-600 hover:bg-green-700 rounded-md transition-colors">
                    Instalar
                </button>
                <button @click="dismissInstall()"
                        class="px-3 py-1.5 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-md transition-colors">
                    Ahora no
                </button>
            </div>
        </div>

        <button @click="dismissInstall()" class="flex-shrink-0 text-gray-400 hover:text-gray-500">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
    </div>
</div>

<script>
function notificationPrompt() {
    return {
        showPrompt: false,

        init() {
            // Solo mostrar si:
            // 1. Notificaciones soportadas
            // 2. Permiso no decidido
            // 3. No descartado recientemente
            if (typeof window.FirebasePush !== 'undefined' &&
                window.FirebasePush.isSupported() &&
                window.FirebasePush.getPermissionState() === 'default' &&
                !this.isDismissed()) {

                // Mostrar después de un delay
                setTimeout(() => {
                    this.showPrompt = true;
                }, 3000);
            }
        },

        async requestPermission() {
            this.showPrompt = false;

            if (typeof window.FirebasePush !== 'undefined') {
                const token = await window.FirebasePush.requestPermission();

                if (token) {
                    // Mostrar confirmación
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            title: 'Notificaciones activadas',
                            text: 'Recibirás alertas importantes en tu dispositivo.',
                            icon: 'success',
                            toast: true,
                            position: 'top-end',
                            showConfirmButton: false,
                            timer: 3000
                        });
                    }
                }
            }
        },

        dismiss() {
            this.showPrompt = false;
            // Recordar que se descartó por 7 días
            localStorage.setItem('fcm_prompt_dismissed', Date.now());
        },

        isDismissed() {
            const dismissed = localStorage.getItem('fcm_prompt_dismissed');
            if (!dismissed) return false;

            const sevenDays = 7 * 24 * 60 * 60 * 1000;
            return (Date.now() - parseInt(dismissed)) < sevenDays;
        }
    };
}

function pwaInstallPrompt() {
    return {
        showInstallPrompt: false,

        init() {
            // Verificar si ya está instalada
            if (typeof window.isPWAInstalled === 'function' && window.isPWAInstalled()) {
                return;
            }

            // Verificar si ya se descartó recientemente
            if (this.isInstallDismissed()) {
                return;
            }

            // Si ya hay un prompt disponible, mostrarlo
            if (typeof window.canInstallPWA === 'function' && window.canInstallPWA()) {
                setTimeout(() => {
                    this.showInstallPrompt = true;
                }, 5000);
            }

            // Escuchar el evento cuando el prompt esté disponible
            window.addEventListener('pwa-install-available', () => {
                if (!this.isInstallDismissed()) {
                    setTimeout(() => {
                        this.showInstallPrompt = true;
                    }, 5000);
                }
            });

            // Ocultar cuando se instale
            window.addEventListener('pwa-installed', () => {
                this.showInstallPrompt = false;
            });
        },

        async installApp() {
            this.showInstallPrompt = false;

            if (typeof window.showPWAInstallPrompt === 'function') {
                const installed = await window.showPWAInstallPrompt();

                if (installed && typeof Swal !== 'undefined') {
                    Swal.fire({
                        title: 'Aplicacion instalada',
                        text: 'Ya puedes acceder a HPR Manager desde tu pantalla de inicio.',
                        icon: 'success',
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 3000
                    });
                }
            }
        },

        dismissInstall() {
            this.showInstallPrompt = false;
            localStorage.setItem('pwa_install_dismissed', Date.now());
        },

        isInstallDismissed() {
            const dismissed = localStorage.getItem('pwa_install_dismissed');
            if (!dismissed) return false;

            const sevenDays = 7 * 24 * 60 * 60 * 1000;
            return (Date.now() - parseInt(dismissed)) < sevenDays;
        }
    };
}
</script>
