<x-app-layout>
    <x-slot name="title">Permisos y configuración</x-slot>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Permisos y configuración') }}
        </h2>
    </x-slot>

    <div class="px-2 sm:px-6 py-4 relative" x-data="{
        openModal: false,
        openModalSecciones: false,
        openNuevoDepartamentoModal: false,
        openNuevaSeccionModal: false,
        departamentoId: null,
        usuariosMarcados: []
    }">

        <!-- Success/Error Messages -->
        @if (session('success'))
            <div class="mb-4 bg-green-50 border-l-4 border-green-500 p-4 rounded-lg shadow-sm">
                <div class="flex items-center">
                    <svg class="w-5 h-5 text-green-500 mr-3" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                            d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                            clip-rule="evenodd" />
                    </svg>
                    <p class="text-green-700 font-medium">{{ session('success') }}</p>
                </div>
            </div>
        @endif

        @if (session('error'))
            <div class="mb-4 bg-red-50 border-l-4 border-red-500 p-4 rounded-lg shadow-sm">
                <div class="flex items-center">
                    <svg class="w-5 h-5 text-red-500 mr-3" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                            d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                            clip-rule="evenodd" />
                    </svg>
                    <p class="text-red-700 font-medium">{{ session('error') }}</p>
                </div>
            </div>
        @endif

        <!-- Tabla Responsive -->
        <div class="bg-white shadow-lg rounded-lg overflow-hidden border border-gray-200">
            <!-- Vista Desktop -->
            <div class="hidden md:block overflow-x-auto">
                <table class="w-full table-auto border-collapse">
                    <thead class="bg-blue-500 text-white">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider">
                                Departamento
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider">
                                Descripción
                            </th>
                            <th class="px-6 py-3 text-center text-xs font-semibold uppercase tracking-wider">
                                Usuarios
                            </th>
                            <th class="px-6 py-3 text-center text-xs font-semibold uppercase tracking-wider">
                                Secciones
                            </th>
                            <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider">
                                Acciones
                            </th>
                        </tr>
                    </thead>
                        @forelse ($departamentos as $departamento)
                    <tbody x-data="{ isExpanded: false }" class="bg-white divide-y divide-gray-200">
                        <tr class="hover:bg-blue-50 transition-colors duration-150">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <button @click="isExpanded = !isExpanded"
                                    class="flex items-center gap-2 text-left font-semibold text-blue-600 hover:text-blue-800 transition-colors">
                                    <svg x-show="!isExpanded" class="w-4 h-4" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 5l7 7-7 7" />
                                    </svg>
                                    <svg x-show="isExpanded" class="w-4 h-4" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19 9l-7 7-7-7" />
                                    </svg>
                                    {{ $departamento->nombre }}
                                </button>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-gray-700">{{ $departamento->descripcion ?? '—' }}</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <span
                                    class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium {{ $departamento->usuarios->count() > 0 ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-600' }}">
                                    <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path
                                            d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z" />
                                    </svg>
                                    {{ $departamento->usuarios->count() }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <span
                                    class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium {{ $departamento->secciones->count() > 0 ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-600' }}">
                                    <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path
                                            d="M2 6a2 2 0 012-2h5l2 2h5a2 2 0 012 2v6a2 2 0 01-2 2H4a2 2 0 01-2-2V6z" />
                                    </svg>
                                    {{ $departamento->secciones->count() }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <div class="flex justify-end gap-2">
                                    <button
                                        @click="openModalSecciones = true; departamentoId = {{ $departamento->id }};"
                                        class="inline-flex items-center px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-medium transition-colors shadow-sm hover:shadow">
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                        Secciones
                                    </button>
                                    <button
                                        @click="openModal = true; departamentoId = {{ $departamento->id }}; usuariosMarcados = {{ $departamento->usuarios->pluck('id') }}"
                                        class="inline-flex items-center px-3 py-1.5 bg-green-600 hover:bg-green-700 text-white rounded-lg text-xs font-medium transition-colors shadow-sm hover:shadow">
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                        </svg>
                                        Usuarios
                                    </button>
                                    <a href="{{ route('departamentos.edit', $departamento) }}" wire:navigate
                                        class="inline-flex items-center px-3 py-1.5 bg-yellow-400 hover:bg-yellow-500 text-white rounded-lg text-xs font-medium transition-colors shadow-sm hover:shadow">
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                        Editar
                                    </a>
                                    <form action="{{ route('departamentos.destroy', $departamento) }}" method="POST"
                                        class="inline-block"
                                        onsubmit="return confirm('¿Está seguro de eliminar este departamento? Esta acción no se puede deshacer.')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                            class="inline-flex items-center px-3 py-1.5 bg-red-600 hover:bg-red-700 text-white rounded-lg text-xs font-medium transition-colors shadow-sm hover:shadow">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                            Eliminar
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>

                        <tr x-show="isExpanded" class="bg-gray-50" x-cloak>
                            <td colspan="5" class="px-6 py-4">
                                @include('departamentos.partials.detalle-departamento', [
                                    'departamento' => $departamento,
                                ])
                            </td>
                        </tr>
                    </tbody>
                        @empty
                    <tbody>
                        <tr>
                            <td colspan="5" class="px-6 py-8 text-center">
                                <div class="flex flex-col items-center justify-center text-gray-500">
                                    <svg class="w-12 h-12 mb-3 text-gray-400" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                                    </svg>
                                    <p class="text-lg font-medium">No hay departamentos registrados</p>
                                    <p class="text-sm mt-1">Crea tu primer departamento para comenzar</p>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                        @endforelse
                </table>
            </div>

            <!-- Vista Mobile -->
            <div class="md:hidden space-y-3 p-3">
                @forelse ($departamentos as $departamento)
                    <div class="border-2 border-gray-200 rounded-xl shadow-md bg-white hover:shadow-lg transition-shadow duration-200"
                        x-data="{ isExpanded: false }">
                        <!-- Cabecera del card -->
                        <div class="p-4 bg-blue-50 rounded-t-xl">
                            <button @click="isExpanded = !isExpanded" class="w-full text-left">
                                <div class="flex items-start justify-between gap-2">
                                    <div class="flex-1">
                                        <h3 class="font-bold text-blue-700 text-lg flex items-center gap-2">
                                            <svg x-show="!isExpanded" class="w-5 h-5" fill="none"
                                                stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M9 5l7 7-7 7" />
                                            </svg>
                                            <svg x-show="isExpanded" class="w-5 h-5" fill="none"
                                                stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M19 9l-7 7-7-7" />
                                            </svg>
                                            {{ $departamento->nombre }}
                                        </h3>
                                        <p class="text-sm text-gray-600 mt-1">
                                            {{ $departamento->descripcion ?? 'Sin descripción' }}</p>
                                    </div>
                                </div>
                                <div class="flex gap-3 mt-3">
                                    <span
                                        class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold {{ $departamento->usuarios->count() > 0 ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' }}">
                                        <svg class="w-3.5 h-3.5 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path
                                                d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z" />
                                        </svg>
                                        {{ $departamento->usuarios->count() }} usuarios
                                    </span>
                                    <span
                                        class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold {{ $departamento->secciones->count() > 0 ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-600' }}">
                                        <svg class="w-3.5 h-3.5 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path
                                                d="M2 6a2 2 0 012-2h5l2 2h5a2 2 0 012 2v6a2 2 0 01-2 2H4a2 2 0 01-2-2V6z" />
                                        </svg>
                                        {{ $departamento->secciones->count() }} secciones
                                    </span>
                                </div>
                            </button>
                        </div>

                        <!-- Botones de acción -->
                        <div class="p-3 space-y-2 bg-white">
                            <div class="grid grid-cols-2 gap-2">
                                <button @click="openModalSecciones = true; departamentoId = {{ $departamento->id }};"
                                    class="flex items-center justify-center gap-1.5 bg-indigo-600 hover:bg-indigo-700 text-white py-2.5 px-3 rounded-lg text-xs font-semibold transition-colors shadow-sm">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    Secciones
                                </button>
                                <button
                                    @click="openModal = true; departamentoId = {{ $departamento->id }}; usuariosMarcados = {{ $departamento->usuarios->pluck('id') }}"
                                    class="flex items-center justify-center gap-1.5 bg-green-600 hover:bg-green-700 text-white py-2.5 px-3 rounded-lg text-xs font-semibold transition-colors shadow-sm">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                    </svg>
                                    Usuarios
                                </button>
                            </div>
                            <div class="grid grid-cols-2 gap-2">
                                <a href="{{ route('departamentos.edit', $departamento) }}" wire:navigate
                                    class="flex items-center justify-center gap-1.5 bg-yellow-400 hover:bg-yellow-500 text-white py-2.5 px-3 rounded-lg text-xs font-semibold transition-colors shadow-sm">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                    Editar
                                </a>
                                <form action="{{ route('departamentos.destroy', $departamento) }}" method="POST"
                                    onsubmit="return confirm('¿Está seguro de eliminar este departamento?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                        class="w-full flex items-center justify-center gap-1.5 bg-red-600 hover:bg-red-700 text-white py-2.5 px-3 rounded-lg text-xs font-semibold transition-colors shadow-sm">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                        Eliminar
                                    </button>
                                </form>
                            </div>
                        </div>

                        <!-- Detalles expandibles -->
                        <div x-show="isExpanded" x-transition class="border-t bg-gray-50 p-4 space-y-4 rounded-b-xl">
                            @include('departamentos.partials.detalle-departamento', [
                                'departamento' => $departamento,
                            ])
                        </div>
                    </div>
                @empty
                    <div class="text-center text-gray-500 py-12">
                        <svg class="w-16 h-16 mx-auto mb-4 text-gray-400" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                        </svg>
                        <p class="text-lg font-medium">No hay departamentos</p>
                        <p class="text-sm mt-1">Crea tu primer departamento</p>
                    </div>
                @endforelse
            </div>
        </div>

        <!-- Tabla resumen de todos los departamentos -->
        <!-- Botones para departamentos -->
        <div class="mt-12 flex flex-col sm:flex-row justify-start items-start sm:items-center mb-4 gap-2 sm:gap-4">
            <h3 class="text-lg font-semibold text-gray-700">Resumen Departamentos</h3>
            <button @click="openNuevoDepartamentoModal = true"
                class="w-full sm:w-auto bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded shadow">
                + Nuevo Departamento
            </button>
        </div>

        <div class="w-full mt-4 overflow-x-auto bg-white shadow-lg rounded-lg">
            <table class="w-full min-w-[600px] border border-gray-300 rounded-lg" id="tablaDepartamentos">
                <thead class="bg-blue-500 text-white">
                    <tr class="text-center text-xs uppercase">
                        <th class="px-4 py-2 text-left">Nombre</th>
                        <th class="px-4 py-2 text-left">Descripción</th>
                        <th class="px-4 py-2 text-left">Usuarios asignados</th>
                        <th class="px-4 py-2 text-left">Secciones visibles</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($departamentos as $dep)
                        <tr x-data="{
                            editando: false,
                            departamento: @js($dep),
                            original: JSON.parse(JSON.stringify(@js($dep)))
                        }" @dblclick="editando = true"
                            @keydown.enter.stop.prevent="guardarDepartamento(departamento); editando = false"
                            @keydown.escape="departamento = JSON.parse(JSON.stringify(original)); editando = false"
                            :class="{ 'bg-yellow-100': editando }"
                            class="border-t cursor-pointer hover:bg-blue-50 focus:outline-none" tabindex="0">


                            <!-- Nombre -->
                            <td class="px-4 py-2 border">
                                <template x-if="!editando">
                                    <span x-text="departamento.nombre"></span>
                                </template>
                                <input x-show="editando" type="text" x-model="departamento.nombre"
                                    class="form-control form-control-sm">
                            </td>

                            <!-- Descripción -->
                            <td class="px-4 py-2 border">
                                <template x-if="!editando">
                                    <span x-text="departamento.descripcion ?? '—'"></span>
                                </template>
                                <input x-show="editando" type="text" x-model="departamento.descripcion"
                                    class="form-control form-control-sm">
                            </td>

                            <!-- Usuarios asignados -->
                            <td class="px-4 py-2 border text-center">
                                {{ $dep->usuarios->count() }} usuario{{ $dep->usuarios->count() === 1 ? '' : 's' }}
                            </td>

                            <!-- Secciones visibles -->
                            <td class="px-4 py-2 border text-center">
                                {{ $dep->secciones->count() }} sección{{ $dep->secciones->count() === 1 ? '' : 'es' }}
                            </td>

                        </tr>
                    @endforeach
                </tbody>

            </table>
        </div>
        <script>
            function actualizarPermisos(departamentoId, userId, tipo, valor) {
                fetch(`/departamentos/${departamentoId}/permisos`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify({
                            user_id: userId,
                            accion: tipo,
                            valor: valor
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (!data.success) {
                            console.error('Error al guardar permisos:', data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error de red:', error);
                    });
            }

            function guardarDepartamento(departamento) {
                fetch(`{{ url('/departamentos') }}/${departamento.id}`, {
                        method: 'PUT',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify({
                            nombre: departamento.nombre,
                            descripcion: departamento.descripcion
                        })
                    })
                    .then(async response => {
                        const contentType = response.headers.get('content-type');
                        let data = {};

                        if (contentType && contentType.includes('application/json')) {
                            data = await response.json();
                        } else {
                            const text = await response.text();
                            throw new Error("El servidor devolvió una respuesta inesperada: " + text.slice(0, 100));
                        }

                        if (response.ok && data.success) {
                            // Se actualizó correctamente
                        } else {
                            let errorMsg = data.message || "Ha ocurrido un error inesperado.";
                            if (data.errors) {
                                errorMsg = Object.values(data.errors).flat().join("<br>");
                            }

                            Swal.fire({
                                icon: "error",
                                title: "Error al actualizar",
                                html: errorMsg,
                                confirmButtonText: "OK",
                                showCancelButton: true,
                                cancelButtonText: "Reportar Error"
                            }).then((result) => {
                                if (result.dismiss === Swal.DismissReason.cancel) {
                                    notificarProgramador(errorMsg);
                                }
                            });
                        }
                    })
                    .catch(error => {
                        Swal.fire({
                            icon: "error",
                            title: "Error de conexión",
                            text: error.message || "No se pudo actualizar el departamento.",
                            confirmButtonText: "OK"
                        });
                    });
            }
        </script>

        <!-- Tabla resumen de todas las secciones -->
        <!-- Botones para secciones -->
        <div class="mt-12 flex flex-col sm:flex-row justify-start items-start sm:items-center mb-4 gap-2 sm:gap-4">
            <h3 class="text-lg font-semibold text-gray-700">Resumen Secciones</h3>
            <button @click="openNuevaSeccionModal = true"
                class="w-full sm:w-auto bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2 px-4 rounded shadow">
                + Nueva Sección
            </button>
        </div>

        <div class="w-full mt-4 overflow-x-auto bg-white shadow-lg rounded-lg">
            <table class="w-full min-w-[600px] border border-gray-300 rounded-lg">
                <thead class="bg-blue-500 text-white">
                    <tr class="text-center text-xs uppercase">
                        <th class="px-4 py-2 text-left">Nombre</th>
                        <th class="px-4 py-2 text-left">Ruta</th>
                        <th class="px-4 py-2 text-left">Icono</th>
                        <th class="px-4 py-2 text-left">Departamentos asociados</th>
                        <th class="px-4 py-2 text-left">Pagina Principal</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($todasLasSecciones as $sec)
                        <tr x-data="{
                            editando: false,
                            seccion: @js($sec),
                            original: JSON.parse(JSON.stringify(@js($sec)))
                        }" @dblclick="editando = true"
                            @keydown.enter.stop.prevent="guardarSeccion(seccion); editando = false"
                            @keydown.escape="seccion = JSON.parse(JSON.stringify(original)); editando = false"
                            :class="{ 'bg-yellow-100': editando }"
                            class="border-t cursor-pointer hover:bg-blue-50 focus:outline-none" tabindex="0">

                            <!-- Nombre -->
                            <td class="px-4 py-2 border">
                                <template x-if="!editando">
                                    <span x-text="seccion.nombre"></span>
                                </template>
                                <input x-show="editando" type="text" x-model="seccion.nombre"
                                    class="form-control form-control-sm">
                            </td>

                            <!-- Ruta -->
                            <td class="px-4 py-2 border">
                                <template x-if="!editando">
                                    <span x-text="seccion.ruta"></span>
                                </template>
                                <input x-show="editando" type="text" x-model="seccion.ruta"
                                    class="form-control form-control-sm">
                            </td>

                            <!-- Icono -->
                            <td class="px-4 py-2 border text-center">
                                <template x-if="!editando">
                                    <template x-if="seccion.icono">
                                        <img :src="'{{ asset('') }}' + seccion.icono" alt="Icono"
                                            class="h-6 mx-auto">
                                    </template>
                                    <template x-if="!seccion.icono">
                                        <span class="text-gray-400 italic">Sin icono</span>
                                    </template>
                                </template>
                                <input x-show="editando" type="text" x-model="seccion.icono"
                                    class="form-control form-control-sm">
                            </td>

                            <!-- Departamentos asociados -->
                            <td class="px-4 py-2 border text-center">
                                {{ $sec->departamentos->pluck('nombre')->join(', ') ?: 'Ninguno' }}
                            </td>

                            <!-- Mostrar en dashboard -->
                            <td class="px-4 py-2 border text-center">
                                <input type="checkbox" :checked="seccion.mostrar_en_dashboard"
                                    @change="seccion.mostrar_en_dashboard = $event.target.checked; toggleMostrarDashboard(seccion.id, $event.target.checked)"
                                    class="form-checkbox h-5 w-5 text-blue-600 cursor-pointer">
                            </td>



                        </tr>
                    @endforeach
                </tbody>

            </table>
        </div>
        <!-- ═══════════════════════════════════════════════════════════════════════════════
             CONFIGURACIÓN DEL DASHBOARD - Orden de secciones y acceso de operarios
        ═══════════════════════════════════════════════════════════════════════════════ -->
        <div class="mt-12 bg-white shadow-lg rounded-lg overflow-hidden border border-gray-200">
            <div class="bg-gradient-to-r from-orange-500 to-amber-500 px-6 py-4">
                <h3 class="text-xl font-bold text-white flex items-center gap-2">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    Configuración del Dashboard
                </h3>
                <p class="text-orange-100 text-sm mt-1">Configura las secciones visibles para los operarios
                </p>
            </div>

            <div class="p-6">
                <!-- Configuración de Secciones para Operarios -->
                @if ($departamentoOperarios)
                    <div class="border-t pt-6 mt-6">
                        <h4 class="text-lg font-semibold text-gray-700 mb-4 flex items-center gap-2">
                            <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                            Secciones visibles para Operarios
                            <span class="text-sm font-normal text-gray-500">(Departamento:
                                {{ $departamentoOperarios->nombre }})</span>
                        </h4>

                        <p class="text-sm text-gray-600 mb-4">
                            Las secciones marcadas serán visibles en el dashboard de los usuarios con rol "operario".
                            <a href="#"
                                @click.prevent="openModalSecciones = true; departamentoId = {{ $departamentoOperarios->id }}"
                                class="text-blue-600 hover:underline">Editar secciones</a>
                        </p>

                        <div class="flex flex-wrap gap-2">
                            @forelse ($departamentoOperarios->secciones as $seccion)
                                <span
                                    class="inline-flex items-center gap-1 px-3 py-1.5 bg-blue-100 text-blue-800 rounded-full text-sm font-medium">
                                    @if ($seccion->icono)
                                        <img src="{{ asset($seccion->icono) }}" alt="" class="w-4 h-4">
                                    @endif
                                    {{ $seccion->nombre }}
                                </span>
                            @empty
                                <span class="text-gray-500 italic">No hay secciones asignadas al departamento
                                    Operarios</span>
                            @endforelse
                        </div>
                    </div>
                @else
                    <div class="border-t pt-6 mt-6">
                        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                            <div class="flex items-start gap-3">
                                <svg class="w-5 h-5 text-yellow-500 mt-0.5" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                                <div>
                                    <h5 class="font-semibold text-yellow-800">Departamento "Operarios" no encontrado
                                    </h5>
                                    <p class="text-sm text-yellow-700 mt-1">
                                        Para configurar las secciones visibles para operarios, primero crea un
                                        departamento llamado
                                        exactamente "Operarios".
                                    </p>
                                    <button type="button" @click="openNuevoDepartamentoModal = true"
                                        class="mt-2 text-sm text-yellow-800 hover:text-yellow-900 font-medium underline">
                                        + Crear departamento Operarios
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <script>
            function toggleMostrarDashboard(seccionId, valor) {
                console.log('🔄 Actualizando sección', seccionId, 'a', valor);

                fetch(`{{ url('/secciones') }}/${seccionId}`, {
                        method: 'PUT',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify({
                            mostrar_en_dashboard: valor ? 1 : 0
                        })
                    })
                    .then(async response => {
                        console.log('📥 Response status:', response.status);
                        const contentType = response.headers.get('content-type');

                        if (contentType && contentType.includes('application/json')) {
                            const data = await response.json();
                            console.log('📦 Response data:', data);

                            if (data.success) {
                                console.log('✅ Dashboard actualizado correctamente');

                            } else {
                                throw new Error(data.message || "Error al actualizar");
                            }
                        } else {
                            const text = await response.text();
                            console.error('❌ Respuesta no JSON:', text);
                            throw new Error("El servidor devolvió una respuesta inesperada");
                        }
                    })
                    .catch(error => {
                        console.error('❌ Error completo:', error);
                        Swal.fire({
                            icon: "error",
                            title: "Error",
                            text: error.message || "No se pudo actualizar la sección.",
                            confirmButtonText: "OK"
                        });
                    });
            }

            function guardarSeccion(seccion) {
                fetch(`{{ url('/secciones') }}/${seccion.id}`, {
                        method: 'PUT',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify({
                            nombre: seccion.nombre,
                            ruta: seccion.ruta,
                            icono: seccion.icono,
                            mostrar_en_dashboard: seccion.mostrar_en_dashboard ? 1 : 0
                        })
                    })
                    .then(async response => {
                        const contentType = response.headers.get('content-type');
                        if (contentType && contentType.includes('application/json')) {
                            const data = await response.json();
                            if (!data.success) {
                                throw new Error(data.message || "Error al actualizar");
                            }
                        } else {
                            const text = await response.text();
                            throw new Error("El servidor devolvió una respuesta inesperada: " + text.slice(0, 100));
                        }
                    })
                    .catch(error => {
                        Swal.fire({
                            icon: "error",
                            title: "Error de conexión",
                            text: error.message || "No se pudo actualizar la sección.",
                            confirmButtonText: "OK"
                        });
                    });
            }
        </script>

        <!-- Modal Nueva Sección -->
        <div x-show="openNuevaSeccionModal" x-cloak
            class="fixed inset-0 bg-black bg-opacity-50 z-40 flex items-center justify-center p-4">
            <div x-show="openNuevaSeccionModal" x-transition
                class="bg-white p-4 sm:p-6 rounded-lg shadow-lg w-full max-w-md z-50 max-h-[90vh] overflow-y-auto">
                    <h3 class="text-lg font-semibold mb-4">Crear nueva sección</h3>

                    <form method="POST" action="{{ route('secciones.store') }}">
                        @csrf

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700">Nombre</label>
                            <input type="text" name="nombre" required
                                class="w-full p-2 border border-gray-300 rounded-lg">
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700">Ruta (route name)</label>
                            <input type="text" name="ruta" required
                                class="w-full p-2 border border-gray-300 rounded-lg"
                                placeholder="ej: productos.index">
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700">Ruta del icono</label>
                            <input type="text" name="icono" class="w-full p-2 border border-gray-300 rounded-lg"
                                placeholder="imagenes/iconos/nombre.png">
                        </div>

                        <div class="mt-4 flex justify-end space-x-2">
                            <button type="button" @click="openNuevaSeccionModal = false"
                                class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded">
                                Cancelar
                            </button>
                            <button type="submit"
                                class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded">
                                Crear
                            </button>
                        </div>
                    </form>
            </div>
        </div>

        <!-- Modal Asignar Usuarios -->
        <div x-show="openModal" x-cloak
            class="fixed inset-0 bg-black bg-opacity-60 z-50 flex items-center justify-center p-4 backdrop-blur-sm">
            <div x-show="openModal" x-transition
                class="bg-white rounded-2xl shadow-2xl w-full max-w-4xl max-h-[85vh] overflow-hidden flex flex-col">
                    <!-- Header del modal -->
                    <div
                        class="bg-gradient-to-r from-green-500 to-emerald-600 px-6 py-4 flex justify-between items-center">
                        <div class="flex items-center gap-3">
                            <div class="bg-white/20 p-2 rounded-lg">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-xl font-bold text-white">Asignar Usuarios</h3>
                                <p class="text-green-100 text-sm">Selecciona los usuarios para este departamento</p>
                            </div>
                        </div>
                        <button @click="openModal = false"
                            class="text-white hover:bg-white/20 rounded-lg p-2 transition-colors">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- Body del modal -->
                    <form method="POST" :action="'/departamentos/' + departamentoId + '/asignar-usuarios'"
                        class="flex flex-col flex-1 overflow-hidden">
                        @csrf

                        <div class="flex-1 overflow-y-auto p-6">
                            <div class="mb-4">
                                <div class="flex items-center justify-between mb-3">
                                    <p class="text-sm text-gray-600">
                                        <span class="font-semibold">{{ count($usuariosOficina) }}</span> usuarios
                                        disponibles
                                    </p>
                                    <button type="button"
                                        @click="$el.closest('form').querySelectorAll('input[type=checkbox]').forEach(cb => cb.checked = !cb.checked)"
                                        class="text-sm text-blue-600 hover:text-blue-700 font-medium">
                                        Invertir selección
                                    </button>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                @forelse ($usuariosOficina as $usuario)
                                    <label
                                        class="flex items-start space-x-3 p-4 border-2 border-gray-200 rounded-xl hover:border-green-400 hover:bg-green-50 transition-all cursor-pointer group">
                                        <input type="checkbox" name="usuarios[]" :value="'{{ $usuario->id }}'"
                                            :checked="usuariosMarcados.includes({{ $usuario->id }})"
                                            class="mt-1 w-5 h-5 rounded border-gray-300 text-green-600 focus:ring-green-500 focus:ring-2 cursor-pointer">
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-center gap-2">
                                                <div
                                                    class="w-8 h-8 rounded-full bg-gradient-to-br from-green-400 to-emerald-500 flex items-center justify-center text-white font-semibold text-sm">
                                                    {{ strtoupper(substr($usuario->nombre_completo, 0, 1)) }}
                                                </div>
                                                <div class="flex-1 min-w-0">
                                                    <p
                                                        class="font-semibold text-gray-900 truncate group-hover:text-green-700 transition-colors">
                                                        {{ $usuario->nombre_completo }}
                                                    </p>
                                                    <p class="text-sm text-gray-500 truncate">{{ $usuario->email }}
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    </label>
                                @empty
                                    <div class="col-span-2 text-center py-12">
                                        <svg class="w-16 h-16 mx-auto mb-4 text-gray-300" fill="none"
                                            stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                        </svg>
                                        <p class="text-gray-500 font-medium">No hay usuarios con rol oficina</p>
                                    </div>
                                @endforelse
                            </div>
                        </div>

                        <!-- Footer del modal -->
                        <div class="border-t bg-gray-50 px-6 py-4 flex justify-between items-center gap-3">
                            <p class="text-sm text-gray-600">
                                <span
                                    x-text="$el.closest('form').querySelectorAll('input[type=checkbox]:checked').length"></span>
                                seleccionado(s)
                            </p>
                            <div class="flex gap-3">
                                <button type="button" @click="openModal = false"
                                    class="px-5 py-2.5 bg-gray-200 hover:bg-gray-300 text-gray-700 font-semibold rounded-lg transition-colors">
                                    Cancelar
                                </button>
                                <button type="submit"
                                    class="px-5 py-2.5 bg-gradient-to-r from-green-500 to-emerald-600 hover:from-green-600 hover:to-emerald-700 text-white font-semibold rounded-lg shadow-lg hover:shadow-xl transition-all">
                                    <span class="flex items-center gap-2">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M5 13l4 4L19 7" />
                                        </svg>
                                        Asignar Usuarios
                                    </span>
                                </button>
                            </div>
                        </div>
                    </form>
            </div>
        </div>

        <!-- Modal Nuevo Departamento -->
        <div x-show="openNuevoDepartamentoModal" x-cloak
            class="fixed inset-0 bg-black bg-opacity-50 z-40 flex items-center justify-center p-4">
            <div x-show="openNuevoDepartamentoModal" x-transition
                class="bg-white p-4 sm:p-6 rounded-lg shadow-lg w-full max-w-md z-50 max-h-[90vh] overflow-y-auto">
                <h3 class="text-lg font-semibold mb-4">Crear nuevo departamento</h3>

                    <form method="POST" action="{{ route('departamentos.store') }}">
                        @csrf

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700">Nombre</label>
                            <input type="text" name="nombre" required
                                class="w-full p-2 border border-gray-300 rounded-lg">
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700">Descripción</label>
                            <textarea name="descripcion" rows="3" class="w-full p-2 border border-gray-300 rounded-lg"></textarea>
                        </div>

                        <div class="mt-4 flex justify-end space-x-2">
                            <button type="button" @click="openNuevoDepartamentoModal = false"
                                class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded">
                                Cancelar
                            </button>
                            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded">
                                Crear
                            </button>
                        </div>
                </form>
            </div>
        </div>

        <!-- Modal Asignar Secciones -->
        <div x-show="openModalSecciones" x-cloak
            class="fixed inset-0 bg-black bg-opacity-60 z-50 flex items-center justify-center p-4 backdrop-blur-sm">
            <div x-show="openModalSecciones" x-transition
                class="bg-white rounded-2xl shadow-2xl w-full max-w-5xl max-h-[85vh] overflow-hidden flex flex-col">
                    <!-- Header del modal -->
                    <div
                        class="bg-gradient-to-r from-indigo-500 to-purple-600 px-6 py-4 flex justify-between items-center">
                        <div class="flex items-center gap-3">
                            <div class="bg-white/20 p-2 rounded-lg">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-xl font-bold text-white">Asignar Secciones</h3>
                                <p class="text-indigo-100 text-sm">Selecciona las secciones visibles para este
                                    departamento</p>
                            </div>
                        </div>
                        <button @click="openModalSecciones = false"
                            class="text-white hover:bg-white/20 rounded-lg p-2 transition-colors">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- Body del modal -->
                    <form method="POST" :action="'/departamentos/' + departamentoId + '/asignar-secciones'"
                        class="flex flex-col flex-1 overflow-hidden">
                        @csrf

                        <div class="flex-1 overflow-y-auto p-6">
                            <div class="mb-4">
                                <div class="flex items-center justify-between mb-3">
                                    <p class="text-sm text-gray-600">
                                        <span class="font-semibold">{{ count($todasLasSecciones) }}</span> secciones
                                        disponibles
                                    </p>
                                    <button type="button"
                                        @click="$el.closest('form').querySelectorAll('input[type=checkbox]').forEach(cb => cb.checked = !cb.checked)"
                                        class="text-sm text-indigo-600 hover:text-indigo-700 font-medium">
                                        Invertir selección
                                    </button>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                                @forelse ($todasLasSecciones as $seccion)
                                    <label
                                        class="flex items-start space-x-3 p-4 border-2 border-gray-200 rounded-xl hover:border-indigo-400 hover:bg-indigo-50 transition-all cursor-pointer group">
                                        <input type="checkbox" name="secciones[]" :value="'{{ $seccion->id }}'"
                                            :checked="{{ $seccion->departamentos->pluck('id') }}.includes(departamentoId)"
                                            class="mt-1 w-5 h-5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 focus:ring-2 cursor-pointer">
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-start gap-2">
                                                @if ($seccion->icono)
                                                    <img src="{{ asset($seccion->icono) }}"
                                                        alt="{{ $seccion->nombre }}" class="w-8 h-8 rounded-lg">
                                                @else
                                                    <div
                                                        class="w-8 h-8 rounded-lg bg-gradient-to-br from-indigo-400 to-purple-500 flex items-center justify-center text-white font-semibold text-xs">
                                                        {{ strtoupper(substr($seccion->nombre, 0, 2)) }}
                                                    </div>
                                                @endif
                                                <div class="flex-1 min-w-0">
                                                    <p
                                                        class="font-semibold text-gray-900 truncate group-hover:text-indigo-700 transition-colors">
                                                        {{ $seccion->nombre }}
                                                    </p>
                                                    <p class="text-xs text-gray-500 truncate">{{ $seccion->ruta }}</p>
                                                    @if ($seccion->mostrar_en_dashboard)
                                                        <span
                                                            class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 mt-1">
                                                            <svg class="w-3 h-3 mr-1" fill="currentColor"
                                                                viewBox="0 0 20 20">
                                                                <path fill-rule="evenodd"
                                                                    d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                                                    clip-rule="evenodd" />
                                                            </svg>
                                                            En dashboard
                                                        </span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </label>
                                @empty
                                    <div class="col-span-3 text-center py-12">
                                        <svg class="w-16 h-16 mx-auto mb-4 text-gray-300" fill="none"
                                            stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                        <p class="text-gray-500 font-medium">No hay secciones registradas</p>
                                        <button type="button"
                                            @click="openModalSecciones = false; openNuevaSeccionModal = true"
                                            class="mt-3 text-indigo-600 hover:text-indigo-700 font-medium text-sm">
                                            + Crear primera sección
                                        </button>
                                    </div>
                                @endforelse
                            </div>
                        </div>

                        <!-- Footer del modal -->
                        <div class="border-t bg-gray-50 px-6 py-4 flex justify-between items-center gap-3">
                            <p class="text-sm text-gray-600">
                                <span
                                    x-text="$el.closest('form').querySelectorAll('input[type=checkbox]:checked').length"></span>
                                seleccionada(s)
                            </p>
                            <div class="flex gap-3">
                                <button type="button" @click="openModalSecciones = false"
                                    class="px-5 py-2.5 bg-gray-200 hover:bg-gray-300 text-gray-700 font-semibold rounded-lg transition-colors">
                                    Cancelar
                                </button>
                                <button type="submit"
                                    class="px-5 py-2.5 bg-gradient-to-r from-indigo-500 to-purple-600 hover:from-indigo-600 hover:to-purple-700 text-white font-semibold rounded-lg shadow-lg hover:shadow-xl transition-all">
                                    <span class="flex items-center gap-2">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M5 13l4 4L19 7" />
                                        </svg>
                                        Asignar Secciones
                                    </span>
                                </button>
                            </div>
                        </div>
                    </form>
            </div>
        </div>

    </div>
</x-app-layout>
