<x-app-layout>
    <x-slot name="title">Incorporaciones</x-slot>

    <div class="w-full max-w-7xl mx-auto py-6 px-4 sm:px-6">
        <!-- Cabecera -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Incorporaciones de Trabajadores</h1>
                <p class="text-gray-600 mt-1">Gestiona las nuevas incorporaciones y su documentación</p>
            </div>
            <a href="{{ route('incorporaciones.create') }}"
                class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Nueva Incorporación
            </a>
        </div>

        <!-- Estadísticas -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                <div class="text-2xl font-bold text-yellow-700">{{ $stats['pendientes'] }}</div>
                <div class="text-sm text-yellow-600">Pendientes</div>
            </div>
            <div class="bg-orange-50 border border-orange-200 rounded-lg p-4">
                <div class="text-2xl font-bold text-orange-700">{{ $stats['datos_recibidos'] }}</div>
                <div class="text-sm text-orange-600">Datos recibidos</div>
            </div>
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                <div class="text-2xl font-bold text-blue-700">{{ $stats['en_proceso'] }}</div>
                <div class="text-sm text-blue-600">En proceso</div>
            </div>
            <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                <div class="text-2xl font-bold text-green-700">{{ $stats['completadas'] }}</div>
                <div class="text-sm text-green-600">Completadas</div>
            </div>
        </div>

        <!-- Filtros -->
        <div class="bg-white rounded-lg shadow-sm border p-4 mb-6">
            <form method="GET" action="{{ route('incorporaciones.index') }}" class="flex flex-wrap gap-4">
                <div class="flex-1 min-w-[200px]">
                    <input type="text" name="buscar" value="{{ request('buscar') }}"
                        placeholder="Buscar por nombre, email o DNI..."
                        class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500">
                </div>
                <div>
                    <select name="estado" class="rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500">
                        <option value="">Todos los estados</option>
                        <option value="pendiente" {{ request('estado') == 'pendiente' ? 'selected' : '' }}>Pendiente
                        </option>
                        <option value="datos_recibidos" {{ request('estado') == 'datos_recibidos' ? 'selected' : '' }}>
                            Datos recibidos</option>
                        <option value="en_proceso" {{ request('estado') == 'en_proceso' ? 'selected' : '' }}>En proceso
                        </option>
                        <option value="completada" {{ request('estado') == 'completada' ? 'selected' : '' }}>Completada
                        </option>
                        <option value="cancelada" {{ request('estado') == 'cancelada' ? 'selected' : '' }}>Cancelada
                        </option>
                    </select>
                </div>
                <div>
                    <select name="empresa" class="rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500">
                        <option value="">Todas las empresas</option>
                        @foreach($empresas as $empresa)
                            <option value="{{ $empresa->id }}" {{ request('empresa') == $empresa->id ? 'selected' : '' }}>
                                {{ $empresa->nombre }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="flex items-center gap-2">
                    <input type="checkbox" name="no_asignado" id="no_asignado" value="1"
                        {{ request('no_asignado') ? 'checked' : '' }}
                        class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                    <label for="no_asignado" class="text-sm text-gray-700">Sin asignar</label>
                </div>
                <button type="submit" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 rounded-lg transition">
                    Filtrar
                </button>
                @if (request()->hasAny(['buscar', 'estado', 'empresa', 'no_asignado']))
                    <a href="{{ route('incorporaciones.index') }}" class="px-4 py-2 text-gray-600 hover:text-gray-800">
                        Limpiar
                    </a>
                @endif
            </form>
        </div>

        <!-- Tabla de incorporaciones -->
        <div class="bg-white rounded-lg shadow-sm border overflow-hidden">
            @if ($incorporaciones->isEmpty())
                <div class="p-8 text-center text-gray-500">
                    <svg class="w-16 h-16 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                    <p>No hay incorporaciones registradas</p>
                    <a href="{{ route('incorporaciones.create') }}"
                        class="mt-4 inline-block text-blue-600 hover:underline">
                        Crear la primera incorporación
                    </a>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Candidato
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Empresa</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Estado</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Documentos
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Usuario</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fecha Inc.
                                </th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Acciones
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach ($incorporaciones as $inc)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4">
                                        <div class="font-medium text-gray-900">{{ $inc->name }}
                                            {{ $inc->primer_apellido }} {{ $inc->segundo_apellido }}</div>
                                        <div class="text-sm text-gray-500">
                                            @if ($inc->dni)
                                                {{ $inc->dni }} &middot;
                                            @endif
                                            {{ $inc->email ?: $inc->email_provisional ?: 'Sin email' }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                            {{ $inc->empresa_nombre }}
                                        </span>
                                        @if ($inc->puesto)
                                            <div class="text-xs text-gray-500 mt-1">{{ $inc->puesto }}</div>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4">
                                        @php $badge = $inc->estado_badge; @endphp
                                        <span
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                            bg-{{ $badge['color'] }}-100 text-{{ $badge['color'] }}-800">
                                            {{ $badge['texto'] }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center">
                                            <div class="w-24 bg-gray-200 rounded-full h-2 mr-2">
                                                <div class="bg-blue-600 h-2 rounded-full"
                                                    style="width: {{ $inc->porcentajeDocumentosPost() }}%"></div>
                                            </div>
                                            <span
                                                class="text-sm text-gray-600">{{ $inc->porcentajeDocumentosPost() }}%</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        @if ($inc->user_id)
                                            <span
                                                class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                <div class="w-1.5 h-1.5 rounded-full bg-green-500"></div>
                                                Asignado
                                            </span>
                                        @else
                                            <span
                                                class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                <div class="w-1.5 h-1.5 rounded-full bg-red-500"></div>
                                                Sin asignar
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4">
                                        @if ($inc->fecha_incorporacion)
                                            <span class="text-sm text-gray-700 font-medium">
                                                {{ $inc->fecha_incorporacion->format('d/m/Y') }}
                                            </span>
                                        @else
                                            <span
                                                class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800 border border-amber-300">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                                </svg>
                                                Sin fecha
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <a href="{{ route('incorporaciones.show', $inc) }}"
                                            class="text-blue-600 hover:text-blue-800 font-medium">
                                            Ver detalles
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Paginación -->
                <div class="px-6 py-4 border-t">
                    {{ $incorporaciones->withQueryString()->links() }}
                </div>
            @endif
        </div>
    </div>

    @if (session('success'))
        <script>
            function initIncorporacionesPage() {
                // Prevenir doble inicialización
                if (document.body.dataset.incorporacionesPageInit === 'true') return;

                console.log('🔍 Inicializando página de incorporaciones...');

                // Mostrar mensaje de éxito si existe
                @if (session('success'))
                    alert('{{ session('success') }}');
                @endif

                // Marcar como inicializado
                document.body.dataset.incorporacionesPageInit = 'true';
            }

            // Registrar en el sistema global
            window.pageInitializers = window.pageInitializers || [];
            window.pageInitializers.push(initIncorporacionesPage);

            // Configurar listeners
            document.addEventListener('livewire:navigated', initIncorporacionesPage);
            document.addEventListener('DOMContentLoaded', initIncorporacionesPage);

            // Limpiar flag antes de navegar
            document.addEventListener('livewire:navigating', () => {
                document.body.dataset.incorporacionesPageInit = 'false';
            });
        </script>
    @endif
</x-app-layout>
