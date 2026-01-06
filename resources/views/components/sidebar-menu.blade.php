@php
    use App\Services\MenuBuilder;
    $menuItems = MenuBuilder::buildForUser(auth()->user());
    $currentRoute = request()->route()->getName();
@endphp

<div x-data="{
    open: true,
    activeSection: null,
    searchOpen: false,
    searchQuery: '',
    init() {
        // Detectar sección activa basada en ruta actual
        @foreach($menuItems as $index => $section)
            @if(isset($section['submenu']))
                @foreach($section['submenu'] as $item)
                    @if($currentRoute === $item['route'] || str_starts_with($currentRoute, explode('.', $item['route'])[0]))
                        this.activeSection = '{{ $section['id'] }}';
                    @endif
                @endforeach
            @endif
        @endforeach

        // Atajo de teclado Cmd/Ctrl + K para búsqueda
        document.addEventListener('keydown', (e) => {
            if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
                e.preventDefault();
                this.searchOpen = !this.searchOpen;
                if (this.searchOpen) {
                    this.$nextTick(() => this.$refs.searchInput?.focus());
                }
            }
        });
    }
}" class="flex h-screen">

    <!-- Sidebar -->
    <div :class="open ? 'w-64' : 'w-16'"
         class="bg-gray-900 text-white transition-all duration-300 ease-in-out flex-shrink-0 flex flex-col">

        <!-- Header del Sidebar -->
        <div class="p-4 flex items-center justify-between border-b border-gray-700">
            <div x-show="open" x-transition class="flex items-center space-x-2">
                <span class="text-lg font-bold">Manager</span>
            </div>
            <button @click="open = !open" class="p-2 hover:bg-gray-800 rounded-lg transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          :d="open ? 'M11 19l-7-7 7-7m8 14l-7-7 7-7' : 'M13 5l7 7-7 7M5 5l7 7-7 7'"></path>
                </svg>
            </button>
        </div>

        <!-- Botón de Búsqueda -->
        <div class="p-4">
            <button @click="searchOpen = true"
                    class="w-full flex items-center space-x-3 px-3 py-2 rounded-lg bg-gray-800 hover:bg-gray-700 transition text-sm">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
                <span x-show="open" class="text-gray-400">Buscar... (Ctrl+K)</span>
            </button>
        </div>

        <!-- Navegación principal -->
        <nav class="flex-1 overflow-y-auto px-2 py-4 space-y-1">
            @foreach($menuItems as $section)
                <div class="mb-2">
                    <!-- Sección Principal -->
                    <button
                        @click="activeSection = activeSection === '{{ $section['id'] }}' ? null : '{{ $section['id'] }}'"
                        class="w-full flex items-center justify-between px-3 py-2 rounded-lg transition
                               {{ $currentRoute === $section['route'] ? 'bg-'.$section['color'].'-600 text-white' : 'text-gray-300 hover:bg-gray-800' }}">
                        <div class="flex items-center space-x-3">
                            <span class="text-xl flex-shrink-0">{{ $section['icon'] }}</span>
                            <span x-show="open" x-transition class="font-medium">{{ $section['label'] }}</span>
                        </div>
                        <svg x-show="open"
                             :class="activeSection === '{{ $section['id'] }}' ? 'rotate-180' : ''"
                             class="w-4 h-4 flex-shrink-0 transition-transform"
                             fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>

                    <!-- Submenú -->
                    @if(isset($section['submenu']))
                        <div x-show="open && activeSection === '{{ $section['id'] }}'"
                             x-transition
                             class="mt-2 ml-4 space-y-1 border-l-2 border-gray-700 pl-4">
                            @foreach($section['submenu'] as $item)
                                <div x-data="{ showActions: false }">
                                    <a href="{{ route($item['route']) }}" wire:navigate
                                       @mouseenter="showActions = true"
                                       @mouseleave="showActions = false"
                                       class="flex items-center justify-between px-3 py-2 rounded-lg text-sm transition group
                                              {{ $currentRoute === $item['route'] || str_starts_with($currentRoute, explode('.', $item['route'])[0])
                                                 ? 'bg-'.$section['color'].'-500 text-white'
                                                 : 'text-gray-400 hover:text-white hover:bg-gray-800' }}">
                                        <div class="flex items-center space-x-2">
                                            <span>{{ $item['icon'] }}</span>
                                            <span>{{ $item['label'] }}</span>
                                        </div>
                                        @if(isset($item['badge']))
                                            <span class="bg-red-500 text-white text-xs px-2 py-0.5 rounded-full">3</span>
                                        @endif
                                    </a>

                                    <!-- Acciones rápidas (aparecen al hover) -->
                                    @if(isset($item['actions']) && count($item['actions']) > 1)
                                        <div x-show="showActions"
                                             x-transition
                                             class="ml-6 mt-1 space-y-1">
                                            @foreach($item['actions'] as $action)
                                                @if($action['route'] !== $item['route'])
                                                    <a href="{{ route($action['route']) }}" wire:navigate
                                                       class="block px-3 py-1 text-xs text-gray-500 hover:text-white hover:bg-gray-800 rounded transition">
                                                        {{ $action['label'] }}
                                                    </a>
                                                @endif
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endforeach
        </nav>

        <!-- Footer del Sidebar -->
        <div class="p-4 border-t border-gray-700">
            <a href="{{ route('dashboard') }}" wire:navigate
               class="flex items-center space-x-3 px-3 py-2 rounded-lg hover:bg-gray-800 transition text-sm">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                </svg>
                <span x-show="open" class="text-gray-400">Dashboard</span>
            </a>
        </div>
    </div>

    <!-- Modal de Búsqueda Global -->
    <div x-show="searchOpen"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @click.self="searchOpen = false"
         class="fixed inset-0 z-50 flex items-start justify-center pt-20 px-4 bg-black bg-opacity-50">

        <div @click.away="searchOpen = false"
             class="bg-white rounded-lg shadow-2xl w-full max-w-2xl">
            <!-- Input de búsqueda -->
            <div class="p-4 border-b">
                <input x-ref="searchInput"
                       x-model="searchQuery"
                       type="text"
                       placeholder="Buscar módulos, acciones..."
                       class="w-full px-4 py-3 text-lg border-0 focus:ring-0 focus:outline-none"
                       @keydown.escape="searchOpen = false">
            </div>

            <!-- Resultados -->
            <div class="max-h-96 overflow-y-auto p-2">
                @foreach($menuItems as $section)
                    @if(isset($section['submenu']))
                        @foreach($section['submenu'] as $item)
                            <a href="{{ route($item['route']) }}" wire:navigate
                               x-show="searchQuery === '' || '{{ strtolower($section['label'].' '.$item['label']) }}'.includes(searchQuery.toLowerCase())"
                               class="block px-4 py-3 hover:bg-gray-100 rounded-lg transition">
                                <div class="flex items-center space-x-3">
                                    <span class="text-2xl">{{ $item['icon'] }}</span>
                                    <div>
                                        <div class="font-medium text-gray-900">{{ $item['label'] }}</div>
                                        <div class="text-sm text-gray-500">{{ $section['label'] }}</div>
                                    </div>
                                </div>
                            </a>
                        @endforeach
                    @endif
                @endforeach
            </div>

            <!-- Footer del modal -->
            <div class="px-4 py-3 bg-gray-50 border-t text-xs text-gray-500 flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <span class="flex items-center"><kbd class="px-2 py-1 bg-white border rounded">↑↓</kbd> Navegar</span>
                    <span class="flex items-center"><kbd class="px-2 py-1 bg-white border rounded">Enter</kbd> Seleccionar</span>
                </div>
                <span class="flex items-center"><kbd class="px-2 py-1 bg-white border rounded">ESC</kbd> Cerrar</span>
            </div>
        </div>
    </div>
</div>
