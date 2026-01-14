<x-guest-layout>
    <div class="w-full" x-data="{ tab: 'privacidad' }">
        <!-- Header -->
        <div class="text-center mb-4">
            <h2 class="text-lg font-bold text-gray-900">Hola, {{ $user->name }}</h2>
            <p class="text-xs text-gray-500">Acepta las politicas para continuar</p>
        </div>

        <form method="POST" action="{{ route('politicas.aceptar') }}" id="formPoliticas">
            @csrf

            <!-- Tabs -->
            <div class="flex border-b border-gray-200 mb-3">
                <button type="button" @click="tab = 'privacidad'"
                    :class="tab === 'privacidad' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-400'"
                    class="flex-1 py-2 text-xs font-medium border-b-2 focus:outline-none">
                    Privacidad
                </button>
                <button type="button" @click="tab = 'cookies'"
                    :class="tab === 'cookies' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-400'"
                    class="flex-1 py-2 text-xs font-medium border-b-2 focus:outline-none">
                    Cookies
                </button>
                <button type="button" @click="tab = 'terminos'"
                    :class="tab === 'terminos' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-400'"
                    class="flex-1 py-2 text-xs font-medium border-b-2 focus:outline-none">
                    Terminos
                </button>
            </div>

            <!-- Contenido scrollable -->
            <div class="h-40 md:h-48 overflow-y-auto border rounded-lg p-2 mb-3 text-[11px] leading-relaxed text-gray-600 bg-gray-50">
                <div x-show="tab === 'privacidad'">
                    @include('politicas.contenido.privacidad')
                </div>
                <div x-show="tab === 'cookies'" x-cloak>
                    @include('politicas.contenido.cookies')
                </div>
                <div x-show="tab === 'terminos'" x-cloak>
                    @include('politicas.contenido.terminos')
                </div>
            </div>

            <!-- Checkboxes compactos -->
            <div class="space-y-2 mb-4">
                <label class="flex items-center cursor-pointer">
                    <input type="checkbox" name="acepta_privacidad" value="1"
                        class="h-4 w-4 text-blue-600 rounded border-gray-300 focus:ring-blue-500">
                    <span class="ml-2 text-xs text-gray-700">Acepto <strong>Privacidad</strong></span>
                </label>
                <label class="flex items-center cursor-pointer">
                    <input type="checkbox" name="acepta_cookies" value="1"
                        class="h-4 w-4 text-blue-600 rounded border-gray-300 focus:ring-blue-500">
                    <span class="ml-2 text-xs text-gray-700">Acepto <strong>Cookies</strong></span>
                </label>
                <label class="flex items-center cursor-pointer">
                    <input type="checkbox" name="acepta_terminos" value="1"
                        class="h-4 w-4 text-blue-600 rounded border-gray-300 focus:ring-blue-500">
                    <span class="ml-2 text-xs text-gray-700">Acepto <strong>Terminos</strong></span>
                </label>
            </div>

            @if ($errors->any())
                <p class="text-xs text-red-600 mb-3">Debes aceptar todas las politicas</p>
            @endif

            <!-- Botones -->
            <div class="flex items-center justify-between">
                <span class="text-xs text-gray-400"><span id="checkCount">0</span>/3</span>
                <div class="flex items-center gap-3">
                    <button type="button" onclick="document.getElementById('logoutForm').submit()"
                        class="text-xs text-gray-400 hover:text-gray-600">Salir</button>
                    <button type="submit" id="btnAceptar"
                        class="px-4 py-2 bg-blue-600 text-white text-xs font-medium rounded-lg disabled:opacity-40"
                        disabled>
                        Aceptar
                    </button>
                </div>
            </div>
        </form>

        <!-- Form de logout separado -->
        <form method="POST" action="{{ route('logout') }}" id="logoutForm" class="hidden">
            @csrf
        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const checkboxes = document.querySelectorAll('input[type="checkbox"]');
            const btnAceptar = document.getElementById('btnAceptar');
            const checkCount = document.getElementById('checkCount');

            function updateState() {
                const checked = document.querySelectorAll('input[type="checkbox"]:checked').length;
                checkCount.textContent = checked;
                btnAceptar.disabled = checked < 3;
            }

            checkboxes.forEach(cb => cb.addEventListener('change', updateState));
            updateState();
        });
    </script>
</x-guest-layout>
