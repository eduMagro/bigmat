<x-app-layout>

    <div class="w-full px-2 sm:px-4 md:px-6 py-4">

        <div x-data="{ mostrarModal: false }">
            <button @click="mostrarModal = true"
                class="w-full md:w-auto bg-blue-500 hover:bg-blue-600 active:bg-blue-700 text-white font-bold py-3 md:py-2 px-6 md:px-4 mb-4 md:mb-2 rounded-lg shadow-md hover:shadow-lg transition-all duration-150 flex items-center justify-center space-x-2">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" />
                </svg>
                <span>Enviar Mensaje</span>
            </button>

            <!-- Modal de creación de alerta -->
            <div x-show="mostrarModal" x-cloak
                class="fixed inset-0 flex items-end md:items-center justify-center bg-gray-900 bg-opacity-50 z-50 p-0 md:p-4"
                style="display: none;" x-transition.opacity @click.self="mostrarModal = false">
                <div class="bg-white rounded-t-2xl md:rounded-xl shadow-2xl p-6 w-full md:w-96 max-h-[90vh] md:max-h-[85vh] overflow-y-auto relative"
                    x-transition:enter="transition ease-out duration-300"
                    x-transition:enter-start="opacity-0 translate-y-full md:translate-y-0 md:scale-95"
                    x-transition:enter-end="opacity-100 translate-y-0 md:scale-100"
                    x-transition:leave="transition ease-in duration-200"
                    x-transition:leave-start="opacity-100 translate-y-0 md:scale-100"
                    x-transition:leave-end="opacity-0 translate-y-full md:translate-y-0 md:scale-95">

                    <!-- Indicador visual de modal móvil -->
                    <div class="md:hidden w-12 h-1 bg-gray-300 rounded-full mx-auto mb-4"></div>

                    <button @click="mostrarModal = false"
                        class="absolute top-4 right-4 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-full p-2 transition-colors duration-150">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                    <h2 class="text-xl font-bold mb-6 text-gray-900 flex items-center space-x-2">
                        <svg class="w-6 h-6 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z" />
                            <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z" />
                        </svg>
                        <span>Enviar Mensaje</span>
                    </h2>
                    <form method="POST" action="{{ route('alertas.store') }}" enctype="multipart/form-data"
                        x-data="{ cargando: false }" @submit="cargando = true">
                        @csrf
                        <div class="mb-4">
                            <label for="mensaje" class="block text-sm font-semibold">Mensaje:</label>
                            <textarea id="mensaje" name="mensaje" rows="3"
                                class="w-full border rounded-lg p-2 focus:ring-2 focus:ring-blue-500" required>{{ old('mensaje') }}</textarea>
                        </div>
                        {{-- <div class="mb-4">
                            <label for="imagen" class="block text-sm font-semibold">Imagen (opcional):</label>
                            <input type="file" id="imagen" name="imagen"
                                class="w-full border rounded-lg p-2 focus:ring-2 focus:ring-blue-500" accept="image/*">
                        </div> --}}

                        @if (auth()->user()->rol === 'oficina')
                            <div class="mb-4">
                                <label for="rol" class="block text-sm font-semibold">Rol</label>
                                <select id="rol" name="rol" class="w-full border rounded-lg p-2">
                                    <option value="">-- Seleccionar un Rol --</option>
                                    @foreach ($roles as $rol)
                                        <option value="{{ $rol }}">{{ ucfirst($rol) }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="mb-4">
                                <label for="categoria" class="block text-sm font-semibold">Categoría</label>
                                <select id="categoria" name="categoria" class="w-full border rounded-lg p-2">
                                    <option value="">-- Seleccionar una Categoría --</option>
                                    @foreach ($categorias as $categoria)
                                        <option value="{{ $categoria }}">{{ ucfirst($categoria) }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="mb-4">
                                <label for="destinatario_id" class="block text-sm font-semibold">Destinatario
                                    Personal</label>
                                <select id="destinatario_id" name="destinatario_id"
                                    class="w-full border rounded-lg p-2">
                                    <option value="">-- Seleccionar un Usuario --</option>
                                    @foreach ($usuarios as $usuario)
                                        <option value="{{ $usuario->id }}">{{ $usuario->nombre_completo }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @else
                            <!-- Para usuarios que no son de oficina: ocultamos los campos -->
                            <input type="hidden" name="enviar_a_departamentos" value="Programador,Administrador">
                        @endif

                        <div class="flex justify-end space-x-2">
                            <button type="button" @click="mostrarModal = false"
                                class="bg-gray-400 hover:bg-gray-500 text-white py-2 px-4 rounded-lg">
                                Cancelar
                            </button>
                            <x-boton-submit texto="Enviar" color="blue" />

                        </div>
                    </form>
                </div>
            </div>

            <!-- Vista Desktop (Tabla) -->
            <div class="hidden md:block w-full overflow-x-auto bg-white shadow-lg rounded-lg">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gradient-to-r from-blue-600 to-blue-500 text-white">
                        <tr>
                            <th class="px-4 py-3 text-xs font-semibold text-center uppercase tracking-wider">
                                Estado
                            </th>
                            <th class="px-4 py-3 text-xs font-semibold text-center uppercase tracking-wider">
                                Enviado por
                            </th>
                            <th class="px-4 py-3 text-xs font-semibold text-left uppercase tracking-wider">
                                Mensaje
                            </th>
                            <th class="px-4 py-3 text-xs font-semibold text-center uppercase tracking-wider">
                                Fecha
                            </th>
                            <th class="px-4 py-3 text-xs font-semibold text-center uppercase tracking-wider">
                                Acciones
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200 text-sm">
                        @forelse ($alertas as $alerta)
                            @php
                                $esParaListado = $alerta->leidas->contains('user_id', $user->id);
                                $esEntrante = $alerta->tipo === 'entrante' || $esParaListado;
                                $esSaliente = $alerta->user_id_1 === $user->id && !$esEntrante;
                                $noLeida = $esEntrante && empty($alertasLeidas[$alerta->id]);
                                // Mostrar badge "Nuevo" si no está leída O si tiene respuestas nuevas
                                $mostrarBadge = $noLeida || ($alerta->tiene_respuestas_nuevas ?? false);
                            @endphp

                            <tr class="cursor-pointer transition-all duration-200 hover:bg-blue-50 hover:shadow-md {{ $mostrarBadge ? 'bg-yellow-50 border-l-4 border-yellow-400' : 'bg-white' }}"
                                data-alerta-id="{{ $alerta->id }}"
                                onclick="marcarAlertaLeida({{ $alerta->id }}, this, @js($alerta->mensaje_completo), {{ $esSaliente ? 'true' : 'false' }})">

                                <!-- Columna Estado -->
                                <td class="px-4 py-3 text-center" onclick="event.stopPropagation()">
                                    @if ($alerta->tipo === 'entrante')
                                        <span
                                            class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 border border-green-200">
                                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                <path
                                                    d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z" />
                                                <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z" />
                                            </svg>
                                            Recibido
                                        </span>
                                    @else
                                        <span
                                            class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800 border border-blue-200">
                                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                <path
                                                    d="M10.894 2.553a1 1 0 00-1.788 0l-7 14a1 1 0 001.169 1.409l5-1.429A1 1 0 009 15.571V11a1 1 0 112 0v4.571a1 1 0 00.725.962l5 1.428a1 1 0 001.17-1.408l-7-14z" />
                                            </svg>
                                            Enviado
                                        </span>
                                    @endif
                                    @if ($mostrarBadge)
                                        <div class="mt-1">
                                            <span
                                                class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-yellow-400 text-yellow-900 animate-pulse">
                                                <svg class="w-2 h-2 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                    <circle cx="10" cy="10" r="8" />
                                                </svg>
                                                Nuevo
                                            </span>
                                        </div>
                                    @endif
                                </td>

                                <!-- Columna Enviado por -->
                                <td class="px-4 py-3 text-center">
                                    <div class="flex flex-col items-center">
                                        <span class="font-medium text-gray-900">
                                            {{ $alerta->nombre_emisor }}
                                        </span>
                                    </div>
                                </td>

                                <!-- Columna Mensaje -->
                                <td class="px-4 py-3">
                                    <div class="flex items-start justify-between space-x-2">
                                        <div class="flex-1 min-w-0">
                                            {{-- Mensaje acortado en móvil --}}
                                            <p class="text-gray-800 line-clamp-2 lg:hidden"
                                                title="{{ $alerta->mensaje_completo }}">
                                                {{ $alerta->mensaje_corto }}
                                            </p>
                                            {{-- Mensaje completo en PC --}}
                                            <p class="text-gray-800 hidden lg:block">
                                                {!! $alerta->mensaje !!}
                                            </p>
                                            @if (strlen($alerta->mensaje_completo) > strlen($alerta->mensaje_corto))
                                                <span
                                                    class="text-xs text-blue-600 hover:text-blue-800 font-medium lg:hidden">
                                                    Clic para ver completo...
                                                </span>
                                            @endif
                                        </div>
                                        @if ($alerta->total_respuestas > 0)
                                            <span
                                                class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-blue-100 text-blue-800 border border-blue-200 whitespace-nowrap">
                                                <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd"
                                                        d="M18 10c0 3.866-3.582 7-8 7a8.841 8.841 0 01-4.083-.98L2 17l1.338-3.123C2.493 12.767 2 11.434 2 10c0-3.866 3.582-7 8-7s8 3.134 8 7zM7 9H5v2h2V9zm8 0h-2v2h2V9zM9 9h2v2H9V9z"
                                                        clip-rule="evenodd" />
                                                </svg>
                                                {{ $alerta->total_respuestas }}
                                            </span>
                                        @endif
                                    </div>
                                </td>

                                <!-- Columna Fecha -->
                                <td class="px-4 py-3 text-center">
                                    <div class="flex flex-col items-center">
                                        <span
                                            class="text-gray-900 font-medium">{{ $alerta->created_at->diffForHumans() }}</span>
                                        <span
                                            class="text-xs text-gray-500 mt-0.5">{{ $alerta->created_at->format('d/m/Y H:i') }}</span>
                                    </div>
                                </td>

                                <!-- Columna Acciones -->
                                <td class="px-4 py-3 text-center" onclick="event.stopPropagation()">
                                    <div class="flex justify-center space-x-2">
                                        @if ($esSaliente)
                                            <button onclick="eliminarAlerta({{ $alerta->id }})"
                                                class="inline-flex items-center px-3 py-1.5 bg-red-500 hover:bg-red-600 text-white text-xs font-medium rounded-md transition-colors duration-150 shadow-sm hover:shadow-md"
                                                title="Eliminar mensaje">
                                                <svg class="w-3.5 h-3.5 mr-1" fill="currentColor"
                                                    viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd"
                                                        d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z"
                                                        clip-rule="evenodd" />
                                                </svg>
                                                Eliminar
                                            </button>
                                        @else
                                            <span class="text-xs text-gray-400 italic">Sin acciones</span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-8 text-gray-500">
                                    <svg class="w-12 h-12 mx-auto mb-2 text-gray-300" fill="none"
                                        stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                                    </svg>
                                    <p class="font-medium">No hay mensajes registrados</p>
                                    <p class="text-sm mt-1">Los mensajes que recibas o envíes aparecerán aquí</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Vista Mobile (Cards) -->
            <div class="md:hidden space-y-3">
                @forelse ($alertas as $alerta)
                    @php
                        $esParaListado = $alerta->leidas->contains('user_id', $user->id);
                        $esEntrante = $alerta->tipo === 'entrante' || $esParaListado;
                        $esSaliente = $alerta->user_id_1 === $user->id && !$esEntrante;
                        $noLeida = $esEntrante && empty($alertasLeidas[$alerta->id]);
                        $mostrarBadge = $noLeida || ($alerta->tiene_respuestas_nuevas ?? false);
                        $usuario = $alerta->usuario1;
                    @endphp

                    <div class="bg-white rounded-lg shadow-md overflow-hidden {{ $mostrarBadge ? 'ring-2 ring-yellow-400 bg-yellow-50' : '' }}"
                        data-alerta-id="{{ $alerta->id }}">

                        <!-- Header de la card -->
                        <div
                            class="bg-gradient-to-r {{ $alerta->tipo === 'entrante' ? 'from-green-500 to-green-400' : 'from-blue-500 to-blue-400' }} px-4 py-3 flex items-center justify-between">
                            <div class="flex items-center space-x-2">
                                <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                    @if ($alerta->tipo === 'entrante')
                                        <path
                                            d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z" />
                                        <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z" />
                                    @else
                                        <path
                                            d="M10.894 2.553a1 1 0 00-1.788 0l-7 14a1 1 0 001.169 1.409l5-1.429A1 1 0 009 15.571V11a1 1 0 112 0v4.571a1 1 0 00.725.962l5 1.428a1 1 0 001.17-1.408l-7-14z" />
                                    @endif
                                </svg>
                                <span class="text-white font-semibold text-sm">
                                    {{ $alerta->tipo === 'entrante' ? 'Recibido' : 'Enviado' }}
                                </span>
                                @if ($mostrarBadge)
                                    <span
                                        class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-yellow-400 text-yellow-900 animate-pulse">
                                        NUEVO
                                    </span>
                                @endif
                            </div>
                            <span class="text-white text-xs">{{ $alerta->created_at->diffForHumans() }}</span>
                        </div>

                        <!-- Contenido de la card -->
                        <div class="p-4 space-y-3"
                            onclick="marcarAlertaLeida({{ $alerta->id }}, this.closest('[data-alerta-id]'), @js($alerta->mensaje_completo), {{ $esSaliente ? 'true' : 'false' }})">

                            <!-- Emisor -->
                            <div class="flex items-start space-x-3">
                                <div class="flex-shrink-0">
                                    <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center">
                                        <svg class="w-6 h-6 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd"
                                                d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z"
                                                clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-gray-900">
                                        {{ $alerta->nombre_emisor }}
                                    </p>
                                </div>
                            </div>

                            <!-- Mensaje -->
                            <div class="bg-gray-50 rounded-lg p-3 border border-gray-200">
                                {{-- Mensaje acortado en móvil --}}
                                <p class="text-sm text-gray-800 line-clamp-3 lg:hidden">
                                    {{ $alerta->mensaje_corto }}
                                </p>
                                {{-- Mensaje completo en PC --}}
                                <p class="text-sm text-gray-800 hidden lg:block">
                                    {!! $alerta->mensaje !!}
                                </p>

                                <div class="flex items-center justify-between mt-2 lg:hidden">
                                    @if (strlen($alerta->mensaje_completo) > strlen($alerta->mensaje_corto))
                                        <button class="text-xs text-blue-600 font-medium flex items-center">
                                            <span>Ver mensaje completo</span>
                                            <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M9 5l7 7-7 7" />
                                            </svg>
                                        </button>
                                    @endif

                                    @if ($alerta->total_respuestas > 0)
                                        <span
                                            class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-800 border border-blue-200">
                                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd"
                                                    d="M18 10c0 3.866-3.582 7-8 7a8.841 8.841 0 01-4.083-.98L2 17l1.338-3.123C2.493 12.767 2 11.434 2 10c0-3.866 3.582-7 8-7s8 3.134 8 7zM7 9H5v2h2V9zm8 0h-2v2h2V9zM9 9h2v2H9V9z"
                                                    clip-rule="evenodd" />
                                            </svg>
                                            {{ $alerta->total_respuestas }}
                                            {{ $alerta->total_respuestas == 1 ? 'respuesta' : 'respuestas' }}
                                        </span>
                                    @endif
                                </div>
                            </div>

                            <!-- Footer -->
                            <div class="flex items-center justify-between pt-2 border-t border-gray-200">
                                <span class="text-xs text-gray-500">
                                    {{ $alerta->created_at->format('d/m/Y H:i') }}
                                </span>
                                @if ($esSaliente)
                                    <button onclick="event.stopPropagation(); eliminarAlerta({{ $alerta->id }})"
                                        class="inline-flex items-center px-3 py-2 bg-red-500 hover:bg-red-600 text-white text-xs font-medium rounded-lg transition-colors duration-150 active:scale-95">
                                        <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd"
                                                d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z"
                                                clip-rule="evenodd" />
                                        </svg>
                                        Eliminar
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-12 bg-white rounded-lg shadow-md">
                        <svg class="w-16 h-16 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                        </svg>
                        <p class="font-medium text-gray-900 text-lg">No hay mensajes</p>
                        <p class="text-sm text-gray-500 mt-1">Los mensajes que recibas o envíes aparecerán aquí</p>
                    </div>
                @endforelse
            </div>


            <!-- Paginación -->
            <x-tabla.paginacion :paginador="$alertas" />
        </div>
    </div>

    @if ($esAdministrador)
        <x-tabla.filtros-aplicados :filtros="$filtrosActivos" />

        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between mt-6 md:mt-8 mb-4 gap-3">
            <h2 class="text-lg sm:text-xl md:text-2xl font-bold text-blue-900 flex items-center">
                <svg class="w-6 h-6 md:w-7 md:h-7 inline-block mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z" />
                    <path fill-rule="evenodd"
                        d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z"
                        clip-rule="evenodd" />
                </svg>
                <span class="hidden sm:inline">Panel de Administración</span>
                <span class="sm:hidden">Panel Admin</span>
            </h2>
            <span
                class="text-xs sm:text-sm text-gray-600 bg-gray-100 px-3 py-1.5 rounded-full font-medium whitespace-nowrap">
                Total: {{ $todasLasAlertas->total() }}
            </span>
        </div>

        <!-- Formulario de filtros para móvil -->
        <div class="md:hidden bg-white rounded-lg shadow-md p-4 mb-4">
            <form method="GET" action="{{ route('alertas.index') }}" class="space-y-3">
                <div>
                    <label class="text-xs font-semibold text-gray-700 block mb-1">Emisor</label>
                    <x-tabla.input name="emisor" value="{{ request('emisor') }}" />
                </div>
                <div>
                    <label class="text-xs font-semibold text-gray-700 block mb-1">Mensaje</label>
                    <x-tabla.input name="mensaje" value="{{ request('mensaje') }}" />
                </div>
                <div>
                    <label class="text-xs font-semibold text-gray-700 block mb-1">Tipo</label>
                    <x-tabla.select name="tipo" :options="$tiposAlerta" :selected="request('tipo')" empty="-- Todos --" />
                </div>
                <div>
                    <label class="text-xs font-semibold text-gray-700 block mb-1">Fecha</label>
                    <x-tabla.input type="date" name="fecha_creada" value="{{ request('fecha_creada') }}" />
                </div>
                <div class="flex gap-2">
                    <button type="submit"
                        class="flex-1 bg-blue-600 hover:bg-blue-700 text-white py-2 px-4 rounded-lg font-medium text-sm">
                        Filtrar
                    </button>
                    <a href="{{ route('alertas.index') }}" wire:navigate
                        class="flex-1 bg-gray-500 hover:bg-gray-600 text-white py-2 px-4 rounded-lg font-medium text-sm text-center">
                        Limpiar
                    </a>
                </div>
            </form>
        </div>

        <!-- Vista Desktop (Tabla) -->
        <div class="hidden md:block w-full overflow-x-auto bg-white shadow-xl rounded-lg border border-gray-200">
            <table class="w-full min-w-[1000px]">
                <thead class="bg-gradient-to-r from-blue-600 to-blue-500 text-white">
                    <tr class="text-center text-xs font-semibold">
                        <th class="p-3 border-r border-blue-400">{!! $ordenablesAlertas['user_id_1'] !!}</th>
                        <th class="p-3 border-r border-blue-400">{!! $ordenablesAlertas['user_id_2'] !!}</th>
                        <th class="p-3 border-r border-blue-400">{!! $ordenablesAlertas['destino'] !!}</th>
                        <th class="p-3 border-r border-blue-400">{!! $ordenablesAlertas['destinatario'] !!}</th>
                        <th class="p-3 border-r border-blue-400">Mensaje</th>
                        <th class="p-3 border-r border-blue-400">{!! $ordenablesAlertas['tipo'] !!}</th>
                        <th class="p-3 border-r border-blue-400">{!! $ordenablesAlertas['created_at'] !!}</th>
                        <th class="p-3">Acciones</th>
                    </tr>
                    <tr class="text-center text-xs bg-blue-400">
                        <form method="GET" action="{{ route('alertas.index') }}">
                            <th class="p-2 border-r border-blue-300">
                                <x-tabla.input name="emisor" value="{{ request('emisor') }}" />
                            </th>
                            <th class="p-2 border-r border-blue-300">
                                <x-tabla.input name="receptor" value="{{ request('receptor') }}" />
                            </th>
                            <th class="p-2 border-r border-blue-300">
                                <x-tabla.input name="destino" value="{{ request('destino') }}" />
                            </th>
                            <th class="p-2 border-r border-blue-300">
                                <x-tabla.input name="destinatario" value="{{ request('destinatario') }}" />
                            </th>
                            <th class="p-2 border-r border-blue-300">
                                <x-tabla.input name="mensaje" value="{{ request('mensaje') }}" />
                            </th>
                            <th class="p-2 border-r border-blue-300">
                                <x-tabla.select name="tipo" :options="$tiposAlerta" :selected="request('tipo')" empty="-- Todos --"
                                    class="text-xs" />
                            </th>
                            <th class="p-2 border-r border-blue-300">
                                <x-tabla.input type="date" name="fecha_creada"
                                    value="{{ request('fecha_creada') }}" />
                            </th>
                            <x-tabla.botones-filtro ruta="alertas.index" />
                        </form>
                    </tr>
                </thead>

                <tbody class="text-gray-700 text-sm">
                    @forelse ($todasLasAlertas as $alerta)
                        <tr
                            class="border-b border-gray-200 odd:bg-gray-50 even:bg-white hover:bg-blue-50 transition-colors duration-150">
                            <td class="p-3 text-center border-r border-gray-200">
                                <div class="flex flex-col items-center">
                                    <span class="font-medium text-gray-900">
                                        {{ $alerta->usuario1?->nombre_completo ?? '—' }}
                                    </span>
                                    @if ($alerta->usuario1?->rol)
                                        <span
                                            class="text-xs text-gray-500 mt-0.5">{{ ucfirst($alerta->usuario1->rol) }}</span>
                                    @endif
                                </div>
                            </td>
                            <td class="p-3 text-center border-r border-gray-200">
                                <span class="text-gray-700">
                                    {{ $alerta->usuario2?->nombre_completo ?? '—' }}
                                </span>
                            </td>
                            <td class="p-3 text-center border-r border-gray-200">
                                @if ($alerta->destino)
                                    <span
                                        class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-800 border border-purple-200">
                                        {{ ucfirst($alerta->destino) }}
                                    </span>
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="p-3 text-center border-r border-gray-200">
                                <span class="text-gray-700">
                                    {{ $alerta->destinatarioUser?->nombre_completo ?? '—' }}
                                </span>
                            </td>
                            <td class="p-3 border-r border-gray-200">
                                <div class="max-w-md">
                                    <p class="text-gray-800 line-clamp-2 leading-relaxed"
                                        title="{{ $alerta->mensaje }}">
                                        {{ $alerta->mensaje }}
                                    </p>
                                    @if (strlen($alerta->mensaje) > 100)
                                        <button onclick="verMensajeCompleto(@js($alerta->mensaje))"
                                            class="text-xs text-blue-600 hover:text-blue-800 font-medium mt-1">
                                            Ver completo →
                                        </button>
                                    @endif
                                </div>
                            </td>
                            <td class="p-3 text-center border-r border-gray-200">
                                @if ($alerta->tipo === 'entrante')
                                    <span
                                        class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 border border-green-200">
                                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path
                                                d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z" />
                                            <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z" />
                                        </svg>
                                        Recibido
                                    </span>
                                @else
                                    <span
                                        class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800 border border-blue-200">
                                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path
                                                d="M10.894 2.553a1 1 0 00-1.788 0l-7 14a1 1 0 001.169 1.409l5-1.429A1 1 0 009 15.571V11a1 1 0 112 0v4.571a1 1 0 00.725.962l5 1.428a1 1 0 001.17-1.408l-7-14z" />
                                        </svg>
                                        Enviado
                                    </span>
                                @endif
                            </td>
                            <td class="p-3 text-center border-r border-gray-200">
                                <div class="flex flex-col items-center">
                                    <span class="text-gray-900 font-medium text-xs">
                                        {{ $alerta->created_at?->format('d/m/Y') }}
                                    </span>
                                    <span class="text-gray-500 text-xs">
                                        {{ $alerta->created_at?->format('H:i') }}
                                    </span>
                                    <span class="text-gray-400 text-xs mt-0.5">
                                        {{ $alerta->created_at?->diffForHumans() }}
                                    </span>
                                </div>
                            </td>
                            <td class="p-3 text-center">
                                <div class="flex justify-center gap-2">
                                    <button onclick="verMensajeCompleto(@js($alerta->mensaje))"
                                        class="inline-flex items-center px-2.5 py-1.5 bg-blue-500 hover:bg-blue-600 text-white text-xs font-medium rounded-md transition-colors duration-150 shadow-sm hover:shadow-md"
                                        title="Ver mensaje completo">
                                        <svg class="w-3.5 h-3.5 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M10 12a2 2 0 100-4 2 2 0 000 4z" />
                                            <path fill-rule="evenodd"
                                                d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z"
                                                clip-rule="evenodd" />
                                        </svg>
                                        Ver
                                    </button>
                                    <button onclick="eliminarAlerta({{ $alerta->id }})"
                                        class="inline-flex items-center px-2.5 py-1.5 bg-red-500 hover:bg-red-600 text-white text-xs font-medium rounded-md transition-colors duration-150 shadow-sm hover:shadow-md"
                                        title="Eliminar alerta">
                                        <svg class="w-3.5 h-3.5 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd"
                                                d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z"
                                                clip-rule="evenodd" />
                                        </svg>
                                        Eliminar
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-8 text-gray-500">
                                <svg class="w-12 h-12 mx-auto mb-2 text-gray-300" fill="none"
                                    stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                                </svg>
                                <p class="font-medium">No hay alertas disponibles</p>
                                <p class="text-sm mt-1">Ajusta los filtros para ver más resultados</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Vista Mobile (Cards) -->
        <div class="md:hidden space-y-4">
            @forelse ($todasLasAlertas as $alerta)
                <div
                    class="bg-white rounded-xl shadow-lg overflow-hidden border border-gray-200 transform transition-all duration-200 hover:shadow-xl">

                    <!-- Header de la card con degradado -->
                    <div
                        class="bg-gradient-to-r {{ $alerta->tipo === 'entrante' ? 'from-green-500 to-emerald-600' : 'from-blue-500 to-indigo-600' }} px-4 py-3">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-2">
                                <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                    @if ($alerta->tipo === 'entrante')
                                        <path
                                            d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z" />
                                        <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z" />
                                    @else
                                        <path
                                            d="M10.894 2.553a1 1 0 00-1.788 0l-7 14a1 1 0 001.169 1.409l5-1.429A1 1 0 009 15.571V11a1 1 0 112 0v4.571a1 1 0 00.725.962l5 1.428a1 1 0 001.17-1.408l-7-14z" />
                                    @endif
                                </svg>
                                <span class="text-white font-bold text-sm">
                                    {{ $alerta->tipo === 'entrante' ? 'RECIBIDO' : 'ENVIADO' }}
                                </span>
                            </div>
                            <span class="text-white text-xs font-medium">
                                {{ $alerta->created_at?->format('d/m/Y') }}
                            </span>
                        </div>
                    </div>

                    <!-- Contenido de la card -->
                    <div class="p-4 space-y-3">

                        <!-- Información del emisor -->
                        <div class="flex items-start space-x-3 pb-3 border-b border-gray-100">
                            <div class="flex-shrink-0">
                                <div
                                    class="w-12 h-12 rounded-full bg-gradient-to-br from-blue-400 to-blue-600 flex items-center justify-center shadow-md">
                                    <svg class="w-7 h-7 text-white" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd"
                                            d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z"
                                            clip-rule="evenodd" />
                                    </svg>
                                </div>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-bold text-gray-900">
                                    {{ $alerta->nombre_emisor }}
                                </p>
                                @if ($alerta->nombre_emisor !== 'Sistema' && $alerta->usuario1?->rol)
                                    <p class="text-xs text-gray-500 mt-0.5">
                                        <span class="inline-block w-2 h-2 rounded-full bg-blue-400 mr-1"></span>
                                        {{ ucfirst($alerta->usuario1->rol) }}
                                    </p>
                                @endif
                                <p class="text-xs text-gray-400 mt-1">
                                    {{ $alerta->created_at?->format('H:i') }} ·
                                    {{ $alerta->created_at?->diffForHumans() }}
                                </p>
                            </div>
                        </div>

                        <!-- Información adicional en chips -->
                        <div class="flex flex-wrap gap-2">
                            @if ($alerta->destino)
                                <span
                                    class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-800 border border-purple-200">
                                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd"
                                            d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z"
                                            clip-rule="evenodd" />
                                    </svg>
                                    Rol: {{ ucfirst($alerta->destino) }}
                                </span>
                            @endif
                            @if ($alerta->destinatarioUser)
                                <span
                                    class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800 border border-indigo-200">
                                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path
                                            d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3zM6 8a2 2 0 11-4 0 2 2 0 014 0zM16 18v-3a5.972 5.972 0 00-.75-2.906A3.005 3.005 0 0119 15v3h-3zM4.75 12.094A5.973 5.973 0 004 15v3H1v-3a3 3 0 013.75-2.906z" />
                                    </svg>
                                    Para: {{ $alerta->destinatarioUser->nombre_completo }}
                                </span>
                            @endif
                        </div>

                        <!-- Mensaje -->
                        <div
                            class="bg-gradient-to-br from-gray-50 to-gray-100 rounded-lg p-4 border border-gray-200 shadow-inner">
                            <p class="text-sm text-gray-800 leading-relaxed">
                                {{ $alerta->mensaje }}
                            </p>
                        </div>

                        <!-- Acciones -->
                        <div class="flex gap-2 pt-2">
                            <button onclick="verMensajeCompleto(@js($alerta->mensaje))"
                                class="flex-1 inline-flex items-center justify-center px-4 py-3 bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white text-sm font-semibold rounded-lg transition-all duration-150 shadow-md hover:shadow-lg active:scale-95">
                                <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M10 12a2 2 0 100-4 2 2 0 000 4z" />
                                    <path fill-rule="evenodd"
                                        d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z"
                                        clip-rule="evenodd" />
                                </svg>
                                Ver Completo
                            </button>
                            <button onclick="eliminarAlerta({{ $alerta->id }})"
                                class="px-4 py-3 bg-gradient-to-r from-red-500 to-red-600 hover:from-red-600 hover:to-red-700 text-white rounded-lg transition-all duration-150 shadow-md hover:shadow-lg active:scale-95"
                                title="Eliminar">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z"
                                        clip-rule="evenodd" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            @empty
                <div class="text-center py-16 bg-white rounded-xl shadow-md">
                    <div class="w-20 h-20 mx-auto mb-4 bg-gray-100 rounded-full flex items-center justify-center">
                        <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                        </svg>
                    </div>
                    <p class="font-bold text-gray-900 text-lg">No hay alertas</p>
                    <p class="text-sm text-gray-500 mt-2">Ajusta los filtros para ver más resultados</p>
                </div>
            @endforelse
        </div>

        <x-tabla.paginacion :paginador="$todasLasAlertas" perPageName="per_page_todas" />
    @endif


    <!-- Modal Chat Moderno -->
    <div id="modalVerMensaje" class="fixed inset-0 z-50 hidden" onclick="cerrarModalMensaje()">
        <!-- Overlay con blur -->
        <div class="modal-overlay absolute inset-0 bg-black/60 backdrop-blur-[2px]"></div>

        <!-- Contenedor del modal -->
        <div class="absolute inset-0 flex items-end md:items-center justify-center p-0 md:p-6">
            <div class="modal-content bg-white w-full md:max-w-[440px] md:rounded-2xl shadow-2xl flex flex-col max-h-[100dvh] md:max-h-[80vh] overflow-hidden"
                onclick="event.stopPropagation()">

                <!-- Header -->
                <div class="bg-emerald-600 px-4 py-3 flex items-center gap-3 shrink-0">
                    <button onclick="cerrarModalMensaje()" class="text-white p-1.5 hover:bg-emerald-700 rounded-lg transition-all -ml-1">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                        </svg>
                    </button>
                    <svg class="w-6 h-6 shrink-0" fill="white" viewBox="0 0 24 24">
                        <path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm0 14H6l-2 2V4h16v12z"/>
                    </svg>
                    <div class="flex-1 min-w-0">
                        <h2 id="headerNombreContacto" class="text-white text-lg font-semibold truncate">Conversacion</h2>
                        <p id="contadorRespuestas" class="text-emerald-200 text-sm"></p>
                    </div>
                </div>

                <!-- Area de chat -->
                <div id="hiloContenido" class="flex-1 overflow-y-auto p-4 space-y-3 bg-gradient-to-b from-slate-50 to-slate-100 min-h-[280px]">
                    <!-- Mensajes -->
                </div>

                <!-- Input -->
                <div class="bg-white border-t border-slate-200 px-3 py-2 flex items-center gap-3 shrink-0">
                    <div class="flex-1 bg-slate-100 rounded-2xl overflow-hidden transition-all duration-200 focus-within:bg-slate-50 focus-within:ring-2 focus-within:ring-emerald-500/30">
                        <textarea id="textoRespuesta"
                            class="w-full resize-none border-0 bg-transparent focus:ring-0 focus:outline-none text-[15px] text-slate-800 leading-6 placeholder-slate-400 py-2.5 px-4 block"
                            rows="1" placeholder="Escribe un mensaje..."
                            oninput="ajustarAlturaTextarea(this)"
                            onkeydown="if(event.key === 'Enter' && !event.shiftKey) { event.preventDefault(); enviarRespuesta(); }"
                            style="height: 44px; max-height: 120px; overflow-y: auto;"></textarea>
                    </div>
                    <button onclick="enviarRespuesta()" id="btnEnviarMensaje"
                        class="w-11 h-11 bg-emerald-600 hover:bg-emerald-700 active:scale-95 rounded-full flex items-center justify-center text-white shrink-0 transition-all duration-150">
                        <svg class="w-5 h-5 ml-0.5" fill="white" viewBox="0 0 24 24">
                            <path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/>
                        </svg>
                    </button>
                </div>

                <!-- Elementos ocultos -->
                <div id="hiloConversacion" class="hidden"></div>
                <textarea id="textareaMensaje" class="hidden"></textarea>
                <div id="botonesEdicion" class="hidden"></div>
                <div id="botonesRespuesta" class="hidden"></div>
                <div id="botonesGuardarCancelar" class="hidden"></div>
                <div id="botonesEnviarCancelarRespuesta" class="hidden"></div>
                <button id="botonEditar" class="hidden"></button>
                <button id="botonContestar" class="hidden"></button>
                <div id="seccionRespuesta" class="hidden"></div>
            </div>
        </div>
    </div>

    <!-- Modal Confirmación Cambio de Máquina -->
    <div id="modalConfirmacionCambio"
        class="fixed inset-0 bg-black bg-opacity-60 hidden items-end md:items-center justify-center z-50 transition-opacity duration-300 p-0 md:p-4"
        onclick="cerrarModalConfirmacion()">
        <div class="bg-white rounded-t-2xl md:rounded-xl shadow-2xl w-full md:max-w-md transform transition-all duration-300"
            onclick="event.stopPropagation()">

            <!-- Indicador visual de modal móvil -->
            <div class="md:hidden w-12 h-1 bg-gray-300 rounded-full mx-auto mt-3 mb-2"></div>

            <!-- Header del modal -->
            <div
                class="bg-gradient-to-r from-orange-500 to-orange-400 px-4 md:px-6 py-4 md:rounded-t-xl sticky top-0 z-10">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-2 md:space-x-3">
                        <svg class="w-6 h-6 md:w-7 md:h-7 text-white" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                                clip-rule="evenodd" />
                        </svg>
                        <h2 class="text-lg md:text-xl font-bold text-white">Confirmación</h2>
                    </div>
                    <button onclick="cerrarModalConfirmacion()"
                        class="md:hidden text-white hover:text-gray-200 p-2 rounded-lg active:scale-95">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Contenido dinámico -->
            <div class="p-4 md:p-6">
                <div class="bg-blue-50 border-l-4 border-blue-500 p-3 md:p-4 rounded-r-lg mb-4">
                    <p class="text-sm md:text-base text-gray-800 mb-3">
                        Solicitud para elemento: <strong class="text-blue-700 text-base md:text-lg"><span
                                id="elementoModal"></span></strong>
                    </p>
                    <div
                        class="flex flex-col sm:flex-row items-center justify-center space-y-2 sm:space-y-0 sm:space-x-4 text-sm">
                        <div class="flex items-center bg-red-100 px-3 py-2 rounded-lg w-full sm:w-auto justify-center">
                            <span class="text-red-700 font-semibold">De: <span id="origenModal"></span></span>
                        </div>
                        <svg class="w-5 h-5 sm:w-6 sm:h-6 text-gray-400 rotate-90 sm:rotate-0" fill="none"
                            stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13 7l5 5m0 0l-5 5m5-5H6" />
                        </svg>
                        <div
                            class="flex items-center bg-green-100 px-3 py-2 rounded-lg w-full sm:w-auto justify-center">
                            <span class="text-green-700 font-semibold">A: <span id="destinoModal"></span></span>
                        </div>
                    </div>
                </div>

                <!-- Formulario de acción -->
                <form id="formAceptarCambio" method="POST">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="maquina_id" id="nueva_maquina_id">

                    <div class="flex flex-col sm:flex-row justify-end gap-2 md:gap-3 mt-4 md:mt-6">
                        <button type="button" onclick="cerrarModalConfirmacion()"
                            class="inline-flex items-center justify-center px-4 md:px-5 py-2.5 bg-gray-500 hover:bg-gray-600 active:bg-gray-700 text-white font-medium rounded-lg transition-colors duration-150 shadow-md active:scale-95">
                            <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                                    clip-rule="evenodd" />
                            </svg>
                            Cancelar
                        </button>

                        <button type="submit"
                            class="inline-flex items-center justify-center px-4 md:px-5 py-2.5 bg-green-600 hover:bg-green-700 active:bg-green-800 text-white font-medium rounded-lg transition-colors duration-150 shadow-md active:scale-95">
                            <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                    clip-rule="evenodd" />
                            </svg>
                            Aceptar Cambio
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script>
        // Definir la función de inicialización globalmente
        window.initAlertasIndexPage = function() {
            // Protección contra doble inicialización
            if (document.body.dataset.alertasIndexPageInit === 'true') return;

            // Variables globales de estado (reiniciarlas)
            window.nuevasAlertas = [];
            window.alertaActualId = null;
            window.alertaActualEsSaliente = false;
            window.esMensajeEntrante = false;

            // --- Lógica de inicialización (antes DOMContentLoaded) ---
            // Detectar nuevas alertas tanto en tabla (tr) como en cards (div)
            document.querySelectorAll("tr.bg-yellow-50, div.ring-2.ring-yellow-400").forEach(elemento => {
                const id = elemento.dataset.alertaId;
                if (id) window.nuevasAlertas.push(id);
            });
            console.log("Nuevas alertas detectadas (SPA):", window.nuevasAlertas);

            // --- Definición de funciones globales para onclick ---

            window.ajustarAlturaTextarea = function(el) {
                el.style.height = '44px';
                el.style.height = Math.min(el.scrollHeight, 120) + 'px';
            }

            window.abrirModalMensaje = function() {
                const modal = document.getElementById('modalVerMensaje');
                if (modal) {
                    modal.classList.remove('hidden');
                    // Trigger reflow
                    modal.offsetHeight;
                    modal.classList.add('show');
                    modal.classList.remove('closing');
                }
            }

            window.cerrarModalMensaje = function() {
                const modal = document.getElementById('modalVerMensaje');
                if (modal) {
                    modal.classList.add('closing');
                    modal.classList.remove('show');
                    setTimeout(() => {
                        modal.classList.add('hidden');
                        modal.classList.remove('closing');

                        // Limpiar contenido
                        const texto = document.getElementById('textoRespuesta');
                        if (texto) {
                            texto.value = '';
                            texto.style.height = '44px';
                        }
                        const hiloContenido = document.getElementById('hiloContenido');
                        if (hiloContenido) hiloContenido.innerHTML = '';

                        // Reset header
                        const headerNombre = document.getElementById('headerNombreContacto');
                        if (headerNombre) headerNombre.textContent = 'Conversacion';
                        const contador = document.getElementById('contadorRespuestas');
                        if (contador) contador.textContent = '';
                    }, 350);
                }
            }

            window.eliminarAlerta = function(alertaId = null) {
                const idAEliminar = alertaId || window.alertaActualId;
                if (!idAEliminar) {
                    mostrarError('No se pudo identificar la alerta a eliminar.');
                    return;
                }

                Swal.fire({
                    title: '¿Eliminar mensaje?',
                    text: "Esta acción no se puede deshacer.",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#EF4444',
                    cancelButtonColor: '#6B7280',
                    confirmButtonText: 'Sí, eliminar',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        fetch(`{{ url('alertas') }}/${idAEliminar}`, {
                            method: 'DELETE',
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            }
                        }).then(response => {
                            if (response.ok) {
                                Swal.fire({
                                    title: 'Eliminado',
                                    text: 'El mensaje fue eliminado correctamente.',
                                    icon: 'success',
                                    timer: 1500,
                                    showConfirmButton: false
                                }).then(() => {
                                    // Recargar componente o página
                                    // window.location.reload(); preferiblemente usar livewire refresh si posible, 
                                    // pero aquí es DELETE directo.
                                    location.reload();
                                });
                            } else {
                                mostrarError('No se pudo eliminar el mensaje.');
                            }
                        }).catch(error => {
                            mostrarError('Error de conexión al eliminar el mensaje.');
                        });
                    }
                });
            }

            window.iniciarEdicionAlerta = function() {
                document.getElementById('hiloConversacion').classList.add('hidden');
                document.getElementById('textareaMensaje').classList.remove('hidden');
                document.getElementById('botonEditar').classList.add('hidden');
                const btnGuardar = document.getElementById('botonesGuardarCancelar');
                btnGuardar.classList.remove('hidden');
                btnGuardar.classList.add('flex');
            }

            window.cancelarEdicionAlerta = function() {
                document.getElementById('textareaMensaje').classList.add('hidden');
                document.getElementById('hiloConversacion').classList.remove('hidden');
                document.getElementById('botonEditar').classList.remove('hidden');
                const btnGuardar = document.getElementById('botonesGuardarCancelar');
                btnGuardar.classList.add('hidden');
                btnGuardar.classList.remove('flex');
            }

            window.guardarEdicionAlerta = function() {
                const nuevoMensaje = document.getElementById('textareaMensaje').value.trim();
                if (!nuevoMensaje) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Mensaje vacío',
                        text: 'Debes escribir un mensaje antes de guardar.'
                    });
                    return;
                }
                window.guardarAlerta({
                    id: window.alertaActualId,
                    mensaje: nuevoMensaje
                });
            }

            window.guardarAlerta = function(alerta) {
                fetch(`{{ url('alertas') }}/${alerta.id}`, {
                        method: 'PUT',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            mensaje: alerta.mensaje
                        })
                    })
                    .then(async response => {
                        const data = await response.json().catch(() => ({}));
                        if (response.ok && data.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Mensaje actualizado',
                                timer: 1500,
                                showConfirmButton: false
                            }).then(() => location.reload());
                        } else {
                            let mensaje = data.error || 'Error inesperado';
                            if (typeof mensaje === 'object') {
                                mensaje = Object.values(mensaje).flat().join('\n');
                            }
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: mensaje
                            });
                        }
                    })
                    .catch(error => {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error de conexión',
                            text: error.message || 'No se pudo actualizar la alerta.'
                        });
                    });
            }

            window.abrirModalAceptarCambio = function(elementoId, origen, destino, maquinaDestinoId = null, alertaId =
                null) {
                document.getElementById('elementoModal').textContent = elementoId;
                document.getElementById('origenModal').textContent = origen;
                document.getElementById('destinoModal').textContent = destino;
                document.getElementById('nueva_maquina_id').value = maquinaDestinoId;

                if (!maquinaDestinoId) {
                    alert("ID de máquina destino no válido");
                    return;
                }

                const form = document.getElementById('formAceptarCambio');
                form.action = `/elementos/${elementoId}/cambio-maquina?alerta_id=${alertaId}`;

                const modal = document.getElementById('modalConfirmacionCambio');
                modal.classList.remove('hidden');
                modal.classList.add('flex');
            }

            window.verMensajeCompleto = function(mensaje, tipo = null) {
                // Detectar si es una solicitud de revision de fichajes
                const revisionMatch = mensaje.match(/\[REVISION_ID:(\d+)\]\[USER_ID:(\d+)\]/);

                if (revisionMatch) {
                    const solicitudId = revisionMatch[1];
                    const userId = revisionMatch[2];
                    // Limpiar los marcadores del mensaje visible
                    const mensajeLimpio = mensaje.replace(/\[REVISION_ID:\d+\]\[USER_ID:\d+\]\n?/, '');

                    Swal.fire({
                        title: 'Solicitud de Revision de Fichajes',
                        html: `
                            <div class="text-left whitespace-pre-wrap text-gray-700 p-4 mb-4 bg-gray-50 rounded-lg max-h-64 overflow-y-auto">${mensajeLimpio}</div>
                            <div class="flex gap-2 justify-center">
                                <button onclick="corregirFichajes(${solicitudId})" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg transition-colors">
                                    Corregir Fichajes
                                </button>
                                <a href="/mi-perfil/${userId}" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition-colors inline-block">
                                    Ver Perfil
                                </a>
                            </div>
                        `,
                        showConfirmButton: false,
                        showCloseButton: true,
                        width: '600px'
                    });
                } else {
                    Swal.fire({
                        title: 'Mensaje Completo',
                        html: `<div class="text-left whitespace-pre-wrap text-gray-700 p-4">${mensaje}</div>`,
                        icon: 'info',
                        confirmButtonColor: '#3B82F6',
                        confirmButtonText: 'Cerrar',
                        width: '600px'
                    });
                }
            }

            window.corregirFichajes = function(solicitudId) {
                Swal.fire({
                    title: 'Corregir Fichajes',
                    text: 'Esto rellenara automaticamente los fichajes faltantes segun el turno asignado. Continuar?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#10B981',
                    cancelButtonColor: '#6B7280',
                    confirmButtonText: 'Si, corregir',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        fetch(`/revision-fichaje/${solicitudId}/auto-rellenar`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            }
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Fichajes Corregidos',
                                    text: data.success,
                                    confirmButtonColor: '#10B981'
                                }).then(() => {
                                    location.reload();
                                });
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: data.error || 'No se pudieron corregir los fichajes'
                                });
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: 'Error de conexion. Intenta de nuevo.'
                            });
                        });
                    }
                });
            }

            window.cerrarModalConfirmacion = function() {
                const modal = document.getElementById('modalConfirmacionCambio');
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            }

            window.marcarAlertaLeida = function(alertaId, elemento, mensaje, esSaliente) {
                window.esMensajeEntrante = !esSaliente;
                window.alertaActualId = alertaId;

                if (!esSaliente) {
                    const data = new FormData();
                    data.append('_token', '{{ csrf_token() }}');
                    data.append('alerta_ids[]', alertaId);

                    fetch("{{ route('alertas.marcarLeidas') }}", {
                            method: 'POST',
                            body: data
                        })
                        .then(res => {
                            if (!res.ok) console.error('Error marcando alerta como leída');
                            return res.json();
                        })
                        .then(data => {
                            console.log('Respuesta del servidor:', data);
                            if (elemento) {
                                elemento.classList.remove('bg-yellow-50', 'border-l-4', 'border-yellow-400');
                                elemento.classList.add('bg-white');
                                const badgeNuevo = elemento.querySelector('.animate-pulse');
                                if (badgeNuevo) badgeNuevo.remove();
                            }
                            actualizarContadorAlertas();
                        })
                        .catch(err => console.error('Error en fetch marcarLeida:', err));
                }

                document.getElementById('textareaMensaje').value = mensaje;
                cargarHiloConversacion(alertaId);

                const btnEdicion = document.getElementById('botonesEdicion');
                if (esSaliente) {
                    btnEdicion.classList.remove('hidden');
                    btnEdicion.classList.add('flex');
                } else {
                    btnEdicion.classList.add('hidden');
                    btnEdicion.classList.remove('flex');
                }

                const btnRespuesta = document.getElementById('botonesRespuesta');
                if (!esSaliente) {
                    btnRespuesta.classList.remove('hidden');
                    btnRespuesta.classList.add('flex');
                } else {
                    btnRespuesta.classList.add('hidden');
                    btnRespuesta.classList.remove('flex');
                }

                document.getElementById('textareaMensaje').classList.add('hidden');
                document.getElementById('botonEditar').classList.remove('hidden');

                const btnGuardar = document.getElementById('botonesGuardarCancelar');
                btnGuardar.classList.add('hidden');
                btnGuardar.classList.remove('flex');

                document.getElementById('seccionRespuesta').classList.add('hidden');
                document.getElementById('botonContestar').classList.remove('hidden');

                const btnEnvResp = document.getElementById('botonesEnviarCancelarRespuesta');
                btnEnvResp.classList.add('hidden');
                btnEnvResp.classList.remove('flex');

                abrirModalMensaje();
            }

            window.actualizarContadorAlertas = function() {
                fetch("{{ route('alertas.verSinLeer') }}")
                    .then(res => res.json())
                    .then(data => {
                        const badge = document.getElementById('alerta-count');
                        if (badge) {
                            if (data.cantidad > 0) {
                                badge.textContent = data.cantidad;
                                badge.classList.remove('hidden');
                            } else {
                                badge.classList.add('hidden');
                            }
                        }
                    })
                    .catch(err => console.error('Error actualizando contador:', err));
            }

            window.cargarHiloConversacion = function(alertaId) {
                fetch(`{{ url('alertas') }}/${alertaId}/hilo`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success && data.hilo) {
                            const hiloContenido = document.getElementById('hiloContenido');
                            hiloContenido.innerHTML = '';

                            // Actualizar header con nombre del contacto
                            const headerNombre = document.getElementById('headerNombreContacto');
                            if (headerNombre && data.contacto) {
                                headerNombre.textContent = data.contacto;
                            }

                            mostrarMensajePadre(data.hilo);
                            const totalRespuestas = data.hilo.respuestas ? contarRespuestas(data.hilo.respuestas) : 0;
                            const totalMensajes = totalRespuestas + 1;
                            document.getElementById('contadorRespuestas').textContent =
                                `${totalMensajes} ${totalMensajes === 1 ? 'mensaje' : 'mensajes'}`;

                            if (data.hilo.respuestas && data.hilo.respuestas.length > 0) {
                                mostrarHilo(data.hilo.respuestas, 0);
                            }
                            setTimeout(() => {
                                hiloContenido.scrollTop = hiloContenido.scrollHeight;
                            }, 100);
                        }
                    })
                    .catch(error => console.error('Error al cargar hilo:', error));
            }

            window.mostrarMensajePadre = function(mensaje) {
                const hiloContenido = document.getElementById('hiloContenido');
                const esPropio = mensaje.es_propio;
                const bubbleClass = esPropio ? 'chat-bubble-out' : 'chat-bubble-in';

                // Detectar si es una solicitud de revision de fichajes
                const revisionMatch = mensaje.mensaje.match(/\[REVISION_ID:(\d+)\]\[USER_ID:(\d+)\]/);
                let mensajeTexto = mensaje.mensaje;
                let botonesRevision = '';

                // Mostrar botones de revision siempre que se detecte el patron
                if (revisionMatch) {
                    const solicitudId = revisionMatch[1];
                    const userId = revisionMatch[2];
                    // Limpiar los marcadores del mensaje visible
                    mensajeTexto = mensaje.mensaje.replace(/\[REVISION_ID:\d+\]\[USER_ID:\d+\]\n?/, '');

                    botonesRevision = `
                        <div class="flex gap-2 mt-3 pt-3 border-t border-gray-200">
                            <button onclick="corregirFichajes(${solicitudId})" class="flex-1 px-3 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-semibold rounded-lg transition-colors">
                                Corregir Fichajes
                            </button>
                            <a href="/mi-perfil/${userId}" class="flex-1 px-3 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-lg transition-colors text-center">
                                Ver Perfil
                            </a>
                        </div>
                    `;
                }

                const mensajeDiv = document.createElement('div');
                mensajeDiv.className = `flex ${esPropio ? 'justify-end' : 'justify-start'}`;
                mensajeDiv.innerHTML = `
                <div class="flex flex-col max-w-[85%]">
                    <div class="chat-bubble ${bubbleClass} px-4 py-3 shadow-md">
                        ${!esPropio ? `
                            <div class="flex items-center gap-2 mb-1">
                                <span class="font-bold text-sm">${mensaje.emisor}</span>
                                <span class="px-2 py-0.5 bg-white bg-opacity-20 text-xs rounded-full font-medium">📌</span>
                            </div>
                        ` : ''}

                        <p class="mensaje-mensaje text-[15px] leading-relaxed whitespace-pre-wrap">${mensajeTexto}</p>

                        ${botonesRevision}

                        <div class="flex items-center justify-end gap-1.5 mt-1.5 -mb-0.5">
                            <span class="text-[11px] text-slate-500">${mensaje.created_at}</span>
                            ${esPropio ? `
                                <svg class="w-4 h-4 text-emerald-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                </svg>
                            ` : ''}
                        </div>
                    </div>
                </div>
            </div>`;
                hiloContenido.appendChild(mensajeDiv);
            }

            window.contarRespuestas = function(respuestas) {
                let total = respuestas.length;
                respuestas.forEach(respuesta => {
                    if (respuesta.respuestas && respuesta.respuestas.length > 0) {
                        total += contarRespuestas(respuesta.respuestas);
                    }
                });
                return total;
            }

            window.mostrarHilo = function(respuestas, nivel = 0) {
                const hiloContenido = document.getElementById('hiloContenido');
                respuestas.forEach((respuesta, index) => {
                    const esPropio = respuesta.es_propio;

                    const respuestaDiv = document.createElement('div');
                    respuestaDiv.className = `flex ${esPropio ? 'justify-end' : 'justify-start'}`;
                    respuestaDiv.style.animationDelay = `${index * 50}ms`;

                    const bubbleClass = esPropio ? 'chat-bubble-out' : 'chat-bubble-in';

                    respuestaDiv.innerHTML = `
                    <div class="chat-bubble ${bubbleClass} max-w-[85%] px-4 py-2.5">
                        ${!esPropio ? `<p class="text-xs font-semibold text-emerald-600 mb-1">${respuesta.emisor}</p>` : ''}
                        <p class="text-[15px] text-slate-800 leading-relaxed whitespace-pre-wrap">${respuesta.mensaje}</p>
                        <div class="flex items-center justify-end gap-1.5 mt-1.5 -mb-0.5">
                            <span class="text-[11px] text-slate-500">${respuesta.created_at}</span>
                            ${esPropio ? '<svg class="w-4 h-4 text-emerald-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>' : ''}
                        </div>
                    </div>`;
                    hiloContenido.appendChild(respuestaDiv);

                    if (respuesta.respuestas && respuesta.respuestas.length > 0) {
                        mostrarHilo(respuesta.respuestas, nivel + 1);
                    }
                });
            }

            window.activarRespuesta = function() {
                document.getElementById('textoRespuesta').focus();
            }

            window.cancelarRespuesta = function() {
                document.getElementById('textoRespuesta').value = '';
            }

            window.enviarRespuesta = function() {
                const mensajeRespuesta = document.getElementById('textoRespuesta').value.trim();
                if (!mensajeRespuesta) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Mensaje vacío',
                        text: 'Debes escribir una respuesta antes de enviar.'
                    });
                    return;
                }

                const datos = {
                    mensaje: mensajeRespuesta,
                    parent_id: window.alertaActualId
                };

                fetch("{{ route('alertas.store') }}", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify(datos)
                    })
                    .then(async response => {
                        const contentType = response.headers.get('content-type');
                        if (!contentType || !contentType.includes('application/json')) {
                            const text = await response.text();
                            console.error('Respuesta no es JSON:', text.substring(0, 500));
                            throw new Error('La respuesta del servidor no es JSON válido');
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Respuesta enviada',
                                text: data.message || 'Tu respuesta ha sido enviada correctamente',
                                timer: 1500,
                                showConfirmButton: false
                            }).then(() => {
                                cerrarModalMensaje();
                                location.reload();
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: data.message || 'No se pudo enviar la respuesta'
                            });
                        }
                    })
                    .catch(error => {
                        console.error('Error completo:', error);
                        Swal.fire({
                            icon: 'error',
                            title: 'Error de conexión',
                            text: error.message || 'No se pudo enviar la respuesta. Intenta de nuevo.'
                        });
                    });
            }

            // Manejador de evento ESC (local)
            const handleEscKey = function(e) {
                if (e.key === 'Escape') {
                    const modalMensaje = document.getElementById('modalVerMensaje');
                    const modalConfirmacion = document.getElementById('modalConfirmacionCambio');

                    if (modalMensaje && !modalMensaje.classList.contains('hidden')) {
                        cerrarModalMensaje();
                    }
                    if (modalConfirmacion && !modalConfirmacion.classList.contains('hidden')) {
                        cerrarModalConfirmacion();
                    }
                }
            };

            document.addEventListener('keydown', handleEscKey);

            // Cleanup
            window.pageInitializers = window.pageInitializers || [];
            window.pageInitializers.push(() => {
                document.body.dataset.alertasIndexPageInit = 'false';
                document.removeEventListener('keydown', handleEscKey);
            });

            // Marcar inicialización completa
            document.body.dataset.alertasIndexPageInit = 'true';
        };

        // Eliminar listener previo
        if (window.initAlertasIndexPage) {
            document.removeEventListener('livewire:navigated', window.initAlertasIndexPage);
        }

        // Registrar y ejecutar
        window.initAlertasIndexPage();
        document.addEventListener('livewire:navigated', window.initAlertasIndexPage);
    </script>
    <style>
        /* ===== ANIMACIONES DEL MODAL ===== */
        #modalVerMensaje .modal-overlay {
            opacity: 0;
            transition: opacity 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        #modalVerMensaje .modal-content {
            opacity: 0;
            transform: translateY(100%) scale(0.95);
            transition: all 0.4s cubic-bezier(0.32, 0.72, 0, 1);
        }
        @media (min-width: 768px) {
            #modalVerMensaje .modal-content {
                transform: translateY(30px) scale(0.95);
            }
        }
        #modalVerMensaje.show .modal-overlay {
            opacity: 1;
        }
        #modalVerMensaje.show .modal-content {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
        #modalVerMensaje.closing .modal-overlay {
            opacity: 0;
        }
        #modalVerMensaje.closing .modal-content {
            opacity: 0;
            transform: translateY(100%) scale(0.95);
        }
        @media (min-width: 768px) {
            #modalVerMensaje.closing .modal-content {
                transform: translateY(30px) scale(0.95);
            }
        }

        /* ===== MENSAJES ===== */
        #hiloContenido > div {
            animation: msgAppear 0.3s cubic-bezier(0.4, 0, 0.2, 1) forwards;
        }
        @keyframes msgAppear {
            from { opacity: 0; transform: translateY(10px) scale(0.98); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }

        /* ===== SCROLL ===== */
        #hiloContenido {
            scroll-behavior: smooth;
            scrollbar-width: thin;
            scrollbar-color: rgba(0,0,0,0.12) transparent;
        }
        #hiloContenido::-webkit-scrollbar { width: 6px; }
        #hiloContenido::-webkit-scrollbar-track { background: transparent; }
        #hiloContenido::-webkit-scrollbar-thumb {
            background: rgba(0,0,0,0.12);
            border-radius: 10px;
        }
        #hiloContenido::-webkit-scrollbar-thumb:hover {
            background: rgba(0,0,0,0.2);
        }

        /* ===== INPUT ===== */
        #textoRespuesta:focus {
            outline: none !important;
            box-shadow: none !important;
        }

        /* ===== BURBUJAS MODERNAS ===== */
        .chat-bubble {
            position: relative;
            word-wrap: break-word;
            box-shadow: 0 1px 2px rgba(0,0,0,0.08);
        }
        .chat-bubble-out {
            background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
            border-radius: 18px 18px 4px 18px;
        }
        .chat-bubble-in {
            background: white;
            border-radius: 18px 18px 18px 4px;
            border: 1px solid rgba(0,0,0,0.04);
        }
    </style>
</x-app-layout>
