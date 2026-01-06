<x-app-layout>
    <x-slot name="title">Crear Usuario - {{ config('app.name') }}</x-slot>
    <div class="bg-gray-50 py-4 px-4">
        <div class="max-w-5xl mx-auto">
            <!-- Form Card -->
            <div class="bg-white shadow rounded-lg overflow-hidden border border-gray-200">
                <div class="bg-blue-600 px-4 py-3">
                    <h1 class="text-xl font-bold text-white">Crear Nuevo Usuario</h1>
                </div>

                <div class="p-4">
                    <form method="POST" action="{{ route('register') }}" x-data="{ cargando: false }">
                        @csrf

                        {{-- INPUT CLASSES --}}
                        @php
                            $inputClass = 'w-full px-2 py-1.5 border border-gray-300 rounded text-xs text-gray-800 focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-transparent';
                            $selectClass = 'w-full px-2 py-1.5 border border-gray-300 rounded text-xs text-gray-800 focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-transparent cursor-pointer';
                            $labelClass = 'block text-xs font-medium text-gray-700 mb-1';
                            $errorClass = 'mt-0.5 text-xs text-red-600';
                        @endphp

                        {{-- SECCIÓN: DATOS PERSONALES --}}
                        <div class="mb-3">
                            <h3 class="text-sm font-semibold text-gray-800 mb-2 pb-1 border-b border-blue-500">
                                Datos Personales
                            </h3>
                            <div class="grid grid-cols-1 md:grid-cols-4 gap-3 mt-2">
                                {{-- NOMBRE --}}
                                <div>
                                    <label for="name" class="{{ $labelClass }}">Nombre <span class="text-red-500">*</span></label>
                                    <input id="name" name="name" type="text" class="{{ $inputClass }}" value="{{ old('name') }}" required autofocus>
                                    <x-input-error :messages="$errors->get('name')" class="{{ $errorClass }}" />
                                </div>

                                {{-- PRIMER APELLIDO --}}
                                <div>
                                    <label for="primer_apellido" class="{{ $labelClass }}">Primer Apellido <span class="text-red-500">*</span></label>
                                    <input id="primer_apellido" name="primer_apellido" type="text" class="{{ $inputClass }}" value="{{ old('primer_apellido') }}" required>
                                    <x-input-error :messages="$errors->get('primer_apellido')" class="{{ $errorClass }}" />
                                </div>

                                {{-- SEGUNDO APELLIDO --}}
                                <div>
                                    <label for="segundo_apellido" class="{{ $labelClass }}">Segundo Apellido</label>
                                    <input id="segundo_apellido" name="segundo_apellido" type="text" class="{{ $inputClass }}" value="{{ old('segundo_apellido') }}">
                                    <x-input-error :messages="$errors->get('segundo_apellido')" class="{{ $errorClass }}" />
                                </div>

                                {{-- DNI --}}
                                <div>
                                    <label for="dni" class="{{ $labelClass }}">DNI <span class="text-red-500">*</span></label>
                                    <input id="dni" name="dni" type="text" class="{{ $inputClass }}" value="{{ old('dni') }}" required>
                                    <x-input-error :messages="$errors->get('dni')" class="{{ $errorClass }}" />
                                </div>
                            </div>
                        </div>

                        {{-- SECCIÓN: CONTACTO --}}
                        <div class="mb-3">
                            <h3 class="text-sm font-semibold text-gray-800 mb-2 pb-1 border-b border-green-500">
                                Contacto
                            </h3>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mt-2">
                                {{-- EMAIL --}}
                                <div>
                                    <label for="email" class="{{ $labelClass }}">Email <span class="text-red-500">*</span></label>
                                    <input id="email" name="email" type="email" class="{{ $inputClass }}" value="{{ old('email') }}" required>
                                    <x-input-error :messages="$errors->get('email')" class="{{ $errorClass }}" />
                                </div>

                                {{-- MÓVIL PERSONAL --}}
                                <div>
                                    <label for="movil_personal" class="{{ $labelClass }}">Móvil Personal</label>
                                    <input id="movil_personal" name="movil_personal" type="text" class="{{ $inputClass }}" value="{{ old('movil_personal') }}">
                                    <x-input-error :messages="$errors->get('movil_personal')" class="{{ $errorClass }}" />
                                </div>

                                {{-- MÓVIL EMPRESA --}}
                                <div>
                                    <label for="movil_empresa" class="{{ $labelClass }}">Móvil Empresa</label>
                                    <input id="movil_empresa" name="movil_empresa" type="text" class="{{ $inputClass }}" value="{{ old('movil_empresa') }}">
                                    <x-input-error :messages="$errors->get('movil_empresa')" class="{{ $errorClass }}" />
                                </div>
                            </div>
                        </div>

                        {{-- SECCIÓN: INFORMACIÓN LABORAL --}}
                        <div class="mb-3">
                            <h3 class="text-sm font-semibold text-gray-800 mb-2 pb-1 border-b border-purple-500">
                                Información Laboral
                            </h3>
                            <div class="grid grid-cols-1 md:grid-cols-4 gap-3 mt-2">
                                {{-- EMPRESA --}}
                                <div>
                                    <label for="empresa_id" class="{{ $labelClass }}">Empresa <span class="text-red-500">*</span></label>
                                    <select id="empresa_id" name="empresa_id" class="{{ $selectClass }}" required>
                                        <option value="">Selecciona</option>
                                        @foreach ($empresas as $empresa)
                                            <option value="{{ $empresa->id }}" {{ old('empresa_id') == $empresa->id ? 'selected' : '' }}>{{ ucfirst($empresa->nombre) }}</option>
                                        @endforeach
                                    </select>
                                    <x-input-error :messages="$errors->get('empresa_id')" class="{{ $errorClass }}" />
                                </div>

                                {{-- ROL --}}
                                <div>
                                    <label for="rol" class="{{ $labelClass }}">Rol <span class="text-red-500">*</span></label>
                                    <select id="rol" name="rol" class="{{ $selectClass }}" required>
                                        <option value="">Selecciona</option>
                                        <option value="operario">Operario</option>
                                        <option value="oficina">Oficina</option>
                                        <option value="transportista">Transportista</option>
                                        <option value="visitante">Visitante</option>
                                    </select>
                                    <x-input-error :messages="$errors->get('rol')" class="{{ $errorClass }}" />
                                </div>

                                {{-- CATEGORÍA --}}
                                <div>
                                    <label for="categoria_id" class="{{ $labelClass }}">Categoría <span class="text-red-500">*</span></label>
                                    <select id="categoria_id" name="categoria_id" class="{{ $selectClass }}" required>
                                        <option value="">Selecciona</option>
                                        @foreach ($categorias as $categoria)
                                            <option value="{{ $categoria->id }}" {{ old('categoria_id') == $categoria->id ? 'selected' : '' }}>{{ ucfirst($categoria->nombre) }}</option>
                                        @endforeach
                                    </select>
                                    <x-input-error :messages="$errors->get('categoria_id')" class="{{ $errorClass }}" />
                                </div>

                                {{-- TURNO --}}
                                <div>
                                    <label for="turno" class="{{ $labelClass }}">Turno</label>
                                    <select id="turno" name="turno" class="{{ $selectClass }}">
                                        <option value="">Selecciona</option>
                                        <option value="nocturno">Nocturno</option>
                                        <option value="diurno">Diurno</option>
                                        <option value="mañana">Mañana</option>
                                    </select>
                                    <x-input-error :messages="$errors->get('turno')" class="{{ $errorClass }}" />
                                </div>
                            </div>
                        </div>

                        {{-- SECCIÓN: SEGURIDAD --}}
                        <div class="mb-3">
                            <h3 class="text-sm font-semibold text-gray-800 mb-2 pb-1 border-b border-red-500">
                                Seguridad
                            </h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mt-2">
                                {{-- CONTRASEÑA --}}
                                <div>
                                    <label for="password" class="{{ $labelClass }}">Contraseña <span class="text-red-500">*</span></label>
                                    <input id="password" name="password" type="password" class="{{ $inputClass }}" required>
                                    <x-input-error :messages="$errors->get('password')" class="{{ $errorClass }}" />
                                </div>

                                {{-- CONFIRMAR CONTRASEÑA --}}
                                <div>
                                    <label for="password_confirmation" class="{{ $labelClass }}">Confirmar Contraseña <span class="text-red-500">*</span></label>
                                    <input id="password_confirmation" name="password_confirmation" type="password" class="{{ $inputClass }}" required>
                                    <x-input-error :messages="$errors->get('password_confirmation')" class="{{ $errorClass }}" />
                                </div>
                            </div>
                        </div>

                        {{-- BOTÓN DE ENVÍO --}}
                        <div class="flex justify-end pt-3 border-t border-gray-200">
                            <button type="submit" class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500" x-bind:disabled="cargando">
                                <span x-show="!cargando">Registrar Usuario</span>
                                <span x-show="cargando">Registrando...</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
