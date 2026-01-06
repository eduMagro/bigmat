<nav id="top-navigation" x-data="{
    open: false,
    notificationsOpen: false,
    userMenuOpen: false,
    sidebarOpen: window.innerWidth >= 768 ? (localStorage.getItem('sidebar_open') !== 'false') : false,
    isToggling: false,

    init() {
        // Escuchar cuando el sidebar se abre/cierra
        window.addEventListener('sidebar-toggled', (e) => {
            this.sidebarOpen = e.detail.isOpen;
        });

        // Escuchar cuando los logos deben salir
        window.addEventListener('logos-exit', () => {
            this.isToggling = true;
            setTimeout(() => {
                this.isToggling = false;
            }, 400);
        });
    }
}"
    class="bg-gray-900 dark:bg-gray-950 border-b border-gray-800 dark:border-gray-700 shadow-sm flex-shrink-0 transition-colors sticky top-0 z-[60]">
    <!-- Primary Navigation Menu -->
    <div class="w-full">
        <div class="flex justify-between items-center h-14">
            <!-- Logo & Title -->
            <div class="flex items-center space-x-4 relative overflow-hidden">
                <!-- Botón hamburguesa para móvil (solo usuarios con sidebar) -->
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(auth()->user()->esOficina()): ?>
                <button @click="$dispatch('toggle-sidebar')"
                    class="md:hidden p-2 rounded-lg text-gray-600 dark:text-gray-300 hover:bg-gray-700 dark:hover:bg-gray-700 transition">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 6h16M4 12h16M4 18h16"></path>
                    </svg>
                </button>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                <!-- Logo del Top Header (visible cuando sidebar está cerrado, o siempre para operarios) -->
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(auth()->user()->esOficina()): ?>
                <a x-show="!sidebarOpen && !isToggling" x-transition:enter="transition-all duration-200 delay-200"
                    x-transition:enter-start="opacity-0 transform -translate-y-16"
                    x-transition:enter-end="opacity-100 transform translate-y-0"
                    x-transition:leave="transition-all duration-200"
                    x-transition:leave-start="opacity-100 transform translate-y-0"
                    x-transition:leave-end="opacity-0 transform -translate-y-16" href="<?php echo e(route('dashboard')); ?>" wire:navigate
                    class="topheader-logo flex items-center space-x-3 group relative z-50">
                    <?php if (isset($component)) { $__componentOriginal8892e718f3d0d7a916180885c6f012e7 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal8892e718f3d0d7a916180885c6f012e7 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.application-logo','data' => ['class' => 'block h-8 w-auto fill-current text-gray-800 dark:text-gray-200 group-hover:text-blue-600 dark:group-hover:text-blue-400 transition']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('application-logo'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'block h-8 w-auto fill-current text-gray-800 dark:text-gray-200 group-hover:text-blue-600 dark:group-hover:text-blue-400 transition']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal8892e718f3d0d7a916180885c6f012e7)): ?>
<?php $attributes = $__attributesOriginal8892e718f3d0d7a916180885c6f012e7; ?>
<?php unset($__attributesOriginal8892e718f3d0d7a916180885c6f012e7); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal8892e718f3d0d7a916180885c6f012e7)): ?>
<?php $component = $__componentOriginal8892e718f3d0d7a916180885c6f012e7; ?>
<?php unset($__componentOriginal8892e718f3d0d7a916180885c6f012e7); ?>
<?php endif; ?>
                </a>
                <?php else: ?>
                <!-- Logo siempre visible para operarios (no tienen sidebar) -->
                <a href="<?php echo e(route('dashboard')); ?>" wire:navigate
                    class="flex items-center space-x-3 group relative z-50 pl-4">
                    <?php if (isset($component)) { $__componentOriginal8892e718f3d0d7a916180885c6f012e7 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal8892e718f3d0d7a916180885c6f012e7 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.application-logo','data' => ['class' => 'block h-8 w-auto fill-current text-gray-800 dark:text-gray-200 group-hover:text-blue-600 dark:group-hover:text-blue-400 transition']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('application-logo'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'block h-8 w-auto fill-current text-gray-800 dark:text-gray-200 group-hover:text-blue-600 dark:group-hover:text-blue-400 transition']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal8892e718f3d0d7a916180885c6f012e7)): ?>
<?php $attributes = $__attributesOriginal8892e718f3d0d7a916180885c6f012e7; ?>
<?php unset($__attributesOriginal8892e718f3d0d7a916180885c6f012e7); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal8892e718f3d0d7a916180885c6f012e7)): ?>
<?php $component = $__componentOriginal8892e718f3d0d7a916180885c6f012e7; ?>
<?php unset($__componentOriginal8892e718f3d0d7a916180885c6f012e7); ?>
<?php endif; ?>
                </a>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </div>

            <!-- Right Side - User Actions -->
            <div class="flex items-center space-x-2 sm:space-x-4">
                <!-- Botón Refrescar Página -->
                <button onclick="window.location.reload()"
                    class="relative p-2 text-gray-300 dark:text-gray-400 hover:bg-gray-700 dark:hover:bg-gray-700 rounded-lg transition"
                    title="Refrescar página">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15">
                        </path>
                    </svg>
                </button>

                <!-- Notificaciones - Enlace directo a alertas -->
                <a href="<?php echo e(route('alertas.index')); ?>" wire:navigate
                    class="relative p-2 text-gray-300 dark:text-gray-400 hover:bg-gray-700 dark:hover:bg-gray-700 rounded-lg transition"
                    title="Ver mensajes">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9">
                        </path>
                    </svg>
                    <!-- Badge de notificaciones (se oculta automáticamente cuando no hay mensajes) -->
                    <span id="alerta-count"
                        class="absolute top-1 right-1 bg-red-500 text-white text-xs font-bold rounded-full w-5 h-5 items-center justify-center hidden"></span>
                </a>

                <!-- User Dropdown -->
                <div class="relative" x-data="{ open: false }">
                    <button @click="open = !open" @click.away="open = false"
                        class="flex items-center space-x-2 px-3 py-2 text-sm font-medium text-gray-300 dark:text-gray-300 hover:bg-gray-700 dark:hover:bg-gray-700 rounded-lg transition">
                        <div
                            class="w-8 h-8 bg-gray-800 dark:bg-gray-950 rounded-full flex items-center justify-center text-white font-bold text-sm border-2 border-gray-600">
                            <?php echo e(strtoupper(substr(Auth::user()->nombre_completo, 0, 1))); ?>

                        </div>
                        <span class="hidden sm:block"><?php echo e(Auth::user()->nombre_completo); ?></span>
                        <svg class="w-4 h-4" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7">
                            </path>
                        </svg>
                    </button>

                    <!-- Dropdown Content -->
                    <div x-show="open" x-transition x-cloak
                        class="absolute right-2 mt-2 w-56 bg-gray-900 dark:bg-gray-800 rounded-lg shadow-xl border border-gray-800 dark:border-gray-700 py-2 z-50">
                        <div class="px-4 py-3 border-b border-gray-800 dark:border-gray-700">
                            <p class="text-sm font-medium text-gray-300 dark:text-white">
                                <?php echo e(Auth::user()->nombre_completo); ?></p>
                            <p class="text-xs text-gray-400 dark:text-gray-400 truncate"><?php echo e(Auth::user()->email); ?></p>
                        </div>

                        <div class="py-2">
                            <a href="<?php echo e(route('usuarios.show', auth()->id())); ?>" wire:navigate
                                class="flex items-center space-x-3 px-4 py-2 text-sm text-gray-300 dark:text-gray-300 hover:bg-gray-700 dark:hover:bg-gray-700 transition">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                </svg>
                                <span>Mi Perfil</span>
                            </a>

                            <a href="<?php echo e(route('dashboard')); ?>" wire:navigate
                                class="flex items-center space-x-3 px-4 py-2 text-sm text-gray-300 dark:text-gray-300 hover:bg-gray-700 dark:hover:bg-gray-700 transition">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6">
                                    </path>
                                </svg>
                                <span>Dashboard</span>
                            </a>

                        </div>

                        <div class="border-t border-gray-800 dark:border-gray-700 py-2">
                            <form method="POST" action="<?php echo e(route('logout')); ?>">
                                <?php echo csrf_field(); ?>
                                <button type="submit"
                                    class="w-full flex items-center space-x-3 px-4 py-2 text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 transition text-left">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1">
                                        </path>
                                    </svg>
                                    <span>Cerrar Sesión</span>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</nav>

<!-- Script para actualizar el contador de alertas en la campanita -->
<script data-navigate-once>
    function actualizarContadorCampanita() {
        fetch("<?php echo e(route('alertas.verSinLeer')); ?>", {
                headers: {
                    'Accept': 'application/json'
                },
                credentials: 'same-origin'
            })
            .then(response => response.json())
            .then(data => {
                const badge = document.getElementById('alerta-count');
                if (badge) {
                    if (data.cantidad > 0) {
                        badge.textContent = data.cantidad;
                        badge.classList.remove('hidden');
                        badge.classList.add('flex');
                    } else {
                        badge.classList.add('hidden');
                        badge.classList.remove('flex');
                    }
                }
            })
            .catch(error => console.error("Error al obtener alertas:", error));
    }

    document.addEventListener("DOMContentLoaded", function() {
        // Actualizar al cargar la página
        actualizarContadorCampanita();

        // Actualizar cada 30 segundos
        setInterval(actualizarContadorCampanita, 30000);
    });

    // También actualizar cuando Livewire navegue
    document.addEventListener('livewire:navigated', () => {
        actualizarContadorCampanita();
    });
</script>
<?php /**PATH C:\xampp\htdocs\bigmat\resources\views/components/top-header-enhanced.blade.php ENDPATH**/ ?>