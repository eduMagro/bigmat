<x-app-layout>
    <div class="py-6">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="mb-6">
                <div class="flex items-center gap-4 mb-2">
                    <a href="{{ route('turnos.index') }}" wire:navigate
                        class="text-gray-600 hover:text-gray-900 transition">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                        </svg>
                    </a>
                    <h2 class="text-2xl font-bold text-gray-900">Editar Turno: {{ $turno->nombre }}</h2>
                </div>
                <p class="text-sm text-gray-600 ml-10">
                    Modifica los horarios y configuración del turno
                </p>
            </div>

            <!-- Formulario -->
            <div class="bg-white rounded-lg shadow-sm p-6">
                <form action="{{ route('turnos.update', $turno) }}" method="POST">
                    @csrf
                    @method('PUT')

                    @include('configuracion.turnos.form')

                    <!-- Botones -->
                    <div class="mt-6 flex items-center justify-end gap-3">
                        <a href="{{ route('turnos.index') }}" wire:navigate
                            class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition">
                            Cancelar
                        </a>
                        <button type="submit"
                            class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition">
                            Guardar Cambios
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
