<x-app-layout>
    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="mb-6 flex justify-between items-center">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Configuración de Turnos</h2>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        Administra los turnos laborales, horarios y offsets para el calendario de producción
                    </p>
                </div>
                <a href="{{ route('turnos.create') }}" wire:navigate
                    class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    Nuevo Turno
                </a>
            </div>

            <!-- Alert de éxito -->
            @if(session('success'))
                <div class="mb-4 bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-800 text-green-700 dark:text-green-400 px-4 py-3 rounded relative"
                    role="alert">
                    <span class="block sm:inline">{{ session('success') }}</span>
                </div>
            @endif

            <!-- Tabla de turnos -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Orden
                            </th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Nombre
                            </th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Horario 1
                            </th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Horario 2
                            </th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Offset Días
                            </th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Estado
                            </th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Acciones
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($turnos as $turno)
                            <tr class="{{ $turno->activo ? '' : 'bg-gray-50 dark:bg-gray-700 opacity-60' }}">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                    {{ $turno->orden }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        @if($turno->color)
                                            <span class="w-4 h-4 rounded-full mr-3"
                                                style="background-color: {{ $turno->color }}"></span>
                                        @endif
                                        <span class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $turno->nombre }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                    {{ substr($turno->hora_inicio, 0, 5) }} - {{ substr($turno->hora_fin, 0, 5) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                    @if($turno->es_partido && $turno->hora_inicio2)
                                        {{ substr($turno->hora_inicio2, 0, 5) }} - {{ substr($turno->hora_fin2, 0, 5) }}
                                    @else
                                        <span class="text-gray-400 dark:text-gray-500">-</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">
                                    <div class="space-y-1">
                                        <div>
                                            <span class="font-medium">Inicio:</span>
                                            @if($turno->offset_dias_inicio == -1)
                                                <span class="text-orange-600">Día anterior</span>
                                            @elseif($turno->offset_dias_inicio == 0)
                                                <span class="text-green-600">Mismo día</span>
                                            @else
                                                <span class="text-blue-600">Día siguiente</span>
                                            @endif
                                        </div>
                                        <div>
                                            <span class="font-medium">Fin:</span>
                                            @if($turno->offset_dias_fin == -1)
                                                <span class="text-orange-600">Día anterior</span>
                                            @elseif($turno->offset_dias_fin == 0)
                                                <span class="text-green-600">Mismo día</span>
                                            @else
                                                <span class="text-blue-600">Día siguiente</span>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <form action="{{ route('turnos.toggle', $turno) }}" method="POST"
                                        class="inline">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit"
                                            class="px-3 py-1 rounded-full text-xs font-semibold {{ $turno->activo ? 'bg-green-100 dark:bg-green-900/50 text-green-800 dark:text-green-400' : 'bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-300' }}">
                                            {{ $turno->activo ? 'Activo' : 'Inactivo' }}
                                        </button>
                                    </form>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                                    <a href="{{ route('turnos.edit', $turno) }}" wire:navigate
                                        class="text-blue-600 dark:text-blue-400 hover:text-blue-900 dark:hover:text-blue-300">
                                        Editar
                                    </a>
                                    <form action="{{ route('turnos.destroy', $turno) }}" method="POST"
                                        class="inline"
                                        onsubmit="return confirm('¿Estás seguro de eliminar este turno?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300">
                                            Eliminar
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                                    No hay turnos configurados. <a href="{{ route('turnos.create') }}" wire:navigate
                                        class="text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300">Crear el primero</a>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Nota explicativa -->
            <div class="mt-6 bg-blue-50 dark:bg-blue-900/30 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                <div class="flex">
                    <svg class="h-5 w-5 text-blue-400 dark:text-blue-300 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                            d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                            clip-rule="evenodd"></path>
                    </svg>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-blue-800 dark:text-blue-300">Información sobre los turnos</h3>
                        <div class="mt-2 text-sm text-blue-700 dark:text-blue-400">
                            <ul class="list-disc list-inside space-y-1">
                                <li><strong>Offset de días:</strong> Permite manejar turnos que cruzan la medianoche (ej:
                                    turno de noche)</li>
                                <li><strong>Turno de noche:</strong> Si el turno es 22:00-06:00, el offset de fin debe ser
                                    "Día siguiente"</li>
                                <li><strong>El turno de noche del lunes</strong> comienza el domingo a las 22:00 y termina
                                    el lunes a las 06:00</li>
                                <li><strong>Turno partido:</strong> Permite definir dos segmentos horarios (ej: 08:00-14:00 y 16:00-19:00)</li>
                                <li><strong>Solo turnos activos</strong> se usan para calcular horarios laborales en el
                                    calendario</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
