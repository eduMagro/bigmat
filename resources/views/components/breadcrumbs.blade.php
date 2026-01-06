@php
    use App\Services\MenuBuilder;
    $breadcrumbs = MenuBuilder::getBreadcrumbs();
    // Ocultar breadcrumbs si solo hay Dashboard o Dashboard + Sección principal
    $shouldShowBreadcrumbs = count($breadcrumbs) > 2;

    // Obtener la ruta actual y el menú filtrado por permisos del usuario
    $currentRoute = request()->route()->getName();
    $menu = MenuBuilder::buildForUser(auth()->user());

    // Buscar la sección actual y sus pestañas (con info de disabled)
    $currentSection = null;
    $sectionTabs = [];

    foreach ($menu as $section) {
        if (isset($section['submenu'])) {
            foreach ($section['submenu'] as $item) {
                if ($item['route'] === $currentRoute || str_starts_with($currentRoute, str_replace('.index', '', $item['route']))) {
                    $currentSection = $section;
                    $sectionTabs = $section['submenu'];
                    break 2;
                }
            }
        }
    }
@endphp

@if($shouldShowBreadcrumbs)
<!-- Indicador de carga para wire:navigate -->
<div wire:loading class="fixed top-0 left-0 right-0 h-1 bg-blue-500 z-50 animate-pulse"></div>

<nav class="hidden md:flex items-center space-x-2 text-sm text-gray-600 mb-6"
     x-data="{
         currentPath: window.location.pathname,

         init() {
             // Actualizar cuando Livewire navega
             document.addEventListener('livewire:navigated', () => {
                 this.currentPath = window.location.pathname;
             });
         },

         isRouteActive(routeUrl) {
             const url = new URL(routeUrl, window.location.origin);
             return this.currentPath === url.pathname;
         },

         routeStartsWith(routeUrl) {
             const url = new URL(routeUrl, window.location.origin);
             const basePath = url.pathname.replace('/index', '');

             // Evitar falsos positivos: solo considerar activo si la ruta coincide exactamente
             // o si es una subruta con separador (ej: /trabajadores/edit pero no /trabajadores-obra)
             if (this.currentPath === basePath) return true;
             if (this.currentPath.startsWith(basePath + '/')) return true;

             return false;
         }
     }">
    @foreach($breadcrumbs as $index => $crumb)
        @if($index > 0)
            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
            </svg>
        @endif

        @if($crumb['route'] && $index < count($breadcrumbs) - 1)
            <a href="{{ route($crumb['route']) }}" wire:navigate
               wire:navigate
               class="hover:text-blue-600 transition-all duration-200 hover:underline">
                {{ $crumb['label'] }}
            </a>
        @else
            {{-- Si es el último elemento y tenemos pestañas de sección, mostrarlas --}}
            @if(count($sectionTabs) > 0 && $index === count($breadcrumbs) - 1)
                <div class="flex items-center space-x-1 flex-wrap gap-y-1">
                    @foreach($sectionTabs as $tabIndex => $tab)
                        @if($tabIndex > 0)
                            <span class="text-gray-400">|</span>
                        @endif
                        @if(!empty($tab['disabled']))
                            {{-- Pestaña DESHABILITADA --}}
                            <span class="px-3 py-1 rounded whitespace-nowrap text-gray-400 cursor-not-allowed opacity-60 inline-flex items-center gap-1"
                                  title="No tienes permiso para acceder a esta sección">
                                {{ $tab['icon'] ?? '' }} {{ $tab['label'] }}
                                <svg class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                </svg>
                            </span>
                        @else
                            {{-- Pestaña HABILITADA --}}
                            <a href="{{ route($tab['route']) }}" wire:navigate
                               :class="isRouteActive('{{ route($tab['route']) }}') || routeStartsWith('{{ route($tab['route']) }}')
                                   ? 'bg-blue-100 text-blue-700 font-semibold'
                                   : 'text-gray-600 hover:text-blue-600 hover:bg-blue-50'"
                               class="px-3 py-1 rounded whitespace-nowrap transition-all duration-200">
                                {{ $tab['icon'] ?? '' }} {{ $tab['label'] }}
                            </a>
                        @endif
                    @endforeach
                </div>
            @else
                <span class="font-medium text-gray-900">{{ $crumb['label'] }}</span>
            @endif
        @endif
    @endforeach
</nav>
@endif
