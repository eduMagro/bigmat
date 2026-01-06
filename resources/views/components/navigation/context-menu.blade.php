@props([
    'items' => [],
    'colorBase' => 'blue',
    'checkRole' => null,
    'badges' => [],
    'mobileLabel' => 'Menú',
    'style' => 'tabs', // tabs, pills, underline
    'size' => 'md', // sm, md, lg
])

@php
    // Control de acceso por rol
    if ($checkRole === 'oficina' && (!auth()->check() || auth()->user()->rol !== 'oficina')) {
        return;
    }
    if ($checkRole === 'no-operario' && auth()->check() && auth()->user()->rol === 'operario') {
        return;
    }

    $rutaActual = request()->route()->getName();

    // Sistema de colores unificado
    $colores = [
        'bg' => "bg-{$colorBase}-600",
        'bgHover' => "hover:bg-{$colorBase}-700",
        'bgActivo' => "bg-{$colorBase}-800",
        'txt' => 'text-white',
        'txtBase' => "text-{$colorBase}-700",
        'txtHover' => "hover:text-{$colorBase}-900",
        'bgLite' => "bg-{$colorBase}-100",
        'borde' => 'border-gray-200',
        'activoTxt' => "text-{$colorBase}-800",
        'hoverLite' => "hover:bg-{$colorBase}-50",
    ];

    // Clases de tamaño
    $sizeClasses = [
        'sm' => 'text-xs px-3 py-1.5',
        'md' => 'text-sm px-4 py-2',
        'lg' => 'text-base px-5 py-3',
    ];

    $currentSize = $sizeClasses[$size] ?? $sizeClasses['md'];

    // Verificar si una ruta está activa
    $isActive = function($itemRoute) use ($rutaActual) {
        if ($rutaActual === $itemRoute) return true;
        if (str_contains($itemRoute, '*')) {
            $pattern = str_replace('*', '', $itemRoute);
            return str_starts_with($rutaActual, $pattern);
        }
        return request()->routeIs($itemRoute) || request()->routeIs($itemRoute . '.*');
    };
@endphp

@if(count($items) > 0)
    <nav class="w-full context-menu-{{ $style }}" x-data="{ mobileOpen: false }" role="navigation" aria-label="Menú contextual">
        <!-- Versión Móvil -->
        <div class="block sm:hidden mb-4">
            <button @click="mobileOpen = !mobileOpen"
                    type="button"
                    class="w-full {{ $colores['bg'] }} {{ $colores['bgHover'] }} {{ $colores['txt'] }} font-semibold {{ $currentSize }} rounded-lg shadow-md transition-all flex items-center justify-between"
                    aria-expanded="false"
                    aria-controls="mobile-context-menu">
                <span>{{ $mobileLabel }}</span>
                <svg class="w-5 h-5 transition-transform" :class="mobileOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                </svg>
            </button>

            <div x-show="mobileOpen"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 transform scale-95"
                 x-transition:enter-end="opacity-100 transform scale-100"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100 transform scale-100"
                 x-transition:leave-end="opacity-0 transform scale-95"
                 @click.away="mobileOpen = false"
                 id="mobile-context-menu"
                 class="mt-2 bg-white dark:bg-gray-800 border {{ $colores['borde'] }} dark:border-gray-700 rounded-lg shadow-xl overflow-hidden"
                 x-cloak>
                @foreach ($items as $item)
                    @php
                        $active = $isActive($item['route']);
                        $hasBadge = isset($badges[$item['route']]) && $badges[$item['route']] > 0;
                    @endphp
                    <a href="{{ route($item['route']) }}" wire:navigate
                       wire:navigate
                       class="relative flex items-center justify-between px-4 py-3 text-sm font-medium transition-colors border-b last:border-b-0 dark:border-gray-700
                              {{ $active
                                  ? $colores['bgLite'] . ' dark:bg-' . $colorBase . '-900/50 ' . $colores['activoTxt'] . ' dark:text-' . $colorBase . '-300 border-l-4 border-' . $colorBase . '-600'
                                  : 'text-gray-700 dark:text-gray-300 ' . $colores['hoverLite'] . ' dark:hover:bg-gray-700 ' . $colores['txtHover'] . ' dark:hover:text-white border-l-4 border-transparent' }}"
                       aria-current="{{ $active ? 'page' : 'false' }}">
                        <span class="flex items-center space-x-2">
                            @if(isset($item['icon']))
                                <span class="text-lg">{{ $item['icon'] }}</span>
                            @endif
                            <span>{{ $item['label'] }}</span>
                        </span>
                        @if($hasBadge)
                            <span class="bg-red-600 text-white text-xs font-bold px-2 py-0.5 rounded-full shadow-sm" role="status" aria-label="{{ $badges[$item['route']] }} notificaciones">
                                {{ $badges[$item['route']] }}
                            </span>
                        @endif
                    </a>
                @endforeach
            </div>
        </div>

        <!-- Versión Desktop -->
        <div class="hidden sm:flex w-full mb-6
                    @if($style === 'tabs') border-b border-gray-200 dark:border-gray-700
                    @elseif($style === 'pills') space-x-2
                    @elseif($style === 'underline') space-x-1 border-b-2 border-gray-200 dark:border-gray-700 @endif">
            @foreach ($items as $item)
                @php
                    $active = $isActive($item['route']);
                    $hasBadge = isset($badges[$item['route']]) && $badges[$item['route']] > 0;
                @endphp
                <a href="{{ route($item['route']) }}" wire:navigate
                   wire:navigate
                   class="relative group transition-all duration-200
                          @if($style === 'tabs')
                              flex-1 text-center {{ $currentSize }} font-semibold
                              {{ $active
                                  ? $colores['bgActivo'] . ' dark:bg-' . $colorBase . '-700 ' . $colores['txt'] . ' border-t-4 border-' . $colorBase . '-500 -mb-px'
                                  : $colores['bg'] . ' dark:bg-gray-800 ' . $colores['bgHover'] . ' dark:hover:bg-gray-700 ' . $colores['txt'] . ' dark:text-gray-300 border-t-4 border-transparent hover:border-' . $colorBase . '-300' }}
                          @elseif($style === 'pills')
                              {{ $currentSize }} font-medium rounded-full
                              {{ $active
                                  ? $colores['bg'] . ' dark:bg-' . $colorBase . '-600 ' . $colores['txt'] . ' shadow-md'
                                  : 'bg-gray-200 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-' . $colorBase . '-100 dark:hover:bg-gray-700 ' . $colores['txtHover'] . ' dark:hover:text-white' }}
                          @elseif($style === 'underline')
                              {{ $currentSize }} font-medium pb-3 border-b-2
                              {{ $active
                                  ? 'border-' . $colorBase . '-600 dark:border-' . $colorBase . '-400 ' . $colores['txtBase'] . ' dark:text-' . $colorBase . '-400 font-semibold'
                                  : 'border-transparent text-gray-600 dark:text-gray-400 hover:border-' . $colorBase . '-300 dark:hover:border-' . $colorBase . '-600 ' . $colores['txtHover'] . ' dark:hover:text-' . $colorBase . '-300' }}
                          @endif"
                   aria-current="{{ $active ? 'page' : 'false' }}">
                    <span class="flex items-center justify-center space-x-2">
                        @if(isset($item['icon']))
                            <span class="text-lg">{{ $item['icon'] }}</span>
                        @endif
                        <span>{{ $item['label'] }}</span>
                    </span>
                    @if($hasBadge)
                        <span class="absolute -top-1 -right-1 bg-red-600 text-white text-xs font-bold px-2 py-0.5 rounded-full shadow-sm" role="status" aria-label="{{ $badges[$item['route']] }} notificaciones">
                            {{ $badges[$item['route']] }}
                        </span>
                    @endif
                </a>
            @endforeach
        </div>
    </nav>
@endif

<style>
    [x-cloak] { display: none !important; }
</style>
