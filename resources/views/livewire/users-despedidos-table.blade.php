<div class="max-md:hidden">
    <style>
        [x-cloak] {
            display: none !important;
        }
    </style>

    <x-tabla.filtros-aplicados :filtros="$filtrosActivos" />

    <div class="mt-2 flex items-center justify-end gap-2 text-xs text-gray-600 dark:text-gray-300">
        <label for="despedidos-per-page" class="font-medium">Mostrar</label>
        <select id="despedidos-per-page" wire:model.live="perPage"
            class="rounded-md border border-gray-300 bg-white px-2 py-1 pr-7 text-xs dark:border-gray-600 dark:bg-gray-800">
            <option value="10">10</option>
            <option value="25">25</option>
            <option value="50">50</option>
            <option value="100">100</option>
        </select>
        <span>filas</span>
    </div>

    <div class="w-full max-w-full overflow-x-auto bg-white dark:bg-gray-800 shadow-lg rounded-lg mt-4">
        <table class="w-full border border-gray-200 dark:border-gray-700 rounded-lg">
            <thead class="bg-blue-500 text-white">
                <tr class="text-center text-xs uppercase">
                    <x-tabla.encabezado-ordenable campo="id" :sortActual="$sort" :orderActual="$order" texto="ID" />
                    <x-tabla.encabezado-ordenable campo="nombre_completo" :sortActual="$sort" :orderActual="$order" texto="Nombre" />
                    <x-tabla.encabezado-ordenable campo="nombre_completo" :sortActual="$sort" :orderActual="$order" texto="Primer Apellido" />
                    <x-tabla.encabezado-ordenable campo="nombre_completo" :sortActual="$sort" :orderActual="$order" texto="Segundo Apellido" />
                    <x-tabla.encabezado-ordenable campo="email" :sortActual="$sort" :orderActual="$order" texto="Email" />
                    <x-tabla.encabezado-ordenable campo="dni" :sortActual="$sort" :orderActual="$order" texto="DNI" />
                    <x-tabla.encabezado-ordenable campo="empresa" :sortActual="$sort" :orderActual="$order" texto="Empresa" />
                    <x-tabla.encabezado-ordenable campo="rol" :sortActual="$sort" :orderActual="$order" texto="Rol" />
                    <x-tabla.encabezado-ordenable campo="categoria" :sortActual="$sort" :orderActual="$order" texto="Categoría" />
                    <x-tabla.encabezado-ordenable campo="fecha_baja" :sortActual="$sort" :orderActual="$order" texto="Fecha Baja" />
                    <th class="p-2 border">Acciones</th>
                </tr>

                {{-- Filtros --}}
                <tr class="text-center text-xs uppercase">
                    <th class="p-1 border">
                        <input type="text" wire:model.live.debounce.300ms="user_id" placeholder="ID"
                            class="w-full text-xs px-2 py-1 border rounded text-blue-900 focus:border-blue-900 focus:ring-1 focus:ring-blue-900 focus:outline-none" />
                    </th>
                    <th class="p-1 border">
                        <input type="text" wire:model.live="filtro_name" placeholder="Nombre" autocomplete="off"
                            class="w-full text-xs px-2 py-1 border rounded text-blue-900 focus:border-blue-900 focus:ring-1 focus:ring-blue-900 focus:outline-none" />
                    </th>
                    <th class="p-1 border">
                        <input type="text" wire:model.live="filtro_primer_apellido" placeholder="Apellido 1" autocomplete="off"
                            class="w-full text-xs px-2 py-1 border rounded text-blue-900 focus:border-blue-900 focus:ring-1 focus:ring-blue-900 focus:outline-none" />
                    </th>
                    <th class="p-1 border">
                        <input type="text" wire:model.live="filtro_segundo_apellido" placeholder="Apellido 2" autocomplete="off"
                            class="w-full text-xs px-2 py-1 border rounded text-blue-900 focus:border-blue-900 focus:ring-1 focus:ring-blue-900 focus:outline-none" />
                    </th>
                    <th class="p-1 border">
                        <input type="text" wire:model.live.debounce.300ms="email" placeholder="Email"
                            class="w-full text-xs px-2 py-1 border rounded text-blue-900 focus:border-blue-900 focus:ring-1 focus:ring-blue-900 focus:outline-none" />
                    </th>
                    <th class="p-1 border">
                        <input type="text" wire:model.live.debounce.300ms="dni" placeholder="DNI"
                            class="w-full text-xs px-2 py-1 border rounded text-blue-900 focus:border-blue-900 focus:ring-1 focus:ring-blue-900 focus:outline-none" />
                    </th>
                    <th class="p-1 border">
                        <select wire:model.live="empresa_id"
                            class="w-full text-xs px-2 py-1 border rounded text-blue-900 focus:border-blue-900 focus:ring-1 focus:ring-blue-900 focus:outline-none">
                            <option value="">Todas</option>
                            @foreach($empresas as $empresa)
                            <option value="{{ $empresa->id }}">{{ $empresa->nombre }}</option>
                            @endforeach
                        </select>
                    </th>
                    <th class="p-1 border">
                        <select wire:model.live="rol"
                            class="w-full text-xs px-2 py-1 border rounded text-blue-900 focus:border-blue-900 focus:ring-1 focus:ring-blue-900 focus:outline-none">
                            <option value="">Todos</option>
                            @foreach($roles as $r)
                            <option value="{{ $r }}">{{ ucfirst($r) }}</option>
                            @endforeach
                        </select>
                    </th>
                    <th class="p-1 border">
                        <select wire:model.live="categoria_id"
                            class="w-full text-xs px-2 py-1 border rounded text-blue-900 focus:border-blue-900 focus:ring-1 focus:ring-blue-900 focus:outline-none">
                            <option value="">Todas</option>
                            @foreach($categorias as $categoria)
                            <option value="{{ $categoria->id }}">{{ $categoria->nombre }}</option>
                            @endforeach
                        </select>
                    </th>
                    <th class="p-1 border"></th>
                    <th class="p-1 border text-center align-middle">
                        <div class="flex justify-center gap-2 items-center h-full">
                            {{-- Botón reset --}}
                            <button type="button" wire:click="limpiarFiltros"
                                class="bg-yellow-500 hover:bg-yellow-600 text-white px-2 py-1 rounded text-xs flex items-center justify-center"
                                title="Restablecer filtros">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M4 4v5h.582M20 20v-5h-.581M4.582 9A7.5 7.5 0 0112 4.5a7.5 7.5 0 016.418 3.418M19.418 15A7.5 7.5 0 0112 19.5a7.5 7.5 0 01-6.418-3.418" />
                                </svg>
                            </button>
                        </div>
                    </th>
                </tr>
            </thead>

            <tbody class="text-gray-600 dark:text-gray-400 text-sm">
                @forelse ($registros as $user)
                <tr wire:key="despedido-{{ $user->id }}"
                    class="border-b border-gray-200 dark:border-gray-700 odd:bg-gray-100 dark:odd:bg-gray-700 even:bg-gray-50 dark:even:bg-gray-800 hover:bg-blue-50 dark:hover:bg-gray-700 text-xs uppercase transition-colors">
                    <td class="px-2 py-3 text-center border">{{ $user->id }}</td>
                    <td class="px-2 py-3 text-center border">{{ $user->name }}</td>
                    <td class="px-2 py-3 text-center border">{{ $user->primer_apellido }}</td>
                    <td class="px-2 py-3 text-center border">{{ $user->segundo_apellido ?? '-' }}</td>
                    <td class="px-2 py-3 text-center border">{{ $user->email }}</td>
                    <td class="px-2 py-3 text-center border">{{ $user->dni ?? '-' }}</td>
                    <td class="px-2 py-3 text-center border">{{ $user->empresa->nombre ?? 'Sin empresa' }}</td>
                    <td class="px-2 py-3 text-center border">{{ $user->rol }}</td>
                    <td class="px-2 py-3 text-center border">{{ $user->categoria->nombre ?? 'Sin asignar' }}</td>
                    <td class="px-2 py-3 text-center border">
                        {{ $user->fecha_baja ? \Carbon\Carbon::parse($user->fecha_baja)->format('d/m/Y') : '-' }}
                    </td>
                    <td class="px-2 py-3 text-center border">
                        <div class="flex items-center space-x-2 justify-center">
                            <x-tabla.boton-ver :href="route('users.show', $user->id)" target="_self" rel="noopener" />

                            <form action="{{ route('usuarios.readmitir', $user->id) }}" method="POST"
                                  id="form-readmitir-{{ $user->id }}">
                                @csrf
                                <button type="button"
                                    onclick="confirmarReadmision({{ $user->id }}, '{{ addslashes($user->name . ' ' . $user->primer_apellido) }}')"
                                    class="w-6 h-6 bg-green-100 text-green-600 rounded hover:bg-green-200 flex items-center justify-center"
                                    title="Readmitir trabajador">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                                    </svg>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="11" class="text-center py-4 text-gray-600 dark:text-gray-400">No hay trabajadores dados de baja.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <x-tabla.paginacion-livewire :paginador="$registros" />

    @push('scripts')
    <script>
        function confirmarReadmision(userId, nombre) {
            Swal.fire({
                title: '¿Readmitir a ' + nombre + '?',
                html: '<p>El trabajador volverá a estar <strong>activo</strong> en la app.</p>' +
                      '<p class="mt-2 text-sm text-yellow-600">⚠️ Los turnos futuros eliminados al despedirlo <strong>no se restauran</strong>. Deberás asignarlos manualmente si son necesarios.</p>',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Sí, readmitir',
                cancelButtonText: 'Cancelar',
                cancelButtonColor: '#d33',
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('form-readmitir-' + userId).submit();
                }
            });
        }
    </script>
    @endpush
</div>
