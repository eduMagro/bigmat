<div>
    <x-tabla.filtros-aplicados :filtros="$filtrosActivos" />

    <!-- Tabla de asignaciones -->
    <div class="w-full max-w-full overflow-x-auto bg-white shadow-lg rounded-lg">
        <table class="w-full border border-gray-300 rounded-lg text-xs uppercase text-center">
            <thead class="bg-blue-500 text-white">
                <tr>
                    <th class="p-2 border cursor-pointer" wire:click="sortBy('user_id')">
                        ID Empleado @if($sort === 'user_id'){{ $order === 'asc' ? '↑' : '↓' }}@endif
                    </th>
                    <th class="p-2 border">Empleado</th>
                    <th class="p-2 border cursor-pointer" wire:click="sortBy('fecha')">
                        Fecha @if($sort === 'fecha'){{ $order === 'asc' ? '↑' : '↓' }}@endif
                    </th>
                    <th class="p-2 border cursor-pointer" wire:click="sortBy('obra_id')">
                        Obra @if($sort === 'obra_id'){{ $order === 'asc' ? '↑' : '↓' }}@endif
                    </th>
                    <th class="p-2 border cursor-pointer" wire:click="sortBy('turno_id')">
                        Turno @if($sort === 'turno_id'){{ $order === 'asc' ? '↑' : '↓' }}@endif
                    </th>
                    <th class="p-2 border cursor-pointer" wire:click="sortBy('entrada')">
                        Entrada @if($sort === 'entrada'){{ $order === 'asc' ? '↑' : '↓' }}@endif
                    </th>
                    <th class="p-2 border cursor-pointer" wire:click="sortBy('salida')">
                        Salida @if($sort === 'salida'){{ $order === 'asc' ? '↑' : '↓' }}@endif
                    </th>
                    <th class="p-2 border">Resumen</th>
                    <th class="p-2 border">Acciones</th>
                </tr>
                <tr class="text-center text-xs uppercase">
                    <th class="p-1 border">
                        <input type="text" wire:model.live.debounce.300ms="user_id" placeholder="ID"
                            class="w-full text-xs px-2 py-1 border rounded text-blue-900 focus:border-blue-900 focus:ring-1 focus:ring-blue-900 focus:outline-none" />
                    </th>
                    <th class="p-1 border">
                        <input type="text" wire:model.live.debounce.300ms="empleado" placeholder="Empleado"
                            class="w-full text-xs px-2 py-1 border rounded text-blue-900 focus:border-blue-900 focus:ring-1 focus:ring-blue-900 focus:outline-none" />
                    </th>
                    <th class="p-1 border">
                        <div class="flex flex-row space-x-1">
                            <input type="date" wire:model.live.debounce.300ms="fecha_inicio"
                                class="text-xs w-full px-2 py-1 border rounded text-blue-900 focus:border-blue-900 focus:ring-1 focus:ring-blue-900 focus:outline-none" />
                            <input type="date" wire:model.live.debounce.300ms="fecha_fin"
                                class="text-xs w-full px-2 py-1 border rounded text-blue-900 focus:border-blue-900 focus:ring-1 focus:ring-blue-900 focus:outline-none" />
                        </div>
                    </th>
                    <th class="p-1 border">
                        <input type="text" wire:model.live.debounce.300ms="obra" placeholder="Obra"
                            class="w-full text-xs px-2 py-1 border rounded text-blue-900 focus:border-blue-900 focus:ring-1 focus:ring-blue-900 focus:outline-none" />
                    </th>
                    <th class="p-1 border">
                        <input type="text" wire:model.live.debounce.300ms="turno" placeholder="Turno"
                            class="w-full text-xs px-2 py-1 border rounded text-blue-900 focus:border-blue-900 focus:ring-1 focus:ring-blue-900 focus:outline-none" />
                    </th>
                    <th class="p-1 border">
                        <input type="text" wire:model.live.debounce.300ms="entrada" placeholder="Entrada"
                            class="w-full text-xs px-2 py-1 border rounded text-blue-900 focus:border-blue-900 focus:ring-1 focus:ring-blue-900 focus:outline-none" />
                    </th>
                    <th class="p-1 border">
                        <input type="text" wire:model.live.debounce.300ms="salida" placeholder="Salida"
                            class="w-full text-xs px-2 py-1 border rounded text-blue-900 focus:border-blue-900 focus:ring-1 focus:ring-blue-900 focus:outline-none" />
                    </th>
                    <th class="p-1 border">

                    </th>
                    <th class="p-1 border text-center align-middle">
                        <div class="flex justify-center gap-2 items-center h-full">
                            {{-- ♻️ Botón reset --}}
                            <button type="button" wire:click="limpiarFiltros"
                                class="bg-yellow-500 hover:bg-yellow-600 text-white px-2 py-1 rounded text-xs flex items-center justify-center"
                                title="Restablecer filtros">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M4 4v5h.582M20 20v-5h-.581M4.582 9A7.5 7.5 0 0112 4.5a7.5 7.5 0 016.418 3.418M19.418 15A7.5 7.5 0 0112 19.5a7.5 7.5 0 01-6.418-3.418" />
                                </svg>
                            </button>

                            {{-- 📤 Botón exportar Excel --}}
                            <a href="{{ route('asignaciones-turnos.exportar', request()->query()) }}" title="Descarga los registros en Excel"
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
            <tbody class="text-gray-700">
                @forelse ($asignaciones as $asignacion)
                    <tr tabindex="0" x-data="{
                        editando: false,
                        asignacion: @js($asignacion),
                        original: JSON.parse(JSON.stringify(@js($asignacion)))
                    }"
                        @dblclick="if(!$event.target.closest('input')) {
                          if(!editando) {
                            editando = true;
                          } else {
                            asignacion = JSON.parse(JSON.stringify(original));
                            editando = false;
                          }
                        }"
                        @keydown.enter.stop="guardarCambios(asignacion, original); editando = false"
                        :class="{ 'bg-yellow-100': editando }"
                        class="border-b odd:bg-gray-100 even:bg-gray-50 hover:bg-blue-200 cursor-pointer text-xs uppercase">

                        <td class="px-2 py-2 border">{{ $asignacion->user->id }}</td>
                        <td class="px-2 py-2 border">
                            {{ $asignacion->user->name ?? '—' }}
                            {{ $asignacion->user->primer_apellido ?? '—' }}
                            {{ $asignacion->user->segundo_apellido ?? '—' }}
                        </td>
                        <td class="px-2 py-2 border">
                            {{ \Carbon\Carbon::parse($asignacion->fecha)->format('d/m/Y') }}
                        </td>
                        <td class="px-2 py-2 border">{{ $asignacion->obra->obra ?? '—' }}</td>
                        <td class="px-2 py-2 border">
                            <template x-if="editando">
                                <select x-model="asignacion.turno_id"
                                    class="w-full px-2 py-1 border border-gray-300 rounded text-xs focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option value="">—</option>
                                    @foreach ($turnos as $turno)
                                        <option value="{{ $turno->id }}">{{ $turno->nombre }}</option>
                                    @endforeach
                                </select>
                            </template>
                            <template x-if="!editando">
                                <span>
                                    {{ $asignacion->turno->nombre ?? '—' }}
                                </span>
                            </template>
                        </td>

                        @php
                            $esperadaEntrada = $asignacion->turno->hora_entrada ?? null;
                            $esperadaSalida = $asignacion->turno->hora_salida ?? null;
                            $realEntrada = $asignacion->entrada;
                            $realSalida = $asignacion->salida;

                            $puntual = '—';

                            if ($esperadaEntrada && $realEntrada) {
                                $puntual = \Carbon\Carbon::parse($realEntrada)->lte(
                                    \Carbon\Carbon::parse($esperadaEntrada),
                                )
                                    ? '✅'
                                    : '❌';
                            } elseif ($esperadaEntrada && !$realEntrada) {
                                $puntual = '⚠️ No fichó';
                            }
                        @endphp

                        <!-- Entrada -->
                        <td class="px-2 py-2 border">
                            <template x-if="editando">
                                <div class="flex items-center gap-1 justify-center">
                                    <input type="time" x-model="asignacion.entrada"
                                        class="w-20 px-1 py-1 border border-gray-300 rounded text-xs focus:outline-none focus:ring-2 focus:ring-blue-500" />
                                    <button type="button" @click="asignacion.entrada = null"
                                        class="text-red-600 text-xs font-bold hover:text-red-800" title="Borrar">
                                        ❌
                                    </button>
                                </div>
                            </template>
                            <template x-if="!editando">
                                <span x-text="asignacion.entrada || '—'"></span>
                            </template>
                        </td>

                        <!-- Salida -->
                        <td class="px-2 py-2 border">
                            <template x-if="editando">
                                <div class="flex items-center gap-1 justify-center">
                                    <input type="time" x-model="asignacion.salida"
                                        class="w-20 px-1 py-1 border border-gray-300 rounded text-xs focus:outline-none focus:ring-2 focus:ring-blue-500" />
                                    <button type="button" @click="asignacion.salida = null"
                                        class="text-red-600 text-xs font-bold hover:text-red-800" title="Borrar">
                                        ❌
                                    </button>
                                </div>
                            </template>
                            <template x-if="!editando">
                                <span x-text="asignacion.salida || '—'"></span>
                            </template>
                        </td>

                        <td class="px-2 py-2 border text-xs font-bold">
                            {!! $puntual !!}
                        </td>
                        <td class="px-2 py-2 border text-xs font-bold">
                            <div class="flex items-center space-x-2 justify-center">
                                <!-- Mostrar solo en modo edición -->
                                <x-tabla.boton-guardar x-show="editando"
                                    @click="guardarCambios(asignacion, original); editando = false" />
                                <!-- Mostrar solo cuando NO está en modo edición -->
                                <template x-if="!editando">
                                    <div class="flex items-center space-x-2">
                                        <x-tabla.boton-ver :href="route('users.show', $asignacion->user->id)" />
                                    </div>
                                </template>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center py-4 text-gray-500">No hay asignaciones
                            disponibles.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <x-tabla.paginacion-livewire :paginador="$asignaciones" />

    <script>
        // Carga el mapa ID -> nombre del turno
        const turnosPorId = @json($turnos->pluck('nombre', 'id'));

        function guardarCambios(asignacionData, originalData) {
            let entrada = asignacionData.entrada;
            let salida = asignacionData.salida;

            if (entrada && entrada.length === 8) entrada = entrada.slice(0, 5);
            if (salida && salida.length === 8) salida = salida.slice(0, 5);

            fetch(`{{ url('/asignaciones-turno') }}/${asignacionData.id}/actualizar-horas`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        entrada,
                        salida
                    })
                })
                .then(async response => {
                    const data = await response.json();
                    if (response.ok && data.ok) {
                        Livewire.dispatch('refreshComponent');
                        Swal.fire("✅ Guardado", "Horas actualizadas correctamente.", "success");
                    } else {
                        Swal.fire("❌ Error", data.message || "No se pudo actualizar.", "error");
                        Object.assign(asignacionData, JSON.parse(JSON.stringify(originalData)));
                    }
                })
                .catch(error => {
                    console.error("❌ Error en Fetch:", error);
                    Object.assign(asignacionData, JSON.parse(JSON.stringify(originalData)));
                    Swal.fire("⚠️ Error de conexión", "No se pudo actualizar la asignación.", "error");
                });
        }
    </script>
</div>
