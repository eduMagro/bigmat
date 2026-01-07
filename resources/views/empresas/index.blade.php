<x-app-layout>
    <x-slot name="title">Empresa</x-slot>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Configuración de Empresa') }}
        </h2>
    </x-slot>

    <div class="px-2 sm:px-6 py-4" x-data="{
        openModal: false,
        editingId: null,
        form: {
            nombre: '',
            nif: '',
            direccion: '',
            localidad: '',
            provincia: '',
            codigo_postal: '',
            telefono: '',
            email: '',
            numero_ss: ''
        },
        isSubmitting: false,
        resetForm() {
            this.form = {
                nombre: '',
                nif: '',
                direccion: '',
                localidad: '',
                provincia: '',
                codigo_postal: '',
                telefono: '',
                email: '',
                numero_ss: ''
            };
        },
        // Obras (Lugares de trabajo)
        openObraModal: false,
        editingObraId: null,
        obraForm: {
            obra: '',
            direccion: '',
            ciudad: '',
            latitud: '',
            longitud: '',
            distancia: ''
        },
        isObraSubmitting: false,
        resetObraForm() {
            this.obraForm = {
                obra: '',
                direccion: '',
                ciudad: '',
                latitud: '',
                longitud: '',
                distancia: ''
            };
        },
        async submitObra() {
            this.isObraSubmitting = true;
            try {
                const url = this.editingObraId
                    ? '{{ route('empresas.obras.updateField') }}'
                    : '{{ route('empresas.obras.store') }}';

                let body;
                if (this.editingObraId) {
                    // Para edicion, enviamos campo por campo
                    const fields = ['obra', 'direccion', 'ciudad', 'latitud', 'longitud', 'distancia'];
                    for (const field of fields) {
                        await fetch('{{ route('empresas.obras.updateField') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify({
                                id: this.editingObraId,
                                field: field,
                                value: this.obraForm[field]
                            })
                        });
                    }
                    window.location.reload();
                    return;
                }

                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify(this.obraForm)
                });

                const data = await response.json();
                if (data.success) {
                    window.location.reload();
                } else {
                    alert(data.message || 'Error al guardar');
                }
            } catch (error) {
                console.error(error);
                alert('Error al guardar el lugar de trabajo');
            } finally {
                this.isObraSubmitting = false;
            }
        },
        async deleteObra(id) {
            if (!confirm('¿Eliminar este lugar de trabajo?')) return;
            try {
                const response = await fetch('{{ route('empresas.obras.destroy') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ id: id })
                });
                const data = await response.json();
                if (data.success) {
                    window.location.reload();
                } else {
                    alert(data.message || 'Error al eliminar');
                }
            } catch (error) {
                alert('Error al eliminar');
            }
        },
        // Categorías
        openCategoriaModal: false,
        editingCategoriaId: null,
        categoriaForm: {
            nombre: ''
        },
        isCategoriaSubmitting: false,
        resetCategoriaForm() {
            this.categoriaForm = {
                nombre: ''
            };
        },
        async submitCategoria() {
            this.isCategoriaSubmitting = true;
            try {
                if (this.editingCategoriaId) {
                    await fetch('{{ route('empresas.categorias.updateField') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            id: this.editingCategoriaId,
                            field: 'nombre',
                            value: this.categoriaForm.nombre
                        })
                    });
                    window.location.reload();
                    return;
                }

                const response = await fetch('{{ route('empresas.categorias.store') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify(this.categoriaForm)
                });

                const data = await response.json();
                if (data.success) {
                    window.location.reload();
                } else {
                    alert(data.message || 'Error al guardar');
                }
            } catch (error) {
                console.error(error);
                alert('Error al guardar la categoría');
            } finally {
                this.isCategoriaSubmitting = false;
            }
        },
        async deleteCategoria(id) {
            if (!confirm('¿Eliminar esta categoría?')) return;
            try {
                const response = await fetch('{{ route('empresas.categorias.destroy') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ id: id })
                });
                const data = await response.json();
                if (data.success) {
                    window.location.reload();
                } else {
                    alert(data.message || 'Error al eliminar');
                }
            } catch (error) {
                alert('Error al eliminar');
            }
        }
    }">

        @if (session('success'))
            <div class="mb-4 bg-green-50 border-l-4 border-green-500 p-4 rounded-lg shadow-sm">
                <p class="text-green-700 font-medium">{{ session('success') }}</p>
            </div>
        @endif

        @if (session('error'))
            <div class="mb-4 bg-red-50 border-l-4 border-red-500 p-4 rounded-lg shadow-sm">
                <p class="text-red-700 font-medium">{{ session('error') }}</p>
            </div>
        @endif

        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold text-gray-700">Lista de Empresas</h3>
            <button @click="openModal = true; editingId = null; resetForm();"
                class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Nueva Empresa
            </button>
        </div>

        <div class="bg-white shadow-lg rounded-lg overflow-hidden border border-gray-200">
            <div class="overflow-x-auto">
                <table class="w-full table-auto border-collapse">
                    <thead class="bg-blue-500 text-white">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase">Nombre</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase">NIF</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase">Localidad</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase">Telefono</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold uppercase">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse ($empresas as $empresa)
                            <tr class="hover:bg-blue-50 transition-colors duration-150">
                                <td class="px-4 py-3">
                                    <span class="font-medium text-gray-900">{{ $empresa->nombre }}</span>
                                </td>
                                <td class="px-4 py-3 text-gray-600">{{ $empresa->nif ?? '-' }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ $empresa->localidad ?? '-' }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ $empresa->telefono ?? '-' }}</td>
                                <td class="px-4 py-3 text-center">
                                    <div class="flex items-center justify-center space-x-2">
                                        <button
                                            @click="openModal = true; editingId = {{ $empresa->id }}; form = {
                                                nombre: '{{ addslashes($empresa->nombre) }}',
                                                nif: '{{ addslashes($empresa->nif ?? '') }}',
                                                direccion: '{{ addslashes($empresa->direccion ?? '') }}',
                                                localidad: '{{ addslashes($empresa->localidad ?? '') }}',
                                                provincia: '{{ addslashes($empresa->provincia ?? '') }}',
                                                codigo_postal: '{{ addslashes($empresa->codigo_postal ?? '') }}',
                                                telefono: '{{ addslashes($empresa->telefono ?? '') }}',
                                                email: '{{ addslashes($empresa->email ?? '') }}',
                                                numero_ss: '{{ addslashes($empresa->numero_ss ?? '') }}'
                                            };"
                                            class="inline-flex items-center px-3 py-1.5 bg-yellow-100 text-yellow-700 rounded-lg hover:bg-yellow-200 transition-colors">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                            Editar
                                        </button>
                                        <form action="{{ route('empresas.destroy', $empresa) }}" method="POST"
                                            onsubmit="return confirm('¿Eliminar esta empresa?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                class="inline-flex items-center px-3 py-1.5 bg-red-100 text-red-700 rounded-lg hover:bg-red-200 transition-colors">
                                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                                Eliminar
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-8 text-center text-gray-500">
                                    <svg class="w-12 h-12 mx-auto text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                    </svg>
                                    No hay empresas registradas
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Modal Crear/Editar -->
        <div x-show="openModal" x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
            <div @click.away="openModal = false"
                class="bg-white w-full max-w-2xl rounded-lg shadow-lg p-6 max-h-[90vh] overflow-y-auto">

                <h3 class="text-xl font-semibold text-gray-800 mb-4" x-text="editingId ? 'Editar Empresa' : 'Nueva Empresa'"></h3>

                <form method="POST" :action="editingId ? '{{ url('empresas') }}/' + editingId : '{{ route('empresas.store') }}'"
                    @submit="isSubmitting = true">
                    @csrf
                    <input type="hidden" name="_method" :value="editingId ? 'PUT' : 'POST'">

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nombre *</label>
                            <input type="text" name="nombre" x-model="form.nombre" required
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">NIF/CIF</label>
                            <input type="text" name="nif" x-model="form.nif"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">N. Seguridad Social</label>
                            <input type="text" name="numero_ss" x-model="form.numero_ss"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Direccion</label>
                            <input type="text" name="direccion" x-model="form.direccion"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Localidad</label>
                            <input type="text" name="localidad" x-model="form.localidad"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Provincia</label>
                            <input type="text" name="provincia" x-model="form.provincia"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Codigo Postal</label>
                            <input type="text" name="codigo_postal" x-model="form.codigo_postal"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Telefono</label>
                            <input type="text" name="telefono" x-model="form.telefono"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                            <input type="email" name="email" x-model="form.email"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>
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

        <!-- ===== SECCIÓN LUGARES DE TRABAJO ===== -->
        <div class="mt-8">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-gray-700">Lugares de Trabajo</h3>
                <button @click="openObraModal = true; editingObraId = null; resetObraForm();"
                    class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Nuevo Lugar de Trabajo
                </button>
            </div>

            <div class="bg-white shadow-lg rounded-lg overflow-hidden border border-gray-200">
                <div class="overflow-x-auto">
                    <table class="w-full table-auto border-collapse">
                        <thead class="bg-green-500 text-white">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase">Nombre</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase">Ciudad</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase">Coordenadas</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase">Radio (m)</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold uppercase">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse ($obras as $obra)
                                <tr class="hover:bg-green-50 transition-colors duration-150">
                                    <td class="px-4 py-3">
                                        <span class="font-medium text-gray-900">{{ $obra->obra }}</span>
                                        @if($obra->direccion)
                                            <p class="text-xs text-gray-500">{{ $obra->direccion }}</p>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-gray-600">{{ $obra->ciudad ?? '-' }}</td>
                                    <td class="px-4 py-3 text-gray-600 text-xs">
                                        @if($obra->latitud && $obra->longitud)
                                            <span class="text-green-600">{{ number_format($obra->latitud, 6) }}, {{ number_format($obra->longitud, 6) }}</span>
                                        @else
                                            <span class="text-red-500">Sin coordenadas</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-gray-600">{{ $obra->distancia ?? '200' }}</td>
                                    <td class="px-4 py-3 text-center">
                                        <div class="flex items-center justify-center space-x-2">
                                            <button
                                                @click="openObraModal = true; editingObraId = {{ $obra->id }}; obraForm = {
                                                    obra: '{{ addslashes($obra->obra) }}',
                                                    direccion: '{{ addslashes($obra->direccion ?? '') }}',
                                                    ciudad: '{{ addslashes($obra->ciudad ?? '') }}',
                                                    latitud: '{{ $obra->latitud ?? '' }}',
                                                    longitud: '{{ $obra->longitud ?? '' }}',
                                                    distancia: '{{ $obra->distancia ?? '' }}'
                                                };"
                                                class="inline-flex items-center px-3 py-1.5 bg-yellow-100 text-yellow-700 rounded-lg hover:bg-yellow-200 transition-colors">
                                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                </svg>
                                                Editar
                                            </button>
                                            <button @click="deleteObra({{ $obra->id }})"
                                                class="inline-flex items-center px-3 py-1.5 bg-red-100 text-red-700 rounded-lg hover:bg-red-200 transition-colors">
                                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                                Eliminar
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-8 text-center text-gray-500">
                                        <svg class="w-12 h-12 mx-auto text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                        </svg>
                                        No hay lugares de trabajo registrados
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Modal Crear/Editar Lugar de Trabajo -->
        <div x-show="openObraModal" x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
            <div @click.away="openObraModal = false"
                class="bg-white w-full max-w-lg rounded-lg shadow-lg p-6 max-h-[90vh] overflow-y-auto">

                <h3 class="text-xl font-semibold text-gray-800 mb-4" x-text="editingObraId ? 'Editar Lugar de Trabajo' : 'Nuevo Lugar de Trabajo'"></h3>

                <form @submit.prevent="submitObra()">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nombre del lugar *</label>
                            <input type="text" x-model="obraForm.obra" required
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-green-500 focus:border-green-500"
                                placeholder="Ej: Oficina Central, Almacen Norte...">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Ciudad</label>
                            <input type="text" x-model="obraForm.ciudad"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-green-500 focus:border-green-500"
                                placeholder="Ciudad">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Direccion</label>
                            <input type="text" x-model="obraForm.direccion"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-green-500 focus:border-green-500"
                                placeholder="Direccion completa">
                        </div>

                        <!-- Coordenadas para fichaje -->
                        <div class="md:col-span-2 border-t pt-4 mt-2">
                            <p class="text-sm font-medium text-gray-700 mb-2">Coordenadas para fichaje (opcional)</p>
                            <p class="text-xs text-gray-500 mb-3">Si se configuran coordenadas, los empleados podran fichar cuando esten cerca de este lugar.</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Latitud</label>
                            <input type="number" step="0.000001" x-model="obraForm.latitud"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-green-500 focus:border-green-500"
                                placeholder="Ej: 40.416775">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Longitud</label>
                            <input type="number" step="0.000001" x-model="obraForm.longitud"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-green-500 focus:border-green-500"
                                placeholder="Ej: -3.703790">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Radio permitido (metros)</label>
                            <input type="number" x-model="obraForm.distancia"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-green-500 focus:border-green-500"
                                placeholder="200">
                            <p class="text-xs text-gray-500 mt-1">Por defecto: 200 metros</p>
                        </div>
                    </div>

                    <div class="flex justify-end space-x-3 mt-6">
                        <button type="button" @click="openObraModal = false"
                            class="px-4 py-2 text-gray-600 hover:text-gray-800 transition-colors">
                            Cancelar
                        </button>
                        <button type="submit" :disabled="isObraSubmitting"
                            class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 disabled:opacity-50 transition-colors">
                            <span x-show="!isObraSubmitting" x-text="editingObraId ? 'Actualizar' : 'Guardar'"></span>
                            <span x-show="isObraSubmitting">Guardando...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- ===== SECCIÓN CATEGORÍAS ===== -->
        <div class="mt-8">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-gray-700">Categorías Profesionales</h3>
                <button @click="openCategoriaModal = true; editingCategoriaId = null; resetCategoriaForm();"
                    class="inline-flex items-center px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Nueva Categoría
                </button>
            </div>

            <div class="bg-white shadow-lg rounded-lg overflow-hidden border border-gray-200">
                <div class="overflow-x-auto">
                    <table class="w-full table-auto border-collapse">
                        <thead class="bg-purple-500 text-white">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase">ID</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase">Nombre</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold uppercase">Empleados</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold uppercase">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse ($categorias as $categoria)
                                <tr class="hover:bg-purple-50 transition-colors duration-150">
                                    <td class="px-4 py-3 text-gray-500 text-sm">{{ $categoria->id }}</td>
                                    <td class="px-4 py-3">
                                        <span class="font-medium text-gray-900">{{ $categoria->nombre }}</span>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $categoria->users_count > 0 ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                            {{ $categoria->users_count }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <div class="flex items-center justify-center space-x-2">
                                            <button
                                                @click="openCategoriaModal = true; editingCategoriaId = {{ $categoria->id }}; categoriaForm = {
                                                    nombre: '{{ addslashes($categoria->nombre) }}'
                                                };"
                                                class="inline-flex items-center px-3 py-1.5 bg-yellow-100 text-yellow-700 rounded-lg hover:bg-yellow-200 transition-colors">
                                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                </svg>
                                                Editar
                                            </button>
                                            <button @click="deleteCategoria({{ $categoria->id }})"
                                                class="inline-flex items-center px-3 py-1.5 bg-red-100 text-red-700 rounded-lg hover:bg-red-200 transition-colors {{ $categoria->users_count > 0 ? 'opacity-50 cursor-not-allowed' : '' }}"
                                                {{ $categoria->users_count > 0 ? 'disabled' : '' }}
                                                title="{{ $categoria->users_count > 0 ? 'Tiene empleados asignados' : 'Eliminar categoría' }}">
                                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                                Eliminar
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-4 py-8 text-center text-gray-500">
                                        <svg class="w-12 h-12 mx-auto text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                                        </svg>
                                        No hay categorías registradas
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Modal Crear/Editar Categoría -->
        <div x-show="openCategoriaModal" x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
            <div @click.away="openCategoriaModal = false"
                class="bg-white w-full max-w-md rounded-lg shadow-lg p-6">

                <h3 class="text-xl font-semibold text-gray-800 mb-4" x-text="editingCategoriaId ? 'Editar Categoría' : 'Nueva Categoría'"></h3>

                <form @submit.prevent="submitCategoria()">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nombre de la categoría *</label>
                            <input type="text" x-model="categoriaForm.nombre" required
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-purple-500 focus:border-purple-500"
                                placeholder="Ej: Oficial 1ª, Peón, Encargado...">
                        </div>
                    </div>

                    <div class="flex justify-end space-x-3 mt-6">
                        <button type="button" @click="openCategoriaModal = false"
                            class="px-4 py-2 text-gray-600 hover:text-gray-800 transition-colors">
                            Cancelar
                        </button>
                        <button type="submit" :disabled="isCategoriaSubmitting"
                            class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 disabled:opacity-50 transition-colors">
                            <span x-show="!isCategoriaSubmitting" x-text="editingCategoriaId ? 'Actualizar' : 'Guardar'"></span>
                            <span x-show="isCategoriaSubmitting">Guardando...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>
</x-app-layout>
