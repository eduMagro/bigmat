<x-app-layout>
    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8" x-data="{
            // Empresas
            openEmpresaModal: false,
            editingEmpresaId: null,
            empresaForm: {
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
            isEmpresaSubmitting: false,
            resetEmpresaForm() {
                this.empresaForm = {
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
            // Agrupaciones de Turnos (Plantillas)
            openAgrupacionModal: false,
            editingAgrupacionId: null,
            agrupacionForm: {
                nombre: '',
                descripcion: '',
                activo: true,
                dias: {
                    1: null, // Lunes
                    2: null, // Martes
                    3: null, // Miercoles
                    4: null, // Jueves
                    5: null, // Viernes
                    6: null, // Sabado
                    0: null  // Domingo
                }
            },
            isAgrupacionSubmitting: false,
            resetAgrupacionForm() {
                this.agrupacionForm = {
                    nombre: '',
                    descripcion: '',
                    activo: true,
                    dias: {
                        1: null,
                        2: null,
                        3: null,
                        4: null,
                        5: null,
                        6: null,
                        0: null
                    }
                };
            },
            async submitAgrupacion() {
                this.isAgrupacionSubmitting = true;
                try {
                    const diasArray = Object.entries(this.agrupacionForm.dias).map(([dia, turnoId]) => ({
                        dia_semana: parseInt(dia),
                        turno_id: turnoId ? parseInt(turnoId) : null
                    }));

                    const payload = {
                        nombre: this.agrupacionForm.nombre,
                        descripcion: this.agrupacionForm.descripcion,
                        activo: this.agrupacionForm.activo,
                        dias: diasArray
                    };

                    const url = this.editingAgrupacionId
                        ? '{{ url("agrupaciones-turnos") }}/' + this.editingAgrupacionId
                        : '{{ route("agrupaciones-turnos.store") }}';

                    const response = await fetch(url, {
                        method: this.editingAgrupacionId ? 'PUT' : 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify(payload)
                    });

                    const data = await response.json();
                    if (data.success) {
                        window.location.reload();
                    } else {
                        alert(data.message || 'Error al guardar');
                    }
                } catch (error) {
                    console.error(error);
                    alert('Error al guardar la plantilla de turno');
                } finally {
                    this.isAgrupacionSubmitting = false;
                }
            },
            async deleteAgrupacion(id) {
                if (!confirm('¿Eliminar esta plantilla de turno?')) return;
                try {
                    const response = await fetch('{{ url("agrupaciones-turnos") }}/' + id, {
                        method: 'DELETE',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        }
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
            async toggleAgrupacion(id) {
                try {
                    const response = await fetch('{{ url("agrupaciones-turnos") }}/' + id + '/toggle', {
                        method: 'PATCH',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        }
                    });
                    const data = await response.json();
                    if (data.success) {
                        window.location.reload();
                    }
                } catch (error) {
                    alert('Error al cambiar estado');
                }
            },
            editAgrupacion(agrupacion) {
                this.editingAgrupacionId = agrupacion.id;
                this.agrupacionForm.nombre = agrupacion.nombre;
                this.agrupacionForm.descripcion = agrupacion.descripcion || '';
                this.agrupacionForm.activo = agrupacion.activo;
                // Resetear dias
                this.agrupacionForm.dias = {1: null, 2: null, 3: null, 4: null, 5: null, 6: null, 0: null};
                // Llenar con los dias existentes
                if (agrupacion.dias) {
                    agrupacion.dias.forEach(dia => {
                        this.agrupacionForm.dias[dia.dia_semana] = dia.turno_id;
                    });
                }
                this.openAgrupacionModal = true;
            },
            async submitObra() {
                this.isObraSubmitting = true;
                try {
                    const url = this.editingObraId
                        ? '{{ route('empresas.obras.updateField') }}'
                        : '{{ route('empresas.obras.store') }}';

                    if (this.editingObraId) {
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
            // Categorias
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
                    alert('Error al guardar la categoria');
                } finally {
                    this.isCategoriaSubmitting = false;
                }
            },
            async deleteCategoria(id) {
                if (!confirm('¿Eliminar esta categoria?')) return;
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
            <!-- Header -->
            <div class="mb-6">
                <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Ajustes</h2>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    Configuracion general del sistema
                </p>
            </div>

            <!-- Alert de exito -->
            @if(session('success'))
                <div class="mb-4 bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-700 text-green-700 dark:text-green-300 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline">{{ session('success') }}</span>
                </div>
            @endif

            @if(session('error'))
                <div class="mb-4 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-700 text-red-700 dark:text-red-300 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline">{{ session('error') }}</span>
                </div>
            @endif

            <!-- ===== SECCION VISIBILIDAD DE USUARIOS ===== -->
            <div class="mb-8">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-300">Visibilidad de usuarios</h3>
                </div>

                <div class="bg-white dark:bg-gray-800 shadow-lg rounded-lg overflow-hidden border border-gray-200 dark:border-gray-700">
                    <form method="POST" action="{{ route('ajustes.visibilidad') }}" class="p-6">
                        @csrf
                        <div class="flex items-start justify-between gap-4">
                            <div class="flex-1">
                                <p class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                    Los responsables ven a todos los usuarios
                                </p>
                                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                    Si está activado, los responsables de departamento podrán ver y gestionar a
                                    <strong>todos</strong> los usuarios (listado de usuarios, solicitudes de vacaciones, etc.).
                                    Si está desactivado, cada responsable solo verá a los trabajadores de sus departamentos.
                                </p>
                            </div>

                            <label class="relative inline-flex items-center cursor-pointer mt-1">
                                <input type="hidden" name="responsables_ven_todos" value="0">
                                <input type="checkbox" name="responsables_ven_todos" value="1"
                                    class="sr-only peer" {{ $responsablesVenTodos ? 'checked' : '' }}
                                    onchange="this.form.submit()">
                                <div class="w-11 h-6 bg-gray-200 dark:bg-gray-600 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600">
                                </div>
                            </label>
                        </div>
                    </form>
                </div>
            </div>

            <!-- ===== SECCION EMPRESAS ===== -->
            <div class="mb-8">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-300">Empresas</h3>
                    <button @click="openEmpresaModal = true; editingEmpresaId = null; resetEmpresaForm();"
                        class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Nueva Empresa
                    </button>
                </div>

                <div class="bg-white dark:bg-gray-800 shadow-lg rounded-lg overflow-hidden border border-gray-200 dark:border-gray-700">
                    <div class="overflow-x-auto">
                        <table class="w-full table-auto border-collapse">
                            <thead class="bg-blue-500 dark:bg-blue-600 text-white">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase">Nombre</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase">NIF</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase">Localidad</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase">Telefono</th>
                                    <th class="px-4 py-3 text-center text-xs font-semibold uppercase">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                @forelse ($empresas as $empresa)
                                    <tr class="hover:bg-blue-50 dark:hover:bg-gray-700 transition-colors duration-150">
                                        <td class="px-4 py-3">
                                            <span class="font-medium text-gray-900 dark:text-gray-100">{{ $empresa->nombre }}</span>
                                        </td>
                                        <td class="px-4 py-3 text-gray-600 dark:text-gray-400">{{ $empresa->nif ?? '-' }}</td>
                                        <td class="px-4 py-3 text-gray-600 dark:text-gray-400">{{ $empresa->localidad ?? '-' }}</td>
                                        <td class="px-4 py-3 text-gray-600 dark:text-gray-400">{{ $empresa->telefono ?? '-' }}</td>
                                        <td class="px-4 py-3 text-center">
                                            <div class="flex items-center justify-center space-x-2">
                                                <button
                                                    @click="openEmpresaModal = true; editingEmpresaId = {{ $empresa->id }}; empresaForm = {
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
                                                    class="inline-flex items-center px-3 py-1.5 bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-300 rounded-lg hover:bg-yellow-200 dark:hover:bg-yellow-900/50 transition-colors">
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
                                                        class="inline-flex items-center px-3 py-1.5 bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300 rounded-lg hover:bg-red-200 dark:hover:bg-red-900/50 transition-colors">
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
                                        <td colspan="5" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                                            No hay empresas registradas
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Modal Crear/Editar Empresa -->
            <div x-show="openEmpresaModal" x-cloak
                class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
                <div @click.away="openEmpresaModal = false"
                    class="bg-white dark:bg-gray-800 w-full max-w-2xl rounded-lg shadow-lg p-6 max-h-[90vh] overflow-y-auto">

                    <h3 class="text-xl font-semibold text-gray-800 dark:text-gray-100 mb-4" x-text="editingEmpresaId ? 'Editar Empresa' : 'Nueva Empresa'"></h3>

                    <form method="POST" :action="editingEmpresaId ? '{{ url('empresas') }}/' + editingEmpresaId : '{{ route('empresas.store') }}'"
                        @submit="isEmpresaSubmitting = true">
                        @csrf
                        <input type="hidden" name="_method" :value="editingEmpresaId ? 'PUT' : 'POST'">

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nombre *</label>
                                <input type="text" name="nombre" x-model="empresaForm.nombre" required
                                    class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-lg px-3 py-2 focus:ring-blue-500 focus:border-blue-500">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">NIF/CIF</label>
                                <input type="text" name="nif" x-model="empresaForm.nif"
                                    class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-lg px-3 py-2 focus:ring-blue-500 focus:border-blue-500">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">N. Seguridad Social</label>
                                <input type="text" name="numero_ss" x-model="empresaForm.numero_ss"
                                    class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-lg px-3 py-2 focus:ring-blue-500 focus:border-blue-500">
                            </div>

                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Direccion</label>
                                <input type="text" name="direccion" x-model="empresaForm.direccion"
                                    class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-lg px-3 py-2 focus:ring-blue-500 focus:border-blue-500">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Localidad</label>
                                <input type="text" name="localidad" x-model="empresaForm.localidad"
                                    class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-lg px-3 py-2 focus:ring-blue-500 focus:border-blue-500">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Provincia</label>
                                <input type="text" name="provincia" x-model="empresaForm.provincia"
                                    class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-lg px-3 py-2 focus:ring-blue-500 focus:border-blue-500">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Codigo Postal</label>
                                <input type="text" name="codigo_postal" x-model="empresaForm.codigo_postal"
                                    class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-lg px-3 py-2 focus:ring-blue-500 focus:border-blue-500">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Telefono</label>
                                <input type="text" name="telefono" x-model="empresaForm.telefono"
                                    class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-lg px-3 py-2 focus:ring-blue-500 focus:border-blue-500">
                            </div>

                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Email</label>
                                <input type="email" name="email" x-model="empresaForm.email"
                                    class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-lg px-3 py-2 focus:ring-blue-500 focus:border-blue-500">
                            </div>
                        </div>

                        <div class="flex justify-end space-x-3 mt-6">
                            <button type="button" @click="openEmpresaModal = false"
                                class="px-4 py-2 text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200 transition-colors">
                                Cancelar
                            </button>
                            <button type="submit" :disabled="isEmpresaSubmitting"
                                class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 transition-colors">
                                <span x-show="!isEmpresaSubmitting" x-text="editingEmpresaId ? 'Actualizar' : 'Guardar'"></span>
                                <span x-show="isEmpresaSubmitting">Guardando...</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- ===== SECCION LUGARES DE TRABAJO ===== -->
            <div class="mb-8">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-300">Lugares de Trabajo</h3>
                    <button @click="openObraModal = true; editingObraId = null; resetObraForm();"
                        class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Nuevo Lugar
                    </button>
                </div>

                <div class="bg-white dark:bg-gray-800 shadow-lg rounded-lg overflow-hidden border border-gray-200 dark:border-gray-700">
                    <div class="overflow-x-auto">
                        <table class="w-full table-auto border-collapse">
                            <thead class="bg-green-500 dark:bg-green-600 text-white">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase">Nombre</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase">Ciudad</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase">Coordenadas</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase">Radio (m)</th>
                                    <th class="px-4 py-3 text-center text-xs font-semibold uppercase">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                @forelse ($obras as $obra)
                                    <tr class="hover:bg-green-50 dark:hover:bg-gray-700 transition-colors duration-150">
                                        <td class="px-4 py-3">
                                            <span class="font-medium text-gray-900 dark:text-gray-100">{{ $obra->obra }}</span>
                                            @if($obra->direccion)
                                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $obra->direccion }}</p>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-gray-600 dark:text-gray-400">{{ $obra->ciudad ?? '-' }}</td>
                                        <td class="px-4 py-3 text-gray-600 dark:text-gray-400 text-xs">
                                            @if($obra->latitud !== null && $obra->longitud !== null)
                                                <span class="text-green-600 dark:text-green-400">{{ number_format($obra->latitud, 6) }}, {{ number_format($obra->longitud, 6) }}</span>
                                            @else
                                                <span class="text-red-500 dark:text-red-400">Sin coordenadas</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-gray-600 dark:text-gray-400">{{ $obra->distancia ?? '200' }}</td>
                                        <td class="px-4 py-3 text-center">
                                            <div class="flex items-center justify-center space-x-2">
                                                <button
                                                    @click="openObraModal = true; editingObraId = {{ $obra->id }}; obraForm = {
                                                        obra: '{{ addslashes($obra->obra) }}',
                                                        direccion: '{{ addslashes($obra->direccion ?? '') }}',
                                                        ciudad: '{{ addslashes($obra->ciudad ?? '') }}',
                                                        latitud: {{ $obra->latitud !== null ? $obra->latitud : "''" }},
                                                        longitud: {{ $obra->longitud !== null ? $obra->longitud : "''" }},
                                                        distancia: {{ $obra->distancia !== null ? $obra->distancia : "''" }}
                                                    };"
                                                    class="inline-flex items-center px-3 py-1.5 bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-300 rounded-lg hover:bg-yellow-200 dark:hover:bg-yellow-900/50 transition-colors">
                                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                    </svg>
                                                    Editar
                                                </button>
                                                <button @click="deleteObra({{ $obra->id }})"
                                                    class="inline-flex items-center px-3 py-1.5 bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300 rounded-lg hover:bg-red-200 dark:hover:bg-red-900/50 transition-colors">
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
                                        <td colspan="5" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
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
                    class="bg-white dark:bg-gray-800 w-full max-w-lg rounded-lg shadow-lg p-6 max-h-[90vh] overflow-y-auto">

                    <h3 class="text-xl font-semibold text-gray-800 dark:text-gray-100 mb-4" x-text="editingObraId ? 'Editar Lugar de Trabajo' : 'Nuevo Lugar de Trabajo'"></h3>

                    <form @submit.prevent="submitObra()">
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nombre del lugar *</label>
                                <input type="text" x-model="obraForm.obra" required
                                    class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-lg px-3 py-2 focus:ring-green-500 focus:border-green-500"
                                    placeholder="Ej: Oficina Central, Almacen Norte...">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Ciudad</label>
                                <input type="text" x-model="obraForm.ciudad"
                                    class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-lg px-3 py-2 focus:ring-green-500 focus:border-green-500"
                                    placeholder="Ciudad">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Direccion</label>
                                <input type="text" x-model="obraForm.direccion"
                                    class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-lg px-3 py-2 focus:ring-green-500 focus:border-green-500"
                                    placeholder="Direccion completa">
                            </div>

                            <div class="md:col-span-2 border-t dark:border-gray-700 pt-4 mt-2">
                                <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Coordenadas para fichaje (opcional)</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">Si se configuran coordenadas, los empleados podran fichar cuando esten cerca de este lugar.</p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Latitud</label>
                                <input type="text" x-model="obraForm.latitud"
                                    class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-lg px-3 py-2 focus:ring-green-500 focus:border-green-500"
                                    placeholder="Ej: 40,416775 o 40.416775">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Longitud</label>
                                <input type="text" x-model="obraForm.longitud"
                                    class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-lg px-3 py-2 focus:ring-green-500 focus:border-green-500"
                                    placeholder="Ej: -3,703790 o -3.703790">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Radio permitido (metros)</label>
                                <input type="number" x-model="obraForm.distancia"
                                    class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-lg px-3 py-2 focus:ring-green-500 focus:border-green-500"
                                    placeholder="200">
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Por defecto: 200 metros</p>
                            </div>
                        </div>

                        <div class="flex justify-end space-x-3 mt-6">
                            <button type="button" @click="openObraModal = false"
                                class="px-4 py-2 text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200 transition-colors">
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

            <!-- ===== SECCION CATEGORIAS ===== -->
            <div class="mb-8">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-300">Categorias Profesionales</h3>
                    <button @click="openCategoriaModal = true; editingCategoriaId = null; resetCategoriaForm();"
                        class="inline-flex items-center px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Nueva Categoria
                    </button>
                </div>

                <div class="bg-white dark:bg-gray-800 shadow-lg rounded-lg overflow-hidden border border-gray-200 dark:border-gray-700">
                    <div class="overflow-x-auto">
                        <table class="w-full table-auto border-collapse">
                            <thead class="bg-purple-500 dark:bg-purple-600 text-white">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase">ID</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase">Nombre</th>
                                    <th class="px-4 py-3 text-center text-xs font-semibold uppercase">Empleados</th>
                                    <th class="px-4 py-3 text-center text-xs font-semibold uppercase">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                @forelse ($categorias as $categoria)
                                    <tr class="hover:bg-purple-50 dark:hover:bg-gray-700 transition-colors duration-150">
                                        <td class="px-4 py-3 text-gray-500 dark:text-gray-400 text-sm">{{ $categoria->id }}</td>
                                        <td class="px-4 py-3">
                                            <span class="font-medium text-gray-900 dark:text-gray-100">{{ $categoria->nombre }}</span>
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $categoria->users_count > 0 ? 'bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300' : 'bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-300' }}">
                                                {{ $categoria->users_count }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                            <div class="flex items-center justify-center space-x-2">
                                                <button
                                                    @click="openCategoriaModal = true; editingCategoriaId = {{ $categoria->id }}; categoriaForm = {
                                                        nombre: '{{ addslashes($categoria->nombre) }}'
                                                    };"
                                                    class="inline-flex items-center px-3 py-1.5 bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-300 rounded-lg hover:bg-yellow-200 dark:hover:bg-yellow-900/50 transition-colors">
                                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                    </svg>
                                                    Editar
                                                </button>
                                                <button @click="deleteCategoria({{ $categoria->id }})"
                                                    class="inline-flex items-center px-3 py-1.5 bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300 rounded-lg hover:bg-red-200 dark:hover:bg-red-900/50 transition-colors {{ $categoria->users_count > 0 ? 'opacity-50 cursor-not-allowed' : '' }}"
                                                    {{ $categoria->users_count > 0 ? 'disabled' : '' }}
                                                    title="{{ $categoria->users_count > 0 ? 'Tiene empleados asignados' : 'Eliminar categoria' }}">
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
                                        <td colspan="4" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                                            No hay categorias registradas
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Modal Crear/Editar Categoria -->
            <div x-show="openCategoriaModal" x-cloak
                class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
                <div @click.away="openCategoriaModal = false"
                    class="bg-white dark:bg-gray-800 w-full max-w-md rounded-lg shadow-lg p-6">

                    <h3 class="text-xl font-semibold text-gray-800 dark:text-gray-100 mb-4" x-text="editingCategoriaId ? 'Editar Categoria' : 'Nueva Categoria'"></h3>

                    <form @submit.prevent="submitCategoria()">
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nombre de la categoria *</label>
                                <input type="text" x-model="categoriaForm.nombre" required
                                    class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-lg px-3 py-2 focus:ring-purple-500 focus:border-purple-500"
                                    placeholder="Ej: Oficial 1a, Peon, Encargado...">
                            </div>
                        </div>

                        <div class="flex justify-end space-x-3 mt-6">
                            <button type="button" @click="openCategoriaModal = false"
                                class="px-4 py-2 text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200 transition-colors">
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

            <!-- ===== SECCION PLANTILLAS DE TURNOS ===== -->
            <div class="mb-8">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-300">Plantillas de Turnos</h3>
                    <button @click="openAgrupacionModal = true; editingAgrupacionId = null; resetAgrupacionForm();"
                        class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Nueva Plantilla
                    </button>
                </div>

                <div class="bg-white dark:bg-gray-800 shadow-lg rounded-lg overflow-hidden border border-gray-200 dark:border-gray-700">
                    <div class="overflow-x-auto">
                        <table class="w-full table-auto border-collapse">
                            <thead class="bg-indigo-500 dark:bg-indigo-600 text-white">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase">Nombre</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase">Dias</th>
                                    <th class="px-4 py-3 text-center text-xs font-semibold uppercase">Estado</th>
                                    <th class="px-4 py-3 text-center text-xs font-semibold uppercase">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                @forelse ($agrupaciones as $agrupacion)
                                    <tr class="hover:bg-indigo-50 dark:hover:bg-gray-700 transition-colors duration-150 {{ $agrupacion->activo ? '' : 'opacity-60' }}">
                                        <td class="px-4 py-3">
                                            <span class="font-medium text-gray-900 dark:text-gray-100">{{ $agrupacion->nombre }}</span>
                                            @if($agrupacion->descripcion)
                                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $agrupacion->descripcion }}</p>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3">
                                            <div class="flex flex-wrap gap-1">
                                                @php
                                                    $diasAbrev = ['D', 'L', 'M', 'X', 'J', 'V', 'S'];
                                                    $diasConfig = $agrupacion->dias->keyBy('dia_semana');
                                                @endphp
                                                @foreach([1, 2, 3, 4, 5, 6, 0] as $dia)
                                                    @php
                                                        $diaConfig = $diasConfig->get($dia);
                                                        $tieneTurno = $diaConfig && $diaConfig->turno_id;
                                                        $turnoColor = $tieneTurno && $diaConfig->turno ? $diaConfig->turno->color : null;
                                                    @endphp
                                                    <span class="inline-flex items-center justify-center w-6 h-6 text-xs font-medium rounded
                                                        {{ $tieneTurno ? 'text-white' : 'bg-gray-200 dark:bg-gray-600 text-gray-500 dark:text-gray-400' }}"
                                                        @if($tieneTurno && $turnoColor) style="background-color: {{ $turnoColor }}" @endif
                                                        @if($tieneTurno && !$turnoColor) class="bg-indigo-500 text-white" @endif
                                                        title="{{ $tieneTurno && $diaConfig->turno ? $diaConfig->turno->nombre : 'No trabaja' }}">
                                                        {{ $diasAbrev[$dia] }}
                                                    </span>
                                                @endforeach
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                            <button @click="toggleAgrupacion({{ $agrupacion->id }})"
                                                class="px-2 py-1 rounded-full text-xs font-semibold {{ $agrupacion->activo ? 'bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300' : 'bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-300' }}">
                                                {{ $agrupacion->activo ? 'Activo' : 'Inactivo' }}
                                            </button>
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                            <div class="flex items-center justify-center space-x-2">
                                                <button
                                                    @click="editAgrupacion({{ $agrupacion->toJson() }})"
                                                    class="inline-flex items-center px-3 py-1.5 bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-300 rounded-lg hover:bg-yellow-200 dark:hover:bg-yellow-900/50 transition-colors">
                                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                    </svg>
                                                    Editar
                                                </button>
                                                <button @click="deleteAgrupacion({{ $agrupacion->id }})"
                                                    class="inline-flex items-center px-3 py-1.5 bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300 rounded-lg hover:bg-red-200 dark:hover:bg-red-900/50 transition-colors"
                                                    title="Eliminar plantilla">
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
                                        <td colspan="5" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                                            No hay plantillas de turnos registradas
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Modal Crear/Editar Plantilla de Turno -->
            <div x-show="openAgrupacionModal" x-cloak
                class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
                <div @click.away="openAgrupacionModal = false"
                    class="bg-white dark:bg-gray-800 w-full max-w-4xl rounded-lg shadow-lg p-6 max-h-[90vh] overflow-y-auto">

                    <h3 class="text-xl font-semibold text-gray-800 dark:text-gray-100 mb-4" x-text="editingAgrupacionId ? 'Editar Plantilla de Turno' : 'Nueva Plantilla de Turno'"></h3>

                    <form @submit.prevent="submitAgrupacion()">
                        <div class="space-y-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nombre de la plantilla *</label>
                                    <input type="text" x-model="agrupacionForm.nombre" required
                                        class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-lg px-3 py-2 focus:ring-indigo-500 focus:border-indigo-500"
                                        placeholder="Ej: TURNO 1, Jornada Completa...">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Descripcion</label>
                                    <input type="text" x-model="agrupacionForm.descripcion"
                                        class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-lg px-3 py-2 focus:ring-indigo-500 focus:border-indigo-500"
                                        placeholder="Descripcion opcional...">
                                </div>
                            </div>

                            <div class="border-t dark:border-gray-700 pt-5">
                                <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Turno por dia de la semana</p>
                                <p class="text-xs text-gray-400 mb-5">Deja vacio los dias que no se trabaja</p>

                                <div class="bg-gray-50 dark:bg-gray-700 rounded-xl p-4">
                                    <div class="grid grid-cols-7 gap-3">
                                        @php
                                            $diasAbrevModal = [1 => 'L', 2 => 'M', 3 => 'X', 4 => 'J', 5 => 'V', 6 => 'S', 0 => 'D'];
                                            $diasNombres = [1 => 'Lunes', 2 => 'Martes', 3 => 'Miercoles', 4 => 'Jueves', 5 => 'Viernes', 6 => 'Sabado', 0 => 'Domingo'];
                                        @endphp
                                        @foreach([1, 2, 3, 4, 5, 6, 0] as $dia)
                                            <div class="text-center">
                                                <div class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-indigo-100 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 font-semibold text-sm mb-2">
                                                    {{ $diasAbrevModal[$dia] }}
                                                </div>
                                                <select x-model="agrupacionForm.dias[{{ $dia }}]"
                                                    class="w-full bg-white dark:bg-gray-600 border-0 rounded-lg px-2 py-2 text-sm text-gray-700 dark:text-gray-200 shadow-sm ring-1 ring-inset ring-gray-200 dark:ring-gray-500 focus:ring-2 focus:ring-indigo-500 cursor-pointer">
                                                    <option value="">-</option>
                                                    @foreach($turnos as $turno)
                                                        @if($turno->activo)
                                                            <option value="{{ $turno->id }}">{{ $turno->nombre }}</option>
                                                        @endif
                                                    @endforeach
                                                </select>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-center space-x-2 pt-2">
                                <input type="checkbox" x-model="agrupacionForm.activo" id="agrupacionActivo"
                                    class="rounded border-gray-300 dark:border-gray-600 text-indigo-600 focus:ring-indigo-500">
                                <label for="agrupacionActivo" class="text-sm text-gray-700 dark:text-gray-300">Plantilla activa</label>
                            </div>
                        </div>

                        <div class="flex justify-end space-x-3 mt-6">
                            <button type="button" @click="openAgrupacionModal = false"
                                class="px-4 py-2 text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200 transition-colors">
                                Cancelar
                            </button>
                            <button type="submit" :disabled="isAgrupacionSubmitting"
                                class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 disabled:opacity-50 transition-colors">
                                <span x-show="!isAgrupacionSubmitting" x-text="editingAgrupacionId ? 'Actualizar' : 'Guardar'"></span>
                                <span x-show="isAgrupacionSubmitting">Guardando...</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- ===== TURNOS Y FESTIVOS (lado a lado) ===== -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- TABLA TURNOS -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 flex items-center">
                            Turnos
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
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Nombre</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Horario</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Estado</th>
                                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                @forelse($turnos as $turno)
                                    <tr class="{{ $turno->activo ? '' : 'bg-gray-50 dark:bg-gray-900 opacity-60' }}">
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <div class="flex items-center">
                                                @if($turno->color)
                                                    <span class="w-3 h-3 rounded-full mr-2" style="background-color: {{ $turno->color }}"></span>
                                                @endif
                                                <span class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $turno->nombre }}</span>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">
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
                                                    class="px-2 py-1 rounded-full text-xs font-semibold {{ $turno->activo ? 'bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300' : 'bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-300' }}">
                                                    {{ $turno->activo ? 'Activo' : 'Inactivo' }}
                                                </button>
                                            </form>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-center text-sm">
                                            <a href="{{ route('turnos.edit', $turno) }}" wire:navigate class="text-blue-600 dark:text-blue-400 hover:text-blue-900 dark:hover:text-blue-300 mr-2">Editar</a>
                                            <form action="{{ route('turnos.destroy', $turno) }}" method="POST" class="inline"
                                                onsubmit="return confirm('¿Eliminar este turno?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300">Eliminar</button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                                            No hay turnos configurados
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- TABLA FESTIVOS -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden" x-data="{
                    openFestivoModal: false,
                    editingFestivoId: null,
                    festivoTitulo: '',
                    festivoFecha: '',
                    isFestivoSubmitting: false
                }">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 flex items-center">
                            Festivos ({{ $anioActual }})
                        </h3>
                        <button @click="openFestivoModal = true; editingFestivoId = null; festivoTitulo = ''; festivoFecha = '';"
                            class="inline-flex items-center px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                            </svg>
                            Nuevo
                        </button>
                    </div>
                    <div class="overflow-x-auto max-h-96 overflow-y-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700 sticky top-0">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Fecha</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Titulo</th>
                                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                @forelse($festivos as $festivo)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <span class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $festivo->fecha->format('d/m/Y') }}</span>
                                            <span class="text-gray-400 text-xs ml-1">({{ $festivo->fecha->translatedFormat('D') }})</span>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                                            {{ $festivo->titulo }}
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-center text-sm">
                                            <button @click="openFestivoModal = true; editingFestivoId = {{ $festivo->id }}; festivoTitulo = '{{ addslashes($festivo->titulo) }}'; festivoFecha = '{{ $festivo->fecha->format('Y-m-d') }}';"
                                                class="text-blue-600 dark:text-blue-400 hover:text-blue-900 dark:hover:text-blue-300 mr-2">Editar</button>
                                            <form action="{{ route('festivos.destroy', $festivo) }}" method="POST" class="inline"
                                                onsubmit="return confirm('¿Eliminar este festivo?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300">Eliminar</button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                                            No hay festivos registrados
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Modal Crear/Editar Festivo -->
                    <div x-show="openFestivoModal" x-cloak
                        class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
                        <div @click.away="openFestivoModal = false"
                            class="bg-white dark:bg-gray-800 w-full max-w-md rounded-lg shadow-lg p-6 space-y-4">
                            <h3 class="text-xl font-semibold text-gray-800 dark:text-gray-100" x-text="editingFestivoId ? 'Editar Festivo' : 'Nuevo Festivo'"></h3>
                            <form method="POST" :action="editingFestivoId ? '{{ url('festivos') }}/' + editingFestivoId : '{{ route('festivos.store') }}'"
                                @submit="isFestivoSubmitting = true">
                                @csrf
                                <input type="hidden" name="_method" :value="editingFestivoId ? 'PUT' : 'POST'">
                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Fecha</label>
                                    <input type="date" name="fecha" x-model="festivoFecha" required
                                        class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-lg px-3 py-2 focus:ring-blue-500 focus:border-blue-500">
                                </div>
                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Titulo</label>
                                    <input type="text" name="titulo" x-model="festivoTitulo" placeholder="Ej: Navidad"
                                        class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-lg px-3 py-2 focus:ring-blue-500 focus:border-blue-500">
                                </div>
                                <div class="flex justify-end space-x-3 mt-6">
                                    <button type="button" @click="openFestivoModal = false"
                                        class="px-4 py-2 text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200 transition-colors">
                                        Cancelar
                                    </button>
                                    <button type="submit" :disabled="isFestivoSubmitting"
                                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 transition-colors">
                                        <span x-show="!isFestivoSubmitting" x-text="editingFestivoId ? 'Actualizar' : 'Guardar'"></span>
                                        <span x-show="isFestivoSubmitting">Guardando...</span>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
