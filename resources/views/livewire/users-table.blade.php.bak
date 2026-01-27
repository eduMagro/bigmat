<div class="max-md:hidden" x-data="{ editandoUserId: null }">
    <style>
        [x-cloak] {
            display: none !important;
        }
    </style>

    <x-tabla.filtros-aplicados :filtros="$filtrosActivos" />

    <div class="w-full max-w-full overflow-x-auto bg-white shadow-lg rounded-lg mt-4">
        <table class="w-full border border-gray-300 rounded-lg">
            <thead class="bg-blue-500 text-white">
                <tr class="text-center text-xs uppercase">
                    <x-tabla.encabezado-ordenable campo="id" :sortActual="$sort" :orderActual="$order" texto="ID" />
                    <x-tabla.encabezado-ordenable campo="nombre_completo" :sortActual="$sort" :orderActual="$order" texto="Nombre" />
                    <x-tabla.encabezado-ordenable campo="nombre_completo" :sortActual="$sort" :orderActual="$order" texto="Primer Apellido" />
                    <x-tabla.encabezado-ordenable campo="nombre_completo" :sortActual="$sort" :orderActual="$order" texto="Segundo Apellido" />
                    <x-tabla.encabezado-ordenable campo="email" :sortActual="$sort" :orderActual="$order" texto="Email" />
                    <th class="p-2 border">Móvil Personal</th>
                    <th class="p-2 border">Móvil Empresa</th>
                    <x-tabla.encabezado-ordenable campo="numero_corto" :sortActual="$sort" :orderActual="$order" texto="Nº Corporativo" />
                    <x-tabla.encabezado-ordenable campo="dni" :sortActual="$sort" :orderActual="$order" texto="DNI" />
                    <x-tabla.encabezado-ordenable campo="empresa" :sortActual="$sort" :orderActual="$order" texto="Empresa" />
                    <x-tabla.encabezado-ordenable campo="rol" :sortActual="$sort" :orderActual="$order" texto="Rol" />
                    <x-tabla.encabezado-ordenable campo="categoria" :sortActual="$sort" :orderActual="$order" texto="Categoría" />
                    <x-tabla.encabezado-ordenable campo="estado" :sortActual="$sort" :orderActual="$order" texto="Estado" />
                    <th class="p-2 border"></th>
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
                        <input type="text" wire:model.live.debounce.300ms="movil_personal" placeholder="Móvil"
                            class="w-full text-xs px-2 py-1 border rounded text-blue-900 focus:border-blue-900 focus:ring-1 focus:ring-blue-900 focus:outline-none" />
                    </th>
                    <th class="p-1 border">
                        <input type="text" wire:model.live.debounce.300ms="movil_empresa" placeholder="Móvil Emp."
                            class="w-full text-xs px-2 py-1 border rounded text-blue-900 focus:border-blue-900 focus:ring-1 focus:ring-blue-900 focus:outline-none" />
                    </th>
                    <th class="p-1 border">
                        <input type="text" wire:model.live.debounce.300ms="numero_corto" placeholder="Nº Corp."
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
                    <th class="p-1 border">
                        <select wire:model.live="estado"
                            class="w-full text-xs px-2 py-1 border rounded text-blue-900 focus:border-blue-900 focus:ring-1 focus:ring-blue-900 focus:outline-none">
                            <option value="">Todos</option>
                            <option value="activo">Activo</option>
                            <option value="baja">Baja</option>
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

                            {{-- Botón exportar Excel --}}
                            <a href="{{ route('users.verExportar', request()->query()) }}" title="Descarga los registros en Excel"
                                class="bg-green-600 hover:bg-green-700 text-white rounded text-xs flex items-center justify-center">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48" class="h-6 w-8">
                                    <path fill="#21A366"
                                        d="M6 8c0-1.1.9-2 2-2h32c1.1 0 2 .9 2 2v32c0 1.1-.9 2-2 2H8c-1.1 0-2-.9-2-2V8z" />
                                    <path fill="#107C41" d="M8 8h16v32H8c-1.1 0-2-.9-2-2V10c0-1.1.9-2 2-2z" />
                                    <path fill="#33C481" d="M24 8h16v32H24z" />
                                    <path fill="#fff"
                                        d="M17.2 17h3.6l3.1 5.3 3.1-5.3h3.6l-5.1 8.4 5.3 8.6h-3.7l-3.3-5.6-3.3 5.6h-3.7l5.3-8.6-5.1-8.4z" />
                                </svg>
                            </a>
                        </div>
                    </th>
                </tr>
            </thead>

            <tbody class="text-gray-700 text-sm">
                @forelse ($registrosUsuarios as $user)
                <tr tabindex="0"
                    wire:key="user-{{ $user->id }}"
                    x-data="{
                        id: {{ $user->id }},
                        usuario: @js($user),
                        original: JSON.parse(JSON.stringify(@js($user))),
                        get editando() { return editandoUserId === this.id },
                        abrirEdicion() {
                            editandoUserId = this.id;
                        },
                        cerrarEdicion() {
                            if (editandoUserId === this.id) {
                                editandoUserId = null;
                            }
                        },
                        cancelarEdicion() {
                            this.usuario = JSON.parse(JSON.stringify(this.original));
                            this.cerrarEdicion();
                        }
                    }"
                    @dblclick="if(!$event.target.closest('input, select, button, a')) {
                        if(!editando) {
                            abrirEdicion();
                        } else {
                            cancelarEdicion();
                        }
                    }"
                    @keydown.enter.stop="if(editando) { guardarCambios(usuario); cerrarEdicion(); }"
                    :class="{
                        'bg-yellow-100': editando,
                        'hover:bg-blue-50': !editando
                    }"
                    class="border-b odd:bg-gray-100 even:bg-gray-50 cursor-pointer text-xs uppercase transition-colors">

                    <td class="px-2 py-3 text-center border">{{ $user->id }}</td>

                    <td class="px-2 py-3 text-center border">
                        <span x-show="!editando">{{ $user->name }}</span>
                        <x-tabla.input x-cloak x-show="editando" x-model="usuario.name" placeholder="Nombre"
                            @keydown.enter.stop="guardarCambios(usuario)" />
                    </td>

                    <td class="px-2 py-3 text-center border">
                        <span x-show="!editando">{{ $user->primer_apellido }}</span>
                        <x-tabla.input x-cloak x-show="editando" x-model="usuario.primer_apellido" placeholder="Apellido 1"
                            @keydown.enter.stop="guardarCambios(usuario)" />
                    </td>

                    <td class="px-2 py-3 text-center border">
                        <span x-show="!editando">{{ $user->segundo_apellido ?? '-' }}</span>
                        <x-tabla.input x-cloak x-show="editando" x-model="usuario.segundo_apellido" placeholder="Apellido 2"
                            @keydown.enter.stop="guardarCambios(usuario)" />
                    </td>

                    <td class="px-2 py-3 text-center border">
                        <span x-show="!editando">{{ $user->email }}</span>
                        <x-tabla.input x-cloak x-show="editando" x-model="usuario.email"
                            @keydown.enter.stop="guardarCambios(usuario)" />
                    </td>

                    <td class="px-2 py-3 text-center border">
                        <span x-show="!editando">{{ $user->movil_personal }}</span>
                        <x-tabla.input x-cloak x-show="editando" x-model="usuario.movil_personal"
                            @keydown.enter.stop="guardarCambios(usuario)" />
                    </td>

                    <td class="px-2 py-3 text-center border">
                        <span x-show="!editando">{{ $user->movil_empresa }}</span>
                        <x-tabla.input x-cloak x-show="editando" x-model="usuario.movil_empresa"
                            @keydown.enter.stop="guardarCambios(usuario)" />
                    </td>

                    <td class="px-2 py-3 text-center border">
                        <span x-show="!editando">{{ $user->numero_corto ?? '-' }}</span>
                        <x-tabla.input x-cloak x-show="editando" x-model="usuario.numero_corto" maxlength="4"
                            placeholder="0000" @keydown.enter.stop="guardarCambios(usuario)" />
                    </td>

                    <td class="px-2 py-3 text-center border">
                        <span x-show="!editando">{{ $user->dni }}</span>
                        <x-tabla.input x-cloak x-show="editando" x-model="usuario.dni"
                            @keydown.enter.stop="guardarCambios(usuario)" />
                    </td>

                    <td class="px-2 py-3 text-center border">
                        <span x-show="!editando">{{ $user->empresa->nombre ?? 'Sin empresa' }}</span>
                        <x-tabla.select-edicion x-cloak x-show="editando" x-model="usuario.empresa_id"
                            @keydown.enter.stop="guardarCambios(usuario)">
                            <option value="">Selecciona empresa</option>
                            @foreach ($empresas as $empresa)
                            <option value="{{ $empresa->id }}">{{ $empresa->nombre }}</option>
                            @endforeach
                        </x-tabla.select-edicion>
                    </td>

                    <td class="px-2 py-3 text-center border">
                        <span x-show="!editando">{{ $user->rol }}</span>
                        <x-tabla.select-edicion x-cloak x-show="editando" x-model="usuario.rol">
                            <option value="">Selecciona rol</option>
                            <option value="oficina">Oficina</option>
                            <option value="operario">Operario</option>
                            <option value="transportista">Transportista</option>
                            <option value="visitante">Visitante</option>
                        </x-tabla.select-edicion>
                    </td>

                    <td class="px-2 py-3 text-center border">
                        <span x-show="!editando">{{ $user->categoria->nombre ?? 'Sin asignar' }}</span>
                        <x-tabla.select-edicion x-cloak x-show="editando" x-model="usuario.categoria_id">
                            <option value="">Selecciona cat.</option>
                            @foreach ($categorias as $categoria)
                            <option value="{{ $categoria->id }}">{{ ucfirst($categoria->nombre) }}</option>
                            @endforeach
                        </x-tabla.select-edicion>
                    </td>

                    <td class="px-2 py-3 text-center border">
                        @if ($user->isOnline())
                        <span class="text-green-600">En línea</span>
                        @else
                        <span class="text-gray-500">Desconectado</span>
                        @endif
                    </td>

                    <td class="px-2 py-3 text-center border">
                        <form action="{{ route('profile.generar.turnos', $user->id) }}" method="POST"
                            id="form-generar-turnos-{{ $user->id }}">
                            @csrf
                            <input type="hidden" name="tipo_turno" id="tipo_turno_{{ $user->id }}">
                            <input type="hidden" name="turno_inicio" id="turno_inicio_{{ $user->id }}">
                            <input type="hidden" id="obra_id_input_{{ $user->id }}" name="obra_id">

                            <input type="hidden" name="agrupacion_turno_id" id="agrupacion_turno_id_{{ $user->id }}">

                            <button type="button"
                                class="w-full bg-indigo-500 hover:bg-indigo-600 text-white text-xs px-2 py-1 rounded"
                                onclick="confirmarGenerarTurnos({{ $user->id }}, obrasHierrosPacoReyes)">
                                Turnos
                            </button>
                        </form>
                    </td>

                    <td class="px-1 py-2 border text-xs font-bold">
                        <div class="flex items-center space-x-2 justify-center">
                            <!-- Mostrar solo en modo edición -->
                            <button x-show="editando" style="display: none;"
                                @click="guardarCambios(usuario); cerrarEdicion()"
                                class="w-6 h-6 bg-green-100 text-green-600 rounded hover:bg-green-200 flex items-center justify-center"
                                title="Guardar cambios">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M5 13l4 4L19 7" />
                                </svg>
                            </button>
                            <button x-show="editando" style="display: none;"
                                @click="cancelarEdicion()"
                                class="w-6 h-6 bg-red-100 text-red-600 rounded hover:bg-red-200 flex items-center justify-center"
                                title="Cancelar edición">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>

                            <!-- Mostrar solo cuando NO está en modo edición -->
                            <template x-if="!editando">
                                <div class="flex items-center space-x-2">
                                    <button @click="abrirEdicion()"
                                        class="w-6 h-6 bg-blue-100 text-blue-600 rounded hover:bg-blue-200 flex items-center justify-center"
                                        title="Editar">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4"
                                            fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </button>
                                    <x-tabla.boton-ver :href="route('users.show', $user->id)" target="_self" rel="noopener" />
                                    <a href="{{ route('users.edit', $user->id) }}" wire:navigate title="Configuración"
                                        class="w-6 h-6 bg-yellow-100 text-yellow-600 rounded hover:bg-yellow-200 flex items-center justify-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4"
                                            viewBox="0 0 24 24" fill="currentColor">
                                            <path fill-rule="evenodd"
                                                d="M11.983 2c.529 0 .96.388 1.025.912l.118.998a7.97 7.97 0 0 1 1.575.645l.892-.516a1.033 1.033 0 0 1 1.4.375l.503.87a1.03 1.03 0 0 1-.208 1.286l-.76.625c.063.32.104.648.123.982l.994.168a1.032 1.032 0 0 1 .873 1.017v1.003a1.032 1.032 0 0 1-.873 1.017l-.994.168a8.114 8.114 0 0 1-.123.982l.76.625c.361.296.463.808.208 1.286l-.503.87a1.033 1.033 0 0 1-1.4.375l-.892-.516a7.968 7.968 0 0 1-1.575.645l-.118.998a1.032 1.032 0 0 1-1.025.912h-1.002a1.032 1.032 0 0 1-1.025-.912l-.118-.998a7.97 7.97 0 0 1-1.575-.645l-.892.516a1.033 1.033 0 0 1-1.4-.375l-.503-.87a1.03 1.03 0 0 1 .208-1.286l.76-.625a8.114 8.114 0 0 1-.123-.982l-.994-.168a1.032 1.032 0 0 1-.873 1.017v-1.003a1.032 1.032 0 0 1 .873-1.017l.994-.168c.019-.334.06-.662.123-.982l-.76-.625a1.03 1.03 0 0 1-.208-1.286l.503-.87a1.033 1.033 0 0 1 1.4-.375l.892.516c.494-.29 1.02-.52 1.575-.645l.118-.998A1.032 1.032 0 0 1 10.981 2h1.002zm-1.232 10a2.25 2.25 0 1 0 4.5 0 2.25 2.25 0 0 0-4.5 0z"
                                                clip-rule="evenodd" />
                                        </svg>
                                    </a>
                                </div>
                            </template>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="15" class="text-center py-4 text-gray-500">No hay usuarios disponibles.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <x-tabla.paginacion-livewire :paginador="$registrosUsuarios" />

    <script>
        const obrasHierrosPacoReyes = @json($obras ?? []);
        const plantillasDetalle = @json($plantillasParaModal);

        function generarHtmlPlantillas() {
            const diasOrden = [1, 2, 3, 4, 5, 6, 0];
            let html = '';

            plantillasDetalle.forEach(function(p) {
                let diasHtml = '';
                diasOrden.forEach(function(d) {
                    const dia = p.dias[d];
                    const tieneTurno = dia.turno !== null;
                    const bg = tieneTurno ? (dia.color || '#6366f1') : '#e5e7eb';
                    const txt = tieneTurno ? '#fff' : '#9ca3af';
                    const turnoNombre = tieneTurno ? dia.turno : '-';
                    diasHtml += '<div style="display:inline-flex;flex-direction:column;align-items:center;min-width:32px;margin:0 2px;">';
                    diasHtml += '<span style="font-size:9px;color:#6b7280;">' + dia.abrev + '</span>';
                    diasHtml += '<span style="background:' + bg + ';color:' + txt + ';border-radius:3px;padding:1px 4px;font-size:10px;">' + turnoNombre + '</span>';
                    diasHtml += '</div>';
                });

                html += '<div class="plantilla-opcion" data-id="' + p.id + '" style="border:2px solid #e5e7eb;border-radius:8px;padding:10px;margin-bottom:8px;cursor:pointer;background:#fff;">';
                html += '<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;">';
                html += '<strong style="color:#1f2937;">' + p.nombre + '</strong>';
                html += '</div>';
                if (p.descripcion) {
                    html += '<p style="font-size:11px;color:#6b7280;margin-bottom:6px;">' + p.descripcion + '</p>';
                }
                html += '<div style="display:flex;justify-content:center;">' + diasHtml + '</div>';
                html += '</div>';
            });

            return html;
        }

        function confirmarGenerarTurnos(userId, obras) {
            let plantillaSeleccionada = null;
            const plantillasHtml = generarHtmlPlantillas();

            Swal.fire({
                title: "Seleccionar plantilla",
                html: '<p style="margin-bottom:10px;color:#6b7280;font-size:13px;">Elige la plantilla para generar turnos:</p><div id="plantillas-box" style="max-height:300px;overflow-y:auto;text-align:left;">' + plantillasHtml + '</div>',
                width: 480,
                showCancelButton: true,
                confirmButtonText: 'Siguiente',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#6366f1',
                didOpen: function() {
                    document.querySelectorAll('.plantilla-opcion').forEach(function(card) {
                        card.onclick = function() {
                            plantillaSeleccionada = this.dataset.id;
                            document.querySelectorAll('.plantilla-opcion').forEach(function(c) {
                                c.style.borderColor = '#e5e7eb';
                                c.style.background = '#fff';
                            });
                            this.style.borderColor = '#6366f1';
                            this.style.background = '#eef2ff';
                        };
                    });
                },
                preConfirm: function() {
                    if (!plantillaSeleccionada) {
                        Swal.showValidationMessage('Selecciona una plantilla');
                        return false;
                    }
                    return plantillaSeleccionada;
                }
            }).then(function(result) {
                if (!result.isConfirmed) return;

                document.getElementById("agrupacion_turno_id_" + userId).value = result.value;

                let opcionesObra = '';
                obras.forEach(function(obra) {
                    opcionesObra += '<option value="' + obra.id + '">' + obra.obra + '</option>';
                });

                Swal.fire({
                    title: "Seleccionar obra",
                    html: '<p style="margin-bottom:10px;color:#6b7280;font-size:13px;">Obra asignada para los turnos:</p><select id="select-obra" style="width:100%;padding:8px;border:1px solid #d1d5db;border-radius:6px;">' + opcionesObra + '</select>',
                    icon: "info",
                    showCancelButton: true,
                    confirmButtonText: "Generar turnos",
                    cancelButtonText: "Cancelar",
                    confirmButtonColor: "#6366f1",
                    preConfirm: function() {
                        const obraId = document.getElementById("select-obra").value;
                        if (!obraId) {
                            Swal.showValidationMessage("Selecciona una obra");
                        }
                        return obraId;
                    }
                }).then(function(resp) {
                    if (!resp.isConfirmed) return;
                    document.getElementById("obra_id_input_" + userId).value = resp.value;
                    document.getElementById("form-generar-turnos-" + userId).submit();
                });
            });
        }

        function guardarCambios(usuario) {
            fetch(`{{ url('/users') }}/${usuario.id}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        _method: 'PUT',
                        name: usuario.name,
                        primer_apellido: usuario.primer_apellido,
                        segundo_apellido: usuario.segundo_apellido,
                        email: usuario.email,
                        movil_personal: usuario.movil_personal,
                        movil_empresa: usuario.movil_empresa,
                        numero_corto: usuario.numero_corto,
                        dni: usuario.dni,
                        empresa_id: usuario.empresa_id,
                        rol: usuario.rol,
                        categoria_id: usuario.categoria_id
                    })
                })
                .then(async (response) => {
                    const contentType = response.headers.get('content-type');
                    let data = {};

                    if (contentType && contentType.includes('application/json')) {
                        data = await response.json();
                    } else {
                        const text = await response.text();
                        throw new Error("Respuesta inesperada del servidor: " + text.slice(0, 200));
                    }

                    if (response.ok && data.success) {
                        Swal.fire({
                            icon: "success",
                            title: "Usuario actualizado",
                            text: "Los cambios se han guardado exitosamente.",
                            timer: 2000,
                            showConfirmButton: false
                        }).then(() => {
                            window.location.reload();
                        });
                    } else {
                        let errorMsg = data.message || "Error al actualizar el usuario.";
                        if (data.errors) {
                            errorMsg = Object.values(data.errors).flat().join("<br>");
                        }
                        mostrarError(errorMsg, "Error al actualizar");
                    }
                })
                .catch((err) => {
                    console.error("Error en la solicitud fetch:", err);
                    mostrarError(err.message || "No se pudo actualizar el usuario. Inténtalo nuevamente.", "Error de conexión");
                });
        }
    </script>
</div>
