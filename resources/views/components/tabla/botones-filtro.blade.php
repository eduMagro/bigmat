@props([
    'ruta', // Ruta base para resetear
    'rutaExportar' => null, // Ruta para exportar, opcional
])

<th class="p-1 border text-center align-middle">
    <div class="flex justify-center gap-2 items-center h-full">
        {{-- 🔍 Botón buscar --}}
        <button type="submit"
            class="bg-blue-600 hover:bg-blue-700 text-white px-2 py-1 rounded text-xs flex items-center justify-center"
            title="Buscar">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M21 21l-4.35-4.35m1.3-5.4a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
        </button>

        {{-- ♻️ Botón reset --}}
        <a href="{{ route($ruta) }}" wire:navigate
            class="bg-yellow-500 hover:bg-yellow-600 text-white px-2 py-1 rounded text-xs flex items-center justify-center"
            title="Restablecer filtros">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M4 4v5h.582M20 20v-5h-.581M4.582 9A7.5 7.5 0 0112 4.5a7.5 7.5 0 016.418 3.418M19.418 15A7.5 7.5 0 0112 19.5a7.5 7.5 0 01-6.418-3.418" />
            </svg>
        </a>

        {{-- 📤 Botón exportar Excel --}}
        @if ($rutaExportar)
            <a href="{{ route($rutaExportar, request()->query()) }}" wire:navigate title="Descarga los registros en Excel"
                class="bg-green-600 hover:bg-green-700 text-white rounded text-xs flex items-center justify-center">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48" class="h-6 w-8">
                    <path fill="#21A366"
                        d="M6 8c0-1.1.9-2 2-2h32c1.1 0 2 .9 2 2v32c0 1.1-.9 2-2 2H8c-1.1 0-2-.9-2-2V8z" />
                    <path fill="#107C41" d="M8 8h16v32H8c-1.1 0-2-.9-2-2V10c0-1.1.9-2 2-2z" />
                    <path fill="#33C481" d="M24 8h16v32H24z" />
                    <path fill="#fff"
                        d="M17.2 17h3.6l3.1 5.3 3.1-5.3h3.6l-5.1 8.4 5.3 8.6h-3.7l-3.3-5.6-3.3 5.6h-3.7l5.3-8.6-5.1-8.4z" />
                </svg>
            </a>
        @endif


    </div>
</th>
