<x-app-layout>
    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="mb-6">
                <h2 class="text-2xl font-bold text-gray-900">Ajustes</h2>
                <p class="mt-1 text-sm text-gray-600">
                    Configuración de turnos laborales y días festivos
                </p>
            </div>

            <!-- Alert de éxito -->
            @if(session('success'))
                <div class="mb-4 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline">{{ session('success') }}</span>
                </div>
            @endif

            @if(session('error'))
                <div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline">{{ session('error') }}</span>
                </div>
            @endif

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- TABLA TURNOS -->
                <div class="bg-white rounded-lg shadow overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                        <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                            <span class="mr-2">🕐</span> Turnos
                        </h3>
                        <a href="{{ route('turnos.create') }}" wire:navigate
                            class="inline-flex items-center px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                            </svg>
                            Nuevo
                        </a>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nombre</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Horario</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Estado</th>
                                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse($turnos as $turno)
                                    <tr class="{{ $turno->activo ? '' : 'bg-gray-50 opacity-60' }}">
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <div class="flex items-center">
                                                @if($turno->color)
                                                    <span class="w-3 h-3 rounded-full mr-2" style="background-color: {{ $turno->color }}"></span>
                                                @endif
                                                <span class="text-sm font-medium text-gray-900">{{ $turno->nombre }}</span>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-600">
                                            {{ substr($turno->hora_inicio, 0, 5) }} - {{ substr($turno->hora_fin, 0, 5) }}
                                            @if($turno->es_partido && $turno->hora_inicio2)
                                                <br><span class="text-xs text-gray-400">{{ substr($turno->hora_inicio2, 0, 5) }} - {{ substr($turno->hora_fin2, 0, 5) }}</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <form action="{{ route('turnos.toggle', $turno) }}" method="POST" class="inline">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit"
                                                    class="px-2 py-1 rounded-full text-xs font-semibold {{ $turno->activo ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                                    {{ $turno->activo ? 'Activo' : 'Inactivo' }}
                                                </button>
                                            </form>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-center text-sm">
                                            <a href="{{ route('turnos.edit', $turno) }}" wire:navigate class="text-blue-600 hover:text-blue-900 mr-2">Editar</a>
                                            <form action="{{ route('turnos.destroy', $turno) }}" method="POST" class="inline"
                                                onsubmit="return confirm('¿Eliminar este turno?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-600 hover:text-red-900">Eliminar</button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-4 py-8 text-center text-gray-500">
                                            No hay turnos configurados
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- TABLA FESTIVOS -->
                <div class="bg-white rounded-lg shadow overflow-hidden" x-data="{
                    openModal: false,
                    editingId: null,
                    titulo: '',
                    fecha: '',
                    isSubmitting: false
                }">
                    <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                        <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                            <span class="mr-2">📅</span> Festivos ({{ $anioActual }})
                        </h3>
                        <button @click="openModal = true; editingId = null; titulo = ''; fecha = '';"
                            class="inline-flex items-center px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                            </svg>
                            Nuevo
                        </button>
                    </div>
                    <div class="overflow-x-auto max-h-96 overflow-y-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50 sticky top-0">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fecha</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Título</th>
                                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse($festivos as $festivo)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <span class="text-sm font-medium text-gray-900">{{ $festivo->fecha->format('d/m/Y') }}</span>
                                            <span class="text-gray-400 text-xs ml-1">({{ $festivo->fecha->translatedFormat('D') }})</span>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-700">
                                            {{ $festivo->titulo }}
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-center text-sm">
                                            <button @click="openModal = true; editingId = {{ $festivo->id }}; titulo = '{{ addslashes($festivo->titulo) }}'; fecha = '{{ $festivo->fecha->format('Y-m-d') }}';"
                                                class="text-blue-600 hover:text-blue-900 mr-2">Editar</button>
                                            <form action="{{ route('festivos.destroy', $festivo) }}" method="POST" class="inline"
                                                onsubmit="return confirm('¿Eliminar este festivo?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-600 hover:text-red-900">Eliminar</button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="px-4 py-8 text-center text-gray-500">
                                            No hay festivos registrados
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Modal Crear/Editar Festivo -->
                    <div x-show="openModal" x-cloak
                        class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
                        <div @click.away="openModal = false"
                            class="bg-white w-full max-w-md rounded-lg shadow-lg p-6 space-y-4">
                            <h3 class="text-xl font-semibold text-gray-800" x-text="editingId ? 'Editar Festivo' : 'Nuevo Festivo'"></h3>
                            <form method="POST" :action="editingId ? '{{ url('festivos') }}/' + editingId : '{{ route('festivos.store') }}'"
                                @submit="isSubmitting = true">
                                @csrf
                                <input type="hidden" name="_method" :value="editingId ? 'PUT' : 'POST'">
                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Fecha</label>
                                    <input type="date" name="fecha" x-model="fecha" required
                                        class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-blue-500 focus:border-blue-500">
                                </div>
                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Título</label>
                                    <input type="text" name="titulo" x-model="titulo" placeholder="Ej: Navidad"
                                        class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-blue-500 focus:border-blue-500">
                                </div>
                                <div class="flex justify-end space-x-3 mt-6">
                                    <button type="button" @click="openModal = false"
                                        class="px-4 py-2 text-gray-600 hover:text-gray-800 transition-colors">
                                        Cancelar
                                    </button>
                                    <button type="submit" :disabled="isSubmitting"
                                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 transition-colors">
                                        <span x-show="!isSubmitting" x-text="editingId ? 'Actualizar' : 'Guardar'"></span>
                                        <span x-show="isSubmitting">Guardando...</span>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Nota informativa -->
            <div class="mt-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
                <div class="flex">
                    <svg class="h-5 w-5 text-blue-400 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                            d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                            clip-rule="evenodd"></path>
                    </svg>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-blue-800">Información</h3>
                        <div class="mt-2 text-sm text-blue-700">
                            <ul class="list-disc list-inside space-y-1">
                                <li><strong>Turnos:</strong> Define los horarios laborales y sus configuraciones para el calendario de producción</li>
                                <li><strong>Festivos:</strong> Los días festivos se excluyen automáticamente del cálculo de jornadas laborales</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
