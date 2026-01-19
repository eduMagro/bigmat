<x-app-layout>
    <x-slot name="title">Nueva Incorporación</x-slot>

    <div class="w-full max-w-2xl mx-auto py-6 px-4 sm:px-6">
        <!-- Cabecera -->
        <div class="mb-6">
            <a href="{{ route('incorporaciones.index') }}" class="text-blue-600 hover:text-blue-800 flex items-center mb-4">
                <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
                Volver al listado
            </a>
            <h1 class="text-2xl font-bold text-gray-800">Nueva Incorporación</h1>
            <p class="text-gray-600 mt-1">Crea una nueva incorporación y genera el enlace para el candidato</p>
        </div>

        <!-- Formulario -->
        <div class="bg-white rounded-lg shadow-sm border p-6">
            <form method="POST" action="{{ route('incorporaciones.store') }}" class="space-y-6">
                @csrf

                <!-- Empresa destino -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Empresa destino <span class="text-red-500">*</span>
                    </label>
                    <select name="empresa_destino" required
                        class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500">
                        <option value="">-- Seleccionar empresa --</option>
                        @foreach($empresas as $empresa)
                            <option value="{{ $empresa->id }}" {{ old('empresa_destino') == $empresa->id ? 'selected' : '' }}>
                                {{ $empresa->nombre }}
                            </option>
                        @endforeach
                    </select>
                    @error('empresa_destino')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Nombre -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                            Nombre <span class="text-red-500">*</span>
                        </label>
                        <input type="text" id="name" name="name" value="{{ old('name') }}"
                            placeholder="Nombre"
                            class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500" required>
                        @error('name')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="primer_apellido" class="block text-sm font-medium text-gray-700 mb-2">
                            Primer apellido
                        </label>
                        <input type="text" id="primer_apellido" name="primer_apellido" value="{{ old('primer_apellido') }}"
                            placeholder="Primer apellido"
                            class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500">
                        @error('primer_apellido')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="segundo_apellido" class="block text-sm font-medium text-gray-700 mb-2">
                            Segundo apellido
                        </label>
                        <input type="text" id="segundo_apellido" name="segundo_apellido" value="{{ old('segundo_apellido') }}"
                            placeholder="Segundo apellido"
                            class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500">
                        @error('segundo_apellido')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Teléfono provisional -->
                <div>
                    <label for="telefono_provisional" class="block text-sm font-medium text-gray-700 mb-2">
                        Teléfono <span class="text-red-500">*</span>
                    </label>
                    <input type="tel" id="telefono_provisional" name="telefono_provisional" value="{{ old('telefono_provisional') }}"
                        placeholder="612345678"
                        class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500" required>
                    <p class="mt-1 text-xs text-gray-500">Para enviar el enlace por WhatsApp</p>
                    @error('telefono_provisional')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Botones -->
                <div class="flex justify-end gap-4 pt-4 border-t">
                    <a href="{{ route('incorporaciones.index') }}"
                        class="px-4 py-2 text-gray-700 hover:text-gray-900">
                        Cancelar
                    </a>
                    <button type="submit"
                        class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition">
                        Crear Incorporación
                    </button>
                </div>
            </form>
        </div>

        <!-- Info -->
        <div class="mt-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
            <h3 class="font-medium text-blue-800 mb-2">¿Cómo funciona?</h3>
            <ol class="list-decimal list-inside text-sm text-blue-700 space-y-1">
                <li>Crea la incorporación con los datos básicos del candidato</li>
                <li>Se generará un enlace único para que el candidato complete sus datos</li>
                <li>Envía el enlace por email o WhatsApp al candidato</li>
                <li>Cuando el candidato complete el formulario, recibirás los documentos</li>
                <li>Completa la documentación post-incorporación desde el panel</li>
            </ol>
        </div>
    </div>
</x-app-layout>
